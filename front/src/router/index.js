import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '@/views/HomeView.vue';
import LoginView from '@/views/LoginView.vue';
import { useAuthStore } from '@/stores/authStore';

const routes = [
    { path: '/', component: HomeView },
    { path: '/login', component: LoginView },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (!authStore.user) {
        await authStore.fetchUser();
    }

    if (to.path === '/login' && authStore.isAuthenticated) {
        return next('/');
    }

    next();
});

export default router;
