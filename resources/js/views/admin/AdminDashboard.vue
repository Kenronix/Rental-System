<template>
  <div class="admin-layout">
    <AdminSidebar />
    
    <main class="admin-main">
      <header class="dashboard-header">
        <h1>Dashboard</h1>
      </header>

      <section class="stats-grid">
        <div class="stat-card" v-for="(val, key) in stats" :key="key">
          <p class="stat-value">{{ val }}</p>
          <p class="stat-label">{{ formatLabel(key) }}</p>
        </div>
      </section>

      <div class="content-grid">
        <section class="activity-section">
          <div class="section-header">
            <h2>Recent System Activity</h2>
            <button class="view-all">View All</button>
          </div>
          <div class="activity-list">
            <div class="activity-item" v-for="item in activities" :key="item.target">
              <div class="activity-avatar" :style="{ backgroundColor: getAvatarColor(item.initial) }">
                {{ item.initial }}
              </div>
              <div class="activity-content">
                <p>
                  <span class="act-user">{{ item.user }}</span>
                  <span class="act-action">{{ item.action }}</span>
                  <span class="act-target">{{ item.target }}</span>
                </p>
                <span class="act-time">{{ item.time }}</span>
              </div>
            </div>
          </div>
        </section>

        <section class="quick-actions">
          <div class="section-header">
            <h2>Quick Actions</h2>
          </div>
          <div class="action-buttons">
            <button class="action-btn">
              Add New Property
              <ChevronRightIcon class="arrow-icon" />
            </button>
            <button class="action-btn">
              Register Landlord
              <ChevronRightIcon class="arrow-icon" />
            </button>
            <button class="action-btn">
              Create Invoice
              <ChevronRightIcon class="arrow-icon" />
            </button>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import api from '@/services/api';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

const stats = ref({
  total_landlords: 0,
  total_tenants: 0,
  total_properties: 0,
  active_users: 0,
  pending_verifications: 0
});

const activities = ref([]);

const formatLabel = (key) => {
  return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getAvatarColor = (initial) => {
  const colors = {
    'S': '#E3F2FD', // Light Blue
    'T': '#F3E5F5', // Light Purple
    'M': '#E8F5E9', // Light Green
    'Generic': '#F5F5F5'
  };
  return colors[initial] || colors['Generic'];
};

const fetchDashboardData = async () => {
  try {
    const response = await api.get('/admin/dashboard');
    stats.value = response.data.stats;
    activities.value = response.data.recent_activity;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.admin-layout {
  display: flex;
  background: #F0F0F0;
  min-height: 100vh;
  font-family: 'Montserrat', sans-serif;
}

.admin-main {
  flex-grow: 1;
  margin-left: 300px;
  padding: 40px;
}

.dashboard-header {
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  font-size: 2rem;
  color: #1a1a1a;
  font-weight: 600;
  margin: 0;
  padding-bottom: 1rem;
  border-bottom: 1px solid #E0E0E0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1.25rem;
  margin-bottom: 2.5rem;
}

.stat-card {
  background: white;
  padding: 2rem 1.5rem;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: left;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
}

.stat-label {
  color: #1a1a1a;
  font-size: 0.9rem;
  font-weight: 600;
  margin: 0;
}

.content-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 2.5rem;
  align-items: start;
}

.activity-section, .quick-actions {
  background: white;
  padding: 1.75rem;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.section-header h2 {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.view-all {
  background: none;
  border: none;
  color: #666;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

.activity-item {
  display: flex;
  gap: 1.25rem;
  align-items: center;
}

.activity-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  color: #555;
}

.activity-content p {
  font-size: 0.85rem;
  color: #555;
  margin: 0;
  line-height: 1.4;
}

.act-user { font-weight: 700; color: #333; margin-right: 0.4rem; }
.act-action { margin-right: 0.4rem; }
.act-target { font-weight: 700; color: #333; }
.act-time { font-size: 0.75rem; color: #999; margin-top: 0.35rem; display: block; }

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border: 1px solid #E0E0E0;
  background: white;
  border-radius: 6px;
  color: #333;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.action-btn:hover {
  background: #fcfcfc;
  border-color: #ccc;
  transform: translateX(4px);
}

.arrow-icon {
  width: 16px;
  height: 16px;
  color: #999;
}
</style>
