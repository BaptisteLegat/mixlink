import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { fetchUserProfile } from '@/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('accessToken') || null);
    const isAuthenticated = computed(() => !!token.value);

    async function fetchUser() {
        if (!token.value) return;
        try {
            user.value = await fetchUserProfile(token.value);
        } catch {
            logout();
        }
    }

    function setToken(newToken) {
        token.value = newToken;
        localStorage.setItem('accessToken', newToken);
    }

    function logout() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('accessToken');
    }

    return {
        user,
        token,
        isAuthenticated,
        fetchUser,
        setToken,
        logout,
    };
});
