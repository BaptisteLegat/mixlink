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
            <filter id="deviceShadow" x="-10%" y="-10%" width="120%" height="120%">
                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-opacity="0.15" :flood-color="props.color" />
            </filter>
            <linearGradient id="connectionGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" :stop-color="props.color" stop-opacity="0.8" />
                <stop offset="100%" :stop-color="props.color" stop-opacity="0.2" />
            </linearGradient>
        </defs>

        <rect x="50" y="60" width="140" height="180" rx="12" :fill="isDark ? '#2a2a3a' : '#F5F2FF'" filter="url(#deviceShadow)" />
        <rect x="50" y="60" width="140" height="40" rx="12" :fill="props.color" />

        <rect x="70" y="110" width="100" height="20" rx="4" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="1.5" />
        <rect x="70" y="140" width="100" height="20" rx="4" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="1.5" />
        <rect x="70" y="170" width="100" height="20" rx="4" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="1.5" />

        <circle cx="170" cy="150" r="25" :fill="props.color">
            <animate attributeName="r" values="25;28;25" dur="2s" repeatCount="indefinite" />
        </circle>
        <path d="M170 140V160M160 150H180" stroke="white" stroke-width="2" stroke-linecap="round" />

        <path d="M190 150L230 150" stroke="url(#connectionGradient)" stroke-width="4" stroke-dasharray="12 6">
            <animate attributeName="stroke-dashoffset" values="18;0;18" dur="1.5s" repeatCount="indefinite" />
        </path>
        <polygon points="230,150 220,145 220,155" :fill="props.color" />

        <rect x="210" y="60" width="140" height="180" rx="12" :fill="isDark ? '#2a2a3a' : '#F5F2FF'" filter="url(#deviceShadow)" />
        <rect x="210" y="60" width="140" height="40" rx="12" :fill="props.color" />

        <circle cx="280" cy="150" r="15" :fill="props.color" opacity="0">
            <animate attributeName="opacity" values="0;0.8;0" dur="2s" repeatCount="indefinite" begin="0.5s" />
            <animate attributeName="r" values="15;20;15" dur="2s" repeatCount="indefinite" begin="0.5s" />
        </circle>

        <path d="M274 150L279 155L286 144" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0">
            <animate attributeName="opacity" values="0;1" dur="0.5s" begin="2s" fill="freeze" />
        </path>
    </svg>
</template>
