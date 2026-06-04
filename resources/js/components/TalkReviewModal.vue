<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show:    { type: Boolean, required: true },
    eventId: { type: Number, required: true },
    talk:    { type: Object, default: null },
})

const emit = defineEmits(['close', 'updated'])

const feedback = ref('')
const loading  = ref(false)
const errors   = ref({})

const actions = computed(() => {
    if (!props.talk) return []
    const map = {
        submetida:  [{ label: 'Colocar em análise', status: 'em_analise', variant: 'primary' }, { label: 'Cancelar proposta', status: 'cancelada', variant: 'danger' }],
        em_analise: [{ label: 'Aprovar', status: 'aprovada', variant: 'success' }, { label: 'Rejeitar', status: 'rejeitada', variant: 'danger' }, { label: 'Cancelar proposta', status: 'cancelada', variant: 'neutral' }],
        aprovada:   [{ label: 'Cancelar proposta', status: 'cancelada', variant: 'danger' }],
        rejeitada:  [{ label: 'Reabrir para análise', status: 'em_analise', variant: 'primary' }],
        cancelada:  [],
    }
    return map[props.talk.status] ?? []
})

const requiresFeedback = computed(() =>
    actions.value.some((a) => a.status === 'rejeitada')
)

const variantClass = {
    primary: 'bg-(--color-primary) hover:bg-(--color-primary-hover) text-white',
    success: 'bg-green-600 hover:bg-green-700 text-white',
    danger:  'bg-(--color-danger) hover:bg-red-700 text-white',
    neutral: 'border border-(--color-border) text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700',
}

watch(() => props.show, (val) => {
    if (val) {
        feedback.value = props.talk?.feedback ?? ''
        errors.value   = {}
    }
})

async function apply(status) {
    if (status === 'rejeitada' && !feedback.value.trim()) {
        errors.value = { feedback: ['O feedback é obrigatório ao rejeitar uma palestra.'] }
        return
    }
    errors.value  = {}
    loading.value = true
    try {
        await axios.patch(`/admin/api/events/${props.eventId}/talks/${props.talk.id}/status`, {
            status,
            feedback: feedback.value || null,
        })
        emit('updated')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {}
        }
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show && talk"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Avaliar palestra"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-2xl bg-(--color-surface) rounded-2xl shadow-xl flex flex-col max-h-[90vh]">

                    <!-- Header -->
                    <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-(--color-border)">
                        <div class="flex-1 min-w-0 pr-4">
                            <p class="text-xs text-(--color-text-muted) mb-1">Palestra</p>
                            <h2 class="text-lg font-bold text-(--color-text) leading-snug">{{ talk.title }}</h2>
                        </div>
                        <button
                            @click="emit('close')"
                            class="shrink-0 text-(--color-text-muted) hover:text-(--color-text) transition"
                            aria-label="Fechar"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="overflow-y-auto px-6 py-5 space-y-5 flex-1">

                        <!-- Resumo -->
                        <div>
                            <p class="text-xs font-medium text-(--color-text-muted) uppercase tracking-wide mb-2">Resumo</p>
                            <p class="text-sm text-(--color-text) leading-relaxed whitespace-pre-wrap">{{ talk.abstract }}</p>
                        </div>

                        <!-- Badges -->
                        <div class="flex flex-wrap gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ { iniciante: 'Iniciante', intermediario: 'Intermediário', avancado: 'Avançado' }[talk.level] ?? talk.level }}
                            </span>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ talk.duration }} min
                            </span>
                        </div>

                        <!-- Palestrante -->
                        <div class="border-t border-(--color-border) pt-4">
                            <p class="text-xs font-medium text-(--color-text-muted) uppercase tracking-wide mb-3">Palestrante</p>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-sm font-bold shrink-0">
                                    {{ (talk.speaker?.user?.name ?? '?').charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-(--color-text)">{{ talk.speaker?.user?.name ?? '—' }}</p>
                                    <p v-if="talk.speaker?.company" class="text-sm text-(--color-text-muted)">{{ talk.speaker.company }}</p>
                                    <p v-if="talk.speaker?.user?.email" class="text-xs text-(--color-text-muted) mt-0.5">{{ talk.speaker.user.email }}</p>
                                </div>
                            </div>

                            <!-- Bio -->
                            <p v-if="talk.speaker?.bio" class="mt-3 text-sm text-(--color-text-muted) leading-relaxed line-clamp-4">
                                {{ talk.speaker.bio }}
                            </p>

                            <!-- Links -->
                            <div v-if="talk.speaker?.github || talk.speaker?.twitter || talk.speaker?.linkedin || talk.speaker?.website" class="flex flex-wrap gap-3 mt-3">
                                <a v-if="talk.speaker?.github"   :href="`https://github.com/${talk.speaker.github}`"       target="_blank" rel="noopener" class="text-xs text-(--color-primary) hover:underline">GitHub</a>
                                <a v-if="talk.speaker?.twitter"  :href="`https://twitter.com/${talk.speaker.twitter}`"      target="_blank" rel="noopener" class="text-xs text-(--color-primary) hover:underline">Twitter</a>
                                <a v-if="talk.speaker?.linkedin" :href="talk.speaker.linkedin"                             target="_blank" rel="noopener" class="text-xs text-(--color-primary) hover:underline">LinkedIn</a>
                                <a v-if="talk.speaker?.website"  :href="talk.speaker.website"                              target="_blank" rel="noopener" class="text-xs text-(--color-primary) hover:underline">Website</a>
                            </div>
                        </div>

                        <!-- Feedback -->
                        <div v-if="actions.length" class="border-t border-(--color-border) pt-4">
                            <label class="block text-sm font-medium text-(--color-text) mb-1">
                                Feedback
                                <span v-if="requiresFeedback" class="text-(--color-text-muted) font-normal">(obrigatório ao rejeitar)</span>
                            </label>
                            <textarea
                                v-model="feedback"
                                rows="3"
                                placeholder="Comentário para o palestrante..."
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text)
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition resize-none"
                                :class="errors.feedback ? 'border-(--color-danger)' : 'border-(--color-border)'"
                            />
                            <p v-if="errors.feedback" class="mt-1 text-xs text-(--color-danger)">{{ errors.feedback[0] }}</p>
                        </div>

                        <!-- Sem ações -->
                        <div v-else class="border-t border-(--color-border) pt-4">
                            <p class="text-sm text-(--color-text-muted)">Esta palestra está encerrada e não pode ser alterada.</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div v-if="actions.length" class="flex flex-wrap items-center justify-end gap-2 px-6 py-4 border-t border-(--color-border)">
                        <button
                            v-for="action in actions"
                            :key="action.status"
                            @click="apply(action.status)"
                            :disabled="loading"
                            :class="['inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition disabled:opacity-60', variantClass[action.variant]]"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ action.label }}
                        </button>
                    </div>
                    <div v-else class="flex items-center justify-end gap-3 px-6 py-4 border-t border-(--color-border)">
                        <button
                            @click="emit('close')"
                            class="px-4 py-2 text-sm text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
