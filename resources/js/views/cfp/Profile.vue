<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import axios from 'axios'
import { useCfpAuth } from '@/composables/useCfpAuth'

const router = useRouter()
const route  = useRoute()

const { user, fetchUser } = useCfpAuth()
const loading    = ref(true)
const savingProfile  = ref(false)
const savingAccount  = ref(false)
const profileSuccess = ref('')
const accountSuccess = ref('')
const profileErrors  = ref({})
const accountErrors  = ref({})

const avatarFile    = ref(null)
const avatarPreview = ref(null)

const UFS = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
             'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO']

const profileForm = ref({
    bio:          '',
    company:      '',
    city:         '',
    state:        '',
    phone_number: '',
    website:      '',
    twitter:      '',
    github:       '',
    linkedin:     '',
    avatar_url:   null,
})

const accountForm = ref({
    name:                  '',
    email:                 '',
    current_password:      '',
    password:              '',
    password_confirmation: '',
})

const redirectFrom = route.query.redirect ?? null

onMounted(async () => {
    await fetchUser()

    if (!user.value) {
        router.push({ name: 'cfp.login', query: { redirect: '/cfp/perfil' } })
        return
    }

    if (user.value.role === 'palestrante') {
        await loadProfile()
    }

    accountForm.value.name  = user.value.name  ?? ''
    accountForm.value.email = user.value.email ?? ''

    loading.value = false
})

async function loadProfile() {
    try {
        const { data } = await axios.get('/cfp/api/speaker/profile')
        if (data.data) {
            const p = data.data
            profileForm.value = {
                bio:          p.bio          ?? '',
                company:      p.company      ?? '',
                city:         p.city         ?? '',
                state:        p.state        ?? '',
                phone_number: p.phone_number ?? '',
                website:      p.website      ?? '',
                twitter:      p.twitter      ?? '',
                github:       p.github       ?? '',
                linkedin:     p.linkedin     ?? '',
                avatar_url:   p.avatar_url   ?? null,
            }
        }
    } catch {
        // no profile yet
    }
}

function onAvatarChange(e) {
    const file = e.target.files?.[0]
    if (!file) return
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
}

function removeAvatar() {
    avatarFile.value = null
    avatarPreview.value = null
    profileForm.value.avatar_url = null
    const input = document.getElementById('avatar-input')
    if (input) input.value = ''
}

async function saveProfile() {
    savingProfile.value  = true
    profileErrors.value  = {}
    profileSuccess.value = ''

    try {
        const fd = new FormData()
        fd.append('_method', 'PATCH')
        fd.append('bio',          profileForm.value.bio)
        fd.append('company',      profileForm.value.company      ?? '')
        fd.append('city',         profileForm.value.city         ?? '')
        fd.append('state',        profileForm.value.state        ?? '')
        fd.append('phone_number', profileForm.value.phone_number ?? '')
        fd.append('website',      profileForm.value.website      ?? '')
        fd.append('twitter',      profileForm.value.twitter      ?? '')
        fd.append('github',       profileForm.value.github       ?? '')
        fd.append('linkedin',     profileForm.value.linkedin     ?? '')
        if (avatarFile.value) {
            fd.append('avatar', avatarFile.value)
        }

        const { data } = await axios.post('/cfp/api/speaker/profile', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        const newAvatar = data.data?.avatar_url ?? null
        profileForm.value.avatar_url = newAvatar
        if (user.value) user.value.avatar_url = newAvatar
        avatarFile.value    = null
        avatarPreview.value = null

        profileSuccess.value = 'Perfil salvo com sucesso!'
        if (redirectFrom) {
            setTimeout(() => router.push(redirectFrom), 800)
        }
    } catch (e) {
        if (e.response?.status === 422) {
            profileErrors.value = e.response.data.errors ?? {}
        }
    } finally {
        savingProfile.value = false
    }
}

async function saveAccount() {
    savingAccount.value  = true
    accountErrors.value  = {}
    accountSuccess.value = ''
    try {
        await axios.patch('/cfp/api/account', accountForm.value)
        accountSuccess.value = 'Dados da conta atualizados com sucesso!'
        accountForm.value.current_password      = ''
        accountForm.value.password              = ''
        accountForm.value.password_confirmation = ''
    } catch (e) {
        if (e.response?.status === 422) {
            accountErrors.value = e.response.data.errors ?? {}
        }
    } finally {
        savingAccount.value = false
    }
}
</script>

<template>
    <div class="px-6 py-8 max-w-3xl mx-auto">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-(--color-text)">Meu Perfil</h1>
            <p class="text-(--color-text-muted) text-sm mt-1">Suas informações visíveis para a comissão avaliadora.</p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-24">
            <svg class="animate-spin w-8 h-8 text-(--color-primary)" viewBox="0 0 24 24" fill="none" aria-label="Carregando">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>

        <template v-else>

            <!-- Aviso: não é palestrante -->
            <div v-if="user && user.role !== 'palestrante'" class="py-12 text-center">
                <p class="text-(--color-text-muted)">Use uma conta de palestrante para acessar o perfil.</p>
                <RouterLink :to="{ name: 'cfp.login' }" class="mt-4 inline-block text-sm text-(--color-primary) underline">Entrar com outra conta</RouterLink>
            </div>

            <div v-else class="space-y-8">

                <!-- Banner perfil incompleto -->
                <div
                    v-if="redirectFrom && !profileForm.bio"
                    class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 rounded-lg"
                    role="alert"
                >
                    ⚠️ Complete seu perfil antes de submeter uma proposta.
                    A bio é obrigatória para que a comissão possa avaliar sua candidatura.
                </div>

                <!-- Seção: Dados do palestrante -->
                <section>
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-(--color-text)">Dados do palestrante</h2>
                        <p class="text-sm text-(--color-text-muted) mt-0.5">Visíveis para a comissão avaliadora ao analisar suas propostas.</p>
                    </div>

                    <div v-if="profileSuccess" role="status" class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm px-4 py-3 rounded-lg mb-4">
                        {{ profileSuccess }}
                    </div>

                    <form @submit.prevent="saveProfile" class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 space-y-5" novalidate>

                        <!-- Avatar -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-3">Foto de perfil</label>
                            <div class="flex items-center gap-5">
                                <!-- Preview -->
                                <div class="relative shrink-0">
                                    <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 border-2 border-(--color-border) flex items-center justify-center">
                                        <img
                                            v-if="avatarPreview || profileForm.avatar_url"
                                            :src="avatarPreview || profileForm.avatar_url"
                                            alt="Avatar"
                                            class="w-full h-full object-cover"
                                        >
                                        <svg v-else class="w-9 h-9 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <button
                                        v-if="avatarPreview || profileForm.avatar_url"
                                        type="button"
                                        @click="removeAvatar"
                                        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition"
                                        aria-label="Remover foto"
                                    >
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Botão de upload -->
                                <div class="flex-1">
                                    <label
                                        for="avatar-input"
                                        class="inline-flex items-center gap-2 px-4 py-2 border border-(--color-border) rounded-lg text-sm text-(--color-text-muted) hover:text-(--color-text) cursor-pointer transition bg-(--color-surface)"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        {{ avatarPreview || profileForm.avatar_url ? 'Trocar foto' : 'Enviar foto' }}
                                    </label>
                                    <input
                                        id="avatar-input"
                                        type="file"
                                        accept="image/*"
                                        class="sr-only"
                                        :disabled="savingProfile"
                                        @change="onAvatarChange"
                                    >
                                    <p class="text-xs text-(--color-text-muted) mt-1.5">JPG, PNG ou WebP · máx. 2 MB</p>
                                    <p v-if="profileErrors.avatar" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ profileErrors.avatar[0] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div>
                            <div class="flex justify-between mb-1.5">
                                <label class="text-sm font-medium text-(--color-text)">
                                    Bio <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <span class="text-xs text-(--color-text-muted)">{{ profileForm.bio.length }}/1000</span>
                            </div>
                            <textarea
                                v-model="profileForm.bio"
                                rows="4"
                                maxlength="1000"
                                :disabled="savingProfile"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text) resize-y"
                                :class="profileErrors.bio ? 'border-red-400' : 'border-(--color-border)'"
                                placeholder="Conte um pouco sobre você..."
                            ></textarea>
                            <p v-if="profileErrors.bio" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ profileErrors.bio[0] }}</p>
                        </div>

                        <!-- Empresa -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Empresa / Organização</label>
                            <input
                                v-model="profileForm.company"
                                type="text"
                                maxlength="255"
                                :disabled="savingProfile"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                placeholder="Empresa onde você trabalha"
                            >
                        </div>

                        <!-- Cidade + UF -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Cidade</label>
                                <input
                                    v-model="profileForm.city"
                                    type="text"
                                    maxlength="255"
                                    :disabled="savingProfile"
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                    placeholder="Fortaleza"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">UF</label>
                                <select
                                    v-model="profileForm.state"
                                    :disabled="savingProfile"
                                    class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                    :class="profileErrors.state ? 'border-red-400' : 'border-(--color-border)'"
                                >
                                    <option value="">—</option>
                                    <option v-for="uf in UFS" :key="uf" :value="uf">{{ uf }}</option>
                                </select>
                                <p v-if="profileErrors.state" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ profileErrors.state[0] }}</p>
                            </div>
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Telefone</label>
                            <input
                                v-model="profileForm.phone_number"
                                type="tel"
                                maxlength="20"
                                :disabled="savingProfile"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                placeholder="+55 85 99999-9999"
                            >
                        </div>

                        <!-- Site -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Site pessoal</label>
                            <input
                                v-model="profileForm.website"
                                type="url"
                                :disabled="savingProfile"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                :class="profileErrors.website ? 'border-red-400' : 'border-(--color-border)'"
                                placeholder="https://seusite.com.br"
                            >
                            <p v-if="profileErrors.website" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ profileErrors.website[0] }}</p>
                        </div>

                        <!-- Redes sociais -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">Twitter / X</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-(--color-border) bg-gray-50 dark:bg-gray-800 text-sm text-(--color-text-muted)">@</span>
                                    <input
                                        v-model="profileForm.twitter"
                                        type="text"
                                        maxlength="100"
                                        :disabled="savingProfile"
                                        class="flex-1 min-w-0 px-3 py-2.5 rounded-r-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                        placeholder="usuario"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">GitHub</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-(--color-border) bg-gray-50 dark:bg-gray-800 text-sm text-(--color-text-muted)">@</span>
                                    <input
                                        v-model="profileForm.github"
                                        type="text"
                                        maxlength="100"
                                        :disabled="savingProfile"
                                        class="flex-1 min-w-0 px-3 py-2.5 rounded-r-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                        placeholder="usuario"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-(--color-text) mb-1.5">LinkedIn</label>
                                <input
                                    v-model="profileForm.linkedin"
                                    type="text"
                                    maxlength="255"
                                    :disabled="savingProfile"
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                    placeholder="linkedin.com/in/usuario"
                                >
                            </div>
                        </div>

                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="savingProfile"
                                class="px-5 py-2.5 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="savingProfile" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                {{ savingProfile ? 'Salvando...' : 'Salvar perfil' }}
                            </button>
                        </div>

                    </form>
                </section>

                <!-- Seção: Dados da conta -->
                <section>
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-(--color-text)">Dados da conta</h2>
                        <p class="text-sm text-(--color-text-muted) mt-0.5">Atualize seu nome, e-mail ou senha.</p>
                    </div>

                    <div v-if="accountSuccess" role="status" class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm px-4 py-3 rounded-lg mb-4">
                        {{ accountSuccess }}
                    </div>

                    <form @submit.prevent="saveAccount" class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 space-y-5" novalidate>

                        <!-- Nome -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Nome <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                v-model="accountForm.name"
                                type="text"
                                maxlength="255"
                                :disabled="savingAccount"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                :class="accountErrors.name ? 'border-red-400' : 'border-(--color-border)'"
                            >
                            <p v-if="accountErrors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ accountErrors.name[0] }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                E-mail <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                v-model="accountForm.email"
                                type="email"
                                maxlength="255"
                                :disabled="savingAccount"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                :class="accountErrors.email ? 'border-red-400' : 'border-(--color-border)'"
                            >
                            <p v-if="accountErrors.email" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ accountErrors.email[0] }}</p>
                        </div>

                        <hr class="border-(--color-border)">
                        <p class="text-xs text-(--color-text-muted) -mt-3">Preencha os campos abaixo apenas se quiser alterar sua senha.</p>

                        <!-- Nova senha -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Nova senha</label>
                            <input
                                v-model="accountForm.password"
                                type="password"
                                autocomplete="new-password"
                                :disabled="savingAccount"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                :class="accountErrors.password ? 'border-red-400' : 'border-(--color-border)'"
                                placeholder="Mínimo 8 caracteres"
                            >
                            <p v-if="accountErrors.password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ accountErrors.password[0] }}</p>
                        </div>

                        <!-- Confirmar senha -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">Confirmar nova senha</label>
                            <input
                                v-model="accountForm.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                :disabled="savingAccount"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-(--color-border) text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                            >
                        </div>

                        <hr class="border-(--color-border)">

                        <!-- Senha atual -->
                        <div>
                            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
                                Senha atual <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                v-model="accountForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                :disabled="savingAccount"
                                class="w-full px-3.5 py-2.5 rounded-lg border text-sm transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:border-transparent disabled:opacity-60 bg-(--color-surface) text-(--color-text)"
                                :class="accountErrors.current_password ? 'border-red-400' : 'border-(--color-border)'"
                                placeholder="Obrigatória para confirmar qualquer alteração"
                            >
                            <p v-if="accountErrors.current_password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ accountErrors.current_password[0] }}</p>
                        </div>

                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="savingAccount"
                                class="px-5 py-2.5 bg-(--color-primary) hover:bg-(--color-primary-hover) text-white text-sm font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-(--color-primary) focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="savingAccount" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                {{ savingAccount ? 'Salvando...' : 'Salvar dados da conta' }}
                            </button>
                        </div>

                    </form>
                </section>

            </div>
        </template>
    </div>
</template>
