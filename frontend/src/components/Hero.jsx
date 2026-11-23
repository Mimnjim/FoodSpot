import React from 'react';
import { Link } from 'react-router';
import '../styles/Hero.css';

export default function Hero() {
    return (
        <section className="hero">

            <div className="hero-content">

                <span className="hero-label">
                    TROUVER ENSEMBLE
                </span>

                <h1>
                    Qui veut manger quoi ?
                </h1>

                <p>
                    Répondez vite à quelques questions et trouvez le lieu qui rassemble tout le monde.
                </p>
                <div className="hero-actions">
                    <Link to="/group" className="hero-primary-action">Décider à plusieurs</Link>
                    <a href="#explorer" className="hero-secondary-action">Explorer seul</a>
                </div>
                <div className="hero-proof">
                    <span><strong>1</strong> session</span>
                    <span><strong>2 min</strong> pour répondre</span>
                    <span><strong>1</strong> choix partagé</span>
                </div>

            </div>

        </section>
    );
}