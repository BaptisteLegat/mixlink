import { fileURLToPath, URL } from 'node:url';
import { constants } from 'node:zlib';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import compression from 'vite-plugin-compression';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
    const isDev = mode === 'development';

    const plugins = [
        vue({
            template: {
                compilerOptions: {
                    hoistStatic: true,
                    cacheHandlers: true,
                    whitespace: isDev ? 'preserve' : 'condense',
                },
            },
        }),
        AutoImport({
            resolvers: [ElementPlusResolver()],
            dts: isDev,
            include: [
                /\.[tj]sx?$/, // .ts, .tsx, .js, .jsx
                /\.vue$/,
                /\.vue\?vue/, // .vue
                /\.md$/, // .md
            ],
        }),
        Components({
            resolvers: [
                ElementPlusResolver({
                    importStyle: 'sass',
                    directives: true,
                    version: '2.6.3',
                }),
            ],
            dts: isDev,
            include: [/\.vue$/, /\.vue\?vue/, /\.md$/],
        }),
        ...(!isDev ? [
            compression({
                algorithm: 'gzip',
                threshold: 1024,
                compressionOptions: { level: 9 },
                deleteOriginFile: false,
                filter: /\.(js|css|html|svg|woff|woff2|ttf|eot)$/,
            }),
            compression({
                algorithm: 'brotliCompress',
                threshold: 1024,
                compressionOptions: {
                    params: {
                        [constants.BROTLI_PARAM_QUALITY]: 11,
                        [constants.BROTLI_PARAM_SIZE_HINT]: 0,
                    },
                },
                deleteOriginFile: false,
                filter: /\.(js|css|html|svg|woff|woff2|ttf|eot)$/,
            })
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
            devSourcemap: isDev,
        },
        build: {
            target: 'es2020',
            minify: 'terser',
            cssMinify: true,
            sourcemap: isDev,
            reportCompressedSize: !isDev,
            chunkSizeWarningLimit: 500,
            rollupOptions: {
                output: {
                    manualChunks: (id) => {
                        if (id.includes('vue') && !id.includes('vue-router') && !id.includes('vue-i18n')) {
                            return 'vue-core';
                        }
                        if (id.includes('vue-router')) {
                            return 'vue-router';
                        }
                        if (id.includes('pinia')) {
                            return 'pinia';
                        }
                        if (id.includes('vue-i18n')) {
                            return 'vue-i18n';
                        }
                        if (id.includes('element-plus')) {
                            if (id.includes('theme') || id.includes('style') || id.includes('css')) {
                                return 'element-plus-theme';
                            }
                            if (id.includes('locale')) {
                                return 'element-plus-locale';
                            }
                            if (id.includes('components')) {
                                if (id.includes('form') || id.includes('input') || id.includes('select')) {
                                    return 'element-plus-form';
                                }
                                if (id.includes('table') || id.includes('pagination') || id.includes('tree')) {
                                    return 'element-plus-data';
                                }
                                if (id.includes('dialog') || id.includes('drawer') || id.includes('message') || id.includes('notification')) {
                                    return 'element-plus-feedback';
                                }
                                if (id.includes('menu') || id.includes('tabs') || id.includes('steps')) {
                                    return 'element-plus-navigation';
                                }
                                return 'element-plus-components';
                            }
                            return 'element-plus-core';
                        }
                        if (id.includes('@element-plus/icons-vue')) {
                            return 'element-plus-icons';
                        }
                        if (id.includes('@vueuse/core')) {
                            return 'vueuse-core';
                        }
                        if (id.includes('@vueuse/motion')) {
                            return 'vueuse-motion';
                        }
                        if (id.includes('node_modules')) {
                            return 'vendor';
                        }
                    },
                    chunkFileNames: 'assets/js/[name]-[hash].js',
                    entryFileNames: 'assets/js/[name]-[hash].js',
                    assetFileNames: (assetInfo) => {
                        const info = assetInfo.name.split('.');
                        const extType = info[info.length - 1];
                        if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                            return `assets/img/[name]-[hash].[ext]`;
                        }
                        if (/woff|woff2|eot|ttf|otf/i.test(extType)) {
                            return `assets/fonts/[name]-[hash].[ext]`;
                        }
                        return `assets/[ext]/[name]-[hash].[ext]`;
                    },
                },
            },
            terserOptions: {
                compress: {
                    drop_console: !isDev,
                    drop_debugger: !isDev,
                    pure_funcs: isDev ? [] : ['console.log', 'console.info', 'console.warn', 'console.error'],
                    passes: 3,
                    dead_code: true,
                    unused: true,
                    toplevel: true,
                    booleans_as_integers: false,
                    pure_getters: true,
                    unsafe: false,
                    unsafe_comps: false,
                    unsafe_Function: false,
                    unsafe_math: false,
                    unsafe_proto: false,
                    unsafe_regexp: false,
                    unsafe_undefined: false,
                },
                mangle: {
                    safari10: true,
                    properties: {
                        regex: /^_/,
                        keep_quoted: true,
                    },
                },
                format: {
                    comments: false,
                    beautify: false,
                },
            },
        },

        optimizeDeps: {
            include: ['vue', 'vue-router', 'pinia', 'vue-i18n', 'element-plus'],
            exclude: ['@vueuse/motion'],
            force: !isDev,
        },
    };
});
