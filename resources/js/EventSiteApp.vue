<script setup>
import { defineAsyncComponent } from 'vue'

const props = defineProps({
    data: { type: Object, required: true },
})

const layouts = {
    1: defineAsyncComponent(() => import('./views/event-site/Layout1.vue')),
    2: defineAsyncComponent(() => import('./views/event-site/Layout2.vue')),
    3: defineAsyncComponent(() => import('./views/event-site/Layout3.vue')),
}

const Layout = layouts[props.data.site?.layout] ?? layouts[1]

function onLayoutReady() {
    const loader = document.getElementById('page-loader')
    if (!loader) return
    loader.classList.add('hidden')
    setTimeout(() => loader.remove(), 400)
}
</script>

<template>
    <Suspense @resolve="onLayoutReady">
        <component
            :is="Layout"
            :event="data.event"
            :site="data.site"
            :sponsors="data.sponsors"
            :schedule="data.schedule"
        />
    </Suspense>
</template>
