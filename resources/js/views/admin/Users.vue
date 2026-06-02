<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import UserModal from '@/components/UserModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const users    = ref([])
const meta     = ref({ current_page: 1, last_page: 1, per_page: 9, total: 0 })
const loading  = ref(false)
const filters  = reactive({ search: '', role: '', status: '' })

const showUserModal    = ref(false)
const editingUser      = ref(null)
const showConfirmModal = ref(false)
const togglingUser     = ref(null)
const togglingLoading  = ref(false)

const roleLabels = { admin: 'Administrador', colaborador: 'Colaborador', palestrante: 'Palestrante' }

async function fetchUsers(page = 1) {
    loading.value = true
    try {
        const { data } = await axios.get('/admin/api/users', {
            params: { ...filters, page },
        })
        users.value = data.data
        meta.value  = data.meta
    } finally {
        loading.value = false
    }
}

function openCreate() {
    editingUser.value   = null
    showUserModal.value = true
}

function openEdit(user) {
    editingUser.value   = user
    showUserModal.value = true
}

function onModalSaved() {
    showUserModal.value = false
    fetchUsers(meta.value.current_page)
}

function requestToggle(user) {
    togglingUser.value = user
    if (user.is_active) {
        showConfirmModal.value = true
    } else {
        executeToggle()
    }
}

async function executeToggle() {
    if (!togglingUser.value) return
    togglingLoading.value = true
    try {
        await axios.patch(`/admin/api/users/${togglingUser.value.id}/toggle-status`)
        await fetchUsers(meta.value.current_page)
    } finally {
        togglingLoading.value  = false
        showConfirmModal.value = false
        togglingUser.value     = null
    }
}

function initials(name) {
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function avatarColor(role) {
    return {
        admin:       'bg-(--color-primary) text-white',
        colaborador: 'bg-amber-400 text-white',
        palestrante: 'bg-(--color-success) text-white',
    }[role] ?? 'bg-gray-300 text-white'
}

function roleBadge(role) {
    return {
        admin:       'bg-blue-100 text-blue-700',
        colaborador: 'bg-amber-100 text-amber-700',
        palestrante: 'bg-green-100 text-green-700',
    }[role] ?? 'bg-gray-100 text-gray-600'
}

function formatLastLogin(date) {
    if (!date) return 'Nunca'
    const diff = Math.floor((Date.now() - new Date(date)) / 86400000)
    if (diff === 0) return 'Hoje'
    if (diff === 1) return 'Ontem'
    return `há ${diff} dias`
}

let searchTimeout
function onSearchInput() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchUsers(1), 400)
}

onMounted(() => fetchUsers())
</script>

<template>
    <div class="min-h-screen bg-(--color-bg)">
        <div class="max-w-7xl mx-auto px-4 py-8">

            <!-- Cabeçalho -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-(--color-text)">Usuários</h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover)
                           text-white text-sm font-semibold rounded-lg transition"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Novo usuário
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
                        placeholder="Buscar por nome ou e-mail..."
                        @input="onSearchInput"
                        class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-(--color-border) bg-white text-(--color-text) text-sm
                               focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                    >
                </div>
                <select
                    v-model="filters.role"
                    @change="fetchUsers(1)"
                    class="px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-white text-(--color-text) text-sm
                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                >
                    <option value="">Todas as funções</option>
                    <option value="admin">Administrador</option>
                    <option value="colaborador">Colaborador</option>
                    <option value="palestrante">Palestrante</option>
                </select>
                <select
                    v-model="filters.status"
                    @change="fetchUsers(1)"
                    class="px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-white text-(--color-text) text-sm
                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                >
                    <option value="">Todos os status</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
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
            <div v-else-if="users.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white rounded-xl border border-(--color-border) p-5 flex flex-col gap-4"
                >
                    <!-- Topo do card -->
                    <div class="flex items-start gap-3">
                        <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0', avatarColor(user.role)]">
                            {{ initials(user.name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-(--color-text) leading-snug truncate">{{ user.name }}</p>
                            <p class="text-sm text-(--color-text-muted) truncate">{{ user.email }}</p>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', roleBadge(user.role)]">
                            {{ roleLabels[user.role] }}
                        </span>
                        <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', user.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                            {{ user.is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <!-- Metadados -->
                    <div class="text-xs text-(--color-text-muted) space-y-1">
                        <p>Último acesso: {{ formatLastLogin(user.last_login_at) }}</p>
                        <p>Criado por: {{ user.created_by ? '#' + user.created_by : 'Seed' }}</p>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center justify-between pt-3 border-t border-(--color-border)">
                        <!-- Toggle ativo/inativo -->
                        <button
                            @click="requestToggle(user)"
                            :aria-label="user.is_active ? 'Desativar usuário' : 'Ativar usuário'"
                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition',
                                     user.is_active ? 'bg-(--color-primary)' : 'bg-gray-300']"
                        >
                            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition',
                                           user.is_active ? 'translate-x-6' : 'translate-x-1']"/>
                        </button>

                        <!-- Editar (oculto para palestrantes) -->
                        <button
                            v-if="user.role !== 'palestrante'"
                            @click="openEdit(user)"
                            class="text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                        >
                            Editar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estado vazio -->
            <div v-else class="text-center py-20 text-(--color-text-muted)">
                <p class="text-lg font-medium mb-1">Nenhum usuário encontrado</p>
                <p class="text-sm">Tente ajustar os filtros ou crie um novo usuário.</p>
            </div>

            <!-- Paginação -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-8">
                <p class="text-sm text-(--color-text-muted)">
                    {{ meta.total }} usuário{{ meta.total !== 1 ? 's' : '' }} no total
                </p>
                <div class="flex items-center gap-1">
                    <button
                        v-for="page in meta.last_page"
                        :key="page"
                        @click="fetchUsers(page)"
                        :class="['w-9 h-9 rounded-lg text-sm font-medium transition',
                                 page === meta.current_page
                                     ? 'bg-(--color-primary) text-white'
                                     : 'text-(--color-text) hover:bg-gray-100']"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modais -->
    <UserModal
        :show="showUserModal"
        :user="editingUser"
        @close="showUserModal = false"
        @saved="onModalSaved"
    />

    <ConfirmModal
        :show="showConfirmModal"
        title="Desativar usuário"
        :message="`Tem certeza que deseja desativar ${togglingUser?.name}? O usuário não conseguirá mais acessar o painel.`"
        confirm-label="Desativar"
        :loading="togglingLoading"
        danger
        @confirm="executeToggle"
        @cancel="showConfirmModal = false; togglingUser = null"
    />
</template>
