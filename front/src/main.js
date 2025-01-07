import ElementPlus from 'element-plus'
import "element-plus/dist/index.css";
import './assets/main.scss';

import { createApp } from 'vue';
import App from './App.vue';

const app = createApp(App);

app.use(ElementPlus).mount('#app');

