<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import defaultImage from '@assets/images/default-user.png'

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
  companies: {
    type: Array,
    default: () => [],
  },
  authUser: {
    type: Object,
    default: () => ({}),
  },
})

const addModal = ref(false)
const dataEdit = ref(false)
const submitted = ref(false)
const selectedData = ref(null)

const photoInput = ref(null)
const photoPreview = ref(defaultImage)
const photoDragging = ref(false)
const passportDragging = ref(false)
const photoUploadError = ref('')
const passportUploadError = ref('')

const passportInput = ref(null)
const currentPassportUrl = ref(null)
const currentPassportName = ref('')

const isSuperAdmin = computed(() => props.authUser?.role === 'Super-admin')
const isAdmin = computed(() => props.authUser?.role === 'Admin')

const companyOptions = computed(() => {
  return props.companies.map(company => ({
    value: company.id,
    label: company.name,
  }))
})

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  passport_number: '',
  passport_file: null,
  email: '',
  password: '',
  company_id: null,
  role: 'learner',
  status: 'active',
  date_of_birth: '',
  place_of_birth: '',
  country: '',
  photo: null,
  remove_photo: false,
  remove_passport_file: false,
})

const passportDisplayName = computed(() => {
  if (form.passport_file?.name) {
    return form.passport_file.name
  }

  return currentPassportName.value || 'No passport document selected'
})

const hasPassportDocument = computed(() => {
  return Boolean(form.passport_file || currentPassportUrl.value)
})

const getUserPhoto = user => {
  if (user?.profile_photo_path) {
    return `/storage/${user.profile_photo_path}`
  }

  if (
    user?.profile_photo_url &&
    !user.profile_photo_url.includes('default-user.png')
  ) {
    return user.profile_photo_url
  }

  return defaultImage
}

const getPassportUrl = user => {
  if (user?.passport_file_url) {
    return user.passport_file_url
  }

  if (user?.passport_file) {
    return `/storage/${user.passport_file}`
  }

  return null
}

const getFileName = path => {
  if (!path) return ''

  return String(path).split('/').pop() || ''
}

const formatDateForInput = value => {
  if (!value) return ''

  return String(value).slice(0, 10)
}

const openPhotoInput = () => {
  photoInput.value?.click()
}

const handlePhotoChange = event => {
  const file = event.target.files?.[0]
  event.target.value = ''

  if (!file) return

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext) || file.size > 2 * 1024 * 1024) {
    photoUploadError.value = 'Choose a JPG, PNG, or WebP image up to 2 MB.'
    return
  }

  photoUploadError.value = ''
  if (photoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(photoPreview.value)
  form.photo = file
  form.remove_photo = false
  photoPreview.value = URL.createObjectURL(file)
}

const handlePhotoDrop = event => {
  photoDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handlePhotoChange({ target: { files: [file], value: '' } })
}

const removePhoto = () => {
  form.photo = null
  form.remove_photo = true
  photoPreview.value = defaultImage

  if (photoInput.value) {
    photoInput.value.value = ''
  }
}

const resetPhoto = () => {
  photoPreview.value = defaultImage
  form.photo = null
  form.remove_photo = false

  if (photoInput.value) {
    photoInput.value.value = ''
  }
}

const openPassportInput = () => {
  passportInput.value?.click()
}

const handlePassportChange = event => {
  const file = event.target.files?.[0]
  event.target.value = ''

  if (!file) return

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(ext) || file.size > 5 * 1024 * 1024) {
    passportUploadError.value = 'Choose a PDF or image file up to 5 MB.'
    return
  }

  passportUploadError.value = ''
  form.passport_file = file
  form.remove_passport_file = false
}

const handlePassportDrop = event => {
  passportDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handlePassportChange({ target: { files: [file], value: '' } })
}

const removePassport = () => {
  form.passport_file = null
  form.remove_passport_file = true
  currentPassportUrl.value = null
  currentPassportName.value = ''

  if (passportInput.value) {
    passportInput.value.value = ''
  }
}

const resetPassport = () => {
  form.passport_file = null
  form.remove_passport_file = false
  currentPassportUrl.value = null
  currentPassportName.value = ''

  if (passportInput.value) {
    passportInput.value.value = ''
  }
}

const handleImageError = event => {
  event.target.src = defaultImage
}

const prepareHiddenFields = () => {
  form.role = 'learner'

  if (isAdmin.value) {
    form.company_id = props.authUser.company_id
  }
}

const resetFormState = () => {
  form.reset()
  form.clearErrors()
  resetPhoto()
  resetPassport()
  form.role = 'learner'
  form.status = 'active'

  if (isAdmin.value) {
    form.company_id = props.authUser.company_id
  } else {
    form.company_id = null
  }
}

const toggleModal = () => {
  addModal.value = true
  dataEdit.value = false
  selectedData.value = null
  submitted.value = false
  resetFormState()
}

const showEdit = data => {
  selectedData.value = data.hashid || data.id
  dataEdit.value = true
  addModal.value = true
  submitted.value = false

  form.clearErrors()
  form.first_name = data.first_name ?? ''
  form.middle_name = data.middle_name ?? ''
  form.last_name = data.last_name ?? ''
  form.passport_number = data.passport_number ?? ''
  form.passport_file = null
  form.email = data.email ?? ''
  form.password = ''
  form.role = 'learner'
  form.status = data.status ?? 'active'
  form.date_of_birth = formatDateForInput(data.date_of_birth)
  form.place_of_birth = data.place_of_birth ?? ''
  form.country = data.country ?? ''
  form.photo = null
  form.remove_photo = false
  form.remove_passport_file = false

  if (isAdmin.value) {
    form.company_id = props.authUser.company_id
  } else {
    form.company_id = data.company_id ?? data.company?.id ?? null
  }

  photoPreview.value = getUserPhoto(data)
  currentPassportUrl.value = getPassportUrl(data)
  currentPassportName.value = getFileName(data.passport_file)

  if (photoInput.value) {
    photoInput.value.value = ''
  }

  if (passportInput.value) {
    passportInput.value.value = ''
  }
}

const validateRequiredFields = () => {
  if (
    !form.first_name?.trim() ||
    !form.last_name?.trim() ||
    !form.passport_number?.trim() ||
    !form.date_of_birth ||
    !form.place_of_birth?.trim() ||
    !form.email?.trim() ||
    !form.status ||
    (!dataEdit.value && !form.password)
  ) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-required-fields'),
      text: t('ui-please-fill-in-all-required-fields'),
      timer: 2000,
      showConfirmButton: false,
    })

    return false
  }

  if (isSuperAdmin.value && !form.company_id) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-required-fields'),
      text: t('ui-please-select-a-company'),
      timer: 2000,
      showConfirmButton: false,
    })

    return false
  }

  return true
}

const closeAfterSuccess = () => {
  resetFormState()
  addModal.value = false
  submitted.value = false
}

const addData = () => {
  submitted.value = true
  prepareHiddenFields()

  if (!validateRequiredFields()) return

  form.post(route('learners.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-learner-added-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      closeAfterSuccess()
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-please-check-the-entered-information'),
      })
    },
  })
}

const updateData = () => {
  submitted.value = true
  prepareHiddenFields()

  if (!validateRequiredFields()) return

  form.post(route('learners.update', selectedData.value), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-learner-updated-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      closeAfterSuccess()
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-please-check-the-entered-information'),
      })
    },
  })
}

const handleSubmit = () => {
  dataEdit.value ? updateData() : addData()
}

defineExpose({
  toggleModal,
  showEdit,
})
</script>

<template>
  <BModal
    v-model="addModal"
    id="user-modal"
    hide-footer
    :title="dataEdit ? 'Edit Learner' : 'Add Learner'"
    header-class="bg-light p-3"
    size="xl"
    class="v-modal-custom user-modal"
    centered
  >
    <BForm
      id="user-form"
      class="tablelist-form"
      autocomplete="off"
      @submit.prevent="handleSubmit"
    >
      <BRow class="g-4">
        <BCol lg="4">
          <div class="upload-panel">
            <div class="upload-panel-title">
              <i class="ri-user-3-line"></i>
              {{ $t('ui-profile-photo') }}
            </div>

            <div class="user-photo-wrapper media-drop-target" :class="{ 'is-dragging': photoDragging }" @dragenter.prevent="photoDragging = true" @dragover.prevent="photoDragging = true" @dragleave.prevent="photoDragging = false" @drop.prevent="handlePhotoDrop">
              <div class="position-relative d-inline-block">
                <img
                  :src="photoPreview"
                  alt="Learner photo"
                  class="user-photo-img"
                  @click="openPhotoInput"
                  @error="handleImageError"
                />

                <button
                  v-if="photoPreview !== defaultImage"
                  type="button"
                  class="photo-delete-btn"
                  :title="$t('ui-remove-photo')"
                  @click.stop="removePhoto"
                >
                  <i class="ri-delete-bin-line"></i>
                </button>

                <button
                  type="button"
                  class="photo-edit-btn"
                  title="Change photo"
                  @click="openPhotoInput"
                >
                  <i class="ri-camera-line"></i>
                </button>
              </div>

              <p class="text-muted mb-0 mt-3 small">
                Drop or choose a JPG, PNG, or WebP image · up to 2 MB
              </p>
              <p v-if="form.photo" class="text-success small mb-0" role="status"><i class="ri-check-line" aria-hidden="true"></i> {{ form.photo.name }}</p>

              <input
                ref="photoInput"
                type="file"
                class="d-none"
                accept="image/jpeg,image/png,image/webp"
                @change="handlePhotoChange"
              />

              <div v-if="form.errors.photo" class="text-danger small mt-2">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                {{ form.errors.photo }}
              </div>
              <div v-if="photoUploadError" class="text-danger small mt-2" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ photoUploadError }}</div>
            </div>
          </div>

          <div class="upload-panel mt-3">
            <div class="upload-panel-title">
              <i class="ri-passport-line"></i>
              {{ $t('ui-passport-document') }}
            </div>

            <input
              ref="passportInput"
              type="file"
              class="d-none"
              accept=".pdf,.jpg,.jpeg,.png,.webp"
              @change="handlePassportChange"
            />

            <button
              type="button"
              class="passport-upload-box media-drop-target"
              :class="{ 'is-dragging': passportDragging }"
              @dragenter.prevent="passportDragging = true"
              @dragover.prevent="passportDragging = true"
              @dragleave.prevent="passportDragging = false"
              @drop.prevent="handlePassportDrop"
              @click="openPassportInput"
            >
              <i class="ri-upload-cloud-2-line"></i>
              <span>{{ form.passport_file?.name || $t('ui-passport-attachment') }}</span>
              <small>{{ $t('ui-pdf-or-image-maximum-5-mb') }}</small>
            </button>

            <div
              v-if="hasPassportDocument"
              class="passport-actions mt-3"
            >
              <a
                v-if="currentPassportUrl && !form.passport_file"
                :href="currentPassportUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-soft-info btn-sm"
              >
                <i class="ri-external-link-line me-1"></i>
                {{ $t('ui-view-current-file') }}
              </a>

              <button
                type="button"
                class="btn btn-soft-danger btn-sm"
                @click="removePassport"
              >
                <i class="ri-delete-bin-line me-1"></i>
                {{ $t('ui-remove') }}
              </button>
            </div>

            <div
              v-if="form.errors.passport_file"
              class="text-danger small mt-2"
              role="alert"
            >
              <i class="ri-error-warning-line" aria-hidden="true"></i>
              {{ form.errors.passport_file }}
            </div>
            <div v-if="passportUploadError" class="text-danger small mt-2" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ passportUploadError }}</div>
            <div v-if="form.processing && form.progress" class="upload-progress mt-2" role="status" aria-live="polite">
              <div class="d-flex justify-content-between small"><span>{{ [form.photo?.name, form.passport_file?.name].filter(Boolean).join(', ') }}</span><strong>{{ form.progress.percentage }}%</strong></div>
              <div class="progress" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" :style="{ width: `${form.progress.percentage}%` }"></div></div>
            </div>
          </div>
        </BCol>

        <BCol lg="8">
          <div class="form-section">
            <div class="form-section-title">
              <i class="ri-user-settings-line"></i>
              {{ $t('ui-identity-information') }}
            </div>

            <BRow class="g-3">
              <BCol md="4">
                <label for="first-name-field" class="form-label">
                  {{ $t('ui-first-name-as-a-passport') }} <span class="text-danger">*</span>
                </label>
                <input
                  id="first-name-field"
                  v-model="form.first_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter first name"
                  :class="{ 'is-invalid': submitted && !form.first_name }"
                />
                <div class="invalid-feedback">{{ $t('ui-please-enter-the-first-name') }}</div>
                <div v-if="form.errors.first_name" class="text-danger small mt-1">
                  {{ form.errors.first_name }}
                </div>
              </BCol>

              <BCol md="4">
                <label for="middle-name-field" class="form-label">
                  {{ $t('ui-middle-name-as-a-passport') }}
                </label>
                <input
                  id="middle-name-field"
                  v-model="form.middle_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter middle name"
                />
                <div v-if="form.errors.middle_name" class="text-danger small mt-1">
                  {{ form.errors.middle_name }}
                </div>
              </BCol>

              <BCol md="4">
                <label for="last-name-field" class="form-label">
                  {{ $t('ui-last-name-as-a-passport') }}<span class="text-danger">*</span>
                </label>
                <input
                  id="last-name-field"
                  v-model="form.last_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter last name"
                  :class="{ 'is-invalid': submitted && !form.last_name }"
                />
                <div class="invalid-feedback">{{ $t('ui-please-enter-the-last-name') }}</div>
                <div v-if="form.errors.last_name" class="text-danger small mt-1">
                  {{ form.errors.last_name }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="passport-number-field" class="form-label">
                  {{ $t('ui-passport-number-id') }} <span class="text-danger">*</span>
                </label>
                <input
                  id="passport-number-field"
                  v-model="form.passport_number"
                  type="text"
                  class="form-control"
                  :placeholder="$t('t-enter-passport-number')"
                  required
                  :class="{ 'is-invalid': submitted && !form.passport_number?.trim() }"
                />
                <div class="invalid-feedback">
                  {{ $t('ui-please-enter-the-passport-number') }}
                </div>
                <div v-if="form.errors.passport_number" class="text-danger small mt-1">
                  {{ form.errors.passport_number }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="date-of-birth-field" class="form-label">
                  {{ $t('ui-date-of-birth-as-a-passport') }} <span class="text-danger">*</span>
                </label>
                <input
                  id="date-of-birth-field"
                  v-model="form.date_of_birth"
                  type="date"
                  class="form-control"
                  :max="new Date().toISOString().split('T')[0]"
                  required
                  :class="{ 'is-invalid': submitted && !form.date_of_birth }"
                />
                <div class="invalid-feedback">
                  {{ $t('ui-please-select-the-date-of-birth') }}
                </div>
                <div v-if="form.errors.date_of_birth" class="text-danger small mt-1">
                  {{ form.errors.date_of_birth }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="place-of-birth-field" class="form-label">
                  {{ $t('ui-place-of-birth-as-a-passport') }}<span class="text-danger">*</span>
                </label>
                <input
                  id="place-of-birth-field"
                  v-model="form.place_of_birth"
                  type="text"
                  class="form-control"
                  placeholder="Enter place of birth"
                  required
                  :class="{ 'is-invalid': submitted && !form.place_of_birth?.trim() }"
                />
                <div class="invalid-feedback">
                  {{ $t('ui-please-enter-the-place-of-birth') }}
                </div>
                <div v-if="form.errors.place_of_birth" class="text-danger small mt-1">
                  {{ form.errors.place_of_birth }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="country-field" class="form-label">{{ $t('ui-country') }}</label>
                <input
                  id="country-field"
                  v-model="form.country"
                  type="text"
                  class="form-control"
                  placeholder="Enter country"
                />
                <div v-if="form.errors.country" class="text-danger small mt-1">
                  {{ form.errors.country }}
                </div>
              </BCol>

            </BRow>
          </div>

          <div class="form-section mt-3">
            <div class="form-section-title">
              <i class="ri-shield-user-line"></i>
              {{ $t('ui-account-information') }}
            </div>

            <BRow class="g-3">
              <BCol md="6">
                <label for="email-field" class="form-label">
                  {{ $t('ui-email') }} <span class="text-danger">*</span>
                </label>
                <input
                  id="email-field"
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  placeholder="Enter email address"
                  :class="{ 'is-invalid': submitted && !form.email }"
                />
                <div class="invalid-feedback">{{ $t('ui-please-enter-the-email-address') }}</div>
                <div v-if="form.errors.email" class="text-danger small mt-1">
                  {{ form.errors.email }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="password-field" class="form-label">
                  {{ $t('ui-password') }}
                  <span v-if="!dataEdit" class="text-danger">*</span>
                  <span v-else class="text-muted small">{{ $t('ui-optional') }}</span>
                </label>
                <input
                  id="password-field"
                  v-model="form.password"
                  type="password"
                  class="form-control"
                  :placeholder="dataEdit ? 'Leave empty to keep current password' : 'Enter password'"
                  :class="{ 'is-invalid': submitted && !dataEdit && !form.password }"
                />
                <div class="invalid-feedback">{{ $t('ui-please-enter-the-password') }}</div>
                <div v-if="form.errors.password" class="text-danger small mt-1">
                  {{ form.errors.password }}
                </div>
              </BCol>

              <BCol v-if="isSuperAdmin" md="6">
                <label class="form-label">
                  {{ $t('ui-company') }} <span class="text-danger">*</span>
                </label>
                <Multiselect
                  v-model="form.company_id"
                  :options="companyOptions"
                  placeholder="Select company"
                  searchable
                  clearable
                  :class="{ 'is-invalid-multiselect': submitted && !form.company_id }"
                />
                <div v-if="submitted && !form.company_id" class="text-danger small mt-1">
                  {{ $t('ui-please-select-a-company') }}
                </div>
                <div v-if="form.errors.company_id" class="text-danger small mt-1">
                  {{ form.errors.company_id }}
                </div>
              </BCol>

              <BCol md="6">
                <label for="status-field" class="form-label">
                  {{ $t('ui-status') }} <span class="text-danger">*</span>
                </label>
                <select
                  id="status-field"
                  v-model="form.status"
                  class="form-select"
                  :class="{ 'is-invalid': submitted && !form.status }"
                >
                  <option value="active">{{ $t('ui-active') }}</option>
                  <option value="inactive">{{ $t('ui-inactive') }}</option>
                </select>
                <div class="invalid-feedback">{{ $t('ui-please-select-a-status') }}</div>
                <div v-if="form.errors.status" class="text-danger small mt-1">
                  {{ form.errors.status }}
                </div>
              </BCol>
            </BRow>
          </div>
        </BCol>
      </BRow>

      <div class="modal-footer v-modal-footer mt-4">
        <div class="hstack gap-2 justify-content-end">
          <BButton type="button" variant="light" @click="addModal = false">
            {{ $t('ui-close') }}
          </BButton>

          <BButton
            type="submit"
            :disabled="form.processing"
            variant="primary"
          >
            <span
              v-if="form.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            {{ dataEdit ? 'Update Learner' : 'Add Learner' }}
          </BButton>
        </div>
      </div>
    </BForm>
  </BModal>
</template>

<style scoped>
.media-drop-target { transition: border-color 140ms ease, background-color 140ms ease; }
.media-drop-target.is-dragging { border: 2px dashed var(--bs-primary, #1f5cff); background: rgba(31, 92, 255, 0.08); }
.upload-progress .progress { height: 7px; }

.user-modal :deep(.modal-content) {
  border: none;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
}

.upload-panel,
.form-section {
  padding: 18px;
  border: 1px solid #e7edf5;
  border-radius: 16px;
  background: #ffffff;
}

.upload-panel-title,
.form-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 12px;
  margin-bottom: 16px;
  color: #405189;
  font-size: 14px;
  font-weight: 700;
  border-bottom: 1px solid #edf2f7;
}

.upload-panel-title i,
.form-section-title i {
  font-size: 18px;
}

.user-photo-wrapper {
  text-align: center;
}

.user-photo-img {
  width: 126px;
  height: 126px;
  object-fit: cover;
  border-radius: 50%;
  border: 4px solid #ffffff;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.16);
  cursor: pointer;
  background-color: #f1f5f9;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.user-photo-img:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.2);
}

.photo-edit-btn,
.photo-delete-btn {
  position: absolute;
  right: 3px;
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 10px;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.2);
}

.photo-edit-btn {
  bottom: 8px;
  background-color: #405189;
}

.photo-delete-btn {
  top: 8px;
  background-color: #f06548;
}

.passport-upload-box {
  width: 100%;
  min-height: 126px;
  padding: 18px;
  border: 1px dashed #b9c3d0;
  border-radius: 14px;
  background: #f8fafc;
  color: #475569;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  text-align: center;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.passport-upload-box:hover {
  border-color: #687cfe;
  background-color: #f4f6ff;
}

.passport-upload-box i {
  color: #687cfe;
  font-size: 30px;
}

.passport-upload-box span {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 600;
}

.passport-upload-box small {
  color: #94a3b8;
}

.passport-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.form-label {
  font-weight: 600;
  color: #334155;
}

.form-control,
.form-select {
  min-height: 42px;
  border-radius: 10px;
}

.form-control:focus,
.form-select:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.18rem rgba(104, 124, 254, 0.16);
}

.v-modal-footer {
  border-top: 1px solid #eef2f7;
  padding-top: 18px;
}

.is-invalid-multiselect {
  border: 1px solid #f06548;
  border-radius: 10px;
}

:deep(.multiselect) {
  min-height: 42px;
  border-radius: 10px;
}

:deep(.multiselect.is-active) {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.18rem rgba(104, 124, 254, 0.16);
}

@media (max-width: 767.98px) {
  .upload-panel,
  .form-section {
    padding: 15px;
  }
}

@media (max-width: 575.98px) {
  .user-photo-img {
    width: 110px;
    height: 110px;
  }

  .modal-footer .hstack {
    width: 100%;
    flex-direction: column;
  }

  .modal-footer .btn {
    width: 100%;
  }
}
</style>
