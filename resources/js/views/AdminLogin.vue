<template>
  <div class="admin-login-container">
    <div class="login-card">
      <h1 class="login-title">Admin Login</h1>
      
      <!-- Email Input -->
      <div class="input-group">
        <label class="input-label">Email Address</label>
        <div class="input-wrapper">
          <EnvelopeIcon class="input-icon" />
          <input 
            v-model="email"
            type="email" 
            class="input-field" 
            placeholder="Enter your email address"
          />
        </div>
      </div>
      
      <!-- Password Input -->
      <div class="input-group">
        <label class="input-label">Password</label>
        <div class="input-wrapper">
          <LockClosedIcon class="input-icon" />
          <input 
            v-model="password"
            type="password" 
            class="input-field" 
            placeholder="Enter your password"
          />
        </div>
      </div>
      
      <!-- Error Message -->
      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>
      
      <!-- Forgot Password Link -->
      <div class="forgot-password">
        <a href="#" class="forgot-link">Forgot Password?</a>
      </div>
      
      <!-- Login Button -->
      <button 
        class="login-submit-btn" 
        @click="handleLogin"
        :disabled="isLoading"
      >
        <span v-if="!isLoading">Login</span>
        <span v-else>Logging in...</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline'
import api from '../services/api.js'
import { useAuth } from '../composables/useAuth.js'
import landingPageImage from '../images/landingPage.png'

const router = useRouter()
const { checkAuth } = useAuth()
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  // Validate inputs
  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter both email and password.'
    return
  }

  errorMessage.value = ''
  isLoading.value = true

  try {
    const response = await api.post('/admin/login', {
      email: email.value,
      password: password.value,
    })

    if (response.data.success) {
      // Update auth state
      await checkAuth()
      
      // Login successful - route to admin dashboard
      router.push('/admin/dashboard')
    }
  } catch (error) {
    // Handle error
    if (error.response && error.response.data) {
      const errors = error.response.data.errors || error.response.data
      if (errors.email) {
        errorMessage.value = Array.isArray(errors.email) ? errors.email[0] : errors.email
      } else if (errors.message) {
        errorMessage.value = errors.message
      } else {
        errorMessage.value = 'Login failed. Please check your credentials.'
      }
    } else {
      errorMessage.value = 'An error occurred. Please try again.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.admin-login-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-image: url('../images/landingPage.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 16px;
  padding: 40px;
  width: 90%;
  max-width: 450px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  font-family: 'Montserrat', sans-serif;
}

.login-title {
  font-size: 32px;
  font-weight: 700;
  color: #000;
  margin: 0 0 30px 0;
  text-align: center;
}

.input-group {
  margin-bottom: 24px;
}

.input-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  font-family: 'Montserrat', sans-serif;
}

.input-wrapper {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #ddd;
  padding-bottom: 8px;
  transition: border-color 0.3s;
}

.input-wrapper:focus-within {
  border-bottom-color: #1500FF;
}

.input-icon {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  color: #666;
  flex-shrink: 0;
}

.input-field {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  padding: 8px 0;
  color: #333;
}

.input-field::placeholder {
  color: #999;
}

.forgot-password {
  text-align: right;
  margin-bottom: 24px;
}

.forgot-link {
  color: #666;
  font-size: 14px;
  text-decoration: none;
  font-family: 'Montserrat', sans-serif;
  transition: color 0.2s;
}

.forgot-link:hover {
  color: #1500FF;
}

.login-submit-btn {
  width: 100%;
  padding: 16px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s;
  margin-bottom: 24px;
}

.login-submit-btn:hover {
  background: #0f00cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
}

.login-submit-btn:active {
  transform: translateY(0);
}

.login-submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.error-message {
  background: #fee;
  border: 1px solid #fcc;
  color: #c33;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
  text-align: center;
}
</style>

