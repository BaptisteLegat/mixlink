import { fetchWithAuth } from '@/api.js';

export async function subscribeToPlan(planName) {
    const response = await fetchWithAuth(`/api/subscribe/${planName}`, {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error('Subscription failed');
    }

    return await response.json();
}

export async function cancelSubscription() {
    return fetchWithAuth('/api/subscription/cancel', {
        method: 'POST',
    });
}
