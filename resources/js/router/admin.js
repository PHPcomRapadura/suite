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
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: 'dashboard',
                name: 'admin.dashboard',
                component: () => import('@/views/admin/Dashboard.vue'),
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('@/views/admin/Users.vue'),
            },
            {
                path: '',
                redirect: { name: 'admin.dashboard' },
            },
        ],
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
