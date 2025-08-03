export async function fetchUserProfile() {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/me`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('common.error_fetch_profile');
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
        throw new Error('common.error_logout');
    }

    return response.json();
}

export async function apiDeleteAccount() {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/me/delete`, {
        method: 'DELETE',
        credentials: 'include',
    });

    if (!response.ok) {
        const errorData = await response.json();
        const error = new Error('Failed to delete account');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return response.json();
}

export async function apiDisconnectProvider(providerId) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/provider/${providerId}/disconnect`, {
        method: 'POST',
        credentials: 'include',
    });

    if (!response.ok) {
        const errorData = await response.json();
        const error = new Error('Failed to disconnect provider');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
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
        const error = new Error('Request failed');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return response;
}

export async function updateSoundCloudEmail(email) {
    const response = await fetchWithAuth('/api/me/email', {
        method: 'PATCH',
        body: JSON.stringify({ email }),
    });
    return response.json();
}

export function getOAuthUrl(provider) {
    return `${import.meta.env.VITE_API_BASE_URL}/api/auth/${provider}`;
}

export async function searchMusicApi(query) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/search/music?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    });
    const data = await response.json();
    if (!response.ok) {
        const error = new Error('Search failed');
        if (data.error) {
            error.translationKey = data.error;
        }
        throw error;
    }
    return data;
}

export async function apiAddSongToPlaylist(playlistId, songData) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/playlist/${playlistId}/add-song`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(songData),
    });

    const data = await response.json();
    if (!response.ok) {
        const error = new Error('Failed to add song');
        if (data.errors) {
            error.errors = data.errors;
        } else if (data.error) {
            error.translationKey = data.error;
        }
        throw error;
    }
    return data;
}

export async function apiRemoveSongFromPlaylist(playlistId, spotifyId) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/playlist/${playlistId}/remove-song/${spotifyId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
    });

    const data = await response.json();
    if (!response.ok) {
        const error = new Error('Failed to remove song');
        if (data.error) {
            error.translationKey = data.error;
        }
        throw error;
    }

    return data;
}

export async function apiExportPlaylist(playlistId, platform) {
    const response = await fetchWithAuth(`/api/playlist/${playlistId}/export/${platform}`, {
        method: 'POST',
    });

    if (!response.ok) {
        const errorData = await response.json();
        const error = new Error('Failed to export playlist');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return response.json();
}
