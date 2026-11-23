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
                return array_slice($results, 0, self::MAX_RESULTS);
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

            return array_slice($results, 0, self::MAX_RESULTS);
        });
    }

    public function findByProviderId(string $providerId): ?array
    {
        if (!ctype_digit($providerId)) {
            return null;
        }

        $query = '[out:json][timeout:12];nwr(' . $providerId . ');out center tags;';
        $payload = $this->cache->get('overpass_v5_id_' . $providerId, function (ItemInterface $item) use ($query): array {
            $item->expiresAfter(self::CACHE_SECONDS);
            $decoded = $this->request($query, null);
            return is_array($decoded['elements'] ?? null) ? $decoded['elements'] : [];
        });

        return isset($payload[0]) && is_array($payload[0])
            ? $this->normalizer->normalize($payload[0])
            : null;
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
