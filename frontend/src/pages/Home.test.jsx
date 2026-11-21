import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Home from './Home.jsx';

vi.mock('../api/foodSpotApi.js', () => ({
    getRestaurants: vi.fn().mockResolvedValue([]),
}));

beforeEach(() => {
    localStorage.clear();
});

afterEach(() => {
    cleanup();
    vi.clearAllMocks();
});

describe('Home city onboarding', () => {
    it('asks for a city on the first visit and saves the choice', () => {
        render(
            <MemoryRouter>
                <Home favorites={[]} onToggleFavorite={vi.fn()} />
            </MemoryRouter>,
        );

        expect(screen.getByRole('dialog')).toBeTruthy();
        fireEvent.change(screen.getByRole('textbox', { name: 'Votre ville' }), {
            target: { value: 'Lyon' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Découvrir les restaurants' }));

        expect(localStorage.getItem('foodspot-city')).toBe('Lyon');
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('does not show onboarding when a city is already saved', () => {
        localStorage.setItem('foodspot-city', 'Bordeaux');

        render(
            <MemoryRouter>
                <Home favorites={[]} onToggleFavorite={vi.fn()} />
            </MemoryRouter>,
        );

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(screen.getByDisplayValue('Bordeaux')).toBeTruthy();
    });
});
