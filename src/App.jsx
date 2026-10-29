
import { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router';
import Home from './pages/Home.jsx';
import RestaurantDetail from './pages/RestaurantDetail.jsx';

export default function App() {
    const [favorites, setFavorites] = useState(() => {
        const savedFavorites = localStorage.getItem('foodspot-favorites');

        return savedFavorites ? JSON.parse(savedFavorites) : [];
    });

    useEffect(() => {
        localStorage.setItem(
            'foodspot-favorites',
            JSON.stringify(favorites)
        );
    }, [favorites]);

    function toggleFavorite(restaurantId) {
        setFavorites((currentFavorites) => {
            if (currentFavorites.includes(restaurantId)) {
                return currentFavorites.filter(
                    (id) => id !== restaurantId
                );
            }

            return [...currentFavorites, restaurantId];
        });
    }

    return (
        <BrowserRouter>
            <Routes>
                <Route
                    path="/"
                    element={
                        <Home
                            favorites={favorites}
                            onToggleFavorite={toggleFavorite}
                        />
                    }
                />
                <Route
                    path="/restaurant/:id"
                    element={<RestaurantDetail />}
                />
            </Routes>
        </BrowserRouter>
    );
}
