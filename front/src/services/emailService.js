import { fetchWithAuth } from '@/api.js';

export async function sendContactEmail(formData) {
    const response = await fetchWithAuth('/api/contact', {
        method: 'POST',
        body: JSON.stringify(formData),
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const error = new Error('Failed to send contact email');
        if (errorData.error) {
            error.translationKey = errorData.error;
        }
        throw error;
    }

    return await response.json();
}
