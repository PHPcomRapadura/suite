<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import EventModal from '@/components/EventModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const { user } = useAuth()

const events  = ref([])
const meta    = ref({ current_page: 1, last_page: 1, per_page: 9, total: 0 })
const loading = ref(false)
const filters = reactive({ search: '', status: '', year: '' })

const showEventModal    = ref(false)
const editingEvent      = ref(null)
const showConfirmModal  = ref(false)
const cancellingEvent   = ref(null)
const actionLoading     = ref(false)

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

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 5 }, (_, i) => currentYear - i)

async function fetchEvents(page = 1) {
    loading.value = true
    try {
        const { data } = await axios.get('/admin/api/events', {
            params: { ...filters, page },
        })
        events.value = data.data
        meta.value   = data.meta
    } finally {
        loading.value = false
    }
}

function openCreate() {
    editingEvent.value   = null
    showEventModal.value = true
}

function openEdit(event) {
    editingEvent.value   = event
    showEventModal.value = true
}

function onModalSaved() {
    showEventModal.value = false
    fetchEvents(meta.value.current_page)
}

async function updateStatus(event, newStatus) {
    try {
        await axios.patch(`/admin/api/events/${event.id}/status`, { status: newStatus })
        await fetchEvents(meta.value.current_page)
    } catch (e) {
        alert(e.response?.data?.message ?? 'Erro ao atualizar status.')
    }
}

function requestCancel(event) {
    cancellingEvent.value = event
    showConfirmModal.value = true
}

async function executeCancel() {
    if (!cancellingEvent.value) return
    actionLoading.value = true
    try {
        await updateStatus(cancellingEvent.value, 'cancelado')
    } finally {
        actionLoading.value    = false
        showConfirmModal.value = false
        cancellingEvent.value  = null
    }
}

async function toggleTalks(event) {
    try {
        await axios.patch(`/admin/api/events/${event.id}/toggle-talks`)
        await fetchEvents(meta.value.current_page)
    } catch (e) {
        alert(e.response?.data?.message ?? 'Erro ao atualizar CFP.')
    }
}

function formatDate(date) {
    if (!date) return null
    return new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function ordinal(n) {
    if (!n) return null
    return `${n}ª edição`
}

let searchTimeout
function onSearchInput() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchEvents(1), 400)
}

onMounted(() => fetchEvents())
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">

        <!-- Cabeçalho -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-(--color-text)">Eventos</h1>
            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover)
                       text-white text-sm font-semibold rounded-lg transition"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo evento
            </button>
        </div>

        <!-- Filtros -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-text-muted)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Buscar por nome ou slug..."
                    @input="onSearchInput"
                    class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                >
            </div>
            <select
                v-model="filters.status"
                @change="fetchEvents(1)"
                class="px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
            >
                <option value="">Todos os status</option>
                <option value="rascunho">Rascunho</option>
                <option value="publicado">Publicado</option>
                <option value="encerrado">Encerrado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <select
                v-model="filters.year"
                @change="fetchEvents(1)"
                class="px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
            >
                <option value="">Todos os anos</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <!-- Grid de cards -->
        <div v-else-if="events.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="event in events"
                :key="event.id"
                class="bg-(--color-surface) rounded-xl border border-(--color-border) flex flex-col overflow-hidden"
            >
                <!-- Imagem de capa -->
                <div
                    v-if="event.cover_image"
                    class="h-32 bg-gray-100 dark:bg-gray-800 overflow-hidden"
                >
                    <img :src="event.cover_image" :alt="event.name" class="w-full h-full object-cover">
                </div>
                <div v-else class="h-32 bg-gradient-to-br from-blue-500/10 to-blue-600/20 flex items-center justify-center">
                    <svg class="text-(--color-primary) opacity-30" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>

                <div class="p-5 flex flex-col gap-3 flex-1">
                    <!-- Status + ações -->
                    <div class="flex items-center justify-between">
                        <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', statusBadge[event.status]]">
                            {{ statusLabels[event.status] }}
                        </span>

                        <!-- Menu de ações -->
                        <div class="flex items-center gap-1">
                            <!-- Publicar (rascunho → publicado, só admin) -->
                            <button
                                v-if="event.status === 'rascunho' && user?.role === 'admin'"
                                @click="updateStatus(event, 'publicado')"
                                class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 hover:opacity-80 transition"
                                title="Publicar"
                            >
                                Publicar
                            </button>
                            <!-- Encerrar (publicado → encerrado) -->
                            <button
                                v-if="event.status === 'publicado'"
                                @click="updateStatus(event, 'encerrado')"
                                class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 hover:opacity-80 transition"
                                title="Encerrar"
                            >
                                Encerrar
                            </button>
                            <!-- Cancelar (só admin) -->
                            <button
                                v-if="['rascunho','publicado'].includes(event.status) && user?.role === 'admin'"
                                @click="requestCancel(event)"
                                class="text-xs px-2 py-1 rounded bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 hover:opacity-80 transition"
                                title="Cancelar"
                            >
                                Cancelar
                            </button>
                            <!-- Editar -->
                            <button
                                v-if="!event.isFinished && !['encerrado','cancelado'].includes(event.status)"
                                @click="openEdit(event)"
                                class="text-xs px-2 py-1 rounded bg-(--color-surface) border border-(--color-border) text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                Editar
                            </button>
                        </div>
                    </div>

                    <!-- Nome + edição -->
                    <div class="flex items-start gap-2">
                        <img v-if="event.logo" :src="event.logo" :alt="event.name + ' logo'" class="w-8 h-8 rounded object-contain shrink-0 mt-0.5">
                        <div class="min-w-0">
                            <p class="font-semibold text-(--color-text) leading-snug">{{ event.name }}</p>
                            <p v-if="event.edition" class="text-xs text-(--color-text-muted) mt-0.5">{{ ordinal(event.edition) }}</p>
                        </div>
                    </div>

                    <!-- Data e local -->
                    <div class="text-sm text-(--color-text-muted) space-y-1">
                        <div class="flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>{{ formatDate(event.starts_at) }}{{ event.ends_at ? ' — ' + formatDate(event.ends_at) : '' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>{{ event.is_online ? '🌐 Online' : (event.location ?? 'Local não definido') }}</span>
                        </div>
                        <div v-if="event.max_attendees" class="flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span>{{ event.max_attendees }} vagas</span>
                        </div>
                    </div>

                    <!-- Toggle CFP (só publicado + admin) -->
                    <div
                        v-if="event.status === 'publicado' && user?.role === 'admin'"
                        class="flex items-center justify-between pt-3 border-t border-(--color-border)"
                    >
                        <span class="text-xs text-(--color-text-muted)">Aceitando palestras</span>
                        <button
                            @click="toggleTalks(event)"
                            :aria-label="event.is_accepting_talks ? 'Desativar CFP' : 'Ativar CFP'"
                            :class="['relative inline-flex h-5 w-9 items-center rounded-full transition',
                                     event.is_accepting_talks ? 'bg-(--color-primary)' : 'bg-gray-300 dark:bg-gray-600']"
                        >
                            <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition',
                                           event.is_accepting_talks ? 'translate-x-4.5' : 'translate-x-0.5']"/>
                        </button>
                    </div>

                    <!-- Ver detalhes -->
                    <div class="mt-auto pt-3 border-t border-(--color-border)">
                        <RouterLink
                            :to="{ name: 'admin.events.show', params: { id: event.id } }"
                            class="text-xs px-2 py-1 rounded border border-(--color-border)
                                   text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            Ver detalhes
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado vazio -->
        <div v-else class="text-center py-20 text-(--color-text-muted)">
            <p class="text-lg font-medium mb-1">Nenhum evento encontrado</p>
            <p class="text-sm">Tente ajustar os filtros ou crie um novo evento.</p>
        </div>

        <!-- Paginação -->
        <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-8">
            <p class="text-sm text-(--color-text-muted)">
                {{ meta.total }} evento{{ meta.total !== 1 ? 's' : '' }} no total
            </p>
            <div class="flex items-center gap-1">
                <button
                    v-for="page in meta.last_page"
                    :key="page"
                    @click="fetchEvents(page)"
                    :class="['w-9 h-9 rounded-lg text-sm font-medium transition',
                             page === meta.current_page
                                 ? 'bg-(--color-primary) text-white'
                                 : 'text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700/50']"
                >
                    {{ page }}
                </button>
            </div>
        </div>

    </div>

    <!-- Modais -->
    <EventModal
        :show="showEventModal"
        :event="editingEvent"
        @close="showEventModal = false"
        @saved="onModalSaved"
    />

    <ConfirmModal
        :show="showConfirmModal"
        title="Cancelar evento"
        :message="`Tem certeza que deseja cancelar '${cancellingEvent?.name}'? Esta ação não pode ser desfeita.`"
        confirm-label="Cancelar evento"
        :loading="actionLoading"
        danger
        @confirm="executeCancel"
        @cancel="showConfirmModal = false; cancellingEvent = null"
    />
</template>
