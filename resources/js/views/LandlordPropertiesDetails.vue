<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import AdminSidebar from '../components/layout/AdminSidebar.vue'

import { 
  ArrowLeftIcon, 
  PlusIcon,
  PencilIcon,
  MapPinIcon,
  BuildingOfficeIcon,
  EllipsisVerticalIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import { ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const route = useRoute()
const router = useRouter()
const isAdmin = computed(() => route.path.startsWith('/admin'))

const property = ref(null)
const units = ref([])
const allUnits = ref([])
const isLoading = ref(false)
const error = ref(null)
const unitsCurrentPage = ref(1)
const unitsPerPage = ref(8)
const openMenuId = ref(null)
const availabilityFilter = ref('All')

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
      allUnits.value = response.data.units.map(unit => ({
        id: unit.id,
        unitNumber: unit.unit_number,
        status: unit.is_occupied ? 'Occupied' : 'Available',
        is_occupied: unit.is_occupied,
        image: unit.photos && unit.photos.length > 0 
          ? unit.photos[0] 
          : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop'
      }))
      unitsCurrentPage.value = 1
      applyAvailabilityFilter()
    }
  } catch (err) {
    console.error('Error fetching units:', err)
  }
}

// Filter units by availability
const filteredUnits = computed(() => {
  if (availabilityFilter.value === 'All') {
    return allUnits.value
  } else if (availabilityFilter.value === 'Available') {
    return allUnits.value.filter(unit => !unit.is_occupied)
  } else if (availabilityFilter.value === 'Occupied') {
    return allUnits.value.filter(unit => unit.is_occupied)
  }
  return allUnits.value
})

// Apply filter and reset to first page
const applyAvailabilityFilter = () => {
  unitsCurrentPage.value = 1
}

const totalUnitsPages = computed(() =>
  Math.max(1, Math.ceil(filteredUnits.value.length / unitsPerPage.value))
)

const paginatedUnits = computed(() => {
  const start = (unitsCurrentPage.value - 1) * unitsPerPage.value
  return filteredUnits.value.slice(start, start + unitsPerPage.value)
})

const unitsPaginationInfo = computed(() => {
  const start = (unitsCurrentPage.value - 1) * unitsPerPage.value
  const end = Math.min(unitsCurrentPage.value * unitsPerPage.value, filteredUnits.value.length)
  return { start: filteredUnits.value.length ? start + 1 : 0, end, total: filteredUnits.value.length }
})

const goToUnitsPrevPage = () => {
  if (unitsCurrentPage.value > 1) unitsCurrentPage.value--
}

const goToUnitsNextPage = () => {
  if (unitsCurrentPage.value < totalUnitsPages.value) unitsCurrentPage.value++
}

const goToUnitsPage = (page) => {
  if (page >= 1 && page <= totalUnitsPages.value) unitsCurrentPage.value = page
}

const handleBack = () => {
  const redirectPath = isAdmin.value ? '/admin/properties' : '/landlord/properties'
  router.push(redirectPath)
}

const handleEditProperty = () => {
  // Navigate to edit property page
  if (property.value?.id) {
    const prefix = isAdmin.value ? '/admin/prop' : '/landlord/prop'
    router.push(`${prefix}-${property.value.id}/edit`)
  }
}

const handleAddUnit = () => {
  // Navigate to add unit page or show modal
  router.push(`/landlord/prop-${route.params.id}/units/add`)
}

const handleViewUnitDetails = (unitId, event) => {
  if (event) event.stopPropagation()
  openMenuId.value = null
  router.push(`/landlord/prop-${route.params.id}/units/${unitId}`)
}

const toggleMenu = (unitId, event) => {
  event.stopPropagation()
  openMenuId.value = openMenuId.value === unitId ? null : unitId
}

const closeMenu = () => {
  openMenuId.value = null
}

const handleShare = async (unitId, event) => {
  event.stopPropagation()
  const shareUrl = `${window.location.origin}/units/${unitId}/apply`
  try {
    if (navigator.share) {
      await navigator.share({
        title: `Unit ${units.value.find(u => u.id === unitId)?.unitNumber} - ${property.value?.name}`,
        text: `Check out this unit at ${property.value?.name}`,
        url: shareUrl
      })
    } else {
      await navigator.clipboard.writeText(shareUrl)
      alert('Link copied to clipboard!')
    }
  } catch (err) {
    // Fallback to clipboard
    try {
      await navigator.clipboard.writeText(shareUrl)
      alert('Link copied to clipboard!')
    } catch (clipboardErr) {
      console.error('Failed to copy:', clipboardErr)
    }
  }
  openMenuId.value = null
}

const handleGenerateQR = (unitId, event) => {
  event.stopPropagation()
  const unitUrl = `${window.location.origin}/units/${unitId}/apply`
  // Open QR code generator or show QR code modal
  const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(unitUrl)}`
  window.open(qrCodeUrl, '_blank')
  openMenuId.value = null
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
    <AdminSidebar v-if="isAdmin" />
    <Sidebar v-else />
    
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
            <h1 class="page-title">Property Details</h1>
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
                <!-- Property Name with Edit Button -->
                <div class="property-name-header">
                  <h2 class="property-name">{{ property.name }}</h2>
                  <button 
                    @click="handleEditProperty"
                    class="edit-button"
                  >
                    <PencilIcon class="button-icon" />
                    <span>Edit Property</span>
                  </button>
                </div>

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
                    <p class="info-value-large">{{ property.units || allUnits.length || 0 }}</p>
                  </div>
                </div>

                <!-- Total Tenants -->
                <div class="info-item">
                  <UsersIcon class="info-icon" />
                  <div class="info-content">
                    <p class="info-label">Total Tenants</p>
                    <p class="info-value-large">{{ property.tenants || allUnits.filter(u => u.is_occupied).length || 0 }}</p>
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
              <div class="units-header-actions">
                <div class="filter-container">
                  <select v-model="availabilityFilter" class="filter-dropdown" @change="applyAvailabilityFilter">
                    <option value="All">All Units</option>
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                  </select>
                  <ChevronDownIcon class="chevron-icon" />
                </div>
                <button 
                  @click="handleAddUnit"
                  class="add-button"
                >
                  <PlusIcon class="button-icon" />
                  <span>Add Unit</span>
                </button>
              </div>
            </div>

            <!-- Units Grid -->
            <div v-if="filteredUnits.length > 0" class="units-grid">
              <div 
                v-for="unit in paginatedUnits" 
                :key="unit.id"
                class="unit-card"
                @click="closeMenu"
              >
                <!-- Unit Image -->
                <div class="unit-image-container">
                  <img 
                    :src="unit.image"
                    :alt="`Unit ${unit.unitNumber}`"
                    class="unit-image"
                  />
                  <!-- Bottom blue gradient (always visible) -->
                  <div class="unit-gradient"></div>
                  
                  <!-- Unit Info Overlay -->
                  <div class="unit-info-overlay">
                    <div class="unit-info-content">
                      <div>
                        <h3 class="unit-number">Unit {{ unit.unitNumber }}</h3>
                        <p class="unit-status">{{ unit.status }}</p>
                      </div>
                      <div class="unit-menu-container">
                        <button 
                          class="unit-menu-button"
                          @click.stop="toggleMenu(unit.id, $event)"
                        >
                          <EllipsisVerticalIcon class="unit-menu-icon" />
                        </button>
                        <!-- Dropdown Menu -->
                        <div 
                          v-if="openMenuId === unit.id"
                          class="unit-menu-dropdown"
                          @click.stop
                        >
                          <button 
                            class="unit-menu-item"
                            @click="handleViewUnitDetails(unit.id, $event)"
                          >
                            View Details
                          </button>
                          <button 
                            class="unit-menu-item"
                            @click="handleShare(unit.id, $event)"
                          >
                            Share
                          </button>
                          <button 
                            class="unit-menu-item"
                            @click="handleGenerateQR(unit.id, $event)"
                          >
                            Generate QR Code
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="allUnits.length === 0" class="empty-state">
              <p class="empty-text">No units found for this property.</p>
              <button 
                @click="handleAddUnit"
                class="add-button-inline"
              >
                <PlusIcon class="button-icon" />
                <span>Add Your First Unit</span>
              </button>
            </div>
            <div v-else-if="filteredUnits.length === 0" class="empty-state">
              <p class="empty-text">No {{ availabilityFilter.toLowerCase() }} units found.</p>
            </div>

            <!-- Units Pagination -->
            <div v-if="filteredUnits.length > 0" class="units-pagination">
              <div class="units-pagination-controls">
                <button 
                  class="pagination-btn" 
                  :disabled="unitsCurrentPage === 1"
                  @click="goToUnitsPrevPage"
                >
                  <ChevronLeftIcon class="chevron-pagination-icon" />
                </button>
                <button 
                  v-for="page in totalUnitsPages" 
                  :key="page"
                  :class="['page-number', { active: unitsCurrentPage === page }]"
                  @click="goToUnitsPage(page)"
                >
                  {{ page }}
                </button>
                <button 
                  class="pagination-btn" 
                  :disabled="unitsCurrentPage === totalUnitsPages"
                  @click="goToUnitsNextPage"
                >
                  <ChevronRightIcon class="chevron-pagination-icon" />
                </button>
              </div>
              <div class="units-pagination-info">
                Showing {{ unitsPaginationInfo.start }} to {{ unitsPaginationInfo.end }} of {{ unitsPaginationInfo.total }} units
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

/* Header Section */
.header-section {
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
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.edit-button:hover {
  background: #1200e6;
}

.button-icon {
  width: 20px;
  height: 20px;
}

/* Overview Section */
.overview-section {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
  margin-bottom: 32px;
  position: relative;
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

.property-name-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.property-name {
  font-size: 30px;
  font-weight: 700;
  color: #111827;
  margin: 0;
  flex: 1;
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
  background: #ffffff;
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

.units-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.units-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.filter-container {
  position: relative;
  display: inline-block;
}

.filter-dropdown {
  appearance: none;
  padding: 10px 36px 10px 14px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  min-width: 140px;
  outline: none;
  transition: border-color 0.2s;
}

.filter-dropdown:hover {
  border-color: #bbb;
}

.filter-dropdown:focus {
  border-color: #1500FF;
}

.chevron-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: #666;
  pointer-events: none;
}

.add-button,
.add-button-inline {
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

.add-button:hover,
.add-button-inline:hover {
  background: #1200e6;
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
  background: linear-gradient(to top, rgba(37, 99, 235, 0.85), rgba(37, 99, 235, 0.5), transparent 50%);
  pointer-events: none;
  z-index: 1;
}

.unit-info-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  color: white;
  z-index: 3;
}

.unit-info-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.unit-menu-container {
  position: relative;
}

.unit-menu-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 0;
}

.unit-menu-button:hover {
  background: rgba(255, 255, 255, 0.3);
}

.unit-menu-icon {
  width: 20px;
  height: 20px;
  color: white;
}

.unit-menu-dropdown {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 8px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  overflow: hidden;
  z-index: 10;
}

.unit-menu-item {
  display: block;
  width: 100%;
  padding: 12px 16px;
  text-align: left;
  background: white;
  border: none;
  color: #374151;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.unit-menu-item:hover {
  background: #f3f4f6;
}

.unit-menu-item:first-child {
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.unit-menu-item:last-child {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.unit-number {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
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

/* Units Pagination */
.units-pagination {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.units-pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f9fafb;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.units-pagination .pagination-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #fff;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.units-pagination .pagination-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.units-pagination .pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.units-pagination .chevron-pagination-icon {
  width: 20px;
  height: 20px;
  color: #374151;
}

.units-pagination .page-number {
  min-width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.units-pagination .page-number:hover {
  background: #e5e7eb;
}

.units-pagination .page-number.active {
  background: #1500FF;
  color: white;
  font-weight: 600;
}

.units-pagination-info {
  font-size: 14px;
  color: #6b7280;
}
</style>
