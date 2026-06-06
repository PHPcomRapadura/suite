<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCfpAuth } from '@/composables/useCfpAuth'

const router = useRouter()
const { user, fetchUser } = useCfpAuth()

const events = ref([])
const loading = ref(true)

const logoSrc = `${import.meta.env.BASE_URL}images/PHPcomRapadura_color.svg`

onMounted(async () => {
    await fetchUser()
    if (user.value?.role === 'palestrante') {
        router.replace({ name: 'cfp.dashboard' })
        return
    }
    await fetchEvents()
})

async function fetchEvents() {
    try {
        const { data } = await axios.get('/cfp/api/events')
        events.value = data.data
    } catch {
        events.value = []
    } finally {
        loading.value = false
    }
}

function handleSubmit(event) {
    if (!user.value) {
        router.push({ name: 'cfp.login', query: { redirect: `/cfp/submit/${event.id}` } })
        return
    }
    router.push(`/cfp/submit/${event.id}`)
}

function formatDate(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function formatDateShort(iso) {
    if (!iso) return null
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(iso))
}
</script>

<template>
    <div class="min-h-screen bg-(--color-bg)">

        <!-- Header -->
        <header class="bg-(--color-surface) border-b border-(--color-border) sticky top-0 z-10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center">
                    <img :src="logoSrc" alt="PHP com Rapadura" class="h-8">
                </a>

                <div class="flex items-center gap-3">
                    <RouterLink
                        :to="{ name: 'cfp.login' }"
                        class="text-sm px-3 py-1.5 rounded-lg bg-(--color-primary) text-white hover:bg-(--color-primary-hover) transition"
                    >
                        Entrar
                    </RouterLink>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <div class="bg-(--color-surface) border-b border-(--color-border)">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
                <h1 class="text-3xl font-bold text-(--color-text) mb-2">Call for Papers</h1>
                <p class="text-(--color-text-muted)">
                    Submeta sua proposta de palestra para os próximos eventos da comunidade PHP com Rapadura.
                </p>
            </div>
        </div>

        <!-- Conteúdo -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

            <!-- Aviso para admin/colaborador logado -->
            <div
                v-if="user && user.role !== 'palestrante'"
                class="mb-6 bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 rounded-lg"
                role="alert"
            >
                Você está logado como <strong>{{ user.role }}</strong>. Para submeter palestras, use uma conta de palestrante.
            </div>

            <!-- Skeleton de carregamento -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden animate-pulse"
                >
                    <div class="aspect-video bg-gray-200 dark:bg-gray-700"></div>
                    <div class="p-5 space-y-3">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                        <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                        <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded mt-4"></div>
                    </div>
                </div>
            </div>

            <!-- Estado vazio -->
            <div
                v-else-if="events.length === 0"
                class="text-center py-20 text-(--color-text-muted)"
            >
                <svg class="w-12 h-12 mx-auto mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium">Nenhum evento com CFP aberto no momento.</p>
                <p class="text-sm mt-1">Acompanhe nossas redes sociais para ficar por dentro das novidades.</p>
            </div>

            <!-- Grid de cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <article
                    v-for="event in events"
                    :key="event.id"
                    class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden flex flex-col"
                >
                    <!-- Cover image -->
                    <div class="aspect-video overflow-hidden bg-gradient-to-br from-blue-600 to-blue-800">
                        <img
                            v-if="event.cover_image"
                            :src="event.cover_image"
                            :alt="event.name"
                            class="w-full h-full object-cover"
                        >
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <img :src="logoSrc" alt="PHP com Rapadura" class="h-10 opacity-60">
                        </div>
                    </div>

                    <!-- Conteúdo do card -->
                    <div class="p-5 flex flex-col flex-1">

                        <!-- Badge de status -->
                        <div class="mb-3">
                            <span
                                v-if="event.cfp.status === 'aberto'"
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Aberto
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
                            >
                                Em breve
                            </span>
                        </div>

                        <!-- Nome e edição -->
                        <h2 class="text-base font-semibold text-(--color-text) leading-snug mb-1">
                            {{ event.name }}
                        </h2>
                        <p v-if="event.edition" class="text-xs text-(--color-text-muted) mb-2">
                            Edição {{ event.edition }}
                        </p>

                        <!-- Data e local -->
                        <div class="space-y-1 text-sm text-(--color-text-muted) mb-3">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ formatDate(event.starts_at) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg v-if="event.is_online" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                                <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ event.is_online ? 'Online' : (event.location || 'Local a confirmar') }}</span>
                            </div>
                        </div>

                        <!-- Período de submissão -->
                        <div class="text-sm text-(--color-text-muted) mb-2">
                            <span v-if="event.cfp.status === 'aberto'">
                                Submissões abertas até <strong class="text-(--color-text)">{{ formatDateShort(event.cfp.closes_at) }}</strong>
                            </span>
                            <span v-else>
                                Submissões abrem em <strong class="text-(--color-text)">{{ formatDateShort(event.cfp.opens_at) }}</strong>
                            </span>
                        </div>

                        <!-- Limite de propostas -->
                        <p
                            v-if="event.cfp.max_talks_per_speaker"
                            class="text-xs text-(--color-text-muted) mb-4"
                        >
                            Máx. {{ event.cfp.max_talks_per_speaker }} proposta{{ event.cfp.max_talks_per_speaker > 1 ? 's' : '' }} por palestrante
                        </p>

                        <!-- CTA -->
                        <div class="mt-auto pt-4 border-t border-(--color-border)">
                            <!-- Aviso para admin/colaborador -->
                            <div
                                v-if="user && user.role !== 'palestrante'"
                                class="text-xs text-(--color-text-muted) text-center py-2"
                                role="note"
                            >
                                Use uma conta de palestrante para submeter propostas
                            </div>

                            <div v-else>
                                <button
                                    :disabled="event.cfp.status === 'aguardando'"
                                    @click="handleSubmit(event)"
                                    class="w-full py-2.5 px-4 rounded-lg text-sm font-semibold transition
                                           focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2"
                                    :class="event.cfp.status === 'aguardando'
                                        ? 'bg-gray-100 dark:bg-gray-800 text-(--color-text-muted) cursor-not-allowed'
                                        : 'bg-(--color-primary) hover:bg-(--color-primary-hover) text-white'"
                                >
                                    Submeter palestra
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-(--color-border) mt-16 py-6">
            <p class="text-center text-sm text-(--color-text-muted)">
                PHP com Rapadura &copy; {{ new Date().getFullYear() }}
            </p>
        </footer>
    </div>
</template>
