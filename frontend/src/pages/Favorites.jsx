import { useState, useEffect } from 'react';
import { Link } from 'react-router';
import Header from '../components/Header.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import Footer from '../components/Footer.jsx';
import { getRestaurant } from '../api/foodSpotApi.js';

import '../styles/Favorites.css';

export default function Favorites({ favorites, onToggleFavorite }) {

    const [restaurants, setRestaurants] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        async function fetchRestaurants() {
            try {
                if (favorites.length === 0) {
                    setRestaurants([]);
                    return;
                }

                const responses = await Promise.all(
                    favorites.map(async (favoriteId) => {
                        try {
                            return await getRestaurant(favoriteId);
                        } catch (error) {
                            if (error.status === 404) {
                                return null;
                            }
                            throw error;
                        }
                    })
                );
                setRestaurants(responses.filter(Boolean));

            } catch (error) {
                setError(error.message);

            } finally {
                setLoading(false);
            }
        }

        fetchRestaurants();

    }, [favorites]);

    const favoriteRestaurants = restaurants.filter((restaurant) => favorites.includes(restaurant.id));

    return (
        <div className="favorites-page">
            <Header />

            <main className="favorites-main">
                <div className="favorites-heading">
                    <span className="favorites-eyebrow">VOTRE SÉLECTION</span>
                    <h1>Mes favoris.</h1>
                    <p>
                        Les lieux que vous aimez, réunis au même endroit.
                    </p>
                </div>

                {loading ? (
                    <p>Chargement des favoris…</p>
                ) : error ? (
                    <p>{error}</p>
                ) : favoriteRestaurants.length > 0 ? (
                    <RestaurantList
                        restaurants={favoriteRestaurants}
                        favorites={favorites}
                        onToggleFavorite={onToggleFavorite}
                    />
                ) : (
                    <div className="favorites-empty">
                        <span className="favorites-empty-heart">♡</span>
                        <h2>Aucun favori pour le moment.</h2>
                        <p>
                            Explorez FoodSpot et enregistrez les restaurants
                            que vous aimeriez découvrir.
                        </p>
                        <Link to="/" className="favorites-explore-button">
                            Explorer les restaurants
                        </Link>
                    </div>
                )}
            </main>

            <Footer />
        </div>
    );
}
