<?php

namespace App\Tests;

use App\Restaurant\OverpassRestaurantProvider;
use App\Restaurant\RestaurantNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Exception\TransportException;

final class OverpassRestaurantProviderTest extends TestCase
{
    public function testItNormalizesAValidOverpassResponse(): void
    {
        $httpClient = new MockHttpClient(function () {
            return new MockResponse(json_encode([
                'elements' => [
                    [
                        'id' => 100,
                        'lat' => 48.8566,
                        'lon' => 2.3522,
                        'tags' => ['name' => 'Overpass Cafe', 'cuisine' => 'french'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        });

        $provider = new OverpassRestaurantProvider(
            new RestaurantNormalizer(),
            new ArrayAdapter(),
            $httpClient,
        );

        $restaurants = $provider->search('Paris', null, null);

        self::assertCount(1, $restaurants);
        self::assertSame('osm-100', $restaurants[0]['id']);
        self::assertSame('Overpass Cafe', $restaurants[0]['name']);
    }

    public function testItRejectsASearchWithoutCity(): void
    {
        $provider = new OverpassRestaurantProvider(
            new RestaurantNormalizer(),
            new ArrayAdapter(),
            new MockHttpClient(),
        );

        $this->expectException(\RuntimeException::class);
        $provider->search('', null, null);
    }

    public function testItReportsAnUnavailableServiceAfterFailedInstances(): void
    {
        $httpClient = new MockHttpClient(function () {
            throw new TransportException('Les instances Overpass ne répondent pas.');
        });

        $provider = new OverpassRestaurantProvider(
            new RestaurantNormalizer(),
            new ArrayAdapter(),
            $httpClient,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('momentanément indisponible');
        $provider->search('Bordeaux', null, null);
    }
}
