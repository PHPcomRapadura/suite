<script setup>
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show:    { type: Boolean, required: true },
    eventId: { type: Number, required: true },
    cfp:     { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = ref(false)
const loading   = ref(false)
const errors    = ref({})

const form = reactive({
    opens_at:              '',
    closes_at:             '',
    speaker_guide:         '',
    max_talks_per_speaker: '',
})

function toLocalDatetime(iso) {
    if (!iso) return ''
    const d   = new Date(iso)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

watch(() => props.show, (val) => {
    if (!val) return
    errors.value    = {}
    isEditing.value = !!props.cfp

    if (props.cfp) {
        form.opens_at              = toLocalDatetime(props.cfp.opens_at)
        form.closes_at             = toLocalDatetime(props.cfp.closes_at)
        form.speaker_guide         = props.cfp.speaker_guide ?? ''
        form.max_talks_per_speaker = props.cfp.max_talks_per_speaker ?? ''
    } else {
        form.opens_at              = ''
        form.closes_at             = ''
        form.speaker_guide         = ''
        form.max_talks_per_speaker = ''
    }
})

async function submit() {
    errors.value  = {}
    loading.value = true

    const payload = {
        opens_at:              form.opens_at,
        closes_at:             form.closes_at,
        speaker_guide:         form.speaker_guide || null,
        max_talks_per_speaker: form.max_talks_per_speaker ? Number(form.max_talks_per_speaker) : null,
    }

    try {
        if (isEditing.value) {
            await axios.put(`/admin/api/events/${props.eventId}/cfp`, payload)
        } else {
            await axios.post(`/admin/api/events/${props.eventId}/cfp`, payload)
        }
        emit('saved')
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
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                :aria-label="isEditing ? 'Editar CFP' : 'Configurar CFP'"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-lg bg-(--color-surface) rounded-2xl shadow-xl overflow-hidden">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-(--color-border)">
                        <h2 class="text-lg font-bold text-(--color-text)">
                            {{ isEditing ? 'Editar CFP' : 'Configurar CFP' }}
                        </h2>
                        <button
                            @click="emit('close')"
                            class="text-(--color-text-muted) hover:text-(--color-text) transition"
                            aria-label="Fechar"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <form @submit.prevent="submit" class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">

                        <!-- Abertura -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1">
                                Abertura das submissões <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.opens_at"
                                type="datetime-local"
                                required
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text)
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.opens_at ? 'border-(--color-danger)' : 'border-(--color-border)'"
                            >
                            <p v-if="errors.opens_at" class="mt-1 text-xs text-(--color-danger)">{{ errors.opens_at[0] }}</p>
                        </div>

                        <!-- Encerramento -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1">
                                Encerramento das submissões <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.closes_at"
                                type="datetime-local"
                                required
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text)
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.closes_at ? 'border-(--color-danger)' : 'border-(--color-border)'"
                            >
                            <p v-if="errors.closes_at" class="mt-1 text-xs text-(--color-danger)">{{ errors.closes_at[0] }}</p>
                        </div>

                        <!-- Limite de propostas -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1">
                                Limite de propostas por palestrante
                                <span class="text-(--color-text-muted) font-normal">(opcional)</span>
                            </label>
                            <input
                                v-model="form.max_talks_per_speaker"
                                type="number"
                                min="1"
                                max="10"
                                placeholder="Sem limite"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm bg-(--color-surface) text-(--color-text)
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            >
                            <p v-if="errors.max_talks_per_speaker" class="mt-1 text-xs text-(--color-danger)">{{ errors.max_talks_per_speaker[0] }}</p>
                        </div>

                        <!-- Guia do palestrante -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1">
                                Guia do palestrante
                                <span class="text-(--color-text-muted) font-normal">(opcional, markdown)</span>
                            </label>
                            <textarea
                                v-model="form.speaker_guide"
                                rows="5"
                                placeholder="Instruções para os palestrantes..."
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm bg-(--color-surface) text-(--color-text)
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition resize-none"
                            />
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-(--color-border)">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="px-4 py-2 text-sm text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="submit"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover)
                                   disabled:opacity-60 text-white text-sm font-semibold rounded-lg transition"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ isEditing ? 'Salvar alterações' : 'Configurar CFP' }}
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
