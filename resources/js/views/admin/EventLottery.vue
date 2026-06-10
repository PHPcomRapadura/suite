<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import confetti from 'canvas-confetti'
import { useAuth } from '@/composables/useAuth'
import ConfirmModal from '@/components/ConfirmModal.vue'

const route  = useRoute()
const { user } = useAuth()

const eventId   = route.params.id
const eventName = ref('')
const winners   = ref([])
const stats     = ref({ total_pool: 0, total_drawn: 0, remaining: 0 })
const loading   = ref(true)
const pageError = ref(null)

// Animation state
const drawing    = ref(false)
const showOverlay = ref(false)
const countdown  = ref(3)
const drawResult = ref(null)
const drawError  = ref(null)
const revealed   = ref(false)

// Reset modal
const showResetModal = ref(false)
const resetting      = ref(false)

let autoCloseTimer = null

const isAdmin   = computed(() => user.value?.role === 'admin')
const poolEmpty = computed(() => stats.value.remaining === 0)

const resetMessage = computed(() => {
    const n = winners.value.length
    if (n === 0) return 'Deseja realmente reiniciar o sorteio? Esta ação não pode ser desfeita.'
    return `Deseja realmente reiniciar o sorteio? Os ${n} participante${n !== 1 ? 's' : ''} já sorteados voltarão ao pool. Esta ação não pode ser desfeita.`
})

async function fetchData() {
    try {
        const res = await axios.get(`/admin/api/events/${eventId}/lottery`)
        winners.value = res.data.winners
        stats.value   = res.data.stats
    } catch {
        pageError.value = 'Erro ao carregar dados do sorteio.'
    } finally {
        loading.value = false
    }
}

async function fetchEventName() {
    try {
        const res = await axios.get(`/admin/api/events/${eventId}`)
        eventName.value = res.data.name
    } catch {}
}

async function doDraw() {
    if (drawing.value) return
    drawing.value  = true
    drawResult.value = null
    drawError.value  = null
    revealed.value   = false
    showOverlay.value = true
    countdown.value  = 3

    // API call in parallel with animation
    const drawPromise = axios.post(`/admin/api/events/${eventId}/lottery/draw`)

    await delay(1000)
    countdown.value = 2
    await delay(1000)
    countdown.value = 1
    await delay(1000)

    try {
        const res = await drawPromise
        drawResult.value = res.data.winner
    } catch (e) {
        drawError.value = e.response?.data?.errors?.draw?.[0]
            ?? e.response?.data?.message
            ?? 'Erro ao sortear. Tente novamente.'
    }

    revealed.value = true

    if (drawResult.value) {
        confetti({
            particleCount: 120,
            spread: 80,
            origin: { y: 0.4 },
            colors: ['#025c98', '#f59e0b', '#16a34a', '#ffffff'],
        })
    }

    autoCloseTimer = setTimeout(() => closeOverlay(), 4000)
}

function closeOverlay() {
    if (autoCloseTimer) { clearTimeout(autoCloseTimer); autoCloseTimer = null }
    showOverlay.value = false
    drawing.value     = false
    pageError.value   = drawError.value ?? null
    fetchData()
}

async function doReset() {
    resetting.value = true
    try {
        await axios.delete(`/admin/api/events/${eventId}/lottery`)
        showResetModal.value = false
        await fetchData()
    } catch {
        showResetModal.value = false
    } finally {
        resetting.value = false
    }
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
}

onMounted(() => {
    fetchData()
    fetchEventName()
})
</script>

<template>
    <div class="flex flex-col gap-6 p-5">

        <!-- Voltar -->
        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: eventId } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition w-fit"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            {{ eventName || 'Voltar para o evento' }}
        </RouterLink>

        <!-- Erro de página -->
        <div v-if="pageError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-700 dark:text-red-400">
            {{ pageError }}
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>

            <!-- Header com stats -->
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5">
                <div class="flex items-center gap-2 mb-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                        <polyline points="20 12 20 22 4 22 4 12"/>
                        <rect x="2" y="7" width="20" height="5"/>
                        <line x1="12" y1="22" x2="12" y2="7"/>
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                    </svg>
                    <h1 class="text-lg font-semibold text-(--color-text)">Sorteio</h1>
                </div>
                <p class="text-sm text-(--color-text-muted)">
                    {{ stats.total_pool }} com check-in ·
                    {{ stats.total_drawn }} sorteado{{ stats.total_drawn !== 1 ? 's' : '' }} ·
                    {{ stats.remaining }} disponíve{{ stats.remaining !== 1 ? 'is' : 'l' }}
                </p>
            </div>

            <!-- Botão Arrocha / Pool esgotado -->
            <div class="flex justify-center">
                <template v-if="isAdmin">
                    <button
                        v-if="!poolEmpty"
                        type="button"
                        :disabled="drawing"
                        class="px-10 py-4 text-lg font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="doDraw"
                    >
                        Arrocha
                    </button>
                    <p v-else class="text-sm text-(--color-text-muted) text-center py-4">
                        Todos os participantes já foram sorteados.
                    </p>
                </template>
            </div>

            <!-- Lista de sorteados -->
            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-(--color-border)">
                    <h2 class="font-semibold text-(--color-text)">Sorteados</h2>
                    <button
                        v-if="isAdmin && winners.length > 0"
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-(--color-danger) hover:opacity-80 transition"
                        @click="showResetModal = true"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 .49-4.95"/>
                        </svg>
                        Resetar
                    </button>
                </div>

                <div v-if="winners.length === 0" class="px-5 py-8 text-center text-sm text-(--color-text-muted)">
                    Nenhum participante sorteado ainda.
                </div>

                <ul v-else class="divide-y divide-(--color-border)">
                    <li
                        v-for="w in winners"
                        :key="w.position"
                        class="flex items-center gap-4 px-5 py-3"
                    >
                        <span class="w-8 text-sm font-semibold text-(--color-text-muted) shrink-0">{{ w.position }}°</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-(--color-text) truncate">{{ w.participant.full_name }}</p>
                            <p class="text-xs text-(--color-text-muted) truncate">{{ w.participant.email_obfuscated }}</p>
                        </div>
                    </li>
                </ul>
            </div>

        </template>

        <!-- Overlay de animação -->
        <Teleport to="body">
            <div
                v-if="showOverlay"
                class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/80"
            >
                <!-- Contagem regressiva -->
                <template v-if="!revealed">
                    <img
                        :src="'/images/favicon.png'"
                        alt=""
                        class="w-20 h-20 mb-8"
                        style="animation: spin-full 1.2s linear infinite"
                        aria-hidden="true"
                    >
                    <span class="text-9xl font-semibold text-white select-none">{{ countdown }}</span>
                </template>

                <!-- Tela de revelação -->
                <template v-else>
                    <template v-if="drawResult">
                        <p class="text-5xl mb-6 select-none" aria-hidden="true">🎉</p>
                        <p class="text-3xl font-bold text-white text-center px-6 mb-2">{{ drawResult.participant.full_name }}</p>
                        <p class="text-sm text-white/70 text-center mb-8">{{ drawResult.participant.email_obfuscated }}</p>
                    </template>
                    <template v-else>
                        <p class="text-lg font-semibold text-white text-center px-6 mb-6">{{ drawError }}</p>
                    </template>

                    <button
                        type="button"
                        class="px-8 py-3 text-sm font-semibold text-white border border-white/40 rounded-xl hover:bg-white/10 transition"
                        @click="closeOverlay"
                    >
                        Continuar
                    </button>
                </template>
            </div>
        </Teleport>

        <!-- Modal de confirmação reset -->
        <ConfirmModal
            :show="showResetModal"
            title="Reiniciar sorteio"
            :message="resetMessage"
            confirm-label="Reiniciar"
            :loading="resetting"
            :danger="true"
            @confirm="doReset"
            @cancel="showResetModal = false"
        />

    </div>
</template>
