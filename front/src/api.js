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
        throw new Error('common.error_delete_account');
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

        throw new Error(errorData.error || 'provider.disconnect.error');
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
        throw new Error(errorData.error || 'common.error_request_failed');
    }

    return response;
}

export function getOAuthUrl(provider) {
    return `${import.meta.env.VITE_API_BASE_URL}/api/auth/${provider}`;
}
