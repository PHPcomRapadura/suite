import { createRouter, createWebHistory } from 'vue-router'
import Login from '@/views/auth/Login.vue'

const routes = [
    {
        path: '/admin/login',
        name: 'admin.login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
        meta: { auth: true },
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: () => import('@/views/admin/Users.vue'),
        meta: { auth: true },
    },
    {
        path: '/admin',
        redirect: { name: 'admin.dashboard' },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
