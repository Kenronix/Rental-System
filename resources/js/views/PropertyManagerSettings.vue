<script setup>
import { ref, onMounted } from 'vue'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import { useAuth } from '../composables/useAuth.js'
import { 
    MapPinIcon, 
    EnvelopeIcon, 
    PhoneIcon,
    CheckBadgeIcon
} from '@heroicons/vue/24/outline'

const { user } = useAuth()

// Mocking some fields that might not be in the database yet but are in the design
const profileInfo = ref({
    city: 'Cebu City',
    facebook: 'Sarah Duterte',
    isVerified: true
})

onMounted(() => {
    // If we had a specific endpoint to fetch extra profile details, we'd call it here
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">My Profile</h1>

      <div class="profile-container">
        <div class="profile-card">
            <!-- Verified Badge -->
            <div v-if="profileInfo.isVerified" class="verified-badge-container">
                <span class="verified-badge">Verified</span>
            </div>

            <div class="profile-header">
                <div class="avatar-wrapper">
                    <img v-if="user?.avatar" :src="user.avatar" alt="Profile" class="profile-img" />
                    <div v-else class="avatar-placeholder"></div>
                </div>
                <div class="profile-titles">
                    <h2 class="profile-name">{{ user?.name || 'HAKDOG' }}</h2>
                    <p class="profile-role">Property Manager</p>
                </div>
            </div>

            <div class="profile-details">
                <div class="detail-item">
                    <MapPinIcon class="detail-icon" />
                    <span class="detail-text">{{ profileInfo.city }}</span>
                </div>
                <div class="detail-item">
                    <EnvelopeIcon class="detail-icon" />
                    <span class="detail-text">{{ user?.email || 'sarahduterte@gmail.com' }}</span>
                </div>
                <div class="detail-item">
                    <div class="fb-icon-wrapper">
                        <!-- Custom FB Icon or similar -->
                        <svg class="detail-icon-svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="detail-text">{{ profileInfo.facebook }}</span>
                </div>
                <div class="detail-item">
                    <PhoneIcon class="detail-icon" />
                    <span class="detail-text">{{ user?.phone || '+63' }}</span>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f3f4f6;
  font-family: 'Montserrat', sans-serif;
}

.main-content {
  flex: 1;
  margin-left: 300px;
  padding: 40px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 32px;
}

.profile-container {
    display: flex;
    justify-content: flex-start;
}

.profile-card {
    background: white;
    border-radius: 8px; /* Slightly less rounded than others */
    padding: 60px 40px;
    width: 600px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: relative;
    display: flex;
    flex-direction: column;
}

.verified-badge-container {
    position: absolute;
    top: 30px;
    right: 30px;
}

.verified-badge {
    background: #d1fae5;
    color: #065f46;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 32px;
    margin-bottom: 40px;
}

.avatar-wrapper {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: #e5e7eb;
    overflow: hidden;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: #d1d5db; /* Grey like in screenshot */
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-titles {
    flex: 1;
}

.profile-name {
    font-size: 42px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1;
    margin-bottom: 8px;
}

.profile-role {
    font-size: 18px;
    color: #6b7280;
    margin: 0;
}

.profile-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 20px;
}

.detail-icon {
    width: 28px;
    height: 28px;
    color: #111827;
}

.detail-icon-svg {
    width: 28px;
    height: 28px;
    color: #111827;
}

.detail-text {
    font-size: 14px;
    color: #111827;
    font-weight: 500;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .profile-card {
        width: 100%;
        padding: 40px 20px;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }

    .avatar-wrapper {
        width: 150px;
        height: 150px;
    }
    
    .profile-name {
        font-size: 32px;
    }
}
</style>
