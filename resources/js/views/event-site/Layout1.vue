<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
    event:    { type: Object, required: true },
    site:     { type: Object, required: true },
    sponsors: { type: Object, default: () => ({}) },
    schedule: { type: Object, default: () => ({}) },
})

const openFaq = ref(null)

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
})
</script>

<template>
    <div
        :style="`--site-primary: ${site.primary_color}; --site-secondary: ${site.secondary_color}; font-family: '${site.font.replace('_', ' ')}', sans-serif`"
        class="min-h-screen bg-(--color-bg)"
    >
        <!-- Hero -->
        <section class="relative w-full" style="min-height: 420px;">
            <div v-if="event.cover_image" class="absolute inset-0">
                <img :src="event.cover_image" :alt="event.name" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 to-black/80" />
            </div>
            <div v-else class="absolute inset-0" :style="`background-color: var(--site-primary)`" />

            <div class="relative z-10 flex flex-col items-center justify-center text-center px-4 py-20" style="min-height: 420px;">
                <img v-if="event.logo" :src="event.logo" :alt="event.name + ' logo'" class="w-20 h-20 object-contain mb-6 rounded-xl bg-white/10 p-2">
                <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight mb-3">{{ event.name }}</h1>
                <p v-if="site.hero_tagline" class="text-lg text-white/80 mb-6 max-w-xl">{{ site.hero_tagline }}</p>
                <div class="flex flex-wrap gap-4 justify-center text-white/70 text-sm mb-8">
                    <span v-if="formatPeriod(event.starts_at, event.ends_at)" class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        {{ formatPeriod(event.starts_at, event.ends_at) }}
                    </span>
                    <span v-if="event.location || event.is_online" class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
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
                    class="inline-block px-8 py-3 rounded-xl font-semibold text-white hover:opacity-90 transition text-base"
                >
                    Comprar ingresso
                </a>
            </div>
        </section>

        <div class="max-w-4xl mx-auto px-4 py-12 space-y-16">

            <!-- Sobre -->
            <section v-if="event.description" class="section-hidden">
                <h2 class="text-2xl font-bold text-(--color-text) mb-4">Sobre o evento</h2>
                <p class="text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ event.description }}</p>
            </section>

            <!-- CFP -->
            <section v-if="event.is_accepting_talks" class="section-hidden">
                <div class="flex flex-col sm:flex-row items-center gap-6 rounded-2xl border-2 px-8 py-7" :style="`border-color: color-mix(in srgb, var(--site-primary) 30%, transparent); background-color: color-mix(in srgb, var(--site-primary) 6%, transparent)`">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center" :style="`background-color: color-mix(in srgb, var(--site-primary) 15%, transparent)`">
                        <svg class="w-6 h-6" :style="`color: var(--site-primary)`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1a3 3 0 0 1 3 3v8a3 3 0 0 1-6 0V4a3 3 0 0 1 3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/>
                            <line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <p class="font-bold text-(--color-text) text-lg leading-snug">Call for Papers aberto</p>
                        <p class="text-(--color-text-muted) text-sm mt-1">Compartilhe seu conhecimento. Submeta uma proposta de palestra para o {{ event.name }}.</p>
                    </div>
                    <a
                        href="/cfp"
                        :style="`background-color: var(--site-primary)`"
                        class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-white text-sm hover:opacity-90 transition whitespace-nowrap"
                    >
                        Enviar proposta
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </section>

            <!-- Patrocinadores -->
            <section v-if="orderedLevels().length" class="section-hidden">
                <h2 class="text-2xl font-bold text-(--color-text) text-center mb-8">Patrocinadores</h2>
                <div v-for="level in orderedLevels()" :key="level" class="mb-8">
                    <p class="text-center text-sm font-semibold text-(--color-text-muted) uppercase tracking-wider mb-4">
                        {{ levelLabels[level] }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-6">
                        <a
                            v-for="sponsor in sponsors[level]"
                            :key="sponsor.id"
                            :href="sponsor.website_url || undefined"
                            :target="sponsor.website_url ? '_blank' : undefined"
                            rel="noopener noreferrer"
                            class="flex items-center justify-center p-4 rounded-xl border border-(--color-border) bg-(--color-surface) hover:border-(--color-primary) transition grayscale hover:grayscale-0"
                            style="min-width: 120px; min-height: 60px;"
                        >
                            <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-12 max-w-32 object-contain">
                            <span v-else class="text-sm font-medium text-(--color-text)">{{ sponsor.name }}</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Programação -->
            <section v-if="scheduleDays.length" class="section-hidden">
                <h2 class="text-2xl font-bold text-(--color-text) mb-6">Programação</h2>

                <!-- Abas por dia -->
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
            <section v-if="site.faq?.length" class="section-hidden">
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
                        >
                            {{ item.question }}
                            <svg
                                :class="['w-4 h-4 text-(--color-text-muted) shrink-0 transition-transform', openFaq === i ? 'rotate-180' : '']"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            >
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div v-show="openFaq === i" class="px-5 pb-4 text-sm text-(--color-text-muted) leading-relaxed">
                            {{ item.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Código de conduta -->
            <section v-if="site.code_of_conduct" class="section-hidden">
                <h2 class="text-2xl font-bold text-(--color-text) mb-4">Código de Conduta</h2>
                <div class="prose prose-sm max-w-none text-(--color-text-muted) whitespace-pre-line">{{ site.code_of_conduct }}</div>
            </section>

        </div>

        <!-- Footer -->
        <footer class="text-center text-sm text-(--color-text-muted) py-8 border-t border-(--color-border)">
            {{ event.name }} · PHP com Rapadura
        </footer>
    </div>
</template>
