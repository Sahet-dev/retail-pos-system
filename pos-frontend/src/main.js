import { createApp } from 'vue'
import App from './App.vue'
import './axios'
import router from "./router/index.js";

createApp(App)
    .use(router)
    .mount('#app')
