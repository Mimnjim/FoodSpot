<?php

namespace App\Restaurant;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class OverpassRestaurantProvider implements RestaurantProviderInterface
{
    private const MAX_RESULTS = 50;
    private const CACHE_SECONDS = 3600;
    private const HTTP_TIMEOUT = 3;

    private const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ];

    public function __construct(
        private readonly RestaurantNormalizer $normalizer,
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function search(?string $city, ?string $category, ?string $search): array
    {
        $city = trim((string) $city);
        if ($city === '') {
            throw new RuntimeException('Une ville est nécessaire pour une recherche OpenStreetMap.');
        }

        if ($category === null && $search === null) {
            $nominatim = $this->requestNominatim($city);
            if ($nominatim !== []) {
                $elements = array_values(array_filter(
                    $nominatim,
                    static fn (array $element): bool => isset($element['tags']['name'])
                        && trim((string) $element['tags']['name']) !== '',
                ));
                usort($elements, [$this, 'comparePopularity']);
                $results = array_map(
                    fn (array $element): array => $this->normalizer->normalize($element, $city),
                    $this->diversifyByCuisine($elements),
                );
                $results = array_slice($results, 0, self::MAX_RESULTS);
                return $this->enrichCollectionWithPanoramax($results);
            }
        }

        $query = $this->buildQuery($city, $category, $search);
        $cacheKey = 'overpass_v5_' . hash('sha256', $query);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $city): array {
            $item->expiresAfter(self::CACHE_SECONDS);
            $payload = $this->request($query, $city);

            $elements = array_values(array_filter(
                $payload['elements'],
                static fn (array $element): bool => isset($element['tags']['name'])
                    && trim((string) $element['tags']['name']) !== '',
            ));
            usort($elements, [$this, 'comparePopularity']);

            $results = array_map(
                fn (array $element): array => $this->normalizer->normalize($element, $city),
                $this->diversifyByCuisine($elements),
            );

            $results = array_slice($results, 0, self::MAX_RESULTS);
            return $this->enrichCollectionWithPanoramax($results);
        });
    }

    public function findByProviderId(string $providerId): ?array
    {
        if (!ctype_digit($providerId)) {
            return null;
        }

        $detail = $this->cache->get(
            'osm_search_detail_v1_' . hash('sha256', $providerId),
            function (ItemInterface $item): ?array {
                $item->expiresAfter(1);
                return null;
            },
        );
        if ($detail !== null) {
            return $detail;
        }

        $nominatimDetail = $this->requestNominatimPlaceDetail($providerId);
        if ($nominatimDetail !== null) {
            $nominatimDetail = $this->enrichWithPanoramax($nominatimDetail);
            return $nominatimDetail;
        }

        return null;
    }

    private function buildQuery(string $city, ?string $category, ?string $search): string
    {
        $area = 'area["name"="' . $this->escape($city) . '"]["boundary"="administrative"]->.searchArea;';
        $filters = '["amenity"="restaurant"]';
        if ($category !== null && $category !== '' && $category !== 'Toutes') {
            $filters .= '["cuisine"~"' . $this->escape($this->cuisinePattern($category)) . '",i]';
        }
        if ($search !== null && trim($search) !== '') {
            $filters .= '["name"~"' . $this->escape(trim($search)) . '",i]';
        }

        return '[out:json][timeout:3];' . $area
            . '(nwr' . $filters . '(area.searchArea););out 120 center tags;';
    }

    private function cuisinePattern(string $category): string
    {
        $normalized = mb_strtolower(trim($category), 'UTF-8');

        return match ($normalized) {
            'asiatique', 'asian', 'asia' => 'asian|chinese|japanese|korean|thai|vietnamese|indian|asian',
            'japonais', 'japanese' => 'japanese|sushi',
            'italien', 'italian' => 'italian|pizza',
            'français', 'francais', 'french' => 'french',
            default => $normalized,
        };
    }

    private function escape(string $value): string
    {
        return addcslashes(preg_quote($value, '/'), '\\"');
    }

    private function comparePopularity(array $left, array $right): int
    {
        return $this->popularityScore($right) <=> $this->popularityScore($left);
    }

    private function popularityScore(array $element): int
    {
        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $score = 0;
        foreach ([
            'website',
            'contact:website',
            'phone',
            'contact:phone',
            'opening_hours',
            'addr:housenumber',
            'addr:street',
            'wikipedia',
        ] as $tag) {
            if (isset($tags[$tag]) && trim((string) $tags[$tag]) !== '') {
                $score += 2;
            }
        }
        if (isset($tags['cuisine']) && trim((string) $tags['cuisine']) !== '') {
            $score += 3;
        }
        if (isset($tags['image']) || isset($tags['brand'])) {
            $score += 1;
        }
        return $score;
    }

    private function diversifyByCuisine(array $elements): array
    {
        $groups = [];
        foreach ($elements as $element) {
            $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
            $cuisine = mb_strtolower(trim((string) ($tags['cuisine'] ?? 'restaurant')), 'UTF-8');
            $category = $cuisine !== '' ? explode(';', $cuisine)[0] : 'restaurant';
            $groups[$category][] = $element;
        }

        $diversified = [];
        $cursor = 0;
        while (count($diversified) < self::MAX_RESULTS) {
            $added = false;
            foreach ($groups as $category => $group) {
                $index = $cursor % max(1, count($group));
                if (isset($group[$index])) {
                    $diversified[] = $group[$index];
                    unset($groups[$category][$index]);
                    $groups[$category] = array_values($groups[$category]);
                    $added = true;
                }
                if (count($diversified) >= self::MAX_RESULTS) {
                    break;
                }
            }
            if (!$added) {
                break;
            }
            $cursor++;
        }

        $seen = [];
        $unique = [];
        foreach ($diversified as $element) {
            $id = (string) ($element['id'] ?? '');
            if ($id !== '' && !isset($seen[$id])) {
                $seen[$id] = true;
                $unique[] = $element;
            }
        }
        return array_slice($unique, 0, self::MAX_RESULTS);
    }

    /** @return array<string, mixed> */
    private function enrichCollectionWithPanoramax(array $restaurants): array
    {
        $coordinates = array_filter(array_map(
            static fn (array $restaurant): ?array => is_array($restaurant['location'] ?? null)
                ? $restaurant['location']
                : null,
            $restaurants,
        ));
        if (count($coordinates) < 2) {
            return $restaurants;
        }

        $latitudes = array_column($coordinates, 'latitude');
        $longitudes = array_column($coordinates, 'longitude');
        $padding = 0.002;
        $bbox = implode(',', [
            min($longitudes) - $padding,
            min($latitudes) - $padding,
            max($longitudes) + $padding,
            max($latitudes) + $padding,
        ]);
        $url = 'https://panoramax.ign.fr/api/search?' . http_build_query([
            'bbox' => $bbox,
            'limit' => 100,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                'timeout' => 2,
            ]);
            $payload = $response->toArray();
        } catch (HttpExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface) {
            return $restaurants;
        }

        $features = is_array($payload['features'] ?? null) ? $payload['features'] : [];
        foreach ($restaurants as &$restaurant) {
            if ($restaurant['image'] !== null) {
                continue;
            }
            $location = $restaurant['location'] ?? null;
            if (!is_array($location)) {
                continue;
            }

            $nearest = null;
            $nearestDistance = null;
            foreach ($features as $feature) {
                $featureCoordinates = $feature['geometry']['coordinates'] ?? null;
                if (!is_array($featureCoordinates) || count($featureCoordinates) < 2) {
                    continue;
                }
                $distance = $this->distanceInMeters(
                    (float) $location['latitude'],
                    (float) $location['longitude'],
                    (float) $featureCoordinates[1],
                    (float) $featureCoordinates[0],
                );
                if ($nearestDistance === null || $distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearest = $feature;
                }
            }

            if ($nearest !== null && $nearestDistance !== null && $nearestDistance <= 200) {
                $thumb = $nearest['assets']['thumb']['href']
                    ?? $nearest['assets']['sd']['href']
                    ?? null;
                if (is_string($thumb) && filter_var($thumb, FILTER_VALIDATE_URL)) {
                    $restaurant['image'] = $thumb;
                    $restaurant['imageSource'] = 'panoramax';
                }
            }
        }
        unset($restaurant);

        return $restaurants;
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array<string, mixed> */
    private function request(string $query, ?string $city): array
    {
        $lastStatus = null;
        $responses = [];

        foreach (self::ENDPOINTS as $endpoint) {
            try {
                $responses[$endpoint] = $this->httpClient->request('POST', $endpoint, [
                    'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                    'body' => ['data' => $query],
                    'timeout' => self::HTTP_TIMEOUT,
                    'max_redirects' => 2,
                ]);
            } catch (TransportExceptionInterface|DecodingExceptionInterface) {
                continue;
            }
        }

        try {
            foreach ($this->httpClient->stream($responses, self::HTTP_TIMEOUT) as $response => $chunk) {
                if (!$chunk->isLast()) {
                    continue;
                }

                try {
                    $lastStatus = $response->getStatusCode();
                    if ($lastStatus < 400) {
                        $payload = $response->toArray();
                        if (is_array($payload) && is_array($payload['elements'] ?? null)) {
                            return $payload;
                        }
                    }
                } catch (HttpExceptionInterface $exception) {
                    $lastStatus = (int) $exception->getCode();
                } catch (TransportExceptionInterface|DecodingExceptionInterface) {
                    continue;
                }
            }
        } catch (HttpExceptionInterface $exception) {
            $lastStatus = (int) $exception->getCode();
        } catch (TransportExceptionInterface) {
            $lastStatus = null;
        }

        if ($city !== null) {
            $nominatim = $this->requestNominatim($city);
            if ($nominatim !== []) {
                return ['elements' => $nominatim];
            }
        }

        if ($lastStatus === 429) {
            throw new RuntimeException('OpenStreetMap limite temporairement les recherches. Veuillez réessayer dans quelques instants.');
        }
        if ($lastStatus === 504) {
            throw new RuntimeException('Le service OpenStreetMap a dépassé son délai de réponse. Veuillez réessayer.');
        }

        throw new RuntimeException('Le service OpenStreetMap est momentanément indisponible.');
    }

    /** @return array<string, mixed> */
    private function enrichWithPanoramax(array $restaurant): array
    {
        if ($restaurant['image'] !== null) {
            return $restaurant;
        }

        $location = $restaurant['location'] ?? null;
        if (!is_array($location) || !isset($location['latitude'], $location['longitude'])) {
            return $restaurant;
        }

        $url = 'https://panoramax.ign.fr/api/search?' . http_build_query([
            'place_position' => $location['longitude'] . ',' . $location['latitude'],
            'place_distance' => '3-50',
            'place_fov_tolerance' => 180,
            'limit' => 1,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                'timeout' => 1,
            ]);
            $payload = $response->toArray();
        } catch (HttpExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface) {
            return $restaurant;
        }

        $feature = $payload['features'][0] ?? null;
        $thumb = $feature['assets']['thumb']['href']
            ?? $feature['assets']['sd']['href']
            ?? null;
        if (is_string($thumb) && filter_var($thumb, FILTER_VALIDATE_URL)) {
            $restaurant['image'] = $thumb;
            $restaurant['imageSource'] = 'panoramax';
        }

        return $restaurant;
    }

    /** @return array<string, mixed>|null */
    private function requestNominatimPlaceDetail(string $placeId): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/lookup?' . http_build_query([
            'osm_ids' => 'N' . $placeId,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'extratags' => 1,
            'namedetails' => 1,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                'timeout' => 3,
            ]);
            $payload = $response->toArray();
        } catch (HttpExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface) {
            return null;
        }

        $place = is_array($payload[0] ?? null) ? $payload[0] : null;
        if ($place === null) {
            return null;
        }

        $address = $place['address'] ?? [];
        $extratags = $place['extratags'] ?? [];
        $name = $place['name'] ?? $place['display_name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            return null;
        }

        $element = [
            'id' => $placeId,
            'lat' => isset($place['lat']) ? (float) $place['lat'] : null,
            'lon' => isset($place['lon']) ? (float) $place['lon'] : null,
            'tags' => array_filter([
                'name' => $name,
                'cuisine' => $extratags['cuisine'] ?? null,
                'website' => $extratags['website'] ?? null,
                'phone' => $extratags['phone'] ?? null,
                'opening_hours' => $extratags['opening_hours'] ?? null,
                'image' => $extratags['image'] ?? null,
                'wikimedia_commons' => $extratags['wikimedia_commons'] ?? null,
                'description' => $extratags['description'] ?? null,
                'addr:street' => $address['road'] ?? null,
                'addr:housenumber' => $address['house_number'] ?? null,
                'addr:city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'image' => $extratags['image'] ?? null,
                'wikimedia_commons' => $extratags['wikimedia_commons'] ?? null,
                'description' => $extratags['description'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
        ];

        return $this->normalizer->normalize($element);
    }

    /** @return list<array<string, mixed>> */
    private function requestNominatim(string $city): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => 'restaurant in ' . $city,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'extratags' => 1,
            'namedetails' => 1,
            'limit' => self::MAX_RESULTS,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                'timeout' => self::HTTP_TIMEOUT,
            ]);
            $payload = $response->toArray();
        } catch (HttpExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface) {
            return [];
        }

        $elements = [];
        foreach ($payload as $place) {
            if (!is_array($place)) {
                continue;
            }

            $address = $place['address'] ?? [];
            $extratags = $place['extratags'] ?? [];
            $name = $place['name'] ?? $place['display_name'] ?? null;
            if (!is_string($name) || trim($name) === '') {
                continue;
            }

            $elements[] = [
                'id' => (string) ($place['place_id'] ?? ''),
                'lat' => isset($place['lat']) ? (float) $place['lat'] : null,
                'lon' => isset($place['lon']) ? (float) $place['lon'] : null,
                'tags' => array_filter([
                    'name' => $name,
                    'cuisine' => $extratags['cuisine'] ?? null,
                    'website' => $extratags['website'] ?? null,
                    'phone' => $extratags['phone'] ?? null,
                    'opening_hours' => $extratags['opening_hours'] ?? null,
                    'addr:street' => $address['road'] ?? null,
                    'addr:housenumber' => $address['house_number'] ?? null,
                    'addr:city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $city,
                ], static fn (mixed $value): bool => $value !== null),
            ];
        }

        return $elements;
    }
}
