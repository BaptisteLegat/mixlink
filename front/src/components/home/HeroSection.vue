<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import LandingPageImg from '@/assets/images/landing-page.png';
    import { ref } from 'vue';
    import { useMotion } from '@vueuse/motion';

    const { t } = useI18n();

    const titleRef = ref(null);
    const subtitleRef = ref(null);
    const ctaRef = ref(null);
    const imageRef = ref(null);

    const scrollToFeatures = () => {
        const el = document.getElementById('features');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    };

    useMotion(titleRef, {
        initial: { opacity: 0, x: -30 },
        enter: {
            opacity: 1,
            x: 0,
            transition: {
                duration: 800,
                type: 'spring',
            },
        },
    });

    useMotion(subtitleRef, {
        initial: { opacity: 0, y: 20 },
        enter: {
            opacity: 1,
            y: 0,
            transition: {
                delay: 200,
                duration: 600,
            },
        },
    });

    useMotion(ctaRef, {
        initial: { opacity: 0, y: 30 },
        enter: {
            opacity: 1,
            y: 0,
            transition: {
                delay: 400,
                duration: 600,
            },
        },
    });

    useMotion(imageRef, {
        initial: { opacity: 0, x: 30 },
        enter: {
            opacity: 1,
            x: 0,
            transition: {
                delay: 100,
                duration: 800,
            },
        },
    });
</script>

<template>
    <el-space direction="vertical" class="hero-section" :fill="true">
        <el-row :gutter="20" justify="center" align="middle">
            <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                <el-space direction="vertical">
                    <el-text tag="h1" ref="titleRef" class="hero-title">
                        {{ t('home.title_1') }} <span :class="isDark ? 'primary-dark' : 'primary'">{{ t('home.title_2') }}</span>
                        {{ t('home.title_3') }}
                    </el-text>
                    <el-text tag="p" ref="subtitleRef" class="hero-subtitle">
                        {{ t('home.subtitle') }}
                    </el-text>
                    <el-space ref="ctaRef" class="hero-cta">
                        <el-button
                            type="primary"
                            size="large"
                            @click="$router.push('/login')"
                            v-motion="{
                                hover: {
                                    scale: 1.05,
                                    transition: { duration: 300 },
                                },
                                press: {
                                    scale: 0.95,
                                },
                            }"
                        >
                            {{ t('home.start_free') }}
                        </el-button>
                        <el-button
                            size="large"
                            bg
                            @click="scrollToFeatures"
                            v-motion="{
                                hover: {
                                    scale: 1.05,
                                    transition: { duration: 300 },
                                },
                                press: {
                                    scale: 0.95,
                                },
                            }"
                        >
                            {{ t('home.view_demo') }}
                        </el-button>
                    </el-space>
                </el-space>
            </el-col>
            <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                <div ref="imageRef" class="hero-image">
                    <el-image
                        :src="LandingPageImg"
                        alt="MixLink Demo"
                        fit="contain"
                        v-motion="{
                            initial: { y: 0 },
                            enter: {
                                y: [0, -15, 0],
                                transition: {
                                    duration: 4000,
                                    repeat: Infinity,
                                    ease: 'easeInOut',
                                },
                            },
                        }"
                    />
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

    .hero-image {
        display: flex;
        justify-content: center;
        align-items: center;
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
            text-align: center;
        }

        .hero-section {
            padding: 40px 0;
        }

        .hero-cta {
            justify-content: center;
        }

        .hero-subtitle {
            text-align: center;
        }
    }
</style>
