<script setup>
import { ref, onMounted, computed } from 'vue'
import { Teleport } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  BoltIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/solid'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const utilities = ref([])
const isLoading = ref(false)
const error = ref(null)
const filterStatus = ref('All')
const showPaymentModal = ref(false)
const selectedUtility = ref(null)
const paymentProof = ref(null)
const paymentProofPreview = ref(null)
const isSubmitting = ref(false)
const paymentFormData = ref({
  payment_method: 'online',
  reference_number: '',
  notes: ''
})

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
    const response = await api.get('/tenant/utilities')
    
    if (response.data.success) {
      utilities.value = response.data.utilities || []
    } else {
      error.value = response.data.message || 'Failed to load utilities. Please try again.'
    }
  } catch (err) {
    console.error('Error fetching utilities:', err)
    error.value = err.response?.data?.message || 'Failed to load utilities. Please try again.'
  } finally {
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

const handlePayNow = (utility) => {
  selectedUtility.value = utility
  paymentFormData.value = {
    payment_method: 'online',
    reference_number: '',
    notes: ''
  }
  paymentProof.value = null
  paymentProofPreview.value = null
  showPaymentModal.value = true
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file')
      return
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('Image size should be less than 5MB')
      return
    }
    
    paymentProof.value = file
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      paymentProofPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removePhoto = () => {
  paymentProof.value = null
  paymentProofPreview.value = null
  // Reset file input
  const fileInput = document.getElementById('payment-proof-input')
  if (fileInput) {
    fileInput.value = ''
  }
}

const closePaymentModal = () => {
  showPaymentModal.value = false
  selectedUtility.value = null
  paymentProof.value = null
  paymentProofPreview.value = null
  paymentFormData.value = {
    payment_method: 'online',
    reference_number: '',
    notes: ''
  }
}

const submitPayment = async () => {
  if (!selectedUtility.value) return
  
  // Validate required fields
  if (!paymentFormData.value.payment_method) {
    alert('Please select a payment method')
    return
  }
  
  if (paymentFormData.value.payment_method === 'online' && !paymentProof.value) {
    alert('Please upload a payment proof photo for online transactions')
    return
  }
  
  isSubmitting.value = true
  
  try {
    const formData = new FormData()
    formData.append('payment_method', paymentFormData.value.payment_method)
    formData.append('reference_number', paymentFormData.value.reference_number || '')
    formData.append('notes', paymentFormData.value.notes || '')
    
    if (paymentProof.value) {
      formData.append('payment_proof', paymentProof.value)
    }
    
    const response = await api.post(`/tenant/utilities/${selectedUtility.value.id}/pay`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    if (response.data.success) {
      alert('Payment submitted successfully! It will be reviewed by your landlord.')
      closePaymentModal()
      await fetchUtilities()
    } else {
      alert(response.data.message || 'Failed to submit payment. Please try again.')
    }
  } catch (err) {
    console.error('Error submitting payment:', err)
    alert(err.response?.data?.message || 'Failed to submit payment. Please try again.')
  } finally {
    isSubmitting.value = false
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
              @click="handlePayNow(utility)"
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

    <!-- Payment Modal -->
    <Teleport to="body">
      <div v-if="showPaymentModal" class="modal-overlay" @click.self="closePaymentModal">
        <div class="modal-content payment-modal">
          <div class="modal-header">
            <h2>Payment Details</h2>
            <button class="close-btn" @click="closePaymentModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>
          
          <div v-if="selectedUtility" class="modal-body">
            <!-- Payment Summary -->
            <div class="payment-summary">
              <h3 class="summary-title">Payment Summary</h3>
              <div class="summary-item">
                <span class="summary-label">Utility:</span>
                <span class="summary-value">{{ selectedUtility.name }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Amount:</span>
                <span class="summary-value amount-highlight">{{ formatCurrency(selectedUtility.amount) }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Due Date:</span>
                <span class="summary-value">{{ formatDate(selectedUtility.dueDate) }}</span>
              </div>
            </div>

            <!-- Payment Form -->
            <form @submit.prevent="submitPayment" class="payment-form">
              <div class="form-group">
                <label>Payment Method <span class="required">*</span></label>
                <select v-model="paymentFormData.payment_method" required>
                  <option value="online">Online Payment</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="cash">Cash</option>
                  <option value="check">Check</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label>Reference Number</label>
                <input 
                  type="text" 
                  v-model="paymentFormData.reference_number"
                  placeholder="e.g., Transaction ID, Check #"
                />
              </div>

              <div class="form-group">
                <label>Payment Proof Photo <span v-if="paymentFormData.payment_method === 'online'" class="required">*</span></label>
                <div class="file-upload-container">
                  <input 
                    id="payment-proof-input"
                    type="file" 
                    accept="image/*"
                    @change="handleFileChange"
                    :required="paymentFormData.payment_method === 'online'"
                    class="file-input"
                  />
                  <label for="payment-proof-input" class="file-label">
                    <span v-if="!paymentProofPreview">Choose Photo</span>
                    <span v-else>Change Photo</span>
                  </label>
                </div>
                <p class="helper-text">Upload a screenshot or photo of your payment transaction</p>
                
                <!-- Photo Preview -->
                <div v-if="paymentProofPreview" class="photo-preview">
                  <img :src="paymentProofPreview" alt="Payment proof preview" />
                  <button type="button" class="remove-photo-btn" @click="removePhoto">Remove</button>
                </div>
              </div>

              <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea 
                  v-model="paymentFormData.notes"
                  rows="3"
                  placeholder="Additional notes about this payment..."
                ></textarea>
              </div>

              <div class="form-actions">
                <button type="button" class="cancel-btn" @click="closePaymentModal">Cancel</button>
                <button type="submit" class="submit-btn" :disabled="isSubmitting">
                  {{ isSubmitting ? 'Submitting...' : 'Submit Payment' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
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

/* Payment Modal Styles */
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

.payment-modal {
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

.modal-body {
  padding: 24px;
}

.payment-summary {
  background: #f9fafb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
}

.summary-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
}

.summary-item:last-child {
  margin-bottom: 0;
}

.summary-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.summary-value {
  font-size: 14px;
  color: #1a1a1a;
  font-weight: 600;
}

.summary-value.amount-highlight {
  font-size: 20px;
  font-weight: 700;
  color: #1500FF;
}

.payment-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
}

.required {
  color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
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

.helper-text {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

.file-upload-container {
  position: relative;
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.file-label {
  display: inline-block;
  padding: 10px 20px;
  background: #f3f4f6;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  transition: all 0.2s ease;
}

.file-label:hover {
  background: #e5e7eb;
  border-color: #1500FF;
  color: #1500FF;
}

.photo-preview {
  margin-top: 12px;
  position: relative;
  display: inline-block;
}

.photo-preview img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.remove-photo-btn {
  margin-top: 8px;
  padding: 6px 12px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.remove-photo-btn:hover {
  background: #fecaca;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 8px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.cancel-btn,
.submit-btn {
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
</style>
