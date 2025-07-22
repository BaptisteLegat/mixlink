import { fetchWithAuth } from '@/api.js';

export async function subscribeToPlan(planName) {
    const response = await fetchWithAuth(`/api/subscribe/${planName}`, {
        method: 'GET',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'subscription.start.error_unable_to_create_checkout_session');
    }

    return await response.json();
}

export async function cancelSubscription() {
    const response = await fetchWithAuth('/api/subscription/cancel', {
        method: 'POST',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'subscription.cancel.error_failed_to_cancel_subscription');
    }

    return await response.json();
}

export async function changeSubscription(planName) {
    const response = await fetchWithAuth(`/api/subscription/change/${planName}`, {
        method: 'POST',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'subscription.change.error_failed_to_change_subscription');
    }

    return await response.json();
}

export async function getSubscriptionDetails() {
    const response = await fetchWithAuth('/api/subscription/details', {
        method: 'GET',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'subscription.error_failed_to_get_details');
    }

    return await response.json();
}
