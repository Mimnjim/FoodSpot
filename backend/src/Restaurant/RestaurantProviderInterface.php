<?php

namespace App\Restaurant;

interface RestaurantProviderInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $city, ?string $category, ?string $search): array;
}
