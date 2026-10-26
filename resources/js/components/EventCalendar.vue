<script setup>
import { nextTick, onMounted, onUnmounted, ref, useId } from 'vue'

defineProps({
    event: { type: Object, required: true },
    light: { type: Boolean, default: false },
})

const open = ref(false)
const container = ref(null)
const trigger = ref(null)
const panel = ref(null)
const panelId = useId()

const options = [
    { id: 'google', name: 'Google Agenda' },
    { id: 'apple', name: 'Apple Calendário' },
    { id: 'microsoft', name: 'Microsoft Outlook' },
]

function close(restoreFocus = false) {
    open.value = false
    if (restoreFocus) trigger.value?.focus()
}

async function toggle() {
    open.value = !open.value
    if (open.value) {
        await nextTick()
        panel.value?.querySelector('a')?.focus()
    }
}

function onPointerDown(event) {
    if (!container.value?.contains(event.target)) close()
}

function onFocusOut(event) {
    if (event.relatedTarget && !container.value?.contains(event.relatedTarget)) close()
}

onMounted(() => document.addEventListener('pointerdown', onPointerDown))
onUnmounted(() => document.removeEventListener('pointerdown', onPointerDown))
</script>

<template>
    <div v-if="event.calendar" ref="container" class="event-calendar" :class="{ 'event-calendar--light': light }" @keydown.esc.stop.prevent="close(true)" @focusout="onFocusOut">
        <button ref="trigger" type="button" class="event-calendar__trigger" :aria-expanded="open" :aria-controls="open ? panelId : undefined" @click="toggle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M16 3v4M8 3v4M3 11h18m-9 3v4m-2-2h4" />
            </svg>
            Adicionar ao calendário
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path :d="open ? 'm6 15 6-6 6 6' : 'm6 9 6 6 6-6'" />
            </svg>
        </button>
        <nav v-if="open" :id="panelId" ref="panel" class="event-calendar__panel" aria-label="Opções para adicionar ao calendário">
            <a v-for="option in options" :key="option.id" :href="event.calendar[option.id]" :target="option.id === 'apple' ? '_self' : '_blank'" rel="noopener noreferrer" class="event-calendar__option" :type="option.id === 'apple' ? 'text/calendar' : undefined">
                <span class="event-calendar__icon" aria-hidden="true">
                    <svg v-if="option.id === 'google'" viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="10" fill="#f3f6fc" />
                        <path d="M8 5h24a3 3 0 0 1 3 3v19l-8 8H8a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z" fill="#fff" />
                        <path d="M8 5h24a3 3 0 0 1 3 3v5H5V8a3 3 0 0 1 3-3Z" fill="#4285f4" />
                        <path d="M5 13h8v22H8a3 3 0 0 1-3-3V13Z" fill="#1a73e8" />
                        <path d="M27 13h8v14h-8V13Z" fill="#fbbc04" />
                        <path d="M13 27h14v8H13v-8Z" fill="#34a853" />
                        <path d="M27 27h8l-8 8v-8Z" fill="#ea4335" />
                        <path d="M16 18a2.2 2.2 0 0 1 4.3.6c0 1.1-.7 1.8-1.8 2 1.3.1 2 1 2 2.1a2.4 2.4 0 0 1-4.7.6m7-4.7 2-1.2V25" stroke="#1a73e8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg v-else-if="option.id === 'apple'" viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="10" fill="#f5f5f7" />
                        <path d="M25.1 10.2c1.1-1.3 1.8-3 1.6-4.7-1.6.1-3.5 1.1-4.6 2.4-1 1.1-1.9 2.9-1.7 4.5 1.8.1 3.6-.9 4.7-2.2Zm4.1 12.1c0-3 2.4-4.5 2.5-4.6-1.4-2.1-3.6-2.4-4.4-2.4-1.9-.2-3.7 1.1-4.7 1.1-1 0-2.4-1.1-4-1.1-2.1 0-4 1.2-5.1 3-2.2 3.8-.6 9.5 1.6 12.6 1 1.5 2.2 3.1 3.8 3 1.5-.1 2.1-1 3.9-1 1.8 0 2.3 1 3.9.9 1.7 0 2.7-1.5 3.7-3 1.2-1.7 1.7-3.4 1.7-3.5-.1 0-2.9-1.1-2.9-5Z" fill="#111" transform="translate(-2 -1)" />
                    </svg>
                    <svg v-else viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="10" fill="#eef6fc" />
                        <rect x="15" y="7" width="19" height="21" rx="2" fill="#0078d4" />
                        <path d="M18 11h6v6h-6z" fill="#50d9ff" />
                        <path d="M25 11h6v6h-6z" fill="#28a8ea" />
                        <path d="M18 18h6v6h-6z" fill="#28a8ea" />
                        <path d="M25 18h6v6h-6z" fill="#0364b8" />
                        <path d="M12 19h24v12a2 2 0 0 1-2 2H14a2 2 0 0 1-2-2V19Z" fill="#1490df" />
                        <path d="m12 19 12 9 12-9" fill="#0a6fc2" />
                        <path d="m12 33 11-8 13 8" fill="#28a8ea" />
                        <rect x="4" y="13" width="18" height="20" rx="2" fill="#0061a8" />
                        <ellipse cx="13" cy="23" rx="4.2" ry="5.3" stroke="#fff" stroke-width="2.3" />
                    </svg>
                </span>
                {{ option.name }}
            </a>
        </nav>
    </div>
</template>

<style scoped>
.event-calendar {
    position: relative;
    display: inline-block;
    max-width: 100%;
    text-align: left;
}

.event-calendar__trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    max-width: 100%;
    min-height: 44px;
    padding: 10px 14px;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    color: var(--color-text);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.event-calendar--light .event-calendar__trigger {
    border-color: rgb(255 255 255 / 40%);
    color: #fff;
}

.event-calendar__trigger:hover {
    background: rgb(127 127 127 / 12%);
}

.event-calendar__panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 50%;
    z-index: 40;
    width: min(270px, calc(100vw - 32px));
    padding: 8px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    color: var(--color-text);
    box-shadow: 0 8px 32px rgb(0 0 0 / 20%);
    transform: translateX(-50%);
}

.event-calendar__option {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 48px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
}

.event-calendar__option:hover {
    background: var(--color-bg);
}

.event-calendar__icon,
.event-calendar__icon svg {
    display: block;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
}

.event-calendar__trigger:focus-visible,
.event-calendar__option:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 3px;
}
</style>
