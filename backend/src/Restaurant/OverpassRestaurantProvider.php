<?php

namespace App\Restaurant;

use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class OverpassRestaurantProvider implements RestaurantProviderInterface
{
    private const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ];

    public function __construct(
        private readonly RestaurantNormalizer $normalizer,
        private readonly CacheInterface $cache,
    ) {
    }

    public function search(?string $city, ?string $category, ?string $search): array
    {
        $city = trim((string) $city);
        if ($city === '') {
            throw new RuntimeException('Une ville est nécessaire pour une recherche OpenStreetMap.');
        }

        $query = $this->buildQuery($city, $category, $search);
        $cacheKey = 'overpass_' . hash('sha256', $query);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query): array {
            $item->expiresAfter(300);
            $payload = $this->request($query);

            return array_map(
                fn (array $element): array => $this->normalizer->normalize($element),
                $payload['elements'],
            );
        });
    }

    public function findByProviderId(string $providerId): ?array
    {
        if (!ctype_digit($providerId)) {
            return null;
        }

        $query = '[out:json][timeout:12];nwr(' . $providerId . ');out center tags;';
        $payload = $this->cache->get('overpass_id_' . $providerId, function (ItemInterface $item) use ($query): array {
            $item->expiresAfter(300);
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
            $filters .= '["cuisine"~"' . $this->escape($category) . '",i]';
        }
        if ($search !== null && trim($search) !== '') {
            $filters .= '["name"~"' . $this->escape(trim($search)) . '",i]';
        }

        return '[out:json][timeout:12];' . $area
            . '(nwr' . $filters . '(area.searchArea););out center tags;';
    }

    private function escape(string $value): string
    {
        return addcslashes(preg_quote($value, '/'), '\\"');
    }

    /** @return array<string, mixed> */
    private function request(string $query): array
    {
        foreach (self::ENDPOINTS as $endpoint) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: FoodSpot/1.0\r\n",
                    'content' => http_build_query(['data' => $query]),
                    'timeout' => 15,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($endpoint, false, $context);
            $status = $this->responseStatus($http_response_header ?? []);

            if ($response === false || $status >= 400) {
                continue;
            }

            try {
                $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }

            if (is_array($payload) && is_array($payload['elements'] ?? null)) {
                return $payload;
            }
        }

        throw new RuntimeException('Le service OpenStreetMap est momentanément indisponible.');
    }

    /** @param list<string> $headers */
    private function responseStatus(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/HTTP\/\S+ (\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
