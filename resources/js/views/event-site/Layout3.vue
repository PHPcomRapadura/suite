<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    event:    { type: Object, required: true },
    site:     { type: Object, required: true },
    sponsors: { type: Object, default: () => ({}) },
    schedule: { type: Object, default: () => ({}) },
})

const openFaq     = ref(null)
const showBackTop = ref(false)

function formatDate(date) {
    if (!date) return null
    return new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })
}

function formatPeriod(start, end) {
    const s = formatDate(start)
    const e = formatDate(end)
    if (!s) return null
    return e && e !== s ? `${s} a ${e}` : s
}

const levelLabels = {
    rapadura_com_castanha: 'Rapadura com Castanha',
    rapadura_com_coco:     'Rapadura com Côco',
    rapadura_tradicional:  'Rapadura Tradicional',
}

const levelOrder = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional']

const TIER_SIZE = {
    rapadura_com_castanha: 'w-36 h-20 p-4',
    rapadura_com_coco:     'w-28 h-16 p-3',
    rapadura_tradicional:  'w-24 h-14 p-2',
}

function orderedLevels() {
    return levelOrder.filter(l => props.sponsors[l]?.length)
}

const scheduleDays = computed(() =>
    Object.entries(props.schedule ?? {})
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, items]) => ({ date, items }))
)

const selectedDay = ref(null)
const activeDay   = computed(() => selectedDay.value ?? scheduleDays.value[0]?.date ?? null)

function formatDayTab(dateStr) {
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatDayFull(dateStr) {
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' })
}

function formatTime(isoStr) {
    return new Date(isoStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' })
}

const TYPE_DOT = {
    palestra: 'bg-blue-500', intervalo: 'bg-yellow-400', abertura: 'bg-green-500',
    encerramento: 'bg-purple-500', outro: 'bg-gray-400',
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onScroll() {
    showBackTop.value = window.scrollY > 400
}

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.section-hidden').forEach(el => el.classList.remove('section-hidden'))
        return
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('section-visible')
                observer.unobserve(entry.target)
            }
        })
    }, { threshold: 0.08 })
    document.querySelectorAll('.section-hidden').forEach(el => observer.observe(el))

    window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll)
})
</script>

<template>
    <div
        :style="`--site-primary: ${site.primary_color}; --site-secondary: ${site.secondary_color}; font-family: '${site.font.replace('_', ' ')}', sans-serif`"
        class="min-h-screen bg-(--color-bg)"
    >
        <!-- Header compacto sticky -->
        <header :style="`background-color: var(--site-primary)`" class="sticky top-0 z-50 py-3 px-4 shadow-sm">
            <div class="max-w-2xl mx-auto flex items-center gap-3">
                <img v-if="event.logo" :src="event.logo" :alt="event.name" class="w-8 h-8 object-contain rounded-lg bg-white/10 shrink-0">
                <span class="font-bold text-white text-base shrink-0">{{ event.name }}</span>

                <!-- Links de navegação — visíveis a partir de md -->
                <nav class="hidden md:flex items-center gap-0.5 ml-2" aria-label="Seções do evento">
                    <a v-if="event.description"        href="#sobre"          class="px-2.5 py-1 text-white/60 hover:text-white text-xs font-medium transition rounded">Sobre</a>
                    <a v-if="event.is_accepting_talks" href="#cfp"            class="px-2.5 py-1 text-white/60 hover:text-white text-xs font-medium transition rounded">CFP</a>
                    <a v-if="orderedLevels().length"   href="#patrocinadores" class="px-2.5 py-1 text-white/60 hover:text-white text-xs font-medium transition rounded">Patrocinadores</a>
                    <a v-if="scheduleDays.length"      href="#programacao"    class="px-2.5 py-1 text-white/60 hover:text-white text-xs font-medium transition rounded">Programação</a>
                    <a v-if="site.faq?.length"         href="#faq"            class="px-2.5 py-1 text-white/60 hover:text-white text-xs font-medium transition rounded">FAQ</a>
                </nav>

                <div class="ml-auto flex items-center gap-3 shrink-0">
                    <span v-if="formatPeriod(event.starts_at, event.ends_at) && !site.ticket_url" class="text-white/60 text-sm hidden sm:block">
                        {{ formatPeriod(event.starts_at, event.ends_at) }}
                    </span>
                    <a
                        v-if="site.ticket_url"
                        :href="site.ticket_url"
                        target="_blank" rel="noopener noreferrer"
                        :style="`background-color: var(--site-secondary)`"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white hover:opacity-90 transition"
                    >
                        Ingressos
                    </a>
                </div>
            </div>
        </header>

        <!-- Corpo centralizado -->
        <main id="conteudo" class="max-w-2xl mx-auto px-4 py-16 space-y-16">

            <!-- Chamada principal -->
            <section class="text-center space-y-4 section-hidden">
                <h1 class="text-4xl font-bold text-(--color-text)">{{ event.name }}</h1>
                <p v-if="site.hero_tagline" class="text-lg text-(--color-text-muted)">{{ site.hero_tagline }}</p>
                <div class="flex flex-wrap gap-3 justify-center text-sm text-(--color-text-muted)">
                    <span v-if="formatPeriod(event.starts_at, event.ends_at)" class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        {{ formatPeriod(event.starts_at, event.ends_at) }}
                    </span>
                    <span v-if="event.location || event.is_online" class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        {{ event.is_online ? 'Online' : event.location }}
                    </span>
                </div>
                <a
                    v-if="site.ticket_url"
                    :href="site.ticket_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :style="`background-color: var(--site-primary)`"
                    class="inline-block px-8 py-3 rounded-xl font-semibold text-white hover:opacity-90 transition"
                >
                    Ingressos
                </a>
            </section>

            <!-- Sobre -->
            <section v-if="event.description" id="sobre" class="section-hidden scroll-mt-14">
                <h2 class="text-xl font-bold text-(--color-text) mb-3">Sobre o evento</h2>
                <p class="text-sm text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ event.description }}</p>
            </section>

            <!-- CFP — card horizontal -->
            <section v-if="event.is_accepting_talks" id="cfp" class="section-hidden scroll-mt-14">
                <div
                    class="flex flex-col sm:flex-row items-center gap-5 rounded-xl border px-6 py-6"
                    :style="`border-color: color-mix(in srgb, var(--site-primary) 30%, transparent); background-color: color-mix(in srgb, var(--site-primary) 5%, transparent)`"
                >
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :style="`background-color: var(--site-primary)`">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 1a3 3 0 0 1 3 3v8a3 3 0 0 1-6 0V4a3 3 0 0 1 3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/>
                            <line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <p class="font-semibold text-(--color-text)">Call for Papers aberto</p>
                        <p class="text-sm text-(--color-text-muted) mt-0.5">Submeta uma proposta de palestra para o {{ event.name }}.</p>
                    </div>
                    <a
                        href="/cfp"
                        :style="`background-color: var(--site-primary)`"
                        class="shrink-0 inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg text-sm font-semibold text-white hover:opacity-90 transition"
                    >
                        Enviar proposta
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </section>

            <!-- Patrocinadores — por tier -->
            <section v-if="orderedLevels().length" id="patrocinadores" class="section-hidden scroll-mt-14">
                <h2 class="text-sm font-semibold text-(--color-text-muted) uppercase tracking-wider text-center mb-8">Patrocinadores</h2>
                <div class="space-y-8">
                    <div v-for="level in orderedLevels()" :key="level">
                        <p class="text-center text-xs text-(--color-text-muted) mb-4">{{ levelLabels[level] }}</p>
                        <div class="flex flex-wrap justify-center" :class="level === 'rapadura_com_castanha' ? 'gap-5' : 'gap-3'">
                            <a
                                v-for="sponsor in sponsors[level]"
                                :key="sponsor.id"
                                :href="sponsor.website_url || undefined"
                                :target="sponsor.website_url ? '_blank' : undefined"
                                rel="noopener noreferrer"
                                :class="['flex items-center justify-center rounded-lg border border-(--color-border) hover:border-(--color-text-muted) transition grayscale hover:grayscale-0', TIER_SIZE[level]]"
                            >
                                <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-full max-w-full object-contain">
                                <span v-else class="text-xs font-medium text-(--color-text-muted) text-center px-1">{{ sponsor.name }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Programação -->
            <section v-if="scheduleDays.length" id="programacao" class="section-hidden scroll-mt-14">
                <h2 class="text-xl font-bold text-(--color-text) mb-4">Programação</h2>

                <!-- Abas por dia (minimalistas) -->
                <div v-if="scheduleDays.length > 1" class="flex gap-1 mb-5 border-b border-(--color-border)">
                    <button
                        v-for="day in scheduleDays"
                        :key="day.date"
                        @click="selectedDay = day.date"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition -mb-px capitalize',
                            activeDay === day.date
                                ? 'border-b-2 text-(--color-primary)'
                                : 'text-(--color-text-muted) hover:text-(--color-text)'
                        ]"
                        :style="activeDay === day.date ? `border-color: var(--site-primary); color: var(--site-primary)` : ''"
                    >
                        {{ formatDayTab(day.date) }}
                    </button>
                </div>

                <template v-for="day in scheduleDays" :key="day.date">
                    <div v-show="scheduleDays.length === 1 || activeDay === day.date">
                        <p v-if="scheduleDays.length === 1" class="text-xs font-semibold text-(--color-text-muted) uppercase tracking-wider capitalize mb-4">
                            {{ formatDayFull(day.date) }}
                        </p>
                        <div class="divide-y divide-(--color-border)">
                            <div
                                v-for="item in day.items"
                                :key="item.starts_at + item.title"
                                class="flex items-start gap-4 py-3"
                            >
                                <span class="text-sm font-mono text-(--color-text-muted) w-12 shrink-0 pt-0.5">
                                    {{ formatTime(item.starts_at) }}
                                </span>
                                <div :class="['w-1.5 h-1.5 rounded-full mt-2 shrink-0', TYPE_DOT[item.type] ?? TYPE_DOT.outro]" aria-hidden="true" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-(--color-text)">{{ item.title }}</p>
                                    <p v-if="item.speaker_name || item.room" class="text-xs text-(--color-text-muted) mt-0.5">
                                        <span v-if="item.speaker_name">{{ item.speaker_name }}</span>
                                        <span v-if="item.speaker_name && (item.room || item.duration)"> · </span>
                                        <span v-if="item.room">{{ item.room }}</span>
                                        <span v-if="item.duration"> · {{ item.duration }} min</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <!-- FAQ -->
            <section v-if="site.faq?.length" id="faq" class="section-hidden scroll-mt-14">
                <h2 class="text-xl font-bold text-(--color-text) mb-4">FAQ</h2>
                <div class="divide-y divide-(--color-border)">
                    <div v-for="(item, i) in site.faq" :key="i">
                        <button
                            @click="openFaq = openFaq === i ? null : i"
                            class="w-full flex items-center justify-between py-4 text-left text-sm font-medium text-(--color-text) hover:text-(--color-primary) transition"
                            :aria-expanded="openFaq === i"
                            :aria-controls="`faq-answer-${i}`"
                            :style="openFaq === i ? `color: var(--site-primary)` : ''"
                        >
                            {{ item.question }}
                            <svg :class="['w-4 h-4 shrink-0 text-(--color-text-muted) transition-transform', openFaq === i ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div :id="`faq-answer-${i}`" v-show="openFaq === i" class="pb-4 text-sm text-(--color-text-muted) leading-relaxed">
                            {{ item.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Código de conduta -->
            <section v-if="site.code_of_conduct" id="conduta" class="section-hidden scroll-mt-14">
                <h2 class="text-xl font-bold text-(--color-text) mb-4">Código de Conduta</h2>
                <div class="text-sm text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ site.code_of_conduct }}</div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="text-center text-xs text-(--color-text-muted) py-6 mt-8 border-t border-(--color-border)">
            {{ event.name }} · PHP com Rapadura
        </footer>

        <!-- Back to top -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0 scale-75"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-75"
        >
            <button
                v-show="showBackTop"
                @click="scrollToTop"
                :style="`background-color: var(--site-primary)`"
                class="fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full shadow-lg flex items-center justify-center text-white hover:opacity-90 transition"
                aria-label="Voltar ao topo"
            >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="18 15 12 9 6 15"/>
                </svg>
            </button>
        </Transition>
    </div>
</template>
