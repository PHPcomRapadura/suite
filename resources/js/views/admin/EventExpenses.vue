<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import ExpenseModal from '@/components/ExpenseModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'
import { useAuth } from '@/composables/useAuth'

const route    = useRoute()
const { user } = useAuth()
const eventId  = route.params.id

const isAdmin = computed(() => user.value?.role === 'admin')

// ── Estado da página ──────────────────────────────────────────────────────────
const event    = ref(null)
const expenses = ref([])
const summary  = ref({ total: 0, paid: 0, pending: 0, by_category: {} })
const meta     = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 })
const loading  = ref(true)
const notFound = ref(false)

// ── Visualização ──────────────────────────────────────────────────────────────
const VIEW_KEY  = 'expenses_view_mode'
const viewMode  = ref(localStorage.getItem(VIEW_KEY) ?? 'cards')
function setView(mode) {
    viewMode.value = mode
    localStorage.setItem(VIEW_KEY, mode)
}

// ── Filtros ───────────────────────────────────────────────────────────────────
const filters = ref({ category: '', is_paid: '', date_from: '', date_to: '' })
watch(filters, () => { meta.value.current_page = 1; fetchExpenses() }, { deep: true })

// ── Modal criar / editar ──────────────────────────────────────────────────────
const showModal    = ref(false)
const editExpense  = ref(null)

function openCreate() {
    editExpense.value = null
    showModal.value   = true
}

function openEdit(expense) {
    editExpense.value = expense
    showModal.value   = true
}

function onSaved() {
    showModal.value = false
    fetchExpenses()
}

// ── Modal excluir ─────────────────────────────────────────────────────────────
const confirmDelete  = ref(false)
const deleteTarget   = ref(null)
const deleteLoading  = ref(false)

function askDelete(expense) {
    deleteTarget.value  = expense
    confirmDelete.value = true
}

async function doDelete() {
    if (!deleteTarget.value) return
    deleteLoading.value = true
    try {
        await axios.delete(`/admin/api/events/${eventId}/expenses/${deleteTarget.value.id}`)
        confirmDelete.value = false
        deleteTarget.value  = null
        fetchExpenses()
    } finally {
        deleteLoading.value = false
    }
}

// ── Dados ─────────────────────────────────────────────────────────────────────
async function fetchEvent() {
    try {
        const { data } = await axios.get(`/admin/api/events/${eventId}`)
        event.value = data
    } catch {
        notFound.value = true
    }
}

async function fetchExpenses(page = meta.value.current_page) {
    loading.value = true
    try {
        const params = { page, ...filters.value }
        const { data } = await axios.get(`/admin/api/events/${eventId}/expenses`, { params })
        expenses.value = data.data
        summary.value  = data.summary
        meta.value     = data.meta
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await fetchEvent()
    if (!notFound.value) await fetchExpenses(1)
})

// ── Formatação ────────────────────────────────────────────────────────────────
const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const fmt      = (v) => currency.format(Number(v) || 0)

function fmtDate(d) {
    if (!d) return ''
    const [y, m, day] = (d.substring ? d.substring(0, 10) : d).split('-')
    return `${day}/${m}/${y}`
}

const CATEGORY_COLORS = {
    alimentacao:   'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    transporte:    'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    hospedagem:    'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
    equipamentos:  'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    marketing:     'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300',
    infraestrutura: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
    palestrantes:  'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
    premiacao:     'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
    outros:        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
}

const CATEGORIES = [
    { value: 'alimentacao',   label: 'Alimentação' },
    { value: 'transporte',    label: 'Transporte' },
    { value: 'hospedagem',    label: 'Hospedagem' },
    { value: 'equipamentos',  label: 'Equipamentos' },
    { value: 'marketing',     label: 'Marketing e Divulgação' },
    { value: 'infraestrutura', label: 'Infraestrutura' },
    { value: 'palestrantes',  label: 'Ajuda de Custo — Palestrantes' },
    { value: 'premiacao',     label: 'Premiação e Brindes' },
    { value: 'outros',        label: 'Outros' },
]

const hasActiveFilters = computed(() =>
    Object.values(filters.value).some(v => v !== '')
)

const progressPct = computed(() => {
    if (!summary.value.total) return 0
    return Math.round((summary.value.paid / summary.value.total) * 100)
})

const selectClass = 'px-3 py-2 text-sm rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition'
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- Voltar -->
        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: eventId } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar para {{ event?.name ?? 'o evento' }}
        </RouterLink>

        <!-- Evento não encontrado -->
        <div v-if="notFound" class="text-center py-20">
            <p class="text-lg font-semibold text-(--color-text) mb-2">Evento não encontrado</p>
            <RouterLink :to="{ name: 'admin.events' }" class="text-sm text-(--color-primary) hover:underline">
                ← Voltar para Eventos
            </RouterLink>
        </div>

        <template v-else>

            <!-- Título da seção -->
            <div class="flex items-center gap-3 mb-6">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <h1 class="text-xl font-bold text-(--color-text)">Despesas</h1>
                <span v-if="event" class="text-sm text-(--color-text-muted)">— {{ event.name }}</span>
            </div>

            <!-- Painel de totais -->
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 mb-6">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-(--color-text-muted) mb-0.5">Total geral</p>
                        <p class="text-lg font-bold text-(--color-text)">{{ fmt(summary.total) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-(--color-text-muted) mb-0.5">Pago</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ fmt(summary.paid) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-(--color-text-muted) mb-0.5">Pendente</p>
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ fmt(summary.pending) }}</p>
                    </div>
                </div>

                <!-- Barra de progresso -->
                <div class="h-2 rounded-full bg-(--color-border) overflow-hidden">
                    <div
                        class="h-full rounded-full bg-green-500 transition-all duration-500"
                        :style="{ width: progressPct + '%' }"
                    />
                </div>
                <p class="text-xs text-(--color-text-muted) mt-1">{{ progressPct }}% pago</p>
            </div>

            <!-- Barra de ações + filtros -->
            <div class="flex flex-wrap items-center gap-3 mb-5">

                <!-- Botão registrar -->
                <button
                    type="button"
                    class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg bg-(--color-primary) hover:bg-(--color-primary-hover) transition shrink-0"
                    @click="openCreate"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Registrar despesa
                </button>

                <!-- Toggle visualização -->
                <div class="flex rounded-lg border border-(--color-border) overflow-hidden ml-auto">
                    <button
                        type="button"
                        :class="['px-3 py-2 text-sm transition', viewMode === 'cards'
                            ? 'bg-(--color-primary) text-white'
                            : 'bg-(--color-surface) text-(--color-text-muted) hover:text-(--color-text)']"
                        :aria-pressed="viewMode === 'cards'"
                        title="Ver em cards"
                        @click="setView('cards')"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        :class="['px-3 py-2 text-sm transition border-l border-(--color-border)', viewMode === 'list'
                            ? 'bg-(--color-primary) text-white'
                            : 'bg-(--color-surface) text-(--color-text-muted) hover:text-(--color-text)']"
                        :aria-pressed="viewMode === 'list'"
                        title="Ver em lista"
                        @click="setView('list')"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                </div>

            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="filters.category" :class="selectClass">
                    <option value="">Todas as categorias</option>
                    <option v-for="cat in CATEGORIES" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
                <select v-model="filters.is_paid" :class="selectClass">
                    <option value="">Todos os status</option>
                    <option value="false">Pendente</option>
                    <option value="true">Pago</option>
                </select>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-(--color-text-muted) shrink-0">De</label>
                    <input v-model="filters.date_from" type="date" :class="selectClass">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-(--color-text-muted) shrink-0">Até</label>
                    <input v-model="filters.date_to" type="date" :class="selectClass">
                </div>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="text-xs text-(--color-text-muted) hover:text-(--color-danger) transition"
                    @click="filters = { category: '', is_paid: '', date_from: '', date_to: '' }"
                >
                    Limpar filtros
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-16">
                <svg class="animate-spin w-7 h-7 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
            </div>

            <template v-else>

                <!-- Estado vazio -->
                <div v-if="expenses.length === 0" class="text-center py-16">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-(--color-text-muted) opacity-30" aria-hidden="true">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <p class="text-base font-semibold text-(--color-text) mb-1">Nenhuma despesa registrada</p>
                    <p class="text-sm text-(--color-text-muted) max-w-xs mx-auto">
                        {{ hasActiveFilters
                            ? 'Nenhuma despesa encontrada para os filtros aplicados.'
                            : 'Clique em "+ Registrar despesa" para começar.' }}
                    </p>
                </div>

                <!-- Modo cards -->
                <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex flex-col gap-3 hover:shadow-md transition-shadow"
                    >
                        <!-- Categoria + status -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-md', CATEGORY_COLORS[expense.category]]">
                                {{ expense.category_label }}
                            </span>
                            <span
                                :class="['ml-auto text-xs font-medium px-2 py-0.5 rounded-md shrink-0',
                                    expense.is_paid
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400']"
                            >
                                {{ expense.is_paid ? 'Pago' : 'Pendente' }}
                            </span>
                        </div>

                        <!-- Descrição -->
                        <p class="text-sm font-medium text-(--color-text) line-clamp-2">{{ expense.description }}</p>

                        <!-- Data -->
                        <p class="text-xs text-(--color-text-muted) flex items-center gap-1.5">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            {{ fmtDate(expense.date) }}
                        </p>

                        <!-- Valor -->
                        <p class="text-base font-bold text-(--color-text) mt-auto">{{ fmt(expense.amount) }}</p>

                        <!-- Registrado por -->
                        <p v-if="expense.creator" class="text-xs text-(--color-text-muted)">por {{ expense.creator.name }}</p>

                        <!-- Comprovante -->
                        <a
                            v-if="expense.receipt_url"
                            :href="expense.receipt_url"
                            target="_blank"
                            rel="noopener"
                            class="text-xs text-(--color-primary) hover:underline flex items-center gap-1"
                        >
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                            </svg>
                            Ver comprovante
                        </a>

                        <!-- Ações (admin) -->
                        <div v-if="isAdmin" class="flex gap-2 pt-1 border-t border-(--color-border)">
                            <button
                                type="button"
                                class="text-xs text-(--color-primary) hover:underline"
                                @click="openEdit(expense)"
                            >
                                Editar
                            </button>
                            <button
                                type="button"
                                class="text-xs text-(--color-danger) hover:underline ml-auto"
                                @click="askDelete(expense)"
                            >
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modo lista -->
                <div v-else class="overflow-x-auto rounded-xl border border-(--color-border)">
                    <table class="w-full text-sm">
                        <thead class="bg-(--color-bg) border-b border-(--color-border)">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide">Categoria</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide">Descrição</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide whitespace-nowrap">Data</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide">Valor</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide">Status</th>
                                <th v-if="isAdmin" class="px-4 py-3"/>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-(--color-border) bg-(--color-surface)">
                            <tr
                                v-for="expense in expenses"
                                :key="expense.id"
                                class="hover:bg-(--color-bg) transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-md whitespace-nowrap', CATEGORY_COLORS[expense.category]]">
                                        {{ expense.category_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-(--color-text) max-w-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate">{{ expense.description }}</span>
                                        <a
                                            v-if="expense.receipt_url"
                                            :href="expense.receipt_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-(--color-primary) shrink-0"
                                            title="Ver comprovante"
                                        >
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-(--color-text-muted) whitespace-nowrap">{{ fmtDate(expense.date) }}</td>
                                <td class="px-4 py-3 text-(--color-text) font-medium text-right whitespace-nowrap">{{ fmt(expense.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="['text-xs font-medium px-2 py-0.5 rounded-md whitespace-nowrap',
                                            expense.is_paid
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400']"
                                    >
                                        {{ expense.is_paid ? 'Pago' : 'Pendente' }}
                                    </span>
                                </td>
                                <td v-if="isAdmin" class="px-4 py-3">
                                    <div class="flex items-center gap-3 justify-end">
                                        <button
                                            type="button"
                                            class="text-xs text-(--color-primary) hover:underline whitespace-nowrap"
                                            @click="openEdit(expense)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs text-(--color-danger) hover:underline whitespace-nowrap"
                                            @click="askDelete(expense)"
                                        >
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="meta.last_page > 1" class="flex justify-center items-center gap-2 mt-6">
                    <button
                        v-for="page in meta.last_page"
                        :key="page"
                        type="button"
                        :class="[
                            'w-8 h-8 rounded-lg text-sm font-medium transition',
                            page === meta.current_page
                                ? 'bg-(--color-primary) text-white'
                                : 'border border-(--color-border) text-(--color-text) hover:bg-(--color-bg)',
                        ]"
                        @click="fetchExpenses(page)"
                    >
                        {{ page }}
                    </button>
                </div>

            </template>

        </template>

        <!-- Modal de criação / edição -->
        <ExpenseModal
            :show="showModal"
            :event-id="eventId"
            :expense="editExpense"
            @close="showModal = false"
            @saved="onSaved"
        />

        <!-- Modal de confirmação de exclusão -->
        <ConfirmModal
            :show="confirmDelete"
            title="Excluir despesa"
            :message="`Tem certeza que deseja excluir a despesa &quot;${deleteTarget?.description}&quot;? Esta ação é irreversível e o comprovante também será removido.`"
            confirm-label="Excluir"
            :loading="deleteLoading"
            :danger="true"
            @confirm="doDelete"
            @cancel="confirmDelete = false; deleteTarget = null"
        />

    </div>
</template>
