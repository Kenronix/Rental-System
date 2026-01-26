import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import LandlordDashboard from '../views/LandlordDashboard.vue'
import LandlordProperties from '../views/LandlordProperties.vue'
import LandlordAddnewproperty from '../views/LandlordAddnewproperty.vue'
import LandlordPropertiesDetails from '../views/LandlordPropertiesDetails.vue'
import LandlordAddUnit from '../views/LandlordAddUnit.vue'
import LandlordPropertiesUnit from '../views/LandlordPropertiesUnit.vue'
import LandlordTenants from '../views/LandlordTenants.vue'
import LandlordPayments from '../views/LandlordPayments.vue'
import LandlordReports from '../views/LandlordReports.vue'
import TenantDashboard from '../views/TenantDashboard.vue'
import TenantApplicationForm from '../views/TenantApplicationForm.vue'
import AdminLogin from '../views/AdminLogin.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import AdminProperties from '../views/AdminProperties.vue'
import { useAuth } from '../composables/useAuth.js'

const routes = [
  {
    path: '/',
    name: 'LandingPage',
    component: LandingPage,
    meta: { requiresAuth: false }
  },
  {
    path: '/landlord/dashboard',
    name: 'Dashboard',
    component: LandlordDashboard,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/properties',
    name: 'Properties',
    component: LandlordProperties,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/properties/add',
    name: 'AddProperty',
    component: LandlordAddnewproperty,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/prop-:id/edit',
    name: 'EditProperty',
    component: LandlordAddnewproperty,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/prop-:id/units/add',
    name: 'AddUnit',
    component: LandlordAddUnit,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/prop-:id/units/:unitId',
    name: 'UnitDetails',
    component: LandlordPropertiesUnit,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/prop-:id/units/:unitId/edit',
    name: 'EditUnit',
    component: LandlordAddUnit,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/prop-:id',
    name: 'PropertyDetails',
    component: LandlordPropertiesDetails,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/tenants',
    name: 'Tenants',
    component: LandlordTenants,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/payments',
    name: 'Payments',
    component: LandlordPayments,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/landlord/reports',
    name: 'Reports',
    component: LandlordReports,
    meta: { requiresAuth: true, userType: 'landlord' }
  },
  {
    path: '/tenant/dashboard',
    name: 'TenantDashboard',
    component: TenantDashboard,
    meta: { requiresAuth: true, userType: 'tenant' }
  },
  {
    path: '/units/:unitId/apply',
    name: 'TenantApplication',
    component: TenantApplicationForm,
    meta: { requiresAuth: false }
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: AdminLogin,
    meta: { requiresAuth: false }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/properties',
    name: 'AdminProperties',
    component: AdminProperties,
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/properties/add',
    name: 'AdminAddProperty',
    component: () => import('../views/LandlordAddnewproperty.vue'),
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/prop-:id',
    name: 'AdminPropertyDetails',
    component: () => import('../views/LandlordPropertiesDetails.vue'),
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/prop-:id/edit',
    name: 'AdminEditProperty',
    component: () => import('../views/LandlordAddnewproperty.vue'),
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: AdminDashboard,
    meta: { requiresAuth: true, userType: 'admin' }
  },
  {
    path: '/admin/reports',
    name: 'AdminReports',
    component: AdminDashboard,
    meta: { requiresAuth: true, userType: 'admin' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const { checkAuth, isAuthenticated, userType } = useAuth()
  
  // Check authentication status
  await checkAuth()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth) {
    if (!isAuthenticated.value) {
      // Not authenticated, redirect to landing page
      next({ name: 'LandingPage' })
      return
    }
    
    // Check if user type matches route requirement
    if (to.meta.userType && userType.value !== to.meta.userType) {
      // Wrong user type, redirect to their dashboard
      if (userType.value === 'landlord') {
        next({ name: 'Dashboard' })
      } else if (userType.value === 'tenant') {
        next({ name: 'TenantDashboard' })
      } else if (userType.value === 'admin') {
        next({ name: 'AdminDashboard' })
      } else {
        next({ name: 'LandingPage' })
      }
      return
    }
  }
  
  // If already authenticated and trying to access landing page, redirect to dashboard
  if (to.name === 'LandingPage' && isAuthenticated.value) {
    if (userType.value === 'landlord') {
      next({ name: 'Dashboard' })
      return
    } else if (userType.value === 'tenant') {
      next({ name: 'TenantDashboard' })
      return
    } else if (userType.value === 'admin') {
      next({ name: 'AdminDashboard' })
      return
    }
  }
  
  // If admin tries to access login while authenticated, redirect to dashboard
  if (to.name === 'AdminLogin' && isAuthenticated.value && userType.value === 'admin') {
    next({ name: 'AdminDashboard' })
    return
  }
  
  next()
})

export default router
