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
}
