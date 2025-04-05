<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';
    import YoutubeIcon from 'vue-material-design-icons/Youtube.vue';

    const { t } = useI18n();
</script>

<template>
    <el-space direction="vertical" class="hero-section" :fill="true">
        <el-row :gutter="20" justify="center" align="middle">
            <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                <el-space direction="vertical">
                    <el-text tag="h1" class="hero-title">
                        {{ t('home.title_1') }} <span :class="isDark ? 'primary-dark' : 'primary'">{{ t('home.title_2') }}</span>
                        {{ t('home.title_3') }}
                    </el-text>
                    <el-text tag="p" class="hero-subtitle">
                        {{ t('home.subtitle') }}
                    </el-text>
                    <el-space>
                        <el-button type="primary" size="large" @click="$router.push('/login')">{{ t('home.start_free') }}</el-button>
                        <el-button size="large" bg>{{ t('home.view_demo') }}</el-button>
                    </el-space>
                </el-space>
                <el-space class="supported-platforms">
                    <SpotifyIcon :size="32" />
                    <YoutubeIcon :size="32" />
                </el-space>
            </el-col>
            <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                <div class="hero-image">
                    <el-image :src="isDark ? '/img/hero-dark.svg' : '/img/hero.svg'" alt="MixLink Demo" fit="contain" class="floating" />
                </div>
            </el-col>
        </el-row>
    </el-space>
</template>

<style scoped>
    .hero-section {
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .hero-subtitle {
        font-size: 1.2rem;
        line-height: 1.6;
        margin-bottom: 30px;
        color: var(--el-text-color-secondary);
    }

    .supported-platforms {
        display: flex;
        gap: 20px;
        color: var(--el-text-color-secondary);
    }

    .hero-image {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .floating {
        animation: float 4s ease-in-out infinite;
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-15px);
        }
        100% {
            transform: translateY(0px);
        }
    }

    .primary {
        color: #6023c0;
    }

    .primary-dark {
        color: #753ed6;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.2rem;
        }

        .hero-section {
            padding: 40px 0;
            text-align: center;
        }

        .hero-cta {
            justify-content: center;
        }

        .supported-platforms {
            justify-content: center;
        }
    }
</style>
