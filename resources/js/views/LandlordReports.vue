<script setup>
import { ref, computed, onMounted, onActivated, onUnmounted } from 'vue'
import { Teleport } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import { MagnifyingGlassIcon, CalendarIcon, ChevronLeftIcon, ChevronRightIcon} from '@heroicons/vue/24/outline'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'
import { ArrowDownTrayIcon } from '@heroicons/vue/24/solid'
import api from '../services/api.js'

const reportData = ref([])
const propertiesData = ref([])
const unitsData = ref([])
const tenantsData = ref([])
const statistics = ref({
  total_tenants: 0,
  paid_tenants: 0,
  unpaid_tenants: 0,
  total_rent: 0,
  total_paid: 0,
  total_unpaid: 0
})
const displayStatistics = computed(() => {
  const base = statistics.value

  // When viewing Payment report filtered by Utilities, total rent should be 0
  if (viewType.value === 'Payment' && paymentTypeFilter.value === 'utility') {
    return {
      ...base,
      total_rent: 0
    }
  }

  return base
})
const isLoading = ref(false)
const error = ref(null)
const filterDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM format for Payment
const propertiesFilterDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM format for Properties
const unitsFilterDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM format for Units
const tenantsFilterDate = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM format for Tenants
const searchQuery = ref('')
const paymentTypeFilter = ref('rent') // Default to 'rent', no 'All' option
const propertyTypeFilter = ref('All') // 'All', 'residential', 'commercial'
const unitStatusFilter = ref('All') // 'All', 'Occupied', 'Available' - for Units view
const viewType = ref('Payment') // 'Properties', 'Units', 'Tenants', 'Payment'
const currentPage = ref(1)
const itemsPerPage = ref(10)
const showDownloadDropdown = ref(false)
const downloadDropdownPosition = ref({ top: 0, left: 0 })

// Fetch report data
const fetchReport = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/reports', {
      params: {
        date: filterDate.value
      }
    })
    
    if (response.data.success) {
      reportData.value = response.data.report || []
      
      if (response.data.statistics) {
        statistics.value = {
          total_tenants: response.data.statistics.total_tenants || 0,
          paid_tenants: response.data.statistics.paid_tenants || 0,
          unpaid_tenants: response.data.statistics.unpaid_tenants || 0,
          total_rent: response.data.statistics.total_rent || 0,
          total_paid: response.data.statistics.total_paid || 0,
          total_unpaid: response.data.statistics.total_unpaid || 0
        }
      }
      
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching report:', err)
    error.value = 'Failed to load report. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Fetch properties
const fetchProperties = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/properties')
    
    if (response.data.success) {
      propertiesData.value = response.data.properties || []
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching properties:', err)
    error.value = 'Failed to load properties. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Fetch all units with tenant info
const fetchUnits = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const propertiesResponse = await api.get('/properties')
    
    if (propertiesResponse.data.success) {
      const properties = propertiesResponse.data.properties || []
      const allUnits = []
      
      // Fetch units for each property
      for (const property of properties) {
        try {
          const unitsResponse = await api.get(`/properties/${property.id}/units`)
          const units = unitsResponse.data.units || []
          
          units.forEach(unit => {
            allUnits.push({
              id: unit.id,
              unit_number: unit.unit_number,
              unit_type: unit.unit_type,
              monthly_rent: unit.monthly_rent,
              status: unit.status,
              is_occupied: unit.is_occupied,
              property_id: property.id,
              property_name: property.name,
              property_type: property.type, // Store property type for filtering
              tenant: unit.tenant ? {
                id: unit.tenant.id,
                name: unit.tenant.name,
                email: unit.tenant.email,
                phone: unit.tenant.phone
              } : null
            })
          })
        } catch (err) {
          console.error(`Error fetching units for property ${property.id}:`, err)
        }
      }
      
      unitsData.value = allUnits
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching units:', err)
    error.value = 'Failed to load units. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Fetch all tenants
const fetchTenants = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await api.get('/tenants')
    
    if (response.data.success) {
      tenantsData.value = response.data.tenants || []
      currentPage.value = 1
    }
  } catch (err) {
    console.error('Error fetching tenants:', err)
    error.value = 'Failed to load tenants. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Handle view type change
const handleViewTypeChange = () => {
  currentPage.value = 1
  searchQuery.value = ''
  propertyTypeFilter.value = 'All' // Reset property type filter when switching views
  unitStatusFilter.value = 'All' // Reset unit status filter when switching views
  paymentTypeFilter.value = 'rent' // Reset payment type filter to 'rent' when switching views
  
  if (viewType.value === 'Payment') {
    fetchReport()
  } else if (viewType.value === 'Properties') {
    fetchProperties()
  } else if (viewType.value === 'Units') {
    fetchUnits()
  } else if (viewType.value === 'Tenants') {
    fetchTenants()
  }
}

// Filter report data
const filteredReport = computed(() => {
  let filtered = reportData.value
  
  // Filter by payment type (always filter, no 'All' option)
  filtered = filtered.filter((item) => {
    if (paymentTypeFilter.value === 'rent') {
      return !item.payment_type || item.payment_type === 'rent'
    } else if (paymentTypeFilter.value === 'utility') {
      return item.payment_type === 'utility'
    }
    return true
  })
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((item) => {
      const name = (item.tenant_name || '').toLowerCase()
      const email = (item.tenant_email || '').toLowerCase()
      const property = (item.property_name || '').toLowerCase()
      const unit = (item.unit_number || '').toLowerCase()
      return name.includes(q) || email.includes(q) || property.includes(q) || unit.includes(q)
    })
  }
  
  return filtered
})

// Filter properties data
const filteredProperties = computed(() => {
  let filtered = propertiesData.value
  
  // Filter by property type
  if (propertyTypeFilter.value !== 'All') {
    filtered = filtered.filter((property) => {
      return property.type === propertyTypeFilter.value
    })
  }
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((property) => {
      const name = (property.name || '').toLowerCase()
      const location = ((property.street_address || '') + ' ' + (property.city || '') + ' ' + (property.state || '')).toLowerCase()
      return name.includes(q) || location.includes(q)
    })
  }
  
  return filtered
})

// Filter units data
const filteredUnits = computed(() => {
  let filtered = unitsData.value
  
  // Filter by status (Occupied/Available)
  if (unitStatusFilter.value !== 'All') {
    filtered = filtered.filter((unit) => {
      // Handle both boolean and numeric values (true/1 for occupied, false/0 for available)
      const isOccupied = unit.is_occupied === true || unit.is_occupied === 1 || unit.is_occupied === '1'
      
      if (unitStatusFilter.value === 'Occupied') {
        return isOccupied
      } else if (unitStatusFilter.value === 'Available') {
        return !isOccupied
      }
      return true
    })
  }
  
  // Filter by search query
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    filtered = filtered.filter((unit) => {
      const unitNumber = (unit.unit_number || '').toLowerCase()
      const propertyName = (unit.property_name || '').toLowerCase()
      const tenantName = (unit.tenant?.name || '').toLowerCase()
      return unitNumber.includes(q) || propertyName.includes(q) || tenantName.includes(q)
    })
  }
  
  return filtered
})

// Filter tenants data
const filteredTenants = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return tenantsData.value
  
  return tenantsData.value.filter((tenant) => {
    const name = (tenant.name || '').toLowerCase()
    const email = (tenant.email || '').toLowerCase()
    const phone = (tenant.phone || '').toLowerCase()
    const property = (tenant.property_name || '').toLowerCase()
    const unit = (tenant.unit_number || '').toLowerCase()
    return name.includes(q) || email.includes(q) || phone.includes(q) || property.includes(q) || unit.includes(q)
  })
})

// Pagination
const paginatedReport = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredReport.value.slice(start, end)
})

const paginatedProperties = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredProperties.value.slice(start, end)
})

const paginatedUnits = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredUnits.value.slice(start, end)
})

const paginatedTenants = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredTenants.value.slice(start, end)
})

const totalPages = computed(() => {
  if (viewType.value === 'Payment') {
    return Math.ceil(filteredReport.value.length / itemsPerPage.value)
  } else if (viewType.value === 'Properties') {
    return Math.ceil(filteredProperties.value.length / itemsPerPage.value)
  } else if (viewType.value === 'Units') {
    return Math.ceil(filteredUnits.value.length / itemsPerPage.value)
  } else if (viewType.value === 'Tenants') {
    return Math.ceil(filteredTenants.value.length / itemsPerPage.value)
  }
  return 1
})

const paginationInfo = computed(() => {
  let total = 0
  if (viewType.value === 'Payment') {
    total = filteredReport.value.length
  } else if (viewType.value === 'Properties') {
    total = filteredProperties.value.length
  } else if (viewType.value === 'Units') {
    total = filteredUnits.value.length
  } else if (viewType.value === 'Tenants') {
    total = filteredTenants.value.length
  }
  
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, total)
  return { start, end, total }
})

const resetToFirstPage = () => {
  currentPage.value = 1
}


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

// Format currency
const formatCurrency = (amount) => {
  if (!amount) return '₱0.00'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(amount)
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Format month display
const formatMonth = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString + '-01')
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
}

// Get payment status class
const getPaymentStatusClass = (status) => {
  switch (status.toLowerCase()) {
    case 'paid':
      return 'status-paid'
    case 'pending':
      return 'status-pending'
    case 'overdue':
      return 'status-overdue'
    default:
      return 'status-unpaid'
  }
}

// Download report as CSV
const downloadReportCsv = async () => {
  try {
    let endpoint = '/reports/download'
    let filename = `tenant_payment_report_${filterDate.value}.csv`
    
    if (viewType.value === 'Properties') {
      endpoint = '/reports/download-properties-csv'
      filename = `properties_report_${new Date().toISOString().slice(0, 10)}.csv`
    } else if (viewType.value === 'Units') {
      endpoint = '/reports/download-units-csv'
      filename = `units_report_${new Date().toISOString().slice(0, 10)}.csv`
    } else if (viewType.value === 'Tenants') {
      endpoint = '/reports/download-tenants-csv'
      filename = `tenants_report_${new Date().toISOString().slice(0, 10)}.csv`
    }
    
    let params = {}
    if (viewType.value === 'Payment') {
      params = { date: filterDate.value, type: paymentTypeFilter.value }
    } else if (viewType.value === 'Properties' && propertyTypeFilter.value !== 'All') {
      params = { type: propertyTypeFilter.value }
    } else if (viewType.value === 'Units' && unitStatusFilter.value !== 'All') {
      params = { status: unitStatusFilter.value.toLowerCase() }
    }
    const response = await api.get(endpoint, {
      params,
      responseType: 'blob'
    })
    
    // Create blob and download
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    showDownloadDropdown.value = false
  } catch (err) {
    console.error('Error downloading report:', err)
    alert('Failed to download report. Please try again.')
    showDownloadDropdown.value = false
  }
}

// Download report as PDF
const downloadReportPdf = async () => {
  try {
    let endpoint = '/reports/download-pdf'
    let filename = `tenant_payment_report_${filterDate.value}.pdf`
    
    if (viewType.value === 'Properties') {
      endpoint = '/reports/download-properties-pdf'
      filename = `properties_report_${new Date().toISOString().slice(0, 10)}.pdf`
    } else if (viewType.value === 'Units') {
      endpoint = '/reports/download-units-pdf'
      filename = `units_report_${new Date().toISOString().slice(0, 10)}.pdf`
    } else if (viewType.value === 'Tenants') {
      endpoint = '/reports/download-tenants-pdf'
      filename = `tenants_report_${new Date().toISOString().slice(0, 10)}.pdf`
    }
    
    let params = {}
    if (viewType.value === 'Payment') {
      params = { date: filterDate.value, type: paymentTypeFilter.value }
    } else if (viewType.value === 'Properties' && propertyTypeFilter.value !== 'All') {
      params = { type: propertyTypeFilter.value }
    } else if (viewType.value === 'Units' && unitStatusFilter.value !== 'All') {
      params = { status: unitStatusFilter.value.toLowerCase() }
    }
    const response = await api.get(endpoint, {
      params,
      responseType: 'blob'
    })
    
    // Check if response is PDF or HTML
    const contentType = response.headers['content-type'] || ''
    
    if (contentType.includes('application/pdf')) {
      // It's a real PDF from DomPDF, download it directly
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      showDownloadDropdown.value = false
    } else {
      // It's HTML (DomPDF not available), convert to PDF using html2pdf.js
      const blob = new Blob([response.data], { type: 'text/html; charset=UTF-8' })
      const reader = new FileReader()
      
      reader.onload = async (e) => {
        const htmlContent = e.target.result
        
        // Validate HTML content
        if (!htmlContent || htmlContent.trim().length === 0) {
          alert('Error: Received empty HTML content. Please try again.')
          showDownloadDropdown.value = false
          return
        }
        
        // Check if HTML contains table data
        const tempDiv = document.createElement('div')
        tempDiv.innerHTML = htmlContent
        const tableRows = tempDiv.querySelectorAll('tbody tr')
        if (tableRows.length === 0) {
          alert('No data available to generate PDF. The report is empty.')
          showDownloadDropdown.value = false
          return
        }
        
        // Load html2pdf.js from CDN if not already loaded
        if (!window.html2pdf) {
          const script = document.createElement('script')
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'
          script.onload = () => convertHtmlToPdf(htmlContent, filename)
          script.onerror = () => {
            alert('Failed to load PDF conversion library. Please check your internet connection.')
            showDownloadDropdown.value = false
          }
          document.head.appendChild(script)
        } else {
          await convertHtmlToPdf(htmlContent, filename)
        }
      }
      
      reader.onerror = () => {
        alert('Error reading response data. Please try again.')
        showDownloadDropdown.value = false
      }
      
      reader.readAsText(blob)
      
      async function convertHtmlToPdf(html, pdfFilename) {
        try {
          // Create a temporary container for the HTML
          const tempDiv = document.createElement('div')
          tempDiv.style.position = 'fixed'
          tempDiv.style.left = '0'
          tempDiv.style.top = '0'
          tempDiv.style.width = '297mm' // A4 landscape width
          tempDiv.style.minHeight = '210mm' // A4 landscape height
          tempDiv.style.padding = '20px'
          tempDiv.style.backgroundColor = 'white'
          tempDiv.style.zIndex = '-9999'
          tempDiv.style.visibility = 'hidden'
          tempDiv.innerHTML = html
          document.body.appendChild(tempDiv)
          
          // Wait for content to render
          await new Promise(resolve => {
            // Wait for images to load
            const images = tempDiv.querySelectorAll('img')
            if (images.length === 0) {
              setTimeout(resolve, 300)
            } else {
              let loadedCount = 0
              images.forEach(img => {
                if (img.complete) {
                  loadedCount++
                } else {
                  img.onload = () => {
                    loadedCount++
                    if (loadedCount === images.length) resolve()
                  }
                  img.onerror = () => {
                    loadedCount++
                    if (loadedCount === images.length) resolve()
                  }
                }
              })
              if (loadedCount === images.length) resolve()
              // Timeout after 2 seconds
              setTimeout(resolve, 2000)
            }
          })
          
          // Verify content exists
          const table = tempDiv.querySelector('table')
          if (!table) {
            throw new Error('No table found in HTML content')
          }
          
          const rows = table.querySelectorAll('tbody tr')
          if (rows.length === 0) {
            throw new Error('No data rows found in table')
          }
          
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
              allowTaint: true,
              width: tempDiv.scrollWidth,
              height: tempDiv.scrollHeight,
              windowWidth: tempDiv.scrollWidth
            },
            jsPDF: { 
              unit: 'mm', 
              format: 'a4', 
              orientation: 'landscape' // Use landscape for wide tables
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
          }
          
          // Generate and download PDF
          await window.html2pdf().set(opt).from(tempDiv).save()
          
          // Clean up
          setTimeout(() => {
            if (tempDiv.parentNode) {
              document.body.removeChild(tempDiv)
            }
          }, 1000)
          
          showDownloadDropdown.value = false
        } catch (error) {
          console.error('Error converting HTML to PDF:', error)
          alert('Failed to generate PDF: ' + error.message + '. Please check if there is data to export.')
          // Clean up on error
          const tempDivs = document.querySelectorAll('div[style*="z-index: -9999"]')
          tempDivs.forEach(div => {
            if (div.parentNode) {
              document.body.removeChild(div)
            }
          })
          // Fallback: download as HTML
          const blob = new Blob([html], { type: 'text/html; charset=UTF-8' })
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement('a')
          link.href = url
          link.setAttribute('download', pdfFilename.replace('.pdf', '.html'))
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
          showDownloadDropdown.value = false
        }
      }
    }
    
    showDownloadDropdown.value = false
  } catch (err) {
    console.error('Error downloading PDF:', err)
    // Fallback: try direct download link
    try {
      let url = ''
      if (viewType.value === 'Payment') {
        url = `/api/reports/download-pdf?date=${filterDate.value}`
      } else if (viewType.value === 'Properties') {
        url = '/api/reports/download-properties-pdf'
      } else if (viewType.value === 'Units') {
        url = '/api/reports/download-units-pdf'
      } else if (viewType.value === 'Tenants') {
        url = '/api/reports/download-tenants-pdf'
      }
      
      // Create a temporary link to download
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', filename)
      link.setAttribute('target', '_blank')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (e) {
      alert('Failed to download PDF. Please try again.')
    }
    showDownloadDropdown.value = false
  }
}

// Toggle download dropdown
const toggleDownloadDropdown = (event) => {
  if (showDownloadDropdown.value) {
    showDownloadDropdown.value = false
  } else {
    const rect = event.currentTarget.getBoundingClientRect()
    downloadDropdownPosition.value = {
      top: rect.bottom + 4,
      left: rect.left
    }
    showDownloadDropdown.value = true
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (showDownloadDropdown.value && !event.target.closest('.download-dropdown-container')) {
    showDownloadDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Handle date change
const handleDateChange = () => {
  fetchReport()
}

// Handle properties date change
const handlePropertiesDateChange = () => {
  fetchProperties()
}

// Handle units date change
const handleUnitsDateChange = () => {
  fetchUnits()
}

// Handle tenants date change
const handleTenantsDateChange = () => {
  fetchTenants()
}

onMounted(() => {
  fetchReport()
})

onActivated(() => {
  if (viewType.value === 'Payment') {
    fetchReport()
  } else if (viewType.value === 'Properties') {
    fetchProperties()
  } else if (viewType.value === 'Units') {
    fetchUnits()
  } else if (viewType.value === 'Tenants') {
    fetchTenants()
  }
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Page Title -->
      <div class="page-header">
        <h1 class="page-title">Reports</h1>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value">{{ displayStatistics.total_tenants }}</div>
          <div class="stat-label">Total Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ displayStatistics.paid_tenants }}</div>
          <div class="stat-label">Paid Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ displayStatistics.unpaid_tenants }}</div>
          <div class="stat-label">Unpaid Tenants</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(displayStatistics.total_rent) }}</div>
          <div class="stat-label">Total Rent</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(displayStatistics.total_paid) }}</div>
          <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ formatCurrency(displayStatistics.total_unpaid) }}</div>
          <div class="stat-label">Total Unpaid</div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="search-container">
          <MagnifyingGlassIcon class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            :placeholder="viewType === 'Payment' ? 'Search tenants by name, email, property, or unit...' : viewType === 'Properties' ? 'Search properties by name or location...' : viewType === 'Units' ? 'Search units by number, property, or tenant...' : 'Search tenants by name, email, phone, property, or unit...'"
            @input="resetToFirstPage"
          />
        </div>
        <div class="filters-group">
          <div class="filter-container">
            <select v-model="viewType" class="filter-dropdown" @change="handleViewTypeChange">
              <option value="Payment">Payment</option>
              <option value="Properties">Properties</option>
              <option value="Units">Units</option>
              <option value="Tenants">Tenants</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div v-if="viewType === 'Payment'" class="filter-container">
            <select v-model="paymentTypeFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="rent">Rent</option>
              <option value="utility">Utilities</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div v-if="viewType === 'Properties'" class="filter-container">
            <select v-model="propertyTypeFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Types</option>
              <option value="residential">Residential</option>
              <option value="commercial">Commercial</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div v-if="viewType === 'Units'" class="filter-container">
            <select v-model="unitStatusFilter" class="filter-dropdown" @change="resetToFirstPage">
              <option value="All">All Status</option>
              <option value="Occupied">Occupied</option>
              <option value="Available">Available</option>
            </select>
            <ChevronDownIcon class="chevron-icon" />
          </div>
          <div v-if="viewType === 'Payment'" class="date-filter-container">
            <CalendarIcon class="calendar-icon" />
            <input
              type="month"
              v-model="filterDate"
              @change="handleDateChange"
              class="date-input"
            />
            <span class="date-label">{{ formatMonth(filterDate) }}</span>
          </div>
          <div v-if="viewType === 'Properties'" class="date-filter-container">
            <CalendarIcon class="calendar-icon" />
            <input
              type="month"
              v-model="propertiesFilterDate"
              @change="handlePropertiesDateChange"
              class="date-input"
            />
            <span class="date-label">{{ formatMonth(propertiesFilterDate) }}</span>
          </div>
          <div v-if="viewType === 'Units'" class="date-filter-container">
            <CalendarIcon class="calendar-icon" />
            <input
              type="month"
              v-model="unitsFilterDate"
              @change="handleUnitsDateChange"
              class="date-input"
            />
            <span class="date-label">{{ formatMonth(unitsFilterDate) }}</span>
          </div>
          <div v-if="viewType === 'Tenants'" class="date-filter-container">
            <CalendarIcon class="calendar-icon" />
            <input
              type="month"
              v-model="tenantsFilterDate"
              @change="handleTenantsDateChange"
              class="date-input"
            />
            <span class="date-label">{{ formatMonth(tenantsFilterDate) }}</span>
          </div>
          <div class="download-dropdown-container">
            <button class="download-btn" @click="toggleDownloadDropdown">
              <ArrowDownTrayIcon class="btn-icon" />
              <span>Download</span>
              <ChevronDownIcon class="btn-chevron-icon" />
            </button>
            <Teleport to="body">
              <div
                v-if="showDownloadDropdown"
                class="download-dropdown-menu"
                :style="{
                  top: downloadDropdownPosition.top + 'px',
                  left: downloadDropdownPosition.left + 'px'
                }"
              >
                <button class="dropdown-item" @click="downloadReportCsv">
                  <ArrowDownTrayIcon class="dropdown-icon" />
                  <span>Download CSV</span>
                </button>
                <button class="dropdown-item" @click="downloadReportPdf">
                  <ArrowDownTrayIcon class="dropdown-icon" />
                  <span>Download PDF</span>
                </button>
              </div>
            </Teleport>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading report...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchReport">Retry</button>
      </div>

      <!-- Payment Report Table -->
      <div v-else-if="viewType === 'Payment'" class="report-table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Tenant Name</th>
              <th>Email</th>
              <th>Property / Unit</th>
              <th v-if="paymentTypeFilter === 'utility'">Electricity</th>
              <th v-if="paymentTypeFilter === 'utility'">Water</th>
              <th v-if="paymentTypeFilter === 'utility'">Internet</th>
              <th v-if="paymentTypeFilter !== 'utility'">Monthly Rent</th>
              <th>Payment Status</th>
              <th>Payment Amount</th>
              <th>Payment Date</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedReport.length === 0">
              <td :colspan="paymentTypeFilter === 'utility' ? 10 : 8" class="empty-state">
                No tenants found
              </td>
            </tr>
            <tr v-for="item in paginatedReport" :key="`${item.tenant_id}-${item.unit_id}`">
              <td>
                <div class="tenant-name">{{ item.tenant_name }}</div>
              </td>
              <td>{{ item.tenant_email }}</td>
              <td>
                <div class="property-info">
                  <div class="property-name">{{ item.property_name }}</div>
                  <div class="unit-number">Unit {{ item.unit_number }}</div>
                </div>
              </td>
              <template v-if="paymentTypeFilter === 'utility'">
                <td class="amount-cell">{{ item.electricity ? formatCurrency(item.electricity) : '-' }}</td>
                <td class="amount-cell">{{ item.water ? formatCurrency(item.water) : '-' }}</td>
                <td class="amount-cell">{{ item.internet ? formatCurrency(item.internet) : '-' }}</td>
              </template>
              <td v-else class="amount-cell">{{ formatCurrency(item.monthly_rent) }}</td>
              <td>
                <span :class="['status-badge', getPaymentStatusClass(item.payment_status)]">
                  {{ item.payment_status }}
                </span>
              </td>
              <td>{{ item.payment_amount ? formatCurrency(item.payment_amount) : '-' }}</td>
              <td>{{ item.payment_date ? formatDate(item.payment_date) : '-' }}</td>
              <td>{{ item.due_date ? formatDate(item.due_date) : '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Properties Table -->
      <div v-else-if="viewType === 'Properties'" class="report-table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Property Name</th>
              <th>Location</th>
              <th>Type</th>
              <th>Units</th>
              <th>Available Units</th>
              <th>Occupied Units</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedProperties.length === 0">
              <td colspan="6" class="empty-state">
                No properties found
              </td>
            </tr>
            <tr v-for="property in paginatedProperties" :key="property.id">
              <td>
                <div class="property-name">{{ property.name }}</div>
              </td>
              <td>{{ property.street_address }}, {{ property.city }}, {{ property.state }} {{ property.zip_code }}</td>
              <td>{{ property.type }}</td>
              <td>{{ property.units || 0 }}</td>
              <td>{{ (property.units || 0) - (property.tenants || 0) }}</td>
              <td>{{ property.tenants || 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Units Table -->
      <div v-else-if="viewType === 'Units'" class="report-table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Unit Number</th>
              <th>Property</th>
              <th>Unit Type</th>
              <th>Monthly Rent</th>
              <th>Status</th>
              <th>Tenant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedUnits.length === 0">
              <td colspan="6" class="empty-state">
                No units found
              </td>
            </tr>
            <tr v-for="unit in paginatedUnits" :key="unit.id">
              <td>
                <div class="unit-number">{{ unit.unit_number }}</div>
              </td>
              <td>{{ unit.property_name }}</td>
              <td>{{ unit.unit_type }}</td>
              <td class="amount-cell">{{ formatCurrency(unit.monthly_rent) }}</td>
              <td>
                <span class="status-badge">
                  {{ unit.is_occupied ? 'Occupied' : 'Available' }}
                </span>
              </td>
              <td>
                <div v-if="unit.tenant" class="tenant-info">
                  <div class="tenant-name">{{ unit.tenant.name }}</div>
                  <div class="tenant-email">{{ unit.tenant.email }}</div>
                  <div class="tenant-phone">{{ unit.tenant.phone }}</div>
                </div>
                <span v-else class="no-tenant">No tenant</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tenants Table -->
      <div v-else-if="viewType === 'Tenants'" class="report-table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Tenant Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Property</th>
              <th>Unit</th>
              <th>Monthly Rent</th>
              <th>Lease Start</th>
              <th>Lease End</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedTenants.length === 0">
              <td colspan="9" class="empty-state">
                No tenants found
              </td>
            </tr>
            <tr v-for="tenant in paginatedTenants" :key="tenant.id || tenant.application_id">
              <td>
                <div class="tenant-name">{{ tenant.name }}</div>
              </td>
              <td>{{ tenant.email }}</td>
              <td>{{ tenant.phone }}</td>
              <td>{{ tenant.property_name }}</td>
              <td>{{ tenant.unit_number }}</td>
              <td class="amount-cell">{{ formatCurrency(tenant.rent) }}</td>
              <td>{{ tenant.lease_start ? formatDate(tenant.lease_start) : '-' }}</td>
              <td>{{ tenant.lease_end ? formatDate(tenant.lease_end) : '-' }}</td>
              <td>
                <span class="status-badge">
                  {{ tenant.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="!isLoading && !error && ((viewType === 'Payment' && filteredReport.length > 0) || (viewType === 'Properties' && filteredProperties.length > 0) || (viewType === 'Units' && filteredUnits.length > 0) || (viewType === 'Tenants' && filteredTenants.length > 0))" class="pagination-container">
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
    </div>
  </div>
</template>

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
  font-family: 'Montserrat', sans-serif;
}

.page-header {
  margin-bottom: 30px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

/* Statistics Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
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
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-container {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #999;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 48px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #1500FF;
}

.filters-group {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
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
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  background: white;
  cursor: pointer;
  min-width: 150px;
}

.filter-dropdown:focus {
  outline: none;
  border-color: #1500FF;
}

.chevron-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #999;
  pointer-events: none;
}

.date-filter-container {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
}

.calendar-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.date-input {
  border: none;
  outline: none;
  font-size: 16px;
  font-family: 'Montserrat', sans-serif;
  color: #1a1a1a;
  background: transparent;
  cursor: pointer;
}

.date-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.download-dropdown-container {
  position: relative;
  display: inline-block;
}

.download-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.download-btn:hover {
  background: #1200cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
}

.btn-chevron-icon {
  width: 16px;
  height: 16px;
  margin-left: 2px;
  flex-shrink: 0;
}

.download-dropdown-menu {
  position: fixed;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 180px;
  overflow: hidden;
}

.download-dropdown-menu .dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: white;
  text-align: left;
  font-size: 14px;
  font-weight: 500;
  font-family: 'Montserrat', sans-serif;
  color: #1a1a1a;
  cursor: pointer;
  transition: background-color 0.2s;
}

.download-dropdown-menu .dropdown-item:hover {
  background: #f9fafb;
}

.download-dropdown-menu .dropdown-icon {
  width: 18px;
  height: 18px;
  color: #666;
}

.btn-icon {
  width: 20px;
  height: 20px;
}

/* Loading and Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.error-state {
  color: #dc2626;
}

.retry-btn {
  margin-top: 16px;
  padding: 10px 20px;
  background: #1500FF;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-family: 'Montserrat', sans-serif;
}

/* Report Table */
.report-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
}

.report-table thead {
  background: #f8f9fa;
}

.report-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
  border-bottom: 2px solid #e5e7eb;
}

.report-table td {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.report-table tbody tr:hover {
  background: #f9fafb;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

.tenant-name {
  font-weight: 600;
  color: #1a1a1a;
}

.property-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.property-name {
  font-weight: 600;
  color: #1a1a1a;
}

.unit-number {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
}

.amount-cell {
  font-weight: 600;
  color: #111827;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-paid {
  background: #d1fae5;
  color: #065f46;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-overdue {
  background: #fee2e2;
  color: #991b1b;
}

.status-unpaid {
  background: #f3f4f6;
  color: #6b7280;
}

.status-occupied {
  background: #dbeafe;
  color: #1e40af;
}

.status-available {
  background: #d1fae5;
  color: #065f46;
}


.tenant-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tenant-phone {
  font-size: 12px;
  color: #666;
}

.no-tenant {
  color: #999;
  font-style: italic;
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
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
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
</style>
