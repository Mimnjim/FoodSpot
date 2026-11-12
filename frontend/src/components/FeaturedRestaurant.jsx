import React from 'react';
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
                    THIS WEEK'S PICK
                </span>

                <h2>
                    {restaurant.name}
                </h2>

                <p>
                    {restaurant.description}
                </p>

                <div>
                    <span>
                        {restaurant.category}
                    </span>

                    <span>
                        {restaurant.city}
                    </span>

                    <span>
                        ★ {restaurant.rating}
                    </span>
                </div>

                <button>
                    Discover this restaurant
                </button>

            </div>

        </section>
    );
}