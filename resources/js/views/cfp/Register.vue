<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const logoSrc = `${import.meta.env.BASE_URL}images/PHPcomRapadura_color.svg`

const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const loading = ref(false)
const errors = ref({})
const showPassword = ref(false)

async function handleRegister() {
    loading.value = true
    errors.value = {}

    try {
        await axios.get('/sanctum/csrf-cookie')
        const { data } = await axios.post('/cfp/register', form.value)

        if (data.user) {
            const redirect = route.query.redirect
            if (redirect && String(redirect).startsWith('/cfp')) {
                window.location.href = String(redirect)
            } else {
                router.push({ name: 'cfp.home' })
            }
        }
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {}
        } else {
            errors.value = { general: e.response?.data?.message ?? 'Ocorreu um erro. Tente novamente.' }
        }
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
                    <img :src="logoSrc" alt="PHP com Rapadura" class="h-12">
                </div>

                <!-- Título -->
                <h1 class="text-[22px] font-bold text-(--color-text) text-center mb-1">
                    Criar conta
                </h1>
                <p class="text-sm text-(--color-text-muted) text-center mb-8">
                    Cadastre-se como palestrante para submeter propostas.
                </p>

                <!-- Erro geral -->
                <div
                    v-if="errors.general"
                    role="alert"
                    class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg mb-4"
                >
                    {{ errors.general }}
                </div>

                <!-- Formulário -->
                <form @submit.prevent="handleRegister" class="space-y-4" novalidate>

                    <!-- Nome -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-(--color-text) mb-1.5">
                            Nome completo
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            autocomplete="name"
                            required
                            :disabled="loading"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition
                                   focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                   disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                            :class="errors.name ? 'border-red-400' : 'border-(--color-border)'"
                            placeholder="Seu nome"
                        >
                        <p v-if="errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ errors.name[0] }}</p>
                    </div>

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
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition
                                   focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                   disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                            :class="errors.email ? 'border-red-400' : 'border-(--color-border)'"
                            placeholder="seu@email.com"
                        >
                        <p v-if="errors.email" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ errors.email[0] }}</p>
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
                                autocomplete="new-password"
                                required
                                :disabled="loading"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition
                                       focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                       disabled:opacity-60 bg-(--color-surface) text-(--color-text) pr-10"
                                :class="errors.password ? 'border-red-400' : 'border-(--color-border)'"
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
                        <p v-if="errors.password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ errors.password[0] }}</p>
                    </div>

                    <!-- Confirmar senha -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-(--color-text) mb-1.5">
                            Confirmar senha
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                            :disabled="loading"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition
                                   focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                                   disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                            :class="errors.password_confirmation ? 'border-red-400' : 'border-(--color-border)'"
                            placeholder="Repita a senha"
                        >
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
                        {{ loading ? 'Criando conta...' : 'Criar conta' }}
                    </button>

                </form>
            </div>

            <!-- Já tem conta -->
            <p class="text-center text-sm text-(--color-text-muted) mt-6">
                Já tem conta?
                <RouterLink :to="{ name: 'cfp.login', query: $route.query }" class="text-(--color-primary) hover:underline font-medium">
                    Entrar
                </RouterLink>
            </p>
        </div>
    </div>
</template>
