import React from 'react';
import '../styles/SearchBar.css';

export default function SearchBar({ search, setSearch, city, setCity }) {
    return (
        <div className="search-bar" role="search">

            <input
                type="text"
                placeholder="Rechercher un restaurant, une cuisine ou une ville…"
                aria-label="Rechercher un restaurant ou une cuisine"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
            />

            <input
                type="text"
                placeholder="Ville, par exemple Paris"
                aria-label="Ville de recherche"
                value={city}
                onChange={(event) => setCity(event.target.value)}
            />

        </div>
    );
}