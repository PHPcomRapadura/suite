<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const event    = ref(null)
const cfp      = ref(null)
const site     = ref(null)
const cfpTalks = ref({ total: 0, submetida: 0, em_analise: 0, aprovada: 0, rejeitada: 0, cancelada: 0 })
const loading  = ref(true)
const notFound = ref(false)

const statusLabels = {
    rascunho:  'Rascunho',
    publicado: 'Publicado',
    encerrado: 'Encerrado',
    cancelado: 'Cancelado',
}

const statusBadge = {
    rascunho:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    publicado: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    encerrado: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    cancelado: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
}

const cfpStatus = computed(() => {
    if (!cfp.value) return 'not_configured'
    const now      = Date.now()
    const opensAt  = cfp.value.opens_at  ? new Date(cfp.value.opens_at).getTime()  : null
    const closesAt = cfp.value.closes_at ? new Date(cfp.value.closes_at).getTime() : null
    if (!opensAt) return 'not_configured'
    if (now < opensAt) return 'aguardando'
    if (closesAt && now > closesAt) return 'encerrado'
    return 'aberto'
})

function formatDate(date) {
    if (!date) return null
    return new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatPeriod(start, end) {
    const s = formatDate(start)
    const e = formatDate(end)
    if (!s) return null
    return e ? `${s} — ${e}` : s
}

function ordinal(n) {
    if (!n) return null
    return `${n}ª edição`
}

async function fetchData() {
    loading.value  = true
    notFound.value = false
    try {
        const [eventRes, cfpRes, siteRes] = await Promise.allSettled([
            axios.get(`/admin/api/events/${route.params.id}`),
            axios.get(`/admin/api/events/${route.params.id}/cfp`),
            axios.get(`/admin/api/events/${route.params.id}/site`),
        ])

        if (eventRes.status === 'rejected') {
            notFound.value = true
            return
        }

        event.value = eventRes.value.data

        if (cfpRes.status === 'fulfilled') {
            const cfpData = cfpRes.value.data.data
            cfp.value = cfpData
            cfpTalks.value = cfpData?.talks_count ?? cfpTalks.value
        }

        if (siteRes.status === 'fulfilled') {
            site.value = siteRes.value.data.data
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => fetchData())
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- Voltar -->
        <RouterLink
            :to="{ name: 'admin.events' }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar para Eventos
        </RouterLink>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <!-- Evento não encontrado -->
        <div v-else-if="notFound" class="text-center py-20">
            <p class="text-lg font-semibold text-(--color-text) mb-2">Evento não encontrado</p>
            <p class="text-sm text-(--color-text-muted) mb-6">O evento que você procura não existe ou foi removido.</p>
            <RouterLink
                :to="{ name: 'admin.events' }"
                class="text-sm text-(--color-primary) hover:text-(--color-primary-hover) transition"
            >
                ← Voltar para Eventos
            </RouterLink>
        </div>

        <!-- Conteúdo principal -->
        <template v-else-if="event">

            <!-- Strip de capa -->
            <div
                v-if="event.cover_image"
                class="h-48 md:h-64 rounded-xl overflow-hidden mb-6"
            >
                <img :src="event.cover_image" :alt="event.name" class="w-full h-full object-cover">
            </div>
            <div
                v-else
                class="h-24 rounded-xl bg-gradient-to-br from-blue-500/10 to-blue-600/20 mb-6"
            />

            <!-- Header do evento -->
            <div class="flex items-start gap-4 mb-8">
                <img
                    v-if="event.logo"
                    :src="event.logo"
                    :alt="event.name + ' logo'"
                    class="w-12 h-12 rounded-lg object-contain shrink-0 mt-0.5 border border-(--color-border) bg-(--color-surface) p-1"
                >
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <h1 class="text-2xl font-bold text-(--color-text) leading-tight">{{ event.name }}</h1>
                        <span v-if="event.edition" class="text-sm text-(--color-text-muted) shrink-0">{{ ordinal(event.edition) }}</span>
                        <span :class="['text-xs font-medium px-2.5 py-1 rounded-full shrink-0', statusBadge[event.status]]">
                            {{ statusLabels[event.status] }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm text-(--color-text-muted)">
                        <span class="flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            {{ formatPeriod(event.starts_at, event.ends_at) }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            {{ event.is_online ? 'Online' : (event.location ?? 'Local não definido') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grid de sub-módulos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <!-- Card CFP -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">CFP</span>
                        <span
                            v-if="cfpStatus === 'aguardando'"
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400 shrink-0"
                        >Aguardando</span>
                        <span
                            v-else-if="cfpStatus === 'aberto'"
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 shrink-0"
                        >Aberto</span>
                        <span
                            v-else-if="cfpStatus === 'encerrado'"
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 shrink-0"
                        >Encerrado</span>
                    </div>

                    <template v-if="cfpStatus === 'not_configured'">
                        <p class="text-sm text-(--color-text-muted)">Não configurado</p>
                    </template>
                    <template v-else>
                        <p class="text-xs text-(--color-text-muted)">
                            {{ formatPeriod(cfp.opens_at, cfp.closes_at) }}
                        </p>
                        <p class="text-sm text-(--color-text)">
                            {{ cfpTalks.total }} palestra{{ cfpTalks.total !== 1 ? 's' : '' }}
                        </p>
                    </template>

                    <RouterLink
                        :to="{ name: 'admin.events.cfp', params: { id: event.id } }"
                        class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                    >
                        {{ cfpStatus === 'not_configured' ? 'Configurar' : 'Gerenciar' }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </RouterLink>
                </div>

                <!-- Card Site do Evento -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">Site do Evento</span>
                        <span
                            v-if="site?.is_published"
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 shrink-0"
                        >Publicado</span>
                        <span
                            v-else
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500 shrink-0"
                        >Não publicado</span>
                    </div>

                    <p class="text-sm text-(--color-text-muted)">
                        {{ site ? 'Página pública do evento configurada.' : 'Página pública do evento não configurada.' }}
                    </p>

                    <RouterLink
                        :to="{ name: 'admin.events.site', params: { id: event.id } }"
                        class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                    >
                        {{ site ? 'Configurar' : 'Configurar' }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </RouterLink>
                </div>

                <!-- Card Despesas -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">Despesas</span>
                    </div>
                    <p class="text-sm text-(--color-text-muted)">Registre e acompanhe os gastos do evento para prestação de contas.</p>
                    <RouterLink
                        :to="{ name: 'admin.events.expenses', params: { id: event.id } }"
                        class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                    >
                        Gerenciar
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </RouterLink>
                </div>

                <!-- Card Tarefas -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">Tarefas</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500 shrink-0">Em breve</span>
                    </div>
                    <p class="text-sm text-(--color-text-muted)">Em desenvolvimento. Em breve você poderá gerenciar tarefas do evento.</p>
                </div>

                <!-- Card Participantes -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">Participantes</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500 shrink-0">Em breve</span>
                    </div>
                    <p class="text-sm text-(--color-text-muted)">Em desenvolvimento. Em breve você poderá acompanhar os participantes do evento.</p>
                </div>

                <!-- Card Sorteio -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                            <polyline points="20 12 20 22 4 22 4 12"/>
                            <rect x="2" y="7" width="20" height="5"/>
                            <line x1="12" y1="22" x2="12" y2="7"/>
                            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
                            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                        </svg>
                        <span class="font-semibold text-(--color-text)">Sorteio</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500 shrink-0">Em breve</span>
                    </div>
                    <p class="text-sm text-(--color-text-muted)">Em desenvolvimento. Em breve você poderá realizar sorteios de brindes.</p>
                </div>

            </div>
        </template>
    </div>
</template>
