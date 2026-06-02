import { createApp } from 'vue'
import router from './router/admin.js'
import App from './App.vue'

const app = createApp(App)
app.use(router)
app.mount('#admin-app')
