<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import PlaylistMusicIcon from 'vue-material-design-icons/PlaylistMusic.vue';
    import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue';
    import LinkVariantIcon from 'vue-material-design-icons/LinkVariant.vue';
    import HistoryIcon from 'vue-material-design-icons/History.vue';

    const { t } = useI18n();
    const features = [
        {
            title: 'home.features.feature1.title',
            description: 'home.features.feature1.description',
            icon: PlaylistMusicIcon,
            color: '#6023c0',
            darkColor: '#baacff',
            bgColor: '#f3eaff',
            darkBgColor: '#3a1b71',
        },
        {
            title: 'home.features.feature2.title',
            description: 'home.features.feature2.description',
            icon: ShareVariantIcon,
            color: '#0080ff',
            darkColor: '#7cc1ff',
            bgColor: '#e6f2ff',
            darkBgColor: '#154275',
        },
        {
            title: 'home.features.feature3.title',
            description: 'home.features.feature3.description',
            icon: LinkVariantIcon,
            color: '#00c48f',
            darkColor: '#7dedc9',
            bgColor: '#e6fff7',
            darkBgColor: '#0a5a41',
        },
        {
            title: 'home.features.feature4.title',
            description: 'home.features.feature4.description',
            icon: HistoryIcon,
            color: '#ff6e42',
            darkColor: '#ffb199',
            bgColor: '#fff1ec',
            darkBgColor: '#7a2e16',
        },
    ];
</script>

<template>
    <el-container class="features-container">
        <el-main>
            <el-space direction="vertical" class="features-section" :fill="true" :size="30">
                <el-row justify="center">
                    <el-col :span="24" :lg="18" :xl="16">
                        <el-text tag="h2" class="section-title">{{ t('home.features.title') }}</el-text>
                        <el-text tag="p" size="large" class="section-subtitle">{{ t('home.features.subtitle') }}</el-text>
                    </el-col>
                </el-row>

                <el-row :gutter="24" class="features-row">
                    <el-col v-for="(feature, index) in features" :key="index" :xs="24" :sm="12" :md="12" :lg="6" :xl="6" class="feature-col">
                        <el-card
                            shadow="hover"
                            class="feature-card"
                            :class="[isDark ? 'feature-card-dark' : '', `feature-${index + 1}`]"
                            :body-style="{ padding: '24px 20px', height: '100%', display: 'flex', flexDirection: 'column' }"
                        >
                            <el-space direction="vertical" alignment="center" :size="10" :fill="true">
                                <div
                                    class="feature-icon"
                                    :class="isDark ? 'feature-icon-dark' : ''"
                                    :style="{
                                        backgroundColor: isDark ? feature.darkBgColor : feature.bgColor,
                                        color: isDark ? feature.darkColor : feature.color,
                                    }"
                                >
                                    <component :is="feature.icon" :size="24" />
                                </div>
                                <el-text tag="h3" class="feature-title">{{ t(feature.title) }}</el-text>
                                <el-text tag="p" class="feature-description">{{ t(feature.description) }}</el-text>
                            </el-space>
                        </el-card>
                    </el-col>
                </el-row>
            </el-space>
        </el-main>
    </el-container>
</template>

<style lang="scss" scoped>
    .features-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;

        &::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(96, 35, 192, 0.08), rgba(0, 196, 143, 0.06));
            top: -50px;
            left: -100px;
            z-index: 0;
            filter: blur(60px);
        }

        &::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 110, 66, 0.06), rgba(0, 128, 255, 0.08));
            bottom: -50px;
            right: -100px;
            z-index: 0;
            filter: blur(50px);
        }
    }

    .features-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;

        @media (min-width: 768px) {
            padding: 80px 24px;
        }
    }

    .section-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        background: linear-gradient(135deg, #6023c0, #8347de);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        text-align: center;

        @media (min-width: 768px) {
            font-size: 2.2rem;
        }
    }

    .section-subtitle {
        display: block;
        text-align: center;
        color: var(--el-text-color-secondary);
        line-height: 1.6;
        max-width: 600px;
        margin: 0 auto 20px;
    }

    .features-row {
        width: 100%;
    }

    .feature-col {
        margin-bottom: 24px;
        display: flex;
    }

    .feature-card {
        width: 100%;
        border-radius: 12px;
        background-color: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
        text-align: center;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;

        &::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        &.feature-1::after {
            background: linear-gradient(90deg, #6023c0, #8347de);
        }
        &.feature-2::after {
            background: linear-gradient(90deg, #0080ff, #7cc1ff);
        }
        &.feature-3::after {
            background: linear-gradient(90deg, #00c48f, #47dbb3);
        }
        &.feature-4::after {
            background: linear-gradient(90deg, #ff6e42, #ff9776);
        }

        &:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);

            &::after {
                transform: scaleX(1);
                transform-origin: left;
            }
        }
    }

    .feature-card-dark {
        background-color: #222;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);

        &:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.35);
        }
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        transition: all 0.3s ease;
        transform: rotate(0deg);

        &::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.4));
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover & {
            transform: rotate(-8deg);

            &::before {
                opacity: 1;
            }
        }
    }

    .feature-icon-dark {
        &::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.15));
        }
    }

    .feature-title {
        font-size: 1.25rem;
        margin: 4px 0;
        font-weight: 600;
        transition: transform 0.3s ease;
        display: block;

        .feature-card:hover & {
            transform: translateY(-2px);
        }
    }

    .feature-description {
        color: var(--el-text-color-secondary);
        line-height: 1.5;
        display: block;
        font-size: 0.95rem;
        transition: opacity 0.3s ease;
        margin: 0;

        .feature-card:hover & {
            opacity: 0.9;
        }
    }
</style>
