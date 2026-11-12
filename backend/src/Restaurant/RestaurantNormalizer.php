<?php

namespace App\Restaurant;

final class RestaurantNormalizer
{
    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    public function normalize(array $element): array
    {
        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $coordinates = [
            'latitude' => isset($element['lat']) ? (float) $element['lat'] : (float) ($element['center']['lat'] ?? 0),
            'longitude' => isset($element['lon']) ? (float) $element['lon'] : (float) ($element['center']['lon'] ?? 0),
        ];

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
            'city' => $tags['addr:city'] ?? null,
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
