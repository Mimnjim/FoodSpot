import { afterEach, describe, expect, it, vi } from 'vitest';
import { getRestaurant, getRestaurants } from './foodSpotApi.js';

afterEach(() => {
    vi.restoreAllMocks();
});

describe('foodSpotApi', () => {
    it('serializes restaurant filters', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            status: 200,
            json: async () => ({
                restaurants: [{ id: 'osm-1', name: 'Test' }],
            }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const restaurants = await getRestaurants({
            city: ' Paris ',
            category: 'italian',
            search: ' Mama ',
        });

        expect(restaurants).toHaveLength(1);
        expect(fetchMock).toHaveBeenCalledOnce();
        expect(fetchMock.mock.calls[0][0]).toBe(
            '/api/restaurants?category=italian&search=Mama&city=Paris',
        );
    });

    it('exposes HTTP status for not found restaurants', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
            ok: false,
            status: 404,
            json: async () => ({ message: 'Restaurant introuvable' }),
        }));

        await expect(getRestaurant('missing')).rejects.toMatchObject({
            status: 404,
            message: 'Restaurant introuvable',
        });
    });
});
