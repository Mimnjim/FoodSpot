<?php

namespace App\Restaurant;

use App\Repository\RestaurantRepository;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class RestaurantSearchService
{
    public function __construct(
        private readonly RestaurantProviderInterface $overpassProvider,
        private readonly RestaurantRepository $localRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $city, ?string $category, ?string $search): array
    {
        $cacheKey = 'restaurant_search_v2_' . hash('sha256', implode('|', [
            trim((string) $city),
            trim((string) $category),
            trim((string) $search),
        ]));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city, $category, $search): array {
            $item->expiresAfter(3600);
            $restaurants = $this->searchUncached($city, $category, $search);
            foreach ($restaurants as $restaurant) {
                $this->cache->get(
                    'osm_search_detail_v1_' . hash('sha256', (string) $restaurant['providerId']),
                    function (ItemInterface $detailItem) use ($restaurant): array {
                        $detailItem->expiresAfter(3600);
                        return $restaurant;
                    },
                );
            }
            return $restaurants;
        });
    }

    private function searchUncached(?string $city, ?string $category, ?string $search): array
    {
        $city = trim((string) $city);
        if ($city !== '') {
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
            $providerId = substr($id, 4);
            $detailKey = 'osm_search_detail_v1_' . hash('sha256', $providerId);
            $cachedDetail = $this->cache->get(
                $detailKey,
                function (ItemInterface $item): ?array {
                    $item->expiresAfter(1);
                    return null;
                },
            );
            if ($cachedDetail !== null) {
                return $cachedDetail;
            }

            $restaurant = $this->overpassProvider->findByProviderId($providerId);
            if ($restaurant !== null) {
                return $restaurant;
            }

            $cacheKey = 'osm_search_detail_v1_' . hash('sha256', $providerId);
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($providerId): ?array {
                $item->expiresAfter(3600);
                $cities = ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux'];
                foreach ($cities as $city) {
                    foreach ($this->searchUncached($city, null, null) as $restaurant) {
                        if ($restaurant['id'] === 'osm-' . $providerId) {
                            return $restaurant;
                        }
                    }
                }
                return null;
            });
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
