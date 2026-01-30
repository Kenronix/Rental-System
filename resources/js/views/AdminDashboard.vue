<template>
  <div class="dashboard-layout">
    <AdminSidebar />
    <div class="main-content">
      <h1 class="page-title">Dashboard</h1>
      
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total landlords</h3>
          <p class="stat-number">{{ stats.totalLandlords || 0 }}</p>
        </div>
        <div class="stat-card">
          <h3>Total tenants</h3>
          <p class="stat-number">{{ stats.totalTenants || 0 }}</p>
        </div>
        <div class="stat-card">
          <h3>Total properties</h3>
          <p class="stat-number">{{ stats.totalProperties || 0 }}</p>
        </div>
        <div class="stat-card">
          <h3>Active users</h3>
          <p class="stat-number">{{ stats.activeUsers || 0 }}</p>
        </div>
        <div class="stat-card">
          <h3>Pending verifications</h3>
          <p class="stat-number">{{ stats.pendingVerifications || 0 }}</p>
        </div>
      </div>

      <!-- Bottom Section -->
      <div class="bottom-section">
        <!-- Recent System Activity -->
        <div class="activity-card">
          <div class="card-header">
            <h2>Recent System Activity</h2>
            <a href="#" class="view-all-link">View All</a>
          </div>
          <div class="activity-list">
            <div v-for="(activity, index) in recentActivities" :key="index" class="activity-item">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ activity.time }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="actions-card">
          <h2>Quick Actions</h2>
          <div class="actions-list">
            <button class="action-btn" @click="handleAddProperty">
              <span>Add New Property</span>
              <ArrowRightIcon class="action-icon" />
            </button>
            <button class="action-btn" @click="handleRegisterLandlord">
              <span>Register Landlord</span>
              <ArrowRightIcon class="action-icon" />
            </button>
            <button class="action-btn" @click="handleCreateInvoice">
              <span>Create Invoice</span>
              <ArrowRightIcon class="action-icon" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import AdminSidebar from '../components/layout/AdminSidebar.vue'
import { useAuth } from '../composables/useAuth.js'
import api from '../services/api.js'

const router = useRouter()
const { user } = useAuth()

const stats = ref({
  totalLandlords: 0,
  totalTenants: 0,
  totalProperties: 0,
  activeUsers: 0,
  pendingVerifications: 0
})

const recentActivities = ref([
  { text: 'Sarah Landlord added a new property Sunset Apartments', time: '2 hours ago' },
  { text: 'Tom Tenant paid rent for Unit 402', time: '4 hours ago' },
  { text: 'System generated monthly reports October 2023', time: '1 day ago' },
  { text: 'Mike Admin approved maintenance request #REQ-2023-001', time: '1 day ago' }
])

const handleAddProperty = () => {
  router.push('/admin/properties/add')
}

const handleRegisterLandlord = () => {
  router.push('/admin/users')
}

const handleCreateInvoice = () => {
  router.push('/admin/reports')
}

onMounted(async () => {
  // Fetch admin stats
  try {
    // You can add API calls here to fetch real stats
    // const response = await api.get('/admin/stats')
    // stats.value = response.data
    
    // For now, using placeholder data
    stats.value = {
      totalLandlords: 12,
      totalTenants: 45,
      totalProperties: 28,
      activeUsers: 57,
      pendingVerifications: 3
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
})
</script>

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
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #333;
  margin: 0 0 30px 0;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  text-transform: capitalize;
}

.stat-number {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #1500FF;
}

/* Bottom Section */
.bottom-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

/* Activity Card */
.activity-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.card-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.view-all-link {
  color: #1500FF;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s;
}

.view-all-link:hover {
  color: #0f00cc;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.activity-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e5e5;
}

.activity-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.activity-text {
  margin: 0;
  font-size: 14px;
  font-weight: 400;
  color: #333;
  line-height: 1.5;
}

.activity-time {
  font-size: 12px;
  font-weight: 400;
  color: #999;
}

/* Actions Card */
.actions-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.actions-card h2 {
  margin: 0 0 20px 0;
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.actions-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 14px 16px;
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  transition: all 0.3s;
  text-align: left;
}

.action-btn:hover {
  background: #f5f5f5;
  border-color: #1500FF;
  transform: translateX(4px);
}

.action-icon {
  width: 20px;
  height: 20px;
  color: #1500FF;
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 1400px) {
  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .bottom-section {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
