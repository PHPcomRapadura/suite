<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { user } = useAuth()

const stats      = ref(null)
const nextEvent  = ref(null)
const activity   = ref([])
const loading    = ref(true)

const now = ref(new Date())
let clockInterval

const greeting = computed(() => {
    const h = now.value.getHours()
    if (h < 12) return 'Bom dia'
    if (h < 18) return 'Boa tarde'
    return 'Boa noite'
})

const formattedDate = computed(() =>
    now.value.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

async function load() {
    const [statsRes, nextRes, actRes] = await Promise.allSettled([
        axios.get('/admin/api/dashboard/stats'),
        axios.get('/admin/api/dashboard/next-event'),
        axios.get('/admin/api/dashboard/activity'),
    ])
    if (statsRes.status === 'fulfilled') stats.value     = statsRes.value.data
    if (nextRes.status  === 'fulfilled') nextEvent.value = nextRes.value.data
    if (actRes.status   === 'fulfilled') activity.value  = actRes.value.data
    loading.value = false
}

onMounted(() => {
    load()
    clockInterval = setInterval(() => { now.value = new Date() }, 60000)
})
onUnmounted(() => clearInterval(clockInterval))

// ── Stat cards ────────────────────────────────────────────────────────────────
const statCards = computed(() => [
    {
        key: 'events',
        label: 'Eventos publicados',
        value: stats.value?.events_published ?? 0,
        sub: `${stats.value?.events_cfp_open ?? 0} com CFP aberto`,
        icon: 'calendar',
        color: 'text-blue-500',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
        route: { name: 'admin.events' },
    },
    {
        key: 'talks',
        label: 'Palestras aguardando',
        value: stats.value?.talks_pending ?? 0,
        sub: 'submetidas ou em análise',
        icon: 'mic',
        color: 'text-violet-500',
        bg: 'bg-violet-50 dark:bg-violet-900/20',
        route: null,
    },
    {
        key: 'speakers',
        label: 'Palestrantes',
        value: stats.value?.speakers_total ?? 0,
        sub: 'cadastrados no sistema',
        icon: 'users',
        color: 'text-teal-500',
        bg: 'bg-teal-50 dark:bg-teal-900/20',
        route: { name: 'admin.speakers' },
    },
    {
        key: 'urgent',
        label: 'Tarefas urgentes',
        value: stats.value?.tasks_urgent ?? 0,
        sub: 'em atraso ou impedidas',
        icon: 'alert',
        color: stats.value?.tasks_urgent > 0 ? 'text-red-500' : 'text-gray-400',
        bg: stats.value?.tasks_urgent > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-800',
        route: null,
    },
    {
        key: 'users',
        label: 'Usuários',
        value: stats.value?.users_total ?? 0,
        sub: `${stats.value?.users_inactive ?? 0} inativo(s)`,
        icon: 'shield',
        color: 'text-amber-500',
        bg: 'bg-amber-50 dark:bg-amber-900/20',
        route: { name: 'admin.users' },
    },
])

// ── Próximo evento ────────────────────────────────────────────────────────────
function formatEventDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function daysUntil(iso) {
    if (!iso) return null
    const diff = Math.ceil((new Date(iso) - new Date()) / 86400000)
    if (diff === 0) return 'Hoje'
    if (diff === 1) return 'Amanhã'
    return `em ${diff} dias`
}

// ── Atividade ────────────────────────────────────────────────────────────────
const talkStatusLabel = {
    submetida:  'Submetida',
    em_analise: 'Em análise',
}
const talkStatusClass = {
    submetida:  'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    em_analise: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
}

function relativeTime(iso) {
    if (!iso) return ''
    const diff = Date.now() - new Date(iso).getTime()
    const min  = Math.floor(diff / 60000)
    if (min < 60)  return `há ${min} min`
    const h = Math.floor(min / 60)
    if (h < 24)    return `há ${h}h`
    const d = Math.floor(h / 24)
    return `há ${d}d`
}

function goToEvent(id) {
    router.push({ name: 'admin.events.show', params: { id } })
}
</script>

<template>
    <div class="p-5 lg:p-8 max-w-7xl mx-auto flex flex-col gap-7">

        <!-- Saudação -->
        <div>
            <h1 class="text-2xl font-bold text-(--color-text)">
                {{ greeting }}, {{ user?.name?.split(' ')[0] ?? '…' }}!
            </h1>
            <p class="text-sm text-(--color-text-muted) mt-1 capitalize">{{ formattedDate }}</p>
        </div>

        <!-- ── Stat cards ──────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Skeleton -->
            <template v-if="loading">
                <div
                    v-for="i in 5" :key="i"
                    class="bg-(--color-surface) border border-(--color-border) rounded-xl p-4 animate-pulse"
                >
                    <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 mb-3" />
                    <div class="w-10 h-7 rounded bg-gray-200 dark:bg-gray-700 mb-2" />
                    <div class="w-24 h-3.5 rounded bg-gray-200 dark:bg-gray-700 mb-1" />
                    <div class="w-20 h-3 rounded bg-gray-200 dark:bg-gray-700" />
                </div>
            </template>

            <template v-else>
                <component
                    :is="card.route ? 'button' : 'div'"
                    v-for="card in statCards"
                    :key="card.key"
                    :class="[
                        'bg-(--color-surface) border border-(--color-border) rounded-xl p-4 text-left',
                        card.route ? 'hover:border-(--color-primary)/40 hover:shadow-sm transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30' : '',
                    ]"
                    @click="card.route ? router.push(card.route) : undefined"
                >
                    <!-- Ícone -->
                    <div :class="['w-9 h-9 rounded-lg flex items-center justify-center mb-3', card.bg]">
                        <!-- calendar -->
                        <svg v-if="card.icon === 'calendar'" :class="['w-5 h-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <!-- mic -->
                        <svg v-else-if="card.icon === 'mic'" :class="['w-5 h-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                        <!-- users -->
                        <svg v-else-if="card.icon === 'users'" :class="['w-5 h-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <!-- alert -->
                        <svg v-else-if="card.icon === 'alert'" :class="['w-5 h-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <!-- shield -->
                        <svg v-else-if="card.icon === 'shield'" :class="['w-5 h-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>

                    <p class="text-2xl font-bold text-(--color-text) tabular-nums">{{ card.value }}</p>
                    <p class="text-sm font-medium text-(--color-text) mt-0.5 leading-tight">{{ card.label }}</p>
                    <p class="text-xs text-(--color-text-muted) mt-0.5">{{ card.sub }}</p>
                </component>
            </template>
        </div>

        <!-- ── Corpo principal ─────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Coluna principal (2/3) -->
            <div class="lg:col-span-2 flex flex-col gap-5">

                <!-- Próximo evento -->
                <div class="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-(--color-border)">
                        <h2 class="text-sm font-semibold text-(--color-text) uppercase tracking-wide">Próximo evento</h2>
                    </div>

                    <!-- Skeleton -->
                    <div v-if="loading" class="p-5 animate-pulse flex flex-col gap-3">
                        <div class="w-48 h-5 rounded bg-gray-200 dark:bg-gray-700" />
                        <div class="w-32 h-4 rounded bg-gray-200 dark:bg-gray-700" />
                        <div class="flex gap-3 mt-2">
                            <div class="w-24 h-8 rounded-lg bg-gray-200 dark:bg-gray-700" />
                            <div class="w-24 h-8 rounded-lg bg-gray-200 dark:bg-gray-700" />
                        </div>
                    </div>

                    <!-- Sem evento futuro -->
                    <div v-else-if="!nextEvent" class="p-8 flex flex-col items-center gap-3 text-center">
                        <svg class="w-10 h-10 text-(--color-text-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <p class="text-sm font-medium text-(--color-text)">Nenhum evento futuro publicado</p>
                        <button
                            class="mt-1 px-4 py-2 text-sm font-medium bg-(--color-primary) text-white rounded-lg hover:bg-(--color-primary-hover) transition"
                            @click="router.push({ name: 'admin.events' })"
                        >
                            Criar evento
                        </button>
                    </div>

                    <!-- Evento encontrado -->
                    <div v-else class="p-5">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <h3 class="text-lg font-semibold text-(--color-text) leading-tight truncate">{{ nextEvent.name }}</h3>
                                    <span
                                        v-if="nextEvent.is_accepting_talks"
                                        class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 shrink-0"
                                    >CFP aberto</span>
                                    <span
                                        v-if="nextEvent.is_online"
                                        class="text-xs px-2 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 shrink-0"
                                    >Online</span>
                                </div>

                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-(--color-text-muted)">
                                    <span class="flex items-center gap-1.5">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        {{ formatEventDate(nextEvent.starts_at) }}
                                        <span class="font-medium text-(--color-primary)">· {{ daysUntil(nextEvent.starts_at) }}</span>
                                    </span>
                                    <span v-if="nextEvent.location" class="flex items-center gap-1.5">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        {{ nextEvent.location }}
                                    </span>
                                </div>
                            </div>

                            <button
                                class="px-4 py-2 text-sm font-medium bg-(--color-primary) text-white rounded-lg hover:bg-(--color-primary-hover) transition shrink-0"
                                @click="goToEvent(nextEvent.id)"
                            >
                                Gerenciar
                            </button>
                        </div>

                        <!-- Mini stats do evento -->
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            <div class="bg-(--color-bg) rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-(--color-text) tabular-nums">{{ nextEvent.participants_count }}</p>
                                <p class="text-xs text-(--color-text-muted) mt-0.5">Inscritos</p>
                            </div>
                            <div class="bg-(--color-bg) rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-(--color-text) tabular-nums">{{ nextEvent.participants_checkedin }}</p>
                                <p class="text-xs text-(--color-text-muted) mt-0.5">Check-in</p>
                            </div>
                            <div class="bg-(--color-bg) rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-(--color-text) tabular-nums">{{ nextEvent.talks_pending }}</p>
                                <p class="text-xs text-(--color-text-muted) mt-0.5">Palestras pend.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Atividade recente -->
                <div class="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-(--color-border)">
                        <h2 class="text-sm font-semibold text-(--color-text) uppercase tracking-wide">Atividade recente</h2>
                        <p class="text-xs text-(--color-text-muted) mt-0.5">Palestras aguardando e tarefas críticas</p>
                    </div>

                    <!-- Skeleton -->
                    <div v-if="loading" class="divide-y divide-(--color-border)">
                        <div v-for="i in 4" :key="i" class="px-5 py-3.5 animate-pulse flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0" />
                            <div class="flex-1">
                                <div class="w-48 h-3.5 rounded bg-gray-200 dark:bg-gray-700 mb-2" />
                                <div class="w-32 h-3 rounded bg-gray-200 dark:bg-gray-700" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activity.length === 0" class="px-5 py-10 text-center">
                        <p class="text-sm text-(--color-text-muted)">Nenhuma atividade pendente. Tudo em dia!</p>
                    </div>

                    <ul v-else class="divide-y divide-(--color-border)">
                        <li
                            v-for="item in activity"
                            :key="`${item.type}-${item.id}`"
                            class="px-5 py-3.5 flex items-start gap-3 hover:bg-(--color-bg) transition cursor-pointer"
                            @click="goToEvent(item.event_id)"
                        >
                            <!-- Ícone mic (palestra) -->
                            <div
                                v-if="item.type === 'talk'"
                                class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0 mt-0.5"
                            >
                                <svg class="w-4 h-4 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                    <line x1="12" y1="19" x2="12" y2="23"/>
                                </svg>
                            </div>
                            <!-- Ícone alert (tarefa) -->
                            <div
                                v-else
                                :class="[
                                    'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5',
                                    item.is_overdue
                                        ? 'bg-red-100 dark:bg-red-900/30'
                                        : 'bg-orange-100 dark:bg-orange-900/30',
                                ]"
                            >
                                <svg
                                    :class="['w-4 h-4', item.is_overdue ? 'text-red-500' : 'text-orange-500']"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            </div>

                            <!-- Conteúdo -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-(--color-text) truncate leading-tight">{{ item.title }}</p>
                                    <!-- Badge palestra -->
                                    <span
                                        v-if="item.type === 'talk'"
                                        :class="['text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0', talkStatusClass[item.status] ?? talkStatusClass.submetida]"
                                    >{{ talkStatusLabel[item.status] ?? item.status }}</span>
                                    <!-- Badge tarefa -->
                                    <span
                                        v-else-if="item.is_overdue"
                                        class="text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400"
                                    >Em atraso</span>
                                    <span
                                        v-else
                                        class="text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0 bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400"
                                    >Impedimento</span>
                                </div>
                                <p class="text-xs text-(--color-text-muted) mt-0.5 truncate">
                                    <span v-if="item.type === 'talk' && item.speaker_name">{{ item.speaker_name }} · </span>
                                    <span v-if="item.event_name">{{ item.event_name }}</span>
                                    <span v-if="item.at"> · {{ relativeTime(item.at) }}</span>
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Coluna lateral (1/3) -->
            <div class="flex flex-col gap-5">

                <!-- Ações rápidas -->
                <div class="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-(--color-border)">
                        <h2 class="text-sm font-semibold text-(--color-text) uppercase tracking-wide">Ações rápidas</h2>
                    </div>
                    <div class="p-3 flex flex-col gap-1">
                        <button
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-(--color-text) hover:bg-(--color-bg) transition text-left w-full"
                            @click="router.push({ name: 'admin.events' })"
                        >
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium leading-tight">Criar evento</p>
                                <p class="text-xs text-(--color-text-muted)">Novo evento na plataforma</p>
                            </div>
                        </button>

                        <button
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-(--color-text) hover:bg-(--color-bg) transition text-left w-full"
                            @click="router.push({ name: 'admin.speakers' })"
                        >
                            <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                    <line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium leading-tight">Ver palestrantes</p>
                                <p class="text-xs text-(--color-text-muted)">
                                    {{ stats?.talks_pending ?? '…' }} palestras aguardando
                                </p>
                            </div>
                        </button>

                        <button
                            v-if="nextEvent"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-(--color-text) hover:bg-(--color-bg) transition text-left w-full"
                            @click="router.push({ name: 'admin.events.participants', params: { id: nextEvent.id } })"
                        >
                            <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium leading-tight">Participantes</p>
                                <p class="text-xs text-(--color-text-muted) truncate">{{ nextEvent.name }}</p>
                            </div>
                        </button>

                        <button
                            v-if="nextEvent"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-(--color-text) hover:bg-(--color-bg) transition text-left w-full"
                            @click="router.push({ name: 'admin.events.lottery', params: { id: nextEvent.id } })"
                        >
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="6"/>
                                    <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium leading-tight">Sorteio</p>
                                <p class="text-xs text-(--color-text-muted) truncate">{{ nextEvent.name }}</p>
                            </div>
                        </button>

                        <button
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-(--color-text) hover:bg-(--color-bg) transition text-left w-full"
                            @click="router.push({ name: 'admin.users' })"
                        >
                            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-(--color-text-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium leading-tight">Gerenciar usuários</p>
                                <p class="text-xs text-(--color-text-muted)">
                                    {{ stats?.users_total ?? '…' }} usuário(s) cadastrado(s)
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Mini card de saúde do sistema -->
                <div class="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-(--color-border)">
                        <h2 class="text-sm font-semibold text-(--color-text) uppercase tracking-wide">Status do sistema</h2>
                    </div>
                    <div class="p-4 flex flex-col gap-3">
                        <template v-if="loading">
                            <div v-for="i in 3" :key="i" class="animate-pulse flex items-center justify-between">
                                <div class="w-28 h-3.5 rounded bg-gray-200 dark:bg-gray-700" />
                                <div class="w-10 h-3.5 rounded bg-gray-200 dark:bg-gray-700" />
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-(--color-text-muted)">Usuários ativos</span>
                                <span class="font-medium text-(--color-text)">
                                    {{ (stats?.users_total ?? 0) - (stats?.users_inactive ?? 0) }}
                                    / {{ stats?.users_total ?? 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-(--color-text-muted)">CFPs abertos</span>
                                <span class="font-medium text-(--color-text)">{{ stats?.events_cfp_open ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-(--color-text-muted)">Tarefas críticas</span>
                                <span
                                    :class="[
                                        'font-medium',
                                        (stats?.tasks_urgent ?? 0) > 0 ? 'text-red-500' : 'text-(--color-text)',
                                    ]"
                                >{{ stats?.tasks_urgent ?? 0 }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
