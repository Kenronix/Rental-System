<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { EnvelopeIcon, LockClosedIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import api from '../../services/api.js'
import { useAuth } from '../../composables/useAuth.js'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const router = useRouter()
const { checkAuth } = useAuth()
const userType = ref('landlord')
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

const selectUserType = (type) => {
  userType.value = type
  errorMessage.value = '' // Clear error when switching user type
}

const handleLogin = async () => {
  // Validate inputs
  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter both email and password.'
    return
  }

  errorMessage.value = ''
  isLoading.value = true

  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value,
      userType: userType.value,
    })

    if (response.data.success) {
      // Update auth state
      await checkAuth()
      
      // Login successful - route based on user type
      if (response.data.userType === 'landlord') {
        router.push('/landlord/dashboard')
      } else if (response.data.userType === 'tenant') {
        router.push('/tenant/dashboard')
      }
      emit('close')
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

const handleGoogleLogin = () => {
  // Handle Google login logic here
  console.log('Google login')
  // For now, just show a message
  errorMessage.value = 'Google login is not yet implemented.'
}

const closeModal = () => {
  errorMessage.value = ''
  email.value = ''
  password.value = ''
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <!-- Close button -->
          <button class="close-button" @click="closeModal">
            <XMarkIcon class="close-icon" />
          </button>
          
          <!-- Title -->
          <h2 class="modal-title">Login</h2>
          
          <!-- User Type Selection -->
          <div class="user-type-selector">
            <button 
              :class="['user-type-btn', { active: userType === 'landlord' }]"
              @click="selectUserType('landlord')"
            >
              Landlord
            </button>
            <button 
              :class="['user-type-btn', { active: userType === 'tenant' }]"
              @click="selectUserType('tenant')"
            >
              Tenant
            </button>
          </div>
          
         
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
          
          <!-- OR Separator -->
          <div class="separator">
            <span class="separator-line"></span>
            <span class="separator-text">OR</span>
            <span class="separator-line"></span>
          </div>
          
          <!-- Google Login Button -->
          <button class="google-login-btn" @click="handleGoogleLogin">
            <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 40px;
  width: 90%;
  max-width: 450px;
  position: relative;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  font-family: 'Montserrat', sans-serif;
}

.close-button {
  position: absolute;
  top: 15px;
  right: 15px;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.close-icon {
  width: 24px;
  height: 24px;
}

.close-button:hover {
  color: #333;
}

.modal-title {
  font-size: 32px;
  font-weight: 700;
  color: #000;
  margin: 0 0 30px 0;
  text-align: center;
}

.user-type-selector {
  display: flex;
  gap: 12px;
  margin-bottom: 30px;
}

.user-type-btn {
  flex: 1;
  padding: 12px 24px;
  border: 2px solid #1500FF;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s;
  background: white;
  color: #1500FF;
}

.user-type-btn.active {
  background: #1500FF;
  color: white;
}

.user-type-btn:hover:not(.active) {
  background: #f5f5f5;
}

.info-message {
  background: #e6f0ff;
  border: 1px solid #b3d9ff;
  color: #0066cc;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
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

.separator {
  display: flex;
  align-items: center;
  margin: 24px 0;
  gap: 12px;
}

.separator-line {
  flex: 1;
  height: 1px;
  background: #ddd;
}

.separator-text {
  color: #666;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
}

.google-login-btn {
  width: 100%;
  padding: 14px;
  background: white;
  color: #333;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  transition: all 0.3s;
}

.google-login-btn:hover {
  background: #f9f9f9;
  border-color: #bbb;
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.google-login-btn:active {
  transform: translateY(0);
}

.google-icon {
  width: 20px;
  height: 20px;
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s, opacity 0.3s;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9);
  opacity: 0;
}
</style>
