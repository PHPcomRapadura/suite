<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth.js'
import TaskModal from '@/components/TaskModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const route  = useRoute()
const { user } = useAuth()

const isAdmin = computed(() => user.value?.role === 'admin')

const eventId   = route.params.id
const eventName = ref('')
const loading   = ref(true)
const activeTab = ref('board')

const board = ref({
    a_fazer:      [],
    em_andamento: [],
    em_revisao:   [],
    impedimento:  [],
    concluida:    [],
})
const summary   = ref({ total: 0, concluida: 0, overdue: 0 })
const assignees = ref([])
const trash     = ref([])
const loadingTrash = ref(false)

const COLUMNS = [
    { key: 'a_fazer',      label: 'A Fazer',      headerClass: 'text-(--color-text-muted)' },
    { key: 'em_andamento', label: 'Em Andamento',  headerClass: 'text-blue-600 dark:text-blue-400' },
    { key: 'em_revisao',   label: 'Em Revisão',    headerClass: 'text-amber-600 dark:text-amber-400' },
    { key: 'impedimento',  label: 'Impedimento',   headerClass: 'text-red-600 dark:text-red-400' },
    { key: 'concluida',    label: 'Concluída',     headerClass: 'text-(--color-success)' },
]

const PRIORITY_CLASSES = {
    alta:  { bg: 'bg-red-100 dark:bg-red-900/30',  text: 'text-red-600 dark:text-red-400' },
    media: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400' },
    baixa: { bg: 'bg-gray-100 dark:bg-gray-800',    text: 'text-gray-500 dark:text-gray-400' },
}

// Modal
const showModal     = ref(false)
const editingTask   = ref(null)
const modalInitialStatus = ref('a_fazer')

// Confirm delete
const confirmTask = ref(null)

// Drag-and-drop state
const dragging = ref(null)      // { task, fromStatus }
const dragOver = ref(null)      // column key being hovered
const dragOverCard = ref(null)  // card id being hovered

async function fetchBoard() {
    loading.value = true
    try {
        const [eventRes, tasksRes] = await Promise.all([
            axios.get(`/admin/api/events/${eventId}`),
            axios.get(`/admin/api/events/${eventId}/tasks`),
        ])
        eventName.value = eventRes.data.name
        board.value     = { a_fazer: [], em_andamento: [], em_revisao: [], impedimento: [], concluida: [], ...tasksRes.data.data }
        summary.value   = tasksRes.data.summary
        assignees.value = tasksRes.data.assignees
    } finally {
        loading.value = false
    }
}

async function fetchTrash() {
    loadingTrash.value = true
    try {
        const res = await axios.get(`/admin/api/events/${eventId}/tasks/trash`)
        trash.value = res.data.data
    } finally {
        loadingTrash.value = false
    }
}

function openCreate(status = 'a_fazer') {
    editingTask.value = null
    modalInitialStatus.value = status
    showModal.value = true
}

function openEdit(task) {
    editingTask.value = task
    showModal.value = true
}

async function onSaved() {
    showModal.value = false
    await fetchBoard()
}

function onCommentChanged({ taskId, delta }) {
    for (const col of Object.values(board.value)) {
        const task = col.find(t => t.id === taskId)
        if (task) {
            task.comments_count = Math.max(0, (task.comments_count ?? 0) + delta)
            break
        }
    }
}

async function deleteTask() {
    if (!confirmTask.value) return
    await axios.delete(`/admin/api/events/${eventId}/tasks/${confirmTask.value.id}`)
    confirmTask.value = null
    await fetchBoard()
}

async function restoreTask(task) {
    await axios.patch(`/admin/api/events/${eventId}/tasks/${task.id}/restore`)
    trash.value = trash.value.filter(t => t.id !== task.id)
    await fetchBoard()
}

// ─── Drag and Drop ────────────────────────────────────────────────────────────

function onDragStart(e, task, fromStatus) {
    dragging.value = { task, fromStatus }
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('taskId', String(task.id))
}

function onDragEnd() {
    dragging.value = null
    dragOver.value = null
    dragOverCard.value = null
}

function onDragOverColumn(e, colKey) {
    e.preventDefault()
    dragOver.value = colKey
    dragOverCard.value = null
}

function onDragOverCard(e, colKey, cardId) {
    e.preventDefault()
    e.stopPropagation()
    dragOver.value = colKey
    dragOverCard.value = cardId
}

function onDragLeaveColumn() {
    dragOver.value = null
}

async function onDropColumn(colKey) {
    if (!dragging.value) return

    const { task, fromStatus } = dragging.value
    dragOver.value = null
    dragOverCard.value = null

    const targetCardId = dragOverCard.value
    const targetColumn = board.value[colKey]

    // Remove task from source column optimistically
    board.value[fromStatus] = board.value[fromStatus].filter(t => t.id !== task.id)

    // Calculate insert position
    let insertIndex = targetColumn.length
    if (targetCardId) {
        const idx = targetColumn.findIndex(t => t.id === targetCardId)
        if (idx !== -1) insertIndex = idx
    }

    // Update task status optimistically
    const updatedTask = { ...task, status: colKey }
    targetColumn.splice(insertIndex, 0, updatedTask)
    board.value[colKey] = [...targetColumn]

    // Recalculate sort_orders
    board.value[colKey] = board.value[colKey].map((t, i) => ({ ...t, sort_order: i }))

    try {
        if (fromStatus !== colKey) {
            await axios.patch(`/admin/api/events/${eventId}/tasks/${task.id}/status`, { status: colKey })
        }
        await axios.patch(`/admin/api/events/${eventId}/tasks/reorder`, {
            items: board.value[colKey].map(t => ({ id: t.id, sort_order: t.sort_order })),
        })
        // Refresh summary
        const res = await axios.get(`/admin/api/events/${eventId}/tasks`)
        summary.value = res.data.summary
    } catch {
        // Revert on error
        await fetchBoard()
    }
}

function formatDate(dateStr) {
    if (!dateStr) return null
    const [y, m, d] = dateStr.split('-')
    return `${d}/${m}/${y}`
}

function formatDeletedAt(iso) {
    if (!iso) return ''
    return new Date(iso).toLocaleDateString('pt-BR')
}

const statusLabel = {
    a_fazer:      'A Fazer',
    em_andamento: 'Em Andamento',
    em_revisao:   'Em Revisão',
    impedimento:  'Impedimento',
    concluida:    'Concluída',
}

onMounted(() => fetchBoard())
</script>

<template>
    <div class="px-4 py-6 max-w-full">

        <!-- Voltar -->
        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: eventId } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar para o evento<span v-if="eventName">: {{ eventName }}</span>
        </RouterLink>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>

            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-(--color-text) flex items-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted)" aria-hidden="true">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        Tarefas
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-(--color-text-muted)">
                        <span>{{ summary.total }} tarefa{{ summary.total !== 1 ? 's' : '' }}</span>
                        <span>·</span>
                        <span>{{ summary.concluida }} concluída{{ summary.concluida !== 1 ? 's' : '' }}</span>
                        <span v-if="summary.overdue > 0" class="text-(--color-danger) font-medium">
                            · ⚠ {{ summary.overdue }} atrasada{{ summary.overdue !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
                <button
                    v-if="isAdmin"
                    type="button"
                    class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg bg-(--color-primary) hover:bg-(--color-primary-hover) transition"
                    @click="openCreate()"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nova tarefa
                </button>
            </div>

            <!-- Abas -->
            <div class="flex border-b border-(--color-border) mb-6 gap-6">
                <button
                    type="button"
                    :class="['pb-2.5 text-sm font-medium border-b-2 transition', activeTab === 'board' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)']"
                    @click="activeTab = 'board'"
                >Board</button>
                <button
                    v-if="isAdmin"
                    type="button"
                    :class="['pb-2.5 text-sm font-medium border-b-2 transition', activeTab === 'trash' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)']"
                    @click="activeTab = 'trash'; fetchTrash()"
                >
                    Lixeira
                    <span v-if="trash.length" class="ml-1 text-xs bg-(--color-border) rounded-full px-1.5 py-0.5">{{ trash.length }}</span>
                </button>
            </div>

            <!-- Board -->
            <div v-if="activeTab === 'board'">

                <!-- Estado vazio total -->
                <div v-if="summary.total === 0" class="flex flex-col items-center justify-center py-20 text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-border) mb-4" aria-hidden="true">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <p class="text-(--color-text) font-medium mb-1">Nenhuma tarefa ainda</p>
                    <p class="text-sm text-(--color-text-muted) mb-6">
                        {{ isAdmin ? "Clique em '+ Nova tarefa' para começar a organizar o evento." : 'Nenhuma tarefa registrada para este evento.' }}
                    </p>
                    <button
                        v-if="isAdmin"
                        type="button"
                        class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg bg-(--color-primary) hover:bg-(--color-primary-hover) transition"
                        @click="openCreate()"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nova tarefa
                    </button>
                </div>

                <!-- Colunas Kanban -->
                <div v-else class="flex gap-4 overflow-x-auto pb-4">
                    <div
                        v-for="col in COLUMNS"
                        :key="col.key"
                        class="shrink-0 w-64 flex flex-col rounded-xl border border-(--color-border) bg-(--color-bg) transition"
                        :class="dragOver === col.key ? 'border-(--color-primary) bg-blue-50/30 dark:bg-blue-900/10' : ''"
                        @dragover.prevent="onDragOverColumn($event, col.key)"
                        @dragleave="onDragLeaveColumn"
                        @drop.prevent="onDropColumn(col.key)"
                    >
                        <!-- Cabeçalho da coluna -->
                        <div class="flex items-center justify-between px-4 py-3 border-b border-(--color-border)">
                            <span :class="['text-sm font-semibold', col.headerClass]">{{ col.label }}</span>
                            <span class="text-xs text-(--color-text-muted) bg-(--color-surface) border border-(--color-border) rounded-full px-2 py-0.5">
                                {{ board[col.key].length }}
                            </span>
                        </div>

                        <!-- Cards -->
                        <div class="flex flex-col gap-2 p-3 min-h-32 flex-1">

                            <!-- Estado vazio da coluna -->
                            <div v-if="board[col.key].length === 0" class="flex-1 flex items-center justify-center">
                                <p class="text-xs text-(--color-text-muted) opacity-60 text-center py-4">Nenhuma tarefa aqui</p>
                            </div>

                            <!-- Card de tarefa -->
                            <div
                                v-for="task in board[col.key]"
                                :key="task.id"
                                class="bg-(--color-surface) border border-(--color-border) rounded-[10px] p-3 cursor-pointer transition hover:shadow-[0_2px_8px_rgba(0,0,0,0.08)]"
                                :class="[
                                    dragging?.task?.id === task.id ? 'opacity-50' : '',
                                    dragOverCard === task.id ? 'border-(--color-primary)' : '',
                                ]"
                                draggable="true"
                                @dragstart="onDragStart($event, task, col.key)"
                                @dragend="onDragEnd"
                                @dragover="onDragOverCard($event, col.key, task.id)"
                                @click="openEdit(task)"
                            >
                                <!-- Badge prioridade -->
                                <span
                                    :class="['inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-[6px] mb-2', PRIORITY_CLASSES[task.priority].bg, PRIORITY_CLASSES[task.priority].text]"
                                >
                                    ● {{ task.priority_label }}
                                </span>

                                <!-- Título -->
                                <p class="text-sm font-medium text-(--color-text) line-clamp-2 mb-2">{{ task.title }}</p>

                                <!-- Metadados -->
                                <div class="space-y-1">
                                    <div v-if="task.due_date" class="flex items-center gap-1 text-xs" :class="task.is_overdue ? 'text-(--color-danger)' : 'text-(--color-text-muted)'">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <span>{{ task.is_overdue ? '⚠ ' : '' }}{{ formatDate(task.due_date) }}</span>
                                    </div>
                                    <div v-if="task.assignee" class="flex items-center gap-1 text-xs text-(--color-text-muted)">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        <span>{{ task.assignee.name }}</span>
                                    </div>
                                    <div v-if="task.comments_count > 0" class="flex items-center gap-1 text-xs text-(--color-text-muted)">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span>{{ task.comments_count }} comentário{{ task.comments_count !== 1 ? 's' : '' }}</span>
                                    </div>
                                </div>

                                <!-- Ações (somente admin, ao passar o mouse) -->
                                <div v-if="isAdmin" class="flex gap-1 mt-2 pt-2 border-t border-(--color-border)" @click.stop>
                                    <button
                                        type="button"
                                        class="text-xs text-(--color-danger) hover:underline"
                                        @click="confirmTask = task"
                                    >Excluir</button>
                                </div>
                            </div>

                            <!-- Botão adicionar -->
                            <button
                                v-if="isAdmin"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-2 text-xs text-(--color-text-muted) hover:text-(--color-primary) hover:bg-(--color-surface) rounded-lg transition border border-dashed border-(--color-border) hover:border-(--color-primary) mt-1"
                                @click="openCreate(col.key)"
                            >
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Adicionar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lixeira -->
            <div v-if="activeTab === 'trash' && isAdmin">
                <div v-if="loadingTrash" class="flex justify-center py-12">
                    <svg class="animate-spin w-6 h-6 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                </div>

                <div v-else-if="trash.length === 0" class="text-center py-16 text-sm text-(--color-text-muted)">
                    Nenhuma tarefa excluída.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="task in trash"
                        :key="task.id"
                        class="flex items-center justify-between gap-4 bg-(--color-surface) border border-(--color-border) rounded-xl px-4 py-3"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-(--color-text) truncate">{{ task.title }}</p>
                            <p class="text-xs text-(--color-text-muted) mt-0.5">Excluída em: {{ formatDeletedAt(task.deleted_at) }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-(--color-bg) border border-(--color-border) text-(--color-text-muted) shrink-0">
                            {{ statusLabel[task.status] }}
                        </span>
                        <button
                            type="button"
                            class="text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition shrink-0"
                            @click="restoreTask(task)"
                        >Restaurar</button>
                    </div>
                </div>
            </div>

        </template>

    </div>

    <!-- Modal de tarefa -->
    <TaskModal
        :show="showModal"
        :event-id="eventId"
        :task="editingTask"
        :assignees="assignees"
        :is-admin="isAdmin"
        :initial-status="modalInitialStatus"
        @close="showModal = false"
        @saved="onSaved"
        @comment-changed="onCommentChanged"
    />

    <!-- Confirm delete -->
    <ConfirmModal
        :show="!!confirmTask"
        title="Excluir tarefa"
        :message="`A tarefa &quot;${confirmTask?.title}&quot; será movida para a lixeira. Você poderá restaurá-la depois.`"
        confirm-label="Mover para lixeira"
        confirm-class="bg-(--color-danger) hover:opacity-90 text-white"
        @confirm="deleteTask"
        @cancel="confirmTask = null"
    />
</template>
