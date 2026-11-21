
import React, { useState, useEffect } from 'react';

import Header from '../components/Header.jsx';
import Hero from '../components/Hero.jsx';
import CategoryFilter from '../components/CategoryFilter.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import FeaturedRestaurant from '../components/FeaturedRestaurant.jsx';
import Footer from '../components/Footer.jsx';
import SearchBar from '../components/SearchBar.jsx';
import { getRestaurants } from '../api/foodSpotApi.js';
import '../styles/Home.css';

export default function Home({ favorites, onToggleFavorite }) {

    const [filteredRestaurants, setFilteredRestaurants] = useState([]);

    const [loading, setLoading] = useState(false);
    const [isFiltering, setIsFiltering] = useState(false);
    const [error, setError] = useState(null);

    const [search, setSearch] = useState('');
    const [city, setCity] = useState(() => (
        localStorage.getItem('foodspot-city')?.trim() || ''
    ));
    const [selectedCategory, setSelectedCategory] = useState('Toutes');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [debouncedCity, setDebouncedCity] = useState(city);
    const [onboardingCity, setOnboardingCity] = useState('');
    const [showCityOnboarding, setShowCityOnboarding] = useState(() => (
        !localStorage.getItem('foodspot-city')
    ));
    const hasActiveFilters = debouncedSearch.trim() !== ''
        || debouncedCity.trim() !== ''
        || selectedCategory !== 'Toutes';

    // Attendre 400 ms après la dernière saisie
    useEffect(() => {
        const timeout = setTimeout(() => {
            setDebouncedSearch(search);
        }, 400);

        return () => {
            clearTimeout(timeout);
        };
    }, [search]);

    useEffect(() => {
        const timeout = setTimeout(() => {
            setDebouncedCity(city);
            if (city.trim()) {
                localStorage.setItem('foodspot-city', city.trim());
            }
        }, 600);

        return () => {
            clearTimeout(timeout);
        };
    }, [city]);

    function chooseOnboardingCity(event) {
        event.preventDefault();
        const selectedCity = onboardingCity.trim();
        if (!selectedCity) {
            return;
        }

        localStorage.setItem('foodspot-city', selectedCity);
        setCity(selectedCity);
        setDebouncedCity(selectedCity);
        setShowCityOnboarding(false);
    }

    // Filtrer les restaurants depuis Symfony uniquement lorsqu'un critère est actif
    useEffect(() => {
        if (!hasActiveFilters) {
            return undefined;
        }

        const controller = new AbortController();

        async function fetchFilteredRestaurants() {

            setIsFiltering(true);

            try {
                setError(null);

                setFilteredRestaurants(await getRestaurants({
                    category: selectedCategory,
                    search: debouncedSearch,
                    city: debouncedCity,
                }, controller.signal));

            } catch (error) {

                if (error.name !== 'AbortError') {
                    setError(error.message);
                }

            } finally {

                // Ne modifier l'état que si la requête n'a pas été annulée
                if (!controller.signal.aborted) {
                    setIsFiltering(false);
                    setLoading(false);
                }
            }
        }

        fetchFilteredRestaurants();

        return () => {
            controller.abort();
        };

    }, [debouncedSearch, debouncedCity, selectedCategory, hasActiveFilters]);

    // Récupérer les catégories disponibles dans les résultats actifs
    const categorySource = filteredRestaurants;
    const discoveredCategories = categorySource
        .flatMap((restaurant) => String(restaurant.category || '')
            .split(/[;,]/)
            .map((category) => category.trim())
            .filter(Boolean));
    const standardCategories = [
        'Asiatique',
        'Japonais',
        'Chinois',
        'Coréen',
        'Thaïlandais',
        'Vietnamien',
        'Indien',
        'Italien',
        'Français',
    ];
    const categories = [
        ...new Set([...standardCategories, ...discoveredCategories]),
    ].sort((a, b) => a.localeCompare(b, 'fr'));

    // Identifier le restaurant mis en avant
    const displayedRestaurants = filteredRestaurants;
    const featuredRestaurant = displayedRestaurants[0];
    const hasOpenStreetMapResults = displayedRestaurants.some(
        (restaurant) => restaurant.provider === 'openstreetmap',
    );

    return (
        <div className="home">

            <Header />

            <main>

                {showCityOnboarding && (
                    <div className="city-onboarding" role="dialog" aria-modal="true" aria-labelledby="city-onboarding-title">
                        <form className="city-onboarding-card" onSubmit={chooseOnboardingCity}>
                            <span className="city-onboarding-eyebrow">PERSONNALISEZ VOTRE DÉCOUVERTE</span>
                            <h1 id="city-onboarding-title">Dans quelle ville avez-vous envie de manger ?</h1>
                            <p>
                                Nous chargerons uniquement les restaurants proches de cette ville.
                            </p>
                            <input
                                autoFocus
                                required
                                type="text"
                                value={onboardingCity}
                                onChange={(event) => setOnboardingCity(event.target.value)}
                                placeholder="Votre ville"
                                aria-label="Votre ville"
                            />
                            <div className="city-suggestions" aria-label="Villes suggérées">
                                {['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux'].map((suggestedCity) => (
                                    <button
                                        type="button"
                                        key={suggestedCity}
                                        onClick={() => setOnboardingCity(suggestedCity)}
                                    >
                                        {suggestedCity}
                                    </button>
                                ))}
                            </div>
                            <button className="city-onboarding-submit" type="submit">
                                Découvrir les restaurants
                            </button>
                        </form>
                    </div>
                )}

                <Hero />

                <section className="discover-section">

                    <div className="section-header">

                        <div>
                            <span>EXPLORE</span>
                            <h2>Des lieux qui valent le détour.</h2>
                        </div>

                        <p>Trouvez votre prochain restaurant préféré.</p>

                    </div>

                    <SearchBar
                        search={search}
                        setSearch={setSearch}
                        city={city}
                        setCity={setCity}
                    />

                    <CategoryFilter
                        categories={categories}
                        selectedCategory={selectedCategory}
                        setSelectedCategory={setSelectedCategory}
                    />

                    {loading ? (
                        <p>Chargement des restaurants…</p>

                    ) : error ? (
                        <p>{error}</p>

                    ) : isFiltering ? (
                        <p>Recherche en cours...</p>

                    ) : (
                        <>
                            {displayedRestaurants.length > 0 && (
                                <p className="results-summary" aria-live="polite">
                                    {displayedRestaurants.length} résultat{displayedRestaurants.length > 1 ? 's' : ''}
                                    {hasOpenStreetMapResults
                                        ? ' — données OpenStreetMap'
                                        : ' — catalogue FoodSpot'}
                                </p>
                            )}
                            <RestaurantList
                                restaurants={displayedRestaurants}
                                favorites={favorites}
                                onToggleFavorite={onToggleFavorite}
                            />

                            {displayedRestaurants.length === 0 && (
                                <p>Aucun restaurant trouvé pour ces critères.</p>
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
