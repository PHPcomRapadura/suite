<script setup>
import { ref } from 'vue'
import axios from 'axios'

const logoSrc = `${import.meta.env.BASE_URL}images/PHPcomRapadura_color.svg`

const form = ref({ email: '', password: '', remember: false })
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

async function handleLogin() {
    loading.value = true
    error.value = ''

    try {
        await axios.get('/sanctum/csrf-cookie')
        const { data } = await axios.post('/admin/login', form.value)
        window.location.href = data.redirect
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

            <!-- Card -->
            <div class="bg-(--color-surface) rounded-2xl shadow-sm border border-(--color-border) p-8">

                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    <img
                        :src="logoSrc"
                        alt="PHP com Rapadura"
                        class="h-12"
                    >
                </div>

                <!-- Título -->
                <h1 class="text-[22px] font-bold text-(--color-text) text-center mb-1">
                    Área restrita
                </h1>
                <p class="text-sm text-(--color-text-muted) text-center mb-8">
                    Acesso exclusivo para membros da organização.
                </p>

                <!-- Formulário -->
                <form @submit.prevent="handleLogin" class="space-y-4" novalidate>

                    <!-- E-mail -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-(--color-text) mb-1.5">
                            E-mail
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
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

                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-(--color-text) mb-1.5">
                            Senha
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                :disabled="loading"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                       disabled:opacity-60 transition pr-10"
                                placeholder="••••••••"
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

                    <!-- Lembrar-me -->
                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            :disabled="loading"
                            class="w-4 h-4 rounded border-(--color-border) text-(--color-primary) focus:ring-(--color-primary)"
                        >
                        <label for="remember" class="text-sm text-(--color-text-muted)">Lembrar-me</label>
                    </div>

                    <!-- Erro -->
                    <div v-if="error" role="alert" class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg">
                        {{ error }}
                    </div>

                    <!-- Botão -->
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
                        {{ loading ? 'Entrando...' : 'Entrar' }}
                    </button>

                </form>
            </div>

            <!-- Rodapé -->
            <p class="text-center text-xs text-(--color-text-muted) mt-6">
                PHP com Rapadura &copy; {{ new Date().getFullYear() }}
            </p>
        </div>
    </div>
</template>
