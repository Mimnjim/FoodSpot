import { useState, useEffect } from 'react';
import { Link, useParams } from 'react-router';
import Header from '../components/Header.jsx';
import Footer from '../components/Footer.jsx';

import '../styles/RestaurantDetail.css';

export default function RestaurantDetail() {
    const { id } = useParams();

    const [restaurant, setRestaurant] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        async function fetchRestaurant() {
            try {
                const response = await fetch(`/api/restaurants/${id}`);

                if (response.status === 404) {
                    setRestaurant(null);
                    return;
                }

                if (!response.ok) {
                    throw new Error('Erreur lors du chargement du restaurant.');
                }

                const data = await response.json();

                setRestaurant(data);

            } catch (error) {
                setError(error.message);

            } finally {
                setLoading(false);
            }
        }

        fetchRestaurant();

    }, [id]);

    if (loading) {
        return <p>Loading restaurant...</p>;
    }

    if (error) {
        return <p>{error}</p>;
    }

    if (!restaurant) {
        return (
            <div className="restaurant-detail-page">
                <Header />

                <main className="restaurant-not-found">
                    <span>404 — NOT FOUND</span>
                    <h1>Restaurant not found.</h1>
                    <p>
                        This restaurant doesn't exist or is no longer available.
                    </p>
                    <Link to="/" className="detail-back-button">
                        Back to FoodSpot
                    </Link>
                </main>

                <Footer />
            </div>
        );
    }

    return (
        <div className="restaurant-detail-page">
            <Header />

            <main className="restaurant-detail">
                <div className="detail-navigation">
                    <Link to="/">← Back to explore</Link>
                    <span>Restaurant #{restaurant.id}</span>
                </div>

                <div className="detail-layout">
                    <div className="detail-image">
                        {restaurant.image ? (
                            <img src={restaurant.image} alt={restaurant.name} />
                        ) : (
                            <span className="detail-image-placeholder">FoodSpot</span>
                        )}
                    </div>

                    <section className="detail-content">
                        <span className="detail-category">
                            {restaurant.category || 'Restaurant'}
                        </span>

                        <h1>{restaurant.name}</h1>

                        <p className="detail-location">
                            {restaurant.city || 'Ville non renseignée'}
                        </p>

                        <div className="detail-meta">
                            {restaurant.rating !== null && <span>★ {restaurant.rating}</span>}
                            {restaurant.priceRange && <span>{restaurant.priceRange}</span>}
                        </div>

                        <div className="detail-divider" />

                        <div className="detail-information">
                            <h2>About this place</h2>
                            <p>{restaurant.description || 'Description non renseignée.'}</p>
                        </div>

                        <div className="detail-information">
                            <h2>Address</h2>
                            <p>{restaurant.address}</p>
                        </div>

                        <Link
                            to="/"
                            className="detail-back-button"
                        >
                            Discover more restaurants
                        </Link>
                    </section>
                </div>
            </main>

            <Footer />
        </div>
    );
}
