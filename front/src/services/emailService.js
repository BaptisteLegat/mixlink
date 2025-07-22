import { fetchWithAuth } from '@/api.js';

export async function sendContactEmail(formData) {
    const response = await fetchWithAuth('/api/contact', {
        method: 'POST',
        body: JSON.stringify(formData),
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'contact.form.error_message');
    }

    return await response.json();
}
