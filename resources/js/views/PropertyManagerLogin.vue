<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import api from '../services/api.js'
import tahananLogo from '../images/tahananLogo.png'
import tahananLogoName from '../images/tahananLogoName.png'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const { checkAuth } = useAuth()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const error = ref('')
const showPassword = ref(false)

const handleLogin = async () => {
  if (!email.value || !password.value) {
    error.value = 'Please enter both email and password'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value,
      userType: 'property_manager'
    })

    if (response.data.success) {
      await checkAuth()
      router.push('/property-manager/dashboard')
    } else {
      error.value = response.data.message || 'Login failed'
    }
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Invalid credentials'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logos">
        <img :src="tahananLogo" alt="Tahanan Logo" class="logo-icon" />
        <img :src="tahananLogoName" alt="Tahanan Logo Name" class="logo-name" />
      </div>
      
      <h2 class="title">Property Manager Login</h2>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input 
            type="email" 
            id="email" 
            v-model="email" 
            placeholder="Enter your email" 
            required
            class="input-field"
          />
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              v-model="password" 
              placeholder="Enter your password" 
              required
              class="input-field"
            />
             <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                <EyeIcon v-if="!showPassword" class="icon" />
                <EyeSlashIcon v-else class="icon" />
            </button>
          </div>
        </div>
        
        <div v-if="error" class="error-message">{{ error }}</div>
        
        <button type="submit" class="submit-btn" :disabled="isLoading">
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  font-family: 'Montserrat', sans-serif;
  background-image: url('../images/landingPage.png'); /* Reuse landing page bg if available or generic */
  background-size: cover;
  background-position: center;
}

.login-card {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 400px;
  text-align: center;
}

.logos {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
}

.logo-icon {
  width: 50px;
  height: auto;
}

.logo-name {
  height: 30px;
  width: auto;
}

.title {
  color: #111827;
  font-size: 24px;
  margin-bottom: 32px;
  font-weight: 700;
}

.login-form {
  text-align: left;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #374151;
  font-weight: 500;
  font-size: 14px;
}

.input-field {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
  transition: border-color 0.2s;
}

.input-field:focus {
  outline: none;
  border-color: #1500FF;
  box-shadow: 0 0 0 3px rgba(21, 0, 255, 0.1);
}

.password-wrapper {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
}

.icon {
  width: 20px;
  height: 20px;
}

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-bottom: 20px;
  text-align: center;
  background: #fee2e2;
  padding: 8px;
  border-radius: 6px;
}

.submit-btn {
  width: 100%;
  padding: 12px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  font-family: inherit;
}

.submit-btn:hover {
  background: #1200e6;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
