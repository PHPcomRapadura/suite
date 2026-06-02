<script setup>
import { ref, onMounted } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { useAuth } from '@/composables/useAuth'

const { fetchUser } = useAuth()
const sidebarOpen = ref(false)
const logoSrc = `${import.meta.env.BASE_URL}images/PHPcomRapadura_color.svg`

onMounted(fetchUser)
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-(--color-bg)">

        <!-- Sidebar desktop -->
        <AppSidebar class="hidden lg:flex" />

        <!-- Overlay mobile -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/40 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar mobile (drawer) -->
        <Transition name="sidebar-slide">
            <div v-if="sidebarOpen" class="fixed inset-y-0 left-0 z-30 lg:hidden">
                <AppSidebar @close="sidebarOpen = false" />
            </div>
        </Transition>

        <!-- Área de conteúdo -->
        <div class="flex-1 flex flex-col min-w-0 overflow-auto">

            <!-- Topbar mobile -->
            <header class="lg:hidden flex items-center h-14 px-4 border-b border-(--color-border) bg-(--color-surface) shrink-0">
                <button
                    class="text-(--color-text-muted) hover:text-(--color-text) transition mr-3"
                    aria-label="Abrir menu"
                    @click="sidebarOpen = true"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <img :src="logoSrc" alt="PHP com Rapadura" class="h-7 mx-auto">
                <div class="w-[22px]" />
            </header>

            <!-- Conteúdo da rota -->
            <main class="flex-1">
                <RouterView />
            </main>

        </div>
    </div>
</template>

<style scoped>
.sidebar-slide-enter-active,
.sidebar-slide-leave-active { transition: transform 0.2s ease; }
.sidebar-slide-enter-from,
.sidebar-slide-leave-to    { transform: translateX(-100%); }

.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to    { opacity: 0; }
</style>
