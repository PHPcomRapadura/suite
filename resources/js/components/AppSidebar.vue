<script setup>
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useTheme } from '@/composables/useTheme'

const emit = defineEmits(['close'])

const route = useRoute()
const { user, logout } = useAuth()
const { isDark, toggle } = useTheme()

const logoSrc = `${import.meta.env.BASE_URL}images/phpcomrapadura_branca.svg`

const navItems = computed(() => [
    { name: 'admin.dashboard', label: 'Dashboard', icon: 'grid',     roles: ['admin', 'colaborador'] },
    { name: 'admin.events',    label: 'Eventos',   icon: 'calendar', roles: ['admin', 'colaborador'] },
    { name: 'admin.users',     label: 'Usuários',  icon: 'users',    roles: ['admin'] },
].filter(item => !user.value || item.roles.includes(user.value.role)))

const roleLabel = { admin: 'Administrador', colaborador: 'Colaborador', palestrante: 'Palestrante' }

function isActive(name) {
    return route.name === name
}

function initials(name) {
    if (!name) return '?'
    return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}
</script>

<template>
    <aside class="w-[260px] h-screen flex flex-col bg-(--color-sidebar-bg) border-r border-(--color-sidebar-border) shrink-0">

        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-5 bg-(--color-sidebar-logo-bg) shrink-0">
            <img :src="logoSrc" alt="PHP com Rapadura" class="h-7" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'" >
            <span class="hidden items-center text-white font-semibold text-sm">PHP com Rapadura</span>

            <!-- Botão fechar (mobile) -->
            <button
                class="lg:hidden ml-auto text-(--color-sidebar-text) hover:text-(--color-sidebar-text-active) transition"
                aria-label="Fechar menu"
                @click="emit('close')"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Navegação -->
        <nav class="flex-1 flex flex-col gap-1 p-3 overflow-y-auto">
            <RouterLink
                v-for="item in navItems"
                :key="item.name"
                :to="{ name: item.name }"
                :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg min-h-[40px] text-sm transition',
                    isActive(item.name)
                        ? 'bg-(--color-sidebar-active) text-(--color-sidebar-text-active) font-medium'
                        : 'text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover) hover:text-(--color-sidebar-text-active)',
                ]"
                @click="emit('close')"
            >
                <!-- Ícone grid (Dashboard) -->
                <svg v-if="item.icon === 'grid'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                </svg>
                <!-- Ícone calendar (Eventos) -->
                <svg v-else-if="item.icon === 'calendar'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <!-- Ícone users (Usuários) -->
                <svg v-else-if="item.icon === 'users'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                {{ item.label }}
            </RouterLink>
        </nav>

        <!-- Rodapé -->
        <div class="p-3 border-t border-(--color-sidebar-border) flex flex-col gap-1">

            <!-- Usuário logado -->
            <div class="flex items-center gap-3 px-3 py-2.5">
                <div class="w-9 h-9 rounded-full bg-(--color-primary) flex items-center justify-center text-white font-semibold text-sm shrink-0">
                    {{ initials(user?.name ?? '') }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-(--color-sidebar-text-active) truncate leading-tight">
                        {{ user?.name ?? '...' }}
                    </p>
                    <p class="text-xs text-(--color-sidebar-text) truncate leading-tight mt-0.5">
                        {{ roleLabel[user?.role] ?? '' }}
                    </p>
                </div>
            </div>

            <!-- Toggle de tema -->
            <button
                :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg min-h-[40px] text-sm transition w-full text-left',
                    'text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover) hover:text-(--color-sidebar-text-active)',
                ]"
                @click="toggle"
            >
                <!-- Ícone sol -->
                <svg v-if="isDark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <!-- Ícone lua -->
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                {{ isDark ? 'Modo claro' : 'Modo escuro' }}
            </button>

            <!-- Logout -->
            <button
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg min-h-[40px] text-sm transition w-full text-left
                       text-(--color-sidebar-text) hover:text-red-400 hover:bg-red-500/10"
                @click="logout"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sair
            </button>
        </div>
    </aside>
</template>
