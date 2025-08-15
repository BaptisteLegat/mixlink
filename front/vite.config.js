import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import compression from 'vite-plugin-compression';
import { VitePWA } from 'vite-plugin-pwa';
import { performanceOptimizationPlugin, imageOptimizationPlugin } from './src/plugins/vite-performance.js';

export default defineConfig(({ mode }) => {
    const isDev = mode === 'development';

    const plugins = [
        vue(),
        performanceOptimizationPlugin(),
        imageOptimizationPlugin(),
        AutoImport({
          resolvers: [ElementPlusResolver()],
        }),
        Components({
          resolvers: [ElementPlusResolver({ importStyle: 'sass' })],
        }),
        ...(!isDev ? [
            compression({ algorithm: 'brotliCompress' }),
        ] : []),
        VitePWA({
            registerType: 'autoUpdate',
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                navigateFallback: null,
            },
            manifest: {
                name: 'mixlink - Playlists Collaboratives',
                short_name: 'mixlink',
                description: 'Créez, partagez et collaborez sur des playlists musicales',
                theme_color: '#5820a0',
                background_color: '#ffffff',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                orientation: 'portrait-primary',
                categories: ['music', 'entertainment', 'social'],
                lang: 'fr-FR',
                dir: 'ltr',
                prefer_related_applications: false,
                icons: [
                    {
                        src: '/logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                    {
                        src: '/logo.svg',
                        sizes: 'any',
                        type: 'image/svg+xml',
                        purpose: 'any',
                    },
                ],
            },
        }),
    ];

    if (isDev) {
        plugins.push(vueDevTools());
    }

    return {
        plugins,
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
            },
        },
        server: {
            port: 3000,
            host: '0.0.0.0',
            strictPort: true,
        },
        css: {
            preprocessorOptions: {
                scss: {
                    additionalData: `
                        @use "@/assets/styles/_variables.scss" as *;
                        @use "@/assets/styles/_fonts.scss" as *;
                        @use "@/assets/styles/element/index.scss" as *;
                    `,
                    api: 'modern-compiler',
                },
            },
        },
        build: {
            target: 'es2020',
            minify: 'terser',
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (id.includes('node_modules/element-plus')) {
                            return 'element-plus';
                        }
                        if (id.includes('node_modules/vue')) {
                            return 'vue';
                        }
                        if (id.includes('node_modules/vue-router')) {
                            return 'vue-router';
                        }
                        if (id.includes('node_modules/pinia')) {
                            return 'pinia';
                        }
                        if (id.includes('node_modules/vue-i18n')) {
                            return 'vue-i18n';
                        }
                        if (id.includes('node_modules')) {
                            return 'vendor';
                        }
                        if (id.includes('/src/utils/') || id.includes('/src/helpers/')) {
                            return 'utils';
                        }
                    },
                    chunkFileNames: (chunkInfo) => {
                        const facadeModuleId = chunkInfo.facadeModuleId ? chunkInfo.facadeModuleId.split('/').pop() : 'chunk'
                        return `js/${facadeModuleId}-[hash].js`
                    },
                    entryFileNames: 'js/[name]-[hash].js',
                    assetFileNames: (assetInfo) => {
                        const info = assetInfo.name.split('.')
                        const ext = info[info.length - 1]
                        if (/\.(css)$/.test(assetInfo.name)) {
                            return `css/[name]-[hash].${ext}`
                        }
                        if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico|webp|avif)$/i.test(assetInfo.name)) {
                            return `images/[name]-[hash].${ext}`
                        }
                        if (/\.(woff|woff2|eot|ttf|otf)$/i.test(assetInfo.name)) {
                            return `fonts/[name]-[hash].${ext}`
                        }
                        return `assets/[name]-[hash].${ext}`
                    }
                },
            },
            // Optimisation des chunks pour réduire le JS inutilisé
            chunkSizeWarningLimit: 1000,
            sourcemap: false
        },
    };
});
