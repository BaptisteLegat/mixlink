<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
    import StarIcon from 'vue-material-design-icons/Star.vue';

    const { t } = useI18n();

    const plans = [
        {
            name: 'home.plans.free.title',
            price: '0',
            features: ['home.plans.free.feature1', 'home.plans.free.feature2'],
            cta: 'home.plans.free.cta',
            highlighted: false,
        },
        {
            name: 'home.plans.premium.title',
            price: '3,99',
            features: ['home.plans.premium.feature1', 'home.plans.premium.feature2', 'home.plans.premium.feature3'],
            cta: 'home.plans.premium.cta',
            highlighted: true,
            badge: 'home.plans.popular',
        },
        {
            name: 'home.plans.enterprise.title',
            price: 'home.plans.enterprise.price',
            features: ['home.plans.enterprise.feature1', 'home.plans.enterprise.feature2'],
            cta: 'home.plans.enterprise.cta',
            highlighted: false,
        },
    ];
</script>

<template>
    <el-container class="pricing-container">
        <el-main>
            <el-space direction="vertical" class="pricing-section" :fill="true" :size="30">
                <el-row justify="center">
                    <el-col :span="24" :lg="18" :xl="16">
                        <el-text tag="h2" class="section-title">{{ t('home.plans.title') }}</el-text>
                        <el-text tag="p" size="large" class="section-subtitle">{{ t('home.plans.subtitle') }}</el-text>
                    </el-col>
                </el-row>

                <el-row :gutter="32" justify="center" class="pricing-row">
                    <el-col v-for="(plan, index) in plans" :key="index" :xs="24" :sm="24" :md="8" :lg="8" :xl="8" class="pricing-col">
                        <el-card
                            class="pricing-card"
                            :class="{
                                'pricing-card-highlighted': plan.highlighted,
                                'pricing-card-dark': isDark && !plan.highlighted,
                                'pricing-card-highlighted-dark': isDark && plan.highlighted,
                            }"
                            shadow="hover"
                            :body-style="{ padding: '32px 24px', height: '100%', display: 'flex', flexDirection: 'column' }"
                        >
                            <div v-if="plan.badge" class="plan-badge">
                                <StarIcon :size="16" />
                                <span>{{ t(plan.badge) }}</span>
                            </div>

                            <el-text tag="h3" class="plan-name">{{ t(plan.name) }}</el-text>

                            <div class="plan-price">
                                <el-text tag="span" class="currency" v-if="plan.price !== 'home.plans.enterprise.price'">€</el-text>
                                <el-text tag="span" class="amount">{{
                                    plan.price === 'home.plans.enterprise.price' ? t(plan.price) : plan.price
                                }}</el-text>
                                <el-text v-if="plan.price !== 'home.plans.enterprise.price'" tag="span" class="period">
                                    {{ t('home.plans.per_month') }}
                                </el-text>
                            </div>

                            <div
                                class="divider"
                                :style="{
                                    background: plan.highlighted
                                        ? `linear-gradient(90deg, ${plan.color}, ${plan.darkColor})`
                                        : isDark
                                          ? 'rgba(255, 255, 255, 0.1)'
                                          : 'rgba(0, 0, 0, 0.06)',
                                }"
                            ></div>

                            <el-space direction="vertical" class="plan-features" :fill="true" :size="16">
                                <div v-for="(feature, featureIndex) in plan.features" :key="featureIndex" class="feature-item">
                                    <CheckCircleIcon :size="18" :fill="plan.highlighted ? '#fff' : isDark ? plan.darkColor : plan.color" />
                                    <el-text class="feature-text">{{ t(feature) }}</el-text>
                                </div>
                            </el-space>

                            <div class="cta-wrapper">
                                <el-button
                                    :type="plan.highlighted ? 'primary' : 'default'"
                                    class="plan-cta"
                                    :class="{
                                        'plan-cta-highlighted': plan.highlighted,
                                        'plan-cta-secondary': !plan.highlighted,
                                    }"
                                    @click="$router.push('/login')"
                                >
                                    {{ t(plan.cta) }}
                                    <template #loading>
                                        <el-icon class="is-loading"><loading /></el-icon>
                                    </template>
                                </el-button>
                            </div>
                        </el-card>
                    </el-col>
                </el-row>
            </el-space>
        </el-main>
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

        @media (min-width: 768px) {
            padding: 80px 24px;
        }
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

    .pricing-row {
        width: 100%;
        margin-top: 20px;
    }

    .pricing-col {
        margin-bottom: 32px;
        display: flex;
        min-height: 520px;

        @media (min-width: 768px) {
            &:nth-child(2) {
                margin-top: -20px;
            }
        }
    }

    .pricing-card {
        width: 100%;
        border-radius: 16px;
        background-color: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-align: center;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;

        &:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
    }

    .pricing-card-dark {
        background-color: #2a2a2a;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);

        &:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35);
        }
    }

    .pricing-card-highlighted {
        background: linear-gradient(135deg, #6023c0, #753ed6);
        color: white;
        position: relative;
        z-index: 1;

        @media (min-width: 768px) {
            transform: scale(1.05);

            &:hover {
                transform: translateY(-8px) scale(1.05);
            }
        }
    }

    .pricing-card-highlighted-dark {
        background: linear-gradient(135deg, #6023c0, #753ed6);
        color: white;
    }

    .plan-badge {
        position: absolute;
        top: 0;
        right: 24px;
        background: linear-gradient(90deg, #ff6e42, #ff9776);
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 4px 12px 6px;
        border-radius: 0 0 8px 8px;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 4px 12px rgba(255, 110, 66, 0.3);
    }

    .plan-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #333;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: white;
        }

        :deep(.el-text__inner) {
            display: inline-block;
        }
    }

    .plan-price {
        margin-bottom: 20px;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 2px;
    }

    .currency {
        font-size: 1.5rem;
        font-weight: 600;
        margin-right: 4px;
        color: #333;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: white;
        }
    }

    .amount {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1.1;
        color: #6023c0;

        .pricing-card-highlighted & {
            color: #ffffff;
        }

        .pricing-card-dark & {
            color: #ffffff;
        }

        :deep(.el-text__inner) {
            background: linear-gradient(135deg, #6023c0, #8347de);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;

            .pricing-card-highlighted & {
                background: #ffffff;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .pricing-card-dark & {
                background: #ffffff;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        }
    }

    .period {
        font-size: 1rem;
        margin-left: 4px;
        color: #666;

        .pricing-card-highlighted & {
            color: rgba(255, 255, 255, 0.9);
        }

        .pricing-card-dark & {
            color: rgba(255, 255, 255, 0.9);
        }
    }

    .divider {
        height: 3px;
        width: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(90deg, #6023c0, #8347de);
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 30px 0;
        text-align: left;
        flex-grow: 1;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .feature-text {
        color: #444;
        font-size: 1rem;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: rgba(255, 255, 255, 0.9);
        }
    }

    .cta-wrapper {
        margin-top: auto;
        padding-top: 24px;
    }

    .plan-cta {
        width: 100%;
        height: $spacing-unit * 3;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
        z-index: 1;
        border: none;
        padding: 0 $spacing-large;
        letter-spacing: 0.5px;

        &-secondary {
            background: transparent;
            color: $primary-color;
            border: 2px solid $primary-color;

            &:hover {
                background: rgba($primary-color, 0.1);
                border-color: $primary-color;
                color: $primary-color;
            }
        }
    }

    .dark {
        .plan-cta {
            &-highlighted {
                background: linear-gradient(135deg, $purple, $purple-light);
                box-shadow: 0 4px 15px rgba($purple, 0.4);

                &:hover {
                    background: linear-gradient(135deg, darken($purple, 5%), darken($purple-light, 5%));
                }
            }

            &-secondary {
                color: $purple-light;
                border-color: $purple-light;

                &:hover {
                    background: rgba($purple-light, 0.1);
                    color: $purple-light;
                }
            }
        }
    }

    @media (max-width: $breakpoint-sm) {
        .plan-cta {
            height: $spacing-unit * 2.5;
            font-size: $font-size-base;
            padding: 0 $spacing-medium;
        }
    }
</style>
