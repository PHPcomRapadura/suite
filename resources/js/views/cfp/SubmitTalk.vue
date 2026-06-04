<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route  = useRoute()

const eventId = route.params.eventId

const event      = ref(null)
const myTalks    = ref([])
const profile    = ref(null)
const user       = ref(null)
const loading    = ref(true)
const submitting = ref(false)
const errors     = ref({})
const success    = ref('')
const guideOpen  = ref(false)
const editingId  = ref(null)

const form = ref({ title: '', abstract: '', duration: '50', level: 'intermediario' })

const cfp = computed(() => event.value?.cfp ?? null)

const cfpStatus = computed(() => cfp.value?.status ?? null)

const limitReached = computed(() => {
    if (!cfp.value?.max_talks_per_speaker) return false
    const active = myTalks.value.filter(t => t.status !== 'cancelada')
    return active.length >= cfp.value.max_talks_per_speaker
})

const canEdit = (talk) => ['submetida', 'em_analise'].includes(talk.status)

onMounted(async () => {
    const meRes = await axios.get('/cfp/api/me').catch(() => null)
    user.value = meRes?.data?.user ?? null

    if (!user.value) {
        router.push({ name: 'cfp.login', query: { redirect: `/cfp/submit/${eventId}` } })
        return
    }

    if (user.value.role !== 'palestrante') {
        loading.value = false
        return
    }

    await Promise.allSettled([fetchEvent(), fetchProfile(), fetchMyTalks()])

    // Redireciona para perfil se bio não preenchida
    if (!profile.value?.bio) {
        router.push({ name: 'cfp.profile', query: { redirect: `/cfp/submit/${eventId}` } })
        return
    }

    loading.value = false
})

async function fetchEvent() {
    try {
        const { data } = await axios.get(`/cfp/api/events/${eventId}`)
        event.value = data.data
    } catch {
        event.value = null
    }
}

async function fetchProfile() {
    try {
        const { data } = await axios.get('/cfp/api/speaker/profile')
        profile.value = data.data
    } catch {
        profile.value = null
    }
}

async function fetchMyTalks() {
    try {
        const { data } = await axios.get(`/cfp/api/events/${eventId}/my-talks`)
        myTalks.value = data.data
    } catch {
        myTalks.value = []
    }
}

function startEdit(talk) {
    editingId.value = talk.id
    form.value = {
        title:    talk.title,
        abstract: talk.abstract,
        duration: talk.duration,
        level:    talk.level,
    }
    document.getElementById('form-section')?.scrollIntoView({ behavior: 'smooth' })
}

function cancelEdit() {
    editingId.value = null
    form.value = { title: '', abstract: '', duration: '50', level: 'intermediario' }
    errors.value = {}
}

async function handleSubmit() {
    submitting.value = true
    errors.value = {}
    success.value = ''

    try {
        let res
        if (editingId.value) {
            res = await axios.put(`/cfp/api/talks/${editingId.value}`, form.value)
            myTalks.value = myTalks.value.map(t => t.id === editingId.value ? res.data : t)
            success.value = 'Proposta atualizada com sucesso!'
            editingId.value = null
        } else {
            res = await axios.post(`/cfp/api/events/${eventId}/talks`, form.value)
            myTalks.value.unshift(res.data)
            success.value = 'Proposta enviada com sucesso!'
        }
        form.value = { title: '', abstract: '', duration: '50', level: 'intermediario' }
    } catch (e) {
        if (e.response?.status === 422) {
            if (e.response.data.errors) {
                errors.value = e.response.data.errors
            } else {
                errors.value = { _general: e.response.data.message }
            }
        }
    } finally {
        submitting.value = false
    }
}

function formatDate(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function formatDateTime(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}

const statusLabel = { submetida: 'Submetida', em_analise: 'Em análise', aprovada: 'Aprovada', rejeitada: 'Rejeitada', cancelada: 'Cancelada' }
const statusClass = {
    submetida:  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    em_analise: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    aprovada:   'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    rejeitada:  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    cancelada:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
}
const levelLabel    = { iniciante: 'Iniciante', intermediario: 'Intermediário', avancado: 'Avançado' }
const durationLabel = { '25': '25 min', '50': '50 min' }
</script>

<template>
    <div class="min-h-screen bg-(--color-bg)">

        <!-- Header simples -->
        <header class="bg-(--color-surface) border-b border-(--color-border)">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 h-14 flex items-center gap-3">
                <RouterLink :to="{ name: 'cfp.home' }" class="text-(--color-text-muted) hover:text-(--color-text) transition flex items-center gap-1.5 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar para o CFP
                </RouterLink>
                <span class="text-(--color-border)">·</span>
                <RouterLink :to="{ name: 'cfp.profile' }" class="text-(--color-text-muted) hover:text-(--color-text) transition text-sm">
                    Meu perfil
                </RouterLink>
            </div>
        </header>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-24">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>

            <!-- Aviso: não é palestrante -->
            <div v-if="user && user.role !== 'palestrante'" class="max-w-3xl mx-auto px-4 sm:px-6 py-12 text-center">
                <p class="text-(--color-text-muted)">Use uma conta de palestrante para submeter propostas.</p>
                <RouterLink :to="{ name: 'cfp.login' }" class="mt-4 inline-block text-sm text-(--color-primary) underline">Entrar com outra conta</RouterLink>
            </div>

            <main v-else class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-8">

                <!-- Cabeçalho do evento -->
                <div v-if="event" class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6">
                    <h1 class="text-xl font-bold text-(--color-text) mb-1">
                        {{ event.name }}<span v-if="event.edition" class="text-(--color-text-muted) font-normal text-base"> · Edição {{ event.edition }}</span>
                    </h1>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-(--color-text-muted) mt-2">
                        <span>📅 {{ formatDate(event.starts_at) }}</span>
                        <span v-if="!event.is_online && event.location">📍 {{ event.location }}</span>
                        <span v-if="event.is_online">🌐 Online</span>
                    </div>
                    <div class="mt-3 text-sm" v-if="cfp">
                        <span v-if="cfpStatus === 'aberto'" class="text-green-700 dark:text-green-400">
                            CFP aberto até {{ formatDate(cfp.closes_at) }}
                        </span>
                        <span v-else-if="cfpStatus === 'aguardando'" class="text-yellow-700 dark:text-yellow-400">
                            CFP abre em {{ formatDate(cfp.opens_at) }}
                        </span>
                        <span v-else class="text-red-600 dark:text-red-400">
                            CFP encerrado
                        </span>
                    </div>

                    <!-- Evento sem CFP -->
                    <p v-if="!event" class="text-(--color-text-muted) text-sm mt-2">Este evento não possui um CFP configurado.</p>
                </div>

                <div v-if="!event" class="text-center py-12 text-(--color-text-muted)">
                    Evento não encontrado.
                </div>

                <template v-if="event && cfp">

                    <!-- Guia do palestrante -->
                    <div v-if="cfp.speaker_guide" class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden">
                        <button
                            @click="guideOpen = !guideOpen"
                            class="w-full flex items-center justify-between px-6 py-4 text-left text-sm font-medium text-(--color-text) hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                        >
                            <span>📖 Guia do palestrante</span>
                            <svg class="w-4 h-4 text-(--color-text-muted) transition-transform" :class="guideOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-show="guideOpen" class="px-6 pb-5 text-sm text-(--color-text) leading-relaxed whitespace-pre-wrap border-t border-(--color-border) pt-4">
                            {{ cfp.speaker_guide }}
                        </div>
                    </div>

                    <!-- Minhas propostas -->
                    <section v-if="myTalks.length > 0">
                        <h2 class="text-base font-semibold text-(--color-text) mb-3">Suas propostas</h2>
                        <div class="space-y-3">
                            <div
                                v-for="talk in myTalks"
                                :key="talk.id"
                                class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 flex items-start justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-(--color-text) truncate">{{ talk.title }}</p>
                                    <p class="text-xs text-(--color-text-muted) mt-0.5">
                                        {{ durationLabel[talk.duration] }} · {{ levelLabel[talk.level] }} · Enviada {{ formatDateTime(talk.submitted_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="statusClass[talk.status]">
                                        {{ statusLabel[talk.status] }}
                                    </span>
                                    <button
                                        v-if="canEdit(talk)"
                                        @click="startEdit(talk)"
                                        class="text-xs px-2.5 py-1 rounded border border-(--color-border) text-(--color-text-muted) hover:text-(--color-text) transition"
                                    >
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Banner limite atingido -->
                    <div
                        v-if="limitReached && cfpStatus === 'aberto'"
                        class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 rounded-lg"
                        role="alert"
                    >
                        ⚠️ Você atingiu o limite de {{ cfp.max_talks_per_speaker }} proposta(s) para este evento.
                    </div>

                    <!-- Formulário -->
                    <section
                        v-if="cfpStatus === 'aberto' && (!limitReached || editingId)"
                        id="form-section"
                    >
                        <h2 class="text-base font-semibold text-(--color-text) mb-4">
                            {{ editingId ? 'Editar proposta' : 'Nova proposta' }}
                        </h2>

                        <!-- Sucesso -->
                        <div
                            v-if="success"
                            role="status"
                            class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm px-4 py-3 rounded-lg mb-4"
                        >
                            {{ success }}
                        </div>

                        <!-- Erro geral -->
                        <div
                            v-if="errors._general"
                            role="alert"
                            class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg mb-4"
                        >
                            {{ errors._general }}
                        </div>

                        <form @submit.prevent="handleSubmit" class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 space-y-5" novalidate>

                            <!-- Título -->
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Título <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    maxlength="255"
                                    :disabled="submitting"
                                    class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                    :class="errors.title ? 'border-red-400' : 'border-(--color-border)'"
                                    placeholder="Título da sua palestra"
                                >
                                <p v-if="errors.title" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ errors.title[0] }}</p>
                            </div>

                            <!-- Resumo -->
                            <div>
                                <div class="flex justify-between mb-1.5">
                                    <label class="text-sm font-medium text-(--color-text)">
                                        Resumo <span class="text-red-500" aria-hidden="true">*</span>
                                    </label>
                                    <span class="text-xs text-(--color-text-muted)">{{ form.abstract.length }}/2000 (mín. 100)</span>
                                </div>
                                <textarea
                                    v-model="form.abstract"
                                    rows="6"
                                    maxlength="2000"
                                    :disabled="submitting"
                                    class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text) resize-y"
                                    :class="errors.abstract ? 'border-red-400' : 'border-(--color-border)'"
                                    placeholder="Descreva o conteúdo da sua palestra..."
                                ></textarea>
                                <p v-if="errors.abstract" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ errors.abstract[0] }}</p>
                            </div>

                            <!-- Duração e Nível -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                                <!-- Duração -->
                                <div>
                                    <p class="text-sm font-medium text-(--color-text) mb-2">
                                        Duração <span class="text-red-500" aria-hidden="true">*</span>
                                    </p>
                                    <div class="space-y-2">
                                        <label
                                            v-for="opt in [{ value: '25', label: '25 min', desc: 'Lightning Talk' }, { value: '50', label: '50 min', desc: 'Palestra' }]"
                                            :key="opt.value"
                                            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                            :class="form.duration === opt.value
                                                ? 'border-(--color-primary) bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-(--color-border) hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.duration"
                                                :value="opt.value"
                                                :disabled="submitting"
                                                class="text-(--color-primary) focus:ring-(--color-primary)"
                                            >
                                            <div>
                                                <span class="text-sm font-medium text-(--color-text)">{{ opt.label }}</span>
                                                <span class="text-xs text-(--color-text-muted) ml-1">{{ opt.desc }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Nível -->
                                <div>
                                    <p class="text-sm font-medium text-(--color-text) mb-2">
                                        Nível <span class="text-red-500" aria-hidden="true">*</span>
                                    </p>
                                    <div class="space-y-2">
                                        <label
                                            v-for="opt in [{ value: 'iniciante', label: 'Iniciante' }, { value: 'intermediario', label: 'Intermediário' }, { value: 'avancado', label: 'Avançado' }]"
                                            :key="opt.value"
                                            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                            :class="form.level === opt.value
                                                ? 'border-(--color-primary) bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-(--color-border) hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.level"
                                                :value="opt.value"
                                                :disabled="submitting"
                                                class="text-(--color-primary) focus:ring-(--color-primary)"
                                            >
                                            <span class="text-sm font-medium text-(--color-text)">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="px-5 py-2.5 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <svg v-if="submitting" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                    </svg>
                                    {{ submitting ? 'Enviando...' : (editingId ? 'Atualizar proposta' : 'Enviar proposta') }}
                                </button>
                                <button
                                    v-if="editingId"
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-5 py-2.5 border border-(--color-border) text-(--color-text-muted) text-sm font-medium rounded-lg hover:text-(--color-text) transition"
                                >
                                    Cancelar
                                </button>
                            </div>

                        </form>
                    </section>

                    <!-- CFP aguardando -->
                    <div
                        v-if="cfpStatus === 'aguardando'"
                        class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-5 py-4 rounded-xl"
                        role="status"
                    >
                        Este CFP ainda não está aberto para submissões. As submissões abrem em {{ formatDate(cfp.opens_at) }}.
                    </div>

                    <!-- CFP encerrado -->
                    <div
                        v-if="cfpStatus === 'encerrado'"
                        class="bg-gray-50 dark:bg-gray-800/30 border border-(--color-border) text-(--color-text-muted) text-sm px-5 py-4 rounded-xl"
                        role="status"
                    >
                        O período de submissão de propostas foi encerrado.
                    </div>

                </template>
            </main>
        </template>
    </div>
</template>
