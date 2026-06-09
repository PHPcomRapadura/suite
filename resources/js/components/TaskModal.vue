<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import ConfirmModal from '@/components/ConfirmModal.vue'

const props = defineProps({
    show:      { type: Boolean, required: true },
    eventId:   { type: [Number, String], required: true },
    task:      { type: Object, default: null },
    assignees: { type: Array, default: () => [] },
    isAdmin:   { type: Boolean, default: false },
    initialStatus: { type: String, default: 'a_fazer' },
})

const emit = defineEmits(['close', 'saved', 'comment-changed'])

const isEditing = computed(() => !!props.task)

const STATUSES = [
    { value: 'a_fazer',      label: 'A Fazer' },
    { value: 'em_andamento', label: 'Em Andamento' },
    { value: 'em_revisao',   label: 'Em Revisão' },
    { value: 'impedimento',  label: 'Impedimento' },
    { value: 'concluida',    label: 'Concluída' },
]

const PRIORITIES = [
    { value: 'baixa', label: 'Baixa' },
    { value: 'media', label: 'Média' },
    { value: 'alta',  label: 'Alta' },
]

const defaultForm = () => ({
    title:       '',
    description: '',
    status:      props.initialStatus || 'a_fazer',
    priority:    'media',
    assigned_to: '',
    due_date:    '',
})

const form    = ref(defaultForm())
const errors  = ref({})
const loading = ref(false)
const activeTab = ref('details')

// Comments
const comments       = ref([])
const loadingComments = ref(false)
const commentBody    = ref('')
const sendingComment = ref(false)
const editingCommentId   = ref(null)
const editingCommentBody = ref('')
const savingCommentEdit  = ref(false)
const confirmDeleteComment = ref(null)

watch(() => props.show, async (val) => {
    if (!val) {
        activeTab.value = 'details'
        return
    }
    errors.value = {}
    if (props.task) {
        form.value = {
            title:       props.task.title,
            description: props.task.description ?? '',
            status:      props.task.status,
            priority:    props.task.priority,
            assigned_to: props.task.assigned_to ?? '',
            due_date:    props.task.due_date ?? '',
        }
    } else {
        form.value = defaultForm()
        form.value.status = props.initialStatus || 'a_fazer'
    }
    commentBody.value = ''
    editingCommentId.value = null
})

watch(activeTab, async (tab) => {
    if (tab === 'comments' && props.task) {
        await loadComments()
    }
})

async function loadComments() {
    loadingComments.value = true
    try {
        const res = await axios.get(`/admin/api/events/${props.eventId}/tasks/${props.task.id}/comments`)
        comments.value = res.data.data
    } finally {
        loadingComments.value = false
    }
}

async function submit() {
    if (!props.isAdmin) return
    loading.value = true
    errors.value  = {}

    const url = isEditing.value
        ? `/admin/api/events/${props.eventId}/tasks/${props.task.id}`
        : `/admin/api/events/${props.eventId}/tasks`

    const method = isEditing.value ? 'put' : 'post'

    try {
        await axios[method](url, form.value)
        emit('saved')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {}
        }
    } finally {
        loading.value = false
    }
}

async function sendComment() {
    if (!commentBody.value.trim()) return
    sendingComment.value = true
    try {
        const res = await axios.post(
            `/admin/api/events/${props.eventId}/tasks/${props.task.id}/comments`,
            { body: commentBody.value }
        )
        comments.value.push(res.data.data)
        commentBody.value = ''
        emit('comment-changed', { taskId: props.task.id, delta: 1 })
    } finally {
        sendingComment.value = false
    }
}

function startEditComment(comment) {
    editingCommentId.value = comment.id
    editingCommentBody.value = comment.body
}

function cancelEditComment() {
    editingCommentId.value = null
    editingCommentBody.value = ''
}

async function saveCommentEdit(comment) {
    savingCommentEdit.value = true
    try {
        const res = await axios.put(
            `/admin/api/events/${props.eventId}/tasks/${props.task.id}/comments/${comment.id}`,
            { body: editingCommentBody.value }
        )
        const idx = comments.value.findIndex(c => c.id === comment.id)
        if (idx !== -1) comments.value[idx] = res.data.data
        editingCommentId.value = null
    } finally {
        savingCommentEdit.value = false
    }
}

async function deleteComment() {
    const comment = confirmDeleteComment.value
    if (!comment) return
    await axios.delete(`/admin/api/events/${props.eventId}/tasks/${props.task.id}/comments/${comment.id}`)
    comments.value = comments.value.filter(c => c.id !== comment.id)
    confirmDeleteComment.value = null
    emit('comment-changed', { taskId: props.task.id, delta: -1 })
}

function formatCommentDate(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    return d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const inputClass = (field) => [
    'w-full px-3.5 py-2.5 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm',
    'focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition',
    errors.value[field] ? 'border-(--color-danger)' : 'border-(--color-border)',
]
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-lg bg-(--color-surface) rounded-2xl shadow-xl my-8">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 pt-6 pb-4">
                        <h2 class="text-lg font-semibold text-(--color-text)">
                            {{ isEditing ? 'Editar tarefa' : 'Nova tarefa' }}
                        </h2>
                        <button
                            type="button"
                            class="text-(--color-text-muted) hover:text-(--color-text) transition"
                            @click="emit('close')"
                            aria-label="Fechar"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Tabs (somente ao editar) -->
                    <div v-if="isEditing" class="flex border-b border-(--color-border) px-6">
                        <button
                            type="button"
                            :class="['pb-2.5 mr-6 text-sm font-medium border-b-2 transition', activeTab === 'details' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)']"
                            @click="activeTab = 'details'"
                        >Detalhes</button>
                        <button
                            type="button"
                            :class="['pb-2.5 text-sm font-medium border-b-2 transition', activeTab === 'comments' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)']"
                            @click="activeTab = 'comments'"
                        >
                            Comentários
                            <span v-if="comments.length" class="ml-1 text-xs bg-(--color-border) rounded-full px-1.5 py-0.5">{{ comments.length }}</span>
                        </button>
                    </div>

                    <!-- Aba Detalhes -->
                    <div v-show="activeTab === 'details'" class="p-6">
                        <form @submit.prevent="submit" class="space-y-4" novalidate>

                            <!-- Título -->
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Título <span class="text-(--color-danger)">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Ex: Confirmar local do evento"
                                    :class="inputClass('title')"
                                    :disabled="!isAdmin"
                                >
                                <p v-if="errors.title" class="mt-1 text-xs text-(--color-danger)">{{ errors.title[0] }}</p>
                            </div>

                            <!-- Descrição -->
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Descrição <span class="text-xs text-(--color-text-muted) font-normal">(opcional)</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    maxlength="5000"
                                    placeholder="Detalhes da tarefa..."
                                    :class="inputClass('description')"
                                    :disabled="!isAdmin"
                                />
                                <p v-if="errors.description" class="mt-1 text-xs text-(--color-danger)">{{ errors.description[0] }}</p>
                            </div>

                            <!-- Coluna + Prioridade -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                        Coluna <span class="text-(--color-danger)">*</span>
                                    </label>
                                    <select v-model="form.status" :class="inputClass('status')" :disabled="!isAdmin">
                                        <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                    <p v-if="errors.status" class="mt-1 text-xs text-(--color-danger)">{{ errors.status[0] }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                        Prioridade <span class="text-(--color-danger)">*</span>
                                    </label>
                                    <select v-model="form.priority" :class="inputClass('priority')" :disabled="!isAdmin">
                                        <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option>
                                    </select>
                                    <p v-if="errors.priority" class="mt-1 text-xs text-(--color-danger)">{{ errors.priority[0] }}</p>
                                </div>
                            </div>

                            <!-- Responsável + Prazo -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                        Responsável <span class="text-xs text-(--color-text-muted) font-normal">(opcional)</span>
                                    </label>
                                    <select v-model="form.assigned_to" :class="inputClass('assigned_to')" :disabled="!isAdmin">
                                        <option value="">Nenhum</option>
                                        <option v-for="u in assignees" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                    <p v-if="errors.assigned_to" class="mt-1 text-xs text-(--color-danger)">{{ errors.assigned_to[0] }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                        Prazo <span class="text-xs text-(--color-text-muted) font-normal">(opcional)</span>
                                    </label>
                                    <input
                                        v-model="form.due_date"
                                        type="date"
                                        :class="inputClass('due_date')"
                                        :disabled="!isAdmin"
                                    >
                                    <p v-if="errors.due_date" class="mt-1 text-xs text-(--color-danger)">{{ errors.due_date[0] }}</p>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    :disabled="loading"
                                    class="px-4 py-2 text-sm font-medium text-(--color-text) border border-(--color-border) rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-60"
                                    @click="emit('close')"
                                >Cancelar</button>
                                <button
                                    v-if="isAdmin"
                                    type="submit"
                                    :disabled="loading"
                                    class="px-4 py-2 text-sm font-medium text-white rounded-lg transition bg-(--color-primary) hover:bg-(--color-primary-hover) disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                    </svg>
                                    Salvar
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Aba Comentários -->
                    <div v-if="isEditing && activeTab === 'comments'" class="p-6">

                        <!-- Loading -->
                        <div v-if="loadingComments" class="flex justify-center py-8">
                            <svg class="animate-spin w-6 h-6 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                        </div>

                        <div v-else class="space-y-3">

                            <!-- Lista de comentários -->
                            <div
                                v-for="comment in comments"
                                :key="comment.id"
                                class="p-3 rounded-lg border border-(--color-border) bg-(--color-bg)"
                            >
                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <span class="text-sm font-medium text-(--color-text)">{{ comment.author?.name ?? 'Usuário' }}</span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs text-(--color-text-muted)">{{ formatCommentDate(comment.created_at) }}</span>
                                        <template v-if="comment.is_mine">
                                            <button
                                                type="button"
                                                class="text-(--color-text-muted) hover:text-(--color-primary) transition"
                                                title="Editar"
                                                @click="startEditComment(comment)"
                                            >
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="text-(--color-text-muted) hover:text-(--color-danger) transition"
                                                title="Excluir"
                                                @click="confirmDeleteComment = comment"
                                            >
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Modo visualização -->
                                <p v-if="editingCommentId !== comment.id" class="text-sm text-(--color-text) whitespace-pre-wrap">{{ comment.body }}</p>

                                <!-- Modo edição inline -->
                                <div v-else class="space-y-2">
                                    <textarea
                                        v-model="editingCommentBody"
                                        rows="3"
                                        maxlength="2000"
                                        class="w-full px-3 py-2 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition resize-none"
                                    />
                                    <div class="flex gap-2 justify-end">
                                        <button
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium text-(--color-text) border border-(--color-border) rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                            @click="cancelEditComment"
                                        >Cancelar</button>
                                        <button
                                            type="button"
                                            :disabled="savingCommentEdit"
                                            class="px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-(--color-primary) hover:bg-(--color-primary-hover) transition disabled:opacity-60"
                                            @click="saveCommentEdit(comment)"
                                        >Salvar</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado vazio -->
                            <p v-if="!comments.length" class="text-sm text-center text-(--color-text-muted) py-6">
                                Nenhum comentário ainda.
                            </p>

                            <!-- Novo comentário -->
                            <div class="pt-2 border-t border-(--color-border) space-y-2">
                                <label class="block text-sm font-medium text-(--color-text)">Adicionar comentário</label>
                                <textarea
                                    v-model="commentBody"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Escreva um comentário..."
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition resize-none"
                                />
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        :disabled="!commentBody.trim() || sendingComment"
                                        class="px-4 py-2 text-sm font-medium text-white rounded-lg transition bg-(--color-primary) hover:bg-(--color-primary-hover) disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                                        @click="sendComment"
                                    >
                                        <svg v-if="sendingComment" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                        </svg>
                                        Enviar
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Confirm delete comment -->
    <ConfirmModal
        :show="!!confirmDeleteComment"
        title="Excluir comentário"
        :message="`Deseja excluir este comentário? Essa ação não pode ser desfeita.`"
        confirm-label="Excluir"
        confirm-class="bg-(--color-danger) hover:opacity-90 text-white"
        @confirm="deleteComment"
        @cancel="confirmDeleteComment = null"
    />
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from,
.modal-leave-to    { opacity: 0; }
</style>
