export async function fetchUserProfile(token) {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/me`, {
        method: 'GET',
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Erreur lors de la récupération du profil');
    }

    return response.json();
}
