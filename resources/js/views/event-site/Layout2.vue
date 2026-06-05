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

const TYPE_COLOR = {
    palestra:     'border-blue-400/50 bg-blue-50/50 dark:bg-blue-950/20',
    intervalo:    'border-yellow-400/50 bg-yellow-50/50 dark:bg-yellow-950/20',
    abertura:     'border-green-400/50 bg-green-50/50 dark:bg-green-950/20',
    encerramento: 'border-purple-400/50 bg-purple-50/50 dark:bg-purple-950/20',
    outro:        'border-gray-200 bg-gray-50 dark:bg-gray-800/50',
}

const TYPE_LABEL = {
    palestra: 'Palestra', intervalo: 'Intervalo', abertura: 'Abertura',
    encerramento: 'Encerramento', outro: 'Outro',
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
        class="min-h-screen"
    >
        <!-- Hero fullscreen com primary_color -->
        <section
            class="flex flex-col items-center justify-center min-h-screen text-center px-4 py-20"
            :style="`background-color: var(--site-primary)`"
        >
            <img v-if="event.logo" :src="event.logo" :alt="event.name + ' logo'" class="w-24 h-24 object-contain mb-8 rounded-2xl bg-white/10 p-3">
            <h1 class="text-5xl md:text-7xl font-bold text-white leading-none mb-4">{{ event.name }}</h1>
            <p v-if="site.hero_tagline" class="text-xl text-white/70 mb-10 max-w-2xl">{{ site.hero_tagline }}</p>
            <a
                v-if="site.ticket_url"
                :href="site.ticket_url"
                target="_blank"
                rel="noopener noreferrer"
                :style="`background-color: var(--site-secondary)`"
                class="inline-block px-10 py-4 rounded-2xl font-bold text-white text-lg hover:opacity-90 transition shadow-lg"
            >
                Comprar ingresso
            </a>
        </section>

        <!-- Faixa de data/local -->
        <div
            class="py-6 px-4 text-center"
            :style="`background-color: color-mix(in srgb, var(--site-primary) 85%, black)`"
        >
            <div class="flex flex-wrap gap-6 justify-center text-white/80 font-medium">
                <span v-if="formatPeriod(event.starts_at, event.ends_at)" class="inline-flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    {{ formatPeriod(event.starts_at, event.ends_at) }}
                </span>
                <span v-if="event.location || event.is_online" class="inline-flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    {{ event.is_online ? 'Online' : event.location }}
                </span>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 py-16 space-y-20">

            <!-- Patrocinadores -->
            <section v-if="orderedLevels().length" class="section-hidden">
                <h2 class="text-3xl font-bold text-center mb-12" :style="`color: var(--site-primary)`">Patrocinadores</h2>
                <div class="space-y-12">
                    <div v-for="level in orderedLevels()" :key="level">
                        <p class="text-center text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-6">
                            {{ levelLabels[level] }}
                        </p>
                        <div class="flex flex-wrap justify-center items-center" :class="level === 'rapadura_com_castanha' ? 'gap-8' : 'gap-5'">
                            <a
                                v-for="sponsor in sponsors[level]"
                                :key="sponsor.id"
                                :href="sponsor.website_url || undefined"
                                :target="sponsor.website_url ? '_blank' : undefined"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-gray-200 dark:hover:border-gray-600 transition-all duration-200 group"
                                :class="level === 'rapadura_com_castanha' ? 'w-52 h-32 p-6' : level === 'rapadura_com_coco' ? 'w-40 h-24 p-5' : 'w-32 h-18 p-4'"
                            >
                                <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-h-full max-w-full object-contain grayscale group-hover:grayscale-0 transition duration-300">
                                <span v-else class="text-sm font-semibold text-center text-gray-700 dark:text-gray-300">{{ sponsor.name }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Programação -->
            <section v-if="scheduleDays.length" class="section-hidden">
                <h2 class="text-3xl font-bold mb-8" :style="`color: var(--site-primary)`">Programação</h2>

                <!-- Abas por dia -->
                <div v-if="scheduleDays.length > 1" class="flex flex-wrap gap-2 mb-8">
                    <button
                        v-for="day in scheduleDays"
                        :key="day.date"
                        @click="selectedDay = day.date"
                        :class="[
                            'px-5 py-2.5 rounded-xl text-sm font-semibold transition capitalize',
                            activeDay === day.date
                                ? 'text-white'
                                : 'bg-white/10 text-gray-700 dark:text-gray-200 hover:bg-white/20'
                        ]"
                        :style="activeDay === day.date ? `background-color: var(--site-primary)` : ''"
                    >
                        {{ formatDayTab(day.date) }}
                    </button>
                </div>

                <template v-for="day in scheduleDays" :key="day.date">
                    <div v-show="scheduleDays.length === 1 || activeDay === day.date">
                        <p v-if="scheduleDays.length === 1" class="text-base font-semibold capitalize mb-6 text-gray-500 dark:text-gray-400">
                            {{ formatDayFull(day.date) }}
                        </p>
                        <div class="space-y-3">
                            <div
                                v-for="item in day.items"
                                :key="item.starts_at + item.title"
                                :class="['flex items-start gap-4 rounded-2xl border-2 px-6 py-5', TYPE_COLOR[item.type] ?? TYPE_COLOR.outro]"
                            >
                                <span class="text-base font-mono font-bold w-14 shrink-0 pt-0.5 text-gray-600 dark:text-gray-300">
                                    {{ formatTime(item.starts_at) }}
                                </span>
                                <div class="flex-1">
                                    <p class="font-semibold text-lg text-gray-900 dark:text-white leading-snug">{{ item.title }}</p>
                                    <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                                        <span v-if="item.speaker_name">{{ item.speaker_name }}</span>
                                        <span v-if="item.room" class="font-medium">{{ item.room }}</span>
                                        <span v-if="item.duration">{{ item.duration }} min</span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-xl shrink-0 border border-current/20 text-gray-600 dark:text-gray-300">
                                    {{ TYPE_LABEL[item.type] ?? item.type }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <!-- FAQ -->
            <section v-if="site.faq?.length" class="section-hidden">
                <h2 class="text-3xl font-bold mb-8" :style="`color: var(--site-primary)`">Perguntas frequentes</h2>
                <div class="space-y-4">
                    <div
                        v-for="(item, i) in site.faq"
                        :key="i"
                        class="rounded-2xl border-2 overflow-hidden"
                        :style="`border-color: color-mix(in srgb, var(--site-primary) 20%, transparent)`"
                    >
                        <button
                            @click="openFaq = openFaq === i ? null : i"
                            class="w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-lg transition"
                            :aria-expanded="openFaq === i"
                        >
                            <span>{{ item.question }}</span>
                            <svg :class="['w-5 h-5 shrink-0 transition-transform', openFaq === i ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div v-show="openFaq === i" class="px-6 pb-5 text-base leading-relaxed text-gray-600 dark:text-gray-300">
                            {{ item.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Código de conduta -->
            <section v-if="site.code_of_conduct" class="section-hidden">
                <div class="rounded-2xl p-8 bg-gray-50 dark:bg-gray-900">
                    <h2 class="text-2xl font-bold mb-4" :style="`color: var(--site-primary)`">Código de Conduta</h2>
                    <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ site.code_of_conduct }}</div>
                </div>
            </section>

        </div>

        <!-- Footer -->
        <footer
            class="text-center text-sm text-white/60 py-8"
            :style="`background-color: var(--site-primary)`"
        >
            {{ event.name }} · PHP com Rapadura
        </footer>
    </div>
</template>
