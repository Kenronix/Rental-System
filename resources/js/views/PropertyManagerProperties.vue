<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import { MagnifyingGlassIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const properties = ref([])
const loading = ref(true)
const searchQuery = ref('')
const selectedType = ref('All Types')

// Computed properties for filtering
const filteredProperties = computed(() => {
  return properties.value.filter(property => {
    // Search filter
    const matchesSearch = property.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          property.location?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          property.city?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    // Type filter
    const matchesType = selectedType.value === 'All Types' || property.type === selectedType.value.toLowerCase()

    return matchesSearch && matchesType
  })
})

const fetchProperties = async () => {
    try {
        const response = await api.get('/property-manager/properties')
        if (response.data.success) {
            properties.value = response.data.properties
        }
    } catch (error) {
        console.error('Error fetching properties:', error)
    } finally {
        loading.value = false
    }
}

const viewDetails = (id) => {
    // Assuming a details page route will be created later or exists
    router.push(`/property-manager/prop-${id}`)
}

onMounted(() => {
    fetchProperties()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">Properties</h1>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="search-wrapper">
            <MagnifyingGlassIcon class="search-icon" />
            <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search Property..." 
                class="search-input"
            />
        </div>
        <div class="filter-dropdown">
            <select v-model="selectedType" class="type-select">
                <option>All Types</option>
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
            </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        Loading properties...
      </div>

      <!-- Properties Grid -->
      <div v-else class="properties-grid">
        <div v-for="property in filteredProperties" :key="property.id" class="property-card">
            <div class="card-image">
                <img :src="property.main_photo || '/placeholder-property.jpg'" :alt="property.name" class="prop-img" />
            </div>
            <div class="card-content">
                <h3 class="prop-name">{{ property.name }}</h3>
                <div class="prop-location">
                    <MapPinIcon class="location-icon" />
                    <span>{{ property.city || property.location || 'Unknown Location' }}</span>
                </div>
                
                <div class="prop-stats">
                    <div class="stat-item">
                        <span class="stat-label">Units</span>
                        <span class="stat-value">{{ property.total_units || 0 }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Tenants</span>
                        <span class="stat-value">{{ property.occupied_units || 0 }}</span>
                    </div>
                </div>

                <button @click="viewDetails(property.id)" class="view-btn">
                    View Details
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && filteredProperties.length === 0" class="empty-state">
            No properties found matching your criteria.
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

/* Filters */
.filters-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
}

.search-wrapper {
    position: relative;
    width: 300px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #9ca3af;
}

.search-input {
    width: 100%;
    padding: 10px 10px 10px 40px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.search-input:focus {
    border-color: #1500FF;
}

.type-select {
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    outline: none;
}

/* Grid */
.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.property-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.property-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.card-image {
    height: 200px;
    background: #e5e7eb;
}

.prop-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-content {
    padding: 20px;
}

.prop-name {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.prop-location {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 20px;
}

.location-icon {
    width: 16px;
    height: 16px;
}

.prop-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
    text-transform: uppercase;
    font-weight: 600;
}

.stat-value {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

.view-btn {
    width: 100%;
    padding: 10px;
    background: #1500FF;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.view-btn:hover {
    background: #1200e6;
}

.loading-state, .empty-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
    font-style: italic;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}
</style>
