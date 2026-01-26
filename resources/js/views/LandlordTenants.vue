<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  MagnifyingGlassIcon,
  EyeIcon,
  PencilIcon,
  EllipsisVerticalIcon,
  EnvelopeIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const router = useRouter()

const tenants = ref([])
const allTenants = ref([])
const properties = ref([])
const statistics = ref({
  total_tenants: 0,
  active_leases: 0,
  pending_invites: 0,
  payment_issues: 0
})
const isLoading = ref(false)
const error = ref(null)
const showApplicationModal = ref(false)
const selectedApplication = ref(null)
const isLoadingApplication = ref(false)
const isUpdatingStatus = ref(false)

const searchQuery = ref('')
const statusFilter = ref('All')
const propertyFilter = ref('All')
const currentPage = ref(1)
const itemsPerPage = ref(5)

// Fetch tenants data
const fetchTenants = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/tenants')
    
    if (response.data.success) {
      allTenants.value = response.data.tenants || []
      
      // Set statistics with proper defaults
      if (response.data.statistics) {
        statistics.value = {
          total_tenants: response.data.statistics.total_tenants || 0,
          active_leases: response.data.statistics.active_leases || 0,
          pending_invites: response.data.statistics.pending_invites || 0,
          payment_issues: response.data.statistics.payment_issues || 0
        }
      } else {
        statistics.value = {
          total_tenants: 0,
          active_leases: 0,
          pending_invites: 0,
          payment_issues: 0
        }
      }
      
      // Get unique properties for filter
      const uniqueProperties = [...new Set((response.data.tenants || []).map(t => t.property_name).filter(Boolean))]
      properties.value = uniqueProperties
      
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching tenants:', err)
    error.value = 'Failed to load tenants. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Filter tenants
const filteredTenants = computed(() => {
  let filtered = allTenants.value
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((tenant) => {
      const name = (tenant.name || '').toLowerCase()
      const email = (tenant.email || '').toLowerCase()
      const property = (tenant.property_name || '').toLowerCase()
      return name.includes(q) || email.includes(q) || property.includes(q)
    })
  }
  
  // Filter by status
  if (statusFilter.value !== 'All') {
    filtered = filtered.filter((tenant) => {
      return tenant.status === statusFilter.value.toLowerCase()
    })
  }
  
  // Filter by property
  if (propertyFilter.value !== 'All') {
    filtered = filtered.filter((tenant) => {
      return tenant.property_name === propertyFilter.value
    })
  }
  
  return filtered
})

// Pagination
const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredTenants.value.length / itemsPerPage.value))
})

const paginatedTenants = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredTenants.value.slice(start, end)
})

const paginationInfo = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, filteredTenants.value.length)
  const total = filteredTenants.value.length
  return { start, end, total }
})

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

// Format currency
const formatCurrency = (amount) => {
  if (!amount) return '₱0'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Get status badge class
const getStatusClass = (status) => {
  switch (status) {
    case 'active':
      return 'status-active'
    case 'inactive':
      return 'status-inactive'
    case 'pending':
      return 'status-pending'
    case 'approved':
      return 'status-approved'
    case 'rejected':
      return 'status-rejected'
    default:
      return ''
  }
}

// Format lease start date (handle applications)
const formatLeaseStart = (dateString, type) => {
  if (type === 'application') {
    return 'Pending'
  }
  return formatDate(dateString)
}

// Actions
const handleViewTenant = async (tenant) => {
  // If it's an application, show application details
  if (tenant.type === 'application' && tenant.application_id) {
    await openApplicationModal(tenant.application_id)
  } else {
    // Navigate to tenant details page for actual tenants
    console.log('View tenant:', tenant)
  }
}

const openApplicationModal = async (applicationId) => {
  isLoadingApplication.value = true
  showApplicationModal.value = true
  
  try {
    const response = await api.get(`/tenant-applications/${applicationId}`)
    if (response.data.success) {
      selectedApplication.value = response.data.application
    } else {
      error.value = 'Failed to load application details.'
      showApplicationModal.value = false
    }
  } catch (err) {
    console.error('Error fetching application:', err)
    error.value = 'Failed to load application details.'
    showApplicationModal.value = false
  } finally {
    isLoadingApplication.value = false
  }
}

const closeApplicationModal = () => {
  showApplicationModal.value = false
  selectedApplication.value = null
}

// Approve application
const approveApplication = async () => {
  if (!selectedApplication.value || !selectedApplication.value.id) return
  
  isUpdatingStatus.value = true
  try {
    const response = await api.put(`/tenant-applications/${selectedApplication.value.id}/approve`)
    if (response.data.success) {
      // Close the modal first
      closeApplicationModal()
      // Small delay to ensure database is committed
      await new Promise(resolve => setTimeout(resolve, 100))
      // Refresh the tenants list to get updated statistics
      await fetchTenants()
      alert('Application approved successfully! Tenant has been assigned to the unit.')
    } else {
      alert(response.data.message || 'Failed to approve application.')
    }
  } catch (err) {
    console.error('Error approving application:', err)
    alert(err.response?.data?.message || 'Failed to approve application. Please try again.')
  } finally {
    isUpdatingStatus.value = false
  }
}

// Reject application
const rejectApplication = async () => {
  if (!selectedApplication.value || !selectedApplication.value.id) return
  
  if (!confirm('Are you sure you want to reject this application?')) {
    return
  }
  
  isUpdatingStatus.value = true
  try {
    const response = await api.put(`/tenant-applications/${selectedApplication.value.id}/reject`)
    if (response.data.success) {
      // Close the modal
      closeApplicationModal()
      // Refresh the tenants list to get updated statistics
      await fetchTenants()
      alert('Application rejected.')
    } else {
      alert(response.data.message || 'Failed to reject application.')
    }
  } catch (err) {
    console.error('Error rejecting application:', err)
    alert(err.response?.data?.message || 'Failed to reject application. Please try again.')
  } finally {
    isUpdatingStatus.value = false
  }
}

const handleEditTenant = (tenant) => {
  // Navigate to edit tenant page
  console.log('Edit tenant:', tenant)
}

const handleInviteTenant = () => {
  // Open invite tenant modal or navigate to invite page
  console.log('Invite tenant')
}

// Format currency for display
const formatCurrencyDisplay = (amount) => {
  if (!amount) return '₱0'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Get image URL
const getImageUrl = (imagePath) => {
  if (!imagePath) return ''
  
  // If it's already a full URL, return as is
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  
  // If it already starts with /storage/, return as is
  if (imagePath.startsWith('/storage/')) {
    return imagePath
  }
  
  // If it starts with storage/ (without leading slash), add leading slash
  if (imagePath.startsWith('storage/')) {
    return '/' + imagePath
  }
  
  // Otherwise, prepend /storage/
  return '/storage/' + imagePath
}

// Handle image load error
const handleImageError = (event) => {
  console.error('Failed to load image:', event.target.src)
  // Optionally show placeholder on error
  event.target.style.display = 'none'
  const container = event.target.closest('.id-picture-wrapper')
  if (container) {
    container.innerHTML = `
      <div class="id-picture-placeholder">
        <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="placeholder-text">ID Picture</span>
      </div>
    `
  }
}

onMounted(() => {
  fetchTenants()
})

onActivated(() => {
  fetchTenants()
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">Tenants</h1>

      <!-- Statistics Cards -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value">{{ statistics.total_tenants }}</div>
          <div class="stat-label">Total Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.active_leases }}</div>
          <div class="stat-label">Active Leases</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.pending_invites }}</div>
          <div class="stat-label">Pending Invites</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.payment_issues }}</div>
          <div class="stat-label">Payment Issues</div>
        </div>
      </div>

      <!-- Search and Filter Section -->
      <div class="filters-section">
        <div class="search-container">
          <MagnifyingGlassIcon class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search tenants by name, email, or property..."
            @input="resetToFirstPage"
          />
        </div>
        <div class="filters-group">
          <div class="filter-container">
            <select v-model="statusFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div class="filter-container">
            <select v-model="propertyFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Properties</option>
              <option v-for="property in properties" :key="property" :value="property">
                {{ property }}
              </option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <button class="invite-btn" @click="handleInviteTenant">
            <EnvelopeIcon class="invite-icon" />
            <span>Invite Tenant</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading tenants...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchTenants">Retry</button>
      </div>

      <!-- Tenants Table -->
      <div v-else-if="paginatedTenants.length > 0" class="table-container">
        <table class="tenants-table">
          <thead>
            <tr>
              <th>Tenant</th>
              <th>Property</th>
              <th>Unit</th>
              <th>Rent</th>
              <th>Lease Start</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tenant in paginatedTenants" :key="tenant.id">
              <td class="tenant-cell">
                <div class="tenant-info">
                  <div class="tenant-avatar">
                    {{ tenant.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="tenant-details">
                    <div class="tenant-name">{{ tenant.name }}</div>
                    <div class="tenant-email">{{ tenant.email }}</div>
                  </div>
                </div>
              </td>
              <td>{{ tenant.property_name }}</td>
              <td>{{ tenant.unit_number }}</td>
              <td>{{ formatCurrency(tenant.rent) }} per month</td>
              <td>{{ formatLeaseStart(tenant.lease_start, tenant.type) }}</td>
              <td>
                <span :class="['status-badge', getStatusClass(tenant.status)]">
                  {{ tenant.status.charAt(0).toUpperCase() + tenant.status.slice(1) }}
                </span>
              </td>
              <td class="actions-cell">
                <div class="actions-group">
                  <button class="action-btn" @click="handleViewTenant(tenant)" title="View Details">
                    <EyeIcon class="action-icon" />
                  </button>
                  <!-- Only show Edit and More Options for non-pending applications -->
                  <template v-if="tenant.status !== 'pending' || tenant.type !== 'application'">
                    <button class="action-btn" @click="handleEditTenant(tenant)" title="Edit">
                      <PencilIcon class="action-icon" />
                    </button>
                    <button class="action-btn" title="More Options">
                      <EllipsisVerticalIcon class="action-icon" />
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <p>No tenants found. {{ searchQuery || statusFilter !== 'All' || propertyFilter !== 'All' ? 'Try adjusting your filters.' : 'Invite tenants to get started.' }}</p>
      </div>

      <!-- Pagination -->
      <div v-if="!isLoading && !error && filteredTenants.length > 0" class="pagination-container">
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

      <!-- Application Details Modal -->
      <div v-if="showApplicationModal" class="modal-overlay" @click.self="closeApplicationModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">Tenant Application Details</h2>
            <button class="modal-close-btn" @click="closeApplicationModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>

          <div v-if="isLoadingApplication" class="modal-loading">
            <p>Loading application details...</p>
          </div>

          <div v-else-if="selectedApplication" class="modal-content">
            <!-- ID Picture -->
            <div class="detail-section">
              <h3 class="section-title">ID Picture</h3>
              <div class="id-picture-container">
                <div v-if="selectedApplication.id_picture" class="id-picture-wrapper">
                  <img 
                    :src="getImageUrl(selectedApplication.id_picture)" 
                    alt="ID Picture" 
                    class="id-picture-image"
                    @error="handleImageError"
                  />
                </div>
                <div v-else class="id-picture-placeholder">
                  <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="placeholder-text">ID Picture</span>
                </div>
              </div>
            </div>

            <!-- Personal Information -->
            <div class="detail-section">
              <h3 class="section-title">Personal Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Full Name</label>
                  <p class="detail-value">{{ selectedApplication.name }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.email }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">WhatsApp</label>
                  <p class="detail-value">{{ selectedApplication.whatsapp }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Occupation</label>
                  <p class="detail-value">{{ selectedApplication.occupation }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Monthly Income</label>
                  <p class="detail-value">{{ formatCurrencyDisplay(selectedApplication.monthly_income) }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Number of People</label>
                  <p class="detail-value">{{ selectedApplication.number_of_people }}</p>
                </div>
              </div>
            </div>

            <!-- Mother's Information -->
            <div class="detail-section">
              <h3 class="section-title">Mother's Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedApplication.mother_name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.mother_address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.mother_phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.mother_email }}</p>
                </div>
              </div>
            </div>

            <!-- Father's Information -->
            <div class="detail-section">
              <h3 class="section-title">Father's Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedApplication.father_name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.father_address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.father_phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.father_email }}</p>
                </div>
              </div>
            </div>

            <!-- Application Status -->
            <div class="detail-section">
              <h3 class="section-title">Application Status</h3>
              <div class="status-display">
                <span :class="['status-badge', getStatusClass(selectedApplication.status)]">
                  {{ selectedApplication.status.charAt(0).toUpperCase() + selectedApplication.status.slice(1) }}
                </span>
              </div>
            </div>

            <!-- Action Buttons (only for pending applications) -->
            <div v-if="selectedApplication.status === 'pending'" class="detail-section">
              <div class="modal-actions">
                <button 
                  class="btn btn-approve" 
                  @click="approveApplication"
                  :disabled="isUpdatingStatus"
                >
                  {{ isUpdatingStatus ? 'Processing...' : 'Approve' }}
                </button>
                <button 
                  class="btn btn-reject" 
                  @click="rejectApplication"
                  :disabled="isUpdatingStatus"
                >
                  {{ isUpdatingStatus ? 'Processing...' : 'Reject' }}
                </button>
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

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 32px 0;
}

/* Statistics Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
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
  gap: 12px;
  margin-bottom: 24px;
  align-items: center;
}

.search-container {
  position: relative;
  flex: 1;
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
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
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

.filters-group {
  display: flex;
  gap: 12px;
  align-items: center;
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
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #666;
  pointer-events: none;
}

.invite-btn {
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

.invite-btn:hover {
  background: #1200e6;
}

.invite-icon {
  width: 20px;
  height: 20px;
}

/* Table */
.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
}

.tenants-table {
  width: 100%;
  border-collapse: collapse;
}

.tenants-table thead {
  background: #f9f9f9;
}

.tenants-table th {
  padding: 16px;
  text-align: left;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #e5e5e5;
}

.tenants-table td {
  padding: 16px;
  border-bottom: 1px solid #e5e5e5;
  font-size: 14px;
  color: #333;
}

.tenants-table tbody tr:hover {
  background: #f9f9f9;
}

.tenants-table tbody tr:last-child td {
  border-bottom: none;
}

/* Tenant Cell */
.tenant-cell {
  min-width: 250px;
}

.tenant-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tenant-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #1500FF;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
  flex-shrink: 0;
}

.tenant-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tenant-name {
  font-weight: 600;
  color: #1a1a1a;
}

.tenant-email {
  font-size: 12px;
  color: #666;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

/* Actions */
.actions-cell {
  width: 120px;
}

.actions-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  color: #666;
}

.action-btn:hover {
  background: #f0f0f0;
  color: #1500FF;
}

.action-icon {
  width: 18px;
  height: 18px;
}

/* Pagination */
.pagination-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
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

/* Loading, Error, Empty States */
.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  background: #1200e6;
}

@media (max-width: 1200px) {
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e5e5;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.modal-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #f5f5f5;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.modal-close-btn:hover {
  background: #e5e5e5;
}

.close-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.modal-loading {
  padding: 60px 24px;
  text-align: center;
  color: #666;
}

.modal-content {
  padding: 24px;
}

.detail-section {
  margin-bottom: 32px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section .section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e5e5;
}

.id-picture-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 20px;
  background: #f9f9f9;
  border-radius: 8px;
  min-height: 200px;
}

.id-picture-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.id-picture-image {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
  object-fit: contain;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.id-picture-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #999;
}

.placeholder-icon {
  width: 64px;
  height: 64px;
  color: #ccc;
}

.placeholder-text {
  font-size: 14px;
  color: #999;
  font-weight: 500;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  color: #1a1a1a;
  margin: 0;
  word-break: break-word;
}

.status-display {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  padding-top: 8px;
}

.btn {
  padding: 12px 32px;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-approve {
  background: #10b981;
  color: white;
}

.btn-approve:hover:not(:disabled) {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover:not(:disabled) {
  background: #dc2626;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-group {
    flex-wrap: wrap;
  }

  .stats-container {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: auto;
  }

  .modal-container {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
