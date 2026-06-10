<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const logoSrc = `${import.meta.env.APP_URL}images/PHPcomRapadura_color.svg`

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const roleWarning = ref('')

onMounted(async () => {
    try {
        const { data } = await axios.get('/cfp/api/me')
        if (data.user) {
            if (data.user.role === 'palestrante') {
                redirectAfterLogin()
            } else {
                roleWarning.value = `Você está logado como ${data.user.role}. Use uma conta de palestrante para submeter propostas.`
            }
        }
    } catch {
        // não autenticado — exibe o formulário normalmente
    }
})

async function handleLogin() {
    loading.value = true
    error.value = ''
    roleWarning.value = ''

    try {
        await axios.get('/sanctum/csrf-cookie')
        const { data } = await axios.post('/cfp/login', form.value)

        if (data.user.role !== 'palestrante') {
            roleWarning.value = `Você está logado como ${data.user.role}. Use uma conta de palestrante para submeter propostas.`
            return
        }

        redirectAfterLogin()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Ocorreu um erro. Tente novamente.'
    } finally {
        loading.value = false
    }
}

function redirectAfterLogin() {
    const redirect = route.query.redirect
    if (redirect && String(redirect).startsWith('/cfp')) {
        window.location.href = String(redirect)
    } else {
        router.push({ name: 'cfp.dashboard' })
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
                    Área do Palestrante
                </h1>
                <p class="text-sm text-(--color-text-muted) text-center mb-8">
                    Entre com sua conta para submeter propostas de palestra.
                </p>

                <!-- Aviso de role incompatível -->
                <div
                    v-if="roleWarning"
                    role="alert"
                    class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 rounded-lg mb-4"
                >
                    {{ roleWarning }}
                </div>

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
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-(--color-text)">
                                Senha
                            </label>
                            <RouterLink :to="{ name: 'cfp.forgot-password' }" class="text-xs text-(--color-primary) hover:underline">
                                Esqueceu a senha?
                            </RouterLink>
                        </div>
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

                    <!-- Erro -->
                    <div
                        v-if="error"
                        role="alert"
                        class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm px-4 py-3 rounded-lg"
                    >
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

                    <!-- Criar conta -->
                    <div class="relative flex items-center gap-3 py-1">
                        <div class="flex-1 h-px bg-(--color-border)"></div>
                        <span class="text-xs text-(--color-text-muted) shrink-0">ou</span>
                        <div class="flex-1 h-px bg-(--color-border)"></div>
                    </div>

                    <RouterLink
                        :to="{ name: 'cfp.register' }"
                        class="w-full py-2.5 px-4 rounded-lg border border-(--color-border) text-(--color-text) text-sm font-semibold
                               hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center justify-center"
                    >
                        Criar conta como palestrante
                    </RouterLink>

                </form>
            </div>

            <!-- Voltar -->
            <p class="text-center text-sm text-(--color-text-muted) mt-6">
                <RouterLink :to="{ name: 'cfp.home' }" class="hover:text-(--color-text) transition underline">
                    ← Voltar para o Call for Papers
                </RouterLink>
            </p>
        </div>
    </div>
</template>
