<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  MagnifyingGlassIcon,
  CalendarIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { ArrowDownTrayIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const reportData = ref([])
const statistics = ref({
  total_tenants: 0,
  paid_tenants: 0,
  unpaid_tenants: 0,
  total_rent: 0,
  total_paid: 0,
  total_unpaid: 0
})
const isLoading = ref(false)
const error = ref(null)
const filterDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM format
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Fetch report data
const fetchReport = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/reports', {
      params: {
        date: filterDate.value
      }
    })
    
    if (response.data.success) {
      reportData.value = response.data.report || []
      
      if (response.data.statistics) {
        statistics.value = {
          total_tenants: response.data.statistics.total_tenants || 0,
          paid_tenants: response.data.statistics.paid_tenants || 0,
          unpaid_tenants: response.data.statistics.unpaid_tenants || 0,
          total_rent: response.data.statistics.total_rent || 0,
          total_paid: response.data.statistics.total_paid || 0,
          total_unpaid: response.data.statistics.total_unpaid || 0
        }
      }
      
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching report:', err)
    error.value = 'Failed to load report. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Filter report data
const filteredReport = computed(() => {
  let filtered = reportData.value
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((item) => {
      const name = (item.tenant_name || '').toLowerCase()
      const email = (item.tenant_email || '').toLowerCase()
      const property = (item.property_name || '').toLowerCase()
      const unit = (item.unit_number || '').toLowerCase()
      return name.includes(q) || email.includes(q) || property.includes(q) || unit.includes(q)
    })
  }
  
  return filtered
})

// Pagination
const paginatedReport = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredReport.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredReport.value.length / itemsPerPage.value)
})

const resetToFirstPage = () => {
  currentPage.value = 1
}

// Format currency
const formatCurrency = (amount) => {
  if (!amount) return '₱0.00'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(amount)
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Format month display
const formatMonth = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString + '-01')
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
}

// Get payment status class
const getPaymentStatusClass = (status) => {
  switch (status.toLowerCase()) {
    case 'paid':
      return 'status-paid'
    case 'pending':
      return 'status-pending'
    case 'overdue':
      return 'status-overdue'
    default:
      return 'status-unpaid'
  }
}

// Download report
const downloadReport = async () => {
  try {
    const response = await api.get('/reports/download', {
      params: {
        date: filterDate.value
      },
      responseType: 'blob'
    })
    
    // Create blob and download
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `tenant_payment_report_${filterDate.value}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error downloading report:', err)
    alert('Failed to download report. Please try again.')
  }
}

// Handle date change
const handleDateChange = () => {
  fetchReport()
}

onMounted(() => {
  fetchReport()
})

onActivated(() => {
  fetchReport()
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Page Title -->
      <div class="page-header">
        <h1 class="page-title">Reports</h1>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value">{{ statistics.total_tenants }}</div>
          <div class="stat-label">Total Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.paid_tenants }}</div>
          <div class="stat-label">Paid Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.unpaid_tenants }}</div>
          <div class="stat-label">Unpaid Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.total_rent) }}</div>
          <div class="stat-label">Total Rent</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.total_paid) }}</div>
          <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.total_unpaid) }}</div>
          <div class="stat-label">Total Unpaid</div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="search-container">
          <MagnifyingGlassIcon class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search tenants by name, email, property, or unit..."
            @input="resetToFirstPage"
          />
        </div>
        <div class="filters-group">
          <div class="date-filter-container">
            <CalendarIcon class="calendar-icon" />
            <input
              type="month"
              v-model="filterDate"
              @change="handleDateChange"
              class="date-input"
            />
            <span class="date-label">{{ formatMonth(filterDate) }}</span>
          </div>
          <button class="download-btn" @click="downloadReport">
            <ArrowDownTrayIcon class="btn-icon" />
            <span>Download CSV</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading report...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchReport">Retry</button>
      </div>

      <!-- Report Table -->
      <div v-else class="report-table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Tenant Name</th>
              <th>Email</th>
              <th>Property / Unit</th>
              <th>Monthly Rent</th>
              <th>Payment Status</th>
              <th>Payment Amount</th>
              <th>Payment Date</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedReport.length === 0">
              <td colspan="8" class="empty-state">
                No tenants found
              </td>
            </tr>
            <tr v-for="item in paginatedReport" :key="`${item.tenant_id}-${item.unit_id}`">
              <td>
                <div class="tenant-name">{{ item.tenant_name }}</div>
              </td>
              <td>{{ item.tenant_email }}</td>
              <td>
                <div class="property-info">
                  <div class="property-name">{{ item.property_name }}</div>
                  <div class="unit-number">Unit {{ item.unit_number }}</div>
                </div>
              </td>
              <td class="amount-cell">{{ formatCurrency(item.monthly_rent) }}</td>
              <td>
                <span :class="['status-badge', getPaymentStatusClass(item.payment_status)]">
                  {{ item.payment_status }}
                </span>
              </td>
              <td>{{ item.payment_amount ? formatCurrency(item.payment_amount) : '-' }}</td>
              <td>{{ item.payment_date ? formatDate(item.payment_date) : '-' }}</td>
              <td>{{ item.due_date ? formatDate(item.due_date) : '-' }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination">
          <button 
            class="pagination-btn" 
            :disabled="currentPage === 1" 
            @click="currentPage--"
          >
            <ChevronLeftIcon class="pagination-icon" />
          </button>
          <span class="pagination-info">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <button 
            class="pagination-btn" 
            :disabled="currentPage === totalPages" 
            @click="currentPage++"
          >
            <ChevronRightIcon class="pagination-icon" />
          </button>
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
  font-family: 'Montserrat', sans-serif;
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
  margin-bottom: 30px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

/* Statistics Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1500FF;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

/* Filters Section */
.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-container {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #999;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 48px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #1500FF;
}

.filters-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.date-filter-container {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
}

.calendar-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.date-input {
  border: none;
  outline: none;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  color: #1a1a1a;
  background: transparent;
  cursor: pointer;
}

.date-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.download-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  cursor: pointer;
  transition: all 0.3s ease;
}

.download-btn:hover {
  background: #1200cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
}

.btn-icon {
  width: 20px;
  height: 20px;
}

/* Loading and Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.error-state {
  color: #dc2626;
}

.retry-btn {
  margin-top: 16px;
  padding: 10px 20px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
}

/* Report Table */
.report-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
}

.report-table thead {
  background: #f8f9fa;
}

.report-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
  border-bottom: 2px solid #e5e7eb;
}

.report-table td {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.report-table tbody tr:hover {
  background: #f9fafb;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

.tenant-name {
  font-weight: 600;
  color: #1a1a1a;
}

.property-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.property-name {
  font-weight: 600;
  color: #1a1a1a;
}

.unit-number {
  font-size: 12px;
  color: #666;
}

.amount-cell {
  font-weight: 600;
  color: #1500FF;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-paid {
  background: #d1fae5;
  color: #065f46;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-overdue {
  background: #fee2e2;
  color: #991b1b;
}

.status-unpaid {
  background: #f3f4f6;
  color: #6b7280;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 24px;
  border-top: 1px solid #e5e7eb;
}

.pagination-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.2s ease;
  font-family: 'Montserrat', sans-serif;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #1500FF;
  color: #1500FF;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-icon {
  width: 20px;
  height: 20px;
}

.pagination-info {
  font-size: 14px;
  color: #666;
}
</style>
