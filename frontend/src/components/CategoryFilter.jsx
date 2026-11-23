import React, { useEffect, useState } from 'react';
import '../styles/CategoryFilter.css';

export default function CategoryFilter({
    categories,
    selectedCategory,
    setSelectedCategory
}) {
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        if (!isOpen) return undefined;
        function closeWithEscape(event) {
            if (event.key === 'Escape') setIsOpen(false);
        }
        window.addEventListener('keydown', closeWithEscape);
        return () => window.removeEventListener('keydown', closeWithEscape);
    }, [isOpen]);

    const canonicalCategories = [...new Set(
        (categories || []).map((category) => String(category).trim()).filter(Boolean),
    )].sort((a, b) => a.localeCompare(b, 'fr'));

    return (
        <div className="category-filter">
            <button
                type="button"
                className="category-filter-trigger"
                onClick={() => setIsOpen(true)}
                aria-haspopup="dialog"
            >
                Filtres
                {selectedCategory !== 'Toutes' && (
                    <span>{selectedCategory}</span>
                )}
            </button>

            {isOpen && (
                <div
                    className="category-modal-backdrop"
                    role="presentation"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) setIsOpen(false);
                    }}
                >
                    <section
                        className="category-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="category-modal-title"
                    >
                        <div className="category-modal-header">
                            <div>
                                <span>AFFINER LA RECHERCHE</span>
                                <h2 id="category-modal-title">Choisir une catégorie</h2>
                            </div>
                            <button type="button" onClick={() => setIsOpen(false)} aria-label="Fermer">
                                ×
                            </button>
                        </div>
                        <div className="category-options">
                <button
                                type="button"
                                onClick={() => {
                                    setSelectedCategory('Toutes');
                                    setIsOpen(false);
                                }}
                                className={selectedCategory === 'Toutes' ? 'active' : ''}
                            >
                                Toutes les cuisines
                            </button>
                            {canonicalCategories.map((category) => (
                                <button
                                    type="button"
                                    key={category}
                                    onClick={() => {
                                        setSelectedCategory(category);
                                        setIsOpen(false);
                                    }}
                                    className={selectedCategory === category ? 'active' : ''}
                                >
                                    {category}
                                </button>
                            ))}
                        </div>
                    </section>
                </div>
            )}

            {selectedCategory !== 'Toutes' && (
                <button
                    type="button"
                    className="category-filter-reset"
                    onClick={() => setSelectedCategory('Toutes')}
                >
                    Réinitialiser
                </button>
            )}
        </div>
    );
}