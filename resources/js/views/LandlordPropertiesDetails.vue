<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  ArrowLeftIcon, 
  PlusIcon,
  PencilIcon,
  MapPinIcon,
  BuildingOfficeIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const route = useRoute()
const router = useRouter()

const property = ref(null)
const units = ref([])
const isLoading = ref(false)
const error = ref(null)

// Fetch property details
const fetchProperty = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get(`/properties/${route.params.id}`)
    
    if (response.data.success) {
      property.value = response.data.property
      // Fetch units for this property
      await fetchUnits()
    }
  } catch (err) {
    console.error('Error fetching property:', err)
    error.value = 'Failed to load property details. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Fetch units for the property
const fetchUnits = async () => {
  try {
    const response = await api.get(`/properties/${route.params.id}/units`)
    
    if (response.data.success) {
      units.value = response.data.units.map(unit => ({
        id: unit.id,
        unitNumber: unit.unit_number,
        status: unit.is_occupied ? 'Occupied' : 'Available',
        image: unit.photos && unit.photos.length > 0 
          ? unit.photos[0] 
          : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop'
      }))
    }
  } catch (err) {
    console.error('Error fetching units:', err)
  }
}

const handleBack = () => {
  router.push('/landlord/properties')
}

const handleEditProperty = () => {
  // Navigate to edit property page
  if (property.value?.id) {
    router.push(`/landlord/properties/${property.value.id}/edit`)
  }
}

const handleAddUnit = () => {
  // Navigate to add unit page or show modal
  router.push(`/landlord/properties/${route.params.id}/units/add`)
}

const getStatusColor = (status) => {
  return status === 'Active' || status === 'active' 
    ? 'status-active' 
    : 'status-inactive'
}

const getTypeColor = (type) => {
  const typeMap = {
    'apartment': 'type-apartment',
    'house': 'type-house',
    'commercial': 'type-commercial',
    'residential': 'type-residential'
  }
  return typeMap[type?.toLowerCase()] || 'type-default'
}

const formatPropertyType = (type) => {
  if (!type) return 'N/A'
  return type.charAt(0).toUpperCase() + type.slice(1)
}

onMounted(() => {
  fetchProperty()
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
          <p class="loading-text">Loading property details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <p class="error-text">{{ error }}</p>
          <button 
            @click="fetchProperty"
            class="retry-button"
          >
            Retry
          </button>
        </div>

        <!-- Property Details -->
        <div v-else-if="property">
          <!-- Header Section -->
          <div class="header-section">
            <div>
              <h1 class="page-title">Property Details</h1>
            </div>
            <button 
              @click="handleEditProperty"
              class="edit-button"
            >
              <PencilIcon class="button-icon" />
              <span>Edit Property</span>
            </button>
          </div>

          <!-- Property Overview Section -->
          <div class="overview-section">
            <div class="overview-grid">
              <!-- Property Image -->
              <div class="image-container">
                <img 
                  :src="property.main_photo || 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop'"
                  :alt="property.name"
                  class="property-image"
                />
              </div>

              <!-- Property Information -->
              <div class="property-info">
                <!-- Property Name -->
                <h2 class="property-name">{{ property.name }}</h2>

                <!-- Status & Type Badges -->
                <div class="badges-container">
                  <span 
                    :class="['badge', getStatusColor(property.status)]"
                  >
                    {{ property.status === 'active' ? 'Active' : 'Inactive' }}
                  </span>
                  <span 
                    :class="['badge', getTypeColor(property.type)]"
                  >
                    {{ formatPropertyType(property.type) }}
                  </span>
                </div>

                <!-- Address -->
                <div class="info-item">
                  <MapPinIcon class="info-icon" />
                  <div class="info-content">
                    <p class="info-label">Address</p>
                    <p class="info-value">
                      {{ property.street_address || 'N/A' }}, 
                      {{ property.city || '' }}, 
                      {{ property.state || '' }} 
                      {{ property.zip_code || '' }}
                    </p>
                  </div>
                </div>

                <!-- Total Units -->
                <div class="info-item">
                  <BuildingOfficeIcon class="info-icon" />
                  <div class="info-content">
                    <p class="info-label">Total Units</p>
                    <p class="info-value-large">{{ property.units || units.length || 0 }}</p>
                  </div>
                </div>

                <!-- Total Tenants -->
                <div class="info-item">
                  <UsersIcon class="info-icon" />
                  <div class="info-content">
                    <p class="info-label">Total Tenants</p>
                    <p class="info-value-large">{{ property.tenants || units.filter(u => u.status === 'Occupied').length || 0 }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Separator -->
          <div class="separator"></div>

          <!-- Units & Rooms Section -->
          <div class="units-section">
            <div class="units-header">
              <h2 class="units-title">Units & Rooms</h2>
              <button 
                @click="handleAddUnit"
                class="add-button"
              >
                <PlusIcon class="button-icon" />
                <span>Add Unit</span>
              </button>
            </div>

            <!-- Units Grid -->
            <div v-if="units.length > 0" class="units-grid">
              <div 
                v-for="unit in units" 
                :key="unit.id"
                class="unit-card"
              >
                <!-- Unit Image -->
                <div class="unit-image-container">
                  <img 
                    :src="unit.image"
                    :alt="`Unit ${unit.unitNumber}`"
                    class="unit-image"
                  />
                  <!-- Gradient Overlay -->
                  <div class="unit-gradient"></div>
                  
                  <!-- Unit Info Overlay -->
                  <div class="unit-info-overlay">
                    <h3 class="unit-number">Unit {{ unit.unitNumber }}</h3>
                    <p class="unit-status">{{ unit.status }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
              <p class="empty-text">No units found for this property.</p>
              <button 
                @click="handleAddUnit"
                class="add-button-inline"
              >
                <PlusIcon class="button-icon" />
                <span>Add Your First Unit</span>
              </button>
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
  background: white;
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
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.retry-button:hover {
  background: #1d4ed8;
}

/* Header Section */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.page-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.edit-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.edit-button:hover {
  background: #1d4ed8;
}

.button-icon {
  width: 20px;
  height: 20px;
}

/* Overview Section */
.overview-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
  margin-bottom: 32px;
}

.overview-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 32px;
}

@media (min-width: 1024px) {
  .overview-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.image-container {
  width: 100%;
  display: flex;
  justify-content: center;
}

.property-image {
  max-width: 80%;
  height: 300px;
  object-fit: cover;
  border-radius: 8px;
}

@media (min-width: 1024px) {
  .property-image {
    height: 350px;
  }
}

.property-info {
  display: flex;
  flex-direction: column;
  gap: 24px;
  justify-content: flex-start;
}

.property-name {
  font-size: 30px;
  font-weight: 700;
  color: #111827;
}

.badges-container {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.badge {
  padding: 8px 16px;
  border-radius: 9999px;
  font-size: 14px;
  font-weight: 600;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}

.status-inactive {
  background: #f3f4f6;
  color: #374151;
}

.type-apartment,
.type-residential {
  background: #dbeafe;
  color: #1e40af;
}

.type-house {
  background: #f3e8ff;
  color: #7c3aed;
}

.type-commercial {
  background: #fed7aa;
  color: #c2410c;
}

.type-default {
  background: #f3f4f6;
  color: #374151;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: #9ca3af;
  margin-top: 4px;
  flex-shrink: 0;
}

.info-content {
  flex: 1;
}

.info-label {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 4px;
}

.info-value {
  color: #374151;
  font-size: 14px;
}

.info-value-large {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
}

/* Separator */
.separator {
  border-top: 1px solid #e5e7eb;
  margin: 32px 0;
}

/* Units Section */
.units-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
}

.units-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.units-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.add-button,
.add-button-inline {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.add-button:hover,
.add-button-inline:hover {
  background: #1d4ed8;
}

.add-button-inline {
  display: inline-flex;
}

/* Units Grid */
.units-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (min-width: 640px) {
  .units-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .units-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1280px) {
  .units-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.unit-card {
  position: relative;
  cursor: pointer;
  overflow: hidden;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.2s;
}

.unit-card:hover {
  box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
}

.unit-image-container {
  position: relative;
  height: 256px;
  overflow: hidden;
}

.unit-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.unit-card:hover .unit-image {
  transform: scale(1.05);
}

.unit-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(30, 58, 138, 0.8), rgba(30, 58, 138, 0.4), transparent);
}

.unit-info-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  color: white;
}

.unit-number {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
}

.unit-status {
  font-size: 14px;
  font-weight: 500;
  opacity: 0.9;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 20px;
}

.empty-text {
  color: #6b7280;
  margin-bottom: 16px;
  font-size: 16px;
}
</style>
