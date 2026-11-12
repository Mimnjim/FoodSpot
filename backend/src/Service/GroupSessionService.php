<?php

namespace App\Service;

use App\Entity\GroupSession;
use App\Repository\GroupSessionRepository;
use App\Restaurant\RestaurantSearchService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final class GroupSessionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupSessionRepository $repository,
        private readonly RestaurantSearchService $searchService,
    ) {
    }

    public function create(string $name, string $city, string $nickname): GroupSession
    {
        $session = new GroupSession();
        $participantId = bin2hex(random_bytes(6));
        $session->setCode($this->generateCode());
        $session->setName($name);
        $session->setCity($city);
        $session->setCreatorParticipantId($participantId);
        $session->setParticipants([[
            'id' => $participantId,
            'nickname' => $nickname,
            'joinedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]]);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    public function join(GroupSession $session, string $nickname): array
    {
        $this->assertActive($session);
        $participants = $session->getParticipants();
        $participants[] = [
            'id' => bin2hex(random_bytes(6)),
            'nickname' => $nickname,
            'joinedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
        $session->setParticipants($participants);
        $this->entityManager->flush();
        return $participants[array_key_last($participants)];
    }

    public function savePreferences(GroupSession $session, string $participantId, array $preferences): void
    {
        $this->assertActive($session);
        $participantIds = array_column($session->getParticipants(), 'id');
        if (!in_array($participantId, $participantIds, true)) {
            throw new RuntimeException('Participant inconnu.');
        }
        foreach (['likedCuisines', 'avoidedCuisines', 'restrictions'] as $field) {
            if (isset($preferences[$field]) && !is_array($preferences[$field])) {
                throw new RuntimeException("Le champ $field doit être une liste.");
            }
        }
        if (isset($preferences['budget']) && !in_array($preferences['budget'], ['1', '2', '3', '4'], true)) {
            throw new RuntimeException('Budget invalide.');
        }
        $all = $session->getPreferences();
        $all[$participantId] = $preferences;
        $session->setPreferences($all);
        $this->entityManager->flush();
    }

    public function leave(GroupSession $session, string $participantId): void
    {
        $this->assertActive($session);
        $participants = array_values(array_filter(
            $session->getParticipants(),
            static fn (array $participant): bool => $participant['id'] !== $participantId,
        ));
        if (count($participants) === count($session->getParticipants())) {
            throw new RuntimeException('Participant inconnu.');
        }
        if ($participants === []) {
            $session->setStatus('expired');
        } elseif ($session->getCreatorParticipantId() === $participantId) {
            $session->setCreatorParticipantId($participants[0]['id']);
        }
        $session->setParticipants($participants);
        $preferences = $session->getPreferences();
        unset($preferences[$participantId]);
        $session->setPreferences($preferences);
        $votes = $session->getVotes();
        unset($votes[$participantId]);
        $session->setVotes($votes);
        $this->entityManager->flush();
    }

    /** @return list<array<string, mixed>> */
    public function recommendations(GroupSession $session): array
    {
        $this->assertActive($session);
        $restaurants = $this->searchService->search($session->getCity(), null, null);
        $preferences = $session->getPreferences();
        $scored = [];
        foreach ($restaurants as $restaurant) {
            $score = 0;
            $reasons = [];
            foreach ($preferences as $preference) {
                $liked = array_map('strtolower', $preference['likedCuisines'] ?? []);
                $avoided = array_map('strtolower', $preference['avoidedCuisines'] ?? []);
                $category = strtolower((string) ($restaurant['category'] ?? ''));
                $categoryTokens = preg_split('/[;,]/', $category) ?: [$category];
                $matchesAvoided = $this->matchesAny($categoryTokens, $avoided);
                $matchesLiked = $this->matchesAny($categoryTokens, $liked);
                if ($matchesAvoided) {
                    $score -= 100;
                    $reasons[] = 'Une préférence de groupe exclut cette cuisine.';
                } elseif ($matchesLiked) {
                    $score += 3;
                    $reasons[] = 'Cuisine appréciée par un participant.';
                }
                $budget = $preference['budget'] ?? null;
                if ($budget !== null && $restaurant['priceRange'] !== null) {
                    $price = strlen((string) $restaurant['priceRange']);
                    if ($price > (int) $budget) {
                        $score -= 4;
                        $reasons[] = 'Le niveau de prix dépasse le budget indiqué.';
                    }
                } elseif ($budget !== null) {
                    $reasons[] = 'Prix non renseigné par OpenStreetMap.';
                }
                foreach ($preference['restrictions'] ?? [] as $restriction) {
                    $restriction = strtolower((string) $restriction);
                    $dietaryTags = array_map('strtolower', $restaurant['dietaryTags'] ?? []);
                    if ($restriction !== '' && !$this->matchesAny($dietaryTags, [$restriction])) {
                        $reasons[] = 'Compatibilité alimentaire non confirmée.';
                    }
                }
            }
            $restaurant['score'] = $score;
            $restaurant['reason'] = $reasons[0] ?? 'Compatible avec la zone choisie et les informations disponibles.';
            $scored[] = $restaurant;
        }
        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, 3);
    }

    public function vote(GroupSession $session, string $participantId, string $restaurantId): void
    {
        $this->assertActive($session);
        if (!in_array($participantId, array_column($session->getParticipants(), 'id'), true)) {
            throw new RuntimeException('Participant inconnu.');
        }
        if (!preg_match('/^(local|osm)-[A-Za-z0-9]+$/', $restaurantId)) {
            throw new RuntimeException('Restaurant invalide.');
        }
        if ($this->searchService->findById($restaurantId) === null) {
            throw new RuntimeException('Restaurant introuvable.');
        }
        $votes = $session->getVotes();
        $votes[$participantId] = $restaurantId;
        $session->setVotes($votes);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function decide(GroupSession $session, string $participantId, string $mode = 'auto'): array
    {
        $this->assertActive($session);
        if ($session->getCreatorParticipantId() !== $participantId) {
            throw new RuntimeException('Seul le créateur de la session peut prendre la décision.');
        }
        $votes = $session->getVotes();
        if ($votes === []) {
            throw new RuntimeException('Au moins un vote est nécessaire.');
        }

        $counts = array_count_values(array_values($votes));
        if ($mode === 'roulette') {
            $restaurantId = array_rand($counts);
        } else {
            arsort($counts);
            $topScore = reset($counts);
            $winners = array_keys(array_filter(
                $counts,
                static fn (int $score): bool => $score === $topScore,
            ));
            $restaurantId = $winners[0];
        }

        $restaurant = $this->searchService->findById((string) $restaurantId);
        if ($restaurant === null) {
            throw new RuntimeException('Le restaurant choisi est introuvable.');
        }

        $result = [
            'restaurantId' => (string) $restaurantId,
            'restaurantName' => $restaurant['name'],
            'restaurant' => $restaurant,
            'mode' => $mode,
            'votes' => $counts,
            'decidedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
        $session->setResult($result);
        $session->setStatus('decided');
        $this->entityManager->flush();

        return $result;
    }

    private function assertActive(GroupSession $session): void
    {
        if ($session->getStatus() === 'decided') {
            throw new RuntimeException('Cette session a déjà rendu sa décision.');
        }

        if ($session->getExpiresAt() <= new \DateTimeImmutable()) {
            $session->setStatus('expired');
            $this->entityManager->flush();
            throw new RuntimeException('Cette session a expiré.');
        }
    }

    /** @param list<string> $values @param list<string> $needles */
    private function matchesAny(array $values, array $needles): bool
    {
        foreach ($values as $value) {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($value, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->repository->findByCode($code) !== null);
        return $code;
    }
}
