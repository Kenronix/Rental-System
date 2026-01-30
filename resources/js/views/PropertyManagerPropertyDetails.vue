<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import PropertyManagerSidebar from '../components/layout/PropertyManagerSidebar.vue'
import api from '../services/api.js'
import { 
    ArrowLeftIcon, 
    MapPinIcon, 
    UserIcon, 
    HomeIcon, 
    UsersIcon, 
    InformationCircleIcon,
    PhoneIcon,
    EnvelopeIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const property = ref(null)
const loading = ref(true)

const fetchPropertyDetails = async () => {
    try {
        const response = await api.get(`/property-manager/properties/${route.params.id}`)
        if (response.data.success) {
            property.value = response.data.property
        }
    } catch (error) {
        console.error('Error fetching property details:', error)
    } finally {
        loading.value = false
    }
}

const goBack = () => {
    router.push('/property-manager/properties')
}

const formatUnitStatus = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'
}

onMounted(() => {
    fetchPropertyDetails()
})
</script>

<template>
  <div class="dashboard-layout">
    <PropertyManagerSidebar />
    <div class="main-content">
      <button @click="goBack" class="back-link">
        <ArrowLeftIcon class="icon-sm" />
        Back
      </button>

      <h1 class="page-title">Property Details</h1>

      <div v-if="loading" class="loading-state">
        Loading property details...
      </div>

      <div v-else-if="property" class="details-container">
        <!-- Top Section -->
        <div class="top-section">
            <div class="property-overview card">
                <div class="prop-image-wrapper">
                    <img :src="property.main_photo || '/placeholder-property.jpg'" :alt="property.name" class="prop-image" />
                </div>
                <div class="prop-info-overview">
                    <div class="header-row">
                        <h2 class="prop-name">{{ property.name }}</h2>
                    </div>
                    <div class="badges-row">
                         <span class="badge active">Active</span>
                         <span class="badge type">{{ property.type || 'Apartment' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <MapPinIcon class="icon-sm" />
                        <span class="info-label">Address</span>
                    </div>
                    <div class="info-detail indent">{{ property.street_address }}, {{ property.city }}</div>

                    <div class="info-row">
                         <HomeIcon class="icon-sm" />
                         <span class="info-label">Total Units</span>
                    </div>
                    <div class="info-detail indent-box">{{ property.total_units }}</div>

                    <div class="info-row">
                         <UsersIcon class="icon-sm" />
                         <span class="info-label">Total Tenants</span>
                    </div>
                    <div class="info-detail indent-box">{{ property.occupied_units }}</div>
                </div>
            </div>

            <div class="landlord-card card">
                <h3>Landlords Information</h3>
                
                <div class="landlord-profile">
                     <div class="avatar">{{ property.landlord?.name ? property.landlord.name.charAt(0).toUpperCase() : 'L' }}</div>
                     <div class="landlord-details">
                        <div class="landlord-name">{{ property.landlord?.name || 'Unknown' }}</div>
                        <div class="landlord-role">Landlord</div>
                     </div>
                </div>

                <div class="contact-info">
                    <div class="contact-item">
                        <PhoneIcon class="icon-sm" />
                        <span>{{ property.landlord?.phone || 'No phone' }}</span>
                    </div>
                     <div class="contact-item">
                        <EnvelopeIcon class="icon-sm" />
                        <span>{{ property.landlord?.email || 'No email' }}</span>
                    </div>
                </div>

                <button class="view-profile-btn">
                    <UserIcon class="btn-icon" />
                    View Profile
                </button>
            </div>
        </div>

        <!-- Bottom Section -->
         <div class="units-section card">
            <h3>Units & Rooms</h3>
            
            <div class="units-grid">
                <div v-for="unit in property.units" :key="unit.id" class="unit-card">
                    <div class="unit-image-wrapper">
                        <img :src="unit.photos && unit.photos.length > 0 ? unit.photos[0] : '/placeholder-unit.jpg'" alt="Unit" class="unit-image" />
                        <div class="unit-overlay">
                            <h4 class="unit-name">{{ unit.unit_number ? 'Unit ' + unit.unit_number : 'Unit' }}</h4>
                             <span class="status-badge-unit">{{ unit.status || (unit.is_occupied ? 'Occupied' : 'Allowed') }}</span>
                             <div class="info-icon-wrapper">
                                <InformationCircleIcon class="info-icon" />
                             </div>
                        </div>
                         <!-- Alternative design matching the image -->
                    </div>
                     <div class="unit-info-overlay-bottom">
                         <div class="unit-name-bottom">{{ unit.unit_number ? 'Unit ' + unit.unit_number : 'Unit' }}</div>
                         <div class="unit-status-bottom">{{ unit.is_occupied ? 'Occupied' : 'Available' }}</div>
                         <button class="info-btn">
                            <InformationCircleIcon class="info-icon-btn" />
                         </button>
                     </div>
                </div>
                 <div v-if="property.units.length === 0" class="empty-units">
                    No units found for this property.
                </div>
            </div>
         </div>
      </div>
       <div v-else class="empty-state">
            Property not found.
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

.back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    margin-bottom: 16px;
    font-weight: 500;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 32px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.top-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* Property Overview */
.property-overview {
    display: flex;
    gap: 24px;
}

.prop-image-wrapper {
    flex: 1;
    border-radius: 8px;
    overflow: hidden;
    height: 250px;
}

.prop-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prop-info-overview {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.prop-name {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.badges-row {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.badge {
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
}

.badge.active {
    background-color: #dcfce7;
    color: #16a34a;
}

.badge.type {
    background-color: #f3f4f6;
    color: #4b5563;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 13px;
    margin-top: 12px;
    margin-bottom: 4px;
}

.info-label {
    font-weight: 500;
}

.indent {
    margin-left: 24px;
    color: #4b5563;
    font-size: 14px;
    background: #f3f4f6;
    padding: 8px;
    border-radius: 4px;
    width: 100%;
}

.indent-box {
    margin-left: 24px;
    color: #4b5563;
    font-size: 14px;
    background: #f3f4f6;
    padding: 8px;
    border-radius: 4px;
    width: 100%;
}

.icon-sm {
    width: 16px;
    height: 16px;
}

/* Landlord Card */
.landlord-card h3 {
    margin-top: 0;
    font-size: 18px;
    margin-bottom: 20px;
}

.landlord-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #dbeafe;
    color: #1e40af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.landlord-name {
    font-weight: 700;
    color: #111827;
}

.landlord-role {
    font-size: 12px;
    color: #6b7280;
}

.contact-info {
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 24px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.view-profile-btn {
    width: 100%;
    background: #1500FF;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-icon {
    width: 18px;
    height: 18px;
}

/* Units Section */
.units-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.units-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.unit-card {
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    height: 200px;
    background: #f3f4f6;
}

.unit-image-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.unit-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.unit-info-overlay-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 20px;
    color: white;
}

.unit-name-bottom {
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 4px;
}

.unit-status-bottom {
    font-size: 12px;
    opacity: 0.9;
}

.info-btn {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 4px;
    border-radius: 4px;
    cursor: pointer;
    backdrop-filter: blur(4px);
}

.info-icon-btn {
    width: 20px;
    height: 20px;
}

.loading-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .top-section {
        grid-template-columns: 1fr;
    }
    
    .property-overview {
        flex-direction: column;
    }

    .prop-image-wrapper {
        height: 200px;
    }
}
</style>
