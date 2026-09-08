import React from 'react';

export default function FeaturedRestaurant({ restaurant }) {
    return (
        <section className="featured-restaurant">

            <div className="featured-image">
                <img
                    src={restaurant.image}
                    alt={restaurant.name}
                />
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