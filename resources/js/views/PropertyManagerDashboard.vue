<script setup>
import { ref, onMounted } from 'vue'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import {
  BuildingOfficeIcon,
  UserGroupIcon,
  CurrencyDollarIcon,
  ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline'

const stats = ref({
  total_properties: 0,
  active_tenants: 0,
  monthly_revenue: 0
})
const recentPayments = ref([])
const expiringLeases = ref([])
const propertyPerformance = ref([])
const isLoading = ref(true)

const fetchDashboardData = async () => {
  try {
    const response = await api.get('/property-manager/dashboard')
    const data = response.data
    stats.value = data.stats
    recentPayments.value = data.recent_payments
    expiringLeases.value = data.expiring_leases
    propertyPerformance.value = data.property_performance
  } catch (error) {
    console.error('Error fetching dashboard details:', error)
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = (amount) => {
  return `₱${Number(amount).toLocaleString()}`
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <h1 class="page-title">Dashboard</h1>

      <div v-if="isLoading" class="loading">Loading...</div>
      
      <div v-else class="dashboard-content">
        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">{{ stats.total_properties }}</div>
            <div class="stat-label">Total Properties</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ stats.active_tenants }}</div>
            <div class="stat-label">Active Tenants</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ formatCurrency(stats.monthly_revenue) }}</div>
            <div class="stat-label">Monthly Revenue</div>
          </div>
        </div>

        <div class="content-grid">
          <!-- Main Column -->
          <div class="main-column">
            <!-- Property Performance -->
            <div class="card">
              <div class="card-header">
                <h3>Property Performance</h3>
                <button class="view-all">View All</button>
              </div>
              <p class="subtitle">Top performing properties this month</p>
              
              <div class="table-container">
                <table class="performance-table">
                  <thead>
                    <tr>
                      <th>Property</th>
                      <th>Location</th>
                      <th>Units</th>
                      <th>Occupancy</th>
                      <th>Revenue</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="prop in propertyPerformance" :key="prop.id">
                      <td>
                        <div class="prop-info">
                          <div class="prop-icon">
                            <BuildingOfficeIcon class="icon-sm" />
                          </div>
                          <span>{{ prop.name }}</span>
                        </div>
                      </td>
                      <td>{{ prop.location }}</td>
                      <td>{{ prop.units }}</td>
                      <td>
                        <div class="occupancy-track">
                          <div class="occupancy-fill" :style="{ width: prop.occupancy + '%', backgroundColor: prop.occupancy >= 90 ? '#4ade80' : prop.occupancy >= 70 ? '#fbbf24' : '#ef4444' }"></div>
                        </div>
                        <span class="occupancy-text">{{ prop.occupancy }}%</span>
                      </td>
                      <td>{{ formatCurrency(prop.revenue) }}</td>
                      <td>
                        <span :class="['status-badge', prop.status.toLowerCase()]">{{ prop.status }}</span>
                      </td>
                    </tr>
                    <tr v-if="propertyPerformance.length === 0">
                        <td colspan="6" class="empty-state">No properties found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Side Column -->
          <div class="side-column">
            <!-- Recent Payments -->
            <div class="card">
              <div class="card-header">
                <h3>Recent Payments</h3>
                <button class="view-all">View All</button>
              </div>
              <div class="payments-list">
                <div v-for="payment in recentPayments" :key="payment.id" class="payment-item">
                  <div class="avatar-sm">{{ payment.tenant_name.charAt(0) }}</div>
                  <div class="payment-info">
                    <div class="payment-name">{{ payment.tenant_name }}</div>
                    <div class="payment-prop">{{ payment.property_name }} #{{ payment.unit_number }}</div>
                  </div>
                  <div class="payment-amount">
                    <div class="amount-positive">+{{ formatCurrency(payment.amount) }}</div>
                    <div class="payment-date">{{ payment.date }}</div>
                  </div>
                </div>
                 <div v-if="recentPayments.length === 0" class="empty-state">No recent payments.</div>
              </div>
            </div>

            <!-- Expiring Leases -->
            <div class="card">
              <div class="card-header">
                <h3>Expiring Leases</h3>
                <button class="view-all">View All</button>
              </div>
              <div class="leases-list">
                <div v-for="lease in expiringLeases" :key="lease.id" class="lease-item">
                  <div class="lease-info">
                    <div class="lease-name">{{ lease.tenant_name }}</div>
                    <div class="lease-prop">{{ lease.property_name }} #{{ lease.unit_number }}</div>
                    <div class="lease-date">Expires: {{ lease.lease_end }}</div>
                  </div>
                  <div class="lease-days">{{ lease.days_remaining }} Days</div>
                </div>
                 <div v-if="expiringLeases.length === 0" class="empty-state">No expiring leases.</div>
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

.loading {
    text-align: center;
    padding: 20px;
    font-size: 18px;
    color: #666;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.card-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.view-all {
  color: #1500FF;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.subtitle {
  color: #6b7280;
  font-size: 14px;
  margin-top: 0;
  margin-bottom: 24px;
}

/* Table */
.table-container {
    overflow-x: auto;
}

.performance-table {
  width: 100%;
  border-collapse: collapse;
}

.performance-table th {
  text-align: left;
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.performance-table td {
  padding: 16px 0;
  font-size: 14px;
  color: #111827;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.performance-table tr:last-child td {
  border-bottom: none;
}

.prop-info {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
}

.prop-icon {
  width: 32px;
  height: 32px;
  background: #eff6ff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1500FF;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.occupancy-track {
  width: 80px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  display: inline-block;
  margin-right: 8px;
  overflow: hidden;
}

.occupancy-fill {
  height: 100%;
  border-radius: 3px;
}

.occupancy-text {
  font-size: 12px;
  color: #6b7280;
}

.status-badge {
    font-size: 12px;
    font-weight: 600;
}
.status-badge.excellent { color: #16a34a; }
.status-badge.good { color: #d97706; }
.status-badge.average { color: #dc2626; }

/* Payments */
.payments-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.payment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.avatar-sm {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  margin-right: 12px;
}

.payment-info {
  flex: 1;
}

.payment-name {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.payment-prop {
  font-size: 12px;
  color: #6b7280;
}

.payment-amount {
  text-align: right;
}

.amount-positive {
  color: #16a34a;
  font-weight: 600;
  font-size: 14px;
}

.payment-date {
  font-size: 11px;
  color: #6b7280;
}

/* Leases */
.leases-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.lease-item {
  background: #fffbeb;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #fef3c7;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.lease-info {
  flex: 1;
}

.lease-name {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.lease-prop {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.lease-date {
  font-size: 11px;
  color: #6b7280;
}

.lease-days {
  font-size: 12px;
  color: #d97706;
  font-weight: 600;
}


.empty-state {
    color: #9ca3af;
    font-size: 13px;
    text-align: center;
    padding: 10px;
}

@media (max-width: 1024px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .content-grid {
    grid-template-columns: 1fr;
  }
  
  .dashboard-layout {
    flex-direction: column;
  }
}
</style>
