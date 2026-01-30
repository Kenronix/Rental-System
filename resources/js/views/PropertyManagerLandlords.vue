<script setup>
import { ref, onMounted, computed } from 'vue'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import { 
    MagnifyingGlassIcon, 
    BuildingOfficeIcon, 
    EyeIcon, 
    PencilSquareIcon, 
    EllipsisVerticalIcon 
} from '@heroicons/vue/24/outline'

const landlords = ref([])
const stats = ref({
    total_landlords: 0,
    total_properties: 0,
    monthly_revenue: 0
})
const loading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref('All Status')
const selectedPropertyFilter = ref('All Properties') // Placeholder

const fetchLandlords = async () => {
    try {
        const response = await api.get('/property-manager/landlords')
        if (response.data.success) {
            landlords.value = response.data.landlords
            stats.value = response.data.stats
        }
    } catch (error) {
        console.error('Error fetching landlords:', error)
    } finally {
        loading.value = false
    }
}

const filteredLandlords = computed(() => {
    return landlords.value.filter(landlord => {
        const matchesSearch = landlord.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                              landlord.email.toLowerCase().includes(searchQuery.value.toLowerCase())
        // Assuming status is always Active for now as per controller
        const matchesStatus = selectedStatus.value === 'All Status' || landlord.status === selectedStatus.value

        return matchesSearch && matchesStatus
    })
})

const formatCurrency = (amount) => {
    return `₱${Number(amount).toLocaleString()}`
}

onMounted(() => {
    fetchLandlords()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">Landlords</h1>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ stats.total_landlords }}</div>
            <div class="stat-label">Total Landlords</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ stats.total_properties }}</div>
            <div class="stat-label">Total Properties</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.monthly_revenue) }}</div>
            <div class="stat-label">Monthly Revenue</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="search-wrapper">
            <MagnifyingGlassIcon class="search-icon" />
            <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search landlords by name, email..." 
                class="search-input"
            />
        </div>
        <div class="filter-dropdown">
            <select v-model="selectedStatus" class="dropdown-select">
                <option>All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
        <div class="filter-dropdown">
            <select v-model="selectedPropertyFilter" class="dropdown-select">
                <option>All Properties</option>
                <!-- Placeholder, could populate dynamically -->
            </select>
        </div>
      </div>

      <!-- Landlords Table -->
      <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Landlord</th>
                    <th>Contact</th>
                    <th>Properties</th>
                    <th>Rent</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td colspan="6" class="loading-cell">Loading landlords...</td>
                </tr>
                <tr v-else-if="filteredLandlords.length === 0">
                    <td colspan="6" class="empty-cell">No landlords found.</td>
                </tr>
                <tr v-else v-for="landlord in filteredLandlords" :key="landlord.id">
                    <td>
                        <div class="landlord-info">
                            <div class="avatar">
                                <img v-if="landlord.avatar" :src="landlord.avatar" alt="Avatar" class="avatar-img" />
                                <span v-else>{{ landlord.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <span class="landlord-name">{{ landlord.name }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="contact-info">
                            <div class="email">{{ landlord.email }}</div>
                            <div class="phone">{{ landlord.phone || 'No phone' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="properties-count">
                            <BuildingOfficeIcon class="icon-xs" />
                            <span>{{ landlord.properties_count }} Properties</span>
                        </div>
                    </td>
                    <td>
                        <div class="rent-info">
                            {{ formatCurrency(landlord.monthly_revenue) }} <span class="per-month">per month</span>
                        </div>
                    </td>
                    <td>
                        <span :class="['status-badge', landlord.status.toLowerCase()]">
                            {{ landlord.status }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button class="action-btn" title="View"><EyeIcon class="action-icon" /></button>
                            <button class="action-btn" title="Edit"><PencilSquareIcon class="action-icon" /></button>
                            <button class="action-btn" title="More"><EllipsisVerticalIcon class="action-icon" /></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
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

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

/* Filters */
.filters-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    background: white; /* Sometimes filters are just loosely placed, but putting them in a bar looks neat. Wait, screenshot shows them floating. */
    background: transparent;
}

.search-wrapper {
    position: relative;
    flex: 1; /* Take simplified width */
    max-width: 400px;
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
    background: white;
}

.dropdown-select {
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    outline: none;
    min-width: 140px;
}

/* Table */
.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.data-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.data-table tr:last-child td {
    border-bottom: none;
}

/* Specific Columns */
.landlord-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb; /* Fallback */
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #4b5563;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.landlord-name {
    font-weight: 600;
    color: #111827;
    font-size: 14px;
}

.contact-info {
    display: flex;
    flex-direction: column;
}

.email {
    font-size: 14px;
    color: #111827;
    margin-bottom: 2px;
}

.phone {
    font-size: 12px;
    color: #6b7280;
}

.properties-count {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #4b5563;
    font-size: 13px;
    font-weight: 500;
}

.icon-xs {
    width: 16px;
    height: 16px;
    color: #6b7280;
}

.rent-info {
    font-weight: 600;
    color: #4b5563;
    font-size: 14px;
}

.per-month {
    font-weight: 400;
    color: #9ca3af;
    font-size: 12px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.active {
    background-color: #dcfce7;
    color: #16a34a;
}
.status-badge.inactive {
    background-color: #fee2e2;
    color: #ef4444;
}

.actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.action-icon {
    width: 18px;
    height: 18px;
}

.loading-cell, .empty-cell {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
