<script setup>
    import { ref, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMotion } from '@vueuse/motion';
    import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';
    import YoutubeIcon from 'vue-material-design-icons/Youtube.vue';
    import PlaylistMusicIcon from 'vue-material-design-icons/PlaylistMusic.vue';
    import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue';
    import HistoryIcon from 'vue-material-design-icons/History.vue';
    import LinkVariantIcon from 'vue-material-design-icons/LinkVariant.vue';
    import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
    import { isDark } from '@/composables/dark';

    const { t } = useI18n();

    const heroRef = ref(null);
    const featuresRef = ref(null);
    const pricingRef = ref(null);
    const ctaRef = ref(null);

    const currentTab = ref('create');

    onMounted(() => {
        // Set up animations
        useMotion(heroRef, {
            initial: { opacity: 0, y: 100 },
            enter: { opacity: 1, y: 0, transition: { duration: 800 } },
        });

        useMotion(featuresRef, {
            initial: { opacity: 0 },
            enter: {
                opacity: 1,
                transition: {
                    delay: 300,
                    duration: 800,
                },
            },
        });

        useMotion(pricingRef, {
            initial: { opacity: 0 },
            enter: {
                opacity: 1,
                transition: {
                    delay: 600,
                    duration: 800,
                },
            },
        });

        useMotion(ctaRef, {
            initial: { opacity: 0, scale: 0.9 },
            enter: {
                opacity: 1,
                scale: 1,
                transition: {
                    delay: 900,
                    duration: 800,
                },
            },
        });
    });

    const features = [
        {
            title:'home.features.feature1.title',
            description:'home.features.feature1.description',
            icon: PlaylistMusicIcon,
        },
        {
            title:'home.features.feature2.title',
            description:'home.features.feature2.description',
            icon: ShareVariantIcon,
        },
        {
            title:'home.features.feature3.title',
            description:'home.features.feature3.description',
            icon: LinkVariantIcon,
        },
        {
            title:'home.features.feature4.title',
            description:'home.features.feature4.description',
            icon: HistoryIcon,
        },
    ];

    const plans = [
        {
            name: 'home.plans.free.title',
            price: '0',
            features: [
                'home.plans.free.feature1',
                'home.plans.free.feature2',
            ],
            cta: 'home.plans.free.cta',
            highlighted: false,
        },
        {
            name: 'home.plans.premium.title',
            price: '3,99',
            features: [
                'home.plans.premium.feature1',
                'home.plans.premium.feature2',
                'home.plans.premium.feature3',
            ],
            cta: 'home.plans.premium.cta',
            highlighted: true,
        },
        {
            name: 'home.plans.enterprise.title',
            price: 'home.plans.enterprise.price',
            features: [
                'home.plans.enterprise.feature1',
                'home.plans.enterprise.feature2',
            ],
            cta: 'home.plans.enterprise.cta',
            highlighted: false,
        },
    ];

    const tabs = [
        { key: 'create', label: 'home.tabs.create' },
        { key: 'share', label: 'home.tabs.share' },
        { key: 'add', label: 'home.tabs.add' },
        { key: 'enjoy', label: 'home.tabs.enjoy' },
    ];
</script>
<template>
    <div class="landing-page">
        <section class="hero-section" ref="heroRef">
            <el-row :gutter="20" justify="center" align="middle">
                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                    <div class="hero-content">
                        <h1 class="hero-title">{{ t('home.title_1') }} <span :class="isDark ? 'primary-dark' : 'primary'">{{ t('home.title_2') }}</span> {{ t('home.title_3') }}</h1>
                        <p class="hero-subtitle">
                            {{ t('home.subtitle') }}
                        </p>
                        <div class="hero-cta">
                            <el-button type="primary" size="large" @click="$router.push('/login')">{{ t('home.start_free') }}</el-button>
                            <el-button size="large" text class="demo-btn">{{ t('home.view_demo') }}</el-button>
                        </div>
                        <div class="supported-platforms">
                            <SpotifyIcon :size="32" />
                            <YoutubeIcon :size="32" />
                        </div>
                    </div>
                </el-col>
                <el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                    <div class="hero-image">
                        <el-image :src="isDark ? '/img/hero-dark.svg' : '/img/hero.svg'" alt="MixLink Demo" fit="contain" class="floating" />
                    </div>
                </el-col>
            </el-row>
        </section>
        <section class="how-it-works" ref="featuresRef">
            <h2 class="section-title">{{ t('home.how_it_works') }}</h2>
            <div class="workflow-tabs">
                <el-tabs v-model="currentTab" class="workflow-tabs">
                    <el-tab-pane v-for="tab in tabs" :key="tab.key" :name="tab.key">
                        <template #label>
                            <div class="tab-label">
                                <div class="tab-circle" :class="{ active: currentTab === tab.key }">
                                    {{ tabs.findIndex((t) => t.key === tab.key) + 1 }}
                                </div>
                                <span>{{ tab.label }}</span>
                            </div>
                        </template>
                    </el-tab-pane>
                </el-tabs>

                <div class="tab-content">
                    <div v-if="currentTab === 'create'" class="workflow-step">
                        <h3>{{ t('home.tabs.one.title') }}</h3>
                        <p>{{ t('home.tabs.one.description') }}</p>
                        <div class="step-image">
                            <el-image :src="isDark ? '/img/create-dark.svg' : '/img/create.svg'" alt="Création" fit="contain" />
                        </div>
                    </div>

                    <div v-else-if="currentTab === 'share'" class="workflow-step">
                        <h3>{{ t('home.tabs.two.title') }}</h3>
                        <p>{{ t('home.tabs.two.description') }}</p>
                        <div class="step-image">
                            <el-image :src="isDark ? '/img/share-dark.svg' : '/img/share.svg'" alt="Partage" fit="contain" />
                        </div>
                    </div>

                    <div v-else-if="currentTab === 'add'" class="workflow-step">
                        <h3>{{ t('home.tabs.three.title') }}</h3>
                        <p>{{ t('home.tabs.three.description') }}</p>
                        <div class="step-image">
                            <el-image :src="isDark ? '/img/add-dark.svg' : '/img/add.svg'" alt="Ajout" fit="contain" />
                        </div>
                    </div>

                    <div v-else-if="currentTab === 'enjoy'" class="workflow-step">
                        <h3>{{ t('home.tabs.four.title') }}</h3>
                        <p>{{ t('home.tabs.four.description') }}</p>
                        <div class="step-image">
                            <el-image :src="isDark ? '/img/enjoy-dark.svg' : '/img/enjoy.svg'" alt="Profiter" fit="contain" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="features-section">
            <h2 class="section-title">{{ t('home.features.title') }}</h2>
            <el-row :gutter="30">
                <el-col v-for="(feature, index) in features" :key="index" :xs="24" :sm="12" :md="12" :lg="6" :xl="6">
                    <div class="feature-card" :class="isDark ? 'feature-card-dark' : ''">
                        <div class="feature-icon" :class="isDark ? 'feature-icon-dark' : ''">
                            <component :is="feature.icon" />
                        </div>
                        <h3>{{ t(feature.title) }}</h3>
                        <p>{{ t(feature.description) }}</p>
                    </div>
                </el-col>
            </el-row>
        </section>
        <section class="pricing-section" ref="pricingRef">
            <h2 class="section-title">{{ t('home.plans.title') }}</h2>
            <p class="section-subtitle">{{ t('home.plans.subtitle') }}</p>
            <el-row :gutter="20" justify="center">
                <el-col v-for="(plan, index) in plans" :key="index" :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                    <div
                        class="pricing-card"
                        :class="{
                            'pricing-card-highlighted': plan.highlighted,
                            'pricing-card-dark': isDark && !plan.highlighted,
                            'pricing-card-highlighted-dark': isDark && plan.highlighted,
                        }"
                    >
                        <h3 class="plan-name">{{ t(plan.name) }}</h3>
                        <div class="plan-price">
                            <span class="amount">{{ t(plan.price) }}</span>
                            <template v-if="plan.price !== 'home.plans.enterprise.price'">
                                <span class="currency">€</span>
                                <span class="period">{{ t('home.plans.per_month') }}</span>
                            </template>
                        </div>
                        <ul class="plan-features">
                            <li v-for="(feature, featureIndex) in plan.features" :key="featureIndex">
                                <CheckCircleIcon :size="16" :fill="plan.highlighted ? '#fff' : '#753ed6'" />
                                {{ t(feature) }}
                            </li>
                        </ul>
                        <el-button :type="plan.highlighted ? 'primary' : 'default'" class="plan-cta" @click="$router.push('/login')">
                            {{ t(plan.cta) }}
                        </el-button>
                    </div>
                </el-col>
            </el-row>
        </section>
        <section class="cta-section" ref="ctaRef">
            <div class="cta-content" :class="isDark ? 'cta-content-dark' : ''">
                <h2>{{ t('home.cta.title') }}</h2>
                <p>{{ t('home.cta.subtitle') }}</p>
                <el-button type="primary" size="large" @click="$router.push('/login')"> {{ t('home.cta.button') }}</el-button>
            </div>
        </section>
    </div>
</template>
<style scoped>
    .landing-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Hero Section */
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

    .hero-cta {
        display: flex;
        gap: 16px;
        margin-bottom: 30px;
    }

    .supported-platforms {
        display: flex;
        gap: 20px;
        margin-top: 30px;
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

    .how-it-works {
        padding: 80px 0;
    }

    .workflow-tabs {
        max-width: 800px;
        margin: 0 auto;
    }

    .tab-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .tab-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #e0e0e0;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .tab-circle.active {
        background-color: #6023c0;
        color: white;
    }

    .tab-content {
        margin-top: 40px;
        min-height: 300px;
    }

    .workflow-step {
        text-align: center;
        animation: fadeIn 0.5s ease-in-out;
    }

    .workflow-step h3 {
        margin-bottom: 16px;
        font-size: 1.5rem;
    }

    .workflow-step p {
        max-width: 600px;
        margin: 0 auto 30px;
        color: var(--el-text-color-secondary);
    }

    .step-image {
        max-width: 600px;
        margin: 0 auto;
    }

    /* Features Section */
    .features-section {
        padding: 80px 0;
    }

    .section-title {
        text-align: center;
        font-size: 2.2rem;
        margin-bottom: 50px;
        font-weight: 700;
    }

    .section-subtitle {
        text-align: center;
        margin-bottom: 40px;
        color: var(--el-text-color-secondary);
    }

    .feature-card {
        padding: 30px 24px;
        border-radius: 12px;
        background-color: #fff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        text-align: center;
        height: 100%;
        transition:
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }

    .feature-card-dark {
        background-color: #2a2a2a;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background-color: #f3eaff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        color: #6023c0;
    }

    .feature-icon-dark {
        background-color: #3a1b71;
        color: #baacff;
    }

    .feature-card h3 {
        font-size: 1.2rem;
        margin-bottom: 12px;
        font-weight: 600;
    }

    .feature-card p {
        color: var(--el-text-color-secondary);
        line-height: 1.6;
    }

    /* Pricing Section */
    .pricing-section {
        padding: 80px 0;
    }

    .pricing-card {
        padding: 40px 24px;
        border-radius: 12px;
        background-color: #fff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        text-align: center;
        height: 100%;
        transition: transform 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .pricing-card-dark {
        background-color: #2a2a2a;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .pricing-card:hover {
        transform: translateY(-10px);
    }

    .pricing-card-highlighted {
        background-color: #6023c0;
        color: white;
        box-shadow: 0 10px 30px rgba(96, 35, 192, 0.3);
        position: relative;
        z-index: 1;
    }

    .pricing-card-highlighted-dark {
        background-color: #753ed6;
        color: white;
    }

    .plan-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .plan-price {
        margin-bottom: 30px;
    }

    .currency {
        font-size: 1.5rem;
        vertical-align: top;
    }

    .amount {
        font-size: 3rem;
        font-weight: 700;
    }

    .period {
        font-size: 1rem;
        color: var(--el-text-color-secondary);
    }

    .pricing-card-highlighted .period {
        color: rgba(255, 255, 255, 0.8);
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 30px 0;
        text-align: left;
        flex-grow: 1;
    }

    .plan-features li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .plan-cta {
        width: 100%;
    }

    /* CTA Section */
    .cta-section {
        padding: 80px 0;
    }

    .cta-content {
        background-color: #f9f5ff;
        border-radius: 16px;
        padding: 60px;
        text-align: center;
        background-image: linear-gradient(135deg, rgba(186, 172, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
    }

    .cta-content-dark {
        background-color: #260e4d;
        background-image: linear-gradient(135deg, rgba(117, 62, 214, 0.2) 0%, rgba(38, 14, 77, 0) 100%);
    }

    .cta-content h2 {
        font-size: 2rem;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .cta-content p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        color: var(--el-text-color-secondary);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-content-dark p {
        color: rgba(255, 255, 255, 0.7);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .primary {
        color: #6023c0;
    }

    .primary-dark {
        color: #753ed6;
    }

    .secondary {
        color: #260e4d;
    }

    .secondary-dark {
        color: #baacff;
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

        .cta-content {
            padding: 30px;
        }
    }
</style>
