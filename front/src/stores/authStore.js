import { ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchUserProfile, apiLogout, apiDeleteAccount, apiDisconnectProvider } from '@/api';
import { subscribeToPlan } from '@/services/subscriptionService';
import { useRouter } from 'vue-router';
import { useSessionStore } from '@/stores/sessionStore';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = ref(false);
    const subscription = ref(null);
    const providers = ref([]);
    const isLoading = ref(false);
    const router = useRouter();

    async function fetchUser() {
        try {
            const response = await fetchUserProfile();

            if (response && Object.keys(response).length > 0) {
                user.value = {
                    id: response.id,
                    email: response.email,
                    firstName: response.firstName,
                    lastName: response.lastName,
                    profilePicture: response.profilePicture,
                    roles: response.roles,
                };
                isAuthenticated.value = true;
                subscription.value = response.subscription || null;
                providers.value = response.providers || [];

                const sessionStore = useSessionStore();
                if (response.currentSession) {
                    sessionStore.setCurrentSession(response.currentSession);
                } else {
                    sessionStore.leaveCurrentSession();
                }

                return true;
            } else {
                resetUserState();

                return false;
            }
        } catch (error) {
            resetUserState();

            throw error;
        }
    }

    function resetUserState() {
        user.value = null;
        isAuthenticated.value = false;
        subscription.value = null;
        providers.value = [];
    }

    async function subscribe(planName) {
        isLoading.value = true;
        try {
            const result = await subscribeToPlan(planName);
            if (result.url) {
                window.location.href = result.url;
            } else {
                await fetchUser();
            }
            return result;
        } finally {
            isLoading.value = false;
        }
    }

    async function logout() {
        try {
            await apiLogout();
        } finally {
            document.cookie = 'AUTH_TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            resetUserState();
            router.push('/');
        }
    }

    async function deleteAccount() {
        await apiDeleteAccount();
        document.cookie = 'AUTH_TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        resetUserState();

        return true;
    }

    async function disconnectProvider(providerId) {
        const response = await apiDisconnectProvider(providerId);
        if (!response.mainProvider) {
            providers.value = providers.value.filter((provider) => provider.id !== providerId);
        }

        return response;
    }

    return {
        user,
        isAuthenticated,
        subscription,
        providers,
        isLoading,
        subscribe,
        fetchUser,
        logout,
        deleteAccount,
        disconnectProvider,
        resetUserState,
    };
});
