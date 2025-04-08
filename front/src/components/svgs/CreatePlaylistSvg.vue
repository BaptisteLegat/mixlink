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
            <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="4" stdDeviation="12" :flood-opacity="isDark ? '0.3' : '0.15'" :flood-color="props.color" />
            </filter>
        </defs>

        <rect x="50" y="50" width="300" height="200" rx="16" :fill="isDark ? '#2a2a3a' : '#F5F2FF'" filter="url(#shadow)" />

        <rect x="50" y="50" width="300" height="60" rx="16" :fill="props.color" />
        <circle cx="80" cy="80" r="4" fill="white" opacity="0.6" />
        <circle cx="95" cy="80" r="4" fill="white" opacity="0.8" />
        <circle cx="110" cy="80" r="4" fill="white" />

        <rect x="80" y="130" width="240" height="40" rx="8" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="1.5" />
        <rect x="80" y="185" width="240" height="40" rx="8" :fill="isDark ? '#3a3a4a' : 'white'" :stroke="props.color" stroke-width="1.5" />

        <circle cx="200" cy="225" r="20" :fill="props.color">
            <animate attributeName="r" values="20;22;20" dur="2s" repeatCount="indefinite" />
        </circle>
        <path d="M190 225H210M200 215V235" stroke="white" stroke-width="2" stroke-linecap="round" />

        <rect x="90" y="140" width="2" height="20" :fill="props.color">
            <animate attributeName="opacity" values="0;1;0" dur="1.5s" repeatCount="indefinite" />
        </rect>
    </svg>
</template>
