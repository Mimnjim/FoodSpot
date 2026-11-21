<?php

namespace App\Restaurant;

final class RestaurantNormalizer
{
    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    public function normalize(array $element, ?string $searchCity = null): array
    {
        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $latitude = isset($element['lat'])
            ? (float) $element['lat']
            : (isset($element['center']['lat']) ? (float) $element['center']['lat'] : null);
        $longitude = isset($element['lon'])
            ? (float) $element['lon']
            : (isset($element['center']['lon']) ? (float) $element['center']['lon'] : null);
        $coordinates = $latitude !== null && $longitude !== null
            ? ['latitude' => $latitude, 'longitude' => $longitude]
            : null;

        $street = $tags['addr:street'] ?? null;
        $houseNumber = $tags['addr:housenumber'] ?? null;
        $address = trim(implode(' ', array_filter([$houseNumber, $street])));
        $dietaryTags = array_filter(
            $tags,
            static fn (mixed $value, string $key): bool => str_starts_with($key, 'diet:') && $value === 'yes',
            ARRAY_FILTER_USE_BOTH,
        );
        $priceLevel = $tags['price:level'] ?? null;
        $priceRange = is_string($priceLevel) && ctype_digit($priceLevel)
            ? str_repeat('€', min(4, max(1, (int) $priceLevel)))
            : null;

        return [
            'id' => 'osm-' . ($element['id'] ?? ''),
            'provider' => 'openstreetmap',
            'providerId' => (string) ($element['id'] ?? ''),
            'name' => $tags['name'] ?? 'Restaurant sans nom',
            'address' => $address !== '' ? $address : null,
            'city' => $tags['addr:city'] ?? (trim((string) $searchCity) !== '' ? trim((string) $searchCity) : null),
            'location' => $coordinates,
            'category' => $tags['cuisine'] ?? 'Restaurant',
            'rating' => null,
            'priceRange' => $priceRange,
            'dietaryTags' => array_keys($dietaryTags),
            'website' => $tags['website'] ?? $tags['contact:website'] ?? null,
            'phone' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
            'openingHours' => $tags['opening_hours'] ?? null,
            'description' => null,
            'image' => null,
        ];
    }
}
