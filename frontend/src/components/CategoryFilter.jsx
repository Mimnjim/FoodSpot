import React from 'react';
import '../styles/CategoryFilter.css';

export default function CategoryFilter({
    categories,
    selectedCategory,
    setSelectedCategory
}) {
    return (
        <div className="category-filter">

            <button
                onClick={() => setSelectedCategory('Toutes')}
                className={
                    selectedCategory === 'Toutes'
                        ? 'active'
                        : ''
                }
            >
                Toutes
            </button>

            {categories.map((category) => (
                <button
                    key={category}
                    onClick={() => setSelectedCategory(category)}
                    className={
                        selectedCategory === category
                            ? 'active'
                            : ''
                    }
                >
                    {category}
                </button>
            ))}

        </div>
    );
}