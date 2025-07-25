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

    const navigateTo = (path) => {
        router.push(path);
    };

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
    <el-footer class="footer">
        <el-row justify="center" align="middle" class="footer-container">
            <el-col :span="24" class="text-center">
                <el-link :underline="false" @click="navigateTo('/')">
                    <h1 :class="isDark ? 'secondary-dark' : 'secondary'" class="logo-text">mix</h1>
                    <el-image :src="isDark ? '/logo-dark.svg' : '/logo.svg'" alt="mixlink" class="logo" fit="contain" />
                    <h1 :class="isDark ? 'primary-dark' : 'primary'" class="logo-text">link</h1>
                </el-link>
            </el-col>
            <el-col :span="24" class="text-center social-container">
                <el-space :size="32">
                    <el-link
                        v-for="(social, index) in socialLinks"
                        :key="`social-${index}`"
                        :underline="false"
                        :href="social.url"
                        target="_blank"
                        :aria-label="social.name"
                    >
                        <component :is="social.icon" class="social-icon" />
                    </el-link>
                </el-space>
            </el-col>
            <el-col :span="24" class="text-center link-section">
                <el-space :size="32" wrap class="link-container">
                    <el-link
                        v-for="(link, index) in footerLinks"
                        :key="`footer-link-${index}`"
                        :underline="false"
                        @click="navigateTo(link.path)"
                        class="footer-link"
                    >
                        {{ t(link.text) }}
                    </el-link>
                </el-space>
            </el-col>
        </el-row>
    </el-footer>
</template>
<style scoped>
    .footer {
        border-top: 1px solid #ebeef5;
        padding: 20px 0;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .text-center {
        text-align: center;
    }

    .logo {
        width: 50px;
        vertical-align: middle;
    }

    .logo-text {
        display: inline-block;
        font-size: 2.2rem;
        vertical-align: middle;
    }

    .social-icon {
        width: 28px;
        height: 28px;
        transition: transform 0.3s ease;
    }

    .social-icon:hover {
        transform: scale(1.2);
    }

    .link-section {
        margin-top: 20px;
    }

    .link-container {
        padding-bottom: 15px;
        gap: 20px !important;
    }

    .footer-link {
        font-size: 1.1rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .logo {
            width: 40px;
            height: 40px;
        }

        .logo-text {
            font-size: 1.8rem;
        }

        .social-container .el-space {
            margin: 0 auto;
        }

        .footer-link {
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .link-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .social-icon {
            width: 24px;
            height: 24px;
        }
    }
</style>
