<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const talks   = ref([])
const loading = ref(true)

const STATUS_STYLE = {
    submetida:   { label: 'Submetida',   cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' },
    em_analise:  { label: 'Em análise',  cls: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' },
    aprovada:    { label: 'Aprovada',    cls: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' },
    recusada:    { label: 'Recusada',    cls: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    cancelada:   { label: 'Cancelada',   cls: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' },
}

const LEVEL_LABEL = {
    iniciante:    'Iniciante',
    intermediario: 'Intermediário',
    avancado:     'Avançado',
}

const byEvent = computed(() => {
    const map = {}
    for (const talk of talks.value) {
        const key = talk.event_id
        if (!map[key]) map[key] = { event: talk.event, talks: [] }
        map[key].talks.push(talk)
    }
    return Object.values(map).sort((a, b) =>
        new Date(b.event?.starts_at ?? 0) - new Date(a.event?.starts_at ?? 0)
    )
})

onMounted(async () => {
    try {
        const { data } = await axios.get('/cfp/api/my-talks')
        talks.value = data.data
    } catch {
        talks.value = []
    } finally {
        loading.value = false
    }
})

function formatDate(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}
</script>

<template>
    <div class="px-6 py-8 max-w-4xl mx-auto">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-(--color-text)">Meus Eventos</h1>
            <p class="text-(--color-text-muted) text-sm mt-1">Histórico de todas as suas propostas de palestra.</p>
        </div>

        <!-- Skeleton -->
        <div v-if="loading" class="space-y-6">
            <div v-for="i in 2" :key="i" class="rounded-xl border border-(--color-border) bg-(--color-surface) p-5 space-y-3 animate-pulse">
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded mt-4"></div>
            </div>
        </div>

        <!-- Estado vazio -->
        <div v-else-if="byEvent.length === 0" class="rounded-xl border border-(--color-border) bg-(--color-surface) p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-(--color-text-muted) opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="font-semibold text-(--color-text)">Nenhuma proposta enviada ainda</p>
            <p class="text-(--color-text-muted) text-sm mt-1">Acesse a área pública do CFP para ver os eventos com submissão aberta.</p>
            <RouterLink
                :to="{ name: 'cfp.dashboard' }"
                class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-(--color-primary) text-white text-sm font-semibold hover:bg-(--color-primary-hover) transition"
            >
                Ver CFP abertos
            </RouterLink>
        </div>

        <!-- Listagem agrupada por evento -->
        <div v-else class="space-y-8">
            <section
                v-for="group in byEvent"
                :key="group.event?.id"
                class="rounded-xl border border-(--color-border) bg-(--color-surface) overflow-hidden"
            >
                <!-- Header do evento -->
                <div class="flex items-center gap-4 px-5 py-4 border-b border-(--color-border) bg-(--color-bg)">
                    <div>
                        <h2 class="font-semibold text-(--color-text) text-sm leading-snug">{{ group.event?.name }}</h2>
                        <p v-if="group.event?.starts_at" class="text-xs text-(--color-text-muted) mt-0.5">{{ formatDate(group.event.starts_at) }}</p>
                    </div>
                    <span class="ml-auto text-xs font-medium text-(--color-text-muted) shrink-0">
                        {{ group.talks.length }} proposta{{ group.talks.length !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Lista de palestras -->
                <div class="divide-y divide-(--color-border)">
                    <div
                        v-for="talk in group.talks"
                        :key="talk.id"
                        class="flex items-start justify-between gap-4 px-5 py-4"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-(--color-text) leading-snug">{{ talk.title }}</p>
                            <p v-if="talk.abstract" class="text-xs text-(--color-text-muted) mt-1 leading-relaxed line-clamp-2">{{ talk.abstract }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', STATUS_STYLE[talk.status]?.cls ?? STATUS_STYLE.submetida.cls]">
                                    {{ STATUS_STYLE[talk.status]?.label ?? talk.status }}
                                </span>
                                <span class="text-xs text-(--color-text-muted)">{{ LEVEL_LABEL[talk.level] ?? talk.level }}</span>
                                <span class="text-xs text-(--color-text-muted)">{{ talk.duration }} min</span>
                            </div>
                        </div>
                        <p class="text-xs text-(--color-text-muted) shrink-0 pt-0.5">{{ formatDate(talk.submitted_at) }}</p>
                    </div>
                </div>
            </section>
        </div>

    </div>
</template>
