import React from 'react';

export default function SearchBar({ search, setSearch }) {
    return (
        <div className="search-bar">

            <input
                type="text"
                placeholder="Search a restaurant, cuisine or city..."
                value={search}
                onChange={(event) => setSearch(event.target.value)}
            />

        </div>
    );
}