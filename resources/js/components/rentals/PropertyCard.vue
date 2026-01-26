<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { BuildingOfficeIcon, UsersIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  property: {
    type: Object,
    required: true
  }
})

const router = useRouter()
const route = useRoute()
const imageError = ref(false)

const handleImageError = (event) => {
  console.error('Image failed to load:', props.property.image)
  imageError.value = true
  // Set a fallback image
  event.target.src = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop'
}

const handleImageLoad = () => {
  imageError.value = false
}

const handleViewDetails = () => {
  // Determine route prefix based on current route
  const isAdmin = route.path.startsWith('/admin')
  const prefix = isAdmin ? '/admin/prop' : '/landlord/prop'
  router.push(`${prefix}-${props.property.id}`)
}
</script>

<template>
  <div class="property-card">
    <div class="property-image">
      <img 
        v-if="!imageError"
        :src="property.image" 
        :alt="property.name"
        @error="handleImageError"
        @load="handleImageLoad"
      />
      <div v-else class="image-placeholder">
        <span>{{ property.name }}</span>
      </div>
    </div>
    <div class="property-content">
      <div class="property-header">
        <h3 class="property-name">{{ property.name }}</h3>
        <span :class="['property-type', property.type]">
          {{ property.type === 'residential' ? 'Residential' : 'Commercial' }}
        </span>
      </div>
      <div class="property-location">
        <svg class="location-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="location-text">{{ property.location }}</span>
      </div>
      <div class="property-metrics">
        <div class="metric">
          <div class="metric-label">
            <BuildingOfficeIcon class="metric-icon" />
            <span>Units</span>
          </div>
          <div class="metric-value">{{ property.units }}</div>
        </div>
        <div class="metric">
          <div class="metric-label">
            <UsersIcon class="metric-icon" />
            <span>Tenants</span>
          </div>
          <div class="metric-value">{{ property.tenants }}</div>
        </div>
      </div>
      <button class="view-details-btn" @click="handleViewDetails">View Details</button>
    </div>
  </div>
</template>

<style scoped>
.property-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.property-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.property-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: #e5e5e5;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.property-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e5e5e5;
  color: #999;
  font-size: 16px;
  font-weight: 600;
  text-align: center;
  padding: 20px;
}

.property-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.property-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.property-name {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
  flex: 1;
}

.property-type {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.property-type.residential {
  background: #E3F2FD;
  color: #1976D2;
}

.property-type.commercial {
  background: #FFF3E0;
  color: #F57C00;
}

.property-location {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  color: #666;
  font-size: 14px;
  margin-bottom: 16px;
  font-family: 'Montserrat', sans-serif;
  min-height: 40px;
  line-height: 1.4;
}

.location-icon {
  width: 16px;
  height: 16px;
  color: #999;
  flex-shrink: 0;
  margin-top: 2px;
}

.location-text {
  flex: 1;
  word-wrap: break-word;
}

.property-metrics {
  display: flex;
  gap: 24px;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e5e5;
}

.metric {
  display: flex;
  flex-direction: column;
}

.metric-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #999;
  margin-bottom: 4px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
}

.metric-icon {
  width: 16px;
  height: 16px;
  color: #999;
  flex-shrink: 0;
}

.metric-value {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
}

.view-details-btn {
  width: 100%;
  padding: 12px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
  margin-top: auto;
}

.view-details-btn:hover {
  background: #0f00cc;
}
</style>
