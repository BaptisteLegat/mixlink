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
    const response = await fetchWithAuth('/api/subscription/cancel', {
        method: 'POST',
    });

    if (!response.ok) {
        throw new Error('Cancellation failed');
    }

    return await response.json();
}

export async function changeSubscription(planName) {
    const response = await fetchWithAuth(`/api/subscription/change/${planName}`, {
        method: 'POST',
    });

    if (!response.ok) {
        throw new Error('Change subscription failed');
    }

    return await response.json();
}

export async function getSubscriptionDetails() {
    const response = await fetchWithAuth('/api/subscription/details', {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error('Failed to get subscription details');
    }

    return await response.json();
}
