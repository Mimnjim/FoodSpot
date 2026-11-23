
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
    const [baseRestaurants, setBaseRestaurants] = useState([]);

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
        }, 300);

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
        }, 400);

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
        if (!city.trim() || debouncedCity.trim() !== city.trim()) {
            return undefined;
        }
        if (search.trim() === '' && debouncedSearch.trim() === '' &&
            selectedCategory === 'Toutes') {
            const controller = new AbortController();
            getRestaurants({ city: debouncedCity }, controller.signal)
                .then((restaurants) => {
                    setBaseRestaurants(restaurants);
                    setFilteredRestaurants(restaurants);
                    setLoading(false);
                    setIsFiltering(false);
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') setError(error.message);
                    setIsFiltering(false);
                });
            return () => controller.abort();
        }
        return undefined;
    }, [city, debouncedCity, search, debouncedSearch, selectedCategory]);

    useEffect(() => {
        if (debouncedSearch.trim() === '' || debouncedCity.trim() !== city.trim()) {
            return undefined;
        }
        const controller = new AbortController();
        getRestaurants({
            category: 'Toutes',
            search: debouncedSearch,
            city: debouncedCity,
        }, controller.signal).then((restaurants) => {
            setFilteredRestaurants(restaurants);
        }).catch((error) => {
            if (error.name !== 'AbortError') setError(error.message);
        }).finally(() => {
            if (!controller.signal.aborted) setIsFiltering(false);
        });
        return () => controller.abort();
    }, [debouncedSearch, debouncedCity, city, selectedCategory, hasActiveFilters]);

    function normalizeCategoryLabel(category) {
        const value = String(category || '').trim().toLowerCase();
        const aliases = {
            asian: 'Asiatique',
            asia: 'Asiatique',
            asiatique: 'Asiatique',
            chinese: 'Chinois',
            chinois: 'Chinois',
            japan: 'Japonais',
            japanese: 'Japonais',
            japonais: 'Japonais',
            korean: 'Coréen',
            coréen: 'Coréen',
            thai: 'Thaïlandais',
            thaï: 'Thaïlandais',
            thaïlandais: 'Thaïlandais',
            vietnamese: 'Vietnamien',
            viet: 'Vietnamien',
            vietnamien: 'Vietnamien',
            indian: 'Indien',
            indien: 'Indien',
            italian: 'Italien',
            italian_pizza: 'Italien',
            italien: 'Italien',
            french: 'Français',
            français: 'Français',
            francais: 'Français',
            mediterranean: 'Méditerranéen',
            mediterranean_food: 'Méditerranéen',
            mexican: 'Mexicain',
            mexicain: 'Mexicain',
            pizza: 'Pizza',
            burger: 'Burger',
            bistro: 'Bistro',
            crepe: 'Crêpes',
            seafood: 'Fruits de mer',
            steak_house: 'Grill',
            lebanese: 'Libanais',
            turkish: 'Turc',
            restaurant: 'Restaurant',
        };

        const firstValue = value.split(/[;,]/)[0].trim();
        return aliases[firstValue] || aliases[value] || null;
    }

    const categories = [...new Set(
        baseRestaurants
            .map((restaurant) => normalizeCategoryLabel(restaurant.category))
            .filter(Boolean),
    )].sort((a, b) => a.localeCompare(b, 'fr'));

    // Identifier les résultats à afficher, avec filtre catégorie entièrement local
    const categoryFilteredRestaurants = selectedCategory === 'Toutes'
        ? baseRestaurants
        : baseRestaurants.filter((restaurant) => {
            const category = String(restaurant.category || '').toLowerCase();
            const selected = selectedCategory.toLowerCase();
            if (selected === 'asiatique') {
                return /asian|china|chinese|japan|korea|thai|viet|indian|lebanese|turkish|mediterranean|seafood/.test(category);
            }
            if (selected === 'français') return category.includes('french');
            return category.includes(selected);
        });
    const displayedRestaurants = debouncedSearch.trim() !== ''
        ? filteredRestaurants
        : categoryFilteredRestaurants;
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
