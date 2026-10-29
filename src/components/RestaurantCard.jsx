import { Link } from 'react-router';
import '../styles/RestaurantCard.css';

export default function RestaurantCard({ restaurant }) {
    return (
        <Link
            to={`/restaurant/${restaurant.id}`}
            className="restaurant-card"
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
    );
}