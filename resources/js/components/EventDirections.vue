<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, useId } from 'vue'

const props = defineProps({
    event: { type: Object, required: true },
    color: { type: String, default: '#025c98' },
})

const open = ref(false)
const container = ref(null)
const trigger = ref(null)
const panel = ref(null)
const panelId = useId()
const fallbackApp = ref(null)
const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
const isAndroid = /Android/i.test(navigator.userAgent)
const isMobile = isIOS || isAndroid
const address = computed(() => (props.event.location ?? '').trim())
const available = computed(() => !props.event.is_online && address.value !== '')

const apps = computed(() => {
    const query = encodeURIComponent(address.value)
    const uber = new URL('https://m.uber.com/ul/')
    uber.search = new URLSearchParams({
        action: 'setPickup',
        pickup: 'my_location',
        'dropoff[formatted_address]': address.value,
        'dropoff[nickname]': address.value,
    }).toString()

    const maps = new URL('https://www.google.com/maps/search/')
    maps.search = new URLSearchParams({ api: '1', query: address.value }).toString()

    const waze = new URL('https://waze.com/ul')
    waze.search = new URLSearchParams({ q: address.value, utm_source: 'phpcomrapadura' }).toString()

    const nativeUber = `uber://riderequest?pickup=my_location&dropoff[formatted_address]=${query}&dropoff[nickname]=${query}`
    const destinations = [
        { id: 'uber', name: 'Uber', webHref: uber.href, nativeHref: nativeUber, androidHref: nativeUber, package: 'com.ubercab' },
        { id: 'maps', name: 'Google Maps', webHref: maps.href, nativeHref: `comgooglemaps://?q=${query}`, androidHref: `geo:0,0?q=${query}`, package: 'com.google.android.apps.maps' },
        { id: 'waze', name: 'Waze', webHref: waze.href, nativeHref: `waze://?q=${query}&utm_source=phpcomrapadura`, androidHref: `waze://?q=${query}&utm_source=phpcomrapadura`, package: 'com.waze' },
    ]

    return destinations.map(app => {
        let href = app.webHref
        if (isIOS) href = app.nativeHref
        if (isAndroid) {
            const [scheme, ...path] = app.androidHref.split(':')
            href = `intent:${path.join(':')}#Intent;scheme=${scheme};package=${app.package};S.browser_fallback_url=${encodeURIComponent(app.webHref)};end`
        }
        return { ...app, href }
    })
})

function close(restoreFocus = false) {
    open.value = false
    if (restoreFocus) trigger.value?.focus()
}

async function toggle() {
    open.value = !open.value
    if (open.value) {
        fallbackApp.value = null
        await nextTick()
        panel.value?.querySelector('a')?.focus()
    }
}

function onPointerDown(event) {
    if (!container.value?.contains(event.target)) close()
}

function onFocusOut(event) {
    // Ao alternar para outro app, o iOS pode retirar o foco sem indicar outro elemento.
    if (!event.relatedTarget) return
    if (!container.value?.contains(event.relatedTarget)) close()
}

onMounted(() => document.addEventListener('pointerdown', onPointerDown))
onUnmounted(() => document.removeEventListener('pointerdown', onPointerDown))
</script>

<template>
    <div
        v-if="available"
        ref="container"
        class="event-directions"
        :style="{ '--directions-color': color }"
        @keydown.esc.stop.prevent="close(true)"
        @focusout="onFocusOut"
    >
        <div v-if="open" :id="panelId" ref="panel" class="event-directions__panel">
            <p class="event-directions__heading">Como chegar</p>
            <p class="event-directions__address">{{ address }}</p>
            <nav aria-label="Aplicativos para chegar ao evento" class="event-directions__apps">
                <a
                    v-for="app in apps"
                    :key="app.id"
                    :href="app.href"
                    :aria-label="`Abrir ${app.name} com o endereço do evento`"
                    :target="isMobile ? '_self' : '_blank'"
                    rel="noopener noreferrer"
                    class="event-directions__app"
                    @click="fallbackApp = isMobile ? app : null"
                >
                    <span class="event-directions__icon" aria-hidden="true">
                        <svg v-if="app.id === 'uber'" viewBox="0 0 40 40" fill="none">
                            <rect width="40" height="40" rx="10" fill="#000" />
                            <text x="20" y="25" fill="#fff" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" letter-spacing="-1">Uber</text>
                        </svg>
                        <svg v-else-if="app.id === 'maps'" viewBox="0 0 40 40" fill="none">
                            <rect width="40" height="40" rx="10" fill="#f3f6fc" />
                            <path d="M20 5a11 11 0 0 0-11 11c0 8 11 19 11 19s11-11 11-19A11 11 0 0 0 20 5Z" fill="#34a853" />
                            <path d="M12.2 8.2A11 11 0 0 0 9 16c0 3 1.5 6.5 3.4 9.5L25.8 6.7A11 11 0 0 0 20 5l-7.8 3.2Z" fill="#4285f4" />
                            <path d="m12.2 8.2 7.8 7.8 5.8-9.3A11 11 0 0 0 12.2 8.2Z" fill="#ea4335" />
                            <path d="m20 16-7.6 9.5c1.3 2.1 2.8 4.1 4.2 5.8L31 16h-11Z" fill="#fbbc04" />
                            <circle cx="20" cy="16" r="4" fill="#fff" />
                        </svg>
                        <svg v-else viewBox="0 0 40 40" fill="none">
                            <rect width="40" height="40" rx="10" fill="#33ccff" />
                            <path d="M12 28c-3 0-5-2-5-5 3 1 3-2 3-5a12 12 0 1 1 12 12H12Z" fill="#fff" stroke="#162b36" stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="15" cy="30" r="3" fill="#162b36" />
                            <circle cx="27" cy="30" r="3" fill="#162b36" />
                            <circle cx="18" cy="17" r="1.4" fill="#162b36" />
                            <circle cx="27" cy="17" r="1.4" fill="#162b36" />
                            <path d="M18 22c2 3 6 3 8 0" stroke="#162b36" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span>{{ app.name }}</span>
                    <svg class="event-directions__external" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3h7v7m0-7L10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5" />
                    </svg>
                </a>
            </nav>
            <a v-if="fallbackApp" :href="fallbackApp.webHref" class="event-directions__fallback">
                Não abriu? Usar {{ fallbackApp.name }} no navegador
            </a>
        </div>
        <button
            ref="trigger"
            type="button"
            class="event-directions__trigger"
            :aria-expanded="open"
            :aria-controls="open ? panelId : undefined"
            @click="toggle"
        >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path v-if="open" d="m6 6 12 12M6 18 18 6" />
                <template v-else>
                    <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z" />
                    <circle cx="12" cy="10" r="2.5" />
                </template>
            </svg>
            <span>{{ open ? 'Fechar' : 'Como chegar' }}</span>
        </button>
    </div>
</template>

<style scoped>
.event-directions {
    position: fixed;
    right: max(24px, env(safe-area-inset-right));
    bottom: calc(84px + env(safe-area-inset-bottom));
    z-index: 60;
}

.event-directions__trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    margin-left: auto;
    padding: 12px 18px;
    border: 1px solid rgb(255 255 255 / 20%);
    border-radius: 999px;
    background: var(--directions-color);
    color: #fff;
    font: 500 14px/1.4 var(--font-sans);
    box-shadow: 0 4px 20px rgb(0 0 0 / 20%);
    cursor: pointer;
}

.event-directions__trigger:hover {
    filter: brightness(1.1);
}

.event-directions__panel {
    position: absolute;
    right: 0;
    bottom: calc(100% + 12px);
    width: min(320px, calc(100vw - 48px));
    max-height: calc(100dvh - 168px - env(safe-area-inset-bottom));
    overflow-y: auto;
    padding: 20px;
    border: 1px solid var(--color-border);
    border-radius: 18px;
    background: var(--color-surface);
    color: var(--color-text);
    box-shadow: 0 12px 48px rgb(0 0 0 / 20%);
}

.event-directions__heading {
    font-size: 17px;
    font-weight: 600;
}

.event-directions__address {
    margin-top: 6px;
    color: var(--color-text-muted);
    font-size: 13px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.event-directions__apps {
    display: grid;
    gap: 4px;
    margin-top: 16px;
}

.event-directions__app {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 8px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
}

.event-directions__app:hover {
    background: var(--color-bg);
}

.event-directions__icon,
.event-directions__icon svg {
    display: block;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
}

.event-directions__external {
    margin-left: auto;
    color: var(--color-text-muted);
}

.event-directions__fallback {
    display: block;
    margin-top: 12px;
    padding-block: 10px;
    color: var(--color-text-muted);
    font-size: 12px;
    line-height: 1.6;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.event-directions__trigger:focus-visible,
.event-directions__app:focus-visible,
.event-directions__fallback:focus-visible {
    outline: 3px solid var(--directions-color);
    outline-offset: 4px;
}
</style>
