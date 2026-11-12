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
            try {
                return $this->overpassProvider->search($city, $category, $search);
            } catch (RuntimeException $exception) {
                $fallback = $this->localRepository->findByFilters($category, $search, $city);
                if ($fallback === []) {
                    throw $exception;
                }

                return array_map(
                    static fn ($restaurant): array => [
                        'id' => 'local-' . $restaurant->getId(),
                        'provider' => 'local-fallback',
                        'providerId' => (string) $restaurant->getId(),
                        'name' => $restaurant->getName(),
                        'category' => $restaurant->getCategory(),
                        'city' => $restaurant->getCity(),
                        'address' => $restaurant->getAddress(),
                        'location' => null,
                        'rating' => $restaurant->getRating(),
                        'priceRange' => $restaurant->getPriceRange(),
                        'dietaryTags' => [],
                        'description' => $restaurant->getDescription(),
                        'image' => $restaurant->getImage(),
                        'website' => null,
                        'phone' => null,
                        'openingHours' => null,
                    ],
                    $fallback,
                );
            }
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
                'dietaryTags' => [],
                'description' => $restaurant->getDescription(),
                'image' => $restaurant->getImage(),
                'website' => null,
                'phone' => null,
                'openingHours' => null,
            ],
            $this->localRepository->findByFilters($category, $search),
        );
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        if (str_starts_with($id, 'osm-')) {
            return $this->overpassProvider->findByProviderId(substr($id, 4));
        }

        if (!str_starts_with($id, 'local-') || !ctype_digit(substr($id, 6))) {
            return null;
        }

        foreach ($this->search(null, null, null) as $restaurant) {
            if ($restaurant['id'] === $id) {
                return $restaurant;
            }
        }

        return null;
    }
}
