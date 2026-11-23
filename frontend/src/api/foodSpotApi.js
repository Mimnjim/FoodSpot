export async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
    });

    const text = await response.text();
    let data;
    try {
        data = text ? JSON.parse(text) : {};
    } catch {
        throw new Error(
            response.status >= 500
                ? 'Le serveur rencontre un problème temporaire.'
                : 'La réponse du serveur est invalide.',
        );
    }
    if (!response.ok) {
        const error = new Error(data.message || 'Une erreur est survenue.');
        error.status = response.status;
        throw error;
    }

    return data;
}

export async function getRestaurants(filters = {}, signal) {
    const params = new URLSearchParams();

    if (filters.category && filters.category !== 'Toutes') {
        params.set('category', filters.category);
    }
    if (filters.search?.trim()) {
        params.set('search', filters.search.trim());
    }
    if (filters.city?.trim()) {
        params.set('city', filters.city.trim());
    }

    const query = params.toString();
    const timeoutController = new AbortController();
    const timeout = window.setTimeout(() => timeoutController.abort(), 3200);

    let combinedSignal = timeoutController.signal;
    if (signal) {
        combinedSignal = timeoutController.signal;
        const abort = () => timeoutController.abort();
        signal.addEventListener('abort', abort, { once: true });
    }

    let payload;
    try {
        payload = await apiRequest(
            `/api/restaurants${query ? `?${query}` : ''}`,
            { signal: combinedSignal },
        );
    } catch (error) {
        if (timeoutController.signal.aborted && !signal?.aborted) {
            throw new Error('La recherche prend trop de temps. Réessayez ou choisissez une autre ville.');
        }
        throw error;
    } finally {
        window.clearTimeout(timeout);
    }

    return payload.restaurants ?? payload;
}

export function getRestaurant(id) {
    return apiRequest(`/api/restaurants/${encodeURIComponent(id)}`);
}
