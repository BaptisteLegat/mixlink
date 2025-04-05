<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import { useIntersectionObserver } from '@vueuse/core';

    const { t } = useI18n();

    // Étapes du processus
    const steps = computed(() => [
        {
            id: 'create',
            title: t('home.tabs.one.title'),
            description: t('home.tabs.one.description'),
            action: t('home.tabs.create'),
            icon: 'create',
            color: '#6C5CE7',
        },
        {
            id: 'share',
            title: t('home.tabs.two.title'),
            description: t('home.tabs.two.description'),
            action: t('home.tabs.share'),
            icon: 'share',
            color: '#00B894',
        },
        {
            id: 'add',
            title: t('home.tabs.three.title'),
            description: t('home.tabs.three.description'),
            action: t('home.tabs.add'),
            icon: 'add',
            color: '#FD79A8',
        },
        {
            id: 'enjoy',
            title: t('home.tabs.four.title'),
            description: t('home.tabs.four.description'),
            action: t('home.tabs.enjoy'),
            icon: 'enjoy',
            color: '#FDCB6E',
        },
    ]);

    const activeStep = ref(0);
    const targetRef = ref(null);
    const isVisible = ref(false);

    // Observer pour déclencher les animations
    const { stop } = useIntersectionObserver(
        targetRef,
        ([{ isIntersecting }]) => {
            if (isIntersecting) {
                isVisible.value = true;
                stop();
            }
        },
        { threshold: 0.1 }
    );

    const setActiveStep = (index) => {
        activeStep.value = index;
    };

    // Animation des icônes
    const getIconPath = (icon) => {
        const icons = {
            create: `
      <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
      <path d="M0 0h24v24H0z" fill="none"/>
    `,
            share: `
      <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
    `,
            add: `
      <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
      <path d="M0 0h24v24H0z" fill="none"/>
    `,
            enjoy: `
      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    `,
        };
        return icons[icon] || '';
    };

    // Animation du SVG
    const drawPath = (index) => {
        return {
            '--path-length': 100 + index * 50,
            '--animation-delay': `${index * 0.2}s`,
        };
    };
</script>

<template>
    <section class="how-it-works" :class="{ 'dark-mode': isDark }" ref="targetRef">
        <div class="container">
            <div class="header">
                <el-text tag="h2" class="title" size="xl">
                    {{ t('home.how_it_works') }}
                </el-text>
                <el-text tag="p" class="subtitle">
                    {{ t('home.tabs.subtitle') }}
                </el-text>
            </div>

            <div class="process-wrapper">
                <!-- Timeline horizontale -->
                <div class="timeline">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.id"
                        class="step"
                        :class="{
                            active: activeStep === index,
                            completed: activeStep > index,
                            visible: isVisible,
                        }"
                        @click="setActiveStep(index)"
                    >
                        <div class="step-number" :style="{ '--step-color': step.color }">
                            {{ index + 1 }}
                        </div>
                        <div class="step-label">
                            {{ step.title }}
                        </div>
                        <div class="step-line" :style="{ '--step-color': step.color }"></div>
                    </div>
                </div>

                <!-- Contenu des étapes -->
                <div class="content-wrapper">
                    <transition-group name="fade-slide" mode="out-in">
                        <div v-for="(step, index) in steps" v-show="activeStep === index" :key="step.id" class="step-content">
                            <div class="illustration">
                                <svg width="300" height="300" viewBox="0 0 300 300" class="animated-svg">
                                    <!-- Cercle de fond animé -->
                                    <circle
                                        cx="150"
                                        cy="150"
                                        r="120"
                                        fill="none"
                                        stroke="var(--step-color)"
                                        stroke-width="2"
                                        stroke-dasharray="var(--path-length)"
                                        stroke-dashoffset="var(--path-length)"
                                        style="opacity: 0.2"
                                        :style="drawPath(index)"
                                    />

                                    <!-- Icône centrale -->
                                    <g transform="translate(100, 100) scale(2)">
                                        <path :d="getIconPath(step.icon)" fill="var(--step-color)" />
                                    </g>

                                    <!-- Éléments décoratifs -->
                                    <circle
                                        cx="80"
                                        cy="80"
                                        r="8"
                                        fill="var(--step-color)"
                                        class="floating-element"
                                        style="animation-delay: 0.3s; opacity: 0.6"
                                    />
                                    <circle
                                        cx="220"
                                        cy="80"
                                        r="6"
                                        fill="var(--step-color)"
                                        class="floating-element"
                                        style="animation-delay: 0.5s; opacity: 0.6"
                                    />
                                    <rect
                                        x="70"
                                        y="220"
                                        width="12"
                                        height="12"
                                        rx="3"
                                        fill="var(--step-color)"
                                        class="floating-element"
                                        style="animation-delay: 0.7s; opacity: 0.6"
                                    />
                                </svg>
                            </div>

                            <div class="text-content">
                                <el-text tag="h3" class="step-title">
                                    {{ step.title }}
                                </el-text>
                                <el-text tag="p" class="step-description">
                                    {{ step.description }}
                                </el-text>

                                <el-button
                                    type="primary"
                                    class="action-button"
                                    :style="{
                                        'background-color': step.color,
                                        'border-color': step.color,
                                    }"
                                    @click="$router.push('/login')"
                                >
                                    {{ step.action }}
                                    <el-icon class="arrow-icon"><right /></el-icon>
                                </el-button>
                            </div>
                        </div>
                    </transition-group>
                </div>

                <!-- Navigation mobile -->
                <div class="mobile-nav">
                    <el-button circle :disabled="activeStep === 0" @click="setActiveStep(activeStep - 1)">
                        <el-icon><arrow-left /></el-icon>
                    </el-button>

                    <div class="step-indicator">{{ activeStep + 1 }} / {{ steps.length }}</div>

                    <el-button circle :disabled="activeStep === steps.length - 1" @click="setActiveStep(activeStep + 1)">
                        <el-icon><arrow-right /></el-icon>
                    </el-button>
                </div>
            </div>
        </div>
    </section>
</template>

<style lang="scss" scoped>
    .how-it-works {
        padding: 6rem 1rem;
        background: linear-gradient(135deg, #f9f7ff 0%, #f0ebff 100%);
        position: relative;
        overflow: hidden;

        &::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(108, 92, 231, 0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            z-index: 0;
        }

        &.dark-mode {
            background: linear-gradient(135deg, #1a1030 0%, #2a1b50 100%);

            .title {
                background-image: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
            }

            .subtitle {
                color: rgba(255, 255, 255, 0.7);
            }

            .step-label {
                color: rgba(255, 255, 255, 0.8);
            }

            .step-content {
                background: rgba(30, 20, 60, 0.7);
            }

            .step-description {
                color: rgba(255, 255, 255, 0.8);
            }
        }
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .header {
        text-align: center;
        margin-bottom: 4rem;

        .title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background-image: linear-gradient(135deg, #6c5ce7 0%, #a18cd1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }
    }

    .process-wrapper {
        position: relative;
    }

    .timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 3rem;

        &::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;

            .dark-mode & {
                background: rgba(255, 255, 255, 0.1);
            }
        }
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        cursor: pointer;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;

        &.visible {
            opacity: 1;
            transform: translateY(0);

            @for $i from 0 through 3 {
                &:nth-child(#{$i + 1}) {
                    transition-delay: $i * 0.15s;
                }
            }
        }

        &:hover {
            .step-number {
                transform: scale(1.1);
                box-shadow: 0 5px 15px rgba(var(--step-color), 0.3);
            }
        }

        &.active {
            .step-number {
                background-color: var(--step-color);
                color: white;
                transform: scale(1.1);
                box-shadow: 0 5px 20px rgba(var(--step-color), 0.4);
            }

            .step-label {
                color: var(--step-color);
                font-weight: 600;
            }

            .step-line {
                background-color: var(--step-color);
            }
        }

        &.completed {
            .step-number {
                background-color: var(--step-color);
                color: white;
            }

            .step-line {
                background-color: var(--step-color);
            }
        }
    }

    .step-number {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        color: #666;
        border: 3px solid #eee;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;

        .dark-mode & {
            background: #2a1b50;
            border-color: #3d2a6e;
            color: rgba(255, 255, 255, 0.8);
        }
    }

    .step-label {
        margin-top: 1rem;
        font-size: 1rem;
        font-weight: 500;
        color: #666;
        text-align: center;
        transition: all 0.3s ease;
    }

    .step-line {
        position: absolute;
        top: 25px;
        height: 3px;
        background: #ddd;
        z-index: 1;
        transition: all 0.5s ease;

        &:first-child {
            display: none;
        }
    }

    .content-wrapper {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        min-height: 400px;
        position: relative;
        overflow: hidden;

        .dark-mode & {
            background: rgba(30, 20, 60, 0.7);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        &::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 30%, rgba(var(--step-color), 0.05) 0%, transparent 50%);
            z-index: 0;
        }
    }

    .step-content {
        display: flex;
        align-items: center;
        gap: 3rem;

        @media (max-width: 768px) {
            flex-direction: column;
            gap: 2rem;
        }
    }

    .illustration {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;

        .animated-svg {
            max-width: 100%;
            height: auto;

            path,
            circle,
            rect {
                animation: draw 1.5s ease forwards;
                animation-delay: var(--animation-delay);
            }
        }
    }

    .text-content {
        flex: 1;
        position: relative;
        z-index: 1;

        @media (max-width: 768px) {
            text-align: center;
        }
    }

    .step-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--step-color);
    }

    .step-description {
        font-size: 1.1rem;
        line-height: 1.7;
        color: #555;
        margin-bottom: 2rem;

        .dark-mode & {
            color: rgba(255, 255, 255, 0.8);
        }
    }

    .action-button {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;

        &:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(var(--step-color), 0.3);

            .arrow-icon {
                transform: translateX(5px);
            }
        }

        .arrow-icon {
            transition: transform 0.3s ease;
            margin-left: 8px;
        }
    }

    .mobile-nav {
        display: none;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        margin-top: 2rem;

        @media (max-width: 768px) {
            display: flex;
        }
    }

    .step-indicator {
        font-weight: 600;
        color: #666;

        .dark-mode & {
            color: rgba(255, 255, 255, 0.8);
        }
    }

    /* Animations */
    @keyframes draw {
        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes float {
        0%,
        100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .floating-element {
        animation: float 3s ease-in-out infinite;
    }

    .fade-slide-enter-active,
    .fade-slide-leave-active {
        transition: all 0.5s ease;
    }

    .fade-slide-enter-from {
        opacity: 0;
        transform: translateX(30px);
    }

    .fade-slide-leave-to {
        opacity: 0;
        transform: translateX(-30px);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .how-it-works {
            padding: 3rem 1rem;
        }

        .header {
            margin-bottom: 2rem;

            .title {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }
        }

        .timeline {
            margin-bottom: 2rem;

            &::before {
                top: 20px;
            }
        }

        .step-number {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .step-label {
            font-size: 0.9rem;
        }

        .content-wrapper {
            padding: 2rem 1rem;
            min-height: auto;
        }

        .step-title {
            font-size: 1.5rem;
        }

        .step-description {
            font-size: 1rem;
        }
    }
</style>
