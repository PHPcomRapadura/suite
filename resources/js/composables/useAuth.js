import { ref } from 'vue'
import axios from 'axios'

const user = ref(null)

async function fetchUser() {
    const { data } = await axios.get('/admin/api/me')
    user.value = data
}

async function logout() {
    await axios.post('/admin/logout')
    user.value = null
    window.location.href = '/admin/login'
}

export function useAuth() {
    return { user, fetchUser, logout }
}
