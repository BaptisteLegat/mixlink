import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { createPinia, setActivePinia } from 'pinia';
import HeaderMobile from '@/components/layout/HeaderMobile.vue';
import { ElLink, ElDrawer, ElMenu, ElMenuItem } from 'element-plus';
import MenuIcon from 'vue-material-design-icons/Menu.vue';
import { useAuthStore } from '@/stores/authStore';

const isDarkMock = vi.hoisted(() => ({ value: false }));

vi.mock('@/stores/authStore', () => ({
    useAuthStore: vi.fn(() => ({
        isAuthenticated: false,
        logout: vi.fn(),
    })),
}));

vi.mock('@/composables/dark', () => ({
    isDark: isDarkMock,
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

describe('HeaderMobile Component', () => {
    let wrapper;
    let i18n;

    beforeEach(() => {
        i18n = createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: {
                    header: {
                        login: 'Login',
                        logout: 'Logout',
                        dark_mode: 'Dark mode',
                        light_mode: 'Light mode',
                    },
                },
                fr: {
                    header: {
                        login: 'Connexion',
                        logout: 'Déconnexion',
                        dark_mode: 'Mode sombre',
                        light_mode: 'Mode clair',
                    },
                },
            },
        });

        const pinia = createPinia();
        setActivePinia(pinia);

        wrapper = mount(HeaderMobile, {
            global: {
                plugins: [i18n, pinia],
                components: {
                    ElLink,
                    ElDrawer,
                    ElMenu,
                    ElMenuItem,
                    MenuIcon,
                },
                mocks: {
                    $router: mockRouter,
                },
            },
        });
    });

    it('should open the drawer when clicking the menu icon', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        expect(wrapper.findComponent(ElDrawer).exists()).toBe(true);
    });

    it('should have a menu with 3 items', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        expect(menu.findAllComponents(ElMenuItem).length).toBe(3);
    });

    it('should toggle language when clicking on the language item', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const languageItem = menu.findAllComponents(ElMenuItem)[0];
        await languageItem.trigger('click');

        expect(i18n.global.locale.value).toBe('fr');
    });

    it('should toggle dark mode when clicking on the dark mode item', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const darkModeItem = menu.findAllComponents(ElMenuItem)[1];
        await darkModeItem.trigger('click');

        expect(isDarkMock.value).toBe(true);
    });

    it('should navigate to login page when clicking on the login item', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const loginItem = menu.findAllComponents(ElMenuItem)[2];
        await loginItem.trigger('click');

        expect(mockRouter.push).toHaveBeenCalledWith('/login');
    });

    it('should display the correct theme text (dark mode)', async () => {
        isDarkMock.value = true;

        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const themeItem = menu.findAllComponents(ElMenuItem)[1];
        expect(themeItem.text()).toContain('Light mode 🌞');
    });

    it('should display the correct theme text (light mode)', async () => {
        isDarkMock.value = false;

        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const themeItem = menu.findAllComponents(ElMenuItem)[1];
        expect(themeItem.text()).toContain('Dark mode 🌙');
    });

    it('should display logout button when user is authenticated', async () => {
        vi.mocked(useAuthStore).mockReturnValue({
            isAuthenticated: true,
            logout: vi.fn(),
        });

        wrapper = mount(HeaderMobile, {
            global: {
                plugins: [i18n, createPinia()],
                components: {
                    ElLink,
                    ElDrawer,
                    ElMenu,
                    ElMenuItem,
                    MenuIcon,
                },
                mocks: {
                    $router: mockRouter,
                },
            },
        });

        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const logoutItem = menu.findAllComponents(ElMenuItem)[2];
        expect(logoutItem.text()).toContain('Logout');
    });

    it('should call logout function when clicking on the logout item', async () => {
        const logoutMock = vi.fn();
        vi.mocked(useAuthStore).mockReturnValue({
            isAuthenticated: true,
            logout: logoutMock,
        });

        wrapper = mount(HeaderMobile, {
            global: {
                plugins: [i18n, createPinia()],
                components: {
                    ElLink,
                    ElDrawer,
                    ElMenu,
                    ElMenuItem,
                    MenuIcon,
                },
                mocks: {
                    $router: mockRouter,
                },
            },
        });

        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const logoutItem = menu.findAllComponents(ElMenuItem)[2];
        await logoutItem.trigger('click');

        expect(logoutMock).toHaveBeenCalled();
    });

    it('should display the correct language text (English)', async () => {
        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const languageItem = menu.findAllComponents(ElMenuItem)[0];
        expect(languageItem.text()).toContain('English 🇬🇧');
    });

    it('should display the correct language text (French)', async () => {
        i18n.global.locale.value = 'fr';

        const menuIcon = wrapper.findComponent(MenuIcon);
        await menuIcon.trigger('click');

        const menu = wrapper.findComponent(ElMenu);
        const languageItem = menu.findAllComponents(ElMenuItem)[0];
        expect(languageItem.text()).toContain('Français 🇫🇷');
    });
});
