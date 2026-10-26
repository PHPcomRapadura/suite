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
    { id: 'google', name: 'Google Agenda', color: '#4285f4', label: 'G' },
    { id: 'apple', name: 'Apple Calendário', color: '#e34850', label: 'A' },
    { id: 'microsoft', name: 'Microsoft Outlook', color: '#0078d4', label: 'O' },
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
                <span class="event-calendar__icon" :style="{ backgroundColor: option.color }" aria-hidden="true">{{ option.label }}</span>
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

.event-calendar__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 7px;
    color: #fff;
    font-weight: 600;
}

.event-calendar__trigger:focus-visible,
.event-calendar__option:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 3px;
}
</style>
