<script setup>
import { ref } from 'vue'
import axios from 'axios'

const logoSrc = `${import.meta.env.APP_URL}images/PHPcomRapadura_color.svg`

const email   = ref('')
const loading = ref(false)
const success = ref(false)
const error   = ref('')

async function handleSubmit() {
    loading.value = true
    error.value   = ''

    try {
        await axios.post('/cfp/forgot-password', { email: email.value })
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
                        <h1 class="text-[22px] font-bold text-(--color-text)">Verifique seu e-mail</h1>
                        <p class="text-sm text-(--color-text-muted) leading-relaxed">
                            Se o endereço <strong class="text-(--color-text)">{{ email }}</strong> estiver cadastrado,
                            você receberá um link de redefinição de senha em instantes.
                        </p>
                        <p class="text-xs text-(--color-text-muted)">Não recebeu? Verifique a pasta de spam.</p>
                    </div>
                </template>

                <!-- Formulário -->
                <template v-else>
                    <h1 class="text-[22px] font-bold text-(--color-text) text-center mb-1">
                        Esqueceu sua senha?
                    </h1>
                    <p class="text-sm text-(--color-text-muted) text-center mb-8">
                        Informe seu e-mail e enviaremos um link para redefinir sua senha.
                    </p>

                    <div
                        v-if="error"
                        role="alert"
                        class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg mb-4"
                    >
                        {{ error }}
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>
                        <div>
                            <label for="email" class="block text-sm font-medium text-(--color-text) mb-1.5">
                                E-mail
                            </label>
                            <input
                                id="email"
                                v-model="email"
                                type="email"
                                autocomplete="email"
                                required
                                :disabled="loading"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                       disabled:opacity-60 transition"
                                placeholder="seu@email.com"
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
                            {{ loading ? 'Enviando...' : 'Enviar link de redefinição' }}
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
