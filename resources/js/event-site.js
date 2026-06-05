import { createApp } from 'vue'
import EventSiteApp from './EventSiteApp.vue'

const rawData = JSON.parse(document.getElementById('event-site-data').textContent)

createApp(EventSiteApp, { data: rawData }).mount('#event-site-app')
