<script setup>
    import { isDark } from '@/composables/dark';
    const props = defineProps({
        color: {
            type: String,
            default: '#8854e1',
        },
    });
</script>

<template>
    <svg class="visual-svg" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <filter id="shadow3" x="-10%" y="-10%" width="120%" height="120%">
                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-opacity="0.2" />
            </filter>
            <linearGradient id="playlistGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" :stop-color="props.color" />
                <stop offset="100%" :stop-color="color + 'AA'" />
            </linearGradient>
        </defs>

        <rect x="50" y="30" width="300" height="240" rx="12" :fill="isDark ? '#2a2a3a' : '#f8f8fc'" filter="url(#shadow3)" />
        <rect x="50" y="30" width="300" height="50" rx="12" fill="url(#playlistGradient _uid)" />
        <text x="200" y="60" font-size="18" fill="white" text-anchor="middle" font-weight="bold">Collaborative Playlist</text>
        <rect x="65" y="90" width="270" height="160" rx="8" :fill="isDark ? '#3a3a4a' : 'white'" />

        <g class="track-item">
            <rect x="75" y="105" width="250" height="30" rx="6" :fill="isDark ? '#444' : '#f0f0f5'" />
            <circle cx="90" cy="120" r="8" :fill="props.color" />
            <text x="110" y="118" font-size="12" :fill="isDark ? '#f0f0f0' : '#333'" font-weight="bold">Track 1</text>
            <text x="110" y="130" font-size="10" :fill="isDark ? '#aaa' : '#777'">Artist 1</text>
            <text x="305" y="120" font-size="10" :fill="isDark ? '#aaa' : '#777'" text-anchor="end">3:45</text>
        </g>

        <g class="track-item">
            <rect x="75" y="145" width="250" height="30" rx="6" :fill="isDark ? '#444' : '#f0f0f5'" />
            <circle cx="90" cy="160" r="8" :fill="props.color" />
            <text x="110" y="158" font-size="12" :fill="isDark ? '#f0f0f0' : '#333'" font-weight="bold">Track 2</text>
            <text x="110" y="170" font-size="10" :fill="isDark ? '#aaa' : '#777'">Artist 2</text>
            <text x="305" y="160" font-size="10" :fill="isDark ? '#aaa' : '#777'" text-anchor="end">4:12</text>
        </g>

        <g class="track-item">
            <rect x="75" y="185" width="250" height="30" rx="6" :fill="isDark ? '#444' : '#f0f0f5'" />
            <circle cx="90" cy="200" r="8" :fill="props.color" />
            <text x="110" y="198" font-size="12" :fill="isDark ? '#f0f0f0' : '#333'" font-weight="bold">Track 3</text>
            <text x="110" y="210" font-size="10" :fill="isDark ? '#aaa' : '#777'">Artist 3</text>
            <text x="305" y="200" font-size="10" :fill="isDark ? '#aaa' : '#777'" text-anchor="end">3:21</text>
        </g>

        <circle cx="300" cy="240" r="20" :fill="props.color">
            <animate attributeName="r" values="20;22;20" dur="2s" repeatCount="indefinite" />
        </circle>
        <path d="M300,235 L300,245 M295,240 L305,240" stroke="white" stroke-width="2" />

        <rect x="15" y="120" width="40" height="20" rx="10" :fill="props.color" opacity="0.7">
            <animate attributeName="x" values="15;40;75" dur="3s" begin="0s" repeatCount="indefinite" />
            <animate attributeName="opacity" values="0.7;0.9;0" dur="3s" begin="0s" repeatCount="indefinite" />
        </rect>

        <rect x="15" y="160" width="40" height="20" rx="10" :fill="props.color" opacity="0.7">
            <animate attributeName="x" values="15;40;75" dur="3s" begin="1s" repeatCount="indefinite" />
            <animate attributeName="opacity" values="0.7;0.9;0" dur="3s" begin="1s" repeatCount="indefinite" />
        </rect>

        <rect x="15" y="200" width="40" height="20" rx="10" :fill="props.color" opacity="0.7">
            <animate attributeName="x" values="15;40;75" dur="3s" begin="2s" repeatCount="indefinite" />
            <animate attributeName="opacity" values="0.7;0.9;0" dur="3s" begin="2s" repeatCount="indefinite" />
        </rect>
    </svg>
</template>
