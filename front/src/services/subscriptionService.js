import { fetchWithAuth } from '@/api.js';

export async function subscribeToPlan(planName) {
    const response = await fetchWithAuth(`/api/subscribe/${planName}`, {
        method: 'GET',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const error = new Error('Failed to subscribe to plan');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return await response.json();
}

export async function cancelSubscription() {
    const response = await fetchWithAuth('/api/subscription/cancel', {
        method: 'POST',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const error = new Error('Failed to cancel subscription');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return await response.json();
}

export async function changeSubscription(planName) {
    const response = await fetchWithAuth(`/api/subscription/change/${planName}`, {
        method: 'POST',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const error = new Error('Failed to change subscription');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return await response.json();
}

export async function getSubscriptionDetails() {
    const response = await fetchWithAuth('/api/subscription/details', {
        method: 'GET',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const error = new Error('Failed to get subscription details');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return await response.json();
}
