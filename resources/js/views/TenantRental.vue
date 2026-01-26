<script setup>
import { ref, onMounted } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  HomeIcon,
  PaperAirplaneIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const rental = ref(null)
const landlord = ref(null)
const isLoading = ref(false)
const error = ref(null)
const messageText = ref('')
const isSendingMessage = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(1)
const totalItems = ref(2)

// Fetch rental details
const fetchRental = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/rental')
    // For now, using mock data based on the image
    setTimeout(() => {
      rental.value = {
        id: 1,
        property_name: 'Sunshine Condominium',
        address: 'Inayawan, Lawaan II Cebu',
        unit_number: '402',
        status: 'Active Lease',
        bedrooms: 2,
        bathrooms: 1,
        monthly_rent: 1200.00,
        security_deposit: 1200.00,
        lease_start: '2025-08-01',
        lease_end: '2026-07-31',
        photos: [
          'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop',
          'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=400&fit=crop',
          'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=400&h=400&fit=crop',
          'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=400&h=400&fit=crop',
          'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=400&h=400&fit=crop'
        ]
      }
      
      landlord.value = {
        id: 1,
        name: 'Sarah Landlord',
        role: 'Property Manager',
        avatar: 'SL'
      }
      
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching rental:', err)
    error.value = 'Failed to load rental details. Please try again.'
    isLoading.value = false
  }
}

const formatCurrency = (amount) => {
  if (!amount) return '₱0.00'
  return `₱${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`
}

const formatDateShort = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getFullYear()}`
}

const sendMessage = async () => {
  if (!messageText.value.trim()) return
  
  isSendingMessage.value = true
  try {
    // TODO: Replace with actual API endpoint
    // await api.post('/tenant/messages', {
    //   landlord_id: landlord.value.id,
    //   message: messageText.value
    // })
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    messageText.value = ''
    alert('Message sent successfully!')
  } catch (err) {
    console.error('Error sending message:', err)
    alert('Failed to send message. Please try again.')
  } finally {
    isSendingMessage.value = false
  }
}

onMounted(() => {
  fetchRental()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">My Rental</h1>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading rental details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchRental">Retry</button>
      </div>

      <!-- Rental Details -->
      <div v-else-if="rental" class="rental-container">
        <!-- Property Images Section -->
        <div class="images-section">
          <!-- Main Large Image -->
          <div class="main-image-container">
            <img 
              v-if="rental.photos && rental.photos.length > 0"
              :src="rental.photos[0]"
              :alt="rental.property_name"
              class="main-image"
            />
            <div v-else class="image-placeholder">
              <span>No image available</span>
            </div>
          </div>

          <!-- Thumbnail Grid -->
          <div v-if="rental.photos && rental.photos.length > 1" class="thumbnail-grid">
            <div
              v-for="(photo, index) in rental.photos.slice(1, 5)"
              :key="index"
              class="thumbnail-item"
            >
              <img 
                :src="photo"
                :alt="`Property image ${index + 2}`"
                class="thumbnail-image"
              />
            </div>
          </div>
        </div>

        <!-- Details Section -->
        <div class="details-section">
          <!-- Unit Details Card -->
          <div class="unit-details-card">
            <div class="card-header">
              <HomeIcon class="header-icon" />
              <h2 class="card-title">{{ rental.property_name }}</h2>
            </div>
            <p class="property-address">{{ rental.address }}</p>
            
            <!-- Badges -->
            <div class="badges-container">
              <span class="badge badge-active">{{ rental.status }}</span>
              <span class="badge badge-gray">{{ rental.bedrooms }} Bedroom{{ rental.bedrooms > 1 ? 's' : '' }}</span>
              <span class="badge badge-gray">{{ rental.bathrooms }} Bath{{ rental.bathrooms > 1 ? 's' : '' }}</span>
            </div>

            <!-- Financial Details -->
            <div class="details-list">
              <div class="detail-row">
                <span class="detail-label">Monthly Rent:</span>
                <span class="detail-value">{{ formatCurrency(rental.monthly_rent) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Security Deposit:</span>
                <span class="detail-value">{{ formatCurrency(rental.security_deposit) }}</span>
              </div>
            </div>

            <!-- Lease Dates -->
            <div class="details-list">
              <div class="detail-row">
                <span class="detail-label">Lease Start:</span>
                <span class="detail-value">{{ formatDate(rental.lease_start) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Lease End:</span>
                <span class="detail-value">{{ formatDateShort(rental.lease_end) }}</span>
              </div>
            </div>
          </div>

          <!-- Landlord Contact Card -->
          <div class="landlord-contact-card">
            <h3 class="contact-title">Landlord Contact</h3>
            <div class="landlord-info">
              <div class="landlord-avatar">{{ landlord?.avatar || 'LL' }}</div>
              <div class="landlord-details">
                <p class="landlord-name">{{ landlord?.name || 'Landlord Name' }}</p>
                <p class="landlord-role">{{ landlord?.role || 'Property Manager' }}</p>
              </div>
            </div>
            <div class="message-section">
              <input
                v-model="messageText"
                type="text"
                class="message-input"
                placeholder="Send Message"
                @keyup.enter="sendMessage"
              />
              <button 
                class="send-button"
                @click="sendMessage"
                :disabled="isSendingMessage || !messageText.trim()"
              >
                <PaperAirplaneIcon class="send-icon" />
                <span>Send</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-section">
          <div class="pagination-info">
            Showing {{ ((currentPage - 1) * itemsPerPage) + 1 }} to {{ Math.min(currentPage * itemsPerPage, totalItems) }} of {{ totalItems }} results
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
}

.page-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 32px;
  font-family: 'Montserrat', sans-serif;
}

/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 80px 20px;
  color: #4b5563;
  font-family: 'Montserrat', sans-serif;
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

/* Rental Container */
.rental-container {
  font-family: 'Montserrat', sans-serif;
}

/* Images Section */
.images-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
  margin-bottom: 32px;
}

.main-image-container {
  width: 100%;
  height: 400px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e5e7eb;
  color: #6b7280;
}

.thumbnail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.thumbnail-item {
  width: 100%;
  height: 192px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s;
}

.thumbnail-item:hover {
  transform: scale(1.02);
}

.thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Details Section */
.details-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.unit-details-card,
.landlord-contact-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.header-icon {
  width: 24px;
  height: 24px;
  color: #1500FF;
}

.card-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.property-address {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 16px;
}

.badges-container {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.badge-active {
  background: #dcfce7;
  color: #15803d;
}

.badge-gray {
  background: #f3f4f6;
  color: #374151;
}

.details-list {
  margin-bottom: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #e5e7eb;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
}

.detail-value {
  color: #111827;
  font-size: 14px;
  font-weight: 600;
}

/* Landlord Contact Card */
.contact-title {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 20px;
}

.landlord-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.landlord-avatar {
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

.landlord-details {
  flex: 1;
}

.landlord-name {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.landlord-role {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.message-section {
  display: flex;
  gap: 8px;
}

.message-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.message-input:focus {
  border-color: #1500FF;
}

.send-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
}

.send-button:hover:not(:disabled) {
  background: #1200e6;
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.send-icon {
  width: 18px;
  height: 18px;
}

/* Pagination */
.pagination-section {
  display: flex;
  justify-content: center;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  color: #6b7280;
  font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .images-section {
    grid-template-columns: 1fr;
  }

  .thumbnail-grid {
    grid-template-columns: repeat(4, 1fr);
  }

  .details-section {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .thumbnail-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
