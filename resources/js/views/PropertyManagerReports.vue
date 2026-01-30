<script setup>
import { ref, onMounted, computed } from 'vue'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import { 
    MagnifyingGlassIcon, 
    ArrowDownTrayIcon,
    EllipsisVerticalIcon 
} from '@heroicons/vue/24/outline'

const transactions = ref([])
const propertiesList = ref([])
const stats = ref({
    properties_managed: 0,
    total_units: 0,
    total_tenants: 0,
    income_this_month: 0
})
const loading = ref(true)
const searchQuery = ref('')
const selectedProperty = ref('All Properties')
const selectedPaymentType = ref('Payment')
const selectedUtility = ref('Utilities')
const selectedReportRange = ref('Monthly Report')

const fetchReportsData = async () => {
    try {
        const response = await api.get('/property-manager/reports')
        if (response.data.success) {
            transactions.value = response.data.transactions
            stats.value = response.data.stats
            propertiesList.value = response.data.properties_list
        }
    } catch (error) {
        console.error('Error fetching reports data:', error)
    } finally {
        loading.value = false
    }
}

const filteredTransactions = computed(() => {
    return transactions.value.filter(tx => {
        const matchesSearch = 
            tx.tenant_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            tx.property_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            tx.unit_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            tx.reference?.toLowerCase().includes(searchQuery.value.toLowerCase())
        
        const matchesProperty = selectedProperty.value === 'All Properties' || tx.property_name === selectedProperty.value

        return matchesSearch && matchesProperty
    })
})

const formatCurrency = (amount) => {
    return `₱${Number(amount).toLocaleString()}`
}

const exportReport = () => {
    alert('Exporting report...')
}

onMounted(() => {
    fetchReportsData()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">Reports</h1>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ stats.properties_managed }}</div>
            <div class="stat-label">Properties Managed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ stats.total_units }}</div>
            <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ stats.total_tenants }}</div>
            <div class="stat-label">Total Tenants</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.income_this_month) }}</div>
            <div class="stat-label">Income (This Month)</div>
        </div>
      </div>

      <!-- Filters Bar -->
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
        
        <select v-model="selectedProperty" class="filter-select">
            <option>All Properties</option>
            <option v-for="prop in propertiesList" :key="prop.id" :value="prop.name">
                {{ prop.name }}
            </option>
        </select>

        <select v-model="selectedPaymentType" class="filter-select">
            <option>Payment</option>
            <option value="Rent">Rent</option>
            <option value="Deposit">Deposit</option>
        </select>

        <select v-model="selectedUtility" class="filter-select">
            <option>Utilities</option>
            <option value="Water">Water</option>
            <option value="Electricity">Electricity</option>
        </select>

        <select v-model="selectedReportRange" class="filter-select">
            <option>Monthly Report</option>
            <option value="Weekly">Weekly Report</option>
            <option value="Yearly">Yearly Report</option>
        </select>

        <button @click="exportReport" class="export-btn">
            <ArrowDownTrayIcon class="btn-icon" />
            Export
        </button>
      </div>

      <!-- Transactions Table -->
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
                    <td colspan="11" class="loading-cell">Loading reports...</td>
                </tr>
                <tr v-else-if="filteredTransactions.length === 0">
                    <td colspan="11" class="empty-cell">No records found.</td>
                </tr>
                <tr v-else v-for="tx in filteredTransactions" :key="tx.id">
                    <td>
                        <div class="tenant-info">
                            <div class="tenant-name">{{ tx.tenant_name }}</div>
                            <div class="tenant-email">{{ tx.tenant_email }}</div>
                        </div>
                    </td>
                    <td class="property-link">{{ tx.property_name }}</td>
                    <td>{{ tx.unit_number }}</td>
                    <td>{{ tx.type }}</td>
                    <td class="amount">{{ formatCurrency(tx.amount) }}</td>
                    <td class="date-cell">{{ tx.payment_date }}</td>
                    <td class="date-cell">{{ tx.due_date }}</td>
                    <td>
                        <span :class="['status-badge', tx.status.toLowerCase().replace(' ', '-')]">
                            {{ tx.status }}
                        </span>
                    </td>
                    <td>{{ tx.payment_method }}</td>
                    <td class="ref-cell">{{ tx.reference }}</td>
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

/* Stats */
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
    font-size: 28px;
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
    gap: 12px;
    margin-bottom: 24px;
    align-items: center;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 300px;
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
    font-size: 14px;
    outline: none;
    background: white;
}

.filter-select {
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    background-color: white;
    cursor: pointer;
    outline: none;
    min-width: 120px;
}

.export-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #1500FF;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.export-btn:hover {
    background: #1200e6;
}

.btn-icon {
    width: 18px;
    height: 18px;
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
    min-width: 1100px;
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
    font-size: 14px;
    color: #374151;
}

.tenant-info {
    display: flex;
    flex-direction: column;
}

.tenant-name {
    font-weight: 600;
    color: #111827;
}

.tenant-email {
    font-size: 11px;
    color: #9ca3af;
}

.property-link {
    color: #1500FF;
    font-weight: 500;
}

.amount {
    font-weight: 700;
    color: #111827;
}

.date-cell {
    color: #9ca3af;
    font-size: 13px;
    white-space: nowrap;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.paid {
    background-color: #dcfce7;
    color: #16a34a;
}
.status-badge.pending {
    background-color: #fef9c3;
    color: #ca8a04;
}
.status-badge.not-paid, .status-badge.overdue {
    background-color: #fee2e2;
    color: #ef4444;
}

.ref-cell {
    color: #9ca3af;
}

.action-btn {
    padding: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
}

.action-icon {
    width: 18px;
    height: 18px;
}

.loading-cell, .empty-cell {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .filters-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .search-wrapper {
        min-width: 0;
    }
}
</style>
