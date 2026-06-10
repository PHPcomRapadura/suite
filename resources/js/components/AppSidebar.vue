<script setup>
import { ref, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useTheme } from '@/composables/useTheme'
import ChangePasswordModal from '@/components/ChangePasswordModal.vue'

const emit = defineEmits(['close'])

const route = useRoute()
const { user, logout } = useAuth()
const { isDark, toggle } = useTheme()

const collapsed = ref(localStorage.getItem('sidebar_collapsed') === '1')
const showPasswordModal = ref(false)

function toggleCollapse() {
    collapsed.value = !collapsed.value
    localStorage.setItem('sidebar_collapsed', collapsed.value ? '1' : '0')
}

const logoExpanded  = '/images/phpcomrapadura_branca.svg?v=2'
const logoCollapsed = '/images/favicon.png'

const navItems = computed(() => [
    { name: 'admin.dashboard', label: 'Dashboard',    icon: 'grid',     roles: ['admin', 'colaborador'] },
    { name: 'admin.events',    label: 'Eventos',      icon: 'calendar', roles: ['admin', 'colaborador'] },
    { name: 'admin.speakers',  label: 'Palestrantes', icon: 'mic',      roles: ['admin', 'colaborador'] },
    { name: 'admin.users',     label: 'Usuários',     icon: 'users',    roles: ['admin'] },
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
    <aside
        :class="[
            'h-screen flex flex-col bg-(--color-sidebar-bg) border-r border-(--color-sidebar-border) shrink-0',
            'transition-[width] duration-200 ease-in-out overflow-hidden',
            collapsed ? 'w-[68px]' : 'w-[260px]',
        ]"
    >
        <!-- Logo -->
        <div :class="['flex px-3 bg-(--color-sidebar-logo-bg) shrink-0 gap-2', collapsed ? 'flex-col items-center py-4 h-auto' : 'items-center h-16']">

            <!-- Expandido: logo colorida + botão collapse -->
            <template v-if="!collapsed">
                <img
                    :src="logoExpanded"
                    alt="PHP com Rapadura"
                    style="width: 155px; height: auto; display: block; margin-top: 8px;"
                    onerror="this.style.display='none'"
                >
                <!-- Botão fechar (mobile) -->
                <button
                    class="lg:hidden text-(--color-sidebar-text) hover:text-(--color-sidebar-text-active) transition shrink-0"
                    aria-label="Fechar menu"
                    @click="emit('close')"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                <!-- Botão collapse (desktop) -->
                <button
                    class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-(--color-sidebar-text) hover:text-(--color-sidebar-text-active) hover:bg-(--color-sidebar-hover) transition shrink-0 ml-auto"
                    aria-label="Recolher menu"
                    @click="toggleCollapse"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
            </template>

            <!-- Colapsado: favicon + botão expand -->
            <template v-else>
                <div class="flex flex-col items-center gap-1 w-full">
                    <img
                        :src="logoCollapsed"
                        alt="PHP com Rapadura"
                        class="w-9 h-9 rounded-lg object-contain"
                        onerror="this.style.display='none'"
                    >
                    <button
                        class="flex items-center justify-center w-7 h-7 rounded-md text-(--color-sidebar-text) hover:text-(--color-sidebar-text-active) hover:bg-(--color-sidebar-hover) transition"
                        aria-label="Expandir menu"
                        @click="toggleCollapse"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Navegação -->
        <nav class="flex-1 flex flex-col gap-1 p-2 overflow-y-auto">
            <RouterLink
                v-for="item in navItems"
                :key="item.name"
                :to="{ name: item.name }"
                :title="collapsed ? item.label : undefined"
                :class="[
                    'flex items-center rounded-lg min-h-[40px] text-sm transition',
                    collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5',
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
                <!-- Ícone mic (Palestrantes) -->
                <svg v-else-if="item.icon === 'mic'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
                <!-- Ícone users (Usuários) -->
                <svg v-else-if="item.icon === 'users'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>

                <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
            </RouterLink>
        </nav>

        <!-- Rodapé -->
        <div class="p-2 border-t border-(--color-sidebar-border) flex flex-col gap-1">

            <!-- Usuário logado -->
            <div
                :class="[
                    'flex items-center px-2 py-2',
                    collapsed ? 'justify-center' : 'gap-3',
                ]"
            >
                <div class="w-9 h-9 rounded-full bg-(--color-primary) flex items-center justify-center text-white font-semibold text-sm shrink-0">
                    {{ initials(user?.name ?? '') }}
                </div>
                <div v-if="!collapsed" class="min-w-0">
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
                :title="collapsed ? (isDark ? 'Modo claro' : 'Modo escuro') : undefined"
                :class="[
                    'flex items-center rounded-lg min-h-[40px] text-sm transition w-full',
                    collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5 text-left',
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
                <span v-if="!collapsed">{{ isDark ? 'Modo claro' : 'Modo escuro' }}</span>
            </button>

            <!-- Alterar senha -->
            <button
                :title="collapsed ? 'Alterar senha' : undefined"
                :class="[
                    'flex items-center rounded-lg min-h-[40px] text-sm transition w-full',
                    collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5 text-left',
                    'text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover) hover:text-(--color-sidebar-text-active)',
                ]"
                @click="showPasswordModal = true"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span v-if="!collapsed">Alterar senha</span>
            </button>

            <!-- Logout -->
            <button
                :title="collapsed ? 'Sair' : undefined"
                :class="[
                    'flex items-center rounded-lg min-h-[40px] text-sm transition w-full',
                    collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5 text-left',
                    'text-(--color-sidebar-text) hover:text-red-400 hover:bg-red-500/10',
                ]"
                @click="logout"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span v-if="!collapsed">Sair</span>
            </button>
        </div>

        <ChangePasswordModal :show="showPasswordModal" @close="showPasswordModal = false" />
    </aside>
</template>
