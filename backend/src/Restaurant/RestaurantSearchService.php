<?php

namespace App\Restaurant;

use App\Repository\RestaurantRepository;
use RuntimeException;

final class RestaurantSearchService
{
    public function __construct(
        private readonly RestaurantProviderInterface $overpassProvider,
        private readonly RestaurantRepository $localRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $city, ?string $category, ?string $search): array
    {
        if (trim((string) $city) !== '') {
            return $this->overpassProvider->search($city, $category, $search);
        }

        return array_map(
            static fn ($restaurant): array => [
                'id' => 'local-' . $restaurant->getId(),
                'provider' => 'local',
                'providerId' => (string) $restaurant->getId(),
                'name' => $restaurant->getName(),
                'category' => $restaurant->getCategory(),
                'city' => $restaurant->getCity(),
                'address' => $restaurant->getAddress(),
                'location' => null,
                'rating' => $restaurant->getRating(),
                'priceRange' => $restaurant->getPriceRange(),
                'description' => $restaurant->getDescription(),
                'image' => $restaurant->getImage(),
                'website' => null,
                'phone' => null,
                'openingHours' => null,
            ],
            $this->localRepository->findByFilters($category, $search),
        );
    }
}
