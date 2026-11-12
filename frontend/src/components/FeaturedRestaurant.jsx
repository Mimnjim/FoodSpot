import { Link } from 'react-router';
import '../styles/FeaturedRestaurant.css';

export default function FeaturedRestaurant({ restaurant }) {
    return (
        <section className="featured-restaurant">

            <div className="featured-image">
                {restaurant.image ? (
                    <img src={restaurant.image} alt={restaurant.name} />
                ) : (
                    <span className="featured-image-placeholder">FoodSpot</span>
                )}
            </div>

            <div className="featured-content">

                <span>
                    À DÉCOUVRIR
                </span>

                <h2>
                    {restaurant.name}
                </h2>

                <p>
                    {restaurant.description || 'Une adresse à découvrir.'}
                </p>

                <div>
                    <span>
                        {restaurant.category}
                    </span>

                    <span>
                        {restaurant.city}
                    </span>

                    <span>
                        {restaurant.rating !== null ? `★ ${restaurant.rating}` : 'Note non renseignée'}
                    </span>
                </div>

                <Link to={`/restaurant/${restaurant.id}`} className="featured-button">
                    Découvrir ce restaurant
                </Link>

            </div>

        </section>
    );
}