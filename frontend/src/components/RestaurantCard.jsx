
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
                        <img
                            src={restaurant.image}
                            alt={`Photo de ${restaurant.name}`}
                            loading="lazy"
                            referrerPolicy="no-referrer"
                            onError={(event) => {
                                event.currentTarget.style.display = 'none';
                                event.currentTarget.nextElementSibling?.removeAttribute('hidden');
                            }}
                        />
                    ) : (
                        <span className="restaurant-card-placeholder" role="img" aria-label={`Illustration de ${restaurant.name}`}>
                            {restaurant.name.slice(0, 1).toUpperCase()}
                        </span>
                    )}
                    {restaurant.image && (
                        <span className="restaurant-card-placeholder" hidden role="img" aria-label={`Illustration de ${restaurant.name}`}>
                            {restaurant.name.slice(0, 1).toUpperCase()}
                        </span>
                    )}
                </div>

                <div className="restaurant-card-content">
                    <div>
                        <span className="restaurant-category">
                            {restaurant.category || 'Restaurant'}
                        </span>

                        <h3>{restaurant.name}</h3>
                        <p>{restaurant.city || 'Ville non renseignée'}</p>
                        {restaurant.address && (
                            <p className="restaurant-card-address">{restaurant.address}</p>
                        )}
                    </div>

                    <div className="restaurant-card-meta">
                        {restaurant.rating !== null && <span>★ {restaurant.rating}</span>}
                        {restaurant.priceRange && <span>{restaurant.priceRange}</span>}
                        {restaurant.provider === 'openstreetmap' && (
                            <span className="restaurant-source">OpenStreetMap</span>
                        )}
                        {restaurant.imageSource === 'panoramax' && (
                            <span className="restaurant-source">Photo Panoramax</span>
                        )}
                        {restaurant.imageSource === 'wikimedia-commons' && (
                            <span className="restaurant-source">Photo Wikimedia Commons</span>
                        )}
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
