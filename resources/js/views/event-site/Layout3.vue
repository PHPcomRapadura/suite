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

const levelOrder = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional']

function allSponsors() {
    return levelOrder.flatMap(l => props.sponsors[l] ?? [])
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
        <!-- Header compacto -->
        <header :style="`background-color: var(--site-primary)`" class="py-3 px-4">
            <div class="max-w-2xl mx-auto flex items-center gap-3">
                <img v-if="event.logo" :src="event.logo" :alt="event.name" class="w-8 h-8 object-contain rounded-lg bg-white/10">
                <span class="font-bold text-white text-lg">{{ event.name }}</span>
                <span v-if="formatPeriod(event.starts_at, event.ends_at)" class="ml-auto text-white/60 text-sm hidden sm:block">
                    {{ formatPeriod(event.starts_at, event.ends_at) }}
                </span>
            </div>
        </header>

        <!-- Corpo centralizado -->
        <main class="max-w-2xl mx-auto px-4 py-16 space-y-16">

            <!-- Chamada principal -->
            <section class="text-center space-y-4 section-hidden">
                <h1 class="text-4xl font-bold text-(--color-text)">{{ event.name }}</h1>
                <p v-if="site.hero_tagline" class="text-lg text-(--color-text-muted)">{{ site.hero_tagline }}</p>
                <div class="flex flex-wrap gap-3 justify-center text-sm text-(--color-text-muted)">
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
                    :style="`background-color: var(--site-primary)`"
                    class="inline-block px-8 py-3 rounded-xl font-semibold text-white hover:opacity-90 transition"
                >
                    Ingressos
                </a>
            </section>

            <!-- Sobre -->
            <section v-if="event.description" class="section-hidden">
                <h2 class="text-xl font-bold text-(--color-text) mb-3">Sobre o evento</h2>
                <p class="text-sm text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ event.description }}</p>
            </section>

            <!-- CFP -->
            <section v-if="event.is_accepting_talks" class="section-hidden text-center space-y-3">
                <div class="flex justify-center">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="`background-color: color-mix(in srgb, var(--site-primary) 12%, transparent)`">
                        <svg class="w-5 h-5" :style="`color: var(--site-primary)`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1a3 3 0 0 1 3 3v8a3 3 0 0 1-6 0V4a3 3 0 0 1 3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/>
                            <line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                    </div>
                </div>
                <p class="font-semibold text-(--color-text)">Call for Papers aberto</p>
                <p class="text-sm text-(--color-text-muted)">Submeta uma proposta de palestra para o {{ event.name }}.</p>
                <a
                    href="/cfp"
                    :style="`color: var(--site-primary)`"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold hover:underline"
                >
                    Enviar proposta
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
            </section>

            <!-- Patrocinadores — linha horizontal simples -->
            <section v-if="allSponsors().length" class="section-hidden">
                <h2 class="text-sm font-semibold text-(--color-text-muted) uppercase tracking-wider text-center mb-6">Realização</h2>
                <div class="flex flex-wrap justify-center gap-4 items-center">
                    <a
                        v-for="sponsor in allSponsors()"
                        :key="sponsor.id"
                        :href="sponsor.website_url || undefined"
                        :target="sponsor.website_url ? '_blank' : undefined"
                        rel="noopener noreferrer"
                        class="flex items-center justify-center p-3 rounded-lg border border-(--color-border) hover:border-(--color-text-muted) transition grayscale hover:grayscale-0"
                        style="min-width: 80px; min-height: 40px;"
                    >
                        <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-8 max-w-24 object-contain">
                        <span v-else class="text-xs font-medium text-(--color-text-muted)">{{ sponsor.name }}</span>
                    </a>
                </div>
            </section>

            <!-- Programação -->
            <section v-if="scheduleDays.length" class="section-hidden">
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
                                ? 'border-b-2 border-(--color-primary) text-(--color-primary)'
                                : 'text-(--color-text-muted) hover:text-(--color-text)'
                        ]"
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
                                <div :class="['w-1.5 h-1.5 rounded-full mt-2 shrink-0', TYPE_DOT[item.type] ?? TYPE_DOT.outro]" />
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
            <section v-if="site.faq?.length" class="section-hidden">
                <h2 class="text-xl font-bold text-(--color-text) mb-4">FAQ</h2>
                <div class="divide-y divide-(--color-border)">
                    <div v-for="(item, i) in site.faq" :key="i">
                        <button
                            @click="openFaq = openFaq === i ? null : i"
                            class="w-full flex items-center justify-between py-4 text-left text-sm font-medium text-(--color-text) hover:text-(--color-primary) transition"
                            :aria-expanded="openFaq === i"
                        >
                            {{ item.question }}
                            <svg :class="['w-4 h-4 shrink-0 text-(--color-text-muted) transition-transform', openFaq === i ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div v-show="openFaq === i" class="pb-4 text-sm text-(--color-text-muted) leading-relaxed">
                            {{ item.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Código de conduta -->
            <section v-if="site.code_of_conduct" class="section-hidden">
                <h2 class="text-xl font-bold text-(--color-text) mb-4">Código de Conduta</h2>
                <div class="text-sm text-(--color-text-muted) leading-relaxed whitespace-pre-line">{{ site.code_of_conduct }}</div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="text-center text-xs text-(--color-text-muted) py-6 mt-8 border-t border-(--color-border)">
            {{ event.name }} · PHP com Rapadura
        </footer>
    </div>
</template>
