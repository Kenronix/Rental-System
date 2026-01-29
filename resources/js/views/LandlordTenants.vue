<script setup>
import { ref, computed, onMounted, onActivated, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import { 
  MagnifyingGlassIcon,
  EyeIcon,
  EllipsisVerticalIcon,
  EnvelopeIcon,
  XMarkIcon,
  TrashIcon,
  KeyIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import { ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const router = useRouter()

const tenants = ref([])
const allTenants = ref([])
const properties = ref([])
const statistics = ref({
  total_tenants: 0,
  active_leases: 0,
  pending_invites: 0
})
const isLoading = ref(false)
const error = ref(null)
const showApplicationModal = ref(false)
const selectedApplication = ref(null)
const isLoadingApplication = ref(false)
const isUpdatingStatus = ref(false)
const openDropdownId = ref(null)
const dropdownPosition = ref({ top: 0, right: 0 })
const showTenantModal = ref(false)
const selectedTenant = ref(null)
const isLoadingTenant = ref(false)

const searchQuery = ref('')
const statusFilter = ref('All')
const propertyFilter = ref('All')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Fetch tenants data
const fetchTenants = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/tenants')
    
    if (response.data.success) {
      allTenants.value = response.data.tenants || []
      
      // Set statistics with proper defaults
      if (response.data.statistics) {
        statistics.value = {
          total_tenants: response.data.statistics.total_tenants || 0,
          active_leases: response.data.statistics.active_leases || 0,
          pending_invites: response.data.statistics.pending_invites || 0
        }
      } else {
        statistics.value = {
          total_tenants: 0,
          active_leases: 0,
          pending_invites: 0
        }
      }
      
      // Get unique properties for filter
      const uniqueProperties = [...new Set((response.data.tenants || []).map(t => t.property_name).filter(Boolean))]
      properties.value = uniqueProperties
      
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching tenants:', err)
    error.value = 'Failed to load tenants. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Filter tenants
const filteredTenants = computed(() => {
  let filtered = allTenants.value
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((tenant) => {
      const name = (tenant.name || '').toLowerCase()
      const email = (tenant.email || '').toLowerCase()
      const property = (tenant.property_name || '').toLowerCase()
      return name.includes(q) || email.includes(q) || property.includes(q)
    })
  }
  
  // Filter by status
  if (statusFilter.value !== 'All') {
    filtered = filtered.filter((tenant) => {
      return tenant.status === statusFilter.value.toLowerCase()
    })
  }
  
  // Filter by property
  if (propertyFilter.value !== 'All') {
    filtered = filtered.filter((tenant) => {
      return tenant.property_name === propertyFilter.value
    })
  }
  
  return filtered
})

// Pagination
const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredTenants.value.length / itemsPerPage.value))
})

const paginatedTenants = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredTenants.value.slice(start, end)
})

const paginationInfo = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, filteredTenants.value.length)
  const total = filteredTenants.value.length
  return { start, end, total }
})

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const goToNextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const resetToFirstPage = () => {
  currentPage.value = 1
}

// Format currency
const formatCurrency = (amount) => {
  if (!amount) return '₱0'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Get status badge class
const getStatusClass = (status) => {
  switch (status) {
    case 'active':
      return 'status-active'
    case 'inactive':
      return 'status-inactive'
    case 'pending':
      return 'status-pending'
    case 'approved':
      return 'status-approved'
    case 'rejected':
      return 'status-rejected'
    default:
      return ''
  }
}

// Format lease start date (handle applications)
const formatLeaseStart = (dateString, type) => {
  if (type === 'application') {
    return '-'
  }
  return formatDate(dateString)
}

// Format lease end date (handle applications)
const formatLeaseEnd = (dateString, type) => {
  if (type === 'application') {
    return '-'
  }
  if (!dateString) {
    return 'N/A'
  }
  return formatDate(dateString)
}

// Actions
const handleViewTenant = async (tenant) => {
  // If it's an application, show application details
  if (tenant.type === 'application' && tenant.application_id) {
    await openApplicationModal(tenant.application_id)
  } else if (tenant.id && tenant.type === 'tenant') {
    // Show tenant details modal with the specific unit they applied for
    await openTenantModal(tenant.id, tenant.unit_id)
  }
}

const openApplicationModal = async (applicationId) => {
  isLoadingApplication.value = true
  showApplicationModal.value = true
  
  try {
    const response = await api.get(`/tenant-applications/${applicationId}`)
    if (response.data.success) {
      selectedApplication.value = response.data.application
    } else {
      error.value = 'Failed to load application details.'
      showApplicationModal.value = false
    }
  } catch (err) {
    console.error('Error fetching application:', err)
    error.value = 'Failed to load application details.'
    showApplicationModal.value = false
  } finally {
    isLoadingApplication.value = false
  }
}

const closeApplicationModal = () => {
  showApplicationModal.value = false
  selectedApplication.value = null
}

const openTenantModal = async (tenantId, unitId) => {
  isLoadingTenant.value = true
  showTenantModal.value = true
  selectedTenant.value = null
  error.value = null
  
  try {
    // Pass unitId as query parameter so backend can find the correct application
    const url = unitId ? `/tenants/${tenantId}?unit_id=${unitId}` : `/tenants/${tenantId}`
    const response = await api.get(url)
    if (response.data.success) {
      const tenant = response.data.tenant
      // Filter to show only the unit they applied for
      if (unitId && tenant.units) {
        tenant.units = tenant.units.filter(unit => unit.id === unitId)
      }
      selectedTenant.value = tenant
    } else {
      error.value = response.data.message || 'Failed to load tenant details.'
    }
  } catch (err) {
    console.error('Error fetching tenant:', err)
    error.value = err.response?.data?.message || 'Failed to load tenant details. Please try again.'
  } finally {
    isLoadingTenant.value = false
  }
}

const closeTenantModal = () => {
  showTenantModal.value = false
  selectedTenant.value = null
}

// Download tenant profile PDF
const downloadTenantProfilePdf = async () => {
  if (!selectedTenant.value || !selectedTenant.value.id) return
  
  try {
    const response = await api.get(`/reports/download-tenant-profile-pdf/${selectedTenant.value.id}`, {
      responseType: 'blob'
    })
    
    // Check if response is PDF or HTML
    const contentType = response.headers['content-type'] || ''
    
    if (contentType.includes('application/pdf')) {
      // It's a real PDF, download it directly
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      const tenantName = selectedTenant.value.name.replace(/\s+/g, '_')
      link.href = url
      link.setAttribute('download', `tenant_profile_${tenantName}_${new Date().toISOString().slice(0, 10)}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } else {
      // It's HTML, convert to PDF using html2pdf.js
      const blob = new Blob([response.data], { type: 'text/html' })
      const reader = new FileReader()
      
      reader.onload = async (e) => {
        const htmlContent = e.target.result
        
        // Load html2pdf.js from CDN if not already loaded
        if (!window.html2pdf) {
          const script = document.createElement('script')
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'
          await new Promise((resolve) => {
            script.onload = resolve
            document.head.appendChild(script)
          })
        }
        
        await convertHtmlToPdf(htmlContent, `tenant_profile_${selectedTenant.value.name.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.pdf`)
      }
      
      reader.readAsText(blob)
      
      async function convertHtmlToPdf(html, pdfFilename) {
        try {
          // Create an iframe to properly render the HTML document
          const iframe = document.createElement('iframe')
          iframe.style.position = 'fixed'
          iframe.style.left = '0'
          iframe.style.top = '0'
          iframe.style.width = '210mm'
          iframe.style.height = '297mm'
          iframe.style.border = 'none'
          iframe.style.opacity = '0'
          iframe.style.pointerEvents = 'none'
          document.body.appendChild(iframe)
          
          // Write HTML to iframe
          const iframeDoc = iframe.contentDocument || iframe.contentWindow.document
          iframeDoc.open()
          iframeDoc.write(html)
          iframeDoc.close()
          
          // Wait for iframe to fully load and render
          await new Promise((resolve) => {
            const checkReady = () => {
              try {
                const body = iframeDoc.body
                if (body && body.children.length > 0 && body.offsetHeight > 0) {
                  resolve()
                } else {
                  setTimeout(checkReady, 100)
                }
              } catch (e) {
                setTimeout(resolve, 2000)
              }
            }
            iframe.onload = () => setTimeout(checkReady, 500)
            setTimeout(checkReady, 500)
          })
          
          // Get the body element from iframe
          const iframeBody = iframeDoc.body
          
          if (!iframeBody) {
            throw new Error('Could not access iframe body')
          }
          
          // Wait for styles and images to fully load
          await new Promise(resolve => setTimeout(resolve, 1500))
          
          // Configure html2pdf options
          const opt = {
            margin: [10, 10, 10, 10],
            filename: pdfFilename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
              scale: 2,
              useCORS: true,
              logging: false,
              letterRendering: true,
              windowWidth: iframeBody.scrollWidth || 800,
              windowHeight: iframeBody.scrollHeight || 600,
              allowTaint: true,
              backgroundColor: '#ffffff'
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
          }
          
          // Generate and download PDF from iframe body
          await window.html2pdf().set(opt).from(iframeBody).save()
          
          // Clean up iframe
          setTimeout(() => {
            if (iframe.parentNode) {
              document.body.removeChild(iframe)
            }
          }, 1000)
        } catch (error) {
          console.error('Error converting HTML to PDF:', error)
          alert('Failed to generate PDF: ' + error.message + '. The HTML file will be downloaded instead.')
          // Fallback: download as HTML
          const blob = new Blob([html], { type: 'text/html' })
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement('a')
          link.href = url
          link.setAttribute('download', pdfFilename.replace('.pdf', '.html'))
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
        }
      }
    }
  } catch (err) {
    console.error('Error downloading tenant profile PDF:', err)
    alert('Failed to download PDF. Please try again.')
  }
}

// Approve application
const approveApplication = async () => {
  if (!selectedApplication.value || !selectedApplication.value.id) return
  
  isUpdatingStatus.value = true
  try {
    const response = await api.put(`/tenant-applications/${selectedApplication.value.id}/approve`)
    if (response.data.success) {
      // Close the modal first
      closeApplicationModal()
      // Longer delay to ensure database is committed and statistics are updated
      await new Promise(resolve => setTimeout(resolve, 300))
      // Refresh the tenants list to get updated statistics
      await fetchTenants()
      alert('Application approved successfully! Tenant has been assigned to the unit.')
    } else {
      alert(response.data.message || 'Failed to approve application.')
    }
  } catch (err) {
    console.error('Error approving application:', err)
    alert(err.response?.data?.message || 'Failed to approve application. Please try again.')
  } finally {
    isUpdatingStatus.value = false
  }
}

// Reject application
const rejectApplication = async () => {
  if (!selectedApplication.value || !selectedApplication.value.id) return
  
  if (!confirm('Are you sure you want to reject this application?')) {
    return
  }
  
  isUpdatingStatus.value = true
  try {
    const response = await api.put(`/tenant-applications/${selectedApplication.value.id}/reject`)
    if (response.data.success) {
      // Close the modal
      closeApplicationModal()
      // Refresh the tenants list to get updated statistics
      await fetchTenants()
      alert('Application rejected.')
    } else {
      alert(response.data.message || 'Failed to reject application.')
    }
  } catch (err) {
    console.error('Error rejecting application:', err)
    alert(err.response?.data?.message || 'Failed to reject application. Please try again.')
  } finally {
    isUpdatingStatus.value = false
  }
}

const toggleDropdown = (tenantId, event) => {
  if (openDropdownId.value === tenantId) {
    openDropdownId.value = null
  } else {
    openDropdownId.value = tenantId
    // Calculate position immediately
    if (event && event.currentTarget) {
      const buttonRect = event.currentTarget.getBoundingClientRect()
      dropdownPosition.value = {
        top: buttonRect.bottom + 4,
        right: window.innerWidth - buttonRect.right
      }
    }
  }
}

const handleDeleteTenant = async (tenant) => {
  if (!tenant.id || tenant.type === 'application') {
    alert('Cannot delete tenant applications. Please reject them instead.')
    return
  }

  if (!confirm(`Are you sure you want to remove ${tenant.name} from this unit? This action cannot be undone.`)) {
    return
  }

  try {
    const response = await api.delete(`/tenants/${tenant.id}`)
    
    if (response.data.success) {
      alert('Tenant removed successfully.')
      // Refresh the tenants list
      await fetchTenants()
    } else {
      alert(response.data.message || 'Failed to remove tenant.')
    }
  } catch (err) {
    console.error('Error deleting tenant:', err)
    alert(err.response?.data?.message || 'Failed to remove tenant. Please try again.')
  }
  
  openDropdownId.value = null
}

const handleGenerateAccount = async (tenant) => {
  if (!tenant.id || tenant.type === 'application') {
    alert('Cannot generate account for tenant applications. Please approve them first.')
    return
  }
  
  if (!confirm(`Generate login account for ${tenant.name}? A new password will be created.`)) {
    return
  }
  
  try {
    const response = await api.post(`/tenants/${tenant.id}/generate-account`)
    if (response.data.success) {
      const credentials = response.data.credentials
      const message = `Account credentials generated successfully!\n\nEmail: ${credentials.email}\nPassword: ${credentials.password}\n\nPlease share these credentials with the tenant.`
      alert(message)
      openDropdownId.value = null
    } else {
      alert(response.data.message || 'Failed to generate account.')
    }
  } catch (err) {
    console.error('Error generating account:', err)
    alert(err.response?.data?.message || 'Failed to generate account. Please try again.')
  }
}

const handleInviteTenant = () => {
  // Open invite tenant modal or navigate to invite page
  console.log('Invite tenant')
}

// Format currency for display
const formatCurrencyDisplay = (amount) => {
  if (!amount) return '₱0'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const getUnitTypeLabel = (type) => {
  const typeMap = {
    'studio': 'Studio',
    'apartment': 'Apartment',
    '1br': '1 Bedroom',
    '2br': '2 Bedroom',
    '3br': '3 Bedroom',
    '4br': '4+ Bedroom',
    'penthouse': 'Penthouse'
  }
  return typeMap[type] || type
}

const getAmenityLabel = (amenity) => {
  const amenityMap = {
    'parking': 'Parking',
    'air_conditioning': 'Air Conditioning',
    'heating': 'Heating',
    'washer_dryer': 'Washer/Dryer',
    'dishwasher': 'Dishwasher',
    'balcony': 'Balcony',
    'gym': 'Gym Access',
    'pool': 'Pool Access',
    'elevator': 'Elevator',
    'pet_friendly': 'Pet Friendly',
    'furnished': 'Furnished',
    'wifi': 'WiFi Included'
  }
  return amenityMap[amenity] || amenity.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatLeaseDuration = (months) => {
  if (!months) return 'N/A'
  const durationMap = {
    1: '1 Month',
    3: '3 Months',
    6: '6 Months',
    12: '1 Year',
    24: '2 Years'
  }
  return durationMap[months] || `${months} Month${months > 1 ? 's' : ''}`
}

const calculateLeaseRemaining = (leaseEnd) => {
  if (!leaseEnd) return 'N/A'
  const endDate = new Date(leaseEnd)
  const today = new Date()
  const diffTime = endDate - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'Expired'
  } else if (diffDays < 30) {
    return `${diffDays} days remaining`
  } else if (diffDays < 365) {
    const months = Math.floor(diffDays / 30)
    return `${months} month${months !== 1 ? 's' : ''} remaining`
  } else {
    const years = Math.floor(diffDays / 365)
    const months = Math.floor((diffDays % 365) / 30)
    if (months > 0) {
      return `${years} year${years !== 1 ? 's' : ''}, ${months} month${months !== 1 ? 's' : ''} remaining`
    }
    return `${years} year${years !== 1 ? 's' : ''} remaining`
  }
}

// Get image URL
const handleAvatarError = (event) => {
  // Hide the image and show the initial letter instead
  event.target.style.display = 'none'
  const avatar = event.target.closest('.tenant-avatar')
  if (avatar) {
    const initial = avatar.querySelector('.tenant-avatar-initial')
    if (initial) {
      initial.style.display = 'flex'
    } else {
      // Create initial if it doesn't exist
      const tenantName = event.target.alt || 'T'
      const initialSpan = document.createElement('span')
      initialSpan.className = 'tenant-avatar-initial'
      initialSpan.textContent = tenantName.charAt(0).toUpperCase()
      avatar.appendChild(initialSpan)
    }
  }
}

const getImageUrl = (imagePath) => {
  if (!imagePath) return ''
  
  // If it's already a full URL, return as is
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  
  // If it already starts with /storage/, return as is
  if (imagePath.startsWith('/storage/')) {
    return imagePath
  }
  
  // If it starts with storage/ (without leading slash), add leading slash
  if (imagePath.startsWith('storage/')) {
    return '/' + imagePath
  }
  
  // Otherwise, prepend /storage/
  return '/storage/' + imagePath
}

// Handle image load error
const handleImageError = (event) => {
  console.error('Failed to load image:', event.target.src)
  // Optionally show placeholder on error
  event.target.style.display = 'none'
  const container = event.target.closest('.id-picture-wrapper')
  if (container) {
    container.innerHTML = `
      <div class="id-picture-placeholder">
        <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="placeholder-text">ID Picture</span>
      </div>
    `
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.dropdown-container') && !event.target.closest('.dropdown-menu-fixed')) {
    openDropdownId.value = null
  }
}

onMounted(() => {
  fetchTenants()
  document.addEventListener('click', handleClickOutside)
})

onActivated(() => {
  fetchTenants()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Page Title -->
      <h1 class="page-title">Tenants</h1>

      <!-- Statistics Cards -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value">{{ statistics.total_tenants }}</div>
          <div class="stat-label">Total Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.active_leases }}</div>
          <div class="stat-label">Active Leases</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ statistics.pending_invites }}</div>
          <div class="stat-label">Pending Invites</div>
        </div>
      </div>

      <!-- Search and Filter Section -->
      <div class="filters-section">
        <div class="search-container">
          <MagnifyingGlassIcon class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search tenants by name, email, or property..."
            @input="resetToFirstPage"
          />
        </div>
        <div class="filters-group">
          <div class="filter-container">
            <select v-model="statusFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div class="filter-container">
            <select v-model="propertyFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Properties</option>
              <option v-for="property in properties" :key="property" :value="property">
                {{ property }}
              </option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <button class="invite-btn" @click="handleInviteTenant">
            <EnvelopeIcon class="invite-icon" />
            <span>Invite Tenant</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading tenants...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchTenants">Retry</button>
      </div>

      <!-- Tenants Table -->
      <div v-else-if="paginatedTenants.length > 0" class="table-container">
        <table class="tenants-table">
          <thead>
            <tr>
              <th>Tenant</th>
              <th>Property</th>
              <th>Unit</th>
              <th>Rent</th>
              <th>Lease Start</th>
              <th>Lease End</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tenant in paginatedTenants" :key="tenant.id">
              <td class="tenant-cell">
                <div class="tenant-info">
                  <div class="tenant-avatar">
                    <img 
                      v-if="tenant.profile_picture" 
                      :src="getImageUrl(tenant.profile_picture)" 
                      :alt="tenant.name"
                      class="tenant-avatar-img"
                      @error="handleAvatarError"
                    />
                    <span v-else class="tenant-avatar-initial">
                      {{ tenant.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div class="tenant-details">
                    <div class="tenant-name">{{ tenant.name }}</div>
                    <div class="tenant-email">{{ tenant.email }}</div>
                  </div>
                </div>
              </td>
              <td>{{ tenant.property_name }}</td>
              <td>{{ tenant.unit_number }}</td>
              <td>{{ formatCurrency(tenant.rent) }} /month</td>
              <td>{{ formatLeaseStart(tenant.lease_start, tenant.type) }}</td>
              <td>{{ formatLeaseEnd(tenant.lease_end, tenant.type) }}</td>
              <td>
                <span :class="['status-badge', getStatusClass(tenant.status)]">
                  {{ tenant.status.charAt(0).toUpperCase() + tenant.status.slice(1) }}
                </span>
              </td>
              <td class="actions-cell">
                <div class="actions-group">
                  <button class="action-btn" @click="handleViewTenant(tenant)" title="View Details">
                    <EyeIcon class="action-icon" />
                  </button>
                  <!-- Only show More Options for non-pending applications -->
                  <template v-if="tenant.status !== 'pending' || tenant.type !== 'application'">
                    <div class="dropdown-container">
                      <button 
                        class="action-btn" 
                        @click.stop="toggleDropdown(tenant.id || `app-${tenant.application_id}`, $event)"
                        title="More Options"
                      >
                        <EllipsisVerticalIcon class="action-icon" />
                      </button>
                    </div>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <p>No tenants found. {{ searchQuery || statusFilter !== 'All' || propertyFilter !== 'All' ? 'Try adjusting your filters.' : 'Invite tenants to get started.' }}</p>
      </div>

      <!-- Dropdown Menu (Fixed Position - Outside Card) -->
      <div 
        v-if="openDropdownId"
        class="dropdown-menu-fixed"
        :style="{ top: dropdownPosition.top + 'px', right: dropdownPosition.right + 'px' }"
        @click.stop
      >
        <button 
          v-if="paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId)?.id && paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId)?.type === 'tenant'"
          class="dropdown-item"
          @click="handleGenerateAccount(paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId))"
        >
          <KeyIcon class="dropdown-icon" />
          <span>Generate Account</span>
        </button>
        <button 
          v-if="paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId)?.id && paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId)?.type === 'tenant'"
          class="dropdown-item delete-item"
          @click="handleDeleteTenant(paginatedTenants.find(t => (t.id || `app-${t.application_id}`) === openDropdownId))"
        >
          <TrashIcon class="dropdown-icon" />
          <span>Delete</span>
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="!isLoading && !error && filteredTenants.length > 0" class="pagination-container">
        <div class="pagination">
          <button 
            class="pagination-btn" 
            :disabled="currentPage === 1"
            @click="goToPreviousPage"
          >
            <ChevronLeftIcon class="chevron-pagination-icon" />
          </button>
          <button 
            v-for="page in totalPages" 
            :key="page"
            :class="['page-number', { active: currentPage === page }]"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <button 
            class="pagination-btn" 
            :disabled="currentPage === totalPages"
            @click="goToNextPage"
          >
            <ChevronRightIcon class="chevron-pagination-icon" />
          </button>
        </div>
        <div class="pagination-info">
          Showing {{ paginationInfo.start }} to {{ paginationInfo.end }} of {{ paginationInfo.total }} results
        </div>
      </div>

      <!-- Application Details Modal -->
      <div v-if="showApplicationModal" class="modal-overlay" @click.self="closeApplicationModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">Tenant Application Details</h2>
            <button class="modal-close-btn" @click="closeApplicationModal">
              <XMarkIcon class="close-icon" />
            </button>
          </div>

          <div v-if="isLoadingApplication" class="modal-loading">
            <p>Loading application details...</p>
          </div>

          <div v-else-if="selectedApplication" class="modal-content">
            <!-- Profile Picture -->
            <div class="detail-section">
              <h3 class="section-title">Profile Picture</h3>
              <div class="id-picture-container">
                <div v-if="selectedApplication.profile_picture" class="id-picture-wrapper">
                  <img 
                    :src="getImageUrl(selectedApplication.profile_picture)" 
                    alt="Profile Picture" 
                    class="id-picture-image"
                    @error="handleImageError"
                  />
                </div>
                <div v-else class="id-picture-placeholder">
                  <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="placeholder-text">Profile Picture</span>
                </div>
              </div>
            </div>

            <!-- ID Picture -->
            <div class="detail-section">
              <h3 class="section-title">ID Picture</h3>
              <div class="id-picture-container">
                <div v-if="selectedApplication.id_picture" class="id-picture-wrapper">
                  <img 
                    :src="getImageUrl(selectedApplication.id_picture)" 
                    alt="ID Picture" 
                    class="id-picture-image"
                    @error="handleImageError"
                  />
                </div>
                <div v-else class="id-picture-placeholder">
                  <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="placeholder-text">ID Picture</span>
                </div>
              </div>
            </div>

            <!-- Personal Information -->
            <div class="detail-section">
              <h3 class="section-title">Personal Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Full Name</label>
                  <p class="detail-value">{{ selectedApplication.name }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.email }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">WhatsApp</label>
                  <p class="detail-value">{{ selectedApplication.whatsapp }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Occupation</label>
                  <p class="detail-value">{{ selectedApplication.occupation }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Monthly Income</label>
                  <p class="detail-value">{{ formatCurrencyDisplay(selectedApplication.monthly_income) }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Number of People</label>
                  <p class="detail-value">{{ selectedApplication.number_of_people }}</p>
                </div>
                <div v-if="selectedApplication.lease_duration_months" class="detail-item">
                  <label class="detail-label">Lease Duration</label>
                  <p class="detail-value">{{ formatLeaseDuration(selectedApplication.lease_duration_months) }}</p>
                </div>
                <div v-if="selectedApplication.lease_start_date" class="detail-item">
                  <label class="detail-label">Lease Start Date</label>
                  <p class="detail-value">{{ formatDate(selectedApplication.lease_start_date) }}</p>
                </div>
              </div>
            </div>

            <!-- Reference 1 Information -->
            <div class="detail-section">
              <h3 class="section-title">Reference 1</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Relationship</label>
                  <p class="detail-value">{{ selectedApplication.reference1_relationship || 'N/A' }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedApplication.reference1_name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.reference1_address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.reference1_phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.reference1_email }}</p>
                </div>
              </div>
            </div>

            <!-- Reference 2 Information -->
            <div class="detail-section">
              <h3 class="section-title">Reference 2</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Relationship</label>
                  <p class="detail-value">{{ selectedApplication.reference2_relationship || 'N/A' }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedApplication.reference2_name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedApplication.reference2_address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedApplication.reference2_phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedApplication.reference2_email }}</p>
                </div>
              </div>
            </div>

            <!-- Application Status -->
            <div class="detail-section">
              <h3 class="section-title">Application Status</h3>
              <div class="status-display">
                <span :class="['status-badge', getStatusClass(selectedApplication.status)]">
                  {{ selectedApplication.status.charAt(0).toUpperCase() + selectedApplication.status.slice(1) }}
                </span>
              </div>
            </div>

            <!-- Action Buttons (only for pending applications) -->
            <div v-if="selectedApplication.status === 'pending'" class="detail-section">
              <div class="modal-actions">
                <button 
                  class="btn btn-approve" 
                  @click="approveApplication"
                  :disabled="isUpdatingStatus"
                >
                  {{ isUpdatingStatus ? 'Processing...' : 'Approve' }}
                </button>
                <button 
                  class="btn btn-reject" 
                  @click="rejectApplication"
                  :disabled="isUpdatingStatus"
                >
                  {{ isUpdatingStatus ? 'Processing...' : 'Reject' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tenant Details Modal -->
      <div v-if="showTenantModal" class="modal-overlay" @click.self="closeTenantModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">Tenant Details</h2>
            <div class="modal-header-actions">
              <button class="download-pdf-btn" @click="downloadTenantProfilePdf" title="Download PDF">
                <ArrowDownTrayIcon class="download-icon" />
                <span>Download PDF</span>
              </button>
              <button class="modal-close-btn" @click="closeTenantModal">
                <XMarkIcon class="close-icon" />
              </button>
            </div>
          </div>

          <div v-if="isLoadingTenant" class="modal-loading">
            <p>Loading tenant details...</p>
          </div>

          <div v-else-if="error && !selectedTenant" class="modal-error">
            <p class="error-text">{{ error }}</p>
            <button class="retry-btn" @click="closeTenantModal">Close</button>
          </div>

          <div v-else-if="selectedTenant" class="modal-content">
            <!-- Profile Picture -->
            <div v-if="selectedTenant.application?.profile_picture" class="detail-section">
              <h3 class="section-title">Profile Picture</h3>
              <div class="id-picture-container">
                <div class="id-picture-wrapper">
                  <img 
                    :src="getImageUrl(selectedTenant.application.profile_picture)" 
                    alt="Profile Picture" 
                    class="id-picture-image"
                    @error="handleImageError"
                  />
                </div>
              </div>
            </div>

            <!-- ID Picture -->
            <div v-if="selectedTenant.application?.id_picture" class="detail-section">
              <h3 class="section-title">ID Picture</h3>
              <div class="id-picture-container">
                <div class="id-picture-wrapper">
                  <img 
                    :src="getImageUrl(selectedTenant.application.id_picture)" 
                    alt="ID Picture" 
                    class="id-picture-image"
                    @error="handleImageError"
                  />
                </div>
              </div>
            </div>

            <!-- Tenant Information -->
            <div class="detail-section">
              <h3 class="section-title">Personal Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Full Name</label>
                  <p class="detail-value">{{ selectedTenant.name }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedTenant.email }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedTenant.phone || 'N/A' }}</p>
                </div>
                <div v-if="selectedTenant.application?.whatsapp" class="detail-item">
                  <label class="detail-label">WhatsApp</label>
                  <p class="detail-value">{{ selectedTenant.application.whatsapp }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedTenant.address || 'N/A' }}</p>
                </div>
                <div v-if="selectedTenant.application?.occupation" class="detail-item">
                  <label class="detail-label">Occupation</label>
                  <p class="detail-value">{{ selectedTenant.application.occupation }}</p>
                </div>
                <div v-if="selectedTenant.application?.monthly_income" class="detail-item">
                  <label class="detail-label">Monthly Income</label>
                  <p class="detail-value">{{ formatCurrencyDisplay(selectedTenant.application.monthly_income) }}</p>
                </div>
                <div v-if="selectedTenant.application?.number_of_people" class="detail-item">
                  <label class="detail-label">Number of People</label>
                  <p class="detail-value">{{ selectedTenant.application.number_of_people }}</p>
                </div>
                <div v-if="selectedTenant.application?.lease_duration_months" class="detail-item">
                  <label class="detail-label">Lease Duration</label>
                  <p class="detail-value">{{ formatLeaseDuration(selectedTenant.application.lease_duration_months) }}</p>
                </div>
              </div>
            </div>

            <!-- Reference 1 Information -->
            <div v-if="selectedTenant.application?.reference1" class="detail-section">
              <h3 class="section-title">Reference 1</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Relationship</label>
                  <p class="detail-value">{{ selectedTenant.application.reference1.relationship || 'N/A' }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedTenant.application.reference1.name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedTenant.application.reference1.address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedTenant.application.reference1.phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedTenant.application.reference1.email }}</p>
                </div>
              </div>
            </div>

            <!-- Reference 2 Information -->
            <div v-if="selectedTenant.application?.reference2" class="detail-section">
              <h3 class="section-title">Reference 2</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label class="detail-label">Relationship</label>
                  <p class="detail-value">{{ selectedTenant.application.reference2.relationship || 'N/A' }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Name</label>
                  <p class="detail-value">{{ selectedTenant.application.reference2.name }}</p>
                </div>
                <div class="detail-item full-width">
                  <label class="detail-label">Address</label>
                  <p class="detail-value">{{ selectedTenant.application.reference2.address }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Phone Number</label>
                  <p class="detail-value">{{ selectedTenant.application.reference2.phone }}</p>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Email</label>
                  <p class="detail-value">{{ selectedTenant.application.reference2.email }}</p>
                </div>
              </div>
            </div>

            <!-- Unit Information -->
            <div v-if="selectedTenant.units && selectedTenant.units.length > 0" class="detail-section">
              <h3 class="section-title">Applied Unit</h3>
              <div class="units-list">
                <div v-for="unit in selectedTenant.units" :key="unit.id" class="unit-item">
                  <div class="unit-info">
                    <div class="unit-header-info">
                      <div class="unit-title-section">
                        <h4 class="unit-name">{{ unit.property_name }} - Unit {{ unit.unit_number }}</h4>
                        <p v-if="unit.property_address" class="unit-property-address">{{ unit.property_address }}</p>
                      </div>
                      <span :class="['status-badge', unit.status === 'active' ? 'status-active' : 'status-inactive']">
                        {{ unit.status.charAt(0).toUpperCase() + unit.status.slice(1) }}
                      </span>
                    </div>
                    
                    <div class="unit-details-grid">
                      <div v-if="unit.unit_type" class="unit-detail-item">
                        <label class="unit-detail-label">Unit Type:</label>
                        <span class="unit-detail-value">{{ getUnitTypeLabel(unit.unit_type) }}</span>
                      </div>
                      <div v-if="unit.bedrooms !== null && unit.bedrooms !== undefined" class="unit-detail-item">
                        <label class="unit-detail-label">Bedrooms:</label>
                        <span class="unit-detail-value">{{ unit.bedrooms }}</span>
                      </div>
                      <div v-if="unit.bathrooms !== null && unit.bathrooms !== undefined" class="unit-detail-item">
                        <label class="unit-detail-label">Bathrooms:</label>
                        <span class="unit-detail-value">{{ unit.bathrooms }}</span>
                      </div>
                      <div v-if="unit.square_footage" class="unit-detail-item">
                        <label class="unit-detail-label">Square Footage:</label>
                        <span class="unit-detail-value">{{ unit.square_footage }} sq ft</span>
                      </div>
                      <div class="unit-detail-item">
                        <label class="unit-detail-label">Monthly Rent:</label>
                        <span class="unit-detail-value highlight">{{ formatCurrencyDisplay(unit.monthly_rent) }}</span>
                      </div>
                      <div v-if="unit.security_deposit" class="unit-detail-item">
                        <label class="unit-detail-label">Security Deposit:</label>
                        <span class="unit-detail-value">{{ formatCurrencyDisplay(unit.security_deposit) }}</span>
                      </div>
                      <div class="unit-detail-item">
                        <label class="unit-detail-label">Lease Start:</label>
                        <span class="unit-detail-value">{{ unit.lease_start ? formatDate(unit.lease_start) : 'N/A' }}</span>
                      </div>
                      <div class="unit-detail-item">
                        <label class="unit-detail-label">Lease End:</label>
                        <span class="unit-detail-value">{{ unit.lease_end ? formatDate(unit.lease_end) : 'N/A' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="detail-section">
              <h3 class="section-title">Applied Unit</h3>
              <div class="empty-units">
                <p>No unit information available.</p>
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
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 32px 0;
}

/* Statistics Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

/* Filters Section */
.filters-section {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  align-items: center;
}

.search-container {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 14px;
  width: 20px;
  height: 20px;
  color: #999;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  outline: none;
  transition: border-color 0.2s;
}

.search-input::placeholder {
  color: #999;
}

.search-input:hover {
  border-color: #bbb;
}

.search-input:focus {
  border-color: #1500FF;
}

.filters-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filter-container {
  position: relative;
  display: inline-block;
}

.filter-dropdown {
  appearance: none;
  padding: 12px 40px 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  min-width: 140px;
  outline: none;
  transition: border-color 0.2s;
}

.filter-dropdown:hover {
  border-color: #bbb;
}

.filter-dropdown:focus {
  border-color: #1500FF;
}

.chevron-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #666;
  pointer-events: none;
}

.invite-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.invite-btn:hover {
  background: #1200e6;
}

.invite-icon {
  width: 20px;
  height: 20px;
}

/* Table */
.table-container {
  background: white;
  border-radius: 12px;
  overflow-x: auto;
  overflow-y: visible;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
  position: relative;
}

.tenants-table {
  width: 100%;
  border-collapse: collapse;
  position: relative;
}

.tenants-table thead {
  background: #f9f9f9;
}

.tenants-table th {
  padding: 16px;
  text-align: left;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #e5e5e5;
}

.tenants-table td {
  padding: 16px;
  border-bottom: 1px solid #e5e5e5;
  font-size: 14px;
  color: #333;
  position: relative;
  overflow: visible;
}

.tenants-table tbody tr:hover {
  background: #f9f9f9;
}

.tenants-table tbody tr:last-child td {
  border-bottom: none;
}

/* Tenant Cell */
.tenant-cell {
  min-width: 250px;
}

.tenant-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tenant-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #1500FF;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}

.tenant-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.tenant-avatar-initial {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
  background: #1500FF;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
  flex-shrink: 0;
}

.tenant-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tenant-name {
  font-weight: 600;
  color: #1a1a1a;
}

.tenant-email {
  font-size: 12px;
  color: #666;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

/* Actions */
.actions-cell {
  width: 120px;
}

.actions-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
  color: #666;
}

.action-btn:hover {
  background: #f0f0f0;
  color: #1500FF;
}

.action-icon {
  width: 18px;
  height: 18px;
}

/* Dropdown Menu */
.dropdown-container {
  position: relative;
  z-index: 1;
}

.dropdown-menu-fixed {
  position: fixed;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 150px;
  z-index: 10000;
  overflow: hidden;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
  color: #374151;
}

.dropdown-item:hover {
  background: #f3f4f6;
}

.dropdown-item.delete-item {
  color: #dc2626;
}

.dropdown-item.delete-item:hover {
  background: #fee2e2;
  color: #991b1b;
}

.dropdown-icon {
  width: 16px;
  height: 16px;
}

/* Pagination */
.pagination-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #f5f5f5;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #e5e5e5;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chevron-pagination-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.page-number {
  min-width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.page-number:hover {
  background: #f5f5f5;
}

.page-number.active {
  background: #1500FF;
  color: white;
  font-weight: 600;
}

.pagination-info {
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  color: #666;
}

/* Loading, Error, Empty States */
.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.loading-state p,
.error-state p,
.empty-state p {
  font-size: 16px;
  color: #666;
  margin-bottom: 16px;
}

.retry-btn {
  padding: 10px 24px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-btn:hover {
  background: #1200e6;
}

@media (max-width: 1200px) {
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e5e5;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.modal-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.download-pdf-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
}

.download-pdf-btn:hover {
  background: #1200cc;
  transform: translateY(-1px);
}

.download-icon {
  width: 18px;
  height: 18px;
}

.modal-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #f5f5f5;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.modal-close-btn:hover {
  background: #e5e5e5;
}

.close-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.modal-loading {
  padding: 60px 24px;
  text-align: center;
  color: #666;
}

.modal-error {
  padding: 40px;
  text-align: center;
}

.modal-error .error-text {
  color: #dc2626;
  margin-bottom: 16px;
  font-size: 16px;
}

.modal-error .retry-btn {
  padding: 8px 16px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 14px;
}

.modal-error .retry-btn:hover {
  background: #1200e6;
}

.modal-content {
  padding: 24px;
}

.detail-section {
  margin-bottom: 32px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section .section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e5e5;
}

.id-picture-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 20px;
  background: #f9f9f9;
  border-radius: 8px;
  min-height: 200px;
}

.id-picture-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.id-picture-image {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
  object-fit: contain;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.id-picture-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #999;
}

.placeholder-icon {
  width: 64px;
  height: 64px;
  color: #ccc;
}

.placeholder-text {
  font-size: 14px;
  color: #999;
  font-weight: 500;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 14px;
  color: #1a1a1a;
  margin: 0;
  word-break: break-word;
}

.status-display {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  padding-top: 8px;
}

.btn {
  padding: 12px 32px;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-approve {
  background: #10b981;
  color: white;
}

.btn-approve:hover:not(:disabled) {
  background: #059669;
}

.btn-reject {
  background: #ef4444;
  color: white;
}

.btn-reject:hover:not(:disabled) {
  background: #dc2626;
}

/* Tenant Modal Specific Styles */
.units-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.unit-item {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e5e7eb;
}

.unit-header-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.unit-title-section {
  flex: 1;
}

.unit-name {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 4px 0;
}

.unit-property-address {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

.unit-specs-section,
.unit-financial-section,
.unit-lease-section,
.unit-amenities-section,
.unit-description-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.unit-specs-section:last-child,
.unit-financial-section:last-child,
.unit-lease-section:last-child,
.unit-amenities-section:last-child,
.unit-description-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.unit-section-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px 0;
}

.unit-specs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.unit-spec-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.unit-spec-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.unit-spec-value {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.unit-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.unit-detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.unit-detail-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.unit-detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.unit-detail-value.highlight {
  font-size: 16px;
  font-weight: 700;
  color: #1500FF;
}

.amenities-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.amenity-tag {
  padding: 6px 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
}

.unit-description-text {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}

.empty-units {
  text-align: center;
  padding: 24px;
  color: #9ca3af;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 20px;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-group {
    flex-wrap: wrap;
  }

  .stats-container {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: auto;
  }

  .modal-container {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
