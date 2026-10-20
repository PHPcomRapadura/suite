<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import axios from 'axios'

const entries = ref([])
const loading = ref(true)
const error = ref('')
const autoRefresh = ref(true)
const levelFilter = ref('ALL')
const search = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
let refreshTimer
let searchTimer

async function load() {
    error.value = ''
    try {
        const response = await axios.get('/admin/api/logs', {
            params: { page: page.value, per_page: 50, level: levelFilter.value, search: search.value },
        })
        entries.value = response.data.entries ?? []
        page.value = response.data.current_page ?? 1
        lastPage.value = response.data.last_page ?? 1
        total.value = response.data.total ?? 0
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'Não foi possível carregar os logs.'
    } finally {
        loading.value = false
    }
}

function configureRefresh() {
    clearInterval(refreshTimer)
    if (autoRefresh.value) refreshTimer = setInterval(load, 15000)
}

onMounted(() => {
    load()
    configureRefresh()
})
onUnmounted(() => clearInterval(refreshTimer))

watch(levelFilter, () => {
    page.value = 1
    load()
})

watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        page.value = 1
        load()
    }, 300)
})

const levels = ['ALL', 'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG']

function levelClass(level) {
    return {
        ERROR: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        CRITICAL: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        ALERT: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        EMERGENCY: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        WARNING: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        NOTICE: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        INFO: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        DEBUG: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    }[level] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
}

const filteredEntries = computed(() => entries.value)

function goToPage(nextPage) {
    if (nextPage < 1 || nextPage > lastPage.value || nextPage === page.value) return
    page.value = nextPage
    load()
}
</script>

<template>
    <main class="p-4 sm:p-6 lg:p-8 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-(--color-text)">Logs da aplicação</h1>
                <p class="text-sm text-(--color-text-muted) mt-1">Ocorrências recentes de <code>storage/logs/laravel.log</code></p>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-(--color-text-muted)">
                    <input v-model="autoRefresh" type="checkbox" @change="configureRefresh">
                    Atualizar automaticamente
                </label>
                <button class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-500" @click="load">
                    Atualizar
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Buscar nos logs…"
                class="flex-1 min-w-56 rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-sm text-(--color-text)"
            >
            <select v-model="levelFilter" class="rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-sm text-(--color-text)">
                <option v-for="level in levels" :key="level" :value="level">{{ level === 'ALL' ? 'Todos os níveis' : level }}</option>
            </select>
        </div>

        <div v-if="error" class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>
        <div v-else class="rounded-xl border border-(--color-border) bg-slate-950 overflow-auto shadow-sm">
            <div v-if="loading" class="p-6 text-sm text-slate-400">Carregando logs…</div>
            <div v-else-if="filteredEntries.length === 0" class="p-6 text-sm text-slate-400">Nenhum registro encontrado.</div>
            <div v-else class="divide-y divide-slate-800">
                <details v-for="(entry, index) in filteredEntries" :key="`${entry.timestamp}-${index}`" class="group">
                    <summary class="flex cursor-pointer list-none items-start gap-3 px-4 py-3 hover:bg-slate-900">
                        <span :class="['mt-0.5 rounded px-2 py-0.5 text-[10px] font-semibold', levelClass(entry.level)]">{{ entry.level }}</span>
                        <span class="min-w-44 text-xs text-slate-500">{{ entry.timestamp }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-200">{{ entry.message }}</span>
                        <span v-if="entry.details" class="text-xs text-slate-500 group-open:rotate-90">›</span>
                    </summary>
                    <div v-if="entry.details" class="border-t border-slate-800 bg-slate-900/60 px-4 py-3">
                        <pre class="whitespace-pre-wrap text-xs leading-5 text-slate-300">{{ entry.details }}</pre>
                    </div>
                </details>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-(--color-text-muted)">
            <span>{{ total }} ocorrência{{ total === 1 ? '' : 's' }} encontrada{{ total === 1 ? '' : 's' }}</span>
            <div class="flex items-center gap-2">
                <button class="rounded-lg border border-(--color-border) px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40" :disabled="page <= 1 || loading" @click="goToPage(page - 1)">Anterior</button>
                <span>Página {{ page }} de {{ lastPage }}</span>
                <button class="rounded-lg border border-(--color-border) px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40" :disabled="page >= lastPage || loading" @click="goToPage(page + 1)">Próxima</button>
            </div>
        </div>
    </main>
</template>
