import { ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchUserProfile, apiLogout } from '@/api';
import { subscribeToPlan } from '@/services/subscriptionService';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = ref(false);
    const subscription = ref(null);
    const isLoading = ref(false);
    const router = useRouter();

    async function fetchUser() {
        try {
            const data = await fetchUserProfile();
            user.value = data.user;
            isAuthenticated.value = data.isAuthenticated;
            subscription.value = data.subscription;
        } catch {
            user.value = null;
            isAuthenticated.value = false;
            subscription.value = null;
        }
    }

    async function subscribe(planName) {
        isLoading.value = true;
        try {
            const result = await subscribeToPlan(planName);
            if (result.url) {
                window.location.href = result.url;
            } else {
                await this.fetchUser();
            }
            return result;
        } finally {
            isLoading.value = false;
        }
    }

    async function logout() {
        try {
            await apiLogout();
        } catch (error) {
            console.error('Erreur lors de la déconnexion :', error);
        } finally {
            document.cookie = 'AUTH_TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

            user.value = null;
            isAuthenticated.value = false;
            subscription.value = null;

            router.push('/');
        }
    }

    return {
        user,
        isAuthenticated,
        subscription,
        isLoading,
        subscribe,
        fetchUser,
        logout,
    };
});
