<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import defaultImage from '@assets/images/default-user.png'

const props = defineProps({
  companies: {
    type: Array,
    default: () => [],
  },
  roles: {
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

const isSuperAdmin = computed(() => {
  return props.authUser?.role === 'Super-admin'
})

const isAdmin = computed(() => {
  return props.authUser?.role === 'Admin'
})

const companyOptions = computed(() => {
  return props.companies.map(company => ({
    value: company.id,
    label: company.name,
  }))
})

const roleOptions = computed(() => {
  return props.roles.map(role => ({
    value: role.name,
    label: role.name,
  }))
})

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  date_of_birth: '',
  place_of_birth: '',
  country: '',
  email: '',
  password: '',
  company_id: null,
  role: null,
  status: 'active',
  photo: null,
  remove_photo: false,
})

const getUserPhoto = user => {
  if (user?.profile_photo_path) {
    return `/storage/${user.profile_photo_path}`
  }

  if (
    user?.profile_photo_url &&
    !user.profile_photo_url.includes('default-user.png') &&
    !user.profile_photo_url.includes('profile-photos/default')
  ) {
    return user.profile_photo_url
  }

  return defaultImage
}

const formatDateForInput = value => {
  if (!value) return ''

  return String(value).slice(0, 10)
}

const handleImageError = event => {
  event.target.src = defaultImage
}

const openPhotoInput = () => {
  photoInput.value?.click()
}

const handlePhotoChange = event => {
  const file = event.target.files?.[0]

  if (!file) return

  form.photo = file
  form.remove_photo = false
  photoPreview.value = URL.createObjectURL(file)
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
  form.photo = null
  form.remove_photo = false
  photoPreview.value = defaultImage

  if (photoInput.value) {
    photoInput.value.value = ''
  }
}

const assignAdminCompany = () => {
  if (isAdmin.value) {
    form.company_id = props.authUser?.company_id ?? null
  }
}

const resetFormState = () => {
  form.reset()
  form.clearErrors()
  resetPhoto()

  form.status = 'active'
  form.role = null

  if (isAdmin.value) {
    form.company_id = props.authUser?.company_id ?? null
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
  form.date_of_birth = formatDateForInput(data.date_of_birth)
  form.place_of_birth = data.place_of_birth ?? ''
  form.country = data.country ?? ''
  form.email = data.email ?? ''
  form.password = ''
  form.role = data.roles?.[0]?.name ?? null
  form.status = data.status ?? 'active'
  form.photo = null
  form.remove_photo = false

  if (isAdmin.value) {
    form.company_id = props.authUser?.company_id ?? null
  } else {
    form.company_id = data.company_id ?? data.company?.id ?? null
  }

  photoPreview.value = getUserPhoto(data)

  if (photoInput.value) {
    photoInput.value.value = ''
  }
}

const validateRequiredFields = () => {
  const requiredFieldsAreValid =
    form.first_name &&
    form.last_name &&
    form.email &&
    form.role &&
    form.status &&
    (dataEdit.value || form.password)

  if (!requiredFieldsAreValid) {
    Swal.fire({
      icon: 'warning',
      title: 'Required fields',
      text: 'Please fill in all required fields.',
      timer: 2000,
      showConfirmButton: false,
    })

    return false
  }

  if (isSuperAdmin.value && !form.company_id) {
    Swal.fire({
      icon: 'warning',
      title: 'Required fields',
      text: 'Please select a company.',
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
  assignAdminCompany()

  if (!validateRequiredFields()) return

  form.post(route('users.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'User added successfully.',
        timer: 2000,
        showConfirmButton: false,
      })

      closeAfterSuccess()
    },
    onError: errors => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: errors?.company_id
          ? errors.company_id
          : 'Please check the entered information.',
      })
    },
  })
}

const updateData = () => {
  submitted.value = true
  assignAdminCompany()

  if (!validateRequiredFields()) return

  form.post(route('users.update', selectedData.value), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'User updated successfully.',
        timer: 2000,
        showConfirmButton: false,
      })

      closeAfterSuccess()
    },
    onError: errors => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: errors?.company_id
          ? errors.company_id
          : 'Please check the entered information.',
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
    :title="dataEdit ? 'Edit User' : 'Add User'"
    header-class="bg-light p-3"
    size="xl"
    class="v-modal-custom user-modal"
    centered
  >
    <BForm
      id="user-form"
      autocomplete="off"
      @submit.prevent="handleSubmit"
    >
      <BRow class="g-4">
        <BCol lg="4">
          <div class="upload-panel">
            <div class="panel-title">
              <i class="ri-user-3-line"></i>
              Profile photo
            </div>

            <div class="user-photo-wrapper">
              <div class="position-relative d-inline-block">
                <img
                  :src="photoPreview"
                  alt="User photo"
                  class="user-photo-img"
                  @click="openPhotoInput"
                  @error="handleImageError"
                />

                <button
                  v-if="photoPreview !== defaultImage"
                  type="button"
                  class="photo-delete-btn"
                  title="Remove photo"
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
                JPG, JPEG, PNG or WEBP. Maximum 2 MB.
              </p>

              <input
                ref="photoInput"
                type="file"
                class="d-none"
                accept="image/jpeg,image/png,image/webp"
                @change="handlePhotoChange"
              />

              <div
                v-if="form.errors.photo"
                class="text-danger small mt-2"
              >
                {{ form.errors.photo }}
              </div>
            </div>
          </div>
        </BCol>

        <BCol lg="8">
          <div class="form-section">
            <div class="panel-title">
              <i class="ri-user-settings-line"></i>
              Personal information
            </div>

            <BRow class="g-3">
              <BCol md="4">
                <label class="form-label">
                  First name <span class="text-danger">*</span>
                </label>

                <input
                  v-model="form.first_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter first name"
                  :class="{
                    'is-invalid': submitted && !form.first_name,
                  }"
                />

                <div class="invalid-feedback">
                  Please enter the first name.
                </div>

                <div
                  v-if="form.errors.first_name"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.first_name }}
                </div>
              </BCol>

              <BCol md="4">
                <label class="form-label">
                  Middle name
                  <span class="text-muted small">(optional)</span>
                </label>

                <input
                  v-model="form.middle_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter middle name"
                />

                <div
                  v-if="form.errors.middle_name"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.middle_name }}
                </div>
              </BCol>

              <BCol md="4">
                <label class="form-label">
                  Last name <span class="text-danger">*</span>
                </label>

                <input
                  v-model="form.last_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter last name"
                  :class="{
                    'is-invalid': submitted && !form.last_name,
                  }"
                />

                <div class="invalid-feedback">
                  Please enter the last name.
                </div>

                <div
                  v-if="form.errors.last_name"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.last_name }}
                </div>
              </BCol>

              <BCol md="4">
                <label class="form-label">
                  Date of birth
                  <span class="text-muted small">(optional)</span>
                </label>

                <input
                  v-model="form.date_of_birth"
                  type="date"
                  class="form-control"
                  :max="new Date().toISOString().split('T')[0]"
                />

                <div
                  v-if="form.errors.date_of_birth"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.date_of_birth }}
                </div>
              </BCol>

              <BCol md="4">
                <label class="form-label">
                  Place of birth
                  <span class="text-muted small">(optional)</span>
                </label>

                <input
                  v-model="form.place_of_birth"
                  type="text"
                  class="form-control"
                  placeholder="Enter place of birth"
                />

                <div
                  v-if="form.errors.place_of_birth"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.place_of_birth }}
                </div>
              </BCol>

              <BCol md="4">
                <label class="form-label">
                  Country
                  <span class="text-muted small">(optional)</span>
                </label>

                <input
                  v-model="form.country"
                  type="text"
                  class="form-control"
                  placeholder="Enter country"
                />

                <div
                  v-if="form.errors.country"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.country }}
                </div>
              </BCol>
            </BRow>
          </div>

          <div class="form-section mt-3">
            <div class="panel-title">
              <i class="ri-shield-user-line"></i>
              Account information
            </div>

            <BRow class="g-3">
              <BCol md="6">
                <label class="form-label">
                  Email <span class="text-danger">*</span>
                </label>

                <input
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  placeholder="Enter email address"
                  :class="{
                    'is-invalid': submitted && !form.email,
                  }"
                />

                <div class="invalid-feedback">
                  Please enter the email address.
                </div>

                <div
                  v-if="form.errors.email"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.email }}
                </div>
              </BCol>

              <BCol md="6">
                <label class="form-label">
                  Password
                  <span v-if="!dataEdit" class="text-danger">*</span>
                  <span v-else class="text-muted small">(optional)</span>
                </label>

                <input
                  v-model="form.password"
                  type="password"
                  class="form-control"
                  :placeholder="
                    dataEdit
                      ? 'Leave empty to keep current password'
                      : 'Enter password'
                  "
                  :class="{
                    'is-invalid':
                      submitted && !dataEdit && !form.password,
                  }"
                />

                <div class="invalid-feedback">
                  Please enter the password.
                </div>

                <div
                  v-if="form.errors.password"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.password }}
                </div>
              </BCol>

              <BCol md="6">
                <label class="form-label">
                  Role <span class="text-danger">*</span>
                </label>

                <Multiselect
                  v-model="form.role"
                  :options="roleOptions"
                  placeholder="Select role"
                  searchable
                  :class="{
                    'is-invalid-multiselect': submitted && !form.role,
                  }"
                />

                <div
                  v-if="submitted && !form.role"
                  class="text-danger small mt-1"
                >
                  Please select a role.
                </div>

                <div
                  v-if="form.errors.role"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.role }}
                </div>
              </BCol>

              <BCol md="6">
                <label class="form-label">
                  Company
                  <span v-if="isSuperAdmin" class="text-danger">*</span>
                </label>

                <Multiselect
                  v-model="form.company_id"
                  :options="companyOptions"
                  placeholder="Select company"
                  searchable
                  :clearable="isSuperAdmin"
                  :disabled="isAdmin"
                  :class="{
                    'is-invalid-multiselect':
                      submitted && isSuperAdmin && !form.company_id,
                  }"
                />

                <div
                  v-if="
                    submitted &&
                    isSuperAdmin &&
                    !form.company_id
                  "
                  class="text-danger small mt-1"
                >
                  Please select a company.
                </div>

                <div
                  v-if="isAdmin"
                  class="text-muted small mt-1"
                >
                  The company is assigned automatically.
                </div>

                <div
                  v-if="form.errors.company_id"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.company_id }}
                </div>
              </BCol>

              <BCol md="6">
                <label class="form-label">
                  Status <span class="text-danger">*</span>
                </label>

                <select
                  v-model="form.status"
                  class="form-select"
                  :class="{
                    'is-invalid': submitted && !form.status,
                  }"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>

                <div class="invalid-feedback">
                  Please select a status.
                </div>

                <div
                  v-if="form.errors.status"
                  class="text-danger small mt-1"
                >
                  {{ form.errors.status }}
                </div>
              </BCol>
            </BRow>
          </div>
        </BCol>
      </BRow>

      <div class="modal-footer v-modal-footer mt-4">
        <div class="hstack gap-2 justify-content-end">
          <BButton
            type="button"
            variant="light"
            @click="addModal = false"
          >
            Close
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

            {{ dataEdit ? 'Update User' : 'Add User' }}
          </BButton>
        </div>
      </div>
    </BForm>
  </BModal>
</template>

<style scoped>
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

.panel-title {
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

.panel-title i {
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
}

.photo-edit-btn {
  bottom: 8px;
  background-color: #405189;
}

.photo-delete-btn {
  top: 8px;
  background-color: #f06548;
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

:deep(.multiselect.is-disabled) {
  opacity: 1;
  background: #f8fafc;
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
