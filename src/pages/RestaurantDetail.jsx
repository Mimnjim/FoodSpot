import { useParams, Link } from 'react-router';
import restaurants from '../data/restaurants.js';

export default function RestaurantDetail() {
    const { id } = useParams();

    const restaurant = restaurants.find(
        (restaurant) => restaurant.id === Number(id)
    );

    if (!restaurant) {
        return (
            <main>
                <h1>Restaurant not found</h1>
                <Link to="/">Back to home</Link>
            </main>
        );
    }

    return (
        <main className="restaurant-detail">
            <Link to="/">← Back to FoodSpot</Link>

            <h1>{restaurant.name}</h1>

            <p>{restaurant.category} · {restaurant.city}</p>

            <p>★ {restaurant.rating} · {restaurant.priceRange}</p>

            <p>{restaurant.address}</p>

            <p>{restaurant.description}</p>
        </main>
    );
}