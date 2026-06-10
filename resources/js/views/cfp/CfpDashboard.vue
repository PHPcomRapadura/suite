<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCfpAuth } from '@/composables/useCfpAuth'

const router = useRouter()
const { user, fetchUser } = useCfpAuth()

const stats    = ref({ total: 0, approved: 0, pending: 0, rejected: 0 })
const events   = ref([])
const loading  = ref(true)

const logoSrc = `${import.meta.env.APP_URL}images/PHPcomRapadura_color.svg`

onMounted(async () => {
    await fetchUser()
    await Promise.allSettled([fetchStats(), fetchEvents()])
    loading.value = false
})

async function fetchStats() {
    try {
        const { data } = await axios.get('/cfp/api/my-talks')
        stats.value = data.stats
    } catch {
        stats.value = { total: 0, approved: 0, pending: 0, rejected: 0 }
    }
}

async function fetchEvents() {
    try {
        const { data } = await axios.get('/cfp/api/events')
        events.value = data.data
    } catch {
        events.value = []
    }
}

function formatDate(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}

const statCards = [
    { key: 'total',    label: 'Total de propostas', color: 'text-blue-600 dark:text-blue-400',   bg: 'bg-blue-50 dark:bg-blue-950/30' },
    { key: 'approved', label: 'Aprovadas',           color: 'text-green-600 dark:text-green-400', bg: 'bg-green-50 dark:bg-green-950/30' },
    { key: 'pending',  label: 'Aguardando',          color: 'text-yellow-600 dark:text-yellow-400', bg: 'bg-yellow-50 dark:bg-yellow-950/30' },
    { key: 'rejected', label: 'Recusadas',           color: 'text-red-600 dark:text-red-400',    bg: 'bg-red-50 dark:bg-red-950/30' },
]
</script>

<template>
    <div class="px-6 py-8 max-w-5xl mx-auto space-y-10">

        <!-- Boas-vindas -->
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0 border-2 border-(--color-border)">
                <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="user?.name" class="w-full h-full object-cover">
                <img v-else :src="logoSrc" alt="PHP com Rapadura" class="w-full h-full object-contain p-3 opacity-40">
            </div>
            <div>
                <h1 class="text-2xl font-bold text-(--color-text)">Olá, {{ user?.name?.split(' ')[0] }}!</h1>
                <p class="text-(--color-text-muted) text-sm mt-0.5">Bem-vindo à sua área de palestrante.</p>
            </div>
        </div>

        <!-- Stats -->
        <section>
            <h2 class="text-sm font-semibold text-(--color-text-muted) uppercase tracking-wider mb-4">Suas propostas</h2>
            <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="i in 4" :key="i" class="h-24 rounded-xl bg-(--color-surface) border border-(--color-border) animate-pulse" />
            </div>
            <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="card in statCards"
                    :key="card.key"
                    :class="['rounded-xl border border-(--color-border) p-5', card.bg]"
                >
                    <p class="text-3xl font-bold" :class="card.color">{{ stats[card.key] }}</p>
                    <p class="text-sm text-(--color-text-muted) mt-1">{{ card.label }}</p>
                </div>
            </div>
        </section>

        <!-- Eventos com CFP aberto -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-(--color-text-muted) uppercase tracking-wider">CFP aberto agora</h2>
                <RouterLink
                    :to="{ name: 'cfp.events' }"
                    class="text-xs text-(--color-primary) hover:underline"
                >
                    Ver histórico →
                </RouterLink>
            </div>

            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div v-for="i in 3" :key="i" class="h-48 rounded-xl bg-(--color-surface) border border-(--color-border) animate-pulse" />
            </div>

            <div v-else-if="events.length === 0" class="rounded-xl border border-(--color-border) bg-(--color-surface) p-10 text-center">
                <svg class="w-10 h-10 mx-auto mb-3 text-(--color-text-muted) opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-(--color-text-muted) text-sm font-medium">Nenhum CFP aberto no momento.</p>
                <p class="text-(--color-text-muted) text-xs mt-1">Fique de olho nas redes sociais para novidades.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <article
                    v-for="event in events"
                    :key="event.id"
                    class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden flex flex-col hover:shadow-md transition-shadow"
                >
                    <div class="aspect-video bg-gradient-to-br from-blue-700 to-blue-900 overflow-hidden">
                        <img v-if="event.cover_image" :src="event.cover_image" :alt="event.name" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <img :src="logoSrc" alt="" class="h-8 opacity-40">
                        </div>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-xs font-medium text-green-700 dark:text-green-400">CFP Aberto</span>
                        </div>
                        <h3 class="text-sm font-semibold text-(--color-text) leading-snug mb-1">{{ event.name }}</h3>
                        <p v-if="event.starts_at" class="text-xs text-(--color-text-muted) mb-3">{{ formatDate(event.starts_at) }}</p>
                        <div class="mt-auto">
                            <RouterLink
                                :to="`/cfp/submit/${event.id}`"
                                class="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-sm font-semibold bg-(--color-primary) text-white hover:bg-(--color-primary-hover) transition"
                            >
                                Submeter palestra
                            </RouterLink>
                        </div>
                    </div>
                </article>
            </div>
        </section>

    </div>
</template>
