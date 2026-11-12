
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
    const [filteredRestaurants, setFilteredRestaurants] = useState([]);

    const [loading, setLoading] = useState(true);
    const [isFiltering, setIsFiltering] = useState(false);
    const [error, setError] = useState(null);

    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('Toutes');
    const [debouncedSearch, setDebouncedSearch] = useState('');

    // Attendre 400 ms après la dernière saisie
    useEffect(() => {
        const timeout = setTimeout(() => {
            setDebouncedSearch(search);
        }, 400);

        return () => {
            clearTimeout(timeout);
        };
    }, [search]);

    // Récupérer tous les restaurants au chargement
    useEffect(() => {

        async function fetchRestaurants() {
            try {
                const response = await fetch('/api/restaurants');

                if (!response.ok) {
                    throw new Error(
                        'Erreur lors du chargement des restaurants.'
                    );
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

    // Filtrer les restaurants depuis Symfony
    useEffect(() => {

        const controller = new AbortController();

        async function fetchFilteredRestaurants() {

            setIsFiltering(true);

            try {
                setError(null);

                // Construire les paramètres de recherche
                const params = new URLSearchParams();

                if (selectedCategory !== 'Toutes') {
                    params.set('category', selectedCategory);
                }

                if (debouncedSearch.trim() !== '') {
                    params.set('search', debouncedSearch.trim());
                }

                // Envoyer la requête à Symfony
                const response = await fetch(
                    `/api/restaurants?${params.toString()}`,
                    { signal: controller.signal }
                );

                if (!response.ok) {
                    throw new Error(
                        'Erreur lors du filtrage des restaurants.'
                    );
                }

                const data = await response.json();

                setFilteredRestaurants(data);

            } catch (error) {

                if (error.name !== 'AbortError') {
                    setError(error.message);
                }

            } finally {

                // Ne modifier l'état que si la requête n'a pas été annulée
                if (!controller.signal.aborted) {
                    setIsFiltering(false);
                }
            }
        }

        fetchFilteredRestaurants();

        return () => {
            controller.abort();
        };

    }, [debouncedSearch, selectedCategory]);

    // Récupérer les catégories disponibles
    const categories = [
        ...new Set(restaurants.map((restaurant) => restaurant.category))
    ];

    // Identifier le restaurant mis en avant
    const featuredRestaurant = restaurants[1];

    return (
        <div className="home">

            <Header />

            <main>

                <Hero />

                <section className="discover-section">

                    <div className="section-header">

                        <div>
                            <span>EXPLORE</span>
                            <h2>Places worth knowing.</h2>
                        </div>

                        <p>Find your next favorite restaurant.</p>

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

                    ) : isFiltering ? (
                        <p>Recherche en cours...</p>

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
