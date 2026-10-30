
import { Link } from 'react-router';
import Header from '../components/Header.jsx';
import RestaurantList from '../components/RestaurantList.jsx';
import Footer from '../components/Footer.jsx';
import restaurants from '../data/restaurants.js';
import '../styles/Favorites.css';

export default function Favorites({ favorites, onToggleFavorite }) {
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

                {favoriteRestaurants.length > 0 ? (
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
