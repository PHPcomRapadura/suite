<template>
    <div class="flex flex-col gap-6 p-5">
        <!-- Voltar -->
        <div>
            <RouterLink
                :to="{ name: 'admin.events.show', params: { id: route.params.id } }"
                class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                {{ event?.name ?? 'Voltar para o evento' }}
            </RouterLink>
        </div>

        <!-- Cabeçalho -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-(--color-text)">Participantes</h1>
                <p v-if="summary.total > 0" class="text-sm text-(--color-text-muted) mt-0.5">
                    {{ summary.total }} participante{{ summary.total !== 1 ? 's' : '' }} ·
                    {{ summary.approved }} aprovado{{ summary.approved !== 1 ? 's' : '' }} ·
                    {{ summary.checked_in }} check-in{{ summary.checked_in !== 1 ? 's' : '' }} realizados
                </p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Toggle cards/lista -->
                <div class="flex rounded-lg border border-(--color-border) overflow-hidden">
                    <button
                        @click="setViewMode('cards')"
                        :class="viewMode === 'cards' ? 'bg-(--color-primary) text-white' : 'bg-(--color-surface) text-(--color-text-muted) hover:text-(--color-text)'"
                        class="px-3 py-2 transition"
                        title="Visualização em cards"
                        aria-label="Modo cards"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                        </svg>
                    </button>
                    <button
                        @click="setViewMode('lista')"
                        :class="viewMode === 'lista' ? 'bg-(--color-primary) text-white' : 'bg-(--color-surface) text-(--color-text-muted) hover:text-(--color-text)'"
                        class="px-3 py-2 border-l border-(--color-border) transition"
                        title="Visualização em lista"
                        aria-label="Modo lista"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                </div>

                <!-- Importar CSV (somente admin) -->
                <button
                    v-if="user?.role === 'admin'"
                    @click="showUploadModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-(--color-primary) text-white rounded-lg hover:bg-(--color-primary-hover) transition"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Importar CSV
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div v-if="summary.total > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex flex-col gap-1">
                <span class="text-xs text-(--color-text-muted) font-medium uppercase tracking-wide">Total</span>
                <span class="text-2xl font-bold text-(--color-text)">{{ summary.total }}</span>
            </div>
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex flex-col gap-1">
                <span class="text-xs text-(--color-text-muted) font-medium uppercase tracking-wide">Aprovados</span>
                <span class="text-2xl font-bold text-(--color-text)">{{ summary.approved }}</span>
            </div>
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex flex-col gap-2">
                <span class="text-xs text-(--color-text-muted) font-medium uppercase tracking-wide">Check-ins</span>
                <span class="text-2xl font-bold text-(--color-text)">{{ summary.checked_in }} <span class="text-sm font-normal text-(--color-text-muted)">/ {{ summary.total }}</span></span>
                <div class="h-1.5 bg-(--color-border) rounded-full overflow-hidden">
                    <div
                        class="h-full bg-(--color-success) rounded-full transition-all"
                        :style="{ width: summary.total ? `${Math.round((summary.checked_in / summary.total) * 100)}%` : '0%' }"
                    />
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Buscar por nome ou e-mail…"
                class="px-3 py-2 text-sm rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30"
                @input="debounceFetch"
            />
            <select v-model="filters.ticket_type" @change="fetchParticipants" class="px-3 py-2 text-sm rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30">
                <option value="">Todos os ingressos</option>
                <option v-for="t in summary.ticket_types" :key="t" :value="t">{{ t }}</option>
            </select>
            <select v-model="filters.payment_status" @change="fetchParticipants" class="px-3 py-2 text-sm rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30">
                <option value="">Todos os pagamentos</option>
                <option value="Aprovado">Aprovado</option>
                <option value="Pendente">Pendente</option>
                <option value="Cancelado">Cancelado</option>
            </select>
            <select v-model="filters.checked_in" @change="fetchParticipants" class="px-3 py-2 text-sm rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30">
                <option value="">Todos (check-in)</option>
                <option value="1">Com check-in</option>
                <option value="0">Sem check-in</option>
            </select>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12 text-(--color-text-muted)">Carregando…</div>

        <!-- Estado vazio -->
        <div v-else-if="participants.length === 0" class="text-center py-16 text-(--color-text-muted)">
            <svg class="mx-auto mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <p class="text-sm">Nenhum participante encontrado.</p>
            <p v-if="user?.role === 'admin' && summary.total === 0" class="text-xs mt-1">Faça o upload de um CSV do Sympla para começar.</p>
        </div>

        <!-- Modo cards -->
        <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="p in participants"
                :key="p.id"
                class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex flex-col gap-2"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-(--color-text) truncate">{{ p.full_name }}</p>
                        <p class="text-xs text-(--color-text-muted) truncate">{{ p.email }}</p>
                    </div>
                    <button
                        v-if="user?.role === 'admin'"
                        @click="confirmDelete(p)"
                        class="shrink-0 p-1.5 rounded-lg text-(--color-text-muted) hover:text-(--color-danger) hover:bg-red-50 dark:hover:bg-red-950/30 transition"
                        aria-label="Remover participante"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>
                <p class="text-xs text-(--color-text-muted) truncate">{{ p.ticket_type }}</p>
                <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-(--color-text-muted)">
                    <span>R$ {{ Number(p.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</span>
                    <span>{{ formatDate(p.purchased_at) }}</span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <span
                        :class="p.payment_status === 'Aprovado' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                        class="text-xs px-2 py-0.5 rounded-full font-medium"
                    >{{ p.payment_status }}</span>
                    <span
                        :class="p.checked_in ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500'"
                        class="text-xs px-2 py-0.5 rounded-full"
                    >{{ p.checked_in ? '✓ Check-in' : '✗ Sem check-in' }}</span>
                </div>
            </div>
        </div>

        <!-- Modo lista -->
        <div v-else class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-(--color-bg) border-b border-(--color-border)">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">#</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Nome</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">E-mail</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Ingresso</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Valor</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Data compra</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Pagamento</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Check-in</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Cupom</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Método</th>
                            <th v-if="user?.role === 'admin'" class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-(--color-border)">
                        <tr v-for="p in participants" :key="p.id" class="hover:bg-(--color-bg) transition">
                            <td class="px-4 py-3 text-(--color-text-muted)">{{ p.registration_order }}</td>
                            <td class="px-4 py-3 font-medium text-(--color-text) whitespace-nowrap">{{ p.full_name }}</td>
                            <td class="px-4 py-3 text-(--color-text-muted)">{{ p.email }}</td>
                            <td class="px-4 py-3 text-(--color-text-muted) max-w-[160px] truncate" :title="p.ticket_type">{{ p.ticket_type }}</td>
                            <td class="px-4 py-3 text-(--color-text) whitespace-nowrap">R$ {{ Number(p.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</td>
                            <td class="px-4 py-3 text-(--color-text-muted) whitespace-nowrap">{{ formatDate(p.purchased_at) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="p.payment_status === 'Aprovado' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                                >{{ p.payment_status }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="p.checked_in ? 'text-blue-600 dark:text-blue-400' : 'text-(--color-text-muted)'">{{ p.checked_in ? '✓' : '✗' }}</span>
                            </td>
                            <td class="px-4 py-3 text-(--color-text-muted) text-xs">{{ p.discount_coupon ?? '—' }}</td>
                            <td class="px-4 py-3 text-(--color-text-muted) text-xs">{{ p.payment_method ?? '—' }}</td>
                            <td v-if="user?.role === 'admin'" class="px-4 py-3">
                                <button
                                    @click="confirmDelete(p)"
                                    class="p-1.5 rounded-lg text-(--color-text-muted) hover:text-(--color-danger) hover:bg-red-50 dark:hover:bg-red-950/30 transition"
                                    aria-label="Remover participante"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação + Limpar todos -->
        <div v-if="!loading && (meta.last_page > 1 || (user?.role === 'admin' && summary.total > 0))" class="flex flex-wrap items-center justify-between gap-3">
            <!-- Paginação -->
            <div v-if="meta.last_page > 1" class="flex items-center gap-1">
                <button @click="goToPage(meta.current_page - 1)" :disabled="meta.current_page === 1" class="px-3 py-1.5 text-sm rounded-lg border border-(--color-border) text-(--color-text-muted) hover:text-(--color-text) disabled:opacity-40 transition">←</button>
                <template v-for="p in pages" :key="p">
                    <span v-if="p === '…'" class="px-2 text-(--color-text-muted)">…</span>
                    <button
                        v-else
                        @click="goToPage(p)"
                        :class="p === meta.current_page ? 'bg-(--color-primary) text-white border-transparent' : 'border-(--color-border) text-(--color-text) hover:bg-(--color-bg)'"
                        class="px-3 py-1.5 text-sm rounded-lg border transition"
                    >{{ p }}</button>
                </template>
                <button @click="goToPage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page" class="px-3 py-1.5 text-sm rounded-lg border border-(--color-border) text-(--color-text-muted) hover:text-(--color-text) disabled:opacity-40 transition">→</button>
            </div>
            <div v-else />

            <!-- Limpar todos (somente admin) -->
            <button
                v-if="user?.role === 'admin' && summary.total > 0"
                @click="showClearConfirm = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-(--color-danger) border border-(--color-danger)/30 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/20 transition"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Limpar todos
            </button>
        </div>

        <!-- Modal upload -->
        <ParticipantUploadModal
            v-if="showUploadModal"
            :event-id="route.params.id"
            @close="showUploadModal = false"
            @saved="onUploaded"
        />

        <!-- Confirm remover individual -->
        <ConfirmModal
            v-if="participantToDelete"
            title="Remover participante"
            :message="`${participantToDelete.full_name} (${participantToDelete.email}) será removido da lista. Esta ação não pode ser desfeita.`"
            confirm-label="Remover"
            :danger="true"
            @confirm="deleteParticipant"
            @cancel="participantToDelete = null"
        />

        <!-- Confirm limpar todos -->
        <ConfirmModal
            v-if="showClearConfirm"
            title="Limpar lista de participantes"
            :message="`Todos os ${summary.total} participantes deste evento serão removidos. Esta ação não pode ser desfeita.`"
            confirm-label="Limpar tudo"
            :danger="true"
            @confirm="clearAll"
            @cancel="showClearConfirm = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import ParticipantUploadModal from '@/components/ParticipantUploadModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const route = useRoute()
const { user } = useAuth()

const event = ref(null)
const participants = ref([])
const summary = ref({ total: 0, approved: 0, checked_in: 0, ticket_types: [] })
const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })
const loading = ref(false)

const filters = ref({ search: '', ticket_type: '', payment_status: '', checked_in: '' })

const showUploadModal = ref(false)
const participantToDelete = ref(null)
const showClearConfirm = ref(false)

const viewMode = ref(localStorage.getItem('participants_view_mode') ?? 'lista')

function setViewMode(mode) {
    viewMode.value = mode
    localStorage.setItem('participants_view_mode', mode)
}

// Debounce para o campo de busca
let debounceTimer = null
function debounceFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => fetchParticipants(), 300)
}

async function fetchParticipants(page = 1) {
    loading.value = true
    try {
        const params = { page, ...filters.value }
        const res = await axios.get(`/admin/api/events/${route.params.id}/participants`, { params })
        participants.value = res.data.data
        meta.value = res.data.meta
        summary.value = res.data.summary
    } finally {
        loading.value = false
    }
}

async function fetchEvent() {
    try {
        const res = await axios.get(`/admin/api/events/${route.params.id}`)
        event.value = res.data.data
    } catch {
        // silencioso — nome é opcional para o breadcrumb
    }
}

function goToPage(page) {
    if (page < 1 || page > meta.value.last_page) return
    fetchParticipants(page)
}

const pages = computed(() => {
    const total = meta.value.last_page
    const current = meta.value.current_page
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
    const pages = new Set([1, total, current - 1, current, current + 1].filter(p => p >= 1 && p <= total))
    const sorted = [...pages].sort((a, b) => a - b)
    const result = []
    for (let i = 0; i < sorted.length; i++) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…')
        result.push(sorted[i])
    }
    return result
})

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function confirmDelete(participant) {
    participantToDelete.value = participant
}

async function deleteParticipant() {
    if (!participantToDelete.value) return
    try {
        await axios.delete(`/admin/api/events/${route.params.id}/participants/${participantToDelete.value.id}`)
        participantToDelete.value = null
        await fetchParticipants(meta.value.current_page)
    } catch {
        participantToDelete.value = null
    }
}

async function clearAll() {
    try {
        await axios.delete(`/admin/api/events/${route.params.id}/participants`)
        showClearConfirm.value = false
        await fetchParticipants(1)
    } catch {
        showClearConfirm.value = false
    }
}

async function onUploaded() {
    await fetchParticipants(1)
}

onMounted(() => {
    fetchEvent()
    fetchParticipants()
})
</script>
