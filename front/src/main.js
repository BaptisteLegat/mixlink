import './assets/main.scss';
import 'element-plus/dist/index.css';

import { MotionPlugin } from '@vueuse/motion';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import i18n from '@/i18n';
import router from '@/router';

const pinia = createPinia();
const app = createApp(App);

app.use(MotionPlugin).use(i18n).use(router).use(pinia).mount('#app');
