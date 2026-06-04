<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import CfpModal from '@/components/CfpModal.vue'
import TalkReviewModal from '@/components/TalkReviewModal.vue'

const route = useRoute()
const eventId = Number(route.params.id)

// ── CFP config ────────────────────────────────────────────────────────────────
const cfp         = ref(null)
const cfpLoading  = ref(true)
const showCfpModal = ref(false)
const showGuide   = ref(false)

// ── Talks ─────────────────────────────────────────────────────────────────────
const talks        = ref([])
const talksMeta    = ref({ current_page: 1, last_page: 1, per_page: 9, total: 0 })
const talksLoading = ref(false)
const talksFilters = reactive({ status: '', search: '' })
const reviewingTalk  = ref(null)
const showReviewModal = ref(false)

const cfpStatusBadge = {
    aguardando: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
    aberto:     'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    encerrado:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
}

const cfpStatusLabel = {
    aguardando: 'Aguardando',
    aberto:     'Aberto',
    encerrado:  'Encerrado',
}

const talkStatusBadge = {
    submetida:  'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    em_analise: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
    aprovada:   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    rejeitada:  'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    cancelada:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
}

const talkStatusLabel = {
    submetida:  'Submetida',
    em_analise: 'Em análise',
    aprovada:   'Aprovada',
    rejeitada:  'Rejeitada',
    cancelada:  'Cancelada',
}

const levelLabel = {
    iniciante:     'Iniciante',
    intermediario: 'Intermediário',
    avancado:      'Avançado',
}

function formatDatetime(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

async function fetchCfp() {
    cfpLoading.value = true
    try {
        const { data } = await axios.get(`/admin/api/events/${eventId}/cfp`)
        cfp.value = data.data
    } finally {
        cfpLoading.value = false
    }
}

async function fetchTalks(page = 1) {
    talksLoading.value = true
    try {
        const { data } = await axios.get(`/admin/api/events/${eventId}/talks`, {
            params: { ...talksFilters, page },
        })
        talks.value    = data.data
        talksMeta.value = data.meta
    } finally {
        talksLoading.value = false
    }
}

function onCfpSaved() {
    showCfpModal.value = false
    fetchCfp()
}

function openReview(talk) {
    reviewingTalk.value   = talk
    showReviewModal.value = true
}

function onTalkUpdated() {
    showReviewModal.value = false
    reviewingTalk.value   = null
    fetchTalks(talksMeta.value.current_page)
    fetchCfp()
}

let searchTimeout
function onSearchInput() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchTalks(1), 400)
}

onMounted(async () => {
    await fetchCfp()
    await fetchTalks()
})
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
            Voltar para o evento
        </RouterLink>

        <!-- ── Configuração do CFP ─────────────────────────────────────────── -->
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-(--color-text) mb-4">Configuração do CFP</h2>

            <div v-if="cfpLoading" class="flex items-center gap-2 text-sm text-(--color-text-muted) py-4">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                Carregando...
            </div>

            <!-- Não configurado -->
            <div v-else-if="!cfp" class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <p class="text-sm text-(--color-text-muted)">Nenhuma configuração de CFP para este evento.</p>
                <button
                    @click="showCfpModal = true"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover)
                           text-white text-sm font-semibold rounded-lg transition"
                >
                    Configurar agora
                </button>
            </div>

            <!-- Configurado -->
            <div v-else class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', cfpStatusBadge[cfp.status]]">
                                {{ cfpStatusLabel[cfp.status] }}
                            </span>
                        </div>
                        <p class="text-sm text-(--color-text)">
                            <span class="text-(--color-text-muted)">Período:</span>
                            {{ formatDatetime(cfp.opens_at) }} → {{ formatDatetime(cfp.closes_at) }}
                        </p>
                        <p class="text-sm text-(--color-text)">
                            <span class="text-(--color-text-muted)">Limite por palestrante:</span>
                            {{ cfp.max_talks_per_speaker ? cfp.max_talks_per_speaker + ' proposta(s)' : 'Sem limite' }}
                        </p>
                    </div>
                    <button
                        @click="showCfpModal = true"
                        class="shrink-0 text-sm px-3 py-1.5 rounded-lg border border-(--color-border)
                               text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        Editar
                    </button>
                </div>

                <!-- Guia do palestrante -->
                <div v-if="cfp.speaker_guide" class="border-t border-(--color-border) pt-4">
                    <button
                        @click="showGuide = !showGuide"
                        class="flex items-center gap-2 text-sm font-medium text-(--color-text) hover:text-(--color-primary) transition"
                    >
                        <svg
                            :class="['w-4 h-4 transition-transform', showGuide ? 'rotate-180' : '']"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        >
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                        Guia do palestrante
                    </button>
                    <div v-if="showGuide" class="mt-3">
                        <pre class="text-sm text-(--color-text) whitespace-pre-wrap font-sans leading-relaxed">{{ cfp.speaker_guide }}</pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Palestras submetidas ───────────────────────────────────────── -->
        <section>
            <h2 class="text-lg font-semibold text-(--color-text) mb-4">Palestras submetidas</h2>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <select
                    v-model="talksFilters.status"
                    @change="fetchTalks(1)"
                    class="px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                >
                    <option value="">Todos os status</option>
                    <option value="submetida">Submetida</option>
                    <option value="em_analise">Em análise</option>
                    <option value="aprovada">Aprovada</option>
                    <option value="rejeitada">Rejeitada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-text-muted)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input
                        v-model="talksFilters.search"
                        type="search"
                        placeholder="Buscar por título..."
                        @input="onSearchInput"
                        class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                               focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition"
                    >
                </div>
            </div>

            <!-- Loading -->
            <div v-if="talksLoading" class="flex justify-center py-12">
                <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
            </div>

            <!-- Vazio -->
            <div v-else-if="!talks.length" class="text-center py-12 text-(--color-text-muted)">
                <p class="font-medium mb-1">Nenhuma palestra submetida ainda.</p>
            </div>

            <!-- Grid de cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="talk in talks"
                    :key="talk.id"
                    class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 flex flex-col gap-3"
                >
                    <!-- Palestrante -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ (talk.speaker?.user?.name ?? '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-(--color-text) truncate">{{ talk.speaker?.user?.name ?? '—' }}</p>
                            <p v-if="talk.speaker?.company" class="text-xs text-(--color-text-muted) truncate">{{ talk.speaker.company }}</p>
                        </div>
                    </div>

                    <!-- Título -->
                    <p class="text-sm font-semibold text-(--color-text) leading-snug line-clamp-2">{{ talk.title }}</p>

                    <!-- Badges -->
                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            {{ levelLabel[talk.level] ?? talk.level }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            {{ talk.duration }} min
                        </span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full', talkStatusBadge[talk.status]]">
                            {{ talkStatusLabel[talk.status] }}
                        </span>
                    </div>

                    <!-- Avaliar -->
                    <div class="mt-auto pt-3 border-t border-(--color-border)">
                        <button
                            @click="openReview(talk)"
                            class="text-xs px-2 py-1 rounded border border-(--color-border)
                                   text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            Avaliar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <div v-if="talksMeta.last_page > 1" class="flex items-center justify-between mt-8">
                <p class="text-sm text-(--color-text-muted)">
                    {{ talksMeta.total }} palestra{{ talksMeta.total !== 1 ? 's' : '' }} no total
                </p>
                <div class="flex items-center gap-1">
                    <button
                        v-for="page in talksMeta.last_page"
                        :key="page"
                        @click="fetchTalks(page)"
                        :class="['w-9 h-9 rounded-lg text-sm font-medium transition',
                                 page === talksMeta.current_page
                                     ? 'bg-(--color-primary) text-white'
                                     : 'text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700/50']"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </section>
    </div>

    <!-- Modais -->
    <CfpModal
        :show="showCfpModal"
        :event-id="eventId"
        :cfp="cfp"
        @close="showCfpModal = false"
        @saved="onCfpSaved"
    />

    <TalkReviewModal
        :show="showReviewModal"
        :event-id="eventId"
        :talk="reviewingTalk"
        @close="showReviewModal = false; reviewingTalk = null"
        @updated="onTalkUpdated"
    />
</template>
