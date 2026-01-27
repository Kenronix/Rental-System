<script setup>
import { ref, computed, watch, onMounted, onActivated } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  MagnifyingGlassIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import { PlusIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const payments = ref([])
const allPayments = ref([])
const tenants = ref([])
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
const selectedPayment = ref(null)
const isSubmitting = ref(false)
const formErrors = ref({})

const searchQuery = ref('')
const statusFilter = ref('All')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Form data
const formData = ref({
  tenant_id: '',
  unit_id: '',
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
    filtered = filtered.filter((payment) => payment.status === statusFilter.value)
  }
  
  return filtered
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

// Get status class
const getStatusClass = (status) => {
  switch (status) {
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

// Open add payment modal
const openAddPaymentModal = () => {
  selectedPayment.value = null
  formData.value = {
    tenant_id: '',
    unit_id: '',
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
  formErrors.value = {}
  showPaymentModal.value = true
}

// Open edit payment modal
const openEditPaymentModal = (payment) => {
  selectedPayment.value = payment
  formData.value = {
    tenant_id: payment.tenant_id.toString(),
    unit_id: payment.unit_id.toString(),
    payment_type: payment.payment_type || 'rent',
    amount: payment.amount.toString(),
    water: payment.water ? payment.water.toString() : '',
    electricity: payment.electricity ? payment.electricity.toString() : '',
    internet: payment.internet ? payment.internet.toString() : '',
    payment_date: payment.payment_date || '',
    due_date: payment.due_date || '',
    status: payment.status,
    payment_method: payment.payment_method || '',
    reference_number: payment.reference_number || '',
    notes: payment.notes || ''
  }
  formErrors.value = {}
  showPaymentModal.value = true
}

// Close payment modal
const closePaymentModal = () => {
  showPaymentModal.value = false
  selectedPayment.value = null
  formData.value = {
    tenant_id: '',
    unit_id: '',
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
  formErrors.value = {}
}

// Handle tenant selection
const handleTenantChange = () => {
  const tenant = tenants.value.find(t => t.id === parseInt(formData.value.tenant_id))
  if (tenant) {
    formData.value.unit_id = tenant.unit_id.toString()
  }
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
  }
})

// Submit payment
const submitPayment = async () => {
  formErrors.value = {}
  isSubmitting.value = true
  
  try {
    const payload = {
      tenant_id: parseInt(formData.value.tenant_id),
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

onMounted(() => {
  fetchPayments()
})

onActivated(() => {
  fetchPayments()
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
          <div class="stat-value">{{ statistics.total_payments }}</div>
          <div class="stat-label">Total Payments</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.total_amount) }}</div>
          <div class="stat-label">Total Amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.paid_amount) }}</div>
          <div class="stat-label">Paid Amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.pending_amount) }}</div>
          <div class="stat-label">Pending Amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(statistics.overdue_amount) }}</div>
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
            <select v-model="statusFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Status</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
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
                  <div class="tenant-email">{{ payment.tenant_email }}</div>
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
                <span :class="['status-badge', getStatusClass(payment.status)]">
                  {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                </span>
              </td>
              <td>{{ payment.payment_method || 'N/A' }}</td>
              <td>{{ payment.reference_number || 'N/A' }}</td>
              <td>
                <div class="actions-cell">
                  <button class="action-btn edit-btn" @click="openEditPaymentModal(payment)" title="Edit">
                    <PencilIcon class="action-icon" />
                  </button>
                  <button class="action-btn delete-btn" @click="openDeleteModal(payment)" title="Delete">
                    <TrashIcon class="action-icon" />
                  </button>
                </div>
              </td>
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
              <label>Tenant <span class="required">*</span></label>
              <select 
                v-model="formData.tenant_id" 
                @change="handleTenantChange"
                :class="{ 'error': formErrors.tenant_id }"
                required
              >
                <option value="">Select Tenant</option>
                <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                  {{ tenant.name }} - {{ tenant.property_name }} Unit {{ tenant.unit_number }}
                </option>
              </select>
              <span v-if="formErrors.tenant_id" class="error-message">{{ formErrors.tenant_id[0] }}</span>
            </div>

            <div class="form-group">
              <label>Unit <span class="required">*</span></label>
              <select 
                v-model="formData.unit_id" 
                :class="{ 'error': formErrors.unit_id }"
                required
                :disabled="!formData.tenant_id"
              >
                <option value="">Select Unit</option>
                <option v-for="unit in getUnitsForTenant" :key="unit.id" :value="unit.id">
                  {{ unit.property_name }} - Unit {{ unit.unit_number }}
                </option>
              </select>
              <span v-if="formErrors.unit_id" class="error-message">{{ formErrors.unit_id[0] }}</span>
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
              <div v-if="formData.payment_type === 'rent'" class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input 
                  type="number" 
                  v-model="formData.amount" 
                  step="0.01"
                  min="0"
                  :class="{ 'error': formErrors.amount }"
                  required
                />
                <span v-if="formErrors.amount" class="error-message">{{ formErrors.amount[0] }}</span>
              </div>

              <div v-if="formData.payment_type === 'utility'" class="form-group">
                <label>Total Amount <span class="required">*</span></label>
                <input 
                  type="number" 
                  v-model="formData.amount" 
                  step="0.01"
                  min="0"
                  :class="{ 'error': formErrors.amount }"
                  readonly
                  style="background-color: #f3f4f6; cursor: not-allowed;"
                />
                <span class="helper-text">Auto-calculated from utility breakdown</span>
                <span v-if="formErrors.amount" class="error-message">{{ formErrors.amount[0] }}</span>
              </div>

              <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select v-model="formData.status" required>
                  <option value="paid">Paid</option>
                  <option value="pending">Pending</option>
                  <option value="overdue">Overdue</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Payment Date <span class="required">*</span></label>
                <input 
                  type="date" 
                  v-model="formData.payment_date" 
                  :class="{ 'error': formErrors.payment_date }"
                  required
                />
                <span v-if="formErrors.payment_date" class="error-message">{{ formErrors.payment_date[0] }}</span>
              </div>

              <div class="form-group">
                <label>Due Date <span class="required">*</span></label>
                <input 
                  type="date" 
                  v-model="formData.due_date" 
                  :min="formData.payment_date"
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

.actions-cell {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-icon {
  width: 18px;
  height: 18px;
}

.edit-btn {
  background: #f3f4f6;
  color: #1a1a1a;
}

.edit-btn:hover {
  background: #e5e7eb;
}

.delete-btn {
  background: #fee2e2;
  color: #dc2626;
}

.delete-btn:hover {
  background: #fecaca;
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
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
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
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
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
  background: #dbeafe;
  color: #1e40af;
}

.type-utility {
  background: #fef3c7;
  color: #92400e;
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
}

.delete-content {
  padding: 24px;
}

.delete-content p {
  margin-bottom: 16px;
  color: #1a1a1a;
}

.payment-details {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  margin-top: 16px;
}

.payment-details p {
  margin-bottom: 8px;
  font-size: 14px;
}

.delete-btn {
  background: #dc2626;
  color: white;
}

.delete-btn:hover:not(:disabled) {
  background: #b91c1c;
}
</style>
