<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

const stats = ref(null)
const loadingStats = ref(true)

const now = ref(new Date())
let clockInterval

const greeting = computed(() => {
    const hour = now.value.getHours()
    if (hour < 12) return 'Bom dia'
    if (hour < 18) return 'Boa tarde'
    return 'Boa noite'
})

const formattedDate = computed(() => now.value.toLocaleDateString('pt-BR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
}))

const statCards = computed(() => [
    {
        key: 'users_total',
        label: 'Total de usuários',
        value: stats.value?.users_total ?? 0,
        icon: 'users',
        color: 'text-blue-500',
    },
    {
        key: 'users_admin',
        label: 'Administradores',
        value: stats.value?.users_admin ?? 0,
        icon: 'shield',
        color: 'text-purple-500',
    },
    {
        key: 'users_inactive',
        label: 'Usuários inativos',
        value: stats.value?.users_inactive ?? 0,
        icon: 'user-x',
        color: 'text-amber-500',
    },
])

async function fetchStats() {
    try {
        const { data } = await axios.get('/admin/api/dashboard/stats')
        stats.value = data
    } finally {
        loadingStats.value = false
    }
}

onMounted(() => {
    fetchStats()
    clockInterval = setInterval(() => { now.value = new Date() }, 60000)
})

onUnmounted(() => clearInterval(clockInterval))
</script>

<template>
    <div class="p-6 lg:p-8 max-w-7xl mx-auto">

        <!-- Saudação -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-(--color-text)">
                {{ greeting }}, {{ user?.name?.split(' ')[0] ?? '...' }}!
            </h1>
            <p class="text-sm text-(--color-text-muted) mt-1 capitalize">{{ formattedDate }}</p>
        </div>

        <!-- Cards de estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Skeleton -->
            <template v-if="loadingStats">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 animate-pulse"
                >
                    <div class="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded mb-3" />
                    <div class="w-12 h-8 bg-gray-200 dark:bg-gray-700 rounded mb-2" />
                    <div class="w-28 h-4 bg-gray-200 dark:bg-gray-700 rounded" />
                </div>
            </template>

            <!-- Cards reais -->
            <template v-else>
                <div
                    v-for="card in statCards"
                    :key="card.key"
                    class="bg-(--color-surface) border border-(--color-border) rounded-xl p-5"
                >
                    <!-- Ícone users -->
                    <svg v-if="card.icon === 'users'" :class="['w-6 h-6 mb-3', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <!-- Ícone shield (admin) -->
                    <svg v-else-if="card.icon === 'shield'" :class="['w-6 h-6 mb-3', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <!-- Ícone user-x (inativos) -->
                    <svg v-else-if="card.icon === 'user-x'" :class="['w-6 h-6 mb-3', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/>
                    </svg>

                    <p class="text-3xl font-bold text-(--color-text) mt-3">{{ card.value }}</p>
                    <p class="text-sm text-(--color-text-muted) mt-1">{{ card.label }}</p>
                </div>
            </template>

        </div>
    </div>
</template>
