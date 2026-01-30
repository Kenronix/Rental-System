<template>
  <div class="admin-login-container" :style="{ backgroundImage: `url(${landingPageImage})` }">
    <div class="logo-container">
      <img :src="tahananLogo" alt="Tahanan Logo" class="tahanan-logo">
      <img :src="tahananLogoName" alt="Tahanan Logo Name" class="tahanan-logo-name">
    </div>

    <div class="login-box">
      <div class="form-header">
        <h1>Admin Portal</h1>
        <p>Login to manage the system</p>
      </div>
      
      <form @submit.prevent="handleLogin">
        <div class="input-group">
          <label class="input-label">Admin Email</label>
          <div class="input-wrapper">
            <EnvelopeIcon class="input-icon" />
            <input 
              type="email" 
              v-model="email" 
              class="input-field"
              required 
              placeholder="Enter your admin email"
            />
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Password</label>
          <div class="input-wrapper">
            <LockClosedIcon class="input-icon" />
            <input 
              type="password" 
              v-model="password" 
              class="input-field"
              required 
              placeholder="Enter your password"
            />
          </div>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="login-submit-btn">
          {{ loading ? 'Authenticating...' : 'Sign In to Admin' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useAuth } from '@/composables/useAuth';
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import landingPageImage from '@/images/landingPage.png';
import tahananLogo from '@/images/tahananLogo.png';
import tahananLogoName from '@/images/tahananLogoName.png';

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();
const { checkAuth } = useAuth();

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value,
      userType: 'landlord'
    });

    if (response.data.success && response.data.user && response.data.user.is_admin) {
      await checkAuth();
      router.push({ name: 'AdminDashboard' });
    } else {
      error.value = 'Access denied. You do not have admin privileges.';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid credentials. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.admin-login-container {
  width: 100vw;
  height: 100vh;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Montserrat', sans-serif;
  position: relative;
}

.logo-container {
  position: absolute;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 10;
}

.tahanan-logo {
  width: 80px;
  height: auto;
  padding: 10px;
  margin-left: 20px;
  margin-top: 20px;
}

.tahanan-logo-name {
  width: auto;
  height: auto;
  max-height: 50px;
  margin-top: 30px;
}

.login-box {
  background: white;
  padding: 2.5rem;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  width: 100%;
  max-width: 450px;
  z-index: 10;
}

.form-header {
  text-align: center;
  margin-bottom: 2rem;
}

.form-header h1 {
  font-size: 1.8rem;
  color: #000;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.form-header p {
  color: #666;
  font-size: 0.9rem;
  margin: 0;
}

.input-group {
  margin-bottom: 1.5rem;
}

.input-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
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
  margin-top: 1rem;
}

.login-submit-btn:hover {
  background: #0f00cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
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
  text-align: center;
}
</style>
