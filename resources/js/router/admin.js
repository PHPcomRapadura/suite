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
                path: 'events',
                name: 'admin.events',
                component: () => import('@/views/admin/Events.vue'),
            },
            {
                path: 'events/:id',
                name: 'admin.events.show',
                component: () => import('@/views/admin/EventDetail.vue'),
            },
            {
                path: 'events/:id/cfp',
                name: 'admin.events.cfp',
                component: () => import('@/views/admin/EventCfp.vue'),
            },
            {
                path: 'events/:id/site',
                name: 'admin.events.site',
                component: () => import('@/views/admin/EventSite.vue'),
            },
            {
                path: 'events/:id/expenses',
                name: 'admin.events.expenses',
                component: () => import('@/views/admin/EventExpenses.vue'),
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
