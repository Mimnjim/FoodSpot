import { useState } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router';
import Home from './pages/Home.jsx';
import RestaurantDetail from './pages/RestaurantDetail.jsx';

export default function App() {
    const [favorites, setFavorites] = useState([]);

    function toggleFavorite(restaurantId) {
      setFavorites(currentFavorites => {
        if (currentFavorites.includes(restaurantId)) {
          return currentFavorites.filter(
            (id) => id !== restaurantId
          )
        }

        return [...currentFavorites, restaurantId];
      })
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