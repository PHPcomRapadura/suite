import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './CfpApp.vue'

const routes = [
    {
        path: '/cfp',
        name: 'cfp.home',
        component: () => import('./views/cfp/Home.vue'),
    },
    {
        path: '/cfp/login',
        name: 'cfp.login',
        component: () => import('./views/cfp/Login.vue'),
    },
    {
        path: '/cfp/register',
        name: 'cfp.register',
        component: () => import('./views/cfp/Register.vue'),
    },
    {
        path: '/cfp/submit/:eventId',
        name: 'cfp.submit',
        component: () => import('./views/cfp/SubmitTalk.vue'),
    },
    {
        path: '/cfp/perfil',
        name: 'cfp.profile',
        component: () => import('./views/cfp/Profile.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

createApp(App).use(router).mount('#cfp-app')

window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader')
    loader?.classList.add('hidden')
    setTimeout(() => loader?.remove(), 400)
})
