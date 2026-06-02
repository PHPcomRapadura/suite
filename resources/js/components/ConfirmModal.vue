<script setup>
defineProps({
    show:         { type: Boolean, required: true },
    title:        { type: String,  default: 'Confirmar ação' },
    message:      { type: String,  default: 'Tem certeza que deseja continuar?' },
    confirmLabel: { type: String,  default: 'Confirmar' },
    loading:      { type: Boolean, default: false },
    danger:       { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="title"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="emit('cancel')"
                />

                <!-- Modal -->
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl p-6">
                    <h2 class="text-lg font-semibold text-(--color-text) mb-2">{{ title }}</h2>
                    <p class="text-sm text-(--color-text-muted) mb-6">{{ message }}</p>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            :disabled="loading"
                            class="px-4 py-2 text-sm font-medium text-(--color-text) bg-white border border-(--color-border)
                                   rounded-lg hover:bg-gray-50 transition disabled:opacity-60"
                            @click="emit('cancel')"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            :disabled="loading"
                            :class="[
                                'px-4 py-2 text-sm font-medium text-white rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2',
                                danger
                                    ? 'bg-(--color-danger) hover:bg-red-700'
                                    : 'bg-(--color-primary) hover:bg-(--color-primary-hover)',
                            ]"
                            @click="emit('confirm')"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ confirmLabel }}
                        </button>
                    </div>
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
