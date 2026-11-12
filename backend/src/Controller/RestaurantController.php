<?php

namespace App\Controller;

use App\Restaurant\RestaurantSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RestaurantController extends AbstractController
{
    #[Route('/api/restaurants', name: 'api_restaurants', methods: ['GET'])]
    public function index(Request $request, RestaurantSearchService $searchService): JsonResponse
    {
        try {
            return $this->json([
                'restaurants' => $searchService->search(
                    $request->query->get('city'),
                    $request->query->get('category'),
                    $request->query->get('search'),
                ),
                'attribution' => '© OpenStreetMap contributors',
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 503);
        }
    }

    #[Route('/api/restaurants/{id}', name: 'api_restaurant_show', methods: ['GET'])]
    public function show(string $id, RestaurantSearchService $searchService): JsonResponse
    {
        if (!str_starts_with($id, 'local-')) {
            return $this->json(['message' => 'Les détails OpenStreetMap sont disponibles depuis les résultats de recherche.'], 404);
        }

        $restaurant = array_values(array_filter(
            $searchService->search(null, null, null),
            static fn (array $item): bool => $item['id'] === $id,
        ))[0] ?? null;

        return $restaurant === null
            ? $this->json(['message' => 'Restaurant introuvable'], 404)
            : $this->json($restaurant);
    }
}
