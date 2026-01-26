<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import {
  ArrowLeftIcon,
  BuildingOfficeIcon,
  ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'
import api from '../services/api.js'

const route = useRoute()
const router = useRouter()

const property = ref(null)
const existingUnits = ref([])
const isLoadingProperty = ref(true)
const isSubmitting = ref(false)
const error = ref(null)
const fieldErrors = ref({
  unit_number: false,
  monthly_rent: false,
  description: false,
  photos: false,
  duplicate_unit: false
})

const formData = ref({
  unit_number: '',
  unit_type: 'apartment',
  bedrooms: 1,
  bathrooms: 1,
  square_footage: '',
  monthly_rent: '',
  security_deposit: '',
  advance_deposit: '',
  description: '',
  photos: [],
  status: 'active',
  is_occupied: false,
  amenities: [],
})

const unitTypes = [
  { value: 'studio', label: 'Studio' },
  { value: 'apartment', label: 'Apartment' },
  { value: '1br', label: '1 Bedroom' },
  { value: '2br', label: '2 Bedroom' },
  { value: '3br', label: '3 Bedroom' },
  { value: '4br', label: '4+ Bedroom' },
  { value: 'penthouse', label: 'Penthouse' },
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'vacant', label: 'Vacant' },
]

const amenitiesOptions = [
  { value: 'parking', label: 'Parking' },
  { value: 'air_conditioning', label: 'Air Conditioning' },
  { value: 'heating', label: 'Heating' },
  { value: 'washer_dryer', label: 'Washer/Dryer' },
  { value: 'dishwasher', label: 'Dishwasher' },
  { value: 'balcony', label: 'Balcony' },
  { value: 'gym', label: 'Gym Access' },
  { value: 'pool', label: 'Pool Access' },
  { value: 'elevator', label: 'Elevator' },
  { value: 'pet_friendly', label: 'Pet Friendly' },
  { value: 'furnished', label: 'Furnished' },
  { value: 'wifi', label: 'WiFi Included' },
]

const propertyId = computed(() => route.params.id)
const unitId = computed(() => route.params.unitId)
const isEditMode = computed(() => !!unitId.value)

// Sanitize helpers (block special characters)
const sanitizeText = (v) => (v || '').replace(/[<>{}[\]\\|`~!@#$%^&*()_+=]/g, '')
const sanitizeAlphanumeric = (v) => (v || '').replace(/[^a-zA-Z0-9\s\-'.,#\/]/g, '')
const sanitizeNumber = (v) => (v || '').replace(/[^0-9]/g, '')

const handleUnitNumberInput = (e) => {
  formData.value.unit_number = sanitizeAlphanumeric(e.target.value)
  // Clear error when user starts typing
  if (fieldErrors.value.unit_number || fieldErrors.value.duplicate_unit) {
    fieldErrors.value.unit_number = false
    fieldErrors.value.duplicate_unit = false
  }
}

const handleDescriptionInput = (e) => {
  formData.value.description = sanitizeText(e.target.value)
  // Clear error when user starts typing
  if (fieldErrors.value.description) {
    fieldErrors.value.description = false
  }
}

const handleSquareFootageInput = (e) => {
  formData.value.square_footage = sanitizeNumber(e.target.value)
}

const handleRentInput = (e) => {
  formData.value.monthly_rent = sanitizeNumber(e.target.value)
  // Clear error when user starts typing
  if (fieldErrors.value.monthly_rent) {
    fieldErrors.value.monthly_rent = false
  }
}

const handleSecurityDepositInput = (e) => {
  formData.value.security_deposit = sanitizeNumber(e.target.value)
}

const handleAdvanceDepositInput = (e) => {
  formData.value.advance_deposit = sanitizeNumber(e.target.value)
}

// Fetch property
const fetchProperty = async () => {
  if (!propertyId.value) return
  isLoadingProperty.value = true
  error.value = null
  try {
    const res = await api.get(`/properties/${propertyId.value}`)
    if (res.data.success && res.data.property) {
      property.value = res.data.property
      // Fetch existing units to check for duplicates
      await fetchExistingUnits()
      // If edit mode, fetch unit data
      if (isEditMode.value) {
        await fetchUnitData()
      }
    } else {
      error.value = 'Property not found.'
    }
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load property. Please try again.'
  } finally {
    isLoadingProperty.value = false
  }
}

// Fetch unit data for editing
const fetchUnitData = async () => {
  if (!unitId.value || !propertyId.value) return
  try {
    const res = await api.get(`/properties/${propertyId.value}/units/${unitId.value}`)
    if (res.data.success && res.data.unit) {
      const unit = res.data.unit
      // Pre-populate form with unit data
      formData.value.unit_number = unit.unit_number || ''
      formData.value.unit_type = unit.unit_type || 'apartment'
      formData.value.bedrooms = unit.bedrooms || 1
      formData.value.bathrooms = unit.bathrooms || 1
      formData.value.square_footage = unit.square_footage ? String(unit.square_footage) : ''
      formData.value.monthly_rent = unit.monthly_rent ? String(unit.monthly_rent) : ''
      formData.value.security_deposit = unit.security_deposit ? String(unit.security_deposit) : ''
      formData.value.advance_deposit = unit.advance_deposit ? String(unit.advance_deposit) : ''
      formData.value.description = unit.description || ''
      formData.value.status = unit.status || 'active'
      formData.value.is_occupied = unit.is_occupied || false
      formData.value.amenities = unit.amenities || []
      
      // Load existing photos
      if (unit.photos && unit.photos.length > 0) {
        photoPreviews.value = unit.photos.map((photo, index) => ({
          id: `existing_${index}`,
          preview: photo,
          isExisting: true
        }))
      }
    } else {
      error.value = 'Unit not found.'
    }
  } catch (err) {
    console.error('Error fetching unit:', err)
    error.value = 'Failed to load unit data. Please try again.'
  }
}

// Fetch existing units for duplicate checking
const fetchExistingUnits = async () => {
  try {
    const res = await api.get(`/properties/${propertyId.value}/units`)
    if (res.data.success && res.data.units) {
      if (isEditMode.value) {
        // Store full unit objects in edit mode to check by ID
        existingUnits.value = res.data.units
      } else {
        // Store just unit numbers in create mode
        existingUnits.value = res.data.units.map(unit => unit.unit_number?.toLowerCase().trim())
      }
    }
  } catch (err) {
    console.error('Error fetching existing units:', err)
  }
}

// Photo upload (min 4, max 10)
const PHOTO_MIN = 4
const PHOTO_MAX = 10
const photoPreviews = ref([])

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files || [])
  const remaining = PHOTO_MAX - photoPreviews.value.length
  if (remaining <= 0) return
  const allowed = ['image/jpeg', 'image/png']
  const toAdd = files.filter((f) => allowed.includes(f.type)).slice(0, remaining)
  toAdd.forEach((file) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      if (photoPreviews.value.length >= PHOTO_MAX) return
      photoPreviews.value.push({
        id: Date.now() + Math.random(),
        preview: e.target.result,
      })
      // Clear photo error when photos are added
      if (fieldErrors.value.photos && photoPreviews.value.length >= PHOTO_MIN && photoPreviews.value.length <= PHOTO_MAX) {
        fieldErrors.value.photos = false
      }
    }
    reader.readAsDataURL(file)
  })
  event.target.value = ''
}

const removePhoto = (id) => {
  photoPreviews.value = photoPreviews.value.filter((p) => p.id !== id)
}

// Submit
const submitUnit = async () => {
  const d = formData.value
  
  // Reset all field errors
  fieldErrors.value = {
    unit_number: false,
    monthly_rent: false,
    description: false,
    photos: false,
    duplicate_unit: false
  }

  let hasError = false

  if (!d.unit_number?.trim()) {
    fieldErrors.value.unit_number = true
    hasError = true
  }
  
  // Check for duplicate unit number (exclude current unit in edit mode)
  const unitNumberTrimmed = d.unit_number?.trim().toLowerCase()
  if (unitNumberTrimmed) {
    if (isEditMode.value) {
      // In edit mode, check if another unit has this number
      const currentUnit = existingUnits.value.find(u => u.id === parseInt(unitId.value))
      const otherUnits = existingUnits.value.filter(u => u.id !== parseInt(unitId.value))
      const otherUnitNumbers = otherUnits.map(u => u.unit_number?.toLowerCase().trim())
      if (otherUnitNumbers.includes(unitNumberTrimmed)) {
        fieldErrors.value.duplicate_unit = true
        fieldErrors.value.unit_number = true
        hasError = true
      }
    } else {
      // In create mode, check all units
      if (existingUnits.value.includes(unitNumberTrimmed)) {
        fieldErrors.value.duplicate_unit = true
        fieldErrors.value.unit_number = true
        hasError = true
      }
    }
  }
  
  if (!d.monthly_rent || parseInt(d.monthly_rent, 10) < 0) {
    fieldErrors.value.monthly_rent = true
    hasError = true
  }
  
  if (!d.description?.trim()) {
    fieldErrors.value.description = true
    hasError = true
  }
  
  const photoCount = photoPreviews.value.length
  if (photoCount < PHOTO_MIN) {
    fieldErrors.value.photos = true
    hasError = true
  }
  if (photoCount > PHOTO_MAX) {
    fieldErrors.value.photos = true
    hasError = true
  }
  
  if (hasError) {
    // Scroll to first error
    const firstErrorField = document.querySelector('.form-input.error, .form-textarea.error')
    if (firstErrorField) {
      firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
      firstErrorField.focus()
    }
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    // Prepare photos - keep existing URLs and new base64 images
    const photos = photoPreviews.value.map((p) => p.preview).filter(Boolean)
    
    const payload = {
      unit_number: d.unit_number.trim(),
      unit_type: d.unit_type,
      bedrooms: parseInt(d.bedrooms, 10) || 0,
      bathrooms: parseInt(d.bathrooms, 10) || 0,
      square_footage: d.square_footage ? parseInt(d.square_footage, 10) : null,
      monthly_rent: parseInt(d.monthly_rent, 10),
      security_deposit: parseInt(d.security_deposit, 10) || 0,
      advance_deposit: parseInt(d.advance_deposit, 10) || 0,
      description: d.description.trim(),
      photos: photos,
      status: d.status,
      is_occupied: !!d.is_occupied,
      amenities: d.amenities || [],
    }

    let res
    if (isEditMode.value) {
      // Update existing unit
      res = await api.put(`/properties/${propertyId.value}/units/${unitId.value}`, payload)
      if (res.data.success) {
        alert('Unit updated successfully!')
        router.push(`/landlord/prop-${propertyId.value}/units/${unitId.value}`)
      } else {
        error.value = res.data.message || 'Failed to update unit.'
      }
    } else {
      // Create new unit
      payload.property_id = parseInt(propertyId.value, 10)
      res = await api.post('/units', payload)
      if (res.data.success) {
        alert('Unit added successfully!')
        router.push(`/landlord/prop-${propertyId.value}`)
      } else {
        error.value = res.data.message || 'Failed to add unit.'
      }
    }
  } catch (err) {
    console.error(err)
    let msg =
      err.response?.data?.message ||
      err.response?.data?.errors?.unit_number?.[0] ||
      (isEditMode.value ? 'Failed to update unit. Please try again.' : 'Failed to add unit. Please try again.')
    
    // Check if error is about duplicate unit number
    if (msg.toLowerCase().includes('unit') && (msg.toLowerCase().includes('already') || msg.toLowerCase().includes('exists') || msg.toLowerCase().includes('duplicate'))) {
      alert(`Unit number "${d.unit_number.trim()}" already exists for this property. Please use a different unit number.`)
    } else {
      error.value = msg
    }
  } finally {
    isSubmitting.value = false
  }
}

const handleBack = () => {
  router.push(`/landlord/prop-${propertyId.value}`)
}

onMounted(() => {
  fetchProperty()
})
</script>

<template>
  <div class="page-container">
    <Sidebar />

    <div class="main-content">
      <div class="content-wrapper">
        <button class="back-button" @click="handleBack">
          <ArrowLeftIcon class="back-icon" />
          <span class="back-text">Back</span>
        </button>

        <div v-if="isLoadingProperty" class="loading-state">
          <p>Loading...</p>
        </div>

        <div v-else-if="error && !property" class="error-state">
          <p class="error-text">{{ error }}</p>
          <button class="retry-button" @click="fetchProperty">Retry</button>
        </div>

        <div v-else-if="property" class="form-container">
          <div class="page-header">
            <h1 class="page-title">{{ isEditMode ? 'Edit Unit' : 'Add Unit' }}</h1>
            <p class="page-subtitle">
              {{ isEditMode ? 'Edit unit details for' : 'Add a new unit to' }} <strong>{{ property.name }}</strong>
            </p>
          </div>

          <form class="unit-form" @submit.prevent="submitUnit">
            <div v-if="error" class="form-error">{{ error }}</div>

            <!-- Basic Information Section -->
            <div class="form-section">
              <h2 class="section-title">Basic Information</h2>
              <div class="form-grid">
                <div class="form-field">
                  <label for="unit_number" class="field-label">Unit Number <span class="required">*</span></label>
                  <input
                    id="unit_number"
                    v-model="formData.unit_number"
                    type="text"
                    class="form-input"
                    placeholder="e.g. 101, A1"
                    @input="handleUnitNumberInput"
                  />
                </div>

                <div class="form-field">
                  <label for="unit_type" class="field-label">Unit Type</label>
                  <select id="unit_type" v-model="formData.unit_type" class="form-input form-select">
                    <option v-for="opt in unitTypes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>

                <div class="form-field">
                  <label class="field-label">Status</label>
                  <select v-model="formData.status" class="form-input form-select">
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>

                <div class="form-field form-checkbox">
                  <input
                    id="is_occupied"
                    v-model="formData.is_occupied"
                    type="checkbox"
                    class="form-checkbox-input"
                  />
                  <label for="is_occupied" class="form-checkbox-label">Unit is currently occupied</label>
                </div>
              </div>
            </div>

            <!-- Unit Details Section -->
            <div class="form-section">
              <h2 class="section-title">Unit Details</h2>
              <div class="form-grid">
                <div class="form-field">
                  <label for="bedrooms" class="field-label">Bedrooms</label>
                  <input
                    id="bedrooms"
                    v-model.number="formData.bedrooms"
                    type="number"
                    min="0"
                    class="form-input"
                  />
                </div>

                <div class="form-field">
                  <label for="bathrooms" class="field-label">Bathrooms</label>
                  <input
                    id="bathrooms"
                    v-model.number="formData.bathrooms"
                    type="number"
                    min="0"
                    class="form-input"
                  />
                </div>

                <div class="form-field">
                  <label for="square_footage" class="field-label">Square Footage</label>
                  <input
                    id="square_footage"
                    v-model="formData.square_footage"
                    type="text"
                    inputmode="numeric"
                    class="form-input"
                    placeholder="Optional"
                    @input="handleSquareFootageInput"
                  />
                </div>
              </div>

              <div class="form-field">
                <label for="description" class="field-label">Description <span class="required">*</span></label>
                <textarea
                  id="description"
                  v-model="formData.description"
                  :class="['form-textarea', { error: fieldErrors.description }]"
                  rows="4"
                  placeholder="Brief description of the unit"
                  @input="handleDescriptionInput"
                />
                <p v-if="fieldErrors.description" class="field-error">Please enter a description.</p>
              </div>
            </div>

            <!-- Financial Section -->
            <div class="form-section">
              <h2 class="section-title">Financial</h2>
              <div class="form-grid">
                <div class="form-field">
                  <label for="monthly_rent" class="field-label">Monthly Rent <span class="required">*</span></label>
                  <input
                    id="monthly_rent"
                    v-model="formData.monthly_rent"
                    type="text"
                    inputmode="numeric"
                    :class="['form-input', { error: fieldErrors.monthly_rent }]"
                    placeholder="0"
                    @input="handleRentInput"
                  />
                  <p v-if="fieldErrors.monthly_rent" class="field-error">Please enter a valid monthly rent.</p>
                </div>

                <div class="form-field">
                  <label for="security_deposit" class="field-label">Security Deposit</label>
                  <input
                    id="security_deposit"
                    v-model="formData.security_deposit"
                    type="text"
                    inputmode="numeric"
                    class="form-input"
                    placeholder="0"
                    @input="handleSecurityDepositInput"
                  />
                </div>

                <div class="form-field">
                  <label for="advance_deposit" class="field-label">Advance Deposit</label>
                  <input
                    id="advance_deposit"
                    v-model="formData.advance_deposit"
                    type="text"
                    inputmode="numeric"
                    class="form-input"
                    placeholder="0"
                    @input="handleAdvanceDepositInput"
                  />
                </div>
              </div>
            </div>

            <!-- Amenities Section -->
            <div class="form-section">
              <h2 class="section-title">Amenities</h2>
              <div class="amenities-grid">
                <div v-for="amenity in amenitiesOptions" :key="amenity.value" class="form-field form-checkbox">
                  <input
                    :id="`amenity_${amenity.value}`"
                    v-model="formData.amenities"
                    type="checkbox"
                    :value="amenity.value"
                    class="form-checkbox-input"
                  />
                  <label :for="`amenity_${amenity.value}`" class="form-checkbox-label">{{ amenity.label }}</label>
                </div>
              </div>
            </div>

            <!-- Unit Photos Section -->
            <div class="form-section" :class="{ 'has-error': fieldErrors.photos }">
              <h2 class="section-title">Unit Photos</h2>
              <div class="form-field">
                <label class="field-label">Photos <span class="required">*</span></label>
                <p v-if="photoPreviews.length" class="photo-count-hint">
                  {{ photoPreviews.length }}/{{ PHOTO_MAX }} photos
                  <span v-if="photoPreviews.length < PHOTO_MIN" class="photo-count-warn">— add at least {{ PHOTO_MIN - photoPreviews.length }} more</span>
                </p>
                <div
                  v-if="photoPreviews.length < PHOTO_MAX"
                  :class="['upload-area', { error: fieldErrors.photos }]"
                  @dragover.prevent
                  @drop.prevent="(e) => handleFileUpload({ target: { files: e.dataTransfer.files } })"
                >
                  <ArrowUpTrayIcon class="upload-icon" />
                  <p class="upload-text">Drag and drop or click to upload (min 4, max 10)</p>
                  <p class="upload-format-hint">Supported format: JPG, PNG.</p>
                  <input
                    type="file"
                    accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                    multiple
                    class="file-input"
                    @change="handleFileUpload"
                  />
                </div>
                <p v-if="fieldErrors.photos && photoPreviews.length < PHOTO_MIN" class="field-error">Please add at least {{ PHOTO_MIN }} photos (currently {{ photoPreviews.length }}).</p>
                <p v-if="fieldErrors.photos && photoPreviews.length > PHOTO_MAX" class="field-error">Maximum {{ PHOTO_MAX }} photos allowed (currently {{ photoPreviews.length }}).</p>
                <div v-if="photoPreviews.length" class="photo-previews">
                  <div v-for="p in photoPreviews" :key="p.id" class="photo-preview-item">
                    <img :src="p.preview" alt="Preview" class="photo-preview-img" />
                    <button type="button" class="photo-remove" @click="removePhoto(p.id)">×</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions">

              <button type="submit" class="btn btn-primary form-actions-primary" :disabled="isSubmitting">
                {{ isSubmitting ? (isEditMode ? 'Updating...' : 'Adding...') : (isEditMode ? 'Update Unit' : 'Add Unit') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

* {
  font-family: 'Montserrat', sans-serif;
}

.page-container {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  margin-left: 300px;
  background: #f0f0f0;
  min-height: 100vh;
}

.content-wrapper {
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.back-button {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #4b5563;
  margin-bottom: 24px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
}

.back-button:hover {
  color: #111;
}

.back-icon {
  width: 20px;
  height: 20px;
}

.back-text {
  font-weight: 500;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 48px 20px;
}

.error-text {
  color: #dc2626;
  margin-bottom: 16px;
}

.retry-button {
  padding: 8px 16px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
}

.retry-button:hover {
  background: #1d4ed8;
}

.form-container {
  width: 100%;
  max-width: 640px;
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #ffffff;
  padding: 32px 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.page-header {
  margin-bottom: 32px;
  text-align: center;
  width: 100%;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #111;
  margin: 0 0 8px 0;
}

.page-subtitle {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

.unit-form {
  width: 100%;
}

.form-section {
  margin-bottom: 40px;
}

.form-section:last-of-type {
  margin-bottom: 0;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 20px 0;
}

.form-error {
  padding: 12px 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 8px;
  margin-bottom: 24px;
  font-size: 14px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px 24px;
  margin-bottom: 24px;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.form-field {
  margin-bottom: 20px;
}

.form-grid .form-field {
  margin-bottom: 0;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
}

.required {
  color: #dc2626;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.form-input.error:focus,
.form-select.error:focus,
.form-textarea.error:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
}

.field-error {
  color: #dc2626;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  grid-column: 1 / -1;
}

.amenities-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px 24px;
}

.amenities-grid .form-checkbox {
  margin-bottom: 0;
}

.form-checkbox-input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.form-checkbox-label {
  font-size: 14px;
  color: #374151;
  cursor: pointer;
  margin: 0;
}

.upload-area {
  position: relative;
  padding: 24px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background-color 0.2s;
}

.upload-area:hover {
  border-color: #2563eb;
  background: #f8fafc;
}

.upload-area.error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.upload-area.error:hover {
  border-color: #dc2626;
  background-color: #fee2e2;
}

.upload-icon {
  width: 40px;
  height: 40px;
  color: #9ca3af;
  margin: 0 auto 12px;
  display: block;
}

.upload-text {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 4px 0;
}

.upload-format-hint {
  font-size: 12px;
  color: #9ca3af;
  margin: 0 0 8px 0;
}

.photo-count-hint {
  font-size: 13px;
  color: #6b7280;
  margin: 0 0 10px 0;
}

.photo-count-warn {
  color: #d97706;
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
}

.photo-previews {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
}

.photo-preview-item {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.photo-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(220, 38, 38, 0.9);
  color: #fff;
  border: none;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
}

.photo-remove:hover {
  background: #dc2626;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 32px;
  justify-content: space-between;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}
</style>
