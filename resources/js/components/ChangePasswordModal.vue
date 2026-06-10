<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: { type: Boolean, required: true },
})

const emit = defineEmits(['close'])

const form = ref({ current_password: '', password: '', password_confirmation: '' })
const errors = ref({})
const loading = ref(false)
const success = ref(false)
const showCurrent = ref(false)
const showNew = ref(false)

watch(() => props.show, (val) => {
    if (val) {
        form.value = { current_password: '', password: '', password_confirmation: '' }
        errors.value = {}
        success.value = false
        showCurrent.value = false
        showNew.value = false
    }
})

async function submit() {
    loading.value = true
    errors.value = {}

    try {
        await axios.put('/admin/api/account/password', form.value)
        success.value = true
        setTimeout(() => emit('close'), 1400)
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {}
        } else {
            errors.value = { current_password: ['Não foi possível alterar a senha. Tente novamente.'] }
        }
    } finally {
        loading.value = false
    }
}

const inputClass = (hasError) => [
    'w-full px-3.5 py-2.5 pr-10 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm',
    'focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
    hasError ? 'border-(--color-danger)' : 'border-(--color-border)',
]
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="change-password-title"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-md bg-(--color-surface) rounded-2xl shadow-xl p-6">

                    <h2 id="change-password-title" class="text-lg font-semibold text-(--color-text) mb-6">
                        Alterar senha
                    </h2>

                    <!-- Sucesso -->
                    <div
                        v-if="success"
                        class="flex items-center gap-3 rounded-lg bg-(--color-success)/10 text-(--color-success) px-4 py-3 text-sm"
                        role="status"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        Senha alterada com sucesso.
                    </div>

                    <form v-else @submit.prevent="submit" class="space-y-4" novalidate>

                        <!-- Senha atual -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Senha atual <span class="text-(--color-danger)">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.current_password"
                                    :type="showCurrent ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    :disabled="loading"
                                    :class="inputClass(errors.current_password)"
                                >
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition"
                                    :aria-label="showCurrent ? 'Ocultar senha' : 'Mostrar senha'"
                                    @click="showCurrent = !showCurrent"
                                >
                                    <svg v-if="showCurrent" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="errors.current_password" class="mt-1 text-xs text-(--color-danger)">{{ errors.current_password[0] }}</p>
                        </div>

                        <!-- Nova senha -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Nova senha <span class="text-(--color-danger)">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showNew ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    :disabled="loading"
                                    :class="inputClass(errors.password)"
                                >
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition"
                                    :aria-label="showNew ? 'Ocultar senha' : 'Mostrar senha'"
                                    @click="showNew = !showNew"
                                >
                                    <svg v-if="showNew" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="errors.password" class="mt-1 text-xs text-(--color-danger)">{{ errors.password[0] }}</p>
                        </div>

                        <!-- Confirmar nova senha -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Confirmar nova senha <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.password_confirmation"
                                :type="showNew ? 'text' : 'password'"
                                autocomplete="new-password"
                                :disabled="loading"
                                :class="inputClass(false)"
                            >
                        </div>

                        <!-- Ações -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-(--color-text) bg-(--color-surface) border border-(--color-border) rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-60"
                                @click="emit('close')"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                Alterar senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>
