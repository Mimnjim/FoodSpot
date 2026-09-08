import React from 'react';
import SearchBar from './SearchBar.jsx';

export default function Hero({ search, setSearch }) {
    return (
        <section className="hero">

            <div className="hero-content">

                <span className="hero-label">
                    RESTAURANT DISCOVERY
                </span>

                <h1>
                    Good food is closer than you think.
                </h1>

                <p>
                    Discover restaurants, hidden gems and places worth remembering.
                </p>

                <SearchBar
                    search={search}
                    setSearch={setSearch}
                />

            </div>

        </section>
    );
}