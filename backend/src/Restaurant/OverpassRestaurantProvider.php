<?php

namespace App\Restaurant;

use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class OverpassRestaurantProvider implements RestaurantProviderInterface
{
    private const ENDPOINT = 'https://overpass-api.de/api/interpreter';

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
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: FoodSpot/1.0\r\n",
                    'content' => http_build_query(['data' => $query]),
                    'timeout' => 15,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents(self::ENDPOINT, false, $context);
            $status = $this->responseStatus($http_response_header ?? []);

            if ($response === false || $status >= 400) {
                throw new RuntimeException('Le service OpenStreetMap est momentanément indisponible.');
            }

            try {
                $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new RuntimeException('Réponse OpenStreetMap invalide.', 0, $exception);
            }

            if (!is_array($payload) || !is_array($payload['elements'] ?? null)) {
                throw new RuntimeException('Réponse OpenStreetMap invalide.');
            }

            return array_map(
                fn (array $element): array => $this->normalizer->normalize($element),
                $payload['elements'],
            );
        });
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
