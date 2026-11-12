
import { Link } from 'react-router';
import '../styles/Header.css';

export default function Header() {
    return (
        <header className="header">
            <Link to="/" className="header-logo">
                FoodSpot
            </Link>

            <nav className="header-nav">
                <ul>
                    <li>
                        <Link to="/">Explorer</Link>
                    </li>

                    <li>
                        <Link to="/favorites">Favoris</Link>
                    </li>
                    <li>
                        <Link to="/group">À plusieurs</Link>
                    </li>

                    <li>
                        <Link to="/about">À propos</Link>
                    </li>

                    <li>
                        <Link to="/group" className="header-button">Créer une session</Link>
                    </li>
                </ul>
            </nav>
        </header>
    );
}
