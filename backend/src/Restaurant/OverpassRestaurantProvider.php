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
    private const MAX_RESULTS = 20;
    private const CACHE_SECONDS = 3600;
    private const HTTP_TIMEOUT = 2;

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

        $query = $this->buildQuery($city, $category, $search);
        $cacheKey = 'overpass_v3_' . hash('sha256', $query);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $city): array {
            $item->expiresAfter(self::CACHE_SECONDS);
            $payload = $this->request($query);

            $results = array_map(
                fn (array $element): array => $this->normalizer->normalize($element, $city),
                $payload['elements'],
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
        $payload = $this->cache->get('overpass_v3_id_' . $providerId, function (ItemInterface $item) use ($query): array {
            $item->expiresAfter(self::CACHE_SECONDS);
            $decoded = $this->request($query);
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
            . '(nwr' . $filters . '(area.searchArea););out 20 center tags;';
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

    /** @return array<string, mixed> */
    private function request(string $query): array
    {
        $lastStatus = null;

        foreach (self::ENDPOINTS as $endpoint) {
            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => ['User-Agent' => 'FoodSpot/1.0'],
                    'body' => ['data' => $query],
                    'timeout' => self::HTTP_TIMEOUT,
                    'max_redirects' => 2,
                ]);
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

        if ($lastStatus === 429) {
            throw new RuntimeException('OpenStreetMap limite temporairement les recherches. Veuillez réessayer dans quelques instants.');
        }
        if ($lastStatus === 504) {
            throw new RuntimeException('Le service OpenStreetMap a dépassé son délai de réponse. Veuillez réessayer.');
        }

        throw new RuntimeException('Le service OpenStreetMap est momentanément indisponible.');
    }
}
