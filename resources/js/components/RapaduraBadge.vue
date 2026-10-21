<script setup>
defineProps({
    tone: { type: String, default: 'auto' },
})
</script>

<template>
    <a
        :class="['rpd-badge', `rpd-badge--${tone}`]"
        href="https://rapadura.net"
        target="_blank"
        rel="noopener"
    >
        <svg class="rpd-badge__mark" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
            <rect width="64" height="64" rx="15" fill="#e8884a"/>
            <path d="M23 48V18M23 30c0-9 7-12 18-12" fill="none" stroke="#2b1a10" stroke-width="10" stroke-linecap="round"/>
        </svg>
        <span class="rpd-badge__text">
            <span class="rpd-badge__eyebrow">Powered by</span>
            <span class="rpd-badge__name">Rapadura Network</span>
        </span>
    </a>
</template>
