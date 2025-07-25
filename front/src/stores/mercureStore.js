import { ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchWithAuth } from '@/api.js';

export const useMercureStore = defineStore('mercure', () => {
    const mercureConnection = ref(null);
    const isConnected = ref(false);
    const lastMessage = ref(null);
    const error = ref(null);

    async function connect({ sessionCode, isHost, onMessage, onError }) {
        if (mercureConnection.value) {
            mercureConnection.value.close();
        }
        try {
            const tokenEndpoint = isHost ? `/api/mercure/auth/host/${sessionCode}` : `/api/mercure/auth/${sessionCode}`;
            const tokenResponse = await fetchWithAuth(tokenEndpoint, {
                method: 'GET',
            });
            if (!tokenResponse.ok) {
                error.value = await tokenResponse.text();
                isConnected.value = false;

                return;
            }
            const tokenData = await tokenResponse.json();
            const url = new URL(tokenData.mercureUrl);
            url.searchParams.append('topic', `session/${sessionCode}`);
            url.searchParams.append('authorization', tokenData.token);
            mercureConnection.value = new EventSource(url.toString());
            mercureConnection.value.onmessage = (event) => {
                const data = JSON.parse(event.data);
                lastMessage.value = data;
                if (onMessage) {
                    onMessage(data);
                }
            };
            mercureConnection.value.onerror = (e) => {
                error.value = e;
                isConnected.value = false;
                if (onError) {
                    onError(e);
                }
            };
            isConnected.value = true;
        } catch (e) {
            error.value = e;
            isConnected.value = false;
            if (onError) {
                onError(e);
            }
        }
    }

    function disconnect() {
        if (mercureConnection.value) {
            mercureConnection.value.close();
            mercureConnection.value = null;
            isConnected.value = false;
        }
    }

    return {
        mercureConnection,
        isConnected,
        lastMessage,
        error,
        connect,
        disconnect,
    };
});
