<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RestaurantController extends AbstractController
{
    #[Route('/api/restaurants', name: 'api_restaurants', methods: ['GET'])]
    public function index(RestaurantRepository $restaurantRepository): JsonResponse
    {
        // Récupérer tous les restaurants depuis SQLite
        $restaurants = $restaurantRepository->findAll();

        // Transformer les objets Restaurant en tableaux JSON
        $data = [];

        foreach ($restaurants as $restaurant) {
            $data[] = [
                'id' => $restaurant->getId(),
                'name' => $restaurant->getName(),
                'category' => $restaurant->getCategory(),
                'city' => $restaurant->getCity(),
                'address' => $restaurant->getAddress(),
                'rating' => $restaurant->getRating(),
                'priceRange' => $restaurant->getPriceRange(),
                'description' => $restaurant->getDescription(),
                'image' => $restaurant->getImage(),
            ];
        }

        return $this->json($data);
    }

    
    #[Route('/api/restaurants/{id}', name: 'api_restaurant_show', methods: ['GET'])]
    public function show(int $id, RestaurantRepository $restaurantRepository): JsonResponse
    {
        // Récupérer le restaurant correspondant à l'identifiant
        $restaurant = $restaurantRepository->find($id);

        // Vérifier si le restaurant existe
        if (!$restaurant) {
            return $this->json([
                'message' => 'Restaurant introuvable'
            ], 404);
        }

        // Transformer le restaurant en tableau JSON
        $data = [
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'category' => $restaurant->getCategory(),
            'city' => $restaurant->getCity(),
            'address' => $restaurant->getAddress(),
            'rating' => $restaurant->getRating(),
            'priceRange' => $restaurant->getPriceRange(),
            'description' => $restaurant->getDescription(),
            'image' => $restaurant->getImage(),
        ];

        return $this->json($data);
    }



}
