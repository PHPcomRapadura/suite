<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route  = useRoute()

const logoSrc = `${import.meta.env.APP_URL}images/PHPcomRapadura_color.svg`

const form = ref({
    token:                 '',
    email:                 '',
    password:              '',
    password_confirmation: '',
})
const loading      = ref(false)
const success      = ref(false)
const error        = ref('')
const showPassword = ref(false)

onMounted(() => {
    form.value.token = String(route.query.token ?? '')
    form.value.email = String(route.query.email ?? '')
})

async function handleSubmit() {
    loading.value = true
    error.value   = ''

    try {
        await axios.post('/cfp/reset-password', form.value)
        success.value = true
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Ocorreu um erro. Tente novamente.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-(--color-bg) px-4">
        <div class="w-full max-w-[420px]">

            <div class="bg-(--color-surface) rounded-2xl shadow-sm border border-(--color-border) p-8">

                <div class="flex justify-center mb-6">
                    <img :src="logoSrc" alt="PHP com Rapadura" class="h-12">
                </div>

                <!-- Sucesso -->
                <template v-if="success">
                    <div class="flex flex-col items-center text-center gap-4 py-2">
                        <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h1 class="text-[22px] font-bold text-(--color-text)">Senha redefinida!</h1>
                        <p class="text-sm text-(--color-text-muted) leading-relaxed">
                            Sua senha foi atualizada com sucesso. Você já pode fazer login com a nova senha.
                        </p>
                        <RouterLink
                            :to="{ name: 'cfp.login' }"
                            class="mt-2 w-full py-2.5 px-4 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg
                                   transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2 text-center block"
                        >
                            Ir para o login
                        </RouterLink>
                    </div>
                </template>

                <!-- Token ausente -->
                <template v-else-if="!form.token">
                    <div class="text-center py-2">
                        <h1 class="text-[22px] font-bold text-(--color-text) mb-3">Link inválido</h1>
                        <p class="text-sm text-(--color-text-muted) mb-6">
                            Este link de redefinição é inválido ou expirou. Solicite um novo.
                        </p>
                        <RouterLink
                            :to="{ name: 'cfp.forgot-password' }"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-(--color-primary) text-white text-sm font-semibold hover:bg-(--color-primary-hover) transition"
                        >
                            Solicitar novo link
                        </RouterLink>
                    </div>
                </template>

                <!-- Formulário -->
                <template v-else>
                    <h1 class="text-[22px] font-bold text-(--color-text) text-center mb-1">
                        Nova senha
                    </h1>
                    <p class="text-sm text-(--color-text-muted) text-center mb-8">
                        Escolha uma senha com pelo menos 8 caracteres.
                    </p>

                    <div
                        v-if="error"
                        role="alert"
                        class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg mb-4"
                    >
                        {{ error }}
                        <RouterLink
                            v-if="error.includes('expirado') || error.includes('inválido')"
                            :to="{ name: 'cfp.forgot-password' }"
                            class="block mt-1 underline font-medium"
                        >
                            Solicitar novo link →
                        </RouterLink>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>

                        <div>
                            <label for="password" class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Nova senha
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    :disabled="loading"
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                           disabled:opacity-60 transition pr-10"
                                    placeholder="Mínimo 8 caracteres"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                >
                                    <svg v-if="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Confirmar nova senha
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                :disabled="loading"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                       disabled:opacity-60 transition"
                                placeholder="Repita a nova senha"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-full py-2.5 px-4 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg
                                   transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2
                                   disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ loading ? 'Salvando...' : 'Redefinir senha' }}
                        </button>

                    </form>
                </template>
            </div>

            <p class="text-center text-sm text-(--color-text-muted) mt-6">
                <RouterLink :to="{ name: 'cfp.login' }" class="hover:text-(--color-text) transition underline">
                    ← Voltar para o login
                </RouterLink>
            </p>
        </div>
    </div>
</template>
