<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  Squares2X2Icon, 
  BuildingOffice2Icon, 
  UsersIcon, 
  ChartBarIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon,
  UserCircleIcon
} from '@heroicons/vue/24/solid';
import tahananLogo from '@/images/tahananLogo.png';
import tahananLogoName from '@/images/tahananLogoName.png';
import sidebarBg from '@/images/Sidebarbg.png';
import { useAuth } from '@/composables/useAuth.js';

const route = useRoute();
const router = useRouter();
const currentRoute = computed(() => route.name);
const { logout: authLogout } = useAuth(); // Assuming useAuth provides user info, or we mock it for admin

const showSettingsMenu = ref(false);
const settingsMenuRef = ref(null);

const isActive = (routeName) => {
  return currentRoute.value === routeName;
};

const toggleSettingsMenu = () => {
  showSettingsMenu.value = !showSettingsMenu.value;
};

const handleLogout = async () => {
  showSettingsMenu.value = false;
  await authLogout();
  router.push('/portal-admin/login');
};

const handleClickOutside = (event) => {
  if (settingsMenuRef.value && !settingsMenuRef.value.contains(event.target)) {
    showSettingsMenu.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="admin-sidebar" :style="{ backgroundImage: `url(${sidebarBg})` }">
    <!-- Logo Section -->
    <div class="logo-section">
      <img :src="tahananLogo" alt="Tahanan Logo" class="logo-icon" />
      <img :src="tahananLogoName" alt="Tahanan Logo Name" class="logo-name" />
    </div>

    <!-- Navigation Links -->
    <nav class="navigation">
      <router-link to="/portal-admin/dashboard" class="nav-item" :class="{ active: isActive('AdminDashboard') }">
        <Squares2X2Icon class="nav-icon" />
        <span class="nav-text">Dashboard</span>
      </router-link>
      <router-link to="/portal-admin/properties" class="nav-item">
        <BuildingOffice2Icon class="nav-icon" />
        <span class="nav-text">Property Management</span>
      </router-link>
      <router-link to="/portal-admin/users" class="nav-item">
        <UsersIcon class="nav-icon" />
        <span class="nav-text">User Management</span>
      </router-link>
      <router-link to="/portal-admin/reports" class="nav-item">
        <ChartBarIcon class="nav-icon" />
        <span class="nav-text">Reports</span>
      </router-link>
    </nav>

    <!-- User Profile Section -->
    <div class="user-profile">
      <div class="avatar">KJ</div>
      <div class="user-info">
        <div class="user-name">Kenneth James</div>
        <div class="user-role">Admin</div>
      </div>
      <div class="settings-container" ref="settingsMenuRef">
        <Cog6ToothIcon class="settings-icon" @click="toggleSettingsMenu" />
        <Transition name="dropdown">
          <div v-if="showSettingsMenu" class="settings-menu">
            <button class="menu-item">
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
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.admin-sidebar {
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
  z-index: 100; /* Ensure high z-index */
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

.settings-container {
  position: relative;
  flex-shrink: 0;
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
