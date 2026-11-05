<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RestaurantFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $restaurants = [
            [
                'name' => 'Sakura',
                'category' => 'Japonais',
                'city' => 'Paris',
                'address' => '24 rue Sainte-Anne, 75001 Paris',
                'rating' => 4.6,
                'priceRange' => '€€',
                'description' => 'Restaurant japonais proposant des sushis, ramen et plats traditionnels.',
                'image' => '/images/sakura.jpg',
            ],
            [
                'name' => 'Mekong Kitchen',
                'category' => 'Cambodgien',
                'city' => 'Paris',
                'address' => '15 rue de la Roquette, 75011 Paris',
                'rating' => 4.8,
                'priceRange' => '€€',
                'description' => 'Cuisine cambodgienne traditionnelle avec des plats inspirés des recettes familiales.',
                'image' => '/images/mekong-kitchen.jpg',
            ],
            [
                'name' => 'La Trattoria',
                'category' => 'Italien',
                'city' => 'Lyon',
                'address' => '8 rue Mercière, 69002 Lyon',
                'rating' => 4.4,
                'priceRange' => '€€',
                'description' => 'Une trattoria conviviale proposant pizzas, pâtes fraîches et spécialités italiennes.',
                'image' => '/images/la-trattoria.jpg',
            ],
            [
                'name' => 'Seoul House',
                'category' => 'Coréen',
                'city' => 'Paris',
                'address' => '12 rue Saint-Anne, 75001 Paris',
                'rating' => 4.7,
                'priceRange' => '€€',
                'description' => 'Cuisine coréenne traditionnelle avec barbecue, bibimbap et plats épicés.',
                'image' => '/images/seoul-house.jpg',
            ],
            [
                'name' => 'Le Comptoir Français',
                'category' => 'Français',
                'city' => 'Bordeaux',
                'address' => '31 rue du Parlement Saint-Pierre, 33000 Bordeaux',
                'rating' => 4.3,
                'priceRange' => '€€€',
                'description' => 'Cuisine française moderne avec des produits locaux et de saison.',
                'image' => '/images/comptoir-francais.jpg',
            ],
            [
                'name' => 'Bangkok Street',
                'category' => 'Thaïlandais',
                'city' => 'Paris',
                'address' => '18 rue du Faubourg Saint-Martin, 75010 Paris',
                'rating' => 4.5,
                'priceRange' => '€€',
                'description' => 'Street food thaïlandaise avec pad thaï, currys et plats relevés.',
                'image' => '/images/bangkok-street.jpg',
            ],
            [
                'name' => 'Casa Mexico',
                'category' => 'Mexicain',
                'city' => 'Toulouse',
                'address' => '6 rue Peyrolières, 31000 Toulouse',
                'rating' => 4.2,
                'priceRange' => '€',
                'description' => 'Restaurant mexicain proposant tacos, burritos et spécialités traditionnelles.',
                'image' => '/images/casa-mexico.jpg',
            ],
            [
                'name' => 'Green Garden',
                'category' => 'Végétarien',
                'city' => 'Nantes',
                'address' => '10 rue Léon Blum, 44000 Nantes',
                'rating' => 4.6,
                'priceRange' => '€€',
                'description' => 'Cuisine végétarienne créative à base de produits frais et de saison.',
                'image' => '/images/green-garden.jpg',
            ],
        ];

        foreach ($restaurants as $data) {
            $restaurant = new Restaurant();

            $restaurant->setName($data['name']);
            $restaurant->setCategory($data['category']);
            $restaurant->setCity($data['city']);
            $restaurant->setAddress($data['address']);
            $restaurant->setRating($data['rating']);
            $restaurant->setPriceRange($data['priceRange']);
            $restaurant->setDescription($data['description']);
            $restaurant->setImage($data['image']);

            $manager->persist($restaurant);
        }

        $manager->flush();
    }
}