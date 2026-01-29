<script setup>
import { ref, onMounted } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import api from '../services/api.js'

const currentBalance = ref(0.00)
const nextDueDate = ref(null)
const paymentHistory = ref([])
const isLoading = ref(false)
const error = ref(null)

// Fetch payments data
const fetchPayments = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/tenant/payments')
    
    if (response.data.success) {
      currentBalance.value = response.data.current_balance || 0.00
      nextDueDate.value = response.data.next_due_date || null
      paymentHistory.value = response.data.payment_history || []
    } else {
      error.value = response.data.message || 'Failed to load payment information. Please try again.'
    }
  } catch (err) {
    console.error('Error fetching payments:', err)
    error.value = err.response?.data?.message || 'Failed to load payment information. Please try again.'
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
  return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`
}

const handleDownloadReceipt = async (receiptUrl, paymentId) => {
  // Check if payment is approved/paid
  const payment = paymentHistory.value.find(p => p.id === paymentId)
  
  if (payment && payment.status === 'paid') {
    try {
      // Generate PDF receipt
      const response = await api.get(`/tenant/receipt/${paymentId}`, {
        responseType: 'blob'
      })
      
      // Create blob URL and download
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `Receipt-${paymentId}-${new Date().toISOString().split('T')[0]}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      console.error('Error generating receipt:', err)
      if (err.response?.status === 400) {
        alert(err.response.data?.message || 'Receipt can only be generated for approved payments.')
      } else {
        alert('Failed to generate receipt. Please try again.')
      }
    }
  } else if (receiptUrl && receiptUrl !== '#') {
    // Fallback: Open payment proof if available
    window.open(receiptUrl, '_blank')
  } else {
    alert('Receipt not available for this payment. Payment must be approved first.')
  }
}

onMounted(() => {
  fetchPayments()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">Payments</h1>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading payment information...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchPayments">Retry</button>
      </div>

      <!-- Payment Content -->
      <div v-else class="payment-content">
        <!-- Current Balance Card -->
        <div class="balance-card">
          <h3 class="card-title">Current Balance</h3>
          <p class="balance-amount">{{ formatCurrency(currentBalance) }}</p>
          <div v-if="currentBalance === 0" class="no-payment-text">No Payment Due</div>
          <div v-else-if="nextDueDate" class="due-date-text">
            Due Date: {{ formatDate(nextDueDate) }}
          </div>
          <div v-else class="due-date-text">Payment Due</div>
        </div>

        <!-- Payment History Section -->
        <div class="payment-history-section">
          <h2 class="section-title">Payment History</h2>
          
          <div class="table-container">
            <table class="payment-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Receipt</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in paymentHistory" :key="payment.id">
                  <td>{{ formatDate(payment.date) }}</td>
                  <td>{{ payment.description }}</td>
                  <td class="amount-cell">{{ formatCurrency(payment.amount) }}</td>
                  <td>
                    <span :class="['status-badge', payment.status === 'paid' ? 'status-paid' : 'status-pending']">
                      {{ payment.status === 'paid' ? 'Paid' : (payment.status === 'pending' ? 'Pending' : 'Unpaid') }}
                    </span>
                  </td>
                  <td>
                    <button 
                      v-if="payment.status === 'paid'"
                      class="download-link"
                      @click="handleDownloadReceipt(payment.payment_proof, payment.id)"
                    >
                      Download Receipt
                    </button>
                    <span v-else class="no-receipt">N/A</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="paymentHistory.length === 0" class="empty-state">
            <p>No payment history available.</p>
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
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 32px;
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

/* Balance Card */
.balance-card {
  background: #1500FF;
  border-radius: 12px;
  padding: 24px;
  color: white;
  margin-bottom: 40px;
  max-width: 500px;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
  opacity: 0.9;
}

.balance-amount {
  font-size: 48px;
  font-weight: 700;
  margin: 0 0 20px 0;
  color: white;
}

.no-payment-text {
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  text-align: center;
}

.due-date-text {
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  text-align: center;
}

/* Payment History Section */
.payment-history-section {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 24px;
}

.table-container {
  overflow-x: auto;
}

.payment-table {
  width: 100%;
  border-collapse: collapse;
}

.payment-table thead {
  background: #f9fafb;
}

.payment-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.payment-table td {
  padding: 16px;
  font-size: 14px;
  color: #111827;
  border-bottom: 1px solid #e5e7eb;
}

.payment-table tbody tr:hover {
  background: #f9fafb;
}

.amount-cell {
  font-weight: 600;
  color: #111827;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.status-paid {
  background: #dcfce7;
  color: #15803d;
}

.status-pending {
  background: #fed7aa;
  color: #c2410c;
}

.no-receipt {
  color: #6b7280;
  font-size: 14px;
}

.download-link {
  background: none;
  border: none;
  color: #1500FF;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  font-family: 'Montserrat', sans-serif;
  padding: 0;
  transition: color 0.2s;
}

.download-link:hover {
  color: #1200e6;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 20px;
  color: #6b7280;
}

/* Responsive Design */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .table-container {
    overflow-x: scroll;
  }

  .payment-table {
    min-width: 600px;
  }
}
</style>
