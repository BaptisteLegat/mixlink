<script setup>
    import { defineProps } from 'vue';

    const props = defineProps({
        showGrid: {
            type: Boolean,
            default: false,
        },
    });
</script>
<template>
    <div class="gradient-background">
        <div class="gradient-sphere sphere-1"></div>
        <div class="gradient-sphere sphere-2"></div>
        <div class="gradient-sphere sphere-3"></div>
        <div class="glow"></div>
        <div v-if="props.showGrid" class="grid-overlay"></div>
        <div class="noise-overlay"></div>
    </div>
</template>
<style scoped lang="scss">
    .gradient-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        overflow: hidden;
        pointer-events: none;
    }

    .gradient-sphere {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
    }

    .sphere-1 {
        width: 40vw;
        height: 40vw;
        background: linear-gradient(40deg, rgba(96, 35, 192, 0.6), rgba(144, 103, 229, 0.3));
        top: -10%;
        left: -10%;
        animation: float-1 15s ease-in-out infinite alternate;
    }

    .sphere-2 {
        width: 45vw;
        height: 45vw;
        background: linear-gradient(240deg, rgba(96, 35, 192, 0.7), rgba(144, 103, 229, 0.4));
        bottom: -20%;
        right: -10%;
        animation: float-2 18s ease-in-out infinite alternate;
    }

    .sphere-3 {
        width: 30vw;
        height: 30vw;
        background: linear-gradient(120deg, rgba(144, 103, 229, 0.5), rgba(96, 35, 192, 0.3));
        top: 60%;
        left: 20%;
        animation: float-3 20s ease-in-out infinite alternate;
    }

    .glow {
        position: absolute;
        width: 40vw;
        height: 40vh;
        background: radial-gradient(circle, rgba(96, 35, 192, 0.15), transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 2;
        animation: pulse 8s infinite alternate;
        filter: blur(30px);
    }

    .grid-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: 40px 40px;
        background-image:
            linear-gradient(to right, rgba(255, 255, 255, 0.08) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
        z-index: 2;
    }

    .noise-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.05;
        z-index: 3;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
    }

    @keyframes float-1 {
        0% {
            transform: translate(0, 0) scale(1);
        }
        100% {
            transform: translate(10%, 10%) scale(1.1);
        }
    }

    @keyframes float-2 {
        0% {
            transform: translate(0, 0) scale(1);
        }
        100% {
            transform: translate(-10%, -5%) scale(1.15);
        }
    }

    @keyframes float-3 {
        0% {
            transform: translate(0, 0) scale(1);
            opacity: 0.3;
        }
        100% {
            transform: translate(-5%, 10%) scale(1.05);
            opacity: 0.6;
        }
    }

    @keyframes pulse {
        0% {
            opacity: 0.3;
            transform: translate(-50%, -50%) scale(0.9);
        }
        100% {
            opacity: 0.7;
            transform: translate(-50%, -50%) scale(1.1);
        }
    }

    .dark {
        .particle {
            background: rgba(144, 103, 229, 0.8);
        }

        .grid-overlay {
            background-image:
                linear-gradient(to right, rgba(144, 103, 229, 0.1) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(144, 103, 229, 0.1) 1px, transparent 1px);
        }
    }

    @media (max-width: 768px) {
        .sphere-1,
        .sphere-2,
        .sphere-3 {
            width: 60vw;
            height: 60vw;
        }

        .glow {
            width: 60vw;
            height: 50vh;
        }
    }
</style>
