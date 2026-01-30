<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  HomeIcon,
  BuildingOfficeIcon,
  CreditCardIcon,
  ChartBarIcon,
  UserGroupIcon,
  Cog6ToothIcon,
  UserCircleIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/solid'
import { BellIcon } from '@heroicons/vue/24/outline'
import tahananLogo from '../../images/tahananLogo.png'
import tahananLogoName from '../../images/tahananLogoName.png'
import sidebarBg from '../../images/Sidebarbg.png'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../services/api.js'

const route = useRoute()
const router = useRouter()
const { logout: authLogout, user } = useAuth()

const showSettingsMenu = ref(false)
const showNotificationsMenu = ref(false)
const settingsMenuRef = ref(null)
const notificationsMenuRef = ref(null)
const notifications = ref([]) // TODO: Property Manager notifications
const unreadCount = ref(0)
const isLoadingNotifications = ref(false)

const navigationItems = [
  { name: 'Dashboard', icon: HomeIcon, path: '/property-manager/dashboard' },
  { name: 'Properties', icon: BuildingOfficeIcon, path: '/property-manager/properties' },
  { name: 'Landlords', icon: UserGroupIcon, path: '/property-manager/landlords' },
  { name: 'Payments', icon: CreditCardIcon, path: '/property-manager/payments' },
  { name: 'Reports', icon: ChartBarIcon, path: '/property-manager/reports' }
]

const isActive = (path) => {
  return route.path === path
}

const toggleSettingsMenu = () => {
  showSettingsMenu.value = !showSettingsMenu.value
  if (showSettingsMenu.value) {
    showNotificationsMenu.value = false
  }
}

const toggleNotificationsMenu = () => {
  showNotificationsMenu.value = !showNotificationsMenu.value
  if (showNotificationsMenu.value) {
    showSettingsMenu.value = false
    // fetchNotifications() // Implement if notifications endpoint exists
  }
}

const handleAccountSettings = () => {
  router.push('/property-manager/settings')
  showSettingsMenu.value = false
}

const handleLogout = async () => {
  showSettingsMenu.value = false
  await authLogout()
  router.push('/property-manager/login')
}

const handleClickOutside = (event) => {
  if (settingsMenuRef.value && !settingsMenuRef.value.contains(event.target)) {
    showSettingsMenu.value = false
  }
  if (notificationsMenuRef.value && !notificationsMenuRef.value.contains(event.target)) {
    showNotificationsMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="sidebar" :style="{ backgroundImage: `url(${sidebarBg})` }">
    <!-- Logo Section -->
    <div class="logo-section">
      <img :src="tahananLogo" alt="Tahanan Logo" class="logo-icon" />
      <img :src="tahananLogoName" alt="Tahanan Logo Name" class="logo-name" />
    </div>

    <!-- Navigation Links -->
    <nav class="navigation">
      <router-link
        v-for="item in navigationItems"
        :key="item.name"
        :to="item.path"
        :class="['nav-item', { active: isActive(item.path) }]"
      >
        <component :is="item.icon" class="nav-icon" />
        <span class="nav-text">{{ item.name }}</span>
      </router-link>
    </nav>

    <!-- User Profile Section -->
    <div class="user-profile">
      <div class="avatar">{{ user ? user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : 'PM' }}</div>
      <div class="user-info">
        <div class="user-name">{{ user ? user.name : 'Property Manager' }}</div>
        <div class="user-role">Property Manager</div>
      </div>
      <div class="actions-container">
        <!-- Notifications -->
        <div class="notifications-container" ref="notificationsMenuRef">
          <div class="notification-icon-wrapper" @click="toggleNotificationsMenu">
            <BellIcon class="notification-icon" />
            <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
          </div>
          <Transition name="dropdown">
            <div v-if="showNotificationsMenu" class="notifications-menu">
              <div class="notifications-header">
                <h3>Notifications</h3>
                <!-- <button v-if="unreadCount > 0" class="mark-all-read-btn" @click="markAllAsRead">Mark all as read</button> -->
              </div>
              <div class="notifications-list">
                 <div class="notification-empty">No new notifications</div>
              </div>
            </div>
          </Transition>
        </div>
        
        <!-- Settings -->
        <div class="settings-container" ref="settingsMenuRef">
          <Cog6ToothIcon class="settings-icon" @click="toggleSettingsMenu" />
          <Transition name="dropdown">
            <div v-if="showSettingsMenu" class="settings-menu">
              <button class="menu-item" @click="handleAccountSettings">
                <UserCircleIcon class="menu-icon" />
                <span>Account Settings</span>
              </button>
              <button class="menu-item" @click="handleLogout">
                <ArrowRightOnRectangleIcon class="menu-icon" />
                <span>Logout</span>
              </button>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.sidebar {
  width: 300px;
  height: 100vh;
  background-size: cover;
  background-position: left center;
  background-repeat: no-repeat;
  position: fixed;
  left: 0;
  top: 0;
  display: flex;
  flex-direction: column;
  font-family: 'Montserrat', sans-serif;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 10;
}

/* Logo Section */
.logo-section {
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  z-index: 2;
  position: relative;
}

.logo-icon {
  width: 65px;
  height: auto;
  filter: brightness(0) invert(1);
}

.logo-name {
  width: auto;
  height: auto;
  max-height: 40px;
  filter: brightness(0) invert(1);
}

/* Navigation */
.navigation {
  flex: 1;
  padding: 20px 0;
  z-index: 2;
  position: relative;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 24px;
  margin: 0 12px 8px 12px;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.nav-item.active {
  background: rgba(0, 0, 199, 0.8);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.nav-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
}

.nav-text {
  font-size: 16px;
  font-weight: 500;
}

/* User Profile Section */
.user-profile {
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  z-index: 2;
  position: relative;
  background: rgba(0, 0, 0, 0.2);
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #1500FF;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 16px;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  color: white;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  font-weight: 400;
}

.actions-container {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.notifications-container {
  position: relative;
}

.notification-icon-wrapper {
  position: relative;
  cursor: pointer;
}

.notification-icon {
  width: 20px;
  height: 20px;
  color: white;
  transition: color 0.2s;
}

.notification-icon:hover {
  color: rgba(255, 255, 255, 0.8);
}

.notification-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  border: 2px solid rgba(0, 0, 0, 0.2);
}

.notifications-menu {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 10px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  width: 320px;
  max-height: 400px;
  overflow: hidden;
  z-index: 1000;
}

.notifications-header {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.notifications-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.notifications-list {
  max-height: 300px;
  overflow-y: auto;
}

.notification-empty {
  padding: 20px;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}

.settings-container {
  position: relative;
}

.settings-icon {
  width: 20px;
  height: 20px;
  color: white;
  cursor: pointer;
  transition: color 0.2s;
}

.settings-icon:hover {
  color: rgba(255, 255, 255, 0.8);
}

.settings-menu {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 10px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  overflow: hidden;
  z-index: 1000;
}

.menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  transition: background-color 0.2s;
}

.menu-item:hover {
  background: #f5f5f5;
}

.menu-item:first-child {
  border-bottom: 1px solid #e5e5e5;
}

.menu-icon {
  width: 18px;
  height: 18px;
  color: #666;
}

/* Dropdown Transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
