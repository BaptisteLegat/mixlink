import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '@/views/HomeView.vue';
import { useAuthStore } from '@/stores/authStore';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/LoginView.vue'),
        },
        {
            path: '/profile',
            name: 'profile',
            component: () => import('@/views/ProfileView.vue'),
            meta: { requiresAuth: true },
        },
    ],
});

let userLoaded = false;

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (!userLoaded) {
        try {
            await authStore.fetchUser();

            userLoaded = true;
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else {
        next();
    }
});

export default router;
