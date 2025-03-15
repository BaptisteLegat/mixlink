import { ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchUserProfile, apiLogout } from '@/api';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = ref(false);
    const router = useRouter();

    async function fetchUser() {
        try {
            const data = await fetchUserProfile();
            user.value = data.user;
            isAuthenticated.value = data.isAuthenticated;
        } catch {
            user.value = null;
            isAuthenticated.value = false;
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

            router.push('/');
        }
    }

    return {
        user,
        isAuthenticated,
        fetchUser,
        logout,
    };
});
