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
      <div class="header">
        <h1>Tenant Dashboard</h1>
        <button class="logout-btn" @click="handleLogout">
          <ArrowRightOnRectangleIcon class="logout-icon" />
          <span>Logout</span>
        </button>
      </div>
      <p>Welcome to your tenant dashboard!</p>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { logout: authLogout } = useAuth()

const handleLogout = async () => {
  await authLogout()
  router.push('/')
}
</script>
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

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.header h1 {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.logout-btn:hover {
  background: #b91c1c;
}

.logout-icon {
  width: 20px;
  height: 20px;
}
</style>

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
