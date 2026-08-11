import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/styles/app.css'
import AppIcon from '@/components/ui/AppIcon/index.vue'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.component('AppIcon', AppIcon)

app.mount('#app')