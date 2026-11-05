import { useState, useEffect } from 'react';
import { Link } from 'react-router';
import Header from '../components/Header.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import Footer from '../components/Footer.jsx';

import '../styles/Favorites.css';

export default function Favorites({ favorites, onToggleFavorite }) {

    const [restaurants, setRestaurants] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        async function fetchRestaurants() {
            try {
                const response = await fetch('/api/restaurants');

                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des favoris.');
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

    const favoriteRestaurants = restaurants.filter((restaurant) =>
        favorites.includes(restaurant.id)
    );

    return (
        <div className="favorites-page">
            <Header />

            <main className="favorites-main">
                <div className="favorites-heading">
                    <span className="favorites-eyebrow">YOUR SELECTION</span>
                    <h1>My favorites.</h1>
                    <p>
                        The places you love, all in one place.
                    </p>
                </div>

                {loading ? (
                    <p>Loading favorites...</p>
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
                        <h2>No favorites yet.</h2>
                        <p>
                            Explore FoodSpot and save the restaurants
                            you would love to discover.
                        </p>
                        <Link to="/" className="favorites-explore-button">
                            Explore restaurants
                        </Link>
                    </div>
                )}
            </main>

            <Footer />
        </div>
    );
}
