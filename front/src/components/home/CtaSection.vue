<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import RocketLaunchIcon from 'vue-material-design-icons/RocketLaunch.vue';

    const { t } = useI18n();
</script>

<template>
    <el-container class="cta-container">
        <el-main>
            <el-space direction="vertical" class="cta-section" :fill="true">
                <el-card class="cta-content" :class="isDark ? 'cta-content-dark' : ''" :body-style="{ padding: 0 }" shadow="hover">
                    <div class="cta-inner">
                        <div class="cta-decoration left"></div>
                        <div class="cta-decoration right"></div>

                        <div class="cta-icon">
                            <RocketLaunchIcon :size="32" />
                        </div>

                        <el-text tag="h2" class="cta-title">{{ t('home.cta.title') }}</el-text>
                        <el-text tag="p" class="cta-subtitle" :class="isDark ? 'cta-text-dark' : ''">
                            {{ t('home.cta.subtitle') }}
                        </el-text>

                        <el-button type="primary" size="large" class="cta-button" @click="$router.push('/login')">
                            {{ t('home.cta.button') }}
                            <span class="button-arrow">→</span>
                        </el-button>
                    </div>
                </el-card>
            </el-space>
        </el-main>
    </el-container>
</template>

<style lang="scss" scoped>
    .cta-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;
    }

    .cta-section {
        position: relative;
        z-index: 1;
        width: 100%;

        @media (min-width: 768px) {
            padding: 80px 24px;
        }
    }

    .cta-content {
        border-radius: 24px;
        border: none;
        overflow: hidden;
        max-width: 900px;
        margin: 0 auto;
        box-shadow: 0 20px 40px rgba(96, 35, 192, 0.15);
        transition: all 0.3s ease;

        &:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(96, 35, 192, 0.2);
        }
    }

    .cta-inner {
        position: relative;
        background-color: #f9f5ff;
        background-image:
            radial-gradient(circle at 10% 90%, rgba(186, 172, 255, 0.4) 0%, rgba(255, 255, 255, 0) 30%),
            radial-gradient(circle at 90% 10%, rgba(96, 35, 192, 0.2) 0%, rgba(255, 255, 255, 0) 40%);
        padding: 70px 60px;
        text-align: center;
        z-index: 1;

        @media (max-width: 768px) {
            padding: 50px 24px;
        }
    }

    .cta-content-dark .cta-inner {
        background-color: #260e4d;
        background-image:
            radial-gradient(circle at 10% 90%, rgba(117, 62, 214, 0.4) 0%, rgba(38, 14, 77, 0) 30%),
            radial-gradient(circle at 90% 10%, rgba(186, 172, 255, 0.2) 0%, rgba(38, 14, 77, 0) 40%);
    }

    .cta-decoration {
        position: absolute;
        border-radius: 50%;
        z-index: 0;

        &.left {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: -50px;
            background: linear-gradient(135deg, rgba(186, 172, 255, 0.5), rgba(96, 35, 192, 0.2));
            filter: blur(40px);
        }

        &.right {
            width: 150px;
            height: 150px;
            top: -30px;
            right: -30px;
            background: linear-gradient(135deg, rgba(96, 35, 192, 0.3), rgba(186, 172, 255, 0.1));
            filter: blur(30px);
        }
    }

    .cta-content-dark .cta-decoration {
        &.left {
            background: linear-gradient(135deg, rgba(117, 62, 214, 0.5), rgba(186, 172, 255, 0.2));
        }

        &.right {
            background: linear-gradient(135deg, rgba(186, 172, 255, 0.3), rgba(117, 62, 214, 0.1));
        }
    }

    .cta-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6023c0, #9067e5);
        color: white;
        margin-bottom: 24px;
        box-shadow: 0 10px 20px rgba(96, 35, 192, 0.3);
    }

    .cta-title {
        font-size: 2.2rem;
        margin-bottom: 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #6023c0, #9067e5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;

        @media (max-width: 768px) {
            font-size: 1.8rem;
        }

        :deep(.el-text__inner) {
            background: inherit;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    }

    .cta-content-dark .cta-title {
        background: linear-gradient(135deg, #baacff, #ffffff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;

        :deep(.el-text__inner) {
            background: inherit;
        }
    }

    .cta-subtitle {
        font-size: 1.25rem;
        line-height: 1.6;
        margin-bottom: 40px;
        color: #555;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        display: block;

        @media (max-width: 768px) {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
    }

    .cta-text-dark {
        color: rgba(255, 255, 255, 0.8);
    }

    .cta-button {
        padding: 0 36px;
        height: 52px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 12px;
        background: linear-gradient(135deg, #6023c0, #9067e5);
        border: none;
        box-shadow: 0 8px 16px rgba(96, 35, 192, 0.3);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(96, 35, 192, 0.4);
            background: linear-gradient(135deg, #6b29d4, #9b74eb);
        }

        &:active {
            transform: translateY(1px);
            box-shadow: 0 5px 10px rgba(96, 35, 192, 0.3);
        }

        .button-arrow {
            margin-left: 8px;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        &:hover .button-arrow {
            transform: translateX(4px);
        }
    }
</style>
