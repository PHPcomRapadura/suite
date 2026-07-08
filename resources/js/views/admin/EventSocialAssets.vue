<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const TYPES = [
    { value: 'announcement', label: 'Chamada para o evento' },
    { value: 'speaker', label: 'Divulgar palestrante' },
]

const loading = ref(true)
const generating = ref(false)
const event = ref(null)
const selectedFormat = ref('story')
const selectedType = ref('announcement')
const selectedTalkId = ref(null)
const talks = ref([])
const loadingTalks = ref(false)
const assetsList = ref([])
const error = ref('')
const success = ref('')

const title = computed(() => {
    if (!event.value) return 'Artes de Divulgação'
    return `Artes para ${event.value.name}`
})

const currentAsset = computed(() => {
    return assetsList.value.find((asset) => {
        if (asset.type !== selectedType.value || asset.format !== selectedFormat.value) return false
        if (selectedType.value === 'speaker') return asset.talk_id === selectedTalkId.value
        return true
    }) ?? null
})

const canGenerate = computed(() => {
    if (selectedType.value === 'speaker') return !!selectedTalkId.value
    return true
})

const generatedAtLabel = computed(() => {
    if (!currentAsset.value?.generated_at) return ''
    return new Date(currentAsset.value.generated_at).toLocaleString('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    })
})

async function loadData() {
    loading.value = true
    error.value = ''

    try {
        const response = await axios.get(`/admin/api/events/${route.params.id}/social-assets`)
        event.value = response.data.data.event
        assetsList.value = response.data.data.assets ?? []
    } catch (e) {
        error.value = 'Não foi possível carregar os dados do evento.'
    } finally {
        loading.value = false
    }
}

async function loadTalks() {
    loadingTalks.value = true

    try {
        const response = await axios.get(`/admin/api/events/${route.params.id}/talks`, {
            params: { status: 'aprovada' },
        })
        talks.value = response.data.data
    } catch (e) {
        talks.value = []
    } finally {
        loadingTalks.value = false
    }
}

watch(selectedType, (type) => {
    selectedTalkId.value = null
    if (type === 'speaker' && talks.value.length === 0) loadTalks()
})

async function generateAsset() {
    generating.value = true
    error.value = ''
    success.value = ''

    try {
        const payload = { format: selectedFormat.value, type: selectedType.value }
        if (selectedType.value === 'speaker') payload.talk_id = selectedTalkId.value

        const response = await axios.post(`/admin/api/events/${route.params.id}/social-assets/generate`, payload)

        assetsList.value = assetsList.value.filter((asset) => asset !== currentAsset.value)
        assetsList.value.push(response.data.data)
        success.value = 'Arte gerada com sucesso.'
    } catch (e) {
        error.value = e?.response?.data?.message || 'Não foi possível gerar a arte neste momento.'
    } finally {
        generating.value = false
    }
}

function downloadAsset() {
    if (!currentAsset.value) return
    const link = document.createElement('a')
    link.href = currentAsset.value.asset_url
    link.download = `${route.params.id}-${selectedType.value}-${selectedFormat.value}.png`
    link.click()
}

onMounted(() => loadData())
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: route.params.id } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar ao evento
        </RouterLink>

        <div v-if="loading" class="flex justify-center py-16">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-6">
            <div class="bg-(--color-surface) border border-(--color-border) rounded-2xl p-6">
                <h1 class="text-2xl font-bold text-(--color-text) mb-2">{{ title }}</h1>
                <p class="text-sm text-(--color-text-muted) mb-6">
                    Gere uma arte simples para Instagram Stories ou post de feed com base nos dados do evento.
                    Artes geradas ficam salvas e podem ser baixadas quando quiser.
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-(--color-text) mb-2">Tipo de arte</label>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="typeOption in TYPES"
                            :key="typeOption.value"
                            type="button"
                            class="px-4 py-2 rounded-lg border transition"
                            :class="selectedType === typeOption.value
                                ? 'bg-(--color-primary) text-white border-(--color-primary)'
                                : 'bg-(--color-surface) text-(--color-text) border-(--color-border) hover:bg-gray-50 dark:hover:bg-gray-800'"
                            @click="selectedType = typeOption.value"
                        >
                            {{ typeOption.label }}
                        </button>
                    </div>
                </div>

                <div v-if="selectedType === 'speaker'" class="mb-6">
                    <label class="block text-sm font-medium text-(--color-text) mb-2">Palestra</label>
                    <select
                        v-model="selectedTalkId"
                        class="w-full px-3 py-2 rounded-lg border border-(--color-border) bg-(--color-surface) text-(--color-text) focus:outline-none focus:ring-2 focus:ring-(--color-primary)"
                    >
                        <option :value="null" disabled>{{ loadingTalks ? 'Carregando palestras...' : 'Selecione uma palestra aprovada' }}</option>
                        <option v-for="talk in talks" :key="talk.id" :value="talk.id">
                            {{ talk.speaker?.user?.name ?? 'Palestrante' }} — {{ talk.title }}
                        </option>
                    </select>
                    <p v-if="!loadingTalks && talks.length === 0" class="text-xs text-(--color-text-muted) mt-2">
                        Nenhuma palestra aprovada neste evento ainda.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-(--color-text) mb-2">Formato</label>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg border transition"
                            :class="selectedFormat === 'story'
                                ? 'bg-(--color-primary) text-white border-(--color-primary)'
                                : 'bg-(--color-surface) text-(--color-text) border-(--color-border) hover:bg-gray-50 dark:hover:bg-gray-800'"
                            @click="selectedFormat = 'story'"
                        >
                            Story (1080 × 1920)
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg border transition"
                            :class="selectedFormat === 'post'
                                ? 'bg-(--color-primary) text-white border-(--color-primary)'
                                : 'bg-(--color-surface) text-(--color-text) border-(--color-border) hover:bg-gray-50 dark:hover:bg-gray-800'"
                            @click="selectedFormat = 'post'"
                        >
                            Post (1080 × 1080)
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mb-6">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-(--color-primary) text-white font-medium hover:bg-(--color-primary-hover) transition disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="generating || !canGenerate"
                        @click="generateAsset"
                    >
                        <svg v-if="generating" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        {{ generating ? 'Gerando...' : (currentAsset ? 'Gerar novamente' : 'Gerar arte') }}
                    </button>
                    <button
                        v-if="currentAsset"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-(--color-border) text-(--color-text) hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        @click="downloadAsset"
                    >
                        Baixar PNG
                    </button>
                </div>

                <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                    {{ error }}
                </div>
                <div v-if="success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                    {{ success }}
                </div>
            </div>

            <div class="bg-(--color-surface) border border-(--color-border) rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-(--color-text) mb-1">Preview</h2>
                <p v-if="generatedAtLabel" class="text-xs text-(--color-text-muted) mb-3">
                    Gerada em {{ generatedAtLabel }}
                </p>
                <div class="rounded-2xl border border-(--color-border) bg-gray-100 p-4 min-h-[420px] flex items-center justify-center" :class="{ 'mt-3': !generatedAtLabel }">
                    <img
                        v-if="currentAsset"
                        :src="currentAsset.asset_url"
                        :alt="`Arte ${selectedFormat}`"
                        class="w-full max-w-[320px] rounded-xl shadow-lg"
                    />
                    <div v-else class="text-center text-sm text-(--color-text-muted)">
                        <p>Selecione um formato e gere a arte.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
