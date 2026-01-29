<script setup>
import { ref, computed, watch, onMounted, onActivated, onUnmounted } from 'vue'
import { Teleport } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  MagnifyingGlassIcon,
  TrashIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDownIcon,
  EllipsisVerticalIcon,
  ShareIcon,
  EyeIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import { PlusIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const payments = ref([])
const allPayments = ref([])
const tenants = ref([])
const properties = ref([])
const units = ref([])
const statistics = ref({
  total_payments: 0,
  total_amount: 0,
  paid_amount: 0,
  pending_amount: 0,
  overdue_amount: 0,
  paid_count: 0,
  pending_count: 0,
  overdue_count: 0
})
const isLoading = ref(false)
const error = ref(null)
const showPaymentModal = ref(false)
const showDeleteModal = ref(false)
const showDetailsModal = ref(false)
const selectedPayment = ref(null)
const isSubmitting = ref(false)
const formErrors = ref({})
const openDropdownId = ref(null)
const dropdownPosition = ref({ top: 0, right: 0 })

const searchQuery = ref('')
const statusFilter = ref('All')
const paymentTypeFilter = ref('All')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Form data
const formData = ref({
  property_id: '',
  unit_id: '',
  tenant_id: '',
  payment_type: 'rent',
  amount: '',
  water: '',
  electricity: '',
  internet: '',
  payment_date: '',
  due_date: '',
  status: 'pending',
  payment_method: '',
  reference_number: '',
  notes: ''
})

// Fetch payments data
const fetchPayments = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/payments')
    
    if (response.data.success) {
      allPayments.value = response.data.payments || []
      
      if (response.data.statistics) {
        statistics.value = {
          total_payments: response.data.statistics.total_payments || 0,
          total_amount: response.data.statistics.total_amount || 0,
          paid_amount: response.data.statistics.paid_amount || 0,
          pending_amount: response.data.statistics.pending_amount || 0,
          overdue_amount: response.data.statistics.overdue_amount || 0,
          paid_count: response.data.statistics.paid_count || 0,
          pending_count: response.data.statistics.pending_count || 0,
          overdue_count: response.data.statistics.overdue_count || 0
        }
      }
      
      // Fetch tenants for dropdown
      await fetchTenants()
      
      // Fetch properties for dropdown
      await fetchProperties()
      
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching payments:', err)
    error.value = 'Failed to load payments. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Fetch tenants for dropdown
const fetchTenants = async () => {
  try {
    const response = await api.get('/tenants')
    if (response.data.success) {
      // Only get actual tenants (not applications)
      tenants.value = (response.data.tenants || []).filter(t => t.type === 'tenant' && t.id)
    }
  } catch (err) {
    console.error('Error fetching tenants:', err)
  }
}

// Fetch properties for dropdown
const fetchProperties = async () => {
  try {
    const response = await api.get('/properties')
    if (response.data.success) {
      properties.value = response.data.properties || []
    }
  } catch (err) {
    console.error('Error fetching properties:', err)
  }
}

// Fetch units for selected property
const fetchUnits = async (propertyId) => {
  if (!propertyId) {
    units.value = []
    return
  }
  
  try {
    const response = await api.get(`/properties/${propertyId}/units`)
    if (response.data.success) {
      units.value = (response.data.units || []).map(unit => ({
        ...unit,
        tenant_name: unit.tenant ? unit.tenant.name : null
      }))
    }
  } catch (err) {
    console.error('Error fetching units:', err)
    units.value = []
  }
}

// Get units for selected tenant
const getUnitsForTenant = computed(() => {
  if (!formData.value.tenant_id) return []
  const tenant = tenants.value.find(t => t.id === parseInt(formData.value.tenant_id))
  if (!tenant) return []
  return [{ id: tenant.unit_id, unit_number: tenant.unit_number, property_name: tenant.property_name }]
})

// Filter payments
const filteredPayments = computed(() => {
  let filtered = allPayments.value
  
  // Filter by payment type
  if (paymentTypeFilter.value !== 'All') {
    filtered = filtered.filter((payment) => {
      const paymentType = payment.payment_type || 'rent'
      return paymentType === paymentTypeFilter.value
    })
  }
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((payment) => {
      const tenantName = (payment.tenant_name || '').toLowerCase()
      const propertyName = (payment.property_name || '').toLowerCase()
      const unitNumber = (payment.unit_number || '').toLowerCase()
      const reference = (payment.reference_number || '').toLowerCase()
      return tenantName.includes(q) || propertyName.includes(q) || unitNumber.includes(q) || reference.includes(q)
    })
  }
  
  // Filter by status
  if (statusFilter.value !== 'All') {
    if (statusFilter.value === 'pending_review') {
      // Filter for PENDING (tenant submitted proof)
      filtered = filtered.filter((payment) => payment.review_status === 'pending_review')
    } else if (statusFilter.value === 'pending') {
      // Filter for NOT PAID (status = pending but no review_status)
      filtered = filtered.filter((payment) => payment.status === 'pending' && !payment.review_status)
    } else {
      filtered = filtered.filter((payment) => payment.status === statusFilter.value)
    }
  }
  
  return filtered
})

// Computed statistics based on filtered payments
const computedStatistics = computed(() => {
  const filtered = filteredPayments.value
  
  const totalPayments = filtered.length
  const paidPayments = filtered.filter(p => p.status === 'paid')
  const pendingPayments = filtered.filter(p => p.status === 'pending')
  const overduePayments = filtered.filter(p => p.status === 'overdue')
  
  return {
    total_payments: totalPayments,
    paid_amount: paidPayments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0),
    pending_amount: pendingPayments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0),
    overdue_amount: overduePayments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0),
    paid_count: paidPayments.length,
    pending_count: pendingPayments.length,
    overdue_count: overduePayments.length
  }
})

// Pagination
const paginatedPayments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredPayments.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredPayments.value.length / itemsPerPage.value)
})

const resetToFirstPage = () => {
  currentPage.value = 1
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

const paginationInfo = computed(() => {
  const total = filteredPayments.value.length
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, total)
  return { start, end, total }
})

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
  if (!dateString) return 'No date'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Get status class
const getStatusClass = (payment) => {
  // If payment is approved and paid, show paid style
  if (payment.review_status === 'approved' && payment.status === 'paid') {
    return 'status-paid'
  }
  
  // If tenant submitted proof (pending_review), show pending/review style
  if (payment.review_status === 'pending_review') {
    return 'status-review'
  }
  
  // If payment is created but no proof submitted yet (NOT PAID), show pending style
  if (payment.status === 'pending' && !payment.review_status) {
    return 'status-pending'
  }
  
  // Default status classes
  switch (payment.status) {
    case 'paid':
      return 'status-paid'
    case 'pending':
      return 'status-pending'
    case 'overdue':
      return 'status-overdue'
    default:
      return ''
  }
}

// Get status display text
const getStatusText = (payment) => {
  // If payment is approved and paid, show Paid
  if (payment.review_status === 'approved' && payment.status === 'paid') {
    return 'Paid'
  }
  
  // If tenant submitted proof (pending_review), show Pending
  if (payment.review_status === 'pending_review') {
    return 'Pending'
  }
  
  // If payment is created but no proof submitted yet (status = pending, no review_status), show Not Paid
  if (payment.status === 'pending' && !payment.review_status) {
    return 'Not Paid'
  }
  
  // Default: show capitalized status
  return payment.status.charAt(0).toUpperCase() + payment.status.slice(1)
}

// Open add payment modal
const openAddPaymentModal = () => {
  selectedPayment.value = null
  formData.value = {
    property_id: '',
    unit_id: '',
    tenant_id: '',
    payment_type: 'rent',
    amount: '',
    water: '',
    electricity: '',
    internet: '',
    payment_date: '',
    due_date: '',
    status: 'pending',
    payment_method: '',
    reference_number: '',
    notes: ''
  }
  units.value = []
  formErrors.value = {}
  showPaymentModal.value = true
}

// Download receipt for a payment (approved/paid only)
const handleDownloadReceipt = async (payment) => {
  const isPaid = payment.review_status === 'approved' && payment.status === 'paid'
  if (!isPaid) {
    alert('Receipt can only be downloaded for approved payments.')
    return
  }
  try {
    const response = await api.get(`/payments/${payment.id}/receipt`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Receipt-${payment.id}-${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error downloading receipt:', err)
    alert(err.response?.data?.message || 'Failed to download receipt. Please try again.')
  }
}

// Close payment modal
const closePaymentModal = () => {
  showPaymentModal.value = false
  selectedPayment.value = null
  formData.value = {
    property_id: '',
    unit_id: '',
    tenant_id: '',
    payment_type: 'rent',
    amount: '',
    water: '',
    electricity: '',
    internet: '',
    payment_date: new Date().toISOString().split('T')[0],
    due_date: '',
    status: 'pending',
    payment_method: '',
    reference_number: '',
    notes: ''
  }
  units.value = []
  formErrors.value = {}
}

// Handle property selection
const handlePropertyChange = async () => {
  formData.value.unit_id = ''
  formData.value.tenant_id = ''
  formData.value.amount = ''
  
  if (formData.value.property_id) {
    await fetchUnits(parseInt(formData.value.property_id))
  } else {
    units.value = []
  }
}

// Handle unit selection
const handleUnitChange = () => {
  if (formData.value.unit_id) {
    const unit = units.value.find(u => u.id === parseInt(formData.value.unit_id))
    
    if (unit) {
      // Auto-fill tenant if unit has a tenant
      if (unit.tenant_id) {
        formData.value.tenant_id = unit.tenant_id.toString()
      } else {
        formData.value.tenant_id = ''
      }
      
      // Auto-fill amount based on payment type
      if (formData.value.payment_type === 'rent' && unit.monthly_rent) {
        formData.value.amount = unit.monthly_rent.toString()
      } else if (formData.value.payment_type === 'utility') {
        formData.value.amount = ''
      }
    }
  } else {
    formData.value.tenant_id = ''
    formData.value.amount = ''
  }
}

// Get tenant name for display
const getTenantName = () => {
  if (!formData.value.unit_id) {
    return ''
  }
  
  const unit = units.value.find(u => u.id === parseInt(formData.value.unit_id))
  if (unit) {
    if (unit.tenant && unit.tenant.name) {
      return unit.tenant.name
    }
    if (unit.tenant_name) {
      return unit.tenant_name
    }
    if (formData.value.tenant_id) {
      const tenant = tenants.value.find(t => t.id === parseInt(formData.value.tenant_id))
      if (tenant) {
        return tenant.name
      }
    }
    return 'No tenant assigned to this unit'
  }
  
  return ''
}

// Auto-calculate total amount for utilities
const calculateUtilityTotal = () => {
  if (formData.value.payment_type === 'utility') {
    const water = parseFloat(formData.value.water) || 0
    const electricity = parseFloat(formData.value.electricity) || 0
    const internet = parseFloat(formData.value.internet) || 0
    const total = water + electricity + internet
    if (total > 0) {
      formData.value.amount = total.toFixed(2)
    }
  }
}

// Watch utility fields to auto-calculate total
watch([() => formData.value.water, () => formData.value.electricity, () => formData.value.internet, () => formData.value.payment_type], () => {
  if (formData.value.payment_type === 'utility') {
    calculateUtilityTotal()
  } else {
    // Clear utility fields when switching to rent
    formData.value.water = ''
    formData.value.electricity = ''
    formData.value.internet = ''
  }
})

// Watch payment_type to handle amount field
watch(() => formData.value.payment_type, (newType) => {
  if (newType === 'utility') {
    // Clear amount for utility (will be calculated from breakdown)
    formData.value.amount = ''
  } else if (newType === 'rent' && formData.value.unit_id) {
    // Re-fill amount from unit if switching back to rent
    handleUnitChange()
  }
})


// Submit payment
const submitPayment = async () => {
  formErrors.value = {} 
  isSubmitting.value = true
  
  try {
    const payload = {
      unit_id: parseInt(formData.value.unit_id),
      payment_type: formData.value.payment_type,
      amount: parseFloat(formData.value.amount),
      water: formData.value.payment_type === 'utility' && formData.value.water ? parseFloat(formData.value.water) : null,
      electricity: formData.value.payment_type === 'utility' && formData.value.electricity ? parseFloat(formData.value.electricity) : null,
      internet: formData.value.payment_type === 'utility' && formData.value.internet ? parseFloat(formData.value.internet) : null,
      payment_date: formData.value.payment_date,
      due_date: formData.value.due_date,
      status: formData.value.status,
      payment_method: formData.value.payment_method || null,
      reference_number: formData.value.reference_number || null,
      notes: formData.value.notes || null
    }
    
    // Add tenant_id if provided
    if (formData.value.tenant_id) {
      payload.tenant_id = parseInt(formData.value.tenant_id)
    }
    
    let response
    if (selectedPayment.value) {
      // Update existing payment
      response = await api.put(`/payments/${selectedPayment.value.id}`, payload)
    } else {
      // Create new payment
      response = await api.post('/payments', payload)
    }
    
    if (response.data.success) {
      closePaymentModal()
      await fetchPayments()
    } else {
      error.value = response.data.message || 'Failed to save payment.'
    }
  } catch (err) {
    console.error('Error saving payment:', err)
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      error.value = err.response?.data?.message || 'Failed to save payment. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}

// Open delete modal
const openDeleteModal = (payment) => {
  selectedPayment.value = payment
  showDeleteModal.value = true
}

// Close delete modal
const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedPayment.value = null
}

// Open details modal
const openDetailsModal = (payment) => {
  selectedPayment.value = payment
  showDetailsModal.value = true
}

// Close details modal
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedPayment.value = null
}

// Delete payment
const deletePayment = async () => {
  if (!selectedPayment.value) return
  
  isSubmitting.value = true
  try {
    const response = await api.delete(`/payments/${selectedPayment.value.id}`)
    if (response.data.success) {
      closeDeleteModal()
      await fetchPayments()
    } else {
      error.value = response.data.message || 'Failed to delete payment.'
    }
  } catch (err) {
    console.error('Error deleting payment:', err)
    error.value = err.response?.data?.message || 'Failed to delete payment. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Approve payment
const approvePayment = async () => {
  if (!selectedPayment.value) return
  
  if (!confirm('Are you sure you want to approve this payment?')) {
    return
  }
  
  isSubmitting.value = true
  try {
    const response = await api.put(`/payments/${selectedPayment.value.id}/approve`)
    if (response.data.success) {
      await fetchPayments()
      // Update selectedPayment to reflect the change
      selectedPayment.value.review_status = 'approved'
      selectedPayment.value.status = 'paid'
      alert('Payment approved successfully!')
    } else {
      error.value = response.data.message || 'Failed to approve payment.'
    }
  } catch (err) {
    console.error('Error approving payment:', err)
    error.value = err.response?.data?.message || 'Failed to approve payment. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Reject payment
const rejectPayment = async () => {
  if (!selectedPayment.value) return
  
  if (!confirm('Are you sure you want to reject this payment?')) {
    return
  }
  
  isSubmitting.value = true
  try {
    const response = await api.put(`/payments/${selectedPayment.value.id}/reject`)
    if (response.data.success) {
      await fetchPayments()
      // Update selectedPayment to reflect the change
      selectedPayment.value.review_status = 'rejected'
      selectedPayment.value.status = 'pending'
      alert('Payment rejected successfully!')
    } else {
      error.value = response.data.message || 'Failed to reject payment.'
    }
  } catch (err) {
    console.error('Error rejecting payment:', err)
    error.value = err.response?.data?.message || 'Failed to reject payment. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Handle dropdown menu
const toggleDropdown = (event, paymentId) => {
  event.stopPropagation()
  
  if (openDropdownId.value === paymentId) {
    openDropdownId.value = null
  } else {
    const rect = event.target.closest('button').getBoundingClientRect()
    dropdownPosition.value = {
      top: rect.bottom + window.scrollY + 4,
      right: window.innerWidth - rect.right + window.scrollX
    }
    openDropdownId.value = paymentId
  }
}

const closeDropdown = () => {
  openDropdownId.value = null
}

// Share payment
const sharePayment = async (payment) => {
  try {
    // Create shareable link or copy to clipboard
    const paymentUrl = `${window.location.origin}/payments/${payment.id}`
    
    if (navigator.share) {
      await navigator.share({
        title: `Payment - ${payment.tenant_name}`,
        text: `Payment details for ${payment.tenant_name}`,
        url: paymentUrl
      })
    } else {
      // Fallback: copy to clipboard
      await navigator.clipboard.writeText(paymentUrl)
      alert('Payment link copied to clipboard!')
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      console.error('Error sharing payment:', err)
      // Fallback: copy to clipboard
      try {
        const paymentUrl = `${window.location.origin}/payments/${payment.id}`
        await navigator.clipboard.writeText(paymentUrl)
        alert('Payment link copied to clipboard!')
      } catch (clipboardErr) {
        alert('Failed to share payment. Please try again.')
      }
    }
  }
  closeDropdown()
}

onMounted(() => {
  fetchPayments()
  // Close dropdown when clicking outside
  document.addEventListener('click', closeDropdown)
})

onActivated(() => {
  fetchPayments()
})

onUnmounted(() => {
  closeDropdown()
  document.removeEventListener('click', closeDropdown)
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Page Title -->
      <div class="page-header">
        <h1 class="page-title">Payments</h1>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value">{{ computedStatistics.total_payments }}</div>
          <div class="stat-label">Total Payments</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(computedStatistics.paid_amount) }}</div>
          <div class="stat-label">Paid Amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(computedStatistics.pending_amount) }}</div>
          <div class="stat-label">Pending Amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(computedStatistics.overdue_amount) }}</div>
          <div class="stat-label">Overdue Amount</div>
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
            placeholder="Search payments by tenant, property, unit, or reference..."
            @input="resetToFirstPage"
          />
        </div>
        <div class="filters-group">
          <div class="filter-container">
            <select v-model="paymentTypeFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Types</option>
              <option value="rent">Rent</option>
              <option value="utility">Utility</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div class="filter-container">
            <select v-model="statusFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Status</option>
              <option value="paid">Paid</option>
              <option value="pending">Not Paid</option>
              <option value="pending_review">Pending</option>
              <option value="overdue">Overdue</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <button class="add-payment-btn" @click="openAddPaymentModal">
            <PlusIcon class="btn-icon" />
            <span>Add Payment</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading payments...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchPayments">Retry</button>
      </div>

      <!-- Payments Table -->
      <div v-else class="payments-table-container">
        <table class="payments-table">
          <thead>
            <tr>
              <th>Tenant</th>
              <th>Property</th>
              <th>Unit</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Payment Date</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Payment Method</th>
              <th>Reference</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedPayments.length === 0">
              <td colspan="10" class="empty-state">
                No payments found
              </td>
            </tr>
            <tr v-for="payment in paginatedPayments" :key="payment.id">
              <td>
                <div class="tenant-info">
                  <div class="tenant-name">{{ payment.tenant_name }}</div>
                  <div class="tenant-email">{{ payment.tenant_email || 'N/A' }}</div>
                </div>
              </td>
              <td>
                <div class="property-info">
                  <div class="property-name">{{ payment.property_name }}</div>
                </div>
              </td>
              <td>
                <div class="unit-info">
                  <div class="unit-number">Unit {{ payment.unit_number }}</div>
                </div>
              </td>
              <td>
                <span :class="['type-badge', payment.payment_type === 'rent' ? 'type-rent' : 'type-utility']">
                  {{ payment.payment_type === 'rent' ? 'Rent' : 'Utility' }}
                </span>
              </td>
              <td class="amount-cell">{{ formatCurrency(payment.amount) }}</td>
              <td>{{ formatDate(payment.payment_date) }}</td>
              <td>{{ formatDate(payment.due_date) }}</td>
              <td>
                <span :class="['status-badge', getStatusClass(payment)]">
                  {{ getStatusText(payment) }}
                </span>
              </td>
              <td>{{ payment.payment_method || 'N/A' }}</td>
              <td>{{ payment.reference_number || 'N/A' }}</td>
              <td>
                <div class="actions-cell">
                  <button 
                    class="action-btn dots-btn" 
                    @click="toggleDropdown($event, payment.id)"
                    title="More options"
                  >
                    <EllipsisVerticalIcon class="action-icon" />
                  </button>
                  
                  <Teleport to="body">
                    <div 
                      v-if="openDropdownId === payment.id"
                      class="dropdown-menu"
                      :style="{ top: dropdownPosition.top + 'px', right: dropdownPosition.right + 'px' }"
                      @click.stop
                    >
                      <button class="dropdown-item" @click="openDetailsModal(payment); closeDropdown()">
                        <EyeIcon class="dropdown-icon" />
                        <span>View Details</span>
                      </button>
                      <button 
                        class="dropdown-item" 
                        @click="handleDownloadReceipt(payment); closeDropdown()"
                        :disabled="payment.review_status !== 'approved' || payment.status !== 'paid'"
                      >
                        <ArrowDownTrayIcon class="dropdown-icon" />
                        <span>Download Receipt</span>
                      </button>
                      <button class="dropdown-item" @click="sharePayment(payment)">
                        <ShareIcon class="dropdown-icon" />
                        <span>Share</span>
                      </button>
                      <button class="dropdown-item delete-item" @click="openDeleteModal(payment); closeDropdown()">
                        <TrashIcon class="dropdown-icon" />
                        <span>Delete</span>
                      </button>
                    </div>
                  </Teleport>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="!isLoading && !error && filteredPayments.length > 0" class="pagination-container">
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

      <!-- Add/Edit Payment Modal -->
      <div v-if="showPaymentModal" class="modal-overlay" @click.self="closePaymentModal">
        <div class="modal-content payment-modal">
          <div class="modal-header">
            <h2>{{ selectedPayment ? 'Edit Payment' : 'Add Payment' }}</h2>
            <button class="close-btn" @click="closePaymentModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>
          
          <form @submit.prevent="submitPayment" class="payment-form">
            <div class="form-group">
              <label>Property <span class="required">*</span></label>
              <select 
                v-model="formData.property_id" 
                @change="handlePropertyChange"
                :class="{ 'error': formErrors.property_id }"
                required
              >
                <option value="">Select Property</option>
                <option v-for="property in properties" :key="property.id" :value="property.id">
                  {{ property.name }}
                </option>
              </select>
              <span v-if="formErrors.property_id" class="error-message">{{ formErrors.property_id[0] }}</span>
            </div>

            <div class="form-group">
              <label>Unit <span class="required">*</span></label>
              <select 
                v-model="formData.unit_id" 
                @change="handleUnitChange"
                :class="{ 'error': formErrors.unit_id }"
                required
                :disabled="!formData.property_id"
              >
                <option value="">Select Unit</option>
                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                  Unit {{ unit.unit_number }}
                </option>
              </select>
              <span v-if="formErrors.unit_id" class="error-message">{{ formErrors.unit_id[0] }}</span>
            </div>

            <div class="form-group">
              <label>Tenant <span class="required">*</span></label>
              <input 
                type="text" 
                :value="getTenantName()"
                :class="{ 'error': formErrors.tenant_id }"
                readonly
                disabled
                style="background-color: #f3f4f6; cursor: not-allowed;"
              />
              <span v-if="formErrors.tenant_id" class="error-message">{{ formErrors.tenant_id[0] }}</span>
            </div>

            <div class="form-group">
              <label>Payment Type <span class="required">*</span></label>
              <select 
                v-model="formData.payment_type" 
                :class="{ 'error': formErrors.payment_type }"
                required
              >
                <option value="rent">Rent</option>
                <option value="utility">Utility</option>
              </select>
              <span v-if="formErrors.payment_type" class="error-message">{{ formErrors.payment_type[0] }}</span>
            </div>

            <!-- Utility Fields (shown only when payment_type is utility) -->
            <div v-if="formData.payment_type === 'utility'" class="utility-fields">
              <h3 class="utility-section-title">Utility Breakdown</h3>
              <div class="form-row three-columns">
                <div class="form-group">
                  <label>Water</label>
                  <input 
                    type="number" 
                    v-model="formData.water" 
                    step="0.01"
                    min="0"
                    :class="{ 'error': formErrors.water }"
                  />
                  <span v-if="formErrors.water" class="error-message">{{ formErrors.water[0] }}</span>
                </div>

                <div class="form-group">
                  <label>Electricity</label>
                  <input 
                    type="number" 
                    v-model="formData.electricity" 
                    step="0.01"
                    min="0"
                    :class="{ 'error': formErrors.electricity }"
                  />
                  <span v-if="formErrors.electricity" class="error-message">{{ formErrors.electricity[0] }}</span>
                </div>

                <div class="form-group">
                  <label>Internet</label>
                  <input 
                    type="number" 
                    v-model="formData.internet" 
                    step="0.01"
                    min="0"
                    :class="{ 'error': formErrors.internet }"
                  />
                  <span v-if="formErrors.internet" class="error-message">{{ formErrors.internet[0] }}</span>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input 
                  type="number" 
                  v-model="formData.amount" 
                  step="0.01"
                  min="0"
                  :class="{ 'error': formErrors.amount }"
                  :readonly="formData.payment_type === 'rent' && formData.unit_id"
                  :disabled="formData.payment_type === 'rent' && formData.unit_id"
                  :style="formData.payment_type === 'rent' && formData.unit_id ? 'background-color: #f3f4f6; cursor: not-allowed;' : ''"
                  required
                />
                <span v-if="formData.payment_type === 'utility'" class="helper-text">Auto-calculated from utility breakdown</span>
                <span v-if="formErrors.amount" class="error-message">{{ formErrors.amount[0] }}</span>
              </div>

              <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select v-model="formData.status" required>
                  <option value="pending">Pending (Not Paid)</option>
                  <option value="paid">Paid</option>
                  <option value="overdue">Overdue</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Payment Date</label>
                <input 
                  type="date" 
                  v-model="formData.payment_date" 
                  :class="{ 'error': formErrors.payment_date }"
                />
                <span v-if="formErrors.payment_date" class="error-message">{{ formErrors.payment_date[0] }}</span>
              </div>

              <div class="form-group">
                <label>Due Date <span class="required">*</span></label>
                <input 
                  type="date" 
                  v-model="formData.due_date" 
                  :min="formData.payment_date || ''"
                  :class="{ 'error': formErrors.due_date }"
                  required
                />
                <span v-if="formErrors.due_date" class="error-message">{{ formErrors.due_date[0] }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Payment Method</label>
                <select v-model="formData.payment_method">
                  <option value="">Select Method</option>
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="check">Check</option>
                  <option value="online">Online Payment</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label>Reference Number</label>
                <input 
                  type="text" 
                  v-model="formData.reference_number" 
                  placeholder="e.g., Check #12345"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea 
                v-model="formData.notes" 
                rows="3"
                placeholder="Additional notes about this payment..."
              ></textarea>
            </div>

            <div class="form-actions">
              <button type="button" class="cancel-btn" @click="closePaymentModal">Cancel</button>
              <button type="submit" class="submit-btn" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : (selectedPayment ? 'Update Payment' : 'Add Payment') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Details Modal -->
      <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
        <div class="modal-content details-modal">
          <div class="modal-header">
            <h2>Payment Details</h2>
            <button class="close-btn" @click="closeDetailsModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>
          
          <div v-if="selectedPayment" class="details-content">
            <div class="details-section">
              <h3 class="details-section-title">Tenant Information</h3>
              <div class="details-grid">
                <div class="detail-item">
                  <span class="detail-label">Name:</span>
                  <span class="detail-value">{{ selectedPayment.tenant_name }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Email:</span>
                  <span class="detail-value">{{ selectedPayment.tenant_email || 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div class="details-section">
              <h3 class="details-section-title">Property Information</h3>
              <div class="details-grid">
                <div class="detail-item">
                  <span class="detail-label">Property:</span>
                  <span class="detail-value">{{ selectedPayment.property_name }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Unit:</span>
                  <span class="detail-value">Unit {{ selectedPayment.unit_number }}</span>
                </div>
              </div>
            </div>

            <div class="details-section">
              <h3 class="details-section-title">Payment Information</h3>
              <div class="details-grid">
                <div class="detail-item">
                  <span class="detail-label">Payment Type:</span>
                  <span class="detail-value">
                    {{ selectedPayment.payment_type === 'rent' ? 'Rent' : 'Utility' }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Amount:</span>
                  <span class="detail-value amount-highlight">{{ formatCurrency(selectedPayment.amount) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Status:</span>
                  <span class="detail-value">
                    {{ getStatusText(selectedPayment) }}
                  </span>
                </div>
                <div v-if="selectedPayment.review_status" class="detail-item">
                  <span class="detail-label">Review Status:</span>
                  <span class="detail-value">
                    {{ selectedPayment.review_status === 'pending_review' ? 'Pending Review' : 
                        selectedPayment.review_status === 'approved' ? 'Approved' : 
                        selectedPayment.review_status === 'rejected' ? 'Rejected' : 'N/A' }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Payment Date:</span>
                  <span class="detail-value">{{ formatDate(selectedPayment.payment_date) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Due Date:</span>
                  <span class="detail-value">{{ formatDate(selectedPayment.due_date) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Payment Method:</span>
                  <span class="detail-value">{{ selectedPayment.payment_method || 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Reference Number:</span>
                  <span class="detail-value">{{ selectedPayment.reference_number || 'N/A' }}</span>
                </div>
                <div v-if="selectedPayment.payment_proof" class="detail-item full-width">
                  <span class="detail-label">Payment Proof:</span>
                  <div class="payment-proof-container">
                    <img :src="`/storage/${selectedPayment.payment_proof}`" alt="Payment proof" class="payment-proof-image" />
                  </div>
                </div>
              </div>
            </div>

            <div v-if="selectedPayment.payment_type === 'utility'" class="details-section">
              <h3 class="details-section-title">Utility Breakdown</h3>
              <div class="details-grid">
                <div class="detail-item">
                  <span class="detail-label">Water:</span>
                  <span class="detail-value">{{ selectedPayment.water ? formatCurrency(selectedPayment.water) : 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Electricity:</span>
                  <span class="detail-value">{{ selectedPayment.electricity ? formatCurrency(selectedPayment.electricity) : 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Internet:</span>
                  <span class="detail-value">{{ selectedPayment.internet ? formatCurrency(selectedPayment.internet) : 'N/A' }}</span>
                </div>
              </div>
            </div>

            <div v-if="selectedPayment.notes" class="details-section">
              <h3 class="details-section-title">Notes</h3>
              <div class="detail-item">
                <span class="detail-value notes-text">{{ selectedPayment.notes }}</span>
              </div>
            </div>

            <!-- Approve/Reject Actions -->
            <div v-if="selectedPayment.review_status === 'pending_review'" class="details-section">
              <h3 class="details-section-title">Review Payment</h3>
              <div class="review-actions">
                <button 
                  class="approve-btn" 
                  @click="approvePayment"
                  :disabled="isSubmitting"
                >
                  {{ isSubmitting ? 'Processing...' : 'Approve Payment' }}
                </button>
                <button 
                  class="reject-btn" 
                  @click="rejectPayment"
                  :disabled="isSubmitting"
                >
                  {{ isSubmitting ? 'Processing...' : 'Reject Payment' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal-content delete-modal">
          <div class="modal-header">
            <h2>Delete Payment</h2>
            <button class="close-btn" @click="closeDeleteModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>
          
          <div class="delete-content">
            <p>Are you sure you want to delete this payment?</p>
            <div v-if="selectedPayment" class="payment-details">
              <p><strong>Tenant:</strong> {{ selectedPayment.tenant_name }}</p>
              <p><strong>Amount:</strong> {{ formatCurrency(selectedPayment.amount) }}</p>
              <p><strong>Date:</strong> {{ formatDate(selectedPayment.payment_date) }}</p>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="closeDeleteModal">Cancel</button>
            <button type="button" class="delete-btn" @click="deletePayment" :disabled="isSubmitting">
              {{ isSubmitting ? 'Deleting...' : 'Delete' }}
            </button>
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

.add-payment-btn {
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

.add-payment-btn:hover {
  background: #1200cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
}

.btn-icon {
  width: 20px;
  height: 20px;
}

/* Statistics Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
  color: #111827;
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

.filter-container {
  position: relative;
}

.filter-dropdown {
  padding: 12px 40px 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  background: white;
  cursor: pointer;
  appearance: none;
  min-width: 150px;
}

.filter-dropdown:focus {
  outline: none;
  border-color: #1500FF;
}

.chevron-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #999;
  pointer-events: none;
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
}

/* Payments Table */
.payments-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.payments-table {
  width: 100%;
  border-collapse: collapse;
}

.payments-table thead {
  background: #f8f9fa;
}

.payments-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
  border-bottom: 2px solid #e5e7eb;
}

.payments-table td {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.payments-table tbody tr:hover {
  background: #f9fafb;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

.tenant-info {
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
  margin-top: 4px;
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

.unit-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.unit-number {
  font-size: 12px;
  color: #666;
}

.amount-cell {
  font-weight: 600;
  color: #111827;
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
  background: #f3f4f6;
  color: #6b7280;
}

.status-overdue {
  background: #fee2e2;
  color: #991b1b;
}

.status-review {
  background: #fed7aa;
  color: #c2410c;
}

.actions-cell {
  display: flex;
  gap: 8px;
  position: relative;
}

.action-btn {
  padding: 8px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
}

.action-btn:hover {
  background: #f3f4f6;
}

.dots-btn {
  color: #666;
}

.action-icon {
  width: 18px;
  height: 18px;
}

.dropdown-menu {
  position: fixed;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 160px;
  z-index: 1000;
  padding: 4px;
  border: 1px solid #e5e7eb;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  border-radius: 6px;
  transition: background-color 0.2s;
  font-family: 'Montserrat', sans-serif;
}

.dropdown-item:hover {
  background: #f3f4f6;
}

.dropdown-item.delete-item {
  color: #dc2626;
}

.dropdown-item.delete-item:hover {
  background: #fee2e2;
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
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
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
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

/* Modal */
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  font-family: 'Montserrat', sans-serif;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
  font-family: 'Montserrat', sans-serif;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
}

.close-btn {
  padding: 8px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.close-btn:hover {
  background: #f3f4f6;
}

.close-icon {
  width: 24px;
  height: 24px;
  color: #666;
}

/* Payment Form */
.payment-form {
  padding: 24px;
  font-family: 'Montserrat', sans-serif;
}

.form-group {
  margin-bottom: 20px;
  font-family: 'Montserrat', sans-serif;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
}

.required {
  color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1500FF;
}

.form-group input.error,
.form-group select.error {
  border-color: #dc2626;
}

.error-message {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #dc2626;
  font-family: 'Montserrat', sans-serif;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-row.three-columns {
  grid-template-columns: repeat(3, 1fr);
}

.utility-fields {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.utility-section-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 16px 0;
  font-family: 'Montserrat', sans-serif;
}

.type-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.type-rent {
  /* No background color */
  color: #111827;
}

.type-utility {
  /* No background color */
  color: #111827;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.cancel-btn,
.submit-btn,
.delete-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn {
  background: #f3f4f6;
  color: #1a1a1a;
}

.cancel-btn:hover {
  background: #e5e7eb;
}

.submit-btn {
  background: #1500FF;
  color: white;
}

.submit-btn:hover:not(:disabled) {
  background: #1200cc;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-modal {
  max-width: 500px;
  font-family: 'Montserrat', sans-serif;
}

.delete-content {
  padding: 24px;
  font-family: 'Montserrat', sans-serif;
}

.delete-content p {
  margin-bottom: 16px;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
}

.details-modal {
  max-width: 700px;
  font-family: 'Montserrat', sans-serif;
}

.details-content {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
  font-family: 'Montserrat', sans-serif;
}

.details-section {
  margin-bottom: 24px;
}

.details-section:last-child {
  margin-bottom: 0;
}

.details-section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
  font-family: 'Montserrat', sans-serif;
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-family: 'Montserrat', sans-serif;
}

.detail-value {
  font-size: 14px;
  color: #1a1a1a;
  font-weight: 500;
  font-family: 'Montserrat', sans-serif;
}

.detail-value.amount-highlight {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.detail-value.notes-text {
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.payment-proof-container {
  margin-top: 8px;
}

.payment-proof-image {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
  .details-grid {
    grid-template-columns: 1fr;
  }
}

.payment-details {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  margin-top: 16px;
  font-family: 'Montserrat', sans-serif;
}

.payment-details p {
  margin-bottom: 8px;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
}

.delete-btn {
  background: #dc2626;
  color: white;
}

.delete-btn:hover:not(:disabled) {
  background: #b91c1c;
}

/* Review Actions */
.review-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  font-family: 'Montserrat', sans-serif;
}

.approve-btn,
.reject-btn {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
}

.approve-btn {
  background: #10b981;
  color: white;
}

.approve-btn:hover:not(:disabled) {
  background: #059669;
}

.reject-btn {
  background: #ef4444;
  color: white;
}

.reject-btn:hover:not(:disabled) {
  background: #dc2626;
}

.approve-btn:disabled,
.reject-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
