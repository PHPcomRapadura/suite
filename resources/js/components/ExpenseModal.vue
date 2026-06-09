<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show:    { type: Boolean, required: true },
    eventId: { type: [Number, String], required: true },
    expense: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = computed(() => !!props.expense)

const CATEGORIES = [
    { value: 'alimentacao',   label: 'Alimentação' },
    { value: 'transporte',    label: 'Transporte' },
    { value: 'hospedagem',    label: 'Hospedagem' },
    { value: 'equipamentos',  label: 'Equipamentos' },
    { value: 'marketing',     label: 'Marketing e Divulgação' },
    { value: 'infraestrutura', label: 'Infraestrutura' },
    { value: 'palestrantes',  label: 'Ajuda de Custo — Palestrantes' },
    { value: 'premiacao',     label: 'Premiação e Brindes' },
    { value: 'outros',        label: 'Outros' },
]

const defaultForm = () => ({
    category:    '',
    description: '',
    amount:      '',
    date:        '',
    is_paid:     false,
    notes:       '',
})

const form            = ref(defaultForm())
const errors          = ref({})
const loading         = ref(false)
const receiptFile     = ref(null)
const receiptName     = ref(null)
const existingReceipt = ref(null)
const amountDisplay   = ref('')

const today = new Date().toISOString().split('T')[0]

function centsToDisplay(cents) {
    const intPart  = Math.floor(cents / 100)
    const decPart  = String(cents % 100).padStart(2, '0')
    return intPart.toLocaleString('pt-BR') + ',' + decPart
}

function onAmountInput(e) {
    const digits = e.target.value.replace(/\D/g, '')
    if (!digits) {
        amountDisplay.value = ''
        form.value.amount   = ''
        return
    }
    const cents         = parseInt(digits, 10)
    amountDisplay.value = centsToDisplay(cents)
    form.value.amount   = cents / 100
}

watch(() => props.show, (val) => {
    if (!val) return
    errors.value        = {}
    receiptFile.value   = null
    receiptName.value   = null

    if (props.expense) {
        const cents       = Math.round(parseFloat(props.expense.amount || 0) * 100)
        amountDisplay.value = cents ? centsToDisplay(cents) : ''
        form.value = {
            category:    props.expense.category,
            description: props.expense.description,
            amount:      cents / 100,
            date:        props.expense.date?.substring(0, 10) ?? '',
            is_paid:     !!props.expense.is_paid,
            notes:       props.expense.notes ?? '',
        }
        existingReceipt.value = props.expense.receipt_url ?? null
    } else {
        form.value            = defaultForm()
        amountDisplay.value   = ''
        existingReceipt.value = null
    }
})

function onReceiptChange(e) {
    const file = e.target.files[0]
    if (!file) return
    receiptFile.value = file
    receiptName.value = file.name
}

function clearReceipt() {
    receiptFile.value   = null
    receiptName.value   = null
    existingReceipt.value = null
}

async function submit() {
    loading.value = true
    errors.value  = {}

    const fd = new FormData()

    if (isEditing.value) fd.append('_method', 'PUT')

    fd.append('category',    form.value.category)
    fd.append('description', form.value.description)
    fd.append('amount',      form.value.amount)
    fd.append('date',        form.value.date)
    fd.append('is_paid',     form.value.is_paid ? '1' : '0')
    if (form.value.notes) fd.append('notes', form.value.notes)
    if (receiptFile.value) fd.append('receipt', receiptFile.value)

    const url = isEditing.value
        ? `/admin/api/events/${props.eventId}/expenses/${props.expense.id}`
        : `/admin/api/events/${props.eventId}/expenses`

    try {
        await axios.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        emit('saved')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {}
        }
    } finally {
        loading.value = false
    }
}

const inputClass = (field) => [
    'w-full px-3.5 py-2.5 rounded-lg border bg-(--color-surface) text-(--color-text) text-sm',
    'focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent transition',
    errors.value[field] ? 'border-(--color-danger)' : 'border-(--color-border)',
]
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40" @click="emit('close')" />

                <!-- Modal -->
                <div class="relative w-full max-w-lg bg-(--color-surface) rounded-2xl shadow-xl p-6 my-8">

                    <h2 class="text-lg font-semibold text-(--color-text) mb-6">
                        {{ isEditing ? 'Editar despesa' : 'Registrar despesa' }}
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4" novalidate>

                        <!-- Categoria -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Categoria <span class="text-(--color-danger)">*</span>
                            </label>
                            <select v-model="form.category" :class="inputClass('category')">
                                <option value="">Selecione...</option>
                                <option v-for="cat in CATEGORIES" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <p v-if="errors.category" class="mt-1 text-xs text-(--color-danger)">{{ errors.category[0] }}</p>
                        </div>

                        <!-- Descrição -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Descrição <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                maxlength="255"
                                placeholder="Ex: Coffee break — dia 1"
                                :class="inputClass('description')"
                            >
                            <p v-if="errors.description" class="mt-1 text-xs text-(--color-danger)">{{ errors.description[0] }}</p>
                        </div>

                        <!-- Valor + Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Valor (R$) <span class="text-(--color-danger)">*</span>
                                </label>
                                <input
                                    :value="amountDisplay"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="0,00"
                                    autocomplete="off"
                                    :class="inputClass('amount')"
                                    @input="onAmountInput"
                                >
                                <p v-if="errors.amount" class="mt-1 text-xs text-(--color-danger)">{{ errors.amount[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Data <span class="text-(--color-danger)">*</span>
                                </label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    :max="today"
                                    :class="inputClass('date')"
                                >
                                <p v-if="errors.date" class="mt-1 text-xs text-(--color-danger)">{{ errors.date[0] }}</p>
                            </div>
                        </div>

                        <!-- Status de pagamento -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-2">Status de pagamento</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-(--color-text)">
                                    <input type="radio" :value="false" v-model="form.is_paid" class="accent-(--color-primary)">
                                    Pendente
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-(--color-text)">
                                    <input type="radio" :value="true" v-model="form.is_paid" class="accent-(--color-primary)">
                                    Pago
                                </label>
                            </div>
                        </div>

                        <!-- Comprovante -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Comprovante <span class="text-xs text-(--color-text-muted) font-normal">(opcional — JPG, PNG, WebP ou PDF, máx. 5 MB)</span>
                            </label>

                            <!-- Arquivo existente -->
                            <div v-if="existingReceipt && !receiptFile" class="flex items-center gap-2 mb-2 p-2.5 rounded-lg border border-(--color-border) bg-(--color-bg)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                </svg>
                                <a :href="existingReceipt" target="_blank" rel="noopener" class="text-sm text-(--color-primary) hover:underline truncate flex-1">
                                    Ver comprovante atual
                                </a>
                                <button type="button" @click="clearReceipt" class="text-xs text-(--color-danger) hover:underline shrink-0">
                                    Remover
                                </button>
                            </div>

                            <!-- Arquivo selecionado -->
                            <div v-if="receiptFile" class="flex items-center gap-2 mb-2 p-2.5 rounded-lg border border-(--color-border) bg-(--color-bg)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text-muted) shrink-0" aria-hidden="true">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                </svg>
                                <span class="text-sm text-(--color-text) truncate flex-1">{{ receiptName }}</span>
                                <button type="button" @click="clearReceipt" class="text-xs text-(--color-text-muted) hover:text-(--color-danger) shrink-0">✕</button>
                            </div>

                            <!-- Input de arquivo -->
                            <label
                                v-if="!receiptFile"
                                class="flex items-center gap-2 cursor-pointer px-3.5 py-2.5 rounded-lg border border-dashed border-(--color-border)
                                       hover:border-(--color-primary) text-sm text-(--color-text-muted) hover:text-(--color-primary) transition"
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                </svg>
                                Selecionar arquivo
                                <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf" @change="onReceiptChange">
                            </label>
                            <p v-if="errors.receipt" class="mt-1 text-xs text-(--color-danger)">{{ errors.receipt[0] }}</p>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Observações <span class="text-xs text-(--color-text-muted) font-normal">(opcional)</span>
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Informações adicionais sobre a despesa..."
                                :class="inputClass('notes')"
                            />
                            <p v-if="errors.notes" class="mt-1 text-xs text-(--color-danger)">{{ errors.notes[0] }}</p>
                        </div>

                        <!-- Ações -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-(--color-text) border border-(--color-border) rounded-lg
                                       hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-60"
                                @click="emit('close')"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-white rounded-lg transition
                                       bg-(--color-primary) hover:bg-(--color-primary-hover)
                                       disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                Salvar
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from,
.modal-leave-to    { opacity: 0; }
</style>
