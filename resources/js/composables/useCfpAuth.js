import { ref } from 'vue'
import axios from 'axios'

const user   = ref(null)
const loaded = ref(false)

export function useCfpAuth() {
    async function fetchUser(force = false) {
        if (loaded.value && !force) return
        try {
            const { data } = await axios.get('/cfp/api/me')
            user.value = data.user
        } catch {
            user.value = null
        } finally {
            loaded.value = true
        }
    }

    async function logout() {
        await axios.post('/cfp/logout').catch(() => {})
        user.value  = null
        loaded.value = false
    }

    return { user, loaded, fetchUser, logout }
}
