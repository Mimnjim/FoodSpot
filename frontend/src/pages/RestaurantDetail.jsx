import { Link, useParams } from 'react-router';
import Header from '../components/Header.jsx';
import Footer from '../components/Footer.jsx';
import restaurants from '../data/restaurants.js';

import '../styles/RestaurantDetail.css';

export default function RestaurantDetail() {
    const { id } = useParams();

    const restaurant = restaurants.find(
        (item) => item.id === Number(id)
    );

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
                        <img
                            src={restaurant.image}
                            alt={restaurant.name}
                        />
                    </div>

                    <section className="detail-content">
                        <span className="detail-category">
                            {restaurant.category}
                        </span>

                        <h1>{restaurant.name}</h1>

                        <p className="detail-location">
                            {restaurant.city}
                        </p>

                        <div className="detail-meta">
                            <span>★ {restaurant.rating}</span>
                            <span>{restaurant.priceRange}</span>
                        </div>

                        <div className="detail-divider" />

                        <div className="detail-information">
                            <h2>About this place</h2>
                            <p>{restaurant.description}</p>
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
