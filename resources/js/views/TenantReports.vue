<script setup>
import { ref, onMounted, computed } from 'vue'
import TenantSidebar from '../components/layout/TenantSidebar.vue'
import { 
  ArrowDownTrayIcon,
  DocumentTextIcon,
  EyeIcon,
  CalendarIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const summary = ref(null)
const paymentHistory = ref([])
const leaseInfo = ref(null)
const maintenanceRequests = ref([])
const utilitiesReport = ref([])
const isLoading = ref(false)
const error = ref(null)

// Filters
const dateRangeFilter = ref('all')
const paymentStatusFilter = ref('all')
const reportTypeFilter = ref('all')

// Fetch reports data
const fetchReports = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API endpoint
    // const response = await api.get('/tenant/reports')
    // For now, using mock data
    setTimeout(() => {
      summary.value = {
        outstandingBalance: 0.00,
        totalAmountPaid: 14400.00,
        leaseStatus: 'Active',
        leaseExpiringSoon: false,
        nextPaymentDueDate: '2025-11-01',
        daysUntilExpiration: 180
      }
      
      paymentHistory.value = [
        {
          id: 1,
          month: 'October 2025',
          dueDate: '2025-10-01',
          amount: 1200.00,
          paidAmount: 1200.00,
          balance: 0.00,
          status: 'paid',
          receiptUrl: '#'
        },
        {
          id: 2,
          month: 'September 2025',
          dueDate: '2025-09-01',
          amount: 1200.00,
          paidAmount: 1200.00,
          balance: 0.00,
          status: 'paid',
          receiptUrl: '#'
        },
        {
          id: 3,
          month: 'August 2025',
          dueDate: '2025-08-01',
          amount: 1200.00,
          paidAmount: 1200.00,
          balance: 0.00,
          status: 'paid',
          receiptUrl: '#'
        },
        {
          id: 4,
          month: 'July 2025',
          dueDate: '2025-07-01',
          amount: 1200.00,
          paidAmount: 0.00,
          balance: 1200.00,
          status: 'pending',
          receiptUrl: '#'
        }
      ]
      
      leaseInfo.value = {
        startDate: '2025-08-01',
        endDate: '2026-07-31',
        monthlyRent: 1200.00,
        securityDeposit: 1200.00,
        securityDepositStatus: 'Paid',
        daysRemaining: 180
      }
      
      maintenanceRequests.value = [
        {
          id: 1,
          type: 'HVAC Repair',
          dateSubmitted: '2025-10-15',
          status: 'resolved',
          resolutionDate: '2025-10-18'
        },
        {
          id: 2,
          type: 'Plumbing Issue',
          dateSubmitted: '2025-10-20',
          status: 'in_progress',
          resolutionDate: null
        },
        {
          id: 3,
          type: 'Electrical Problem',
          dateSubmitted: '2025-10-25',
          status: 'pending',
          resolutionDate: null
        }
      ]
      
      utilitiesReport.value = [
        {
          id: 1,
          type: 'Electricity',
          billingPeriod: 'October 2025',
          amount: 84.50,
          status: 'paid',
          paidDate: '2025-10-10'
        },
        {
          id: 2,
          type: 'Water',
          billingPeriod: 'October 2025',
          amount: 32.00,
          status: 'paid',
          paidDate: '2025-10-08'
        },
        {
          id: 3,
          type: 'Internet',
          billingPeriod: 'October 2025',
          amount: 60.00,
          status: 'paid',
          paidDate: '2025-10-05'
        }
      ]
      
      isLoading.value = false
    }, 500)
  } catch (err) {
    console.error('Error fetching reports:', err)
    error.value = 'Failed to load reports. Please try again.'
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

const formatDateShort = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[date.getMonth()]} ${date.getDate()}`
}

// Computed properties for filtered data
const filteredPaymentHistory = computed(() => {
  let filtered = paymentHistory.value
  
  if (paymentStatusFilter.value !== 'all') {
    filtered = filtered.filter(p => p.status === paymentStatusFilter.value)
  }
  
  return filtered
})

const paidPayments = computed(() => {
  return paymentHistory.value.filter(p => p.status === 'paid').length
})

const latePayments = computed(() => {
  return paymentHistory.value.filter(p => p.status === 'overdue').length
})

const handleViewReceipt = (receiptUrl, paymentId) => {
  // TODO: Implement view receipt
  console.log('View receipt for payment:', paymentId)
  alert('View receipt functionality will be implemented soon.')
}

const handleDownloadPDF = (type, id) => {
  // TODO: Implement PDF download
  console.log('Download PDF:', type, id)
  alert(`${type} download functionality will be implemented soon.`)
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'paid':
    case 'resolved':
      return 'status-paid'
    case 'pending':
      return 'status-pending'
    case 'overdue':
      return 'status-overdue'
    case 'in_progress':
      return 'status-in-progress'
    default:
      return ''
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'paid':
      return 'Paid'
    case 'pending':
      return 'Pending'
    case 'overdue':
      return 'Overdue'
    case 'resolved':
      return 'Resolved'
    case 'in_progress':
      return 'In Progress'
    default:
      return status
  }
}

onMounted(() => {
  fetchReports()
})
</script>

<template>
  <div class="dashboard-layout">
    <TenantSidebar />
    <div class="main-content">
      <!-- Page Title -->
      <div class="page-header">
        <h1 class="page-title">Reports</h1>
        <div class="header-actions">
          <button class="download-all-button" @click="handleDownloadPDF('All Reports', null)">
            <ArrowDownTrayIcon class="download-icon" />
            <span>Download All Reports</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading reports...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchReports">Retry</button>
      </div>

      <!-- Reports Content -->
      <div v-else class="reports-content">
        <!-- Summary Cards -->
        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-icon outstanding">
              <DocumentTextIcon class="icon" />
            </div>
            <div class="card-content">
              <p class="card-label">Outstanding Balance</p>
              <p class="card-value">{{ formatCurrency(summary?.outstandingBalance) }}</p>
            </div>
          </div>

          <div class="summary-card">
            <div class="card-icon paid">
              <CheckCircleIcon class="icon" />
            </div>
            <div class="card-content">
              <p class="card-label">Total Amount Paid</p>
              <p class="card-value">{{ formatCurrency(summary?.totalAmountPaid) }}</p>
            </div>
          </div>

          <div class="summary-card">
            <div class="card-icon" :class="summary?.leaseExpiringSoon ? 'expiring' : 'active'">
              <CalendarIcon class="icon" />
            </div>
            <div class="card-content">
              <p class="card-label">Lease Status</p>
              <p class="card-value">{{ summary?.leaseStatus }}</p>
              <p v-if="summary?.leaseExpiringSoon" class="card-subtext">Expiring in {{ summary?.daysUntilExpiration }} days</p>
            </div>
          </div>

          <div class="summary-card">
            <div class="card-icon due">
              <ClockIcon class="icon" />
            </div>
            <div class="card-content">
              <p class="card-label">Next Payment Due</p>
              <p class="card-value">{{ formatDateShort(summary?.nextPaymentDueDate) }}</p>
            </div>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
          <div class="filter-group">
            <label class="filter-label">Payment Status</label>
            <select v-model="paymentStatusFilter" class="filter-select">
              <option value="all">All Status</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="overdue">Overdue</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Date Range</label>
            <select v-model="dateRangeFilter" class="filter-select">
              <option value="all">All Time</option>
              <option value="month">This Month</option>
              <option value="quarter">This Quarter</option>
              <option value="year">This Year</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Report Type</label>
            <select v-model="reportTypeFilter" class="filter-select">
              <option value="all">All Reports</option>
              <option value="rent">Rent</option>
              <option value="utilities">Utilities</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
        </div>

        <!-- Payment History Overview -->
        <div class="overview-section">
          <div class="overview-card">
            <h3 class="section-title">Payment History Overview</h3>
            <div class="overview-stats">
              <div class="stat-item">
                <div class="stat-icon paid-stat">
                  <CheckCircleIcon class="stat-icon-svg" />
                </div>
                <div class="stat-info">
                  <p class="stat-label">Paid Payments</p>
                  <p class="stat-value">{{ paidPayments }}</p>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-icon late-stat">
                  <XCircleIcon class="stat-icon-svg" />
                </div>
                <div class="stat-info">
                  <p class="stat-label">Late Payments</p>
                  <p class="stat-value">{{ latePayments }}</p>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-info">
                  <p class="stat-label">Lifetime Total</p>
                  <p class="stat-value-large">{{ formatCurrency(summary?.totalAmountPaid) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rent & Payment Report -->
        <div class="report-section">
          <div class="report-card">
            <div class="report-header">
              <h3 class="section-title">Rent & Payment Report</h3>
              <button class="download-button" @click="handleDownloadPDF('Rent Payment Report', null)">
                <ArrowDownTrayIcon class="download-icon" />
                <span>Download PDF</span>
              </button>
            </div>
            <div class="table-container">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Month</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="payment in filteredPaymentHistory" :key="payment.id">
                    <td>{{ payment.month }}</td>
                    <td>{{ formatDateShort(payment.dueDate) }}</td>
                    <td class="amount-cell">{{ formatCurrency(payment.amount) }}</td>
                    <td class="amount-cell">{{ formatCurrency(payment.paidAmount) }}</td>
                    <td class="amount-cell">{{ formatCurrency(payment.balance) }}</td>
                    <td>
                      <span :class="['status-badge', getStatusBadgeClass(payment.status)]">
                        {{ getStatusLabel(payment.status) }}
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="action-btn view-btn" @click="handleViewReceipt(payment.receiptUrl, payment.id)">
                          <EyeIcon class="action-icon" />
                          <span>View</span>
                        </button>
                        <button class="action-btn download-btn" @click="handleDownloadPDF('Receipt', payment.id)">
                          <ArrowDownTrayIcon class="action-icon" />
                          <span>PDF</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Lease / Contract Report -->
        <div class="report-section">
          <div class="report-card">
            <div class="report-header">
              <h3 class="section-title">Lease / Contract Report</h3>
              <button class="download-button" @click="handleDownloadPDF('Lease Summary', null)">
                <ArrowDownTrayIcon class="download-icon" />
                <span>Download Summary</span>
              </button>
            </div>
            <div class="lease-info-grid">
              <div class="info-item">
                <p class="info-label">Lease Start Date</p>
                <p class="info-value">{{ formatDate(leaseInfo?.startDate) }}</p>
              </div>
              <div class="info-item">
                <p class="info-label">Lease End Date</p>
                <p class="info-value" :class="{ 'expiring-soon': leaseInfo?.daysRemaining < 90 }">
                  {{ formatDate(leaseInfo?.endDate) }}
                </p>
              </div>
              <div class="info-item">
                <p class="info-label">Monthly Rent</p>
                <p class="info-value highlight">{{ formatCurrency(leaseInfo?.monthlyRent) }}</p>
              </div>
              <div class="info-item">
                <p class="info-label">Security Deposit</p>
                <p class="info-value">{{ formatCurrency(leaseInfo?.securityDeposit) }}</p>
              </div>
              <div class="info-item">
                <p class="info-label">Security Deposit Status</p>
                <span :class="['status-badge', leaseInfo?.securityDepositStatus === 'Paid' ? 'status-paid' : 'status-pending']">
                  {{ leaseInfo?.securityDepositStatus }}
                </span>
              </div>
              <div class="info-item">
                <p class="info-label">Days Remaining</p>
                <p class="info-value" :class="{ 'expiring-soon': leaseInfo?.daysRemaining < 90 }">
                  {{ leaseInfo?.daysRemaining }} days
                </p>
              </div>
            </div>
            <div v-if="leaseInfo?.daysRemaining < 90" class="expiring-notice">
              <ClockIcon class="notice-icon" />
              <p>Your lease is expiring soon. Please contact your landlord for renewal.</p>
            </div>
          </div>
        </div>

        <!-- Maintenance & Service Requests Report -->
        <div v-if="reportTypeFilter === 'all' || reportTypeFilter === 'maintenance'" class="report-section">
          <div class="report-card">
            <div class="report-header">
              <h3 class="section-title">Maintenance & Service Requests</h3>
            </div>
            <div class="table-container">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Request Type</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Resolution Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="request in maintenanceRequests" :key="request.id">
                    <td>{{ request.type }}</td>
                    <td>{{ formatDate(request.dateSubmitted) }}</td>
                    <td>
                      <span :class="['status-badge', getStatusBadgeClass(request.status)]">
                        {{ getStatusLabel(request.status) }}
                      </span>
                    </td>
                    <td>{{ request.resolutionDate ? formatDate(request.resolutionDate) : 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Utilities Report -->
        <div v-if="reportTypeFilter === 'all' || reportTypeFilter === 'utilities'" class="report-section">
          <div class="report-card">
            <div class="report-header">
              <h3 class="section-title">Utilities Report</h3>
            </div>
            <div class="table-container">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Utility Type</th>
                    <th>Billing Period</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="utility in utilitiesReport" :key="utility.id">
                    <td>{{ utility.type }}</td>
                    <td>{{ utility.billingPeriod }}</td>
                    <td class="amount-cell">{{ formatCurrency(utility.amount) }}</td>
                    <td>
                      <span :class="['status-badge', getStatusBadgeClass(utility.status)]">
                        {{ getStatusLabel(utility.status) }}
                      </span>
                    </td>
                    <td>{{ utility.paidDate ? formatDateShort(utility.paidDate) : 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
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

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.page-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.download-all-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
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

.download-all-button:hover {
  background: #1200e6;
}

.download-icon {
  width: 18px;
  height: 18px;
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

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.summary-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.card-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon.outstanding {
  background: #fef3c7;
  color: #d97706;
}

.card-icon.paid {
  background: #dcfce7;
  color: #15803d;
}

.card-icon.active {
  background: #dbeafe;
  color: #1e40af;
}

.card-icon.expiring {
  background: #fed7aa;
  color: #c2410c;
}

.card-icon.due {
  background: #e0e7ff;
  color: #6366f1;
}

.card-icon .icon {
  width: 28px;
  height: 28px;
}

.card-content {
  flex: 1;
}

.card-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 4px 0;
}

.card-value {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.card-subtext {
  font-size: 12px;
  color: #c2410c;
  margin: 4px 0 0 0;
  font-weight: 600;
}

/* Filters Section */
.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-select {
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  cursor: pointer;
  min-width: 160px;
  outline: none;
  transition: border-color 0.2s;
}

.filter-select:hover {
  border-color: #9ca3af;
}

.filter-select:focus {
  border-color: #1500FF;
}

/* Overview Section */
.overview-section {
  margin-bottom: 32px;
}

.overview-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 20px 0;
}

.overview-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon.paid-stat {
  background: #dcfce7;
  color: #15803d;
}

.stat-icon.late-stat {
  background: #fee2e2;
  color: #dc2626;
}

.stat-icon-svg {
  width: 24px;
  height: 24px;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 4px 0;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.stat-value-large {
  font-size: 28px;
  font-weight: 700;
  color: #1500FF;
  margin: 0;
}

/* Report Sections */
.report-section {
  margin-bottom: 32px;
}

.report-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.download-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.download-button:hover {
  background: #e5e7eb;
}

/* Tables */
.table-container {
  overflow-x: auto;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
}

.report-table thead {
  background: #f9fafb;
}

.report-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.report-table td {
  padding: 16px;
  font-size: 14px;
  color: #111827;
  border-bottom: 1px solid #e5e7eb;
}

.report-table tbody tr:hover {
  background: #f9fafb;
}

.amount-cell {
  font-weight: 600;
  color: #111827;
}

/* Status Badges */
.status-badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}

.status-paid {
  background: #dcfce7;
  color: #15803d;
}

.status-pending {
  background: #fef3c7;
  color: #d97706;
}

.status-overdue {
  background: #fee2e2;
  color: #dc2626;
}

.status-in-progress {
  background: #dbeafe;
  color: #1e40af;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-family: 'Montserrat', sans-serif;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.view-btn {
  background: #e0e7ff;
  color: #6366f1;
}

.view-btn:hover {
  background: #c7d2fe;
}

.download-btn {
  background: #f3f4f6;
  color: #374151;
}

.download-btn:hover {
  background: #e5e7eb;
}

.action-icon {
  width: 16px;
  height: 16px;
}

/* Lease Info Grid */
.lease-info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.info-item {
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 8px 0;
}

.info-value {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.info-value.highlight {
  color: #1500FF;
  font-size: 20px;
}

.info-value.expiring-soon {
  color: #c2410c;
  font-weight: 700;
}

.expiring-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
  padding: 16px;
  background: #fef3c7;
  border-radius: 8px;
  border-left: 4px solid #d97706;
}

.notice-icon {
  width: 24px;
  height: 24px;
  color: #d97706;
  flex-shrink: 0;
}

.expiring-notice p {
  font-size: 14px;
  color: #92400e;
  margin: 0;
  font-weight: 500;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .overview-stats {
    grid-template-columns: 1fr;
  }

  .lease-info-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .lease-info-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: scroll;
  }

  .report-table {
    min-width: 800px;
  }
}
</style>
