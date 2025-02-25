import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { fetchUserProfile, apiLogout } from '@/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        try {
            user.value = await fetchUserProfile();
        } catch {
            user.value = null;
        }
    }

    async function logout() {
        try {
            await apiLogout();
        } finally {
            document.cookie = "AUTH_TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            user.value = null;
        }
    }

    return {
        user,
        isAuthenticated,
        fetchUser,
        logout,
    };
});
