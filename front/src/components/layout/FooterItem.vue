<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import { useRouter } from 'vue-router';
    import FacebookIcon from 'vue-material-design-icons/Facebook.vue';
    import TwitterIcon from 'vue-material-design-icons/Twitter.vue';
    import InstagramIcon from 'vue-material-design-icons/Instagram.vue';
    import LinkedinIcon from 'vue-material-design-icons/Linkedin.vue';

    const { t } = useI18n();
    const router = useRouter();

    const navigateTo = (path) => router.push(path);

    const socialLinks = [
        { name: 'Facebook', icon: FacebookIcon, url: 'https://facebook.com' },
        { name: 'Twitter', icon: TwitterIcon, url: 'https://twitter.com' },
        { name: 'Instagram', icon: InstagramIcon, url: 'https://instagram.com' },
        { name: 'LinkedIn', icon: LinkedinIcon, url: 'https://linkedin.com' },
    ];

    const footerLinks = [
        { text: 'footer.terms', path: '/terms' },
        { text: 'footer.privacy', path: '/privacy' },
        { text: 'footer.contact', path: '/contact' },
        { text: 'footer.faq', path: '/faq' },
    ];
</script>
<template>
    <footer class="footer">
        <div class="footer-container">
            <div class="logo-section">
                <button @click="navigateTo('/')" class="logo-link" :aria-label="t('header.logo_link')" :title="t('header.logo_link')">
                    <span class="logo-wrapper">
                        <h1 :class="isDark ? 'secondary-dark' : 'secondary'" class="logo-text">mix</h1>
                        <el-image
                            :src="isDark ? '/logo-dark.svg' : '/logo.svg'"
                            alt="mixlink logo"
                            class="logo"
                            fit="contain"
                            loading="eager"
                            width="48"
                            height="48"
                        />
                        <h1 :class="isDark ? 'primary-dark' : 'primary'" class="logo-text">link</h1>
                    </span>
                </button>
            </div>

            <nav class="links-section" aria-label="Footer navigation">
                <ul class="footer-nav">
                    <li v-for="(link, index) in footerLinks" :key="`footer-link-${index}`">
                        <button class="footer-link" @click="navigateTo(link.path)" :style="{ minWidth: '80px' }">
                            {{ t(link.text) }}
                        </button>
                    </li>
                </ul>
            </nav>

            <div class="social-section">
                <ul class="social-wrapper">
                    <li v-for="(social, index) in socialLinks" :key="`social-${index}`">
                        <a :href="social.url" target="_blank" rel="noopener noreferrer" :aria-label="social.name" class="social-link">
                            <component :is="social.icon" class="social-icon" />
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-container">
                <p class="copyright">© 2025 mixlink</p>
            </div>
        </div>
    </footer>
</template>

<style scoped>
    .footer {
        border-top: 1px solid var(--el-border-color);
        padding: 24px 0;
        min-height: 128px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .logo-section {
        flex-shrink: 0;
    }

    .logo-link {
        background: none;
        border: none;
        cursor: pointer;
    }

    .logo-wrapper {
        display: flex;
        align-items: center;
        min-width: 140px;
        min-height: 48px;
    }

    .logo {
        width: 48px;
        height: 48px;
        min-width: 48px;
        min-height: 48px;
        flex-shrink: 0;
        display: inline-block;
    }

    .logo-text {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 4px;
        white-space: nowrap;
        line-height: 1;
    }

    .footer-nav {
        list-style: none;
        display: flex;
        gap: 24px;
        padding: 0;
        margin: 0;
    }

    .footer-link {
        font-size: 0.95rem;
        font-weight: 500;
        padding: 8px 12px;
        border-radius: 8px;
        white-space: nowrap;
        background: none;
        border: none;
        cursor: pointer;
        position: relative;
        color: var(--el-text-color-primary) !important;
    }

    .footer-link::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--el-color-primary);
        transition: width 0.3s;
        transform: translateX(-50%);
    }

    .footer-link:hover::after {
        width: 80%;
    }

    .social-wrapper {
        list-style: none;
        display: flex;
        gap: 16px;
        padding: 0;
        margin: 0;
    }

    .social-link {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .social-icon {
        width: 24px;
        height: 24px;
        min-width: 24px;
        min-height: 24px;
        flex-shrink: 0;
        display: inline-block;
    }

    .footer-bottom {
        margin-top: 20px;
    }

    .footer-bottom-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
    }

    .copyright {
        margin: 0;
        font-size: 0.875rem;
        color: var(--el-text-color-secondary);
        font-weight: 400;
    }

    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .logo-wrapper {
            min-width: 120px;
            min-height: 40px;
        }

        .logo-text {
            font-size: 1.6rem;
        }

        .logo {
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
        }

        .footer-nav {
            flex-wrap: wrap;
            gap: 12px 16px;
            justify-content: center;
        }

        .footer-link {
            min-width: 80px;
        }

        .social-link {
            width: 36px;
            height: 36px;
        }

        .social-icon {
            width: 20px;
            height: 20px;
            min-width: 20px;
            min-height: 20px;
        }

        .footer-bottom {
            padding: 12px 0;
            margin-top: 16px;
        }

        .copyright {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 480px) {
        .logo-wrapper {
            min-width: 110px;
            min-height: 36px;
        }

        .logo-text {
            font-size: 1.4rem;
        }

        .logo {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
        }

        .footer-link {
            font-size: 0.85rem;
            padding: 6px 10px;
            min-width: 75px;
        }

        .social-link {
            width: 32px;
            height: 32px;
        }

        .social-icon {
            width: 18px;
            height: 18px;
            min-width: 18px;
            min-height: 18px;
        }

        .footer-bottom {
            padding: 10px 0;
            margin-top: 12px;
        }

        .copyright {
            font-size: 0.75rem;
        }
    }
</style>
