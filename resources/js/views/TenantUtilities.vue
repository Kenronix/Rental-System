<script setup>
import { ref, onMounted, computed } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  BoltIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const utilities = ref([])
const isLoading = ref(false)
const error = ref(null)
const filterStatus = ref('All')

// Utility icons mapping
const utilityIcons = {
  electricity: BoltIcon,
  water: '💧', // Using emoji as fallback, can be replaced with custom icon
  internet: '📶' // Using emoji as fallback, can be replaced with custom icon
}

// Fetch utilities
const fetchUtilities = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/utilities')
    // For now, using mock data based on the image
    setTimeout(() => {
      utilities.value = [
        {
          id: 1,
          name: 'Electricity',
          type: 'electricity',
          amount: 84.50,
          dueDate: '2023-10-15',
          status: 'unpaid',
          icon: 'electricity'
        },
        {
          id: 2,
          name: 'Water',
          type: 'water',
          amount: 32.00,
          dueDate: '2023-10-05',
          status: 'paid',
          icon: 'water'
        },
        {
          id: 3,
          name: 'Internet',
          type: 'internet',
          amount: 60.00,
          dueDate: '2023-10-01',
          status: 'paid',
          icon: 'internet'
        }
      ]
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching utilities:', err)
    error.value = 'Failed to load utilities. Please try again.'
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
  return `${months[date.getMonth()]} ${date.getDate()}`
}

const filteredUtilities = computed(() => {
  if (filterStatus.value === 'All') {
    return utilities.value
  } else if (filterStatus.value === 'Paid') {
    return utilities.value.filter(u => u.status === 'paid')
  } else if (filterStatus.value === 'Unpaid') {
    return utilities.value.filter(u => u.status === 'unpaid')
  }
  return utilities.value
})

const handlePayNow = async (utilityId) => {
  try {
    // TODO: Replace with actual API endpoint
    // await api.post(`/tenant/utilities/${utilityId}/pay`)
    alert('Payment processing...')
    // Refresh utilities after payment
    await fetchUtilities()
  } catch (err) {
    console.error('Error processing payment:', err)
    alert('Failed to process payment. Please try again.')
  }
}

onMounted(() => {
  fetchUtilities()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">Utilities</h1>

      <!-- Filter Dropdown -->
      <div class="filter-section">
        <div class="filter-container">
          <select v-model="filterStatus" class="filter-dropdown">
            <option value="All">All</option>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
          </select>
          <ChevronDownIcon class="chevron-icon" />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading utilities...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchUtilities">Retry</button>
      </div>

      <!-- Utilities List -->
      <div v-else class="utilities-list">
        <div
          v-for="utility in filteredUtilities"
          :key="utility.id"
          :class="['utility-card', { 'utility-card-highlighted': utility.status === 'unpaid' }]"
        >
          <div class="utility-icon-container">
            <BoltIcon v-if="utility.icon === 'electricity'" class="utility-icon" />
            <span v-else-if="utility.icon === 'water'" class="utility-icon-emoji">💧</span>
            <span v-else-if="utility.icon === 'internet'" class="utility-icon-emoji">📶</span>
          </div>
          
          <div class="utility-info">
            <h3 class="utility-name">{{ utility.name }}</h3>
            <p class="utility-due">Due: {{ formatDate(utility.dueDate) }}</p>
          </div>

          <div class="utility-amount">
            <p class="amount-text">{{ formatCurrency(utility.amount) }}</p>
          </div>

          <div class="utility-action">
            <button
              v-if="utility.status === 'unpaid'"
              class="pay-button"
              @click="handlePayNow(utility.id)"
            >
              Pay Now
            </button>
            <span v-else class="paid-badge">Paid</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!isLoading && !error && filteredUtilities.length === 0" class="empty-state">
        <p>No utilities found.</p>
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

.page-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 24px;
}

/* Filter Section */
.filter-section {
  margin-bottom: 24px;
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
  min-width: 120px;
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

/* Utilities List */
.utilities-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.utility-card {
  display: flex;
  align-items: center;
  gap: 20px;
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
}

.utility-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.utility-card-highlighted {
  background: #EFF6FF;
  border: 2px solid #1500FF;
}

.utility-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}

.utility-icon {
  width: 32px;
  height: 32px;
  color: #1500FF;
}

.utility-icon-emoji {
  font-size: 32px;
  line-height: 1;
}

.utility-info {
  flex: 1;
  min-width: 0;
}

.utility-name {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.utility-due {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.utility-amount {
  display: flex;
  align-items: center;
  margin-right: auto;
}

.amount-text {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.utility-action {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.pay-button {
  padding: 10px 24px;
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

.pay-button:hover {
  background: #1200e6;
}

.paid-badge {
  padding: 6px 16px;
  background: #dcfce7;
  color: #15803d;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: #6b7280;
}

/* Responsive Design */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .utility-card {
    flex-wrap: wrap;
    gap: 12px;
  }

  .utility-amount {
    margin-right: 0;
    width: 100%;
  }

  .utility-action {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
