<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import SpeakerModal from '@/components/SpeakerModal.vue'

const speakers    = ref([])
const total       = ref(0)
const currentPage = ref(1)
const lastPage    = ref(1)
const loading     = ref(false)

const search   = ref('')
const city     = ref('')
const state    = ref('')
const viewMode = ref(localStorage.getItem('speakers_view_mode') ?? 'cards')

const selectedSpeakerId = ref(null)
const modalOpen         = ref(false)

const STATES = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
    'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO',
]

let searchTimer = null
let cityTimer   = null

function debounceSearch(val) {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => { search.value = val; currentPage.value = 1 }, 300)
}

function debounceCity(val) {
    clearTimeout(cityTimer)
    cityTimer = setTimeout(() => { city.value = val; currentPage.value = 1 }, 300)
}

function onStateChange(val) {
    state.value = val
    currentPage.value = 1
}

async function load() {
    loading.value = true
    try {
        const params = { page: currentPage.value }
        if (search.value) params.search = search.value
        if (city.value)   params.city   = city.value
        if (state.value)  params.state  = state.value

        const res = await axios.get('/admin/api/speakers', { params })
        speakers.value    = res.data.data
        total.value       = res.data.meta.total
        lastPage.value    = res.data.meta.last_page
    } finally {
        loading.value = false
    }
}

watch([search, city, state, currentPage], load, { immediate: true })

function toggleView(mode) {
    viewMode.value = mode
    localStorage.setItem('speakers_view_mode', mode)
}

function openModal(id) {
    selectedSpeakerId.value = id
    modalOpen.value = true
}

function closeModal() {
    modalOpen.value = false
    selectedSpeakerId.value = null
}

const pages = computed(() => {
    if (lastPage.value <= 7) return Array.from({ length: lastPage.value }, (_, i) => i + 1)
    const p = currentPage.value
    const l = lastPage.value
    const set = new Set([1, 2, p - 1, p, p + 1, l - 1, l].filter(n => n >= 1 && n <= l))
    const arr = [...set].sort((a, b) => a - b)
    const result = []
    let prev = null
    for (const n of arr) {
        if (prev !== null && n - prev > 1) result.push('…')
        result.push(n)
        prev = n
    }
    return result
})

function initials(name) {
    if (!name) return '?'
    return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}
</script>

<template>
    <div class="flex flex-col gap-6 p-5">

        <!-- Cabeçalho -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-semibold text-(--color-text)">Palestrantes</h1>
                <p class="text-sm text-(--color-text-muted) mt-0.5">
                    {{ total }} palestrante{{ total !== 1 ? 's' : '' }} cadastrado{{ total !== 1 ? 's' : '' }}
                </p>
            </div>

            <!-- Toggle cards / lista -->
            <div class="flex items-center gap-1 bg-(--color-surface) border border-(--color-border) rounded-lg p-1">
                <button
                    :class="['flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-md transition', viewMode === 'cards' ? 'bg-(--color-primary) text-white font-medium' : 'text-(--color-text-muted) hover:text-(--color-text)']"
                    @click="toggleView('cards')"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Cards
                </button>
                <button
                    :class="['flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-md transition', viewMode === 'lista' ? 'bg-(--color-primary) text-white font-medium' : 'text-(--color-text-muted) hover:text-(--color-text)']"
                    @click="toggleView('lista')"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    Lista
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-3">
            <input
                type="text"
                placeholder="Buscar por nome ou e-mail…"
                class="flex-1 min-w-[200px] h-10 px-3 rounded-lg border border-(--color-border) bg-(--color-surface) text-sm text-(--color-text) placeholder-text-(--color-text-muted) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30"
                @input="debounceSearch($event.target.value)"
            >
            <input
                type="text"
                placeholder="Cidade"
                class="w-44 h-10 px-3 rounded-lg border border-(--color-border) bg-(--color-surface) text-sm text-(--color-text) placeholder-text-(--color-text-muted) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30"
                @input="debounceCity($event.target.value)"
            >
            <select
                class="w-28 h-10 px-3 rounded-lg border border-(--color-border) bg-(--color-surface) text-sm text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30"
                @change="onStateChange($event.target.value)"
            >
                <option value="">Estado</option>
                <option v-for="uf in STATES" :key="uf" :value="uf">{{ uf }}</option>
            </select>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>
            <!-- Vazio -->
            <div v-if="speakers.length === 0" class="flex flex-col items-center justify-center py-20 text-center gap-3">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-(--color-text-muted)" aria-hidden="true">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
                <p class="text-sm font-medium text-(--color-text)">Nenhum palestrante encontrado</p>
                <p class="text-sm text-(--color-text-muted)">Tente ajustar os filtros de busca</p>
            </div>

            <!-- Cards -->
            <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <button
                    v-for="s in speakers"
                    :key="s.id"
                    class="flex flex-col gap-3 p-4 bg-(--color-surface) border border-(--color-border) rounded-xl text-left hover:border-(--color-primary)/40 hover:shadow-sm transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30"
                    @click="openModal(s.id)"
                >
                    <!-- Avatar + nome -->
                    <div class="flex items-center gap-3">
                        <img v-if="s.avatar_url" :src="s.avatar_url" :alt="s.name" class="w-11 h-11 rounded-full object-cover shrink-0">
                        <div v-else class="w-11 h-11 rounded-full bg-(--color-primary) flex items-center justify-center text-white font-bold text-sm shrink-0">
                            {{ initials(s.name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-(--color-text) truncate leading-tight">{{ s.name }}</p>
                            <p class="text-xs text-(--color-text-muted) truncate mt-0.5">{{ s.email }}</p>
                        </div>
                    </div>

                    <!-- Localização / empresa -->
                    <p v-if="s.company || s.city" class="text-xs text-(--color-text-muted) truncate">
                        <span v-if="s.company">{{ s.company }}</span>
                        <span v-if="s.company && s.city"> · </span>
                        <span v-if="s.city">{{ s.city }}<span v-if="s.state">, {{ s.state }}</span></span>
                    </p>

                    <!-- Rodapé: palestras + status -->
                    <div class="flex items-center justify-between mt-auto pt-1 border-t border-(--color-border)">
                        <p class="text-xs text-(--color-text-muted)">
                            {{ s.talks_count }} submetida{{ s.talks_count !== 1 ? 's' : '' }}
                            · {{ s.talks_approved }} aprovada{{ s.talks_approved !== 1 ? 's' : '' }}
                        </p>
                        <span
                            :class="[
                                'text-xs px-2 py-0.5 rounded-full font-medium',
                                s.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                            ]"
                        >{{ s.is_active ? 'Ativo' : 'Inativo' }}</span>
                    </div>
                </button>
            </div>

            <!-- Lista -->
            <div v-else class="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-(--color-border) bg-(--color-bg)">
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Palestrante</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide hidden md:table-cell">Localização</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide hidden lg:table-cell">Telefone</th>
                            <th class="text-center px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Palestras</th>
                            <th class="text-center px-4 py-3 text-xs font-medium text-(--color-text-muted) uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in speakers"
                            :key="s.id"
                            class="border-b border-(--color-border) last:border-0 hover:bg-(--color-bg) cursor-pointer transition"
                            @click="openModal(s.id)"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img v-if="s.avatar_url" :src="s.avatar_url" :alt="s.name" class="w-8 h-8 rounded-full object-cover shrink-0">
                                    <div v-else class="w-8 h-8 rounded-full bg-(--color-primary) flex items-center justify-center text-white font-bold text-xs shrink-0">
                                        {{ initials(s.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-(--color-text) truncate">{{ s.name }}</p>
                                        <p class="text-xs text-(--color-text-muted) truncate">{{ s.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-(--color-text-muted) hidden md:table-cell">
                                <span v-if="s.city">{{ s.city }}<span v-if="s.state">, {{ s.state }}</span></span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-(--color-text-muted) hidden lg:table-cell">
                                {{ s.phone_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <p class="text-sm text-(--color-text)">{{ s.talks_count }}</p>
                                <p class="text-xs text-(--color-text-muted)">{{ s.talks_approved }} apr.</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'text-xs px-2 py-0.5 rounded-full font-medium',
                                        s.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                    ]"
                                >{{ s.is_active ? 'Ativo' : 'Inativo' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div v-if="lastPage > 1" class="flex justify-center items-center gap-1 flex-wrap">
                <button
                    :disabled="currentPage === 1"
                    class="h-9 w-9 flex items-center justify-center rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:bg-(--color-surface) disabled:opacity-40 disabled:cursor-not-allowed transition"
                    @click="currentPage--"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                </button>

                <template v-for="p in pages" :key="p">
                    <span v-if="p === '…'" class="h-9 w-9 flex items-center justify-center text-sm text-(--color-text-muted)">…</span>
                    <button
                        v-else
                        :class="['h-9 w-9 flex items-center justify-center rounded-lg border text-sm transition', p === currentPage ? 'bg-(--color-primary) border-(--color-primary) text-white font-medium' : 'border-(--color-border) text-(--color-text-muted) hover:bg-(--color-surface)']"
                        @click="currentPage = p"
                    >{{ p }}</button>
                </template>

                <button
                    :disabled="currentPage === lastPage"
                    class="h-9 w-9 flex items-center justify-center rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:bg-(--color-surface) disabled:opacity-40 disabled:cursor-not-allowed transition"
                    @click="currentPage++"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Modal de detalhes -->
    <SpeakerModal
        :show="modalOpen"
        :speaker-id="selectedSpeakerId"
        @close="closeModal"
    />
</template>
