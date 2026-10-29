
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
                        <Link to="/">Explore</Link>
                    </li>

                    <li>
                        <Link to="/favorites">Favorites</Link>
                    </li>

                    <li>
                        <Link to="/">About</Link>
                    </li>

                    <li>
                        <button
                            type="button"
                            className="header-button"
                        >
                            + Add a restaurant
                        </button>
                    </li>
                </ul>
            </nav>
        </header>
    );
}
