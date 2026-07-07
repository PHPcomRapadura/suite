<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const loading = ref(true)
const generating = ref(false)
const event = ref(null)
const selectedFormat = ref('story')
const assets = reactive({ story: null, post: null })
const error = ref('')
const success = ref('')

const title = computed(() => {
    if (!event.value) return 'Artes de Divulgação'
    return `Artes para ${event.value.name}`
})

const currentAsset = computed(() => assets[selectedFormat.value])

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
        assets.story = response.data.data.assets?.story ?? null
        assets.post = response.data.data.assets?.post ?? null
    } catch (e) {
        error.value = 'Não foi possível carregar os dados do evento.'
    } finally {
        loading.value = false
    }
}

async function generateAsset() {
    generating.value = true
    error.value = ''
    success.value = ''

    try {
        const response = await axios.post(`/admin/api/events/${route.params.id}/social-assets/generate`, {
            format: selectedFormat.value,
        })

        assets[selectedFormat.value] = response.data.data
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
    link.download = `${route.params.id}-${selectedFormat.value}.png`
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

                <div class="flex flex-wrap gap-3 mb-6">
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

                <div class="flex flex-wrap gap-3 mb-6">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-(--color-primary) text-white font-medium hover:bg-(--color-primary-hover) transition"
                        :disabled="generating"
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
