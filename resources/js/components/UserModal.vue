<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: { type: Boolean, required: true },
    user: { type: Object,  default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = computed(() => !!props.user)

const form = ref({ name: '', email: '', role: 'colaborador', password: '', password_confirmation: '' })
const errors  = ref({})
const loading = ref(false)
const showPassword = ref(false)

watch(() => props.show, (val) => {
    if (val) {
        errors.value  = {}
        showPassword.value = false
        form.value = props.user
            ? { name: props.user.name, email: props.user.email, role: props.user.role, password: '', password_confirmation: '' }
            : { name: '', email: '', role: 'colaborador', password: '', password_confirmation: '' }
    }
})

async function submit() {
    loading.value = true
    errors.value  = {}

    try {
        if (isEditing.value) {
            await axios.put(`/admin/api/users/${props.user.id}`, form.value)
        } else {
            await axios.post('/admin/api/users', form.value)
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
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-md bg-(--color-surface) rounded-2xl shadow-xl p-6">

                    <h2 class="text-lg font-semibold text-(--color-text) mb-6">
                        {{ isEditing ? 'Editar usuário' : 'Novo usuário' }}
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4" novalidate>

                        <!-- Nome -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Nome completo <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                :disabled="loading"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
                                         errors.name ? 'border-(--color-danger)' : 'border-(--color-border)']"
                            >
                            <p v-if="errors.name" class="mt-1 text-xs text-(--color-danger)">{{ errors.name[0] }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                E-mail <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                :disabled="loading"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
                                         errors.email ? 'border-(--color-danger)' : 'border-(--color-border)']"
                            >
                            <p v-if="errors.email" class="mt-1 text-xs text-(--color-danger)">{{ errors.email[0] }}</p>
                        </div>

                        <!-- Função -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Função <span class="text-(--color-danger)">*</span>
                            </label>
                            <select
                                v-model="form.role"
                                :disabled="loading"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
                                         errors.role ? 'border-(--color-danger)' : 'border-(--color-border)']"
                            >
                                <option value="colaborador">Colaborador</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <p v-if="errors.role" class="mt-1 text-xs text-(--color-danger)">{{ errors.role[0] }}</p>
                        </div>

                        <!-- Senha -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Senha <span v-if="!isEditing" class="text-(--color-danger)">*</span>
                                <span v-else class="text-(--color-text-muted) font-normal">(deixe em branco para manter)</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    :disabled="loading"
                                    :class="['w-full px-3.5 py-2.5 pr-10 rounded-lg border text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
                                             errors.password ? 'border-(--color-danger)' : 'border-(--color-border)']"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                >
                                    <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <p v-if="errors.password" class="mt-1 text-xs text-(--color-danger)">{{ errors.password[0] }}</p>
                        </div>

                        <!-- Confirmar senha -->
                        <div v-if="form.password || !isEditing">
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Confirmar senha <span v-if="!isEditing" class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                :disabled="loading"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                       disabled:opacity-60 transition"
                            >
                        </div>

                        <!-- Ações -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-(--color-text) bg-(--color-surface) border border-(--color-border)
                                       rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-60"
                                @click="emit('close')"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-white bg-(--color-primary) hover:bg-(--color-primary-hover)
                                       rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                Salvar
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
.modal-leave-to    { opacity: 0; }
</style>
