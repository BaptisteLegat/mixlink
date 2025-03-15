import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { createPinia, setActivePinia } from 'pinia';
import Header from '@/components/layout/HeaderItem.vue';
import HeaderMobile from '@/components/layout/HeaderMobile.vue';
import { ElHeader, ElRow, ElCol, ElLink, ElImage, ElDropdown, ElButton, ElSwitch } from 'element-plus';
import { useMediaQuery } from '@vueuse/core';

vi.mock('@/stores/authStore', () => ({
    useAuthStore: vi.fn(() => ({
        isAuthenticated: false,
        logout: vi.fn(),
    })),
}));

vi.mock('@/composables/dark', () => ({
    isDark: false,
}));

vi.mock('@vueuse/core', () => ({
    useMediaQuery: vi.fn(() => false),
}));

const mockRouter = {
    push: vi.fn(),
};

vi.mock('vue-router', () => ({
    useRouter: () => mockRouter,
}));

describe('Header Component', () => {
    let wrapper;
    let i18n;

    beforeEach(() => {
        // Setup i18n
        i18n = createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: {
                    header: {
                        login: 'Login',
                        logout: 'Logout',
                    },
                },
                fr: {
                    header: {
                        login: 'Connexion',
                        logout: 'Déconnexion',
                    },
                },
            },
        });

        const pinia = createPinia();
        setActivePinia(pinia);

        wrapper = mount(Header, {
            global: {
                plugins: [i18n, pinia],
                stubs: {
                    'vue-material-design-icons/Translate.vue': true,
                    'vue-material-design-icons/WhiteBalanceSunny.vue': true,
                    'vue-material-design-icons/MoonWaningCrescent.vue': true,
                    HeaderMobile: true,
                    ElHeader,
                    ElRow,
                    ElCol,
                    ElLink,
                    ElImage,
                    ElDropdown,
                    ElButton,
                    ElSwitch,
                },
                mocks: {
                    $router: mockRouter,
                },
            },
        });
    });

    it('renders correctly', () => {
        expect(wrapper.exists()).toBe(true);
        expect(wrapper.find('h1').text()).toBe('mix');
        expect(wrapper.findAll('h1')[1].text()).toBe('link');
    });

    it('displays the logo properly', () => {
        const logo = wrapper.find('img');
        expect(logo.exists()).toBe(true);
        expect(logo.attributes('src')).toBe('logo.svg');
        expect(logo.attributes('alt')).toBe('mixlink');
    });

    it('displays login button when not authenticated', () => {
        const loginButton = wrapper.find('.el-button');
        expect(loginButton.exists()).toBe(true);
        expect(loginButton.text()).toBe('Login');
    });

    it('navigates to login page when login button is clicked', async () => {
        const loginButton = wrapper.find('.el-button');
        await loginButton.trigger('click');
        expect(mockRouter.push).toHaveBeenCalledWith('/login');
    });

    it('changes language when dropdown item is clicked', async () => {
        const vm = wrapper.vm;
        vm.changeLanguage('fr');
        expect(i18n.global.locale.value).toBe('fr');
    });

    it('uses desktop layout when not on mobile', () => {
        expect(wrapper.findComponent(HeaderMobile).exists()).toBe(false);
        expect(wrapper.find('.el-dropdown').exists()).toBe(true);
        expect(wrapper.find('.el-switch').exists()).toBe(true);
    });
});

describe('Header Component - Mobile View', () => {
    let wrapper;

    beforeEach(() => {
        vi.mocked(useMediaQuery).mockReturnValue(true);

        const i18n = createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: { header: { login: 'Login', logout: 'Logout' } },
                fr: { header: { login: 'Connexion', logout: 'Déconnexion' } },
            },
        });

        const pinia = createPinia();
        setActivePinia(pinia);

        wrapper = mount(Header, {
            global: {
                plugins: [i18n, pinia],
                stubs: {
                    'vue-material-design-icons/Translate.vue': true,
                    'vue-material-design-icons/WhiteBalanceSunny.vue': true,
                    'vue-material-design-icons/MoonWaningCrescent.vue': true,
                    HeaderMobile: true,
                    ElHeader,
                    ElRow,
                    ElCol,
                    ElLink,
                    ElImage,
                },
            },
        });
    });

    it('renders mobile header when on mobile view', () => {
        expect(wrapper.findComponent({ name: 'HeaderMobile' }).exists()).toBe(true);
        expect(wrapper.find('.el-dropdown').exists()).toBe(false);
        expect(wrapper.find('.el-switch').exists()).toBe(false);
    });
});
