<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show:  { type: Boolean, required: true },
    event: { type: Object,  default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = computed(() => !!props.event)

const defaultForm = () => ({
    name:           '',
    slug:           '',
    edition:        '',
    description:    '',
    starts_at:      '',
    ends_at:        '',
    is_online:      false,
    location:       '',
    max_attendees:  '',
})

const form          = ref(defaultForm())
const errors        = ref({})
const loading       = ref(false)
const slugManual    = ref(false)

const coverFile     = ref(null)
const logoFile      = ref(null)
const coverPreview  = ref(null)
const logoPreview   = ref(null)
const removeCover   = ref(false)
const removeLogo    = ref(false)

function toLocalDatetime(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

watch(() => props.show, (val) => {
    if (!val) return
    errors.value      = {}
    coverFile.value   = null
    logoFile.value    = null
    coverPreview.value = null
    logoPreview.value  = null
    removeCover.value = false
    removeLogo.value  = false
    slugManual.value  = false

    if (props.event) {
        form.value = {
            name:          props.event.name,
            slug:          props.event.slug,
            edition:       props.event.edition ?? '',
            description:   props.event.description ?? '',
            starts_at:     toLocalDatetime(props.event.starts_at),
            ends_at:       toLocalDatetime(props.event.ends_at),
            is_online:     !!props.event.is_online,
            location:      props.event.location ?? '',
            max_attendees: props.event.max_attendees ?? '',
        }
    } else {
        form.value = defaultForm()
    }
})

watch(() => form.value.name, (name) => {
    if (isEditing.value || slugManual.value) return
    form.value.slug = name
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
})

function onSlugInput() {
    slugManual.value = true
}

function onCoverChange(e) {
    const file = e.target.files[0]
    if (!file) return
    coverFile.value   = file
    removeCover.value = false
    coverPreview.value = URL.createObjectURL(file)
}

function onLogoChange(e) {
    const file = e.target.files[0]
    if (!file) return
    logoFile.value   = file
    removeLogo.value = false
    logoPreview.value = URL.createObjectURL(file)
}

function clearCover() {
    coverFile.value    = null
    coverPreview.value = null
    removeCover.value  = true
}

function clearLogo() {
    logoFile.value    = null
    logoPreview.value = null
    removeLogo.value  = true
}

async function submit() {
    loading.value = true
    errors.value  = {}

    const hasFiles = coverFile.value || logoFile.value

    try {
        if (isEditing.value) {
            const fd = new FormData()
            fd.append('_method', 'PUT')
            Object.entries(form.value).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') fd.append(k, v)
            })
            if (form.value.is_online === false) fd.set('is_online', '0')
            if (form.value.is_online === true)  fd.set('is_online', '1')
            if (coverFile.value)   fd.append('cover_image', coverFile.value)
            if (removeCover.value) fd.append('remove_cover', '1')
            if (logoFile.value)    fd.append('logo', logoFile.value)
            if (removeLogo.value)  fd.append('remove_logo', '1')

            await axios.post(`/admin/api/events/${props.event.id}`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        } else if (hasFiles) {
            const fd = new FormData()
            Object.entries(form.value).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') fd.append(k, v)
            })
            if (form.value.is_online === false) fd.set('is_online', '0')
            if (form.value.is_online === true)  fd.set('is_online', '1')
            if (coverFile.value) fd.append('cover_image', coverFile.value)
            if (logoFile.value)  fd.append('logo', logoFile.value)

            await axios.post('/admin/api/events', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        } else {
            await axios.post('/admin/api/events', form.value)
        }

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
    'focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 transition',
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
                <div class="relative w-full max-w-2xl bg-(--color-surface) rounded-2xl shadow-xl p-6 my-8">

                    <h2 class="text-lg font-semibold text-(--color-text) mb-6">
                        {{ isEditing ? 'Editar evento' : 'Novo evento' }}
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4" novalidate>

                        <!-- Nome -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Nome do evento <span class="text-(--color-danger)">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                :disabled="loading"
                                :class="inputClass('name')"
                                placeholder="Ex: PHP com Rapadura 2026"
                            >
                            <p v-if="errors.name" class="mt-1 text-xs text-(--color-danger)">{{ errors.name[0] }}</p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Slug
                                <span class="text-(--color-text-muted) font-normal ml-1">(auto-gerado)</span>
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                :disabled="loading"
                                :class="inputClass('slug')"
                                placeholder="php-com-rapadura-2026"
                                @input="onSlugInput"
                            >
                            <p v-if="errors.slug" class="mt-1 text-xs text-(--color-danger)">{{ errors.slug[0] }}</p>
                        </div>

                        <!-- Edição + Capacidade -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Edição</label>
                                <input
                                    v-model="form.edition"
                                    type="number"
                                    min="1"
                                    max="999"
                                    :disabled="loading"
                                    :class="inputClass('edition')"
                                    placeholder="Ex: 3"
                                >
                                <p v-if="errors.edition" class="mt-1 text-xs text-(--color-danger)">{{ errors.edition[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Capacidade máxima</label>
                                <input
                                    v-model="form.max_attendees"
                                    type="number"
                                    min="1"
                                    :disabled="loading"
                                    :class="inputClass('max_attendees')"
                                    placeholder="Ex: 300"
                                >
                                <p v-if="errors.max_attendees" class="mt-1 text-xs text-(--color-danger)">{{ errors.max_attendees[0] }}</p>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Descrição</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                :disabled="loading"
                                :class="inputClass('description')"
                                placeholder="Chamada do evento, detalhes, informações importantes…"
                            />
                            <p v-if="errors.description" class="mt-1 text-xs text-(--color-danger)">{{ errors.description[0] }}</p>
                        </div>

                        <!-- Início + Fim -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                    Data/hora de início <span class="text-(--color-danger)">*</span>
                                </label>
                                <input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    :disabled="loading"
                                    :class="inputClass('starts_at')"
                                >
                                <p v-if="errors.starts_at" class="mt-1 text-xs text-(--color-danger)">{{ errors.starts_at[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Data/hora de encerramento</label>
                                <input
                                    v-model="form.ends_at"
                                    type="datetime-local"
                                    :disabled="loading"
                                    :class="inputClass('ends_at')"
                                >
                                <p v-if="errors.ends_at" class="mt-1 text-xs text-(--color-danger)">{{ errors.ends_at[0] }}</p>
                            </div>
                        </div>

                        <!-- Toggle online + Local -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    :aria-label="form.is_online ? 'Desativar evento online' : 'Ativar evento online'"
                                    :class="['relative inline-flex h-5 w-9 items-center rounded-full transition shrink-0',
                                             form.is_online ? 'bg-(--color-primary)' : 'bg-gray-300 dark:bg-gray-600']"
                                    @click="form.is_online = !form.is_online"
                                >
                                    <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition',
                                                   form.is_online ? 'translate-x-4.5' : 'translate-x-0.5']"/>
                                </button>
                                <span class="text-sm text-(--color-text)">Evento online</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Local</label>
                                <input
                                    v-model="form.location"
                                    type="text"
                                    :disabled="loading || form.is_online"
                                    :class="inputClass('location')"
                                    placeholder="Ex: Centro de Convenções, Fortaleza — CE"
                                >
                                <p v-if="errors.location" class="mt-1 text-xs text-(--color-danger)">{{ errors.location[0] }}</p>
                            </div>
                        </div>

                        <!-- Imagem de capa -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Imagem de capa
                                <span class="text-(--color-text-muted) font-normal ml-1">jpg/jpeg/png/webp, máx. 5 MB</span>
                            </label>

                            <div v-if="coverPreview || (isEditing && event?.cover_image && !removeCover)" class="mb-2 relative inline-block">
                                <img
                                    :src="coverPreview ?? event?.cover_image"
                                    alt="Preview da capa"
                                    class="h-24 rounded-lg object-cover border border-(--color-border)"
                                >
                                <button
                                    type="button"
                                    @click="clearCover"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition"
                                    aria-label="Remover imagem de capa"
                                >✕</button>
                            </div>

                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                :disabled="loading"
                                class="block w-full text-sm text-(--color-text-muted) file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0
                                       file:text-sm file:font-medium file:bg-(--color-primary) file:text-white
                                       hover:file:bg-(--color-primary-hover) file:cursor-pointer file:transition disabled:opacity-60"
                                @change="onCoverChange"
                            >
                            <p v-if="errors.cover_image" class="mt-1 text-xs text-(--color-danger)">{{ errors.cover_image[0] }}</p>
                        </div>

                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Logo do evento
                                <span class="text-(--color-text-muted) font-normal ml-1">jpg/jpeg/png/webp/svg, máx. 2 MB</span>
                            </label>

                            <div v-if="logoPreview || (isEditing && event?.logo && !removeLogo)" class="mb-2 relative inline-block">
                                <img
                                    :src="logoPreview ?? event?.logo"
                                    alt="Preview do logo"
                                    class="h-16 rounded-lg object-contain border border-(--color-border) p-1 bg-white"
                                >
                                <button
                                    type="button"
                                    @click="clearLogo"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition"
                                    aria-label="Remover logo"
                                >✕</button>
                            </div>

                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                :disabled="loading"
                                class="block w-full text-sm text-(--color-text-muted) file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0
                                       file:text-sm file:font-medium file:bg-(--color-primary) file:text-white
                                       hover:file:bg-(--color-primary-hover) file:cursor-pointer file:transition disabled:opacity-60"
                                @change="onLogoChange"
                            >
                            <p v-if="errors.logo" class="mt-1 text-xs text-(--color-danger)">{{ errors.logo[0] }}</p>
                        </div>

                        <!-- Ações -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-(--color-text) bg-(--color-surface) border border-(--color-border)
                                       rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-60"
                                @click="emit('close')"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-white bg-(--color-primary) hover:bg-(--color-primary-hover)
                                       rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
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
