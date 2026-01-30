<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import AdminSidebar from '../components/layout/AdminSidebar.vue'
import PropertyCard from '../components/rentals/PropertyCard.vue'
import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/solid'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const router = useRouter()

const searchQuery = ref('')
const propertyTypeFilter = ref('All')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const allProperties = ref([])
const isLoading = ref(false)
const error = ref(null)

// Fetch all properties from all landlords (admin only)
const fetchProperties = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/admin/properties')
    
    if (response.data.success) {
      // Transform database properties to match PropertyCard format
      allProperties.value = response.data.properties.map(property => {
        // Get the image URL - prioritize main_photo, then first photo, then fallback
        let imageUrl = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop'
        
        if (property.main_photo) {
          imageUrl = property.main_photo
        } else if (property.photos && Array.isArray(property.photos) && property.photos.length > 0) {
          imageUrl = property.photos[0]
        }
        
        // Ensure the URL is absolute
        if (imageUrl && !imageUrl.startsWith('http') && !imageUrl.startsWith('//')) {
          // If it's a relative path, make it absolute
          if (imageUrl.startsWith('/')) {
            imageUrl = window.location.origin + imageUrl
          } else {
            imageUrl = window.location.origin + '/' + imageUrl
          }
        }
        
        return {
          id: property.id,
          name: property.name,
          location: `${property.street_address}, ${property.city}, ${property.state} ${property.zip_code}`,
          type: property.type,
          units: property.units || 0,
          tenants: property.tenants || 0,
          image: imageUrl,
          landlord: property.landlord ? property.landlord.name : 'Unknown'
        }
      })
    }
  } catch (err) {
    console.error('Error fetching properties:', err)
    error.value = 'Failed to load properties. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Load properties on mount and when route is activated
onMounted(() => {
  fetchProperties()
})

// Refresh properties when navigating back to this page
onActivated(() => {
  fetchProperties()
})

const filteredProperties = computed(() => {
  let filtered = allProperties.value
  
  // Filter by property type
  if (propertyTypeFilter.value !== 'All') {
    filtered = filtered.filter((p) => {
      const type = (p.type || '').toLowerCase()
      return type === propertyTypeFilter.value.toLowerCase()
    })
  }
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((p) => {
      const name = (p.name || '').toLowerCase()
      const location = (p.location || '').toLowerCase()
      const type = (p.type || '').toLowerCase()
      return name.includes(q) || location.includes(q) || type.includes(q)
    })
  }
  
  return filtered
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredProperties.value.length / itemsPerPage.value))
})

const properties = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredProperties.value.slice(start, end)
})

const paginationInfo = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, filteredProperties.value.length)
  const total = filteredProperties.value.length
  return { start, end, total }
})

const handleAddProperty = () => {
  router.push('/admin/properties/add')
}

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const goToNextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const resetToFirstPage = () => {
  currentPage.value = 1
}

const handleFilterChange = () => {
  resetToFirstPage()
}
</script>

<template>
  <div class="dashboard-layout">
    <AdminSidebar />
    <div class="main-content">
      <!-- Header -->
      <div class="page-header">
        <h1 class="page-title">Properties</h1>
      </div>

      <!-- Action Bar -->
      <div class="action-bar">
        <div class="filters-container">
          <div class="search-container">
            <MagnifyingGlassIcon class="search-icon" />
            <input
              v-model="searchQuery"
              type="text"
              class="search-input"
              placeholder="Search property..."
              @input="resetToFirstPage"
            />
          </div>
          <div class="filter-container">
            <select v-model="propertyTypeFilter" class="filter-dropdown" @change="handleFilterChange">
              <option value="All">All Types</option>
              <option value="Residential">Residential</option>
              <option value="Commercial">Commercial</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
        </div>
        <button class="add-property-btn" @click="handleAddProperty">
          <PlusIcon class="plus-icon" />
          <span>Add Property</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading properties...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchProperties">Retry</button>
      </div>

      <!-- Empty State -->
      <div v-else-if="properties.length === 0" class="empty-state">
        <p v-if="searchQuery.trim()">
          No properties match "{{ searchQuery.trim() }}". Try a different search.
        </p>
        <p v-else>No properties found. Click "Add Property" to create your first property.</p>
      </div>

      <!-- Properties Grid -->
      <div v-else class="properties-grid">
        <PropertyCard
          v-for="property in properties"
          :key="property.id"
          :property="property"
        />
      </div>

      <!-- Pagination -->
      <div v-if="!isLoading && !error && properties.length > 0" class="pagination-container">
        <div class="pagination">
          <button 
            class="pagination-btn" 
            :disabled="currentPage === 1"
            @click="goToPreviousPage"
          >
            <ChevronLeftIcon class="chevron-pagination-icon" />
          </button>
          <button 
            v-for="page in totalPages" 
            :key="page"
            :class="['page-number', { active: currentPage === page }]"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <button 
            class="pagination-btn" 
            :disabled="currentPage === totalPages"
            @click="goToNextPage"
          >
            <ChevronRightIcon class="chevron-pagination-icon" />
          </button>
        </div>
        <div class="pagination-info">
          Showing {{ paginationInfo.start }} to {{ paginationInfo.end }} of {{ paginationInfo.total }} results
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

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.filters-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-container {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 14px;
  width: 20px;
  height: 20px;
  color: #999;
  pointer-events: none;
}

.search-input {
  padding: 12px 16px 12px 44px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  min-width: 260px;
  outline: none;
  transition: border-color 0.2s;
}

.search-input::placeholder {
  color: #999;
}

.search-input:hover {
  border-color: #bbb;
}

.search-input:focus {
  border-color: #1500FF;
}

.filter-container {
  position: relative;
  display: inline-block;
}

.filter-dropdown {
  appearance: none;
  padding: 12px 40px 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  min-width: 150px;
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
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #666;
  pointer-events: none;
}

.add-property-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.add-property-btn:hover {
  background: #0f00cc;
}

.plus-icon {
  width: 20px;
  height: 20px;
}

.properties-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.pagination-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-top: 40px;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #f5f5f5;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #e5e5e5;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chevron-pagination-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.page-number {
  min-width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.page-number:hover {
  background: #f5f5f5;
}

.page-number.active {
  background: #1500FF;
  color: white;
  font-weight: 600;
}

.pagination-info {
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  color: #666;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  font-family: 'Montserrat', sans-serif;
}

.loading-state p,
.error-state p,
.empty-state p {
  font-size: 16px;
  color: #666;
  margin-bottom: 16px;
}

.retry-btn {
  padding: 10px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-btn:hover {
  background: #0f00cc;
}
</style>

