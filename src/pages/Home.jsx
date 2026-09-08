import React, { useState } from 'react';

import Header from '../components/Header.jsx';
import Hero from '../components/Hero.jsx';
import CategoryFilter from '../components/CategoryFilter.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import FeaturedRestaurant from '../components/FeaturedRestaurant.jsx';
import Footer from '../components/Footer.jsx';
import SearchBar from '../components/SearchBar.jsx';
import '../styles/Home.css';

import restaurants from '../data/restaurants.js';

export default function Home() {

    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('Toutes');

    const categories = [
        ...new Set(restaurants.map((restaurant) => restaurant.category))
    ];

    const filteredRestaurants = restaurants.filter((restaurant) => {

        const matchesSearch =
            restaurant.name.toLowerCase().includes(search.toLowerCase()) ||
            restaurant.category.toLowerCase().includes(search.toLowerCase()) ||
            restaurant.city.toLowerCase().includes(search.toLowerCase());

        const matchesCategory =
            selectedCategory === 'Toutes' ||
            restaurant.category === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    const featuredRestaurant = restaurants[1];

    return (
        <div className="home">

            <Header />

            <main>

                <Hero />

                <section className="discover-section">

                    <div className="section-header">

                        <div>
                            <span>
                                EXPLORE
                            </span>

                            <h2>
                                Places worth knowing.
                            </h2>
                        </div>

                        <p>
                            Find your next favorite restaurant.
                        </p>

                    </div>

                    <SearchBar
                        search={search}
                        setSearch={setSearch}
                    />

                    <CategoryFilter
                        categories={categories}
                        selectedCategory={selectedCategory}
                        setSelectedCategory={setSelectedCategory}
                    />

                    <RestaurantList
                        restaurants={filteredRestaurants}
                    />

                    {filteredRestaurants.length === 0 && (
                        <p>
                            No restaurants found.
                        </p>
                    )}

                </section>

                <FeaturedRestaurant
                    restaurant={featuredRestaurant}
                />

            </main>

            <Footer />

        </div>
    );
}