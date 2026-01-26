<script setup>
import { ref, onMounted } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const currentBalance = ref(0.00)
const paymentMethod = ref(null)
const paymentHistory = ref([])
const isLoading = ref(false)
const error = ref(null)

// Fetch payments data
const fetchPayments = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/payments')
    // For now, using mock data based on the image
    setTimeout(() => {
      currentBalance.value = 0.00
      
      paymentMethod.value = {
        id: 1,
        type: 'VISA',
        last4: '4242',
        isDefault: true
      }
      
      paymentHistory.value = [
        {
          id: 1,
          date: '2023-10-01',
          description: 'Rent Payment - October',
          amount: 1200.00,
          status: 'paid',
          receiptUrl: '#'
        },
        {
          id: 2,
          date: '2023-10-02',
          description: 'Rent Payment - October',
          amount: 1200.00,
          status: 'paid',
          receiptUrl: '#'
        },
        {
          id: 3,
          date: '2023-10-03',
          description: 'Rent Payment - October',
          amount: 1200.00,
          status: 'paid',
          receiptUrl: '#'
        }
      ]
      
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching payments:', err)
    error.value = 'Failed to load payment information. Please try again.'
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

const handleManageMethods = () => {
  // TODO: Navigate to payment methods management page
  console.log('Manage payment methods')
}

const handleDownloadReceipt = (receiptUrl, paymentId) => {
  // TODO: Implement receipt download
  console.log('Download receipt for payment:', paymentId)
  // For now, just show an alert
  alert('Receipt download functionality will be implemented soon.')
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
        <!-- Top Cards Section -->
        <div class="cards-section">
          <!-- Current Balance Card -->
          <div class="balance-card">
            <h3 class="card-title">Current Balance</h3>
            <p class="balance-amount">{{ formatCurrency(currentBalance) }}</p>
            <button class="no-payment-button">No Payment Due</button>
          </div>

          <!-- Payment Method Card -->
          <div class="payment-method-card">
            <h3 class="card-title">Payment Method</h3>
            <div class="payment-method-info">
              <span class="card-number">{{ paymentMethod ? `${paymentMethod.type} **** ${paymentMethod.last4}` : 'No payment method' }}</span>
              <span v-if="paymentMethod?.isDefault" class="default-badge">Default</span>
            </div>
            <button class="manage-methods-button" @click="handleManageMethods">
              Manage Methods
            </button>
          </div>
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
                    <span class="status-badge status-paid">{{ payment.status === 'paid' ? 'Paid' : payment.status }}</span>
                  </td>
                  <td>
                    <button 
                      class="download-link"
                      @click="handleDownloadReceipt(payment.receiptUrl, payment.id)"
                    >
                      Download
                    </button>
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

/* Cards Section */
.cards-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 40px;
}

.balance-card {
  background: #1500FF;
  border-radius: 12px;
  padding: 24px;
  color: white;
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

.no-payment-button {
  padding: 12px 24px;
  background: white;
  color: #1500FF;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.no-payment-button:hover {
  background: #f0f0f0;
}

.payment-method-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.payment-method-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.card-number {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.default-badge {
  padding: 4px 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.manage-methods-button {
  padding: 10px 20px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.manage-methods-button:hover {
  background: #4b5563;
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
@media (max-width: 1024px) {
  .cards-section {
    grid-template-columns: 1fr;
  }
}

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
