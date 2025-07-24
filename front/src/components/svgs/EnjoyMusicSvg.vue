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
            <radialGradient id="vinylGradient" cx="50%" cy="50%" r="50%">
                <stop offset="0%" :stop-color="props.color" stop-opacity="0.7" />
                <stop offset="100%" :stop-color="props.color" stop-opacity="0.1" />
            </radialGradient>
        </defs>

        <rect x="60" y="60" width="280" height="180" rx="24" :fill="isDark ? '#232336' : '#F5F2FF'" filter="url(#playerShadow)" />

        <g>
            <circle cx="200" cy="150" r="70" fill="url(#vinylGradient)" opacity="0.7" />
            <circle cx="200" cy="150" r="60" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="2" />
            <circle cx="200" cy="150" r="40" :fill="props.color" opacity="0.15">
                <animateTransform attributeName="transform" type="rotate" from="0 200 150" to="360 200 150" dur="5s" repeatCount="indefinite" />
            </circle>
            <circle cx="200" cy="150" r="12" fill="white" />
            <circle cx="200" cy="150" r="6" :fill="props.color" />
        </g>

        <g>
            <g>
                <path d="M220 120 q10 -20 20 0" :stroke="props.color" stroke-width="2.5" fill="none" opacity="0.8">
                    <animateTransform attributeName="transform" type="translate" from="0 0" to="0 -40" dur="2.5s" repeatCount="indefinite" />
                    <animate attributeName="opacity" values="0.8;0.2;0.8" dur="2.5s" repeatCount="indefinite" />
                </path>
                <circle cx="240" cy="120" r="5" :fill="props.color" opacity="0.8">
                    <animateTransform attributeName="transform" type="translate" from="0 0" to="0 -40" dur="2.5s" repeatCount="indefinite" />
                    <animate attributeName="opacity" values="0.8;0.2;0.8" dur="2.5s" repeatCount="indefinite" />
                </circle>
            </g>
            <g>
                <path d="M180 110 q-8 -18 -16 0" :stroke="props.color" stroke-width="2.5" fill="none" opacity="0.7">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        from="0 0"
                        to="0 -35"
                        dur="2.2s"
                        repeatCount="indefinite"
                        begin="0.7s"
                    />
                    <animate attributeName="opacity" values="0.7;0.2;0.7" dur="2.2s" repeatCount="indefinite" begin="0.7s" />
                </path>
                <circle cx="164" cy="110" r="4" :fill="props.color" opacity="0.7">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        from="0 0"
                        to="0 -35"
                        dur="2.2s"
                        repeatCount="indefinite"
                        begin="0.7s"
                    />
                    <animate attributeName="opacity" values="0.7;0.2;0.7" dur="2.2s" repeatCount="indefinite" begin="0.7s" />
                </circle>
            </g>
            <g>
                <path d="M210 170 q12 -18 24 0" :stroke="props.color" stroke-width="2" fill="none" opacity="0.6">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        from="0 0"
                        to="0 -30"
                        dur="2.8s"
                        repeatCount="indefinite"
                        begin="1.2s"
                    />
                    <animate attributeName="opacity" values="0.6;0.2;0.6" dur="2.8s" repeatCount="indefinite" begin="1.2s" />
                </path>
                <circle cx="234" cy="170" r="3.5" :fill="props.color" opacity="0.6">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        from="0 0"
                        to="0 -30"
                        dur="2.8s"
                        repeatCount="indefinite"
                        begin="1.2s"
                    />
                    <animate attributeName="opacity" values="0.6;0.2;0.6" dur="2.8s" repeatCount="indefinite" begin="1.2s" />
                </circle>
            </g>
        </g>
    </svg>
</template>
