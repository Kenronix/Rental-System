<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  ArrowLeftIcon,
  PencilIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const route = useRoute()
const router = useRouter()

const unit = ref(null)
const property = ref(null)
const selectedImageIndex = ref(0)
const isLoading = ref(false)
const error = ref(null)

// Fetch unit details
const fetchUnit = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // Fetch property first to get units
    const propertyResponse = await api.get(`/properties/${route.params.id}`)
    
    if (propertyResponse.data.success) {
      property.value = propertyResponse.data.property
      
      // Fetch units for the property
      const unitsResponse = await api.get(`/properties/${route.params.id}/units`)
      
      if (unitsResponse.data.success) {
        const foundUnit = unitsResponse.data.units.find(u => u.id === parseInt(route.params.unitId))
        
        if (foundUnit) {
          unit.value = foundUnit
        } else {
          error.value = 'Unit not found.'
        }
      } else {
        error.value = 'Failed to load unit details.'
      }
    } else {
      error.value = 'Property not found.'
    }
  } catch (err) {
    console.error('Error fetching unit:', err)
    error.value = 'Failed to load unit details. Please try again.'
  } finally {
    isLoading.value = false
  }
}

const handleBack = () => {
  router.push(`/landlord/prop-${route.params.id}`)
}

const handleEditUnit = () => {
  router.push(`/landlord/prop-${route.params.id}/units/${route.params.unitId}/edit`)
}

const handleAddTenant = () => {
  // Navigate to tenant application form or tenant management
  // For now, navigate to the unit's application link
  const unitId = route.params.unitId
  router.push(`/units/${unitId}/apply`)
}

const selectImage = (index) => {
  selectedImageIndex.value = index
}

const formatCurrency = (amount) => {
  if (!amount) return '₱0'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const getUnitTypeLabel = (type) => {
  const typeMap = {
    'studio': 'Studio',
    'apartment': 'Apartment',
    '1br': '1 Bedroom',
    '2br': '2 Bedroom',
    '3br': '3 Bedroom',
    '4br': '4+ Bedroom',
    'penthouse': 'Penthouse'
  }
  return typeMap[type] || type
}

const getAmenityLabel = (amenity) => {
  const amenityMap = {
    'parking': 'Parking',
    'air_conditioning': 'Air Conditioning',
    'heating': 'Heating',
    'washer_dryer': 'Washer/Dryer',
    'dishwasher': 'Dishwasher',
    'balcony': 'Balcony',
    'gym': 'Gym Access',
    'pool': 'Pool Access',
    'elevator': 'Elevator',
    'pet_friendly': 'Pet Friendly',
    'furnished': 'Furnished',
    'wifi': 'WiFi Included'
  }
  return amenityMap[amenity] || amenity.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

onMounted(() => {
  fetchUnit()
})
</script>

<template>
  <div class="page-container">
    <Sidebar />
    
    <div class="main-content">
      <div class="content-wrapper">
        <!-- Back Button -->
        <button 
          @click="handleBack"
          class="back-button"
        >
          <ArrowLeftIcon class="back-icon" />
          <span class="back-text">Back</span>
        </button>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-state">
          <p class="loading-text">Loading unit details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <p class="error-text">{{ error }}</p>
          <button 
            @click="fetchUnit"
            class="retry-button"
          >
            Retry
          </button>
        </div>

        <!-- Unit Details -->
        <div v-else-if="unit" class="unit-details-container">
          <!-- Header -->
          <div class="unit-header">
            <h1 class="unit-title">Unit {{ unit.unit_number }}</h1>
            <button 
              @click="handleEditUnit"
              class="edit-unit-button"
            >
              <PencilIcon class="button-icon" />
              <span>Edit Unit</span>
            </button>
          </div>

          <!-- Unit Media and Details Section -->
          <div class="unit-media-details-section">
            <div class="unit-media-column">
              <!-- Main Image -->
              <div class="main-image-container">
                <img 
                  v-if="unit.photos && unit.photos.length > 0"
                  :src="unit.photos[selectedImageIndex] || unit.photos[0]"
                  :alt="`Unit ${unit.unit_number}`"
                  class="main-image"
                />
                <div v-else class="image-placeholder">
                  <span>No image available</span>
                </div>
              </div>
              
              <!-- Thumbnail Images -->
              <div v-if="unit.photos && unit.photos.length > 1" class="thumbnail-container">
                <div
                  v-for="(photo, index) in unit.photos"
                  :key="index"
                  :class="['thumbnail-item', { active: selectedImageIndex === index }]"
                  @click="selectImage(index)"
                >
                  <img 
                    :src="photo"
                    :alt="`Thumbnail ${index + 1}`"
                    class="thumbnail-image"
                  />
                </div>
              </div>
            </div>

            <!-- Unit Details Column -->
            <div class="unit-details-column">
              <div class="unit-details-card">
                <h2 class="details-title">Unit Details</h2>
                
                <div class="details-grid">
                  <div class="detail-item">
                    <span class="detail-label">Unit Type:</span>
                    <span class="detail-value">{{ getUnitTypeLabel(unit.unit_type) }}</span>
                  </div>
                  
                  <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span :class="['detail-value', 'status-badge', unit.is_occupied ? 'status-occupied' : 'status-available']">
                      {{ unit.is_occupied ? 'Occupied' : 'Available' }}
                    </span>
                  </div>
                  
                  <div class="detail-item">
                    <span class="detail-label">Bedrooms:</span>
                    <span class="detail-value">{{ unit.bedrooms || 0 }}</span>
                  </div>
                  
                  <div class="detail-item">
                    <span class="detail-label">Bathrooms:</span>
                    <span class="detail-value">{{ unit.bathrooms || 0 }}</span>
                  </div>
                  
                  <div v-if="unit.square_footage" class="detail-item">
                    <span class="detail-label">Square Footage:</span>
                    <span class="detail-value">{{ unit.square_footage }} sq ft</span>
                  </div>
                  
                  <div class="detail-item">
                    <span class="detail-label">Monthly Rent:</span>
                    <span class="detail-value highlight">{{ formatCurrency(unit.monthly_rent) }}</span>
                  </div>
                  
                  <div v-if="unit.security_deposit" class="detail-item">
                    <span class="detail-label">Security Deposit:</span>
                    <span class="detail-value">{{ formatCurrency(unit.security_deposit) }}</span>
                  </div>
                  
                  <div v-if="unit.advance_deposit" class="detail-item">
                    <span class="detail-label">Advance Deposit:</span>
                    <span class="detail-value">{{ formatCurrency(unit.advance_deposit) }}</span>
                  </div>
                </div>
                
                <div v-if="unit.amenities && unit.amenities.length > 0" class="amenities-section">
                  <h3 class="amenities-title">Amenities</h3>
                  <div class="amenities-list">
                    <span 
                      v-for="amenity in unit.amenities" 
                      :key="amenity"
                      class="amenity-tag"
                    >
                      {{ getAmenityLabel(amenity) }}
                    </span>
                  </div>
                </div>
                
                <div v-if="unit.description" class="description-section">
                  <h3 class="description-title">Description</h3>
                  <p class="description-text">{{ unit.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tenants Information Section -->
          <div class="tenants-section">
            <div class="tenants-header">
              <h2 class="tenants-title">Tenants Information</h2>
              <button 
                v-if="!unit.tenant"
                @click="handleAddTenant"
                class="add-tenant-button"
              >
                <PlusIcon class="button-icon" />
                <span>Add Tenant</span>
              </button>
            </div>
            <div v-if="unit.tenant" class="tenants-card">
              <div class="tenant-info">
                <div class="tenant-avatar">
                  <span>{{ unit.tenant.name?.charAt(0).toUpperCase() || 'T' }}</span>
                </div>
                <div class="tenant-details">
                  <h3 class="tenant-name">{{ unit.tenant.name }}</h3>
                  <p class="tenant-email">{{ unit.tenant.email }}</p>
                  <p v-if="unit.tenant.phone" class="tenant-phone">{{ unit.tenant.phone }}</p>
                </div>
              </div>
            </div>
            <div v-else class="tenants-empty">
              <p class="empty-text">No tenants yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

* {
  font-family: 'Montserrat', sans-serif;
}

.page-container {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  margin-left: 300px;
  background: #f0f0f0;
  min-height: 100vh;
}

.content-wrapper {
  padding: 40px;
}

/* Back Button */
.back-button {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4b5563;
  margin-bottom: 24px;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  font-size: 16px;
}

.back-button:hover {
  color: #111827;
}

.back-icon {
  width: 20px;
  height: 20px;
}

.back-text {
  font-weight: 500;
}

/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 80px 20px;
}

.loading-text {
  color: #4b5563;
  font-size: 16px;
}

.error-text {
  color: #dc2626;
  margin-bottom: 16px;
  font-size: 16px;
}

.retry-button {
  padding: 8px 16px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.retry-button:hover {
  background: #1200e6;
}

/* Unit Header */
.unit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.unit-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.edit-unit-button {
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
  font-size: 14px;
}

.edit-unit-button:hover {
  background: #1200e6;
}

.button-icon {
  width: 20px;
  height: 20px;
}

/* Unit Media and Details Section */
.unit-media-details-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  margin-bottom: 32px;
}

@media (max-width: 1024px) {
  .unit-media-details-section {
    grid-template-columns: 1fr;
  }
}

/* Unit Media Column */
.unit-media-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.main-image-container {
  width: 100%;
  height: 400px;
  border-radius: 12px;
  overflow: hidden;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  color: #9ca3af;
  font-size: 16px;
}

.thumbnail-container {
  display: flex;
  gap: 12px;
  overflow-x: auto;
}

.thumbnail-item {
  flex-shrink: 0;
  width: 120px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.2s;
}

.thumbnail-item:hover {
  border-color: #1500FF;
}

.thumbnail-item.active {
  border-color: #1500FF;
}

.thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Unit Details Column */
.unit-details-column {
  display: flex;
  flex-direction: column;
}

.unit-details-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
  height: 100%;
}

.details-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 24px 0;
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-size: 14px;
  font-weight: 600;
  color: #6b7280;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.detail-value.highlight {
  font-size: 18px;
  font-weight: 700;
  color: #1500FF;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.status-occupied {
  background: #fef2f2;
  color: #dc2626;
}

.status-available {
  background: #dcfce7;
  color: #15803d;
}

.amenities-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.amenities-title {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 12px 0;
}

.amenities-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.amenity-tag {
  padding: 6px 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
}

.description-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.description-title {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 12px 0;
}

.description-text {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}

/* Tenants Section */
.tenants-section {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
}

.tenants-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.tenants-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.add-tenant-button {
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
  font-size: 14px;
}

.add-tenant-button:hover {
  background: #1200e6;
}

.tenants-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 24px;
}

.tenants-empty {
  background: #f9fafb;
  border-radius: 8px;
  padding: 48px 24px;
  text-align: center;
}

.empty-text {
  color: #9ca3af;
  font-size: 16px;
  margin: 0;
}

.tenant-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.tenant-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #1500FF;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 700;
  flex-shrink: 0;
}

.tenant-details {
  flex: 1;
}

.tenant-name {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 4px 0;
}

.tenant-email {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 4px 0;
}

.tenant-phone {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}
</style>
