<script setup>
    import { isDark } from '@/composables/dark';

    const props = defineProps({
        color: {
            type: String,
            default: '#6023c0',
        },
    });
</script>

<template>
    <svg class="visual-svg" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <filter id="playerShadow" x="-10%" y="-10%" width="120%" height="120%">
                <feDropShadow dx="0" dy="4" stdDeviation="10" flood-opacity="0.1" :flood-color="props.color" />
            </filter>
            <radialGradient id="vinylGradient" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                <stop offset="0%" :stop-color="props.color" stop-opacity="0.6" />
                <stop offset="100%" :stop-color="props.color" stop-opacity="0" />
            </radialGradient>
        </defs>

        <rect x="50" y="50" width="300" height="200" rx="16" :fill="isDark ? '#2a2a3a' : '#F5F2FF'" filter="url(#playerShadow)" />

        <circle cx="180" cy="130" r="70" fill="url(#vinylGradient)" opacity="0.5" />
        <circle cx="180" cy="130" r="60" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="2" />
        <circle cx="180" cy="130" r="40" :fill="props.color" opacity="0.1">
            <animateTransform attributeName="transform" type="rotate" from="0 180 130" to="360 180 130" dur="6s" repeatCount="indefinite" />
        </circle>
        <circle cx="180" cy="130" r="15" fill="white" />
        <circle cx="180" cy="130" r="8" :fill="props.color" />

        <path d="M80 130Q100 110 120 130" :stroke="props.color" stroke-width="2" fill="none" opacity="0.7">
            <animate
                attributeName="d"
                values="M80 130Q100 110 120 130; M80 130Q100 120 120 130; M80 130Q100 110 120 130"
                dur="1.2s"
                repeatCount="indefinite"
            />
        </path>
        <path d="M80 150Q100 130 120 150" :stroke="props.color" stroke-width="2" fill="none" opacity="0.7">
            <animate
                attributeName="d"
                values="M80 150Q100 130 120 150; M80 150Q100 140 120 150; M80 150Q100 130 120 150"
                dur="1.5s"
                repeatCount="indefinite"
                begin="0.3s"
            />
        </path>

        <rect x="260" y="100" width="10" height="60" rx="2" :fill="props.color">
            <animate attributeName="height" values="60;30;60;20;60" dur="2s" repeatCount="indefinite" />
        </rect>
        <rect x="275" y="100" width="10" height="40" rx="2" :fill="props.color">
            <animate attributeName="height" values="40;60;40;20;40" dur="2.2s" repeatCount="indefinite" begin="0.2s" />
        </rect>
        <rect x="290" y="100" width="10" height="50" rx="2" :fill="props.color">
            <animate attributeName="height" values="50;20;50;60;50" dur="1.8s" repeatCount="indefinite" begin="0.4s" />
        </rect>

        <circle cx="150" cy="210" r="15" :fill="props.color" opacity="0.1" />
        <path d="M140 200V220L160 210Z" :fill="props.color" />

        <circle cx="200" cy="210" r="20" :fill="props.color">
            <animate attributeName="opacity" values="1;0.8;1" dur="2s" repeatCount="indefinite" />
        </circle>
        <rect x="195" y="200" width="4" height="20" rx="2" fill="white" />
        <rect x="203" y="200" width="4" height="20" rx="2" fill="white" />

        <circle cx="250" cy="210" r="15" :fill="props.color" opacity="0.1" />
        <path d="M260 200V220" :stroke="props.color" stroke-width="2" stroke-linecap="round" />
    </svg>
</template>
