<script setup>
    import { useI18n } from 'vue-i18n';
    import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
    import EditIcon from 'vue-material-design-icons/Pencil.vue';
    import ShareIcon from 'vue-material-design-icons/Share.vue';
    import PlaylistAddIcon from 'vue-material-design-icons/PlaylistPlus.vue';
    import MusicNoteIcon from 'vue-material-design-icons/Music.vue';
    import CreatePlaylistSvg from '@/components/svgs/CreatePlaylistSvg.vue';
    import SharePlaylistSvg from '@/components/svgs/SharePlaylistSvg.vue';
    import AddTracksSvg from '@/components/svgs/AddTracksSvg.vue';
    import EnjoyMusicSvg from '@/components/svgs/EnjoyMusicSvg.vue';

    const { t } = useI18n();
    const activeStep = ref(0);
    const isIntersecting = ref(false);
    const stepsContainer = ref(null);
    let observer = null;
    let stepTimeout = null;

    const stepKeys = computed(() => ['create', 'share', 'add', 'enjoy']);

    const steps = computed(() => [
        {
            key: 'create',
            title: 'home.tabs.one.title',
            description: 'home.tabs.one.description',
            icon: EditIcon,
            color: '#6023c0',
            svg: CreatePlaylistSvg,
        },
        {
            key: 'share',
            title: 'home.tabs.two.title',
            description: 'home.tabs.two.description',
            icon: ShareIcon,
            color: '#6023c0',
            svg: SharePlaylistSvg,
        },
        {
            key: 'add',
            title: 'home.tabs.three.title',
            description: 'home.tabs.three.description',
            icon: PlaylistAddIcon,
            color: '#6023c0',
            svg: AddTracksSvg,
        },
        {
            key: 'enjoy',
            title: 'home.tabs.four.title',
            description: 'home.tabs.four.description',
            icon: MusicNoteIcon,
            color: '#6023c0',
            svg: EnjoyMusicSvg,
        },
    ]);

    const advanceStep = () => {
        if (activeStep.value < steps.value.length - 1) {
            activeStep.value++;
        } else {
            activeStep.value = 0;
        }

        if (isIntersecting.value) {
            stepTimeout = setTimeout(advanceStep, 5000);
        }
    };

    onMounted(() => {
        nextTick(() => {
            if (!stepsContainer.value) return;

            observer = new IntersectionObserver(
                (entries) => {
                    const isVisible = entries[0]?.isIntersecting || false;
                    isIntersecting.value = isVisible;

                    if (stepTimeout) {
                        clearTimeout(stepTimeout);
                        stepTimeout = null;
                    }

                    if (isVisible) {
                        activeStep.value = 0;
                        stepTimeout = setTimeout(advanceStep, 3000);
                    }
                },
                { threshold: 0.3 }
            );

            observer.observe(stepsContainer.value);
        });
    });

    onBeforeUnmount(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        if (stepTimeout) {
            clearTimeout(stepTimeout);
            stepTimeout = null;
        }
    });

    const setActiveStep = (index) => {
        if (stepTimeout) {
            clearTimeout(stepTimeout);
            stepTimeout = null;
        }

        activeStep.value = index;

        if (isIntersecting.value) {
            stepTimeout = setTimeout(advanceStep, 5000);
        }
    };
</script>

<template>
    <el-container class="how-it-works-container" id="how-it-works">
        <div ref="stepsContainer" class="steps-wrapper">
            <el-space direction="vertical" alignment="center" class="how-it-works-section" :fill="true" :size="30">
                <el-text tag="h2" class="section-title">
                    {{ t('home.how_it_works') }}
                </el-text>

                <el-card class="steps-card" shadow="hover">
                    <div class="steps-content">
                        <div class="steps-navigation">
                            <div class="step-labels">
                                <div
                                    v-for="(step, index) in steps"
                                    :key="step.key"
                                    class="step-label"
                                    :class="{ active: index === activeStep }"
                                    @click="setActiveStep(index)"
                                >
                                    <div class="label-content">
                                        <component :is="step.icon" :size="20" class="label-icon" />
                                        <el-text>{{ t(`home.tabs.${stepKeys[index]}`) }}</el-text>
                                    </div>
                                    <div class="active-indicator" :style="{ backgroundColor: step.color }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="step-showcase">
                            <transition name="fade-slide" mode="out-in">
                                <div :key="activeStep" class="step-content">
                                    <div class="step-info">
                                        <el-text tag="h3" class="step-title">
                                            <el-text class="step-number">0{{ activeStep + 1 }}</el-text>
                                            {{ t(steps[activeStep].title) }}
                                        </el-text>
                                        <el-text tag="p" class="step-description">{{ t(steps[activeStep].description) }}</el-text>
                                    </div>

                                    <div class="step-visual">
                                        <transition name="zoom-fade">
                                            <component
                                                :key="activeStep"
                                                :is="steps[activeStep].svg"
                                                class="visual-container"
                                                :color="steps[activeStep].color"
                                            />
                                        </transition>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <div class="mobile-controls">
                            <div
                                v-for="(step, index) in steps"
                                :key="step.key"
                                class="control-dot"
                                :class="{ active: index === activeStep }"
                                :style="index === activeStep ? { backgroundColor: step.color } : {}"
                                @click="setActiveStep(index)"
                            ></div>
                        </div>
                    </div>
                </el-card>
            </el-space>
        </div>
    </el-container>
</template>

<style lang="scss" scoped>
    .how-it-works-container {
        padding: 100px 16px;
        max-width: 1440px;
        margin: 0 auto;
        position: relative;
        display: flex;
        justify-content: center;
        background: var(--el-bg-color);
    }

    .steps-wrapper {
        width: 100%;
        max-width: 1200px;
    }

    .how-it-works-section {
        position: relative;
        z-index: 1;
        width: 100%;
    }

    .steps-card {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        border-radius: 16px;
        overflow: hidden;
        border: none;
        background-color: var(--el-bg-color);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        text-align: center;
        background: linear-gradient(135deg, #6023c0, #9067e5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;

        &::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #6023c0, #9067e5);
            margin: 20px auto 0;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            font-size: 2rem;
            margin-bottom: 30px;
        }
    }

    .steps-navigation {
        width: 100%;
        display: none;

        @media (min-width: 1024px) {
            display: block;
        }
    }

    .step-labels {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .step-label {
        flex: 1;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--el-text-color-secondary);
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 12px 0;
        position: relative;

        .label-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .label-icon {
            transition: all 0.3s ease;
        }

        .active-indicator {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 2px 2px 0 0;
            opacity: 0;
            transition: all 0.3s ease;
        }

        &.active {
            color: var(--el-text-color-primary);

            .label-icon {
                color: v-bind('steps[activeStep].color');
            }

            .active-indicator {
                opacity: 1;
            }
        }

        &:hover:not(.active) {
            color: var(--el-text-color-primary);

            .label-icon {
                color: v-bind('steps[activeStep].color');
                opacity: 0.7;
            }
        }
    }

    .step-showcase {
        min-height: 400px;
        height: 100%;
        width: 100%;
        border-radius: 12px;
        overflow: hidden;

        @media (max-width: 768px) {
            min-height: auto;
        }
    }

    .step-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        height: 100%;
        min-height: 400px;
        background: var(--el-bg-color);
        border-radius: 12px;
        overflow: hidden;

        @media (max-width: 768px) {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            min-height: auto;
        }
    }

    .step-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;

        @media (max-width: 768px) {
            padding: 30px 20px;
            text-align: center;
            align-items: center;
        }
    }

    .step-number {
        font-size: 1.5rem;
        font-weight: 800;
        color: v-bind('steps[activeStep].color');
        margin-right: 8px;
        display: inline;
    }

    .step-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--el-text-color-primary);
        line-height: 1.3;
        display: block;

        @media (max-width: 768px) {
            font-size: 1.7rem;
        }
    }

    .step-description {
        font-size: 1.1rem;
        line-height: 1.7;
        color: var(--el-text-color-regular);
        max-width: 400px;
        margin-bottom: 30px;
        display: block;

        @media (max-width: 768px) {
            font-size: 1rem;
        }
    }

    .step-actions {
        margin-top: 20px;
    }

    .step-visual {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;

        @media (max-width: 768px) {
            padding: 20px;
        }
    }

    .visual-container {
        width: 100%;
        max-width: 400px;
        height: 100%;
        padding: 20px;
        position: relative;
        z-index: 1;
        margin: 0 auto;

        @media (max-width: 768px) {
            max-width: 100%;
            padding: 15px;
        }
    }

    .mobile-controls {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 30px;
        padding: 0 20px;

        @media (min-width: 1024px) {
            display: none;
        }
    }

    .control-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--el-border-color);
        cursor: pointer;
        transition: all 0.3s ease;

        &.active {
            width: 30px;
            border-radius: 6px;
            background-color: v-bind('steps[activeStep].color');
            box-shadow: 0 2px 8px v-bind('steps[activeStep].color + "80"');
        }
    }

    /* Animations améliorées */
    .fade-slide-enter-active {
        transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .fade-slide-leave-active {
        transition: all 0.3s cubic-bezier(0.55, 0, 0.55, 0.2);
    }
    .fade-slide-enter-from {
        opacity: 0;
        transform: translateY(10px);
    }
    .fade-slide-leave-to {
        opacity: 0;
        transform: translateY(-10px);
    }

    .zoom-fade-enter-active {
        transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    }
    .zoom-fade-leave-active {
        transition: all 0.3s cubic-bezier(0.55, 0, 0.55, 0.2);
    }
    .zoom-fade-enter-from {
        opacity: 0;
        transform: scale(0.9);
    }
    .zoom-fade-leave-to {
        opacity: 0;
        transform: scale(1.05);
    }
</style>
