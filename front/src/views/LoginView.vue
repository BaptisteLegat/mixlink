<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import { ref, onMounted } from 'vue';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '@/stores/authStore';
    import GoogleIcon from 'vue-material-design-icons/Google.vue';
    import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';
    import { useMotion } from '@vueuse/motion';
    import GradientBackground from '@/components/ui/GradientBackground.vue';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();
    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

    const cardRef = ref(null);
    const googleButtonRef = ref(null);
    const spotifyButtonRef = ref(null);

    const redirectTo = (provider) => {
        window.location.href = `${API_BASE_URL}/auth/${provider}`;
    };

    onMounted(() => {
        if (authStore.isAuthenticated) {
            router.push('/');
        }

        useMotion(cardRef, {
            initial: { opacity: 0, y: 20 },
            enter: {
                opacity: 1,
                y: 0,
                transition: { duration: 600 },
            },
        });

        useMotion(googleButtonRef, {
            initial: { opacity: 0, x: -20 },
            enter: {
                opacity: 1,
                x: 0,
                transition: { delay: 300, duration: 500 },
            },
        });

        useMotion(spotifyButtonRef, {
            initial: { opacity: 0, x: 20 },
            enter: {
                opacity: 1,
                x: 0,
                transition: { delay: 500, duration: 500 },
            },
        });
    });
</script>
<template>
    <div class="login-page">
        <GradientBackground :showGrid="true" />
        <el-container class="login-container" v-if="!authStore.isAuthenticated">
            <el-space direction="vertical" class="login-section" :fill="true" :size="20">
                <el-row justify="center" align="middle">
                    <el-col :xs="22" :sm="16" :md="10" :lg="8" :xl="6">
                        <el-card ref="cardRef" shadow="hover" class="login-card" :class="{ 'login-card-dark': isDark }">
                            <div class="login-header">
                                <el-text tag="h1" class="login-title">
                                    <span class="login-title-part">{{ t('login.title') }}</span>
                                </el-text>
                                <el-text tag="p" class="login-subtitle">
                                    {{ t('login.welcome_message') }}
                                </el-text>
                            </div>
                            <el-divider />
                            <div class="login-buttons">
                                <el-button ref="googleButtonRef" size="large" type="default" plain @click="redirectTo('google')" class="oauth-button">
                                    <GoogleIcon :size="24" style="min-width: 24px; margin-right: 10px" />
                                    <span>{{ t('login.connect_with_google') }}</span>
                                </el-button>

                                <el-button ref="spotifyButtonRef" size="large" type="success" @click="redirectTo('spotify')" class="oauth-button">
                                    <SpotifyIcon :size="24" style="min-width: 24px; margin-right: 10px" />
                                    <span>{{ t('login.connect_with_spotify') }}</span>
                                </el-button>
                            </div>
                            <div style="text-align: center; margin-top: 24px">
                                <el-text tag="p">
                                    {{ t('login.terms_notice') }}
                                    <el-link type="primary" href="/terms">
                                        {{ t('login.terms_link') }}
                                    </el-link>
                                </el-text>
                            </div>
                        </el-card>
                        <el-button text style="display: block; margin: 20px auto 0" @click="$router.push('/')">
                            ← {{ t('profile.back_to_home') }}
                        </el-button>
                    </el-col>
                </el-row>
            </el-space>
        </el-container>
    </div>
</template>
<style scoped lang="scss">
    .login-page {
        position: relative;
        min-height: calc(100vh - 160px);
        overflow: hidden;
    }

    .login-container {
        position: relative;
        z-index: 10;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 160px);
    }

    .login-section {
        width: 100%;
        padding: 30px 20px;
    }

    .login-card {
        border-radius: 16px;
        border: none;
        backdrop-filter: blur(20px);
        background: rgba(255, 255, 255, 0.1);
        box-shadow:
            0 15px 35px rgba(96, 35, 192, 0.2),
            0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;

        &:hover {
            transform: translateY(-5px);
            box-shadow:
                0 20px 40px rgba(96, 35, 192, 0.25),
                0 10px 20px rgba(0, 0, 0, 0.15);
        }
    }

    .login-card-dark {
        background: rgba(26, 26, 26, 0.8);
        box-shadow:
            0 15px 35px rgba(0, 0, 0, 0.4),
            0 5px 15px rgba(96, 35, 192, 0.2);
    }

    .login-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .login-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 16px;

        .login-title-part {
            background: linear-gradient(135deg, #6023c0, #9067e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(96, 35, 192, 0.3);
        }
    }

    .login-subtitle {
        font-size: 1rem;
        color: var(--el-text-color-secondary);
        line-height: 1.5;
    }

    .login-buttons {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin: 16px 0;
        width: 100%;
    }

    .oauth-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin: 0;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;

        &:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(96, 35, 192, 0.3);

            &::after {
                opacity: 1;
            }
        }

        &:active {
            transform: translateY(0);
        }

        &::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    }

    .dark {
        .particle {
            background: rgba(144, 103, 229, 0.8);
        }

        .grid-overlay {
            background-image:
                linear-gradient(to right, rgba(144, 103, 229, 0.1) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(144, 103, 229, 0.1) 1px, transparent 1px);
        }
    }

    @media (max-width: 768px) {
        .sphere-1,
        .sphere-2,
        .sphere-3 {
            width: 60vw;
            height: 60vw;
        }

        .glow {
            width: 60vw;
            height: 50vh;
        }
    }
</style>
