<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import ConfirmModal from '@/components/ConfirmModal.vue'

const route   = useRoute()
const eventId = Number(route.params.id)

// ── State ────────────────────────────────────────────────────────────────────
const loading   = ref(true)
const event     = ref(null)
const site      = ref(null)
const sponsors  = ref([])
const saving    = ref({ appearance: false, content: false, toggle: false, sponsor: false, schedule: false })
const errors    = reactive({ appearance: {}, content: {}, sponsor: {}, schedule: {} })

// ── Appearance form ───────────────────────────────────────────────────────────
const appearanceForm = reactive({
    layout:          1,
    primary_color:   '#025c98',
    secondary_color: '#f59e0b',
    font:            'lexend',
    hero_tagline:    '',
    ticket_url:      '',
})

// ── Content form ──────────────────────────────────────────────────────────────
const contentForm = reactive({
    code_of_conduct: '',
    faq:             [],
})

// ── Sponsor modal ─────────────────────────────────────────────────────────────
const showSponsorModal   = ref(false)
const editingSponsor     = ref(null)
const sponsorLogoFile    = ref(null)
const sponsorLogoPreview = ref(null)
const sponsorForm        = reactive({ name: '', website_url: '', level: 'rapadura_tradicional', sort_order: 0 })

// ── Confirm delete modal ──────────────────────────────────────────────────────
const showDeleteModal  = ref(false)
const deletingSponsor  = ref(null)

// ── Constants ─────────────────────────────────────────────────────────────────
const FONTS = [
    { value: 'lexend',       label: 'Lexend' },
    { value: 'inter',        label: 'Inter' },
    { value: 'poppins',      label: 'Poppins' },
    { value: 'space_grotesk', label: 'Space Grotesk' },
]

const LEVELS = [
    { value: 'rapadura_com_castanha', label: 'Rapadura com Castanha' },
    { value: 'rapadura_com_coco',     label: 'Rapadura com Coco' },
    { value: 'rapadura_tradicional',  label: 'Rapadura Tradicional' },
]

const LEVEL_ORDER = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional']

const publicUrl = computed(() => `${window.location.origin}/${event.value?.slug ?? eventId}`)

const sponsorsByLevel = computed(() =>
    LEVEL_ORDER.map(level => ({
        level,
        label: LEVELS.find(l => l.value === level)?.label ?? level,
        items: sponsors.value.filter(s => s.level === level).sort((a, b) => a.sort_order - b.sort_order),
    })).filter(g => g.items.length)
)

// ── Fetch ─────────────────────────────────────────────────────────────────────
async function fetchData() {
    loading.value = true
    try {
        const [eventRes, siteRes, sponsorsRes] = await Promise.allSettled([
            axios.get(`/admin/api/events/${eventId}`),
            axios.get(`/admin/api/events/${eventId}/site`),
            axios.get(`/admin/api/events/${eventId}/site/sponsors`),
        ])

        if (eventRes.status === 'fulfilled') {
            event.value = eventRes.value.data
        }

        if (siteRes.status === 'fulfilled' && siteRes.value.data.data) {
            site.value = siteRes.value.data.data
            Object.assign(appearanceForm, {
                layout:          site.value.layout,
                primary_color:   site.value.primary_color,
                secondary_color: site.value.secondary_color,
                font:            site.value.font,
                hero_tagline:    site.value.hero_tagline ?? '',
                ticket_url:      site.value.ticket_url ?? '',
            })
            Object.assign(contentForm, {
                code_of_conduct: site.value.code_of_conduct ?? '',
                faq:             site.value.faq ? JSON.parse(JSON.stringify(site.value.faq)) : [],
            })
        }

        if (sponsorsRes.status === 'fulfilled') {
            sponsors.value = sponsorsRes.value.data.data ?? []
        }
    } finally {
        loading.value = false
    }
}

// ── Save appearance ───────────────────────────────────────────────────────────
async function saveAppearance() {
    saving.value.appearance = true
    errors.appearance = {}
    try {
        const payload = { ...appearanceForm }
        if (site.value) {
            const { data } = await axios.put(`/admin/api/events/${eventId}/site`, payload)
            site.value = data.data
        } else {
            const { data } = await axios.post(`/admin/api/events/${eventId}/site`, payload)
            site.value = data.data
        }
    } catch (err) {
        if (err.response?.status === 422) errors.appearance = err.response.data.errors ?? {}
    } finally {
        saving.value.appearance = false
    }
}

// ── Save content ──────────────────────────────────────────────────────────────
async function saveContent() {
    saving.value.content = true
    errors.content = {}
    try {
        const payload = {
            ...appearanceForm,
            code_of_conduct: contentForm.code_of_conduct,
            faq:             contentForm.faq,
        }
        if (site.value) {
            const { data } = await axios.put(`/admin/api/events/${eventId}/site`, payload)
            site.value = data.data
        } else {
            const { data } = await axios.post(`/admin/api/events/${eventId}/site`, payload)
            site.value = data.data
        }
    } catch (err) {
        if (err.response?.status === 422) errors.content = err.response.data.errors ?? {}
    } finally {
        saving.value.content = false
    }
}

// ── Toggle published ──────────────────────────────────────────────────────────
async function togglePublished() {
    if (!site.value) return
    saving.value.toggle = true
    try {
        const { data } = await axios.patch(`/admin/api/events/${eventId}/site/toggle-published`)
        site.value = data.data
    } finally {
        saving.value.toggle = false
    }
}

// ── FAQ helpers ───────────────────────────────────────────────────────────────
function addFaqItem() {
    contentForm.faq.push({ question: '', answer: '' })
}

function removeFaqItem(idx) {
    contentForm.faq.splice(idx, 1)
}

// ── Copy URL ──────────────────────────────────────────────────────────────────
const copied = ref(false)
async function copyUrl() {
    await navigator.clipboard.writeText(publicUrl.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

// ── Sponsor logo ──────────────────────────────────────────────────────────────
function onSponsorLogo(e) {
    const file = e.target.files?.[0]
    if (!file) return
    sponsorLogoFile.value = file
    sponsorLogoPreview.value = URL.createObjectURL(file)
}

function removeSponsorLogo() {
    sponsorLogoFile.value    = null
    sponsorLogoPreview.value = null
}

// ── Sponsor modal open/close ──────────────────────────────────────────────────
function openCreateSponsor(defaultLevel) {
    editingSponsor.value     = null
    sponsorLogoFile.value    = null
    sponsorLogoPreview.value = null
    errors.sponsor           = {}
    Object.assign(sponsorForm, { name: '', website_url: '', level: defaultLevel ?? 'rapadura_tradicional', sort_order: 0 })
    showSponsorModal.value   = true
}

function openEditSponsor(sponsor) {
    editingSponsor.value     = sponsor
    sponsorLogoFile.value    = null
    sponsorLogoPreview.value = sponsor.logo_url ?? null
    errors.sponsor           = {}
    Object.assign(sponsorForm, {
        name:        sponsor.name,
        website_url: sponsor.website_url ?? '',
        level:       sponsor.level,
        sort_order:  sponsor.sort_order ?? 0,
    })
    showSponsorModal.value = true
}

async function saveSponsor() {
    saving.value.sponsor = true
    errors.sponsor = {}
    try {
        const fd = new FormData()
        fd.append('name', sponsorForm.name)
        fd.append('website_url', sponsorForm.website_url)
        fd.append('level', sponsorForm.level)
        fd.append('sort_order', String(sponsorForm.sort_order))
        if (sponsorLogoFile.value) fd.append('logo', sponsorLogoFile.value)

        if (editingSponsor.value) {
            fd.append('_method', 'PUT')
            const { data } = await axios.post(`/admin/api/events/${eventId}/site/sponsors/${editingSponsor.value.id}`, fd)
            const idx = sponsors.value.findIndex(s => s.id === editingSponsor.value.id)
            if (idx !== -1) sponsors.value[idx] = data.data
        } else {
            const { data } = await axios.post(`/admin/api/events/${eventId}/site/sponsors`, fd)
            sponsors.value.push(data.data)
        }
        showSponsorModal.value = false
    } catch (err) {
        if (err.response?.status === 422) errors.sponsor = err.response.data.errors ?? {}
    } finally {
        saving.value.sponsor = false
    }
}

function confirmDelete(sponsor) {
    deletingSponsor.value = sponsor
    showDeleteModal.value = true
}

async function deleteSponsor() {
    if (!deletingSponsor.value) return
    await axios.delete(`/admin/api/events/${eventId}/site/sponsors/${deletingSponsor.value.id}`)
    sponsors.value = sponsors.value.filter(s => s.id !== deletingSponsor.value.id)
    deletingSponsor.value = null
    showDeleteModal.value = false
}

// ── Schedule ──────────────────────────────────────────────────────────────────
const schedule              = ref([])
const approvedTalks         = ref([])
const showScheduleModal     = ref(false)
const editingScheduleItem   = ref(null)
const showScheduleDeleteModal = ref(false)
const deletingScheduleItem  = ref(null)
const scheduleForm          = reactive({
    talk_id: '',
    type: 'palestra',
    title: '',
    speaker_name: '',
    date: '',
    time: '',
    duration: 50,
    room: '',
})

const TYPE_CONFIG = {
    palestra:     { label: 'Palestra',     cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' },
    intervalo:    { label: 'Intervalo',    cls: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' },
    abertura:     { label: 'Abertura',     cls: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    encerramento: { label: 'Encerramento', cls: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' },
    outro:        { label: 'Outro',        cls: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
}

const scheduleByDay = computed(() => {
    const days = {}
    for (const item of schedule.value) {
        const d = item.starts_at.split('T')[0]
        if (!days[d]) days[d] = []
        days[d].push(item)
    }
    return Object.entries(days)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, items]) => ({ date, items }))
})

function formatDay(dateStr) {
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('pt-BR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    })
}

function formatTime(isoStr) {
    return new Date(isoStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' })
}

async function fetchSchedule() {
    const { data } = await axios.get(`/admin/api/events/${eventId}/site/schedule`)
    schedule.value = data.data ?? []
}

async function fetchApprovedTalks() {
    try {
        const { data } = await axios.get(`/admin/api/events/${eventId}/talks`, { params: { status: 'aprovada' } })
        approvedTalks.value = data.data ?? []
    } catch {
        approvedTalks.value = []
    }
}

function onTalkSelected() {
    const talk = approvedTalks.value.find(t => t.id === Number(scheduleForm.talk_id))
    if (!talk) return
    scheduleForm.title        = talk.title
    scheduleForm.speaker_name = talk.speaker?.user?.name ?? ''
    scheduleForm.duration     = talk.duration ?? 50
}

function openCreateScheduleItem() {
    editingScheduleItem.value = null
    errors.schedule = {}
    Object.assign(scheduleForm, {
        talk_id: '', type: 'palestra', title: '', speaker_name: '',
        date: '', time: '', duration: 50, room: '',
    })
    fetchApprovedTalks()
    showScheduleModal.value = true
}

function openEditScheduleItem(item) {
    editingScheduleItem.value = item
    errors.schedule = {}
    const dt = new Date(item.starts_at)
    Object.assign(scheduleForm, {
        talk_id:      item.talk_id ?? '',
        type:         item.type,
        title:        item.title ?? '',
        speaker_name: item.speaker_name ?? '',
        date:         dt.toLocaleDateString('fr-CA', { timeZone: 'UTC' }), // YYYY-MM-DD
        time:         dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'UTC' }),
        duration:     item.duration,
        room:         item.room ?? '',
    })
    fetchApprovedTalks()
    showScheduleModal.value = true
}

async function saveScheduleItem() {
    saving.value.schedule = true
    errors.schedule = {}
    try {
        const payload = {
            talk_id:      scheduleForm.talk_id || null,
            type:         scheduleForm.type,
            title:        scheduleForm.title || null,
            speaker_name: scheduleForm.speaker_name || null,
            starts_at:    `${scheduleForm.date} ${scheduleForm.time}`,
            duration:     scheduleForm.duration,
            room:         scheduleForm.room || null,
        }

        if (editingScheduleItem.value) {
            const { data } = await axios.put(
                `/admin/api/events/${eventId}/site/schedule/${editingScheduleItem.value.id}`,
                payload,
            )
            const idx = schedule.value.findIndex(i => i.id === editingScheduleItem.value.id)
            if (idx !== -1) schedule.value[idx] = data.data
        } else {
            const { data } = await axios.post(`/admin/api/events/${eventId}/site/schedule`, payload)
            schedule.value.push(data.data)
            schedule.value.sort((a, b) => a.starts_at.localeCompare(b.starts_at))
        }
        showScheduleModal.value = false
    } catch (err) {
        if (err.response?.status === 422) errors.schedule = err.response.data.errors ?? {}
    } finally {
        saving.value.schedule = false
    }
}

function confirmDeleteScheduleItem(item) {
    deletingScheduleItem.value  = item
    showScheduleDeleteModal.value = true
}

async function performDeleteScheduleItem() {
    if (!deletingScheduleItem.value) return
    await axios.delete(`/admin/api/events/${eventId}/site/schedule/${deletingScheduleItem.value.id}`)
    schedule.value = schedule.value.filter(i => i.id !== deletingScheduleItem.value.id)
    deletingScheduleItem.value  = null
    showScheduleDeleteModal.value = false
}

onMounted(async () => {
    await fetchData()
    await fetchSchedule()
})
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-8">

        <!-- Voltar -->
        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: eventId } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar para o evento
        </RouterLink>

        <h1 class="text-2xl font-bold text-(--color-text) mb-8">Site do Evento</h1>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>

            <!-- ── Seção A — Aparência ──────────────────────────────────────── -->
            <section class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 mb-6">
                <h2 class="text-base font-semibold text-(--color-text) mb-5">Aparência</h2>

                <!-- Layout picker -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-(--color-text) mb-3">Layout</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            v-for="n in [1, 2, 3]"
                            :key="n"
                            @click="appearanceForm.layout = n"
                            :class="[
                                'relative flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition',
                                appearanceForm.layout === n
                                    ? 'border-(--color-primary) bg-blue-50 dark:bg-blue-950/20'
                                    : 'border-(--color-border) hover:border-(--color-text-muted)'
                            ]"
                        >
                            <!-- Miniatura representativa -->
                            <div class="w-full aspect-video rounded-md overflow-hidden bg-gray-100 dark:bg-gray-800">
                                <!-- Layout 1 — Clássico: imagem topo + conteúdo -->
                                <template v-if="n === 1">
                                    <div class="h-1/2 bg-blue-400/60 relative">
                                        <div class="absolute bottom-1 left-2 right-2 space-y-0.5">
                                            <div class="h-1.5 bg-white/70 rounded w-3/4" />
                                            <div class="h-1 bg-white/40 rounded w-1/2" />
                                        </div>
                                    </div>
                                    <div class="h-1/2 p-1.5 space-y-1">
                                        <div class="h-1 bg-gray-200 dark:bg-gray-600 rounded w-full" />
                                        <div class="h-1 bg-gray-200 dark:bg-gray-600 rounded w-4/5" />
                                        <div class="h-1 bg-gray-200 dark:bg-gray-600 rounded w-3/5" />
                                    </div>
                                </template>
                                <!-- Layout 2 — Imersivo: fundo colorido fullscreen -->
                                <template v-else-if="n === 2">
                                    <div class="h-full flex flex-col items-center justify-center gap-1 bg-blue-600 p-2">
                                        <div class="h-2 bg-white/90 rounded w-3/4" />
                                        <div class="h-1 bg-white/50 rounded w-1/2" />
                                        <div class="h-2 bg-yellow-400 rounded-sm w-1/3 mt-1" />
                                    </div>
                                </template>
                                <!-- Layout 3 — Minimalista: faixa topo + conteúdo central -->
                                <template v-else>
                                    <div class="h-2 bg-blue-600" />
                                    <div class="p-2 flex flex-col items-center gap-1">
                                        <div class="h-2 bg-gray-700 dark:bg-gray-200 rounded w-2/3" />
                                        <div class="h-1 bg-gray-300 dark:bg-gray-500 rounded w-1/2" />
                                        <div class="h-1.5 bg-blue-300 rounded w-1/3 mt-0.5" />
                                        <div class="mt-1 space-y-0.5 w-full px-2">
                                            <div class="h-0.5 bg-gray-200 dark:bg-gray-600 rounded" />
                                            <div class="h-0.5 bg-gray-200 dark:bg-gray-600 rounded" />
                                            <div class="h-0.5 bg-gray-200 dark:bg-gray-600 rounded w-3/4" />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <span :class="['text-xs font-medium', appearanceForm.layout === n ? 'text-(--color-primary)' : 'text-(--color-text-muted)']">
                                {{ ['Clássico', 'Imersivo', 'Minimalista'][n - 1] }}
                            </span>
                            <svg v-if="appearanceForm.layout === n" class="absolute top-1.5 right-1.5 w-4 h-4 text-(--color-primary)" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm-1 14.5-4-4 1.41-1.41L11 13.67l6.59-6.59L19 8.5l-8 8z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Cores -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Cor primária</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="appearanceForm.primary_color"
                                type="color"
                                class="w-10 h-10 rounded-lg border border-(--color-border) cursor-pointer p-0.5 bg-(--color-surface)"
                            >
                            <input
                                v-model="appearanceForm.primary_color"
                                type="text"
                                maxlength="7"
                                class="flex-1 px-3 py-2 rounded-lg border text-sm font-mono bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.appearance?.primary_color ? 'border-red-500' : 'border-(--color-border)'"
                            >
                        </div>
                        <p v-if="errors.appearance?.primary_color" class="text-xs text-red-500 mt-1">{{ errors.appearance.primary_color[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Cor secundária</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="appearanceForm.secondary_color"
                                type="color"
                                class="w-10 h-10 rounded-lg border border-(--color-border) cursor-pointer p-0.5 bg-(--color-surface)"
                            >
                            <input
                                v-model="appearanceForm.secondary_color"
                                type="text"
                                maxlength="7"
                                class="flex-1 px-3 py-2 rounded-lg border text-sm font-mono bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.appearance?.secondary_color ? 'border-red-500' : 'border-(--color-border)'"
                            >
                        </div>
                        <p v-if="errors.appearance?.secondary_color" class="text-xs text-red-500 mt-1">{{ errors.appearance.secondary_color[0] }}</p>
                    </div>
                </div>

                <!-- Fonte -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">Fonte</label>
                    <select
                        v-model="appearanceForm.font"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                    >
                        <option v-for="f in FONTS" :key="f.value" :value="f.value">{{ f.label }}</option>
                    </select>
                </div>

                <!-- Tagline -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">Tagline / Chamada</label>
                    <input
                        v-model="appearanceForm.hero_tagline"
                        type="text"
                        maxlength="255"
                        placeholder="A maior comunidade PHP do Nordeste"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                    >
                </div>

                <!-- Link de ingressos -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">Link de ingressos</label>
                    <input
                        v-model="appearanceForm.ticket_url"
                        type="url"
                        placeholder="https://sympla.com.br/..."
                        class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                        :class="errors.appearance?.ticket_url ? 'border-red-500' : 'border-(--color-border)'"
                    >
                    <p v-if="errors.appearance?.ticket_url" class="text-xs text-red-500 mt-1">{{ errors.appearance.ticket_url[0] }}</p>
                </div>

                <button
                    @click="saveAppearance"
                    :disabled="saving.appearance"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition disabled:opacity-60"
                >
                    <svg v-if="saving.appearance" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    Salvar aparência
                </button>
            </section>

            <!-- ── Seção B — Conteúdo ──────────────────────────────────────── -->
            <section class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 mb-6">
                <h2 class="text-base font-semibold text-(--color-text) mb-5">Conteúdo</h2>

                <!-- Código de conduta -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                        Código de conduta
                        <span class="font-normal text-(--color-text-muted) ml-1">(Markdown)</span>
                    </label>
                    <textarea
                        v-model="contentForm.code_of_conduct"
                        rows="8"
                        placeholder="## Código de Conduta&#10;&#10;Este evento..."
                        class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm font-mono leading-relaxed focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition resize-y"
                    />
                </div>

                <!-- FAQ -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-sm font-medium text-(--color-text)">FAQ</label>
                        <button
                            @click="addFaqItem"
                            class="inline-flex items-center gap-1 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Adicionar pergunta
                        </button>
                    </div>

                    <div v-if="!contentForm.faq.length" class="text-sm text-(--color-text-muted) py-4 text-center border border-dashed border-(--color-border) rounded-xl">
                        Nenhuma pergunta adicionada.
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, idx) in contentForm.faq"
                            :key="idx"
                            class="rounded-xl border border-(--color-border) p-4"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold text-(--color-text-muted) uppercase tracking-wide">Pergunta {{ idx + 1 }}</span>
                                <button
                                    @click="removeFaqItem(idx)"
                                    class="text-xs text-red-500 hover:text-red-700 transition"
                                    aria-label="Remover pergunta"
                                >
                                    Remover
                                </button>
                            </div>
                            <input
                                v-model="item.question"
                                type="text"
                                placeholder="Pergunta"
                                class="w-full px-3 py-2 rounded-lg border border-(--color-border) bg-(--color-bg) text-(--color-text) text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            >
                            <textarea
                                v-model="item.answer"
                                rows="3"
                                placeholder="Resposta"
                                class="w-full px-3 py-2 rounded-lg border border-(--color-border) bg-(--color-bg) text-(--color-text) text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition resize-none"
                            />
                        </div>
                    </div>
                </div>

                <button
                    @click="saveContent"
                    :disabled="saving.content"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition disabled:opacity-60"
                >
                    <svg v-if="saving.content" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    Salvar conteúdo
                </button>
            </section>

            <!-- ── Seção C — Publicação ────────────────────────────────────── -->
            <section class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 mb-6">
                <h2 class="text-base font-semibold text-(--color-text) mb-4">Publicação</h2>

                <div v-if="!site" class="text-sm text-(--color-text-muted)">
                    Salve a aparência antes de publicar.
                </div>

                <template v-else>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-(--color-text)">Publicar página do evento</p>
                            <p class="text-xs text-(--color-text-muted) mt-0.5">Torna a página acessível ao público.</p>
                        </div>
                        <button
                            @click="togglePublished"
                            :disabled="saving.toggle"
                            :class="[
                                'relative inline-flex w-11 h-6 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2 disabled:opacity-60',
                                site.is_published ? 'bg-(--color-primary)' : 'bg-gray-300 dark:bg-gray-600'
                            ]"
                            :aria-pressed="site.is_published"
                        >
                            <span
                                :class="[
                                    'inline-block w-5 h-5 bg-white rounded-full shadow-sm transition-transform translate-y-0.5',
                                    site.is_published ? 'translate-x-5.5' : 'translate-x-0.5'
                                ]"
                            />
                        </button>
                    </div>

                    <div v-if="site.is_published" class="mt-4 flex items-center gap-2">
                        <a
                            :href="publicUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 text-sm text-(--color-primary) hover:underline truncate"
                        >
                            {{ publicUrl }}
                        </a>
                        <button
                            @click="copyUrl"
                            class="shrink-0 text-xs px-3 py-1.5 rounded-lg border border-(--color-border) text-(--color-text) hover:bg-(--color-bg) transition"
                        >
                            {{ copied ? 'Copiado!' : 'Copiar' }}
                        </button>
                    </div>
                </template>
            </section>

            <!-- ── Seção D — Patrocinadores ───────────────────────────────── -->
            <section class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-semibold text-(--color-text)">Patrocinadores</h2>
                    <button
                        @click="openCreateSponsor(null)"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Adicionar
                    </button>
                </div>

                <div v-if="!sponsors.length" class="text-sm text-(--color-text-muted) text-center py-6 border border-dashed border-(--color-border) rounded-xl">
                    Nenhum patrocinador cadastrado.
                </div>

                <div v-else class="space-y-6">
                    <div v-for="group in sponsorsByLevel" :key="group.level">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-(--color-text-muted) uppercase tracking-wider">{{ group.label }}</p>
                            <button
                                @click="openCreateSponsor(group.level)"
                                class="text-xs text-(--color-primary) hover:text-(--color-primary-hover) transition"
                            >
                                + Adicionar
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="sponsor in group.items"
                                :key="sponsor.id"
                                class="flex items-center gap-3 rounded-xl border border-(--color-border) p-3 hover:bg-(--color-bg) transition"
                            >
                                <div class="w-12 h-10 flex items-center justify-center rounded-lg border border-(--color-border) bg-(--color-surface) shrink-0 overflow-hidden">
                                    <img v-if="sponsor.logo_url" :src="sponsor.logo_url" :alt="sponsor.name" class="max-w-full max-h-full object-contain">
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-(--color-text-muted)">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-(--color-text) truncate">{{ sponsor.name }}</p>
                                    <p v-if="sponsor.website_url" class="text-xs text-(--color-text-muted) truncate">{{ sponsor.website_url }}</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        @click="openEditSponsor(sponsor)"
                                        class="text-xs px-2.5 py-1.5 rounded-lg border border-(--color-border) text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="confirmDelete(sponsor)"
                                        class="text-xs px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition"
                                    >
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Seção E — Grade de Palestras ──────────────────────────── -->
            <section class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 mt-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-semibold text-(--color-text)">Grade de Palestras</h2>
                    <button
                        @click="openCreateScheduleItem"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover) transition"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Adicionar slot
                    </button>
                </div>

                <div v-if="!schedule.length" class="text-sm text-(--color-text-muted) text-center py-6 border border-dashed border-(--color-border) rounded-xl">
                    Nenhum slot adicionado ainda.
                </div>

                <div v-else class="space-y-6">
                    <div v-for="day in scheduleByDay" :key="day.date">
                        <p class="text-xs font-semibold text-(--color-text-muted) uppercase tracking-wider mb-3 capitalize">
                            {{ formatDay(day.date) }}
                        </p>
                        <div class="space-y-1.5">
                            <div
                                v-for="item in day.items"
                                :key="item.id"
                                class="flex items-center gap-3 rounded-xl border border-(--color-border) px-4 py-3 hover:bg-(--color-bg) transition"
                            >
                                <span class="text-sm font-mono font-medium text-(--color-text-muted) w-12 shrink-0">
                                    {{ formatTime(item.starts_at) }}
                                </span>
                                <span :class="['text-xs px-2 py-0.5 rounded-full shrink-0', TYPE_CONFIG[item.type]?.cls ?? TYPE_CONFIG.outro.cls]">
                                    {{ TYPE_CONFIG[item.type]?.label ?? item.type }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-(--color-text) truncate">{{ item.title }}</p>
                                    <p v-if="item.speaker_name || item.room" class="text-xs text-(--color-text-muted) truncate">
                                        <span v-if="item.speaker_name">{{ item.speaker_name }}</span>
                                        <span v-if="item.speaker_name && item.room"> · </span>
                                        <span v-if="item.room">{{ item.room }}</span>
                                        <span v-if="item.duration"> · {{ item.duration }} min</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        @click="openEditScheduleItem(item)"
                                        class="text-xs px-2.5 py-1.5 rounded-lg border border-(--color-border) text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="confirmDeleteScheduleItem(item)"
                                        class="text-xs px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition"
                                    >
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </template>
    </div>

    <!-- ── Modal de patrocinador ────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="showSponsorModal"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            @click.self="showSponsorModal = false"
        >
            <div class="bg-(--color-surface) rounded-2xl w-full max-w-md shadow-xl">
                <div class="flex items-center justify-between p-6 border-b border-(--color-border)">
                    <h3 class="text-lg font-semibold text-(--color-text)">
                        {{ editingSponsor ? 'Editar patrocinador' : 'Adicionar patrocinador' }}
                    </h3>
                    <button @click="showSponsorModal = false" class="text-(--color-text-muted) hover:text-(--color-text) transition">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Fechar">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">

                    <!-- Nome -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Nome <span class="text-red-500">*</span></label>
                        <input
                            v-model="sponsorForm.name"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            :class="errors.sponsor?.name ? 'border-red-500' : 'border-(--color-border)'"
                        >
                        <p v-if="errors.sponsor?.name" class="text-xs text-red-500 mt-1">{{ errors.sponsor.name[0] }}</p>
                    </div>

                    <!-- Logo -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Logo</label>
                        <div v-if="sponsorLogoPreview" class="flex items-center gap-3 mb-3">
                            <img :src="sponsorLogoPreview" alt="preview" class="h-12 max-w-24 object-contain rounded-lg border border-(--color-border)">
                            <button @click="removeSponsorLogo" class="text-xs text-red-500 hover:text-red-700 transition">Remover</button>
                        </div>
                        <label class="cursor-pointer inline-flex items-center gap-2 text-sm text-(--color-primary) hover:text-(--color-primary-hover) transition">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            Enviar imagem
                            <input type="file" accept="image/*" class="sr-only" @change="onSponsorLogo">
                        </label>
                        <p v-if="errors.sponsor?.logo" class="text-xs text-red-500 mt-1">{{ errors.sponsor.logo[0] }}</p>
                    </div>

                    <!-- Site -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Site</label>
                        <input
                            v-model="sponsorForm.website_url"
                            type="url"
                            placeholder="https://empresa.com.br"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            :class="errors.sponsor?.website_url ? 'border-red-500' : 'border-(--color-border)'"
                        >
                        <p v-if="errors.sponsor?.website_url" class="text-xs text-red-500 mt-1">{{ errors.sponsor.website_url[0] }}</p>
                    </div>

                    <!-- Nível -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Nível <span class="text-red-500">*</span></label>
                        <select
                            v-model="sponsorForm.level"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                        >
                            <option v-for="l in LEVELS" :key="l.value" :value="l.value">{{ l.label }}</option>
                        </select>
                        <p v-if="errors.sponsor?.level" class="text-xs text-red-500 mt-1">{{ errors.sponsor.level[0] }}</p>
                    </div>

                </div>
                <div class="flex justify-end gap-3 px-6 pb-6">
                    <button
                        @click="showSponsorModal = false"
                        class="px-4 py-2 text-sm rounded-lg border border-(--color-border) text-(--color-text) hover:bg-(--color-bg) transition"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveSponsor"
                        :disabled="saving.sponsor"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition disabled:opacity-60"
                    >
                        <svg v-if="saving.sponsor" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Modal de confirmação de exclusão de patrocinador ─────────────────── -->
    <ConfirmModal
        :show="showDeleteModal"
        title="Remover patrocinador"
        :message="`Tem certeza que deseja remover &quot;${deletingSponsor?.name ?? ''}&quot;? Esta ação não pode ser desfeita.`"
        confirm-label="Remover"
        confirm-variant="danger"
        @close="showDeleteModal = false; deletingSponsor = null"
        @confirm="deleteSponsor"
    />

    <!-- ── Modal de slot de grade ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="showScheduleModal"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            @click.self="showScheduleModal = false"
        >
            <div class="bg-(--color-surface) rounded-2xl w-full max-w-lg shadow-xl max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-(--color-border)">
                    <h3 class="text-lg font-semibold text-(--color-text)">
                        {{ editingScheduleItem ? 'Editar slot' : 'Adicionar slot' }}
                    </h3>
                    <button @click="showScheduleModal = false" class="text-(--color-text-muted) hover:text-(--color-text) transition">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Fechar">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Tipo <span class="text-red-500">*</span></label>
                        <select
                            v-model="scheduleForm.type"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                        >
                            <option v-for="(cfg, val) in TYPE_CONFIG" :key="val" :value="val">{{ cfg.label }}</option>
                        </select>
                    </div>

                    <!-- Palestra aprovada (opcional) -->
                    <div v-if="approvedTalks.length">
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                            Palestra aprovada
                            <span class="font-normal text-(--color-text-muted) ml-1">(opcional — preenche título e palestrante)</span>
                        </label>
                        <select
                            v-model="scheduleForm.talk_id"
                            @change="onTalkSelected"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                        >
                            <option value="">— Nenhuma —</option>
                            <option v-for="talk in approvedTalks" :key="talk.id" :value="talk.id">
                                {{ talk.title }} — {{ talk.speaker?.user?.name ?? '?' }}
                            </option>
                        </select>
                    </div>

                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Título</label>
                        <input
                            v-model="scheduleForm.title"
                            type="text"
                            placeholder="Ex: Coffee Break, Abertura, Nome da palestra"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            :class="errors.schedule?.title ? 'border-red-500' : 'border-(--color-border)'"
                        >
                        <p v-if="errors.schedule?.title" class="text-xs text-red-500 mt-1">{{ errors.schedule.title[0] }}</p>
                    </div>

                    <!-- Palestrante -->
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">Palestrante</label>
                        <input
                            v-model="scheduleForm.speaker_name"
                            type="text"
                            placeholder="Nome do palestrante"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                        >
                    </div>

                    <!-- Data e Hora -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Data <span class="text-red-500">*</span></label>
                            <input
                                v-model="scheduleForm.date"
                                type="date"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.schedule?.starts_at ? 'border-red-500' : 'border-(--color-border)'"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Horário <span class="text-red-500">*</span></label>
                            <input
                                v-model="scheduleForm.time"
                                type="time"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                                :class="errors.schedule?.starts_at ? 'border-red-500' : 'border-(--color-border)'"
                            >
                        </div>
                    </div>
                    <p v-if="errors.schedule?.starts_at" class="text-xs text-red-500 -mt-2">{{ errors.schedule.starts_at[0] }}</p>

                    <!-- Duração e Sala -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Duração (min)</label>
                            <input
                                v-model.number="scheduleForm.duration"
                                type="number"
                                min="5"
                                max="480"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Sala / Trilha</label>
                            <input
                                v-model="scheduleForm.room"
                                type="text"
                                placeholder="Ex: Sala Principal"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary) transition"
                            >
                        </div>
                    </div>

                </div>
                <div class="flex justify-end gap-3 px-6 pb-6">
                    <button
                        @click="showScheduleModal = false"
                        class="px-4 py-2 text-sm rounded-lg border border-(--color-border) text-(--color-text) hover:bg-(--color-bg) transition"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveScheduleItem"
                        :disabled="saving.schedule"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition disabled:opacity-60"
                    >
                        <svg v-if="saving.schedule" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Modal de confirmação de exclusão de slot ───────────────────────────── -->
    <ConfirmModal
        :show="showScheduleDeleteModal"
        title="Remover slot"
        :message="`Tem certeza que deseja remover &quot;${deletingScheduleItem?.title ?? 'este slot'}&quot;?`"
        confirm-label="Remover"
        confirm-variant="danger"
        @close="showScheduleDeleteModal = false; deletingScheduleItem = null"
        @confirm="performDeleteScheduleItem"
    />
</template>
