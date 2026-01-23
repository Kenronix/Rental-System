import { ref, computed } from 'vue'
import api from '../services/api.js'

// Shared state (singleton pattern)
const user = ref(null)
const userType = ref(null)
const isAuthenticated = ref(false)
const isLoading = ref(false)

const checkAuth = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/user')
    if (response.data.authenticated) {
      user.value = response.data.user
      userType.value = response.data.userType
      isAuthenticated.value = true
    } else {
      user.value = null
      userType.value = null
      isAuthenticated.value = false
    }
  } catch (error) {
    user.value = null
    userType.value = null
    isAuthenticated.value = false
  } finally {
    isLoading.value = false
  }
}

const logout = async () => {
  try {
    await api.post('/logout')
    user.value = null
    userType.value = null
    isAuthenticated.value = false
  } catch (error) {
    console.error('Logout error:', error)
  }
}

export function useAuth() {
  const isLandlord = computed(() => userType.value === 'landlord')
  const isTenant = computed(() => userType.value === 'tenant')

  return {
    user,
    userType,
    isAuthenticated,
    isLoading,
    isLandlord,
    isTenant,
    checkAuth,
    logout,
  }
}
