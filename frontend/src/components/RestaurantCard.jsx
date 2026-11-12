
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
                    {restaurant.image ? (
                        <img src={restaurant.image} alt={restaurant.name} />
                    ) : (
                        <span className="restaurant-card-placeholder">FoodSpot</span>
                    )}
                </div>

                <div className="restaurant-card-content">
                    <div>
                        <span className="restaurant-category">
                            {restaurant.category || 'Restaurant'}
                        </span>

                        <h3>{restaurant.name}</h3>
                        <p>{restaurant.city}</p>
                    </div>

                    <div className="restaurant-card-meta">
                        {restaurant.rating !== null && <span>★ {restaurant.rating}</span>}
                        {restaurant.priceRange && <span>{restaurant.priceRange}</span>}
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
