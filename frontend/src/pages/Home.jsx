
import React, { useState, useEffect } from 'react';

import Header from '../components/Header.jsx';
import Hero from '../components/Hero.jsx';
import CategoryFilter from '../components/CategoryFilter.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import FeaturedRestaurant from '../components/FeaturedRestaurant.jsx';
import Footer from '../components/Footer.jsx';
import SearchBar from '../components/SearchBar.jsx';
import '../styles/Home.css';

export default function Home({ favorites, onToggleFavorite }) {

    const [restaurants, setRestaurants] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    
    useEffect(() => {

        async function fetchRestaurants() {
            try {
                const response = await fetch('/api/restaurants');

                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des restaurants.');
                }

                const data = await response.json();

                setRestaurants(data);

            } catch (error) {
                setError(error.message);

            } finally {
                setLoading(false);
            }
        }

        fetchRestaurants();

    }, []);



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

                    {loading ? (
                        <p>Loading restaurants...</p>
                    ) : error ? (
                        <p>{error}</p>
                    ) : (
                        <>
                            <RestaurantList
                                restaurants={filteredRestaurants}
                                favorites={favorites}
                                onToggleFavorite={onToggleFavorite}
                            />

                            {filteredRestaurants.length === 0 && (
                                <p>No restaurants found.</p>
                            )}
                        </>
                    )}

                </section>

                {!loading && !error && featuredRestaurant && (
                    <FeaturedRestaurant
                        restaurant={featuredRestaurant}
                    />
                )}

            </main>

            <Footer />

        </div>
    );
}
