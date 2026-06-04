<script setup>
import { useRoute } from 'vue-router'
const route = useRoute()
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">

        <RouterLink
            :to="{ name: 'admin.events.show', params: { id: route.params.id } }"
            class="inline-flex items-center gap-1.5 text-sm text-(--color-text-muted) hover:text-(--color-text) transition mb-6"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Voltar para o evento
        </RouterLink>

        <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-12 text-center">
            <svg class="mx-auto mb-4 text-(--color-text-muted)" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <p class="text-lg font-semibold text-(--color-text) mb-2">Módulo CFP</p>
            <p class="text-sm text-(--color-text-muted)">Em desenvolvimento.</p>
        </div>

    </div>
</template>
