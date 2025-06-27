import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import './assets/main.scss';
import fr from 'element-plus/es/locale/lang/fr';
import { MotionPlugin } from '@vueuse/motion';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import i18n from '@/i18n';
import router from '@/router';

const pinia = createPinia();
const app = createApp(App);

app.use(MotionPlugin).use(i18n).use(router).use(pinia).use(ElementPlus, { locale: fr }).mount('#app');
