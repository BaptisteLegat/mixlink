<script setup>
    import { useI18n } from 'vue-i18n';
    import PlanSelector from '@/components/subscription/PlanSelector.vue';
    import { useAuthStore } from '@/stores/authStore';
    import router from '@/router';

    const { t } = useI18n();
    const authStore = useAuthStore();
</script>

<template>
    <el-container class="pricing-container">
        <el-space direction="vertical" class="pricing-section" :fill="true" :size="30">
            <el-row justify="center">
                <el-col :span="24" :lg="18" :xl="16">
                    <el-text tag="h2" class="section-title">{{ t('home.plans.title') }}</el-text>
                    <el-text tag="p" size="large" class="section-subtitle">{{ t('home.plans.subtitle') }}</el-text>
                </el-col>
            </el-row>

            <el-row v-if="authStore.subscription" justify="center">
                <el-col :span="24" :lg="18" :xl="16">
                    <el-text tag="h3" class="section-subtitle">
                        {{ t('home.plans.current_plan') }}: {{ t('home.plans.' + authStore.subscription.plan.name + '.title') }}
                        {{ t('home.plans.since') }} {{ authStore.subscription.startDate }}
                    </el-text>
                    <el-text tag="p" class="section-subtitle">
                        {{ t('home.plans.change_plan') }}
                    </el-text>
                    <div class="button-center">
                        <el-button type="primary" size="large" round @click="router.push('/profile')">
                            {{ t('home.plans.change_plan_cta') }}
                        </el-button>
                    </div>
                </el-col>
            </el-row>

            <PlanSelector v-else />
        </el-space>
    </el-container>
</template>
<style lang="scss" scoped>
    .pricing-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;
    }

    .pricing-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;
        width: 100%;
    }

    .section-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        color: #6023c0;
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
        font-size: 1.1rem;
    }

    .button-center {
        display: flex;
        justify-content: center;
    }
</style>
