import { ref, watchEffect } from 'vue'

const isDark = ref(document.documentElement.classList.contains('dark'))

function toggle() {
    isDark.value = !isDark.value
}

watchEffect(() => {
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('admin-theme', isDark.value ? 'dark' : 'light')
})

export function useTheme() {
    return { isDark, toggle }
}
