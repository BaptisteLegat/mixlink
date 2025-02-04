import { createRouter, createWebHistory } from 'vue-router';
import Home from '@/views/Home.vue';
import Login from '@/views/Login.vue';
import { useAuthStore } from '@/stores/authStore';

const routes = [
    { path: '/', component: Home },
    { path: '/login', component: Login },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (token) {
        const authStore = useAuthStore();
        authStore.setToken(token);

        window.history.replaceState({}, document.title, to.path);

        await authStore.fetchUser();

        next('/');
    } else {
        next();
    }
});

export default router;
