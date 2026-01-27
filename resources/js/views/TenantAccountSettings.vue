<script setup>
import { ref, onMounted } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  MapPinIcon,
  EnvelopeIcon,
  PhoneIcon,
  PencilIcon,
  CameraIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const profile = ref(null)
const isLoading = ref(false)
const error = ref(null)
const isEditing = ref(false)

// Fetch account settings data
const fetchAccountSettings = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/account-settings')
    // For now, using mock data based on the image
    setTimeout(() => {
      profile.value = {
        name: 'HAKDOG',
        role: 'Tenant',
        status: 'Active',
        location: 'Cebu City',
        email: 'sarahduterte@gmail.com',
        phone: '+63',
        avatar: null // Will use initials if no avatar
      }
      
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching account settings:', err)
    error.value = 'Failed to load account settings. Please try again.'
    isLoading.value = false
  }
}


const getInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const handleEdit = () => {
  isEditing.value = true
}

const handleSave = async () => {
  // TODO: Implement save functionality
  console.log('Saving account settings...')
  isEditing.value = false
  alert('Account settings saved successfully!')
}

const handleCancel = () => {
  isEditing.value = false
  // Reload data to reset changes
  fetchAccountSettings()
}

onMounted(() => {
  fetchAccountSettings()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <div class="page-header">
        <h1 class="page-title">Account Settings</h1>
        <button v-if="!isEditing" class="edit-button" @click="handleEdit">
          <PencilIcon class="edit-icon" />
          <span>Edit Profile</span>
        </button>
        <div v-else class="action-buttons">
          <button class="cancel-button" @click="handleCancel">Cancel</button>
          <button class="save-button" @click="handleSave">Save Changes</button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading account settings...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchAccountSettings">Retry</button>
      </div>

      <!-- Account Settings Content -->
      <div v-else class="settings-content">
        <!-- Profile Section -->
        <div class="profile-section">
          <div class="profile-header">
            <div class="avatar-container">
              <div class="avatar-large">
                {{ getInitials(profile?.name) }}
              </div>
              <button v-if="isEditing" class="avatar-edit-button">
                <CameraIcon class="camera-icon" />
              </button>
            </div>
            <div class="profile-info">
              <div class="name-section">
                <h2 class="profile-name">{{ profile?.name }}</h2>
                <span class="status-badge status-active">{{ profile?.status }}</span>
              </div>
              <p class="profile-role">{{ profile?.role }}</p>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="contact-section">
            <h3 class="section-title">Contact Information</h3>
            <div class="contact-grid">
              <div class="contact-item">
                <div class="contact-icon-container">
                  <MapPinIcon class="contact-icon" />
                </div>
                <div class="contact-details">
                  <p class="contact-label">Location</p>
                  <p class="contact-value">{{ profile?.location || 'Not provided' }}</p>
                </div>
              </div>

              <div class="contact-item">
                <div class="contact-icon-container">
                  <EnvelopeIcon class="contact-icon" />
                </div>
                <div class="contact-details">
                  <p class="contact-label">Email</p>
                  <p class="contact-value">{{ profile?.email || 'Not provided' }}</p>
                </div>
              </div>


              <div class="contact-item">
                <div class="contact-icon-container">
                  <PhoneIcon class="contact-icon" />
                </div>
                <div class="contact-details">
                  <p class="contact-label">Phone</p>
                  <p class="contact-value">{{ profile?.phone || 'Not provided' }}</p>
                </div>
              </div>
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
}

.main-content {
  flex: 1;
  margin-left: 300px;
  padding: 40px;
  background: #F0F0F0;
  min-height: 100vh;
  font-family: 'Montserrat', sans-serif;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.page-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.edit-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.edit-button:hover {
  background: #1200e6;
}

.edit-icon {
  width: 18px;
  height: 18px;
}

.action-buttons {
  display: flex;
  gap: 12px;
}

.cancel-button,
.save-button {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-button {
  background: #f3f4f6;
  color: #374151;
}

.cancel-button:hover {
  background: #e5e7eb;
}

.save-button {
  background: #1500FF;
  color: white;
}

.save-button:hover {
  background: #1200e6;
}

/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 80px 20px;
  color: #4b5563;
}

.retry-btn {
  margin-top: 16px;
  padding: 8px 16px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  transition: background-color 0.2s;
}

.retry-btn:hover {
  background: #1200e6;
}

/* Settings Content */
.settings-content {
  max-width: 800px;
}

/* Profile Section */
.profile-section {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.profile-header {
  display: flex;
  gap: 24px;
  margin-bottom: 32px;
  padding-bottom: 32px;
  border-bottom: 2px solid #f3f4f6;
}

.avatar-container {
  position: relative;
  flex-shrink: 0;
}

.avatar-large {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 48px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.avatar-edit-button {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #1500FF;
  border: 3px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.avatar-edit-button:hover {
  background: #1200e6;
}

.camera-icon {
  width: 18px;
  height: 18px;
  color: white;
}

.profile-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.name-section {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.profile-name {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}

.profile-role {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

/* Contact Section */
.contact-section {
  margin-top: 24px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 20px;
}

.contact-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 12px;
  transition: background-color 0.2s;
}

.contact-item:hover {
  background: #f3f4f6;
}

.contact-icon-container {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #1500FF;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.contact-icon {
  width: 24px;
  height: 24px;
  color: white;
}


.contact-details {
  flex: 1;
}

.contact-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 4px 0;
}

.contact-value {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .profile-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .name-section {
    flex-direction: column;
    align-items: center;
  }
}
</style>
