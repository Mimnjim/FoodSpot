<?php

namespace App\Controller;

use App\Entity\GroupSession;
use App\Repository\GroupSessionRepository;
use App\Service\GroupSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GroupSessionController extends AbstractController
{
    #[Route('/api/group-sessions', name: 'api_group_session_create', methods: ['POST'])]
    public function create(Request $request, GroupSessionService $service): JsonResponse
    {
        $data = $this->jsonBody($request);
        foreach (['name', 'city', 'nickname'] as $field) {
            if (!is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                return $this->json(['message' => "Le champ $field est requis."], 400);
            }
        }

        if (strlen(trim($data['name'])) > 120 || strlen(trim($data['city'])) > 120 || strlen(trim($data['nickname'])) > 60) {
            return $this->json(['message' => 'Les champs dépassent la longueur autorisée.'], 400);
        }
        $session = $service->create(trim($data['name']), trim($data['city']), trim($data['nickname']));
        return $this->json($this->serialize($session), 201);
    }

    #[Route('/api/group-sessions/{code}', name: 'api_group_session_show', methods: ['GET'])]
    public function show(string $code, GroupSessionRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $session = $repository->findByCode($code);
        if ($session !== null && $session->getStatus() === 'collecting' && $session->getExpiresAt() <= new \DateTimeImmutable()) {
            $session->setStatus('expired');
            $entityManager->flush();
        }
        return $session === null
            ? $this->json(['message' => 'Session introuvable.'], 404)
            : $this->json($this->serialize($session));
    }

    #[Route('/api/group-sessions/{code}/participants', name: 'api_group_session_join', methods: ['POST'])]
    public function join(string $code, Request $request, GroupSessionRepository $repository, GroupSessionService $service): JsonResponse
    {
        $session = $repository->findByCode($code);
        $data = $this->jsonBody($request);
        if ($session === null) {
            return $this->json(['message' => 'Session introuvable.'], 404);
        }
        if (!is_string($data['nickname'] ?? null) || trim($data['nickname']) === '') {
            return $this->json(['message' => 'Le pseudonyme est requis.'], 400);
        }
        if (strlen(trim($data['nickname'])) > 60) {
            return $this->json(['message' => 'Le pseudonyme est trop long.'], 400);
        }
        try {
            return $this->json($service->join($session, trim($data['nickname'])), 201);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    #[Route('/api/group-sessions/{code}/preferences', name: 'api_group_session_preferences', methods: ['PUT'])]
    public function preferences(string $code, Request $request, GroupSessionRepository $repository, GroupSessionService $service): JsonResponse
    {
        $session = $repository->findByCode($code);
        $data = $this->jsonBody($request);
        if ($session === null) {
            return $this->json(['message' => 'Session introuvable.'], 404);
        }
        if (!is_string($data['participantId'] ?? null) || !is_array($data['preferences'] ?? null)) {
            return $this->json(['message' => 'participantId et preferences sont requis.'], 400);
        }
        try {
            $service->savePreferences($session, $data['participantId'], $data['preferences']);
            return $this->json(['message' => 'Préférences enregistrées.']);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    #[Route('/api/group-sessions/{code}/recommendations', name: 'api_group_session_recommendations', methods: ['GET'])]
    public function recommendations(string $code, GroupSessionRepository $repository, GroupSessionService $service): JsonResponse
    {
        $session = $repository->findByCode($code);
        if ($session === null) {
            return $this->json(['message' => 'Session introuvable.'], 404);
        }
        try {
            return $this->json(['recommendations' => $service->recommendations($session)]);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 503);
        }
    }

    #[Route('/api/group-sessions/{code}/votes', name: 'api_group_session_vote', methods: ['POST'])]
    public function vote(string $code, Request $request, GroupSessionRepository $repository, GroupSessionService $service): JsonResponse
    {
        $session = $repository->findByCode($code);
        $data = $this->jsonBody($request);
        if ($session === null) {
            return $this->json(['message' => 'Session introuvable.'], 404);
        }

        if (!is_string($data['participantId'] ?? null) || !is_string($data['restaurantId'] ?? null)) {
            return $this->json(['message' => 'participantId et restaurantId sont requis.'], 400);
        }
        try {
            $service->vote($session, $data['participantId'], $data['restaurantId']);
            return $this->json(['message' => 'Vote enregistré.']);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    #[Route('/api/group-sessions/{code}/decision', name: 'api_group_session_decision', methods: ['POST'])]
    public function decision(string $code, Request $request, GroupSessionRepository $repository, GroupSessionService $service): JsonResponse
    {
        $session = $repository->findByCode($code);
        $data = $this->jsonBody($request);
        if ($session === null) {
            return $this->json(['message' => 'Session introuvable.'], 404);
        }
        $mode = $data['mode'] ?? 'auto';
        if (!is_string($mode) || !in_array($mode, ['auto', 'roulette'], true)) {
            return $this->json(['message' => 'Le mode doit être auto ou roulette.'], 400);
        }
        try {
            return $this->json(['result' => $service->decide($session, $mode)]);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    /** @return array<string, mixed> */
    private function jsonBody(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        return is_array($data) ? $data : [];
    }

    /** @return array<string, mixed> */
    private function serialize(GroupSession $session): array
    {
        return [
            'code' => $session->getCode(),
            'name' => $session->getName(),
            'city' => $session->getCity(),
            'status' => $session->getStatus(),
            'expiresAt' => $session->getExpiresAt()->format(DATE_ATOM),
            'participants' => $session->getParticipants(),
            'preferences' => $session->getPreferences(),
            'votes' => $session->getVotes(),
            'result' => $session->getResult(),
        ];
    }
}
