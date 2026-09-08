import React from 'react';
import RestaurantCard from './RestaurantCard.jsx';
import '../styles/RestaurantList.css';

export default function RestaurantList({ restaurants }) {
    return (
        <div className="restaurant-list">

            {restaurants.map((restaurant) => (
                <RestaurantCard
                    key={restaurant.id}
                    restaurant={restaurant}
                />
            ))}

        </div>
    );
}