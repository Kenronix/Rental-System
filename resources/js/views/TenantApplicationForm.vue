<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const route = useRoute()
const router = useRouter()

const unitId = computed(() => route.params.unitId)
const unit = ref(null)
const property = ref(null)
const isLoading = ref(true)
const error = ref(null)
const isSubmitting = ref(false)
const submitSuccess = ref(false)

const formData = ref({
  // Personal Information
  id_picture: null,
  profile_picture: null,
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  phone: '',
  whatsapp: '',
  occupation: '',
  monthly_income: '',
  address: '',
  number_of_people: '',
  lease_duration_months: '',
  lease_start_date: '',
  
  // References
  reference1_name: '',
  reference1_address: '',
  reference1_phone: '',
  reference1_email: '',
  reference1_relationship: '',
  reference2_name: '',
  reference2_address: '',
  reference2_phone: '',
  reference2_email: '',
  reference2_relationship: ''
})

const idPicturePreview = ref(null)
const profilePicturePreview = ref(null)
const fieldErrors = ref({})

// Fetch unit and property information
const fetchUnitInfo = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // Fetch unit with property relationship
    const unitRes = await api.get(`/units/${unitId.value}`)
    
    if (unitRes.data.success) {
      unit.value = unitRes.data.unit
      // Property is included in the response
      if (unitRes.data.property) {
        property.value = unitRes.data.property
      }
    } else {
      error.value = 'Unit not found.'
    }
  } catch (err) {
    console.error('Error fetching unit:', err)
    error.value = 'Failed to load unit information. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Handle ID picture upload
const handleIdPictureUpload = (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Please upload an image file.')
    return
  }
  
  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    alert('Image size should be less than 5MB.')
    return
  }
  
  formData.value.id_picture = file
  
  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    idPicturePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// Remove ID picture
const removeIdPicture = () => {
  formData.value.id_picture = null
  idPicturePreview.value = null
}

// Handle profile picture upload
const handleProfilePictureUpload = (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Please upload an image file.')
    return
  }
  
  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    alert('Image size should be less than 5MB.')
    return
  }
  
  formData.value.profile_picture = file
  
  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    profilePicturePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// Remove profile picture
const removeProfilePicture = () => {
  formData.value.profile_picture = null
  profilePicturePreview.value = null
}

// Validate form
const validateForm = () => {
  fieldErrors.value = {}
  let hasError = false
  
  if (!formData.value.first_name?.trim()) {
    fieldErrors.value.first_name = true
    hasError = true
  }
  
  if (!formData.value.last_name?.trim()) {
    fieldErrors.value.last_name = true
    hasError = true
  }
  
  if (!formData.value.email?.trim()) {
    fieldErrors.value.email = true
    hasError = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
    fieldErrors.value.email = true
    hasError = true
  }
  
  if (!formData.value.phone?.trim()) {
    fieldErrors.value.phone = true
    hasError = true
  }
  
  if (!formData.value.whatsapp?.trim()) {
    fieldErrors.value.whatsapp = true
    hasError = true
  }
  
  if (!formData.value.occupation?.trim()) {
    fieldErrors.value.occupation = true
    hasError = true
  }
  
  if (!formData.value.monthly_income?.trim()) {
    fieldErrors.value.monthly_income = true
    hasError = true
  }
  
  if (!formData.value.address?.trim()) {
    fieldErrors.value.address = true
    hasError = true
  }
  
  if (!formData.value.number_of_people || parseInt(formData.value.number_of_people, 10) < 1) {
    fieldErrors.value.number_of_people = true
    hasError = true
  }
  
  if (!formData.value.lease_duration_months) {
    fieldErrors.value.lease_duration_months = true
    hasError = true
  }
  
  if (!formData.value.lease_start_date) {
    fieldErrors.value.lease_start_date = true
    hasError = true
  }
  
  if (!formData.value.id_picture) {
    fieldErrors.value.id_picture = true
    hasError = true
  }
  
  // References validation - Reference 1
  if (!formData.value.reference1_name?.trim()) {
    fieldErrors.value.reference1_name = true
    hasError = true
  }
  
  if (!formData.value.reference1_address?.trim()) {
    fieldErrors.value.reference1_address = true
    hasError = true
  }
  
  if (!formData.value.reference1_phone?.trim()) {
    fieldErrors.value.reference1_phone = true
    hasError = true
  }
  
  if (!formData.value.reference1_email?.trim()) {
    fieldErrors.value.reference1_email = true
    hasError = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.reference1_email)) {
    fieldErrors.value.reference1_email = true
    hasError = true
  }
  
  if (!formData.value.reference1_relationship?.trim()) {
    fieldErrors.value.reference1_relationship = true
    hasError = true
  }
  
  // References validation - Reference 2
  if (!formData.value.reference2_name?.trim()) {
    fieldErrors.value.reference2_name = true
    hasError = true
  }
  
  if (!formData.value.reference2_address?.trim()) {
    fieldErrors.value.reference2_address = true
    hasError = true
  }
  
  if (!formData.value.reference2_phone?.trim()) {
    fieldErrors.value.reference2_phone = true
    hasError = true
  }
  
  if (!formData.value.reference2_email?.trim()) {
    fieldErrors.value.reference2_email = true
    hasError = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.reference2_email)) {
    fieldErrors.value.reference2_email = true
    hasError = true
  }
  
  if (!formData.value.reference2_relationship?.trim()) {
    fieldErrors.value.reference2_relationship = true
    hasError = true
  }
  
  if (hasError) {
    const firstErrorField = document.querySelector('.form-input.error, .form-textarea.error, .file-upload.error')
    if (firstErrorField) {
      firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
      firstErrorField.focus()
    }
    return false
  }
  
  return true
}

// Submit application
const submitApplication = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  error.value = null
  
  try {
    // Convert ID picture to base64
    let idPictureBase64 = null
    if (formData.value.id_picture) {
      idPictureBase64 = await new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = () => resolve(reader.result)
        reader.onerror = reject
        reader.readAsDataURL(formData.value.id_picture)
      })
    }
    
    // Convert profile picture to base64 (optional)
    let profilePictureBase64 = null
    if (formData.value.profile_picture) {
      profilePictureBase64 = await new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = () => resolve(reader.result)
        reader.onerror = reject
        reader.readAsDataURL(formData.value.profile_picture)
      })
    }
    
    const payload = {
      unit_id: parseInt(unitId.value, 10),
      id_picture: idPictureBase64,
      profile_picture: profilePictureBase64,
      first_name: formData.value.first_name.trim(),
      middle_name: formData.value.middle_name?.trim() || '',
      last_name: formData.value.last_name.trim(),
      name: `${formData.value.first_name.trim()} ${formData.value.middle_name?.trim() || ''} ${formData.value.last_name.trim()}`.trim(), // Combine for backward compatibility
      email: formData.value.email.trim(),
      phone: formData.value.phone.trim(),
      whatsapp: formData.value.whatsapp.trim(),
      occupation: formData.value.occupation.trim(),
      monthly_income: parseInt(formData.value.monthly_income.replace(/[^0-9]/g, ''), 10),
      address: formData.value.address.trim(),
      number_of_people: parseInt(formData.value.number_of_people, 10),
      lease_duration_months: parseInt(formData.value.lease_duration_months, 10),
      lease_start_date: formData.value.lease_start_date,
      reference1_name: formData.value.reference1_name.trim(),
      reference1_address: formData.value.reference1_address.trim(),
      reference1_phone: formData.value.reference1_phone.trim(),
      reference1_email: formData.value.reference1_email.trim(),
      reference1_relationship: formData.value.reference1_relationship.trim(),
      reference2_name: formData.value.reference2_name.trim(),
      reference2_address: formData.value.reference2_address.trim(),
      reference2_phone: formData.value.reference2_phone.trim(),
      reference2_email: formData.value.reference2_email.trim(),
      reference2_relationship: formData.value.reference2_relationship.trim()
    }
    
    const res = await api.post('/tenant-applications', payload)
    
    if (res.data.success) {
      submitSuccess.value = true
    } else {
      error.value = res.data.message || 'Failed to submit application. Please try again.'
    }
  } catch (err) {
    console.error('Error submitting application:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      
      // Set field errors for validation failures
      if (errors.email) {
        fieldErrors.value.email = true
        error.value = errors.email[0] || 'Email validation failed.'
      } else if (err.response?.data?.message) {
        error.value = err.response.data.message
      } else {
        error.value = 'Failed to submit application. Please check your inputs and try again.'
      }
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
      
      // Check if it's an email-related error
      if (err.response.data.message.toLowerCase().includes('email')) {
        fieldErrors.value.email = true
      }
    } else {
      error.value = 'Failed to submit application. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}

// Clear field error on input
const clearFieldError = (fieldName) => {
  if (fieldErrors.value[fieldName]) {
    fieldErrors.value[fieldName] = false
  }
}

onMounted(() => {
  fetchUnitInfo()
})
</script>

<template>
  <div class="application-page">
    <div class="application-container">
      <!-- Header -->
      <div class="page-header">
        <h1 class="page-title">Unit Application</h1>
        <p v-if="unit && property" class="page-subtitle">
          Applying for <strong>{{ unit.unit_number }}</strong> at <strong>{{ property.name }}</strong>
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <p>Loading unit information...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error && !unit" class="error-state">
        <p class="error-text">{{ error }}</p>
      </div>

      <!-- Success State -->
      <div v-else-if="submitSuccess" class="success-state">
        <div class="success-content">
          <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h2>Application Submitted Successfully!</h2>
          <p>Thank you for your interest. The landlord will review your application and contact you soon.</p>
        </div>
      </div>

      <!-- Application Form -->
      <form v-else-if="unit && property" @submit.prevent="submitApplication" class="application-form">
        <!-- Personal Information Section -->
        <div class="form-section">
          <h2 class="section-title">Personal Information</h2>
          
          <!-- ID Picture -->
          <div class="form-group">
            <label class="form-label">ID Picture <span class="required">*</span></label>
            <div :class="['file-upload', { error: fieldErrors.id_picture }]">
              <input
                type="file"
                accept="image/*"
                @change="handleIdPictureUpload"
                class="file-input"
                id="id-picture"
              />
              <label for="id-picture" class="file-label">
                <svg v-if="!idPicturePreview" class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span v-if="!idPicturePreview">Click to upload ID picture</span>
                <img v-else :src="idPicturePreview" alt="ID Preview" class="id-preview" />
              </label>
              <button v-if="idPicturePreview" type="button" @click="removeIdPicture" class="remove-image-btn">Remove</button>
            </div>
            <p v-if="fieldErrors.id_picture" class="field-error">ID picture is required</p>
          </div>

          <!-- Profile Picture -->
          <div class="form-group">
            <label class="form-label">Profile Picture</label>
            <div :class="['file-upload', { error: fieldErrors.profile_picture }]">
              <input
                type="file"
                accept="image/*"
                @change="handleProfilePictureUpload"
                class="file-input"
                id="profile-picture"
              />
              <label for="profile-picture" class="file-label">
                <svg v-if="!profilePicturePreview" class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span v-if="!profilePicturePreview">Click to upload profile picture</span>
                <img v-else :src="profilePicturePreview" alt="Profile Preview" class="id-preview" />
              </label>
              <button v-if="profilePicturePreview" type="button" @click="removeProfilePicture" class="remove-image-btn">Remove</button>
            </div>
            <p v-if="fieldErrors.profile_picture" class="field-error">Profile picture error</p>
          </div>

          <!-- Name -->
          <!-- First Name -->
          <div class="form-group">
            <label class="form-label">First Name <span class="required">*</span></label>
            <input
              v-model="formData.first_name"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.first_name }"
              placeholder="Enter your first name"
              @input="clearFieldError('first_name')"
            />
            <p v-if="fieldErrors.first_name" class="field-error">First name is required</p>
          </div>

          <!-- Middle Name -->
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input
              v-model="formData.middle_name"
              type="text"
              class="form-input"
              placeholder="Enter your middle name (optional)"
            />
          </div>

          <!-- Last Name -->
          <div class="form-group">
            <label class="form-label">Last Name <span class="required">*</span></label>
            <input
              v-model="formData.last_name"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.last_name }"
              placeholder="Enter your last name"
              @input="clearFieldError('last_name')"
            />
            <p v-if="fieldErrors.last_name" class="field-error">Last name is required</p>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input
              v-model="formData.email"
              type="email"
              class="form-input"
              :class="{ error: fieldErrors.email }"
              placeholder="Enter your email"
              @input="clearFieldError('email')"
            />
            <p v-if="fieldErrors.email" class="field-error">Valid email is required</p>
          </div>

          <!-- Phone -->
          <div class="form-group">
            <label class="form-label">Phone Number <span class="required">*</span></label>
            <input
              v-model="formData.phone"
              type="tel"
              class="form-input"
              :class="{ error: fieldErrors.phone }"
              placeholder="Enter your phone number"
              @input="clearFieldError('phone')"
            />
            <p v-if="fieldErrors.phone" class="field-error">Phone number is required</p>
          </div>

          <!-- WhatsApp -->
          <div class="form-group">
            <label class="form-label">WhatsApp Account <span class="required">*</span></label>
            <input
              v-model="formData.whatsapp"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.whatsapp }"
              placeholder="Enter your WhatsApp number"
              @input="clearFieldError('whatsapp')"
            />
            <p v-if="fieldErrors.whatsapp" class="field-error">WhatsApp account is required</p>
          </div>

          <!-- Occupation -->
          <div class="form-group">
            <label class="form-label">Occupation <span class="required">*</span></label>
            <input
              v-model="formData.occupation"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.occupation }"
              placeholder="Enter your occupation"
              @input="clearFieldError('occupation')"
            />
            <p v-if="fieldErrors.occupation" class="field-error">Occupation is required</p>
          </div>

          <!-- Monthly Income -->
          <div class="form-group">
            <label class="form-label">Monthly Income <span class="required">*</span></label>
            <input
              v-model="formData.monthly_income"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.monthly_income }"
              placeholder="Enter your monthly income"
              @input="clearFieldError('monthly_income')"
            />
            <p v-if="fieldErrors.monthly_income" class="field-error">Monthly income is required</p>
          </div>

          <!-- Address -->
          <div class="form-group">
            <label class="form-label">Address <span class="required">*</span></label>
            <textarea
              v-model="formData.address"
              class="form-textarea"
              :class="{ error: fieldErrors.address }"
              placeholder="Enter your complete address"
              rows="3"
              @input="clearFieldError('address')"
            ></textarea>
            <p v-if="fieldErrors.address" class="field-error">Address is required</p>
          </div>

          <!-- Number of People -->
          <div class="form-group">
            <label class="form-label">Number of People Who Will Stay <span class="required">*</span></label>
            <input
              v-model="formData.number_of_people"
              type="number"
              min="1"
              class="form-input"
              :class="{ error: fieldErrors.number_of_people }"
              placeholder="Enter number of people"
              @input="clearFieldError('number_of_people')"
            />
            <p v-if="fieldErrors.number_of_people" class="field-error">Number of people is required (minimum 1)</p>
          </div>

          <!-- Lease Duration -->
          <div class="form-group">
            <label class="form-label">Lease Duration <span class="required">*</span></label>
            <select
              v-model="formData.lease_duration_months"
              class="form-select"
              :class="{ error: fieldErrors.lease_duration_months }"
              @change="clearFieldError('lease_duration_months')"
            >
              <option value="">Select lease duration</option>
              <option value="1">1 Month</option>
              <option value="3">3 Months</option>
              <option value="6">6 Months</option>
              <option value="12">1 Year</option>
              <option value="24">2 Years</option>
            </select>
            <p v-if="fieldErrors.lease_duration_months" class="field-error">Lease duration is required</p>
          </div>

          <!-- Lease Start Date -->
          <div class="form-group">
            <label class="form-label">Lease Start Date <span class="required">*</span></label>
            <input
              v-model="formData.lease_start_date"
              type="date"
              class="form-input"
              :class="{ error: fieldErrors.lease_start_date }"
              :min="new Date().toISOString().split('T')[0]"
              @input="clearFieldError('lease_start_date')"
            />
            <p v-if="fieldErrors.lease_start_date" class="field-error">Lease start date is required</p>
          </div>
        </div>

        <!-- References Section -->
        <div class="form-section">
          <h2 class="section-title">References</h2>
          
          <!-- Reference 1 Information -->
          <div class="reference-group">
            <h3 class="reference-title">Reference 1</h3>
            
            <div class="form-group">
              <label class="form-label">Relationship <span class="required">*</span></label>
              <select
                v-model="formData.reference1_relationship"
                class="form-input"
                :class="{ error: fieldErrors.reference1_relationship }"
                @change="clearFieldError('reference1_relationship')"
              >
                <option value="">Select relationship</option>
                <option value="Mother">Mother</option>
                <option value="Father">Father</option>
                <option value="Sibling">Sibling</option>
                <option value="Spouse">Spouse</option>
                <option value="Friend">Friend</option>
                <option value="Colleague">Colleague</option>
                <option value="Relative">Relative</option>
                <option value="Other">Other</option>
              </select>
              <p v-if="fieldErrors.reference1_relationship" class="field-error">Relationship is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Name <span class="required">*</span></label>
              <input
                v-model="formData.reference1_name"
                type="text"
                class="form-input"
                :class="{ error: fieldErrors.reference1_name }"
                placeholder="Enter reference's full name"
                @input="clearFieldError('reference1_name')"
              />
              <p v-if="fieldErrors.reference1_name" class="field-error">Name is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Address <span class="required">*</span></label>
              <textarea
                v-model="formData.reference1_address"
                class="form-textarea"
                :class="{ error: fieldErrors.reference1_address }"
                placeholder="Enter reference's complete address"
                rows="2"
                @input="clearFieldError('reference1_address')"
              ></textarea>
              <p v-if="fieldErrors.reference1_address" class="field-error">Address is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Phone Number <span class="required">*</span></label>
              <input
                v-model="formData.reference1_phone"
                type="tel"
                class="form-input"
                :class="{ error: fieldErrors.reference1_phone }"
                placeholder="Enter reference's phone number"
                @input="clearFieldError('reference1_phone')"
              />
              <p v-if="fieldErrors.reference1_phone" class="field-error">Phone number is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input
                v-model="formData.reference1_email"
                type="email"
                class="form-input"
                :class="{ error: fieldErrors.reference1_email }"
                placeholder="Enter reference's email"
                @input="clearFieldError('reference1_email')"
              />
              <p v-if="fieldErrors.reference1_email" class="field-error">Valid email is required</p>
            </div>
          </div>

          <!-- Reference 2 Information -->
          <div class="reference-group">
            <h3 class="reference-title">Reference 2</h3>
            
            <div class="form-group">
              <label class="form-label">Relationship <span class="required">*</span></label>
              <select
                v-model="formData.reference2_relationship"
                class="form-input"
                :class="{ error: fieldErrors.reference2_relationship }"
                @change="clearFieldError('reference2_relationship')"
              >
                <option value="">Select relationship</option>
                <option value="Mother">Mother</option>
                <option value="Father">Father</option>
                <option value="Sibling">Sibling</option>
                <option value="Spouse">Spouse</option>
                <option value="Friend">Friend</option>
                <option value="Colleague">Colleague</option>
                <option value="Relative">Relative</option>
                <option value="Other">Other</option>
              </select>
              <p v-if="fieldErrors.reference2_relationship" class="field-error">Relationship is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Name <span class="required">*</span></label>
              <input
                v-model="formData.reference2_name"
                type="text"
                class="form-input"
                :class="{ error: fieldErrors.reference2_name }"
                placeholder="Enter reference's full name"
                @input="clearFieldError('reference2_name')"
              />
              <p v-if="fieldErrors.reference2_name" class="field-error">Name is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Address <span class="required">*</span></label>
              <textarea
                v-model="formData.reference2_address"
                class="form-textarea"
                :class="{ error: fieldErrors.reference2_address }"
                placeholder="Enter reference's complete address"
                rows="2"
                @input="clearFieldError('reference2_address')"
              ></textarea>
              <p v-if="fieldErrors.reference2_address" class="field-error">Address is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Phone Number <span class="required">*</span></label>
              <input
                v-model="formData.reference2_phone"
                type="tel"
                class="form-input"
                :class="{ error: fieldErrors.reference2_phone }"
                placeholder="Enter reference's phone number"
                @input="clearFieldError('reference2_phone')"
              />
              <p v-if="fieldErrors.reference2_phone" class="field-error">Phone number is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input
                v-model="formData.reference2_email"
                type="email"
                class="form-input"
                :class="{ error: fieldErrors.reference2_email }"
                placeholder="Enter reference's email"
                @input="clearFieldError('reference2_email')"
              />
              <p v-if="fieldErrors.reference2_email" class="field-error">Valid email is required</p>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <!-- Submit Button -->
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Submitting...' : 'Submit Application' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.application-page {
  min-height: 100vh;
  background: #f0f0f0;
  padding: 40px 20px;
  font-family: 'Montserrat', sans-serif;
}

.application-container {
  max-width: 800px;
  margin: 0 auto;
  background: white;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.page-header {
  margin-bottom: 32px;
  text-align: center;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.page-subtitle {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
}

.error-text {
  color: #dc2626;
  font-size: 16px;
}

.success-state {
  text-align: center;
  padding: 60px 20px;
}

.success-content {
  max-width: 500px;
  margin: 0 auto;
}

.success-icon {
  width: 64px;
  height: 64px;
  color: #10b981;
  margin: 0 auto 24px;
}

.success-content h2 {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.success-content p {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.application-form {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e5e5;
}

.reference-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-top: 16px;
}

.reference-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 12px 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.required {
  color: #dc2626;
}

.form-input,
.form-textarea,
.form-select {
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  color: #333;
  outline: none;
  transition: border-color 0.2s;
  background-color: white;
}

.form-input:hover,
.form-textarea:hover,
.form-select:hover {
  border-color: #bbb;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: #1500FF;
}

.form-input.error,
.form-textarea.error,
.form-select.error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.form-select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 40px;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.file-upload {
  position: relative;
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.file-upload:hover {
  border-color: #1500FF;
  background-color: #f0f0ff;
}

.file-upload.error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.file-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.upload-icon {
  width: 48px;
  height: 48px;
  color: #999;
}

.id-preview {
  max-width: 200px;
  max-height: 200px;
  border-radius: 8px;
  object-fit: cover;
}

.remove-image-btn {
  margin-top: 12px;
  padding: 8px 16px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.remove-image-btn:hover {
  background: #b91c1c;
}

.field-error {
  font-size: 12px;
  color: #dc2626;
  margin: 0;
}

.error-message {
  padding: 12px 16px;
  background: #fef2f2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #dc2626;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: center;
  margin-top: 8px;
}

.btn {
  padding: 14px 32px;
  border: none;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #1500FF;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1200e6;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .application-container {
    padding: 24px;
  }

  .page-title {
    font-size: 24px;
  }
}
</style>
