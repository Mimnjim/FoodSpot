<?php

namespace App\Tests;

use App\Restaurant\RestaurantNormalizer;
use PHPUnit\Framework\TestCase;

final class RestaurantNormalizerTest extends TestCase
{
    public function testItNormalizesAnOverpassRestaurantWithMissingOptionalFields(): void
    {
        $normalizer = new RestaurantNormalizer();

        $result = $normalizer->normalize([
            'id' => 123456,
            'center' => ['lat' => 48.8566, 'lon' => 2.3522],
            'tags' => [
                'name' => 'Le Test',
                'cuisine' => 'italian',
                'addr:street' => 'Rue Exemple',
                'addr:housenumber' => '12',
            ],
        ], 'Paris');

        self::assertSame('osm-123456', $result['id']);
        self::assertSame('openstreetmap', $result['provider']);
        self::assertSame('Le Test', $result['name']);
        self::assertSame('12 Rue Exemple', $result['address']);
        self::assertSame('Paris', $result['city']);
        self::assertNull($result['rating']);
        self::assertNull($result['priceRange']);
        self::assertNull($result['image']);
        self::assertSame(['latitude' => 48.8566, 'longitude' => 2.3522], $result['location']);
    }

    public function testItKeepsCoordinatesNullWhenTheyAreAbsent(): void
    {
        $normalizer = new RestaurantNormalizer();

        $result = $normalizer->normalize(['id' => 42, 'tags' => []], 'Lyon');

        self::assertNull($result['location']);
        self::assertSame('Restaurant sans nom', $result['name']);
    }

    public function testItUsesOnlyARealWikimediaCommonsImage(): void
    {
        $normalizer = new RestaurantNormalizer();

        $result = $normalizer->normalize([
            'id' => 43,
            'lat' => 48.8566,
            'lon' => 2.3522,
            'tags' => [
                'name' => 'Restaurant image',
                'wikimedia_commons' => 'File:Restaurant sample.jpg',
            ],
        ], 'Paris');

        self::assertSame(
            'https://commons.wikimedia.org/wiki/Special:Redirect/file/Restaurant_sample.jpg',
            $result['image'],
        );
    }
}
