import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import { afterEach, describe, expect, it, vi } from 'vitest';
import RestaurantCard from './RestaurantCard.jsx';

afterEach(() => {
    cleanup();
});

describe('RestaurantCard', () => {
    it('shows missing Overpass information explicitly', () => {
        render(
            <MemoryRouter>
                <RestaurantCard
                    restaurant={{
                        id: 'osm-42',
                        name: 'Lieu test',
                        category: 'french',
                        city: null,
                        address: null,
                        rating: null,
                        priceRange: null,
                        image: null,
                        provider: 'openstreetmap',
                    }}
                    isFavorite={false}
                    onToggleFavorite={vi.fn()}
                />
            </MemoryRouter>,
        );

        expect(screen.getByText('Ville non renseignée')).toBeTruthy();
        expect(screen.getByText('Note non renseignée')).toBeTruthy();
        expect(screen.getByText('Prix non renseigné')).toBeTruthy();
        expect(screen.getByText('OpenStreetMap')).toBeTruthy();
    });

    it('calls the favorite handler with the restaurant id', () => {
        const onToggleFavorite = vi.fn();

        render(
            <MemoryRouter>
                <RestaurantCard
                    restaurant={{
                        id: 'local-1',
                        name: 'Sakura',
                        category: 'Japonais',
                        city: null,
                        rating: null,
                        priceRange: null,
                        image: null,
                    }}
                    isFavorite={false}
                    onToggleFavorite={onToggleFavorite}
                />
            </MemoryRouter>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Ajouter Sakura aux favoris' }));
        expect(onToggleFavorite).toHaveBeenCalledWith('local-1');
    });
});
