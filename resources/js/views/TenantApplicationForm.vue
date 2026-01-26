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
  name: '',
  email: '',
  phone: '',
  whatsapp: '',
  occupation: '',
  monthly_income: '',
  address: '',
  number_of_people: '',
  
  // References
  mother_name: '',
  mother_address: '',
  mother_phone: '',
  mother_email: '',
  father_name: '',
  father_address: '',
  father_phone: '',
  father_email: ''
})

const idPicturePreview = ref(null)
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

// Validate form
const validateForm = () => {
  fieldErrors.value = {}
  let hasError = false
  
  if (!formData.value.name?.trim()) {
    fieldErrors.value.name = true
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
  
  if (!formData.value.id_picture) {
    fieldErrors.value.id_picture = true
    hasError = true
  }
  
  // References validation
  if (!formData.value.mother_name?.trim()) {
    fieldErrors.value.mother_name = true
    hasError = true
  }
  
  if (!formData.value.mother_address?.trim()) {
    fieldErrors.value.mother_address = true
    hasError = true
  }
  
  if (!formData.value.mother_phone?.trim()) {
    fieldErrors.value.mother_phone = true
    hasError = true
  }
  
  if (!formData.value.mother_email?.trim()) {
    fieldErrors.value.mother_email = true
    hasError = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.mother_email)) {
    fieldErrors.value.mother_email = true
    hasError = true
  }
  
  if (!formData.value.father_name?.trim()) {
    fieldErrors.value.father_name = true
    hasError = true
  }
  
  if (!formData.value.father_address?.trim()) {
    fieldErrors.value.father_address = true
    hasError = true
  }
  
  if (!formData.value.father_phone?.trim()) {
    fieldErrors.value.father_phone = true
    hasError = true
  }
  
  if (!formData.value.father_email?.trim()) {
    fieldErrors.value.father_email = true
    hasError = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.father_email)) {
    fieldErrors.value.father_email = true
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
    
    const payload = {
      unit_id: parseInt(unitId.value, 10),
      id_picture: idPictureBase64,
      name: formData.value.name.trim(),
      email: formData.value.email.trim(),
      phone: formData.value.phone.trim(),
      whatsapp: formData.value.whatsapp.trim(),
      occupation: formData.value.occupation.trim(),
      monthly_income: parseInt(formData.value.monthly_income.replace(/[^0-9]/g, ''), 10),
      address: formData.value.address.trim(),
      number_of_people: parseInt(formData.value.number_of_people, 10),
      mother_name: formData.value.mother_name.trim(),
      mother_address: formData.value.mother_address.trim(),
      mother_phone: formData.value.mother_phone.trim(),
      mother_email: formData.value.mother_email.trim(),
      father_name: formData.value.father_name.trim(),
      father_address: formData.value.father_address.trim(),
      father_phone: formData.value.father_phone.trim(),
      father_email: formData.value.father_email.trim()
    }
    
    const res = await api.post('/tenant-applications', payload)
    
    if (res.data.success) {
      submitSuccess.value = true
    } else {
      error.value = res.data.message || 'Failed to submit application. Please try again.'
    }
  } catch (err) {
    console.error('Error submitting application:', err)
    error.value = err.response?.data?.message || 'Failed to submit application. Please try again.'
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

          <!-- Name -->
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input
              v-model="formData.name"
              type="text"
              class="form-input"
              :class="{ error: fieldErrors.name }"
              placeholder="Enter your full name"
              @input="clearFieldError('name')"
            />
            <p v-if="fieldErrors.name" class="field-error">Name is required</p>
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
        </div>

        <!-- References Section -->
        <div class="form-section">
          <h2 class="section-title">References</h2>
          
          <!-- Mother Information -->
          <div class="reference-group">
            <h3 class="reference-title">Mother's Information</h3>
            
            <div class="form-group">
              <label class="form-label">Mother's Name <span class="required">*</span></label>
              <input
                v-model="formData.mother_name"
                type="text"
                class="form-input"
                :class="{ error: fieldErrors.mother_name }"
                placeholder="Enter mother's full name"
                @input="clearFieldError('mother_name')"
              />
              <p v-if="fieldErrors.mother_name" class="field-error">Mother's name is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Mother's Address <span class="required">*</span></label>
              <textarea
                v-model="formData.mother_address"
                class="form-textarea"
                :class="{ error: fieldErrors.mother_address }"
                placeholder="Enter mother's complete address"
                rows="2"
                @input="clearFieldError('mother_address')"
              ></textarea>
              <p v-if="fieldErrors.mother_address" class="field-error">Mother's address is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Mother's Phone Number <span class="required">*</span></label>
              <input
                v-model="formData.mother_phone"
                type="tel"
                class="form-input"
                :class="{ error: fieldErrors.mother_phone }"
                placeholder="Enter mother's phone number"
                @input="clearFieldError('mother_phone')"
              />
              <p v-if="fieldErrors.mother_phone" class="field-error">Mother's phone number is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Mother's Email <span class="required">*</span></label>
              <input
                v-model="formData.mother_email"
                type="email"
                class="form-input"
                :class="{ error: fieldErrors.mother_email }"
                placeholder="Enter mother's email"
                @input="clearFieldError('mother_email')"
              />
              <p v-if="fieldErrors.mother_email" class="field-error">Valid mother's email is required</p>
            </div>
          </div>

          <!-- Father Information -->
          <div class="reference-group">
            <h3 class="reference-title">Father's Information</h3>
            
            <div class="form-group">
              <label class="form-label">Father's Name <span class="required">*</span></label>
              <input
                v-model="formData.father_name"
                type="text"
                class="form-input"
                :class="{ error: fieldErrors.father_name }"
                placeholder="Enter father's full name"
                @input="clearFieldError('father_name')"
              />
              <p v-if="fieldErrors.father_name" class="field-error">Father's name is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Father's Address <span class="required">*</span></label>
              <textarea
                v-model="formData.father_address"
                class="form-textarea"
                :class="{ error: fieldErrors.father_address }"
                placeholder="Enter father's complete address"
                rows="2"
                @input="clearFieldError('father_address')"
              ></textarea>
              <p v-if="fieldErrors.father_address" class="field-error">Father's address is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Father's Phone Number <span class="required">*</span></label>
              <input
                v-model="formData.father_phone"
                type="tel"
                class="form-input"
                :class="{ error: fieldErrors.father_phone }"
                placeholder="Enter father's phone number"
                @input="clearFieldError('father_phone')"
              />
              <p v-if="fieldErrors.father_phone" class="field-error">Father's phone number is required</p>
            </div>

            <div class="form-group">
              <label class="form-label">Father's Email <span class="required">*</span></label>
              <input
                v-model="formData.father_email"
                type="email"
                class="form-input"
                :class="{ error: fieldErrors.father_email }"
                placeholder="Enter father's email"
                @input="clearFieldError('father_email')"
              />
              <p v-if="fieldErrors.father_email" class="field-error">Valid father's email is required</p>
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
.form-textarea {
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  color: #333;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:hover,
.form-textarea:hover {
  border-color: #bbb;
}

.form-input:focus,
.form-textarea:focus {
  border-color: #1500FF;
}

.form-input.error,
.form-textarea.error {
  border-color: #dc2626;
  background-color: #fef2f2;
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
