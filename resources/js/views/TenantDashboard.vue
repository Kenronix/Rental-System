<template>
  <div class="dashboard-layout">
    <div class="main-content">
      <div class="header">
        <h1>Tenant Dashboard</h1>
        <button class="logout-btn" @click="handleLogout">
          <ArrowRightOnRectangleIcon class="logout-icon" />
          <span>Logout</span>
        </button>
      </div>
      <p>Welcome to your tenant dashboard!</p>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { logout: authLogout } = useAuth()

const handleLogout = async () => {
  await authLogout()
  router.push('/')
}
</script>
<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  padding: 40px;
  background: #F0F0F0;
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.header h1 {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.logout-btn:hover {
  background: #b91c1c;
}

.logout-icon {
  width: 20px;
  height: 20px;
}
</style>

