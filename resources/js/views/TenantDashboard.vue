<script setup>
import { ref, onMounted } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  BellIcon,
  DocumentTextIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const unit = ref(null)
const currentBalance = ref(0.00)
const utilityBills = ref([])
const paymentHistory = ref([])
const notifications = ref([])
const isLoading = ref(false)
const error = ref(null)

// Fetch dashboard data
const fetchDashboardData = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/dashboard')
    // For now, using mock data based on the image
    setTimeout(() => {
      unit.value = {
        unit_number: '402',
        property_name: 'Sunset Apartments',
        address: '123 Main St',
        status: 'Active Lease',
        monthly_rent: 1200.00,
        next_due: '2023-11-01',
        lease_end: '2026-08-01'
      }
      
      currentBalance.value = 0.00
      
      utilityBills.value = [
        {
          id: 1,
          name: 'Electricity',
          amount: 84.50,
          status: 'unpaid',
          dueDate: '2023-10-15'
        },
        {
          id: 2,
          name: 'Water',
          amount: 32.00,
          status: 'paid',
          paidDate: '2023-10-05'
        }
      ]
      
      paymentHistory.value = [
        {
          id: 1,
          month: 'October 2023',
          amount: 1200.00,
          status: 'paid',
          datePaid: '2023-10-01',
          receiptUrl: '#'
        },
        {
          id: 2,
          month: 'September 2023',
          amount: 1200.00,
          status: 'paid',
          datePaid: '2023-09-02',
          receiptUrl: '#'
        }
      ]
      
      notifications.value = [
        {
          id: 1,
          type: 'maintenance',
          title: 'Maintenance Scheduled',
          message: 'HVAC inspection on Oct 20, 10:00 AM.',
          icon: 'bell'
        },
        {
          id: 2,
          type: 'lease',
          title: 'Lease Renewal',
          message: 'Your lease expires in 10 months.',
          icon: 'document'
        }
      ]
      
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching dashboard data:', err)
    error.value = 'Failed to load dashboard data. Please try again.'
    isLoading.value = false
  }
}

const formatCurrency = (amount) => {
  if (!amount) return '₱0.00'
  return `₱${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

const formatCurrencyShort = (amount) => {
  if (!amount) return '₱0'
  return `₱${amount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getDate()}`
}

const formatDateFull = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`
}

const formatMonthYear = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getFullYear()}`
}

const handleDownloadReceipt = (receiptUrl, paymentId) => {
  // TODO: Implement receipt download
  console.log('Download receipt for payment:', paymentId)
  alert('Receipt download functionality will be implemented soon.')
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">Dashboard</h1>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading dashboard...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchDashboardData">Retry</button>
      </div>

      <!-- Dashboard Content -->
      <div v-else class="dashboard-content">
        <!-- Top Row: Unit Info and Current Balance -->
        <div class="top-row">
          <!-- Unit 402 Card -->
          <div class="unit-card">
            <div class="unit-header">
              <div class="unit-title-section">
                <h2 class="unit-number">Unit {{ unit?.unit_number }}</h2>
                <p class="unit-address">{{ unit?.property_name }}, {{ unit?.address }}</p>
              </div>
              <span class="status-badge status-active">{{ unit?.status }}</span>
            </div>
            
            <div class="unit-details-grid">
              <div class="detail-card">
                <p class="detail-label">Monthly Rent</p>
                <p class="detail-value">{{ formatCurrencyShort(unit?.monthly_rent) }}</p>
              </div>
              <div class="detail-card">
                <p class="detail-label">Next Due</p>
                <p class="detail-value">{{ formatDate(unit?.next_due) }}</p>
              </div>
              <div class="detail-card">
                <p class="detail-label">Lease Ends</p>
                <p class="detail-value">{{ formatMonthYear(unit?.lease_end) }}</p>
              </div>
            </div>
          </div>

          <!-- Current Balance Card -->
          <div class="balance-card">
            <h3 class="card-title">Current Balance</h3>
            <p class="balance-amount">{{ formatCurrency(currentBalance) }}</p>
            <button class="no-payment-button">No Payment Due</button>
            <p class="invoice-note">Next invoice will be generated on Oct 25</p>
          </div>
        </div>

        <!-- Middle Row: Utility Bills -->
        <div class="middle-row">
          <div class="utility-bills-card">
            <h3 class="card-title">Utility Bills</h3>
            <div class="utility-bills-list">
              <div v-for="utility in utilityBills" :key="utility.id" class="utility-bill-item">
                <div class="utility-bill-info">
                  <p class="utility-name">{{ utility.name }}</p>
                  <p class="utility-amount">{{ formatCurrency(utility.amount) }}</p>
                </div>
                <div class="utility-bill-status">
                  <span 
                    :class="['status-badge', utility.status === 'paid' ? 'status-paid' : 'status-unpaid']"
                  >
                    {{ utility.status === 'paid' ? 'Paid' : 'Unpaid' }}
                  </span>
                  <p class="utility-date">
                    {{ utility.status === 'paid' ? 'Paid' : 'Due' }} {{ formatDate(utility.dueDate || utility.paidDate) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Row: Payment History and Notifications -->
        <div class="bottom-row">
          <!-- Payment History Card -->
          <div class="payment-history-card">
            <h3 class="card-title">Payment History</h3>
            <div class="table-container">
              <table class="payment-table">
                <thead>
                  <tr>
                    <th>Month</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date Paid</th>
                    <th>Receipt</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="payment in paymentHistory" :key="payment.id">
                    <td>{{ payment.month }}</td>
                    <td class="amount-cell">{{ formatCurrency(payment.amount) }}</td>
                    <td>
                      <span class="status-badge status-paid">{{ payment.status === 'paid' ? 'Paid' : payment.status }}</span>
                    </td>
                    <td>{{ formatDateFull(payment.datePaid) }}</td>
                    <td>
                      <button 
                        class="download-link"
                        @click="handleDownloadReceipt(payment.receiptUrl, payment.id)"
                      >
                        <ArrowDownTrayIcon class="download-icon" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notifications Card -->
          <div class="notifications-card">
            <h3 class="card-title">Notifications</h3>
            <div class="notifications-list">
              <div v-for="notification in notifications" :key="notification.id" class="notification-item">
                <div class="notification-icon-container">
                  <BellIcon v-if="notification.icon === 'bell'" class="notification-icon" />
                  <DocumentTextIcon v-else-if="notification.icon === 'document'" class="notification-icon" />
                </div>
                <div class="notification-content">
                  <p class="notification-title">{{ notification.title }}</p>
                  <p class="notification-message">{{ notification.message }}</p>
                </div>
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

/* Dashboard Content */
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Top Row */
.top-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

/* Unit Card */
.unit-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.unit-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.unit-title-section {
  flex: 1;
}

.unit-number {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px 0;
}

.unit-address {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}

.unit-details-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.detail-card {
  background: #1500FF;
  border-radius: 8px;
  padding: 16px;
  color: white;
}

.detail-label {
  font-size: 12px;
  font-weight: 500;
  opacity: 0.9;
  margin: 0 0 8px 0;
}

.detail-value {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}

/* Balance Card */
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
  width: 100%;
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
  margin-bottom: 12px;
}

.no-payment-button:hover {
  background: #f0f0f0;
}

.invoice-note {
  font-size: 12px;
  opacity: 0.8;
  margin: 0;
}

/* Middle Row */
.middle-row {
  display: grid;
  grid-template-columns: 1fr;
}

.utility-bills-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.utility-bills-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.utility-bill-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.utility-bill-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.utility-bill-info {
  flex: 1;
}

.utility-name {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.utility-amount {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.utility-bill-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.status-paid {
  background: #dcfce7;
  color: #15803d;
}

.status-unpaid {
  background: #fed7aa;
  color: #c2410c;
}

.utility-date {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

/* Bottom Row */
.bottom-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.payment-history-card,
.notifications-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

.download-link {
  background: none;
  border: none;
  color: #1500FF;
  cursor: pointer;
  padding: 4px;
  display: inline-flex;
  align-items: center;
  transition: color 0.2s;
}

.download-link:hover {
  color: #1200e6;
}

.download-icon {
  width: 20px;
  height: 20px;
}

/* Notifications */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.notification-item {
  display: flex;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.notification-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.notification-icon-container {
  flex-shrink: 0;
}

.notification-icon {
  width: 24px;
  height: 24px;
  color: #1500FF;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.notification-message {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .top-row,
  .bottom-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .unit-details-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: scroll;
  }

  .payment-table {
    min-width: 600px;
  }
}
</style>
