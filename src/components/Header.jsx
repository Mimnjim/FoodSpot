import React from 'react';
import '../styles/Header.css';

export default function Header() {
    return (
        <header className="header">

            <a href="/" className="header-logo">
                FoodSpot
            </a>

            <nav className="header-nav">
                <ul>
                    <li>
                        <a href="/">Explore</a>
                    </li>

                    <li>
                        <a href="/">Favorites</a>
                    </li>

                    <li>
                        <a href="/">About</a>
                    </li>

                    <li>
                        <button className="header-button">
                            + Add a restaurant
                        </button>
                    </li>
                </ul>
            </nav>

        </header>
    );
}