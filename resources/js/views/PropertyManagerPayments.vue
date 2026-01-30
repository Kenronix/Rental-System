<script setup>
import { ref, onMounted, computed } from 'vue'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import { 
    MagnifyingGlassIcon, 
    EllipsisVerticalIcon 
} from '@heroicons/vue/24/outline'

const payments = ref([])
const stats = ref({
    total_payments: 0,
    paid_amount: 0,
    pending_amount: 0,
    overdue_amount: 0
})
const loading = ref(true)
const searchQuery = ref('')
const selectedType = ref('All Types')
const selectedStatus = ref('All Status')

const fetchPayments = async () => {
    try {
        const response = await api.get('/property-manager/payments')
        if (response.data.success) {
            payments.value = response.data.payments
            stats.value = response.data.stats
        }
    } catch (error) {
        console.error('Error fetching payments:', error)
    } finally {
        loading.value = false
    }
}

const filteredPayments = computed(() => {
    return payments.value.filter(payment => {
        const matchesSearch = 
            payment.tenant_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            payment.property_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            payment.unit_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            payment.reference?.toLowerCase().includes(searchQuery.value.toLowerCase())
        
        const matchesType = selectedType.value === 'All Types' || payment.type === selectedType.value
        const matchesStatus = selectedStatus.value === 'All Status' || payment.status === selectedStatus.value.toLowerCase()

        return matchesSearch && matchesType && matchesStatus
    })
})

const formatCurrency = (amount) => {
    return `₱${Number(amount).toLocaleString()}`
}

onMounted(() => {
    fetchPayments()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">Payments</h1>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ stats.total_payments }}</div>
            <div class="stat-label">Total Payments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.paid_amount) }}</div>
            <div class="stat-label">Paid Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.pending_amount) }}</div>
            <div class="stat-label">Pending Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.overdue_amount) }}</div>
            <div class="stat-label">Overdue Amount</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="search-wrapper">
            <MagnifyingGlassIcon class="search-icon" />
            <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search payments by tenant, property, unit, or reference..." 
                class="search-input"
            />
        </div>
        <div class="filter-dropdown">
            <select v-model="selectedType" class="dropdown-select">
                <option>All Types</option>
                <option value="Rent">Rent</option>
                <option value="Utility">Utility</option>
            </select>
        </div>
        <div class="filter-dropdown">
            <select v-model="selectedStatus" class="dropdown-select">
                <option>All Status</option>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Overdue">Overdue</option>
            </select>
        </div>
      </div>

      <!-- Payments Table -->
      <div class="table-container">
        <table class="data-table">
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td colspan="11" class="loading-cell">Loading payments...</td>
                </tr>
                <tr v-else-if="filteredPayments.length === 0">
                    <td colspan="11" class="empty-cell">No payments found.</td>
                </tr>
                <tr v-else v-for="payment in filteredPayments" :key="payment.id">
                    <td>
                        <div class="tenant-info">
                            <div class="tenant-name">{{ payment.tenant_name }}</div>
                            <div class="tenant-email">{{ payment.tenant_email }}</div>
                        </div>
                    </td>
                    <td class="property-name">{{ payment.property_name }}</td>
                    <td>{{ payment.unit_number }}</td>
                    <td>{{ payment.type }}</td>
                    <td class="amount">{{ formatCurrency(payment.amount) }}</td>
                    <td class="date">{{ payment.payment_date }}</td>
                    <td class="date">{{ payment.due_date }}</td>
                    <td>
                        <span :class="['status-badge', payment.status.toLowerCase().replace(' ', '-')]">
                            {{ payment.status }}
                        </span>
                    </td>
                    <td>{{ payment.payment_method }}</td>
                    <td>{{ payment.reference }}</td>
                    <td>
                        <button class="action-btn"><EllipsisVerticalIcon class="action-icon" /></button>
                    </td>
                </tr>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f3f4f6;
  font-family: 'Montserrat', sans-serif;
}

.main-content {
  flex: 1;
  margin-left: 300px;
  padding: 40px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 32px;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
}

/* Filters */
.filters-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
}

.search-wrapper {
    position: relative;
    flex: 2;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #9ca3af;
}

.search-input {
    width: 100%;
    padding: 10px 10px 10px 40px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    background: white;
}

.filter-dropdown {
    flex: 0 0 auto;
}

.dropdown-select {
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    outline: none;
    min-width: 140px;
}

/* Table */
.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px; /* Ensure table doesn't squash too much */
}

.data-table th {
    text-align: left;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    white-space: nowrap;
}

.data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    font-size: 14px;
    color: #374151;
}

.data-table tr:last-child td {
    border-bottom: none;
}

/* Specific Column Styles */
.tenant-info {
    display: flex;
    flex-direction: column;
}

.tenant-name {
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}

.tenant-email {
    font-size: 12px;
    color: #6b7280;
}

.property-name {
    color: #1500FF; /* As seen in screenshot, property name is link-colored */
    font-weight: 500;
}

.amount {
    font-weight: 700;
    color: #111827;
}

.date {
    color: #6b7280;
    font-size: 13px;
    white-space: nowrap;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    display: inline-block;
    white-space: nowrap;
}

.status-badge.paid {
    background-color: #dcfce7;
    color: #16a34a;
}
.status-badge.pending {
    background-color: #fef9c3;
    color: #ca8a04;
}
.status-badge.overdue, .status-badge.not-paid {
    background-color: #fee2e2;
    color: #ef4444;
}

.action-btn {
    padding: 4px;
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    border-radius: 4px;
}

.action-btn:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.action-icon {
    width: 20px;
    height: 20px;
}

.loading-cell, .empty-cell {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
