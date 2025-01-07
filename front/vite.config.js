import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';

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
});
