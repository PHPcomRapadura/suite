<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    event:    { type: Object, required: true },
    site:     { type: Object, required: true },
    sponsors: { type: Object, default: () => ({}) },
    schedule: { type: Object, default: () => ({}) },
    speakers: { type: Array,  default: () => [] },
})

const openFaq      = ref(null)
const navVisible   = ref(false)
const showBackTop  = ref(false)
const activeSection = ref(null)

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
    rapadura_com_coco:     'Rapadura com Coco',
    rapadura_tradicional:  'Rapadura Tradicional',
}

const levelOrder = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional']

const TIER_SIZE = {
    rapadura_com_castanha: 'w-44 h-28 p-5',
    rapadura_com_coco:     'w-36 h-20 p-4',
    rapadura_tradicional:  'w-28 h-16 p-3',
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

const TYPE_STYLE = {
    palestra:     { label: 'Palestra',     cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' },
    intervalo:    { label: 'Intervalo',    cls: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' },
    abertura:     { label: 'Abertura',     cls: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    encerramento: { label: 'Encerramento', cls: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' },
    outro:        { label: 'Outro',        cls: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
}

function formatDayTab(dateStr) {
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatDayFull(dateStr) {
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' })
}

function formatTime(isoStr) {
    return new Date(isoStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' })
}

function initials(name) {
    if (!name) return '?'
    return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onScroll() {
    showBackTop.value = window.scrollY > 400
    const sections = document.querySelectorAll('main section[id]')
    let current = null
    for (const section of sections) {
        if (section.getBoundingClientRect().top <= 80) current = section.id
    }
    activeSection.value = current
}

onMounted(() => {
    const hero = document.getElementById('hero')
    if (hero) {
        const heroObserver = new IntersectionObserver(([entry]) => {
            navVisible.value = !entry.isIntersecting
        }, { threshold: 0.1 })
        heroObserver.observe(hero)
    }

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
        <!-- Skip link -->
        <a
            href="#conteudo"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:text-white focus:text-sm focus:font-semibold focus:shadow-lg"
            :style="`background-color: var(--site-primary)`"
        >Pular para o conteúdo</a>

        <!-- Nav sticky — aparece quando o hero sai do viewport -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <nav
                v-if="navVisible"
                class="fixed top-0 inset-x-0 z-50 py-3 px-4 shadow-lg"
                :style="`background-color: var(--site-primary)`"
                aria-label="Navegação do evento"
            >
                <div class="max-w-4xl mx-auto flex items-center gap-1 overflow-x-auto">
                    <span class="font-bold text-white text-sm shrink-0 mr-3">{{ event.name }}</span>
                    <a v-if="event.description"        href="#sobre"          :aria-current="activeSection === 'sobre'          ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'sobre'          ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">Sobre</a>
                    <a v-if="event.is_accepting_talks" href="#cfp"            :aria-current="activeSection === 'cfp'            ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'cfp'            ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">CFP</a>
                    <a v-if="speakers.length"          href="#palestrantes"   :aria-current="activeSection === 'palestrantes'   ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'palestrantes'   ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">Palestrantes</a>
                    <a v-if="orderedLevels().length"   href="#patrocinadores" :aria-current="activeSection === 'patrocinadores'  ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'patrocinadores'  ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">Patrocinadores</a>
                    <a v-if="scheduleDays.length"      href="#programacao"    :aria-current="activeSection === 'programacao'     ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'programacao'     ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">Programação</a>
                    <a v-if="site.faq?.length"         href="#faq"            :aria-current="activeSection === 'faq'             ? 'true' : undefined" :class="['px-3 py-1 text-sm shrink-0 transition rounded', activeSection === 'faq'             ? 'text-white bg-white/15' : 'text-white/70 hover:text-white']">FAQ</a>
                    <a
                        v-if="site.ticket_url"
                        :href="site.ticket_url"
                        target="_blank" rel="noopener noreferrer"
                        :style="`background-color: var(--site-secondary)`"
                        class="ml-auto shrink-0 px-4 py-1.5 rounded-lg text-sm font-semibold text-white hover:opacity-90 transition"
                    >
                        Ingressos
                    </a>
                </div>
            </nav>
        </Transition>

        <!-- Hero -->
        <section id="hero" class="relative w-full" style="min-height: 480px;">
            <div v-if="event.cover_image" class="absolute inset-0">
                <img :src="event.cover_image" :alt="event.name" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/60 to-black/80" />
            </div>
            <div v-else class="absolute inset-0" :style="`background-color: var(--site-primary)`" />

            <div class="relative z-10 flex flex-col items-center justify-center text-center px-4 py-20" style="min-height: 480px;">
                <img v-if="event.logo" :src="event.logo" :alt="event.name + ' — logo'" class="w-20 h-20 object-contain mb-6 rounded-xl bg-white/10 p-2">
                <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight mb-3">{{ event.name }}</h1>
                <p v-if="site.hero_tagline" class="text-lg text-white/80 mb-6 max-w-xl">{{ site.hero_tagline }}</p>
                <div class="flex flex-wrap gap-4 justify-center text-white/70 text-sm mb-8">
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
                    :style="`background-color: var(--site-secondary)`"
                    class="inline-block px-8 py-3 rounded-xl font-semibold text-white hover:opacity-90 transition text-base shadow-lg"
                >
                    Comprar ingresso
                </a>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-6 inset-x-0 flex justify-center animate-bounce" aria-hidden="true">
                <svg class="w-6 h-6 text-white/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
        </section>

        <main id="conteudo" class="max-w-4xl mx-auto px-4 py-12 space-y-16">

            <!-- Sobre -->
            <section v-if="event.description" id="sobre" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) mb-4">Sobre o evento</h2>
                <p class="text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ event.description }}</p>
            </section>

            <!-- CFP -->
            <section v-if="event.is_accepting_talks" id="cfp" class="section-hidden scroll-mt-16">
                <div
                    class="rounded-2xl px-8 py-10 text-center"
                    :style="`background-color: color-mix(in srgb, var(--site-primary) 8%, transparent); border: 2px solid color-mix(in srgb, var(--site-primary) 25%, transparent)`"
                >
                    <div class="flex justify-center mb-5">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" :style="`background-color: var(--site-primary)`">
                            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 1a3 3 0 0 1 3 3v8a3 3 0 0 1-6 0V4a3 3 0 0 1 3-3z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="23"/>
                                <line x1="8" y1="23" x2="16" y2="23"/>
                            </svg>
                        </div>
                    </div>
                    <h2 class="font-bold text-2xl mb-2" :style="`color: var(--site-primary)`">Call for Papers aberto</h2>
                    <p class="text-(--color-text-muted) text-sm max-w-md mx-auto mb-7">
                        Compartilhe seu conhecimento. Submeta uma proposta de palestra para o {{ event.name }}.
                    </p>
                    <a
                        href="/cfp"
                        :style="`background-color: var(--site-primary)`"
                        class="inline-flex items-center gap-2 px-7 py-3 rounded-xl font-semibold text-white hover:opacity-90 transition shadow-md"
                    >
                        Enviar proposta
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </section>

            <!-- Palestrantes -->
            <section v-if="speakers.length" id="palestrantes" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) text-center mb-8">Palestrantes</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <div
                        v-for="s in speakers"
                        :key="s.id"
                        class="flex flex-col items-center text-center gap-2 bg-(--color-surface) border border-(--color-border) rounded-xl p-5"
                    >
                        <img
                            v-if="s.avatar_url"
                            :src="s.avatar_url"
                            :alt="s.name"
                            class="w-20 h-20 rounded-full object-cover shrink-0"
                        >
                        <div
                            v-else
                            class="w-20 h-20 rounded-full flex items-center justify-center text-white font-bold text-xl shrink-0"
                            :style="`background-color: var(--site-primary)`"
                        >
                            {{ initials(s.name) }}
                        </div>

                        <p class="text-sm font-semibold text-(--color-text) leading-tight">{{ s.name }}</p>
                        <p v-if="s.city || s.state" class="text-xs text-(--color-text-muted) -mt-1">
                            {{ [s.city, s.state].filter(Boolean).join(', ') }}
                        </p>

                        <div v-if="s.twitter || s.github || s.linkedin || s.website" class="flex items-center gap-2.5 mt-1 flex-wrap justify-center">
                            <a v-if="s.twitter" :href="`https://twitter.com/${s.twitter}`" target="_blank" rel="noopener noreferrer" :aria-label="`${s.name} no Twitter`" class="text-(--color-text-muted) hover:text-(--color-primary) transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.738l7.73-8.835L1.254 2.25H8.08l4.259 5.629L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                            </a>
                            <a v-if="s.github" :href="`https://github.com/${s.github}`" target="_blank" rel="noopener noreferrer" :aria-label="`${s.name} no GitHub`" class="text-(--color-text-muted) hover:text-(--color-primary) transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5 5 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5 5 0 0 0-1.33 3.48c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                            </a>
                            <a v-if="s.linkedin" :href="s.linkedin" target="_blank" rel="noopener noreferrer" :aria-label="`${s.name} no LinkedIn`" class="text-(--color-text-muted) hover:text-(--color-primary) transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                            </a>
                            <a v-if="s.website" :href="s.website" target="_blank" rel="noopener noreferrer" :aria-label="`Site de ${s.name}`" class="text-(--color-text-muted) hover:text-(--color-primary) transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Patrocinadores -->
            <section v-if="orderedLevels().length" id="patrocinadores" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) text-center mb-8">Patrocinadores</h2>
                <div v-for="level in orderedLevels()" :key="level" class="mb-10 last:mb-0">
                    <p class="text-center text-xs font-semibold text-(--color-text-muted) uppercase tracking-widest mb-5">
                        {{ levelLabels[level] }}
                    </p>
                    <div class="flex flex-wrap justify-center" :class="level === 'rapadura_com_castanha' ? 'gap-8' : 'gap-5'">
                        <a
                            v-for="sponsor in sponsors[level]"
                            :key="sponsor.id"
                            :href="sponsor.website_url || undefined"
                            :target="sponsor.website_url ? '_blank' : undefined"
                            rel="noopener noreferrer"
                            :class="['flex items-center justify-center rounded-xl border border-(--color-border) bg-(--color-surface) hover:border-(--color-primary) hover:shadow-md transition grayscale hover:grayscale-0', TIER_SIZE[level]]"
                        >
                            <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-full max-w-full object-contain">
                            <span v-else class="text-sm font-medium text-center text-(--color-text) px-2">{{ sponsor.name }}</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Programação -->
            <section v-if="scheduleDays.length" id="programacao" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) mb-6">Programação</h2>

                <div v-if="scheduleDays.length > 1" class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-for="day in scheduleDays"
                        :key="day.date"
                        @click="selectedDay = day.date"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-medium transition capitalize',
                            activeDay === day.date
                                ? 'bg-(--color-primary) text-white'
                                : 'bg-(--color-surface) border border-(--color-border) text-(--color-text) hover:border-(--color-primary)'
                        ]"
                    >
                        {{ formatDayTab(day.date) }}
                    </button>
                </div>

                <template v-for="day in scheduleDays" :key="day.date">
                    <div v-show="scheduleDays.length === 1 || activeDay === day.date">
                        <p v-if="scheduleDays.length === 1" class="text-sm font-semibold text-(--color-text-muted) capitalize mb-4">
                            {{ formatDayFull(day.date) }}
                        </p>
                        <div class="space-y-2">
                            <div
                                v-for="item in day.items"
                                :key="item.starts_at + item.title"
                                class="flex items-start gap-4 rounded-xl border border-(--color-border) bg-(--color-surface) px-5 py-4"
                            >
                                <span class="text-sm font-mono font-semibold text-(--color-text-muted) w-12 shrink-0 pt-0.5">
                                    {{ formatTime(item.starts_at) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                        <span :class="['text-xs px-2 py-0.5 rounded-full', TYPE_STYLE[item.type]?.cls ?? TYPE_STYLE.outro.cls]">
                                            {{ TYPE_STYLE[item.type]?.label ?? item.type }}
                                        </span>
                                        <span v-if="item.room" class="text-xs text-(--color-text-muted)">{{ item.room }}</span>
                                        <span v-if="item.duration" class="text-xs text-(--color-text-muted)">{{ item.duration }} min</span>
                                    </div>
                                    <p class="text-sm font-semibold text-(--color-text)">{{ item.title }}</p>
                                    <p v-if="item.speaker_name" class="text-xs text-(--color-text-muted) mt-0.5">{{ item.speaker_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <!-- FAQ -->
            <section v-if="site.faq?.length" id="faq" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) mb-6">Perguntas frequentes</h2>
                <div class="space-y-3">
                    <div
                        v-for="(item, i) in site.faq"
                        :key="i"
                        class="rounded-xl border border-(--color-border) bg-(--color-surface) overflow-hidden"
                    >
                        <button
                            @click="openFaq = openFaq === i ? null : i"
                            class="w-full flex items-center justify-between px-5 py-4 text-left font-medium text-(--color-text) hover:bg-(--color-bg) transition"
                            :aria-expanded="openFaq === i"
                            :aria-controls="`faq-answer-${i}`"
                        >
                            {{ item.question }}
                            <svg
                                :class="['w-4 h-4 text-(--color-text-muted) shrink-0 transition-transform', openFaq === i ? 'rotate-180' : '']"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true"
                            >
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div :id="`faq-answer-${i}`" v-show="openFaq === i" class="px-5 pb-4 text-sm text-(--color-text-muted) leading-relaxed">
                            {{ item.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Código de conduta -->
            <section v-if="site.code_of_conduct" id="conduta" class="section-hidden scroll-mt-16">
                <h2 class="text-2xl font-bold text-(--color-text) mb-4">Código de Conduta</h2>
                <div class="prose prose-sm max-w-none text-(--color-text-muted) whitespace-pre-line">{{ site.code_of_conduct }}</div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="text-center text-sm text-(--color-text-muted) py-8 border-t border-(--color-border)">
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
