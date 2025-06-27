import { fetchWithAuth } from '@/api.js';

export async function sendContactEmail(formData) {
    const response = await fetchWithAuth('/api/contact', {
        method: 'POST',
        body: JSON.stringify(formData),
    });

    if (!response.ok) {
        throw new Error('Email sending failed');
    }

    return await response.json();
}
