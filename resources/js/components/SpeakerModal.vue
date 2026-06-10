<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show:      { type: Boolean, required: true },
    speakerId: { type: Number, default: null },
})

const emit = defineEmits(['close'])

const speaker = ref(null)
const loading = ref(false)

const statusLabel = {
    submetida:  'Submetida',
    em_analise: 'Em análise',
    aprovada:   'Aprovada',
    rejeitada:  'Rejeitada',
    cancelada:  'Cancelada',
}

const statusClass = {
    submetida:  'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    em_analise: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
    aprovada:   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    rejeitada:  'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    cancelada:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
}

const levelLabel = { iniciante: 'Iniciante', intermediario: 'Intermediário', avancado: 'Avançado' }

watch(() => props.speakerId, async (id) => {
    if (!id) { speaker.value = null; return }
    loading.value = true
    try {
        const res = await axios.get(`/admin/api/speakers/${id}`)
        speaker.value = res.data
    } finally {
        loading.value = false
    }
})

function initials(name) {
    if (!name) return '?'
    return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function formatDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDatetime(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
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
                aria-label="Detalhes do palestrante"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-lg bg-(--color-surface) rounded-2xl shadow-xl flex flex-col max-h-[90vh]">

                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-20">
                        <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                    </div>

                    <template v-else-if="speaker">
                        <!-- Header -->
                        <div class="flex items-start gap-4 p-6 border-b border-(--color-border) shrink-0">
                            <img
                                v-if="speaker.avatar_url"
                                :src="speaker.avatar_url"
                                :alt="speaker.name"
                                class="w-16 h-16 rounded-full object-cover shrink-0"
                            >
                            <div
                                v-else
                                class="w-16 h-16 rounded-full bg-(--color-primary) flex items-center justify-center text-white font-bold text-xl shrink-0"
                            >
                                {{ initials(speaker.name) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-lg font-semibold text-(--color-text) leading-tight">{{ speaker.name }}</h2>
                                    <span
                                        :class="[
                                            'text-xs px-2 py-0.5 rounded-full font-medium shrink-0',
                                            speaker.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                        ]"
                                    >{{ speaker.is_active ? 'Ativo' : 'Inativo' }}</span>
                                </div>
                                <p class="text-sm text-(--color-text-muted) mt-0.5">{{ speaker.email }}</p>
                                <p v-if="speaker.company || speaker.city" class="text-sm text-(--color-text-muted) mt-0.5">
                                    <span v-if="speaker.company">{{ speaker.company }}</span>
                                    <span v-if="speaker.company && speaker.city"> · </span>
                                    <span v-if="speaker.city">{{ speaker.city }}<span v-if="speaker.state">, {{ speaker.state }}</span></span>
                                </p>
                            </div>

                            <button
                                class="text-(--color-text-muted) hover:text-(--color-text) transition shrink-0"
                                aria-label="Fechar"
                                @click="emit('close')"
                            >
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Corpo scrollável -->
                        <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-5">

                            <!-- Bio -->
                            <div v-if="speaker.bio">
                                <p class="text-xs font-medium text-(--color-text-muted) uppercase tracking-wide mb-1">Bio</p>
                                <p class="text-sm text-(--color-text) leading-relaxed">{{ speaker.bio }}</p>
                            </div>

                            <!-- Contato e redes -->
                            <div class="flex flex-col gap-1.5">
                                <p class="text-xs font-medium text-(--color-text-muted) uppercase tracking-wide mb-0.5">Contato</p>

                                <a v-if="speaker.phone_number" :href="`tel:${speaker.phone_number}`"
                                   class="inline-flex items-center gap-2 text-sm text-(--color-text) hover:text-(--color-primary) transition">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.27-.88a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    {{ speaker.phone_number }}
                                </a>

                                <a v-if="speaker.website" :href="speaker.website" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 text-sm text-(--color-text) hover:text-(--color-primary) transition truncate">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    {{ speaker.website }}
                                </a>

                                <div class="flex flex-wrap gap-3 mt-1">
                                    <a v-if="speaker.twitter" :href="`https://twitter.com/${speaker.twitter}`" target="_blank" rel="noopener"
                                       class="text-sm text-(--color-text-muted) hover:text-(--color-primary) transition">
                                        𝕏 @{{ speaker.twitter }}
                                    </a>
                                    <a v-if="speaker.github" :href="`https://github.com/${speaker.github}`" target="_blank" rel="noopener"
                                       class="text-sm text-(--color-text-muted) hover:text-(--color-primary) transition">
                                        GitHub /{{ speaker.github }}
                                    </a>
                                    <a v-if="speaker.linkedin" :href="speaker.linkedin" target="_blank" rel="noopener"
                                       class="text-sm text-(--color-text-muted) hover:text-(--color-primary) transition">
                                        LinkedIn
                                    </a>
                                </div>
                            </div>

                            <!-- Último acesso -->
                            <p v-if="speaker.last_login_at" class="text-xs text-(--color-text-muted)">
                                Último acesso: {{ formatDatetime(speaker.last_login_at) }}
                            </p>

                            <!-- Palestras -->
                            <div>
                                <p class="text-xs font-medium text-(--color-text-muted) uppercase tracking-wide mb-2">
                                    Palestras ({{ speaker.talks_count }} submetida{{ speaker.talks_count !== 1 ? 's' : '' }} · {{ speaker.talks_approved }} aprovada{{ speaker.talks_approved !== 1 ? 's' : '' }})
                                </p>

                                <div v-if="speaker.talks.length === 0" class="text-sm text-(--color-text-muted)">
                                    Nenhuma palestra submetida.
                                </div>

                                <ul v-else class="flex flex-col gap-3">
                                    <li
                                        v-for="talk in speaker.talks"
                                        :key="talk.id"
                                        class="bg-(--color-bg) rounded-lg p-3 flex flex-col gap-1"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-medium text-(--color-text) leading-tight">{{ talk.title }}</p>
                                            <span :class="['text-xs px-2 py-0.5 rounded-full shrink-0 font-medium', statusClass[talk.status] ?? statusClass.submetida]">
                                                {{ statusLabel[talk.status] ?? talk.status }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-(--color-text-muted)">
                                            {{ talk.event }}
                                            <span v-if="talk.level"> · {{ levelLabel[talk.level] ?? talk.level }}</span>
                                            <span v-if="talk.duration"> · {{ talk.duration }} min</span>
                                        </p>
                                        <p v-if="talk.submitted_at" class="text-xs text-(--color-text-muted)">
                                            Enviada em {{ formatDate(talk.submitted_at) }}
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-(--color-border) flex justify-end shrink-0">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-(--color-text) border border-(--color-border) rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                @click="emit('close')"
                            >
                                Fechar
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from,
.modal-leave-to    { opacity: 0; }
</style>
