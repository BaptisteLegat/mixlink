export async function fetchUserProfile() {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/me`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Erreur lors de la récupération du profil');
    }

    const data = await response.json();

    return data;
}

export async function apiLogout() {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/logout`, {
        method: 'POST',
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error('Erreur lors de la déconnexion');
    }

    return response.json();
}

export async function fetchWithAuth(path, options = {}) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}${path}`, {
        ...options,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers,
        },
    });

    if (!response.ok && !response.redirected) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Request failed');
    }

    return response;
}
