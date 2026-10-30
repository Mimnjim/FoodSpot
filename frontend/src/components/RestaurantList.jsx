
import RestaurantCard from './RestaurantCard.jsx';
import '../styles/RestaurantList.css';

export default function RestaurantList({
    restaurants,
    favorites,
    onToggleFavorite
}) {
    return (
        <div className="restaurant-list">
            {restaurants.map((restaurant) => (
                <RestaurantCard
                    key={restaurant.id}
                    restaurant={restaurant}
                    isFavorite={favorites.includes(restaurant.id)}
                    onToggleFavorite={onToggleFavorite}
                />
            ))}
        </div>
    );
}
