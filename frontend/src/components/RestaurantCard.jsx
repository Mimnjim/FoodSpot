
import { Link } from 'react-router';
import '../styles/RestaurantCard.css';

export default function RestaurantCard({
    restaurant,
    isFavorite,
    onToggleFavorite
}) {
    return (
        <div className="restaurant-card">
            <Link
                to={`/restaurant/${restaurant.id}`}
                className="restaurant-card-link"
            >
                <div className="restaurant-card-image">
                    <img
                        src={restaurant.image}
                        alt={restaurant.name}
                    />
                </div>

                <div className="restaurant-card-content">
                    <div>
                        <span className="restaurant-category">
                            {restaurant.category}
                        </span>

                        <h3>{restaurant.name}</h3>
                        <p>{restaurant.city}</p>
                    </div>

                    <div className="restaurant-card-meta">
                        <span>★ {restaurant.rating}</span>
                        <span>{restaurant.priceRange}</span>
                    </div>
                </div>
            </Link>

            <button
                type="button"
                className={`favorite-button ${isFavorite ? 'is-favorite' : ''}`}
                onClick={() => onToggleFavorite(restaurant.id)}
                aria-label={
                    isFavorite
                        ? `Retirer ${restaurant.name} des favoris`
                        : `Ajouter ${restaurant.name} aux favoris`
                }
                aria-pressed={isFavorite}
            >
                {isFavorite ? '♥' : '♡'}
            </button>
        </div>
    );
}
