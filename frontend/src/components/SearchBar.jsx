import React from 'react';
import '../styles/SearchBar.css';

export default function SearchBar({ search, setSearch, city, setCity }) {
    return (
        <div className="search-bar">

            <input
                type="text"
                placeholder="Search a restaurant, cuisine or city..."
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