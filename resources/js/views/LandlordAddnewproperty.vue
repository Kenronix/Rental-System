<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import api from '../services/api.js'
import { 
  HomeIcon, 
  BuildingOfficeIcon, 
  ChevronLeftIcon, 
  ChevronRightIcon,
  MapPinIcon,
  PhotoIcon,
  PencilIcon,
  ArrowUpTrayIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()

const isEditMode = computed(() => route.name === 'EditProperty' && route.params.id)
const propertyId = computed(() => isEditMode.value ? route.params.id : null)
const isLoadingProperty = ref(false)

const currentStep = ref(1)
const selectedType = ref('residential')

// Form data
const formData = ref({
  type: 'residential',
  name: '',
  description: '',
  streetAddress: '',
  city: '',
  state: '',
  zipCode: '',
  photos: [],
  mainPhoto: null
})

const steps = [
  { number: 1, label: 'Type' },
  { number: 2, label: 'Details' },
  { number: 3, label: 'Location' },
  { number: 4, label: 'Photos' },
  { number: 5, label: 'Review' }
]

const selectType = (type) => {
  selectedType.value = type
  formData.value.type = type
}

const goToNextStep = () => {
  // Validate current step before proceeding
  if (currentStep.value === 1) {
    // Step 1: Type selection is always valid (has default)
    if (currentStep.value < steps.length) {
      currentStep.value++
    }
  } else if (currentStep.value === 2) {
    // Step 2: Name is required
    if (!formData.value.name || formData.value.name.trim() === '') {
      alert('Please enter a property name')
      return
    }
    if (currentStep.value < steps.length) {
      currentStep.value++
    }
  } else if (currentStep.value === 3) {
    // Step 3: All location fields are required
    if (!formData.value.streetAddress || formData.value.streetAddress.trim() === '') {
      alert('Please enter a street address')
      return
    }
    if (!formData.value.city || formData.value.city.trim() === '') {
      alert('Please enter a city')
      return
    }
    if (!formData.value.state || formData.value.state.trim() === '') {
      alert('Please enter a state')
      return
    }
    if (!formData.value.zipCode || formData.value.zipCode.trim() === '') {
      alert('Please enter a zip code')
      return
    }
    if (currentStep.value < steps.length) {
      currentStep.value++
    }
  } else if (currentStep.value < steps.length) {
    currentStep.value++
  }
}

const goToPreviousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  } else {
    router.push('/landlord/properties')
  }
}

const goToStep = (stepNumber) => {
  currentStep.value = stepNumber
}

// Validation functions for special characters
const sanitizeName = (value) => {
  // Allow letters, numbers, spaces, hyphens, apostrophes, and periods
  return value.replace(/[^a-zA-Z0-9\s\-'.,]/g, '')
}

const sanitizeDescription = (value) => {
  // Allow letters, numbers, spaces, and common punctuation
  return value.replace(/[<>{}[\]\\|`~!@#$%^&*()_+=]/g, '')
}

const sanitizeAddress = (value) => {
  // Allow letters, numbers, spaces, hyphens, commas, periods, #, /, and apostrophes
  return value.replace(/[<>{}[\]\\|`~!$%^&*()_+=]/g, '')
}

const sanitizeCity = (value) => {
  // Allow letters, numbers, spaces, hyphens, and apostrophes
  return value.replace(/[^a-zA-Z0-9\s\-'.,]/g, '')
}

const sanitizeState = (value) => {
  // Allow letters, numbers, spaces, and hyphens
  return value.replace(/[^a-zA-Z0-9\s\-]/g, '')
}

const sanitizeZipCode = (value) => {
  // Allow only numbers and hyphens (for zip+4 format)
  return value.replace(/[^0-9\-]/g, '')
}

// Input handlers - sanitize input on change
const handleNameInput = (event) => {
  const sanitized = sanitizeName(event.target.value)
  formData.value.name = sanitized
}

const handleDescriptionInput = (event) => {
  const sanitized = sanitizeDescription(event.target.value)
  formData.value.description = sanitized
}

const handleStreetAddressInput = (event) => {
  const sanitized = sanitizeAddress(event.target.value)
  formData.value.streetAddress = sanitized
}

const handleCityInput = (event) => {
  const sanitized = sanitizeCity(event.target.value)
  formData.value.city = sanitized
}

const handleStateInput = (event) => {
  const sanitized = sanitizeState(event.target.value)
  formData.value.state = sanitized
}

const handleZipCodeInput = (event) => {
  const sanitized = sanitizeZipCode(event.target.value)
  formData.value.zipCode = sanitized
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        const photoData = {
          id: Date.now() + Math.random(),
          file: file,
          preview: e.target.result
        }
        formData.value.photos.push(photoData)
        if (!formData.value.mainPhoto) {
          formData.value.mainPhoto = photoData
        }
      }
      reader.readAsDataURL(file)
    }
  })
}

const setMainPhoto = (photo) => {
  formData.value.mainPhoto = photo
}

const removePhoto = (photoId) => {
  // Remove from photos array
  formData.value.photos = formData.value.photos.filter(p => p.id !== photoId)
  
  // If removing main photo, set a new one or clear it
  if (formData.value.mainPhoto && formData.value.mainPhoto.id === photoId) {
    formData.value.mainPhoto = formData.value.photos.length > 0 ? formData.value.photos[0] : null
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  event.stopPropagation()
}

const handleDrop = (event) => {
  event.preventDefault()
  event.stopPropagation()
  const files = Array.from(event.dataTransfer.files)
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        const photoData = {
          id: Date.now() + Math.random(),
          file: file,
          preview: e.target.result
        }
        formData.value.photos.push(photoData)
        if (!formData.value.mainPhoto) {
          formData.value.mainPhoto = photoData
        }
      }
      reader.readAsDataURL(file)
    }
  })
}

// Load property data for editing
const loadPropertyForEdit = async () => {
  if (!isEditMode.value || !propertyId.value) return
  
  isLoadingProperty.value = true
  try {
    const response = await api.get(`/properties/${propertyId.value}`)
    
    if (response.data.success && response.data.property) {
      const prop = response.data.property
      
      // Populate form with existing property data
      formData.value.name = prop.name || ''
      formData.value.description = prop.description || ''
      formData.value.type = prop.type || 'residential'
      formData.value.streetAddress = prop.street_address || ''
      formData.value.city = prop.city || ''
      formData.value.state = prop.state || ''
      formData.value.zipCode = prop.zip_code || ''
      selectedType.value = prop.type || 'residential'
      
      // Load existing photos
      const allPhotos = []
      
      if (prop.main_photo) {
        const mainPhotoObj = {
          id: 'main',
          preview: prop.main_photo,
          isExisting: true
        }
        formData.value.mainPhoto = mainPhotoObj
        allPhotos.push(mainPhotoObj)
      }
      
      if (prop.photos && Array.isArray(prop.photos)) {
        // Add other photos (excluding main photo if it's already in the array)
        prop.photos.forEach((photo, index) => {
          // Only add if it's not the main photo
          if (photo !== prop.main_photo) {
            allPhotos.push({
              id: `existing-${index}`,
              preview: photo,
              isExisting: true
            })
          }
        })
      }
      
      formData.value.photos = allPhotos
    }
  } catch (error) {
    console.error('Error loading property:', error)
    alert('Failed to load property data. Please try again.')
    router.push('/landlord/properties')
  } finally {
    isLoadingProperty.value = false
  }
}

const submitProperty = async () => {
  // Validate all required fields
  if (!formData.value.name || formData.value.name.trim() === '') {
    alert('Please enter a property name')
    goToStep(2)
    return
  }
  if (!formData.value.streetAddress || formData.value.streetAddress.trim() === '') {
    alert('Please enter a street address')
    goToStep(3)
    return
  }
  if (!formData.value.city || formData.value.city.trim() === '') {
    alert('Please enter a city')
    goToStep(3)
    return
  }
  if (!formData.value.state || formData.value.state.trim() === '') {
    alert('Please enter a state')
    goToStep(3)
    return
  }
  if (!formData.value.zipCode || formData.value.zipCode.trim() === '') {
    alert('Please enter a zip code')
    goToStep(3)
    return
  }
  
  try {
    // Prepare photos array - send all photos (both existing URLs and new base64)
    const photos = formData.value.photos && formData.value.photos.length > 0
      ? formData.value.photos
          .filter(photo => photo.preview)
          .map(photo => {
            // If it's a new photo (base64), send as is. If existing (URL), send as is for now
            // Backend will handle URL vs base64 detection
            return photo.preview
          })
      : []
    
    // Main photo - send if exists (either new base64 or existing URL)
    const mainPhoto = formData.value.mainPhoto && formData.value.mainPhoto.preview
      ? formData.value.mainPhoto.preview
      : null
    
    if (isEditMode.value && propertyId.value) {
      // Update existing property
      const updateData = {
        name: formData.value.name.trim(),
        description: formData.value.description ? formData.value.description.trim() : null,
        type: formData.value.type,
        street_address: formData.value.streetAddress.trim(),
        city: formData.value.city.trim(),
        state: formData.value.state.trim(),
        zip_code: formData.value.zipCode.trim(),
      }
      
      // Include photos - send all current photos (existing + new)
      if (mainPhoto) {
        updateData.main_photo = mainPhoto
      }
      if (photos.length > 0) {
        updateData.photos = photos
      }
      
      const response = await api.put(`/properties/${propertyId.value}`, updateData)
      
      if (response.data.success) {
        alert('Property updated successfully!')
        router.push(`/landlord/prop-${propertyId.value}`)
      } else {
        alert('Failed to update property. Please try again.')
      }
    } else {
      // Create new property
      const response = await api.post('/properties', {
        name: formData.value.name.trim(),
        description: formData.value.description ? formData.value.description.trim() : null,
        type: formData.value.type,
        street_address: formData.value.streetAddress.trim(),
        city: formData.value.city.trim(),
        state: formData.value.state.trim(),
        zip_code: formData.value.zipCode.trim(),
        photos: photos,
        main_photo: mainPhoto,
      })

      if (response.data.success) {
        alert('Property created successfully!')
        router.push('/landlord/properties')
      } else {
        alert('Failed to create property. Please try again.')
      }
    }
  } catch (error) {
    console.error(`Error ${isEditMode.value ? 'updating' : 'creating'} property:`, error)
    if (error.response && error.response.data) {
      const errors = error.response.data.errors || error.response.data
      const errorMessage = errors.message || Object.values(errors).flat().join(', ')
      alert(`Error: ${errorMessage}`)
    } else {
      alert(`An error occurred while ${isEditMode.value ? 'updating' : 'creating'} the property. Please try again.`)
    }
  }
}

// Load property data when component mounts in edit mode
onMounted(() => {
  if (isEditMode.value) {
    loadPropertyForEdit()
  }
})
</script>

<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <!-- Header -->
      <div class="page-header">
        <h1 class="page-title">{{ isEditMode ? 'Edit Property' : 'Add New Property' }}</h1>
        <p class="page-subtitle">{{ isEditMode ? 'Update the property details below.' : 'Fill in the details below to list your property.' }}</p>
      </div>
      
      <!-- Loading State for Edit Mode -->
      <div v-if="isEditMode && isLoadingProperty" class="loading-state">
        <p>Loading property data...</p>
      </div>

      <!-- Form Content -->
      <template v-if="!isEditMode || !isLoadingProperty">
        <!-- Progress Stepper -->
        <div class="stepper-container">
        <div class="stepper">
          <template v-for="(step, index) in steps" :key="step.number">
            <div class="stepper-item">
              <div
                :class="[
                  'stepper-circle',
                  {
                    active: currentStep === step.number,
                    completed: currentStep > step.number
                  }
                ]"
              >
                <span v-if="currentStep > step.number" class="checkmark">✓</span>
                <span v-else>{{ step.number }}</span>
              </div>
              <div
                :class="[
                  'stepper-label',
                  {
                    active: currentStep === step.number
                  }
                ]"
              >
                {{ step.label }}
              </div>
            </div>
            <div
              v-if="index < steps.length - 1"
              :class="[
                'stepper-line',
                {
                  active: currentStep > step.number
                }
              ]"
            ></div>
          </template>
        </div>
      </div>

      <!-- Form Card -->
      <div class="form-card">
        <!-- Step 1: Type Selection -->
        <div v-if="currentStep === 1" class="step-content">
          <div class="step-header">
            <h2 class="step-title">What type of property is this?</h2>
            <p class="step-subtitle">Select the category that best fits your property.</p>
          </div>

          <div class="type-cards">
            <div
              :class="['type-card', { selected: selectedType === 'residential' }]"
              @click="selectType('residential')"
            >
              <div v-if="selectedType === 'residential'" class="selected-indicator"></div>
              <HomeIcon class="type-icon" />
              <h3 class="type-title">Residential</h3>
              <p class="type-description">Single family homes, apartments, condos</p>
            </div>

            <div
              :class="['type-card', { selected: selectedType === 'commercial' }]"
              @click="selectType('commercial')"
            >
              <div v-if="selectedType === 'commercial'" class="selected-indicator"></div>
              <BuildingOfficeIcon class="type-icon" />
              <h3 class="type-title">Commercial</h3>
              <p class="type-description">Office spaces, retail stores, warehouses</p>
            </div>
          </div>
        </div>

        <!-- Step 2: Details -->
        <div v-if="currentStep === 2" class="step-content">
          <div class="step-header">
            <h2 class="step-title">Property Details</h2>
            <p class="step-subtitle">Tell us more about the property's features.</p>
          </div>

          <div class="form-fields">
            <div class="form-field">
              <label for="name" class="field-label">Name <span class="required-asterisk">*</span></label>
              <input
                id="name"
                v-model="formData.name"
                type="text"
                class="form-input"
                placeholder="Enter property name"
                @input="handleNameInput"
                required
              />
            </div>

            <div class="form-field">
              <label for="description" class="field-label">Description</label>
              <textarea
                id="description"
                v-model="formData.description"
                class="form-textarea"
                rows="6"
                placeholder="Enter property description"
                @input="handleDescriptionInput"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Step 3: Location -->
        <div v-if="currentStep === 3" class="step-content">
          <div class="step-header">
            <h2 class="step-title">Location</h2>
            <p class="step-subtitle">Where is the property located?</p>
          </div>

          <div class="info-box">
            <MapPinIcon class="info-icon" />
            <p class="info-text">This address will be used to display the property on the map. Please ensure it is accurate.</p>
          </div>

          <div class="form-fields">
            <div class="form-field">
              <label for="streetAddress" class="field-label">Street Address <span class="required-asterisk">*</span></label>
              <input
                id="streetAddress"
                v-model="formData.streetAddress"
                type="text"
                class="form-input"
                placeholder="Enter street address"
                @input="handleStreetAddressInput"
                required
              />
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="city" class="field-label">City <span class="required-asterisk">*</span></label>
                <input
                  id="city"
                  v-model="formData.city"
                  type="text"
                  class="form-input"
                  placeholder="Enter city"
                  @input="handleCityInput"
                  required
                />
              </div>

              <div class="form-field">
                <label for="state" class="field-label">State <span class="required-asterisk">*</span></label>
                <input
                  id="state"
                  v-model="formData.state"
                  type="text"
                  class="form-input"
                  placeholder="Enter state"
                  @input="handleStateInput"
                  required
                />
              </div>
            </div>

            <div class="form-field">
              <label for="zipCode" class="field-label">Zip Code <span class="required-asterisk">*</span></label>
              <input
                id="zipCode"
                v-model="formData.zipCode"
                type="text"
                class="form-input"
                placeholder="Enter zip code"
                @input="handleZipCodeInput"
                required
              />
            </div>
          </div>
        </div>

        <!-- Step 4: Photos -->
        <div v-if="currentStep === 4" class="step-content">
          <div class="step-header">
            <h2 class="step-title">Property Photos</h2>
            <p class="step-subtitle">Upload high-quality photos to attract more interest.</p>
          </div>

          <div
            class="upload-area"
            @dragover="handleDragOver"
            @drop="handleDrop"
          >
            <ArrowUpTrayIcon class="upload-icon" />
            <p class="upload-text">Click to upload or drag and drop</p>
            <p class="upload-hint">PNG or JPG (max. 800×400px)</p>
            <input
              type="file"
              id="file-upload"
              class="file-input"
              multiple
              accept="image/*"
              @change="handleFileUpload"
            />
            <label for="file-upload" class="select-files-btn">Select Files</label>
          </div>

          <div v-if="formData.photos.length > 0 || formData.mainPhoto" class="photos-preview">
            <div class="main-photo-section">
              <div class="photo-label">Main Photo</div>
              <div class="main-photo-container">
                <img
                  v-if="formData.mainPhoto"
                  :src="formData.mainPhoto.preview"
                  alt="Main photo"
                  class="main-photo"
                />
                <div v-else class="main-photo-placeholder">
                  <span class="placeholder-x">×</span>
                </div>
                <button
                  v-if="formData.mainPhoto"
                  @click="removePhoto(formData.mainPhoto.id)"
                  class="remove-photo-btn"
                  title="Remove main photo"
                >
                  ×
                </button>
              </div>
            </div>
            
            <div v-if="formData.photos.length > 0" class="photos-gallery">
              <div class="photo-label">All Photos</div>
              <div class="photos-grid">
                <div
                  v-for="photo in formData.photos"
                  :key="photo.id"
                  class="photo-item"
                  :class="{ 'is-main': formData.mainPhoto && formData.mainPhoto.id === photo.id }"
                >
                  <img
                    :src="photo.preview"
                    alt="Property photo"
                    class="photo-thumbnail"
                  />
                  <div class="photo-actions">
                    <button
                      v-if="formData.mainPhoto && formData.mainPhoto.id !== photo.id"
                      @click="setMainPhoto(photo)"
                      class="photo-action-btn set-main-btn"
                      title="Set as main photo"
                    >
                      ⭐
                    </button>
                    <button
                      @click="removePhoto(photo.id)"
                      class="photo-action-btn remove-btn"
                      title="Remove photo"
                    >
                      ×
                    </button>
                  </div>
                  <div v-if="formData.mainPhoto && formData.mainPhoto.id === photo.id" class="main-badge">
                    Main
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 5: Review -->
        <div v-if="currentStep === 5" class="step-content">
          <div class="step-header">
            <h2 class="step-title">Review Property</h2>
            <p class="step-subtitle">Review your listing details before publishing.</p>
          </div>

          <div class="review-sections">
            <!-- Property Type Section -->
            <div class="review-section">
              <div class="review-section-header">
                <div class="review-section-title">
                  <HomeIcon class="review-icon" />
                  <span>Property Type</span>
                </div>
                <button class="edit-button" @click="goToStep(1)">
                  <PencilIcon class="edit-icon" />
                  <span>Edit</span>
                </button>
              </div>
              <div class="review-content">
                <span class="review-value">{{ formData.type || 'Not provided' }}</span>
              </div>
            </div>

            <!-- Details Section -->
            <div class="review-section">
              <div class="review-section-header">
                <div class="review-section-title">
                  <HomeIcon class="review-icon" />
                  <span>Details</span>
                </div>
                <button class="edit-button" @click="goToStep(2)">
                  <PencilIcon class="edit-icon" />
                  <span>Edit</span>
                </button>
              </div>
              <div class="review-content">
                <div class="review-item">
                  <span class="review-label">Name:</span>
                  <span class="review-value">{{ formData.name || 'Not provided' }}</span>
                </div>
                <div class="review-item">
                  <span class="review-label">Description:</span>
                  <span class="review-value">{{ formData.description || 'Not provided' }}</span>
                </div>
              </div>
            </div>

            <!-- Location Section -->
            <div class="review-section">
              <div class="review-section-header">
                <div class="review-section-title">
                  <MapPinIcon class="review-icon" />
                  <span>Location</span>
                </div>
                <button class="edit-button" @click="goToStep(3)">
                  <PencilIcon class="edit-icon" />
                  <span>Edit</span>
                </button>
              </div>
              <div class="review-content">
                <div class="review-item">
                  <span class="review-value">{{ formData.streetAddress || 'Not provided' }}</span>
                </div>
                <div class="review-item">
                  <span class="review-value">{{ [formData.city, formData.state, formData.zipCode].filter(Boolean).join(', ') || 'Not provided' }}</span>
                </div>
              </div>
            </div>

            <!-- Photos Section -->
            <div class="review-section">
              <div class="review-section-header">
                <div class="review-section-title">
                  <PhotoIcon class="review-icon" />
                  <span>Photos ({{ formData.photos.length }})</span>
                </div>
                <button class="edit-button" @click="goToStep(4)">
                  <PencilIcon class="edit-icon" />
                  <span>Edit</span>
                </button>
              </div>
              <div class="review-content">
                <div v-if="formData.mainPhoto" class="review-photo">
                  <img :src="formData.mainPhoto.preview" alt="Property photo" />
                </div>
                <div v-else class="review-photo-placeholder"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="form-navigation">
          <button class="nav-button back-button" @click="goToPreviousStep">
            <ChevronLeftIcon class="nav-icon" />
            <span>Back</span>
          </button>
          <button
            v-if="currentStep < steps.length"
            class="nav-button next-button"
            @click="goToNextStep"
          >
            <span>Next Step</span>
            <ChevronRightIcon class="nav-icon" />
          </button>
          <button
            v-if="currentStep === steps.length"
            class="nav-button next-button"
            @click="submitProperty"
          >
            <span>{{ isEditMode ? 'Update Property' : 'Publish Property' }}</span>
          </button>
        </div>
      </div>
      </template>
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

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
  font-size: 16px;
  font-weight: 400;
  color: #666;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
}

.stepper-container {
  display: flex;
  justify-content: center;
  margin-bottom: 40px;
}

.stepper {
  display: flex;
  align-items: flex-start;
  gap: 0;
  position: relative;
}

.stepper-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex-shrink: 0;
}

.stepper-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
  background: #f5f5f5;
  border: 2px solid #ddd;
  color: #999;
  position: relative;
  z-index: 2;
  transition: all 0.3s;
}

.stepper-circle.active {
  background: #e6f0ff;
  border-color: #1500FF;
  color: #1500FF;
}

.stepper-circle.completed {
  background: #1500FF;
  border-color: #1500FF;
  color: white;
}

.checkmark {
  font-size: 20px;
  font-weight: bold;
}

.stepper-label {
  margin-top: 8px;
  font-size: 14px;
  font-weight: 400;
  color: #999;
  transition: all 0.3s;
}

.stepper-label.active {
  font-weight: 600;
  color: #1500FF;
}

.stepper-line {
  width: 100px;
  height: 2px;
  background: #ddd;
  margin-top: 20px;
  margin-left: -20px;
  margin-right: -20px;
  flex-shrink: 0;
  transition: all 0.3s;
}

.stepper-line.active {
  background: #1500FF;
}

.form-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 40px;
  max-width: 800px;
  margin: 0 auto;
}

.step-content {
  min-height: 400px;
}

.step-header {
  text-align: center;
  margin-bottom: 40px;
}

.step-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  font-family: 'Montserrat', sans-serif;
}

.step-subtitle {
  font-size: 16px;
  font-weight: 400;
  color: #666;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
}

.type-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 40px;
}

.type-card {
  position: relative;
  padding: 32px 24px;
  border: 2px solid #e5e5e5;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
}

.type-card:hover {
  border-color: #1500FF;
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.1);
}

.type-card.selected {
  background: #e6f0ff;
  border-color: #1500FF;
}

.selected-indicator {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #1500FF;
}

.type-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px;
  color: #1500FF;
  stroke-width: 1.5;
}

.type-card:not(.selected) .type-icon {
  color: #999;
}

.type-title {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  font-family: 'Montserrat', sans-serif;
}

.type-card.selected .type-title {
  color: #1500FF;
}

.type-description {
  font-size: 14px;
  font-weight: 400;
  color: #666;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
}

.form-navigation {
  display: flex;
  justify-content: space-between;
  margin-top: 40px;
  padding-top: 24px;
  border-top: 1px solid #e5e5e5;
}

.nav-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.back-button {
  background: transparent;
  color: #666;
  border: 1px solid #ddd;
}

.back-button:hover {
  background: #f5f5f5;
  border-color: #bbb;
}

.next-button {
  background: #1500FF;
  color: white;
}

.next-button:hover {
  background: #0f00cc;
}

.nav-icon {
  width: 20px;
  height: 20px;
}

/* Review Section Styles */
.review-sections {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.review-section {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  padding: 20px 24px;
}

.review-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.review-section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
}

.review-icon {
  width: 20px;
  height: 20px;
  color: #666;
}

.edit-button {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  color: #1500FF;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
  font-family: 'Montserrat', sans-serif;
}

.edit-button:hover {
  background: #f0f0ff;
}

.edit-icon {
  width: 16px;
  height: 16px;
  color: #1500FF;
}

.review-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.review-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.review-item:not(:first-child) {
  margin-top: 8px;
}

.review-label {
  font-size: 14px;
  font-weight: 400;
  color: #999;
  font-family: 'Montserrat', sans-serif;
}

.review-value {
  font-size: 14px;
  font-weight: 400;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
  text-transform: capitalize;
  display: block;
}

.review-photo {
  width: 100%;
  max-width: 300px;
  height: 200px;
  border-radius: 8px;
  overflow: hidden;
  background: #f5f5f5;
}

.review-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.review-photo-placeholder {
  width: 100%;
  max-width: 300px;
  height: 200px;
  border-radius: 8px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e5e5e5;
}

/* Form Fields Styles */
.form-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.field-label {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  font-family: 'Montserrat', sans-serif;
}

.required-asterisk {
  color: #ef4444;
  font-weight: 600;
}

.form-input,
.form-textarea {
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
  color: #1a1a1a;
  transition: border-color 0.2s;
  background: white;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #1500FF;
}

.form-textarea {
  resize: vertical;
  min-height: 120px;
}

/* Info Box Styles */
.info-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #e6f0ff;
  border-radius: 8px;
  margin-bottom: 24px;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: #1500FF;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-text {
  font-size: 14px;
  font-weight: 400;
  color: #1500FF;
  margin: 0;
  font-family: 'Montserrat', sans-serif;
  line-height: 1.5;
}

/* Upload Area Styles */
.upload-area {
  position: relative;
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 48px 24px;
  text-align: center;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.3s;
  margin-bottom: 24px;
}

.upload-area:hover {
  border-color: #1500FF;
  background: #f0f0ff;
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.upload-icon {
  width: 48px;
  height: 48px;
  color: #1500FF;
  margin: 0 auto 16px;
}

.upload-text {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  font-family: 'Montserrat', sans-serif;
}

.upload-hint {
  font-size: 14px;
  font-weight: 400;
  color: #666;
  margin: 0 0 16px 0;
  font-family: 'Montserrat', sans-serif;
}

.select-files-btn {
  display: inline-block;
  padding: 10px 24px;
  background: #666;
  color: white;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  font-family: 'Montserrat', sans-serif;
  transition: background-color 0.2s;
}

.select-files-btn:hover {
  background: #555;
}

.photos-preview {
  margin-top: 24px;
}

.main-photo-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.photo-label {
  font-size: 14px;
  font-weight: 600;
  color: #1500FF;
  font-family: 'Montserrat', sans-serif;
}

.main-photo-container {
  width: 200px;
  height: 200px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e5e5;
  position: relative;
}

.main-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-photo-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(220, 38, 38, 0.9);
  color: white;
  border: none;
  cursor: pointer;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
  font-weight: bold;
}

.remove-photo-btn:hover {
  background: rgba(220, 38, 38, 1);
}

.photos-gallery {
  margin-top: 24px;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 16px;
  margin-top: 12px;
}

.photo-item {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e5e5e5;
  cursor: pointer;
  transition: border-color 0.2s;
}

.photo-item.is-main {
  border-color: #2563eb;
}

.photo-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-actions {
  position: absolute;
  top: 0;
  right: 0;
  display: flex;
  gap: 4px;
  padding: 4px;
  opacity: 0;
  transition: opacity 0.2s;
}

.photo-item:hover .photo-actions {
  opacity: 1;
}

.photo-action-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
  font-weight: bold;
}

.set-main-btn {
  background: rgba(37, 99, 235, 0.9);
  color: white;
}

.set-main-btn:hover {
  background: rgba(37, 99, 235, 1);
  transform: scale(1.1);
}

.remove-btn {
  background: rgba(220, 38, 38, 0.9);
  color: white;
}

.remove-btn:hover {
  background: rgba(220, 38, 38, 1);
  transform: scale(1.1);
}

.main-badge {
  position: absolute;
  bottom: 4px;
  left: 4px;
  background: rgba(37, 99, 235, 0.9);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.main-photo-placeholder {
  width: 100%;
  height: 100%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-x {
  font-size: 48px;
  color: #ddd;
  font-weight: 300;
}
</style>
