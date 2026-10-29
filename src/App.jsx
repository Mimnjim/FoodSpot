import { BrowserRouter, Routes, Route } from 'react-router';
import Home from './pages/Home.jsx';
import RestaurantDetail from './pages/RestaurantDetail.jsx';

export default function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Home />} />
                <Route
                    path="/restaurant/:id"
                    element={<RestaurantDetail />}
                />
            </Routes>
        </BrowserRouter>
    );
}