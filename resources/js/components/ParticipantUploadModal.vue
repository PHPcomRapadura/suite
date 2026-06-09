<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />
        <div class="relative bg-(--color-surface) rounded-xl shadow-xl w-full max-w-lg" role="dialog" aria-modal="true" aria-labelledby="upload-modal-title">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-(--color-border)">
                <h2 id="upload-modal-title" class="text-base font-semibold text-(--color-text)">Importar participantes</h2>
                <button @click="$emit('close')" class="text-(--color-text-muted) hover:text-(--color-text) transition" aria-label="Fechar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 flex flex-col gap-4">
                <!-- Resultado -->
                <template v-if="result">
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-medium text-(--color-success)">✅ Importação concluída</p>
                        <p class="text-sm text-(--color-text)">{{ result.imported }} novo{{ result.imported !== 1 ? 's' : '' }} · {{ result.updated }} atualizado{{ result.updated !== 1 ? 's' : '' }}</p>

                        <div v-if="result.errors.length > 0" class="mt-1">
                            <p class="text-sm font-medium text-(--color-warning)">⚠ {{ result.errors.length }} linha{{ result.errors.length !== 1 ? 's' : '' }} com erro:</p>
                            <ul class="mt-1 text-xs text-(--color-text-muted) list-disc list-inside space-y-0.5">
                                <li v-for="err in result.errors" :key="err">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </template>

                <!-- Formulário -->
                <template v-else>
                    <div>
                        <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                            Arquivo CSV <span class="text-(--color-danger)">*</span>
                            <span class="text-(--color-text-muted) font-normal">(exportado do Sympla)</span>
                        </label>

                        <!-- Drop zone -->
                        <div
                            class="border-2 border-dashed border-(--color-border) rounded-lg p-6 text-center cursor-pointer transition hover:border-(--color-primary)"
                            :class="{ 'border-(--color-primary) bg-blue-50 dark:bg-blue-950/20': isDragging }"
                            @click="fileInput?.click()"
                            @dragover.prevent="isDragging = true"
                            @dragleave="isDragging = false"
                            @drop.prevent="onDrop"
                        >
                            <svg class="mx-auto mb-2 text-(--color-text-muted)" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p v-if="selectedFile" class="text-sm font-medium text-(--color-text)">{{ selectedFile.name }}</p>
                            <p v-else class="text-sm text-(--color-text-muted)">Clique ou arraste o arquivo aqui</p>
                            <input ref="fileInput" type="file" accept=".csv,text/csv,text/plain" class="hidden" @change="onFileChange" />
                        </div>

                        <p v-if="fileError" class="mt-1.5 text-xs text-(--color-danger)">{{ fileError }}</p>
                    </div>

                    <div class="text-xs text-(--color-text-muted) bg-(--color-bg) rounded-lg p-3 space-y-1">
                        <p class="font-medium">Colunas importadas:</p>
                        <p>Ordem de inscrição, Nome, Sobrenome, Email, Tipo de ingresso, Valor, Data compra, Estado de pagamento, Check-in, Cupom de Desconto, Método de pagamento.</p>
                        <p>Delimitador: <span class="font-mono font-semibold text-(--color-text)">;</span> (ponto e vírgula) — padrão do export do Sympla.</p>
                        <p>Re-uploads atualizam os dados existentes.</p>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 p-5 border-t border-(--color-border)">
                <template v-if="result">
                    <button @click="onClose" class="px-4 py-2 text-sm rounded-lg border border-(--color-border) text-(--color-text) hover:bg-(--color-bg) transition">
                        Fechar
                    </button>
                </template>
                <template v-else>
                    <button @click="$emit('close')" class="px-4 py-2 text-sm rounded-lg border border-(--color-border) text-(--color-text) hover:bg-(--color-bg) transition" :disabled="loading">
                        Cancelar
                    </button>
                    <button @click="submit" :disabled="loading || !selectedFile" class="px-4 py-2 text-sm rounded-lg bg-(--color-primary) text-white hover:bg-(--color-primary-hover) disabled:opacity-50 transition flex items-center gap-2">
                        <svg v-if="loading" class="animate-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        {{ loading ? 'Importando…' : 'Importar' }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({ eventId: { type: [String, Number], required: true } })
const emit = defineEmits(['close', 'saved'])

const fileInput = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const fileError = ref('')
const loading = ref(false)
const result = ref(null)

function onFileChange(e) {
    setFile(e.target.files[0])
}

function onDrop(e) {
    isDragging.value = false
    setFile(e.dataTransfer.files[0])
}

function setFile(file) {
    fileError.value = ''
    if (!file) return
    if (!file.name.match(/\.csv$/i) && file.type !== 'text/csv' && file.type !== 'text/plain') {
        fileError.value = 'O arquivo deve ser um CSV.'
        return
    }
    selectedFile.value = file
}

async function submit() {
    if (!selectedFile.value) return
    loading.value = true
    fileError.value = ''

    const form = new FormData()
    form.append('csv', selectedFile.value)

    try {
        const res = await axios.post(`/admin/api/events/${props.eventId}/participants/upload`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        result.value = res.data
    } catch (err) {
        const msg = err.response?.data?.errors?.csv?.[0] || err.response?.data?.message || 'Erro ao importar o arquivo.'
        fileError.value = msg
    } finally {
        loading.value = false
    }
}

function onClose() {
    emit('saved')
    emit('close')
}
</script>
