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
        $session->setCode($this->generateCode());
        $session->setName($name);
        $session->setCity($city);
        $session->setParticipants([[
            'id' => bin2hex(random_bytes(6)),
            'nickname' => $nickname,
            'joinedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]]);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    public function join(GroupSession $session, string $nickname): array
    {
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
        $participantIds = array_column($session->getParticipants(), 'id');
        if (!in_array($participantId, $participantIds, true)) {
            throw new RuntimeException('Participant inconnu.');
        }
        $all = $session->getPreferences();
        $all[$participantId] = $preferences;
        $session->setPreferences($all);
        $this->entityManager->flush();
    }

    /** @return list<array<string, mixed>> */
    public function recommendations(GroupSession $session): array
    {
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
                if (in_array($category, $avoided, true)) {
                    $score -= 100;
                    $reasons[] = 'Une préférence de groupe exclut cette cuisine.';
                } elseif (in_array($category, $liked, true)) {
                    $score += 3;
                    $reasons[] = 'Cuisine appréciée par un participant.';
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
        if (!in_array($participantId, array_column($session->getParticipants(), 'id'), true)) {
            throw new RuntimeException('Participant inconnu.');
        }
        $votes = $session->getVotes();
        $votes[$participantId] = $restaurantId;
        $session->setVotes($votes);
        $this->entityManager->flush();
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->repository->findByCode($code) !== null);
        return $code;
    }
}
