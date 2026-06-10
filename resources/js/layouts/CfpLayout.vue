<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, RouterLink, useLink } from 'vue-router'
import { useCfpAuth } from '@/composables/useCfpAuth'

const router = useRouter()
const { user, fetchUser, logout } = useCfpAuth()

const sidebarOpen = ref(false)
const logoSrc = `${import.meta.env.APP_URL}images/PHPcomRapadura_color.svg`

onMounted(async () => {
    await fetchUser()
    if (!user.value || user.value.role !== 'palestrante') {
        router.push({ name: 'cfp.login' })
    }
})

async function handleLogout() {
    await logout()
    router.push({ name: 'cfp.login' })
}

const nav = [
    {
        name: 'cfp.dashboard',
        label: 'Dashboard',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>`,
    },
    {
        name: 'cfp.events',
        label: 'Meus Eventos',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>`,
    },
    {
        name: 'cfp.profile',
        label: 'Perfil',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>`,
    },
]
</script>

<template>
    <div class="min-h-screen bg-(--color-bg) flex">

        <!-- Overlay mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-20 bg-black/40 md:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 w-60 flex flex-col bg-(--color-surface) border-r border-(--color-border) transition-transform duration-200',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center px-5 border-b border-(--color-border) shrink-0">
                <a href="/" class="flex items-center">
                    <img :src="logoSrc" alt="PHP com Rapadura" class="h-7">
                </a>
            </div>

            <!-- Perfil do palestrante -->
            <div class="px-4 py-5 border-b border-(--color-border) shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0 border-2 border-(--color-border)">
                        <img
                            v-if="user?.avatar_url"
                            :src="user.avatar_url"
                            :alt="user?.name"
                            class="w-full h-full object-cover"
                        >
                        <svg v-else class="w-full h-full text-gray-300 dark:text-gray-600 p-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-(--color-text) truncate">{{ user?.name }}</p>
                        <p class="text-xs text-(--color-text-muted)">Palestrante</p>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <RouterLink
                    v-for="item in nav"
                    :key="item.name"
                    :to="{ name: item.name }"
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="$route.name === item.name
                        ? 'bg-(--color-primary) text-white'
                        : 'text-(--color-text-muted) hover:bg-(--color-bg) hover:text-(--color-text)'"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" v-html="item.icon" />
                    {{ item.label }}
                </RouterLink>
            </nav>

            <!-- Rodapé da sidebar -->
            <div class="px-3 py-4 border-t border-(--color-border) space-y-0.5 shrink-0">
                <a
                    href="/cfp"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-(--color-text-muted) hover:bg-(--color-bg) hover:text-(--color-text) transition-colors"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Área pública do CFP
                </a>
                <button
                    @click="handleLogout"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-(--color-text-muted) hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400 transition-colors"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sair
                </button>
            </div>
        </aside>

        <!-- Conteúdo principal -->
        <div class="flex-1 flex flex-col min-w-0 md:ml-60">

            <!-- Top bar mobile -->
            <header class="md:hidden h-14 flex items-center justify-between px-4 bg-(--color-surface) border-b border-(--color-border) sticky top-0 z-10">
                <button
                    @click="sidebarOpen = true"
                    class="p-2 rounded-lg text-(--color-text-muted) hover:bg-(--color-bg) transition"
                    aria-label="Abrir menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <a href="/" class="flex items-center">
                    <img :src="logoSrc" alt="PHP com Rapadura" class="h-6">
                </a>
                <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 border border-(--color-border)">
                    <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="user?.name" class="w-full h-full object-cover">
                    <svg v-else class="w-full h-full text-gray-300 dark:text-gray-600 p-1.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </header>

            <main class="flex-1">
                <RouterView />
            </main>
        </div>
    </div>
</template>
