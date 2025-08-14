<script setup>
    import { useI18n } from 'vue-i18n';
    import { useSEO } from '@/composables/useSEO';

    const { t } = useI18n();

    useSEO('privacy');
</script>

<template>
    <el-container class="privacy-container">
        <el-space direction="vertical" class="privacy-section" :fill="true" :size="30">
            <el-row justify="center">
                <el-col :span="24" :md="20" :lg="18">
                    <el-text tag="h1" class="page-title">{{ t('privacy.title') }}</el-text>
                    <el-divider />
                    <el-text tag="p" class="last-updated">
                        {{ t('privacy.last_updated', { date: '12/08/2025' }) }}
                    </el-text>

                    <el-card shadow="hover" class="privacy-card">
                        <div class="privacy-content">
                            <el-text tag="h2" class="section-title">{{ t('privacy.section1.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section1.content_oauth') }}</el-text>

                            <el-text tag="h2" class="section-title">{{ t('privacy.section2.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section2.content') }}</el-text>

                            <el-text tag="h2" class="section-title">{{ t('privacy.section3.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section3.content') }}</el-text>

                            <el-text tag="h2" class="section-title">{{ t('privacy.section4.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section4.content') }}</el-text>

                            <el-text tag="h2" class="section-title">{{ t('privacy.section5.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section5.content') }}</el-text>

                            <el-text tag="h2" class="section-title">{{ t('privacy.section6.title') }}</el-text>
                            <el-text tag="p" class="section-content">{{ t('privacy.section6.content_oauth') }}</el-text>
                        </div>
                    </el-card>
                </el-col>
            </el-row>
        </el-space>
    </el-container>
</template>

<style lang="scss" scoped>
    .privacy-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;
    }

    .privacy-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;
        width: 100%;
    }

    .page-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        color: var(--el-color-primary);
        display: block;
        text-align: center;

        @media (min-width: 768px) {
            font-size: 2.2rem;
        }
    }

    .privacy-card {
        margin-bottom: 30px;
        border-radius: 12px;
    }

    .last-updated {
        display: block;
        text-align: right;
        margin-bottom: 20px;
        font-style: italic;
        color: var(--el-text-color-secondary);
    }

    .privacy-content {
        padding: 20px 0;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 16px;
        margin-top: 30px;
        color: var(--el-color-primary);
        display: block;

        &:first-child {
            margin-top: 0;
        }
    }

    .section-content {
        margin-bottom: 24px;
        line-height: 1.6;
        font-size: 1.05rem;
        color: var(--el-text-color-primary);
        display: block;
    }
</style>
