import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import compression from 'vite-plugin-compression';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        vue(),
        vueDevTools(),
        AutoImport({
          resolvers: [ElementPlusResolver()],
        }),
        Components({
          resolvers: [ElementPlusResolver({ importStyle: 'sass' })],
        }),
        compression({ algorithm: 'brotliCompress' }),
        VitePWA({
          registerType: 'autoUpdate',
          workbox: {
            globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
            cleanupOutdatedCaches: true,
            skipWaiting: true
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
            edge_side_panel: {
              preferred_width: 400
            },
            launch_handler: {
              client_mode: ['focus-existing', 'auto']
            },
            icons: [
              {
                src: '/logo.png',
                sizes: '512x512',
                type: 'image/png',
                purpose: 'any maskable'
              },
              {
                src: '/logo.svg',
                sizes: 'any',
                type: 'image/svg+xml',
                purpose: 'any'
              }
            ],
            screenshots: [
              {
                src: '/screenshot-wide.png',
                sizes: '1280x720',
                type: 'image/png',
                form_factor: 'wide',
                label: 'Interface principale mixlink'
              },
              {
                src: '/screenshot-narrow.png',
                sizes: '720x1280',
                type: 'image/png',
                form_factor: 'narrow',
                label: 'Interface mobile mixlink'
              }
            ]
          }
        })
    ],
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
        target: 'es2015',
        minify: 'terser',
        cssMinify: true,
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
                    // Grouper les icônes material design
                    if (id.includes('vue-material-design-icons')) {
                        return 'icons';
                    }
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: 'assets/[ext]/[name]-[hash].[ext]',
            },
        },
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info'],
                passes: 2,
            },
            mangle: {
                safari10: true,
            },
        },
    },
});
