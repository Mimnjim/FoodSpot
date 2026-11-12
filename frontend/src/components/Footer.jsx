import { Link } from 'react-router';
import '../styles/Footer.css';

export default function Footer() {
    return (
        <footer className="footer">

            <div className="footer-logo">
                FoodSpot
            </div>

            <p>
                Découvrez des lieux qui méritent d'être retenus.
            </p>

            <div className="footer-links">

                <Link to="/">Explorer</Link>

                <Link to="/favorites">Favoris</Link>

                <Link to="/about">À propos</Link>

            </div>

            <p>
                © 2026 FoodSpot
            </p>

            <p>© OpenStreetMap contributors</p>

        </footer>
    );
}