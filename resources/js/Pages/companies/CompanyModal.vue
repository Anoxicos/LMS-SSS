<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import defaultImage from '@assets/images/default-company.jpg'

/* ===================== STATE ===================== */
const addModal = ref(false)
const dataEdit = ref(false)
const submitted = ref(false)
const selectedData = ref(null)
const logoInput = ref(null)
const logoPreview = ref(defaultImage)

/* ===================== FORM ===================== */
const form = useForm({
  name: '',
  email: '',
  phone: '',
  address: '',
  status: 'active',
  logo: null,
  remove_logo: false,
})

/* ===================== METHODS ===================== */
const resetLogo = () => {
  logoPreview.value = defaultImage
  form.logo = null
  form.remove_logo = false

  if (logoInput.value) {
    logoInput.value.value = ''
  }
}

const toggleModal = () => {
  addModal.value = true
  dataEdit.value = false
  selectedData.value = null
  submitted.value = false

  form.reset()
  form.clearErrors()
  resetLogo()
}

const showEdit = (data) => {
  selectedData.value = data.hashid
  dataEdit.value = true
  addModal.value = true
  submitted.value = false

  form.clearErrors()

  form.name = data.name ?? ''
  form.email = data.email ?? ''
  form.phone = data.phone ?? ''
  form.address = data.address ?? ''
  form.status = data.status ?? 'active'
  form.logo = null
  form.remove_logo = false

  logoPreview.value = data.logo
    ? `/storage/${data.logo}`
    : defaultImage

  if (logoInput.value) {
    logoInput.value.value = ''
  }
}

const openLogoInput = () => {
  logoInput.value?.click()
}

const handleLogoChange = (event) => {
  const file = event.target.files[0]

  if (!file) return

  form.logo = file
  form.remove_logo = false
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  form.logo = null
  form.remove_logo = true
  logoPreview.value = defaultImage

  if (logoInput.value) {
    logoInput.value.value = ''
  }
}

const addData = () => {
  submitted.value = true

  form.post(route('companies.store'), {
    forceFormData: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-company-added-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      form.reset()
      resetLogo()
      addModal.value = false
      submitted.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-an-error-occurred-while-adding-the-company'),
      })
    },
  })
}

const updateData = () => {
  submitted.value = true

  form.post(route('companies.update', selectedData.value), {
    forceFormData: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-company-updated-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      form.reset()
      resetLogo()
      addModal.value = false
      submitted.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-an-error-occurred-while-updating-the-company'),
      })
    },
  })
}

const handleSubmit = () => {
  dataEdit.value ? updateData() : addData()
}

/* ===================== EXPOSE ===================== */
defineExpose({
  toggleModal,
  showEdit,
})
</script>

<template>
  <BModal
    v-model="addModal"
    id="showmodal"
    hide-footer
    title-class="exampleModalLabel"
    :title="dataEdit ? 'Edit Company' : 'Add Company'"
    header-class="bg-light p-3"
    size="lg"
    class="v-modal-custom company-modal"
    centered
  >
    <BForm
      id="addform"
      class="tablelist-form"
      autocomplete="off"
      @submit.prevent="handleSubmit"
    >
      <input type="hidden" id="id_" />

      <!-- Company logo -->
      <div class="company-logo-wrapper mb-4">
        <div class="company-logo-card">
          <div class="position-relative d-inline-block">
            <img
              :src="logoPreview"
              :alt="$t('ui-company-logo')"
              class="company-logo-img"
              @click="openLogoInput"
            />

            <button
              v-if="logoPreview !== defaultImage"
              type="button"
              class="logo-delete-btn"
              title="Remove logo"
              @click.stop="removeLogo"
            >
              <i class="ri-delete-bin-line"></i>
            </button>

            <button
              type="button"
              class="logo-edit-btn"
              title="Change logo"
              @click="openLogoInput"
            >
              <i class="ri-camera-line"></i>
            </button>
          </div>

          <div class="mt-3">
            <h6 class="mb-1">{{ $t('ui-company-logo') }}</h6>
            <p class="text-muted mb-0 small">
              {{ $t('ui-click-the-image-to-upload-or-change-the-logo') }}
            </p>
          </div>

          <input
            ref="logoInput"
            type="file"
            class="d-none"
            accept="image/*"
            @change="handleLogoChange"
          />

          <div v-if="form.errors.logo" class="text-danger small mt-2">
            {{ form.errors.logo }}
          </div>
        </div>
      </div>

      <BRow class="g-3">
        <BCol lg="6">
          <div>
            <label for="name-field" class="form-label">{{ $t('ui-name') }}</label>
            <input
              type="text"
              id="name-field"
              class="form-control"
              placeholder="Enter company name"
              v-model="form.name"
              :class="{ 'is-invalid': submitted && !form.name }"
            />
            <div class="invalid-feedback">{{ $t('ui-please-enter-the-company-name') }}</div>
            <div v-if="form.errors.name" class="text-danger small mt-1">
              {{ form.errors.name }}
            </div>
          </div>
        </BCol>

        <BCol lg="6">
          <div>
            <label for="email-field" class="form-label">{{ $t('ui-email') }}</label>
            <input
              type="email"
              id="email-field"
              class="form-control"
              placeholder="Enter email address"
              v-model="form.email"
              :class="{ 'is-invalid': submitted && !form.email }"
            />
            <div class="invalid-feedback">{{ $t('ui-please-enter-the-email-address') }}</div>
            <div v-if="form.errors.email" class="text-danger small mt-1">
              {{ form.errors.email }}
            </div>
          </div>
        </BCol>

        <BCol lg="6">
          <div>
            <label for="phone-field" class="form-label">{{ $t('ui-phone') }}</label>
            <input
              type="text"
              id="phone-field"
              class="form-control"
              placeholder="Enter phone number"
              v-model="form.phone"
              :class="{ 'is-invalid': submitted && !form.phone }"
            />
            <div class="invalid-feedback">{{ $t('ui-please-enter-the-phone-number') }}</div>
            <div v-if="form.errors.phone" class="text-danger small mt-1">
              {{ form.errors.phone }}
            </div>
          </div>
        </BCol>

        <BCol lg="6">
          <div>
            <label for="status-field" class="form-label">{{ $t('ui-status') }}</label>
            <select
              id="status-field"
              class="form-select"
              v-model="form.status"
              :class="{ 'is-invalid': submitted && !form.status }"
            >
              <option value="active">{{ $t('ui-active') }}</option>
              <option value="inactive">{{ $t('ui-inactive') }}</option>
            </select>
            <div class="invalid-feedback">{{ $t('ui-please-select-a-status') }}</div>
            <div v-if="form.errors.status" class="text-danger small mt-1">
              {{ form.errors.status }}
            </div>
          </div>
        </BCol>

        <BCol lg="12">
          <div>
            <label for="address-field" class="form-label">{{ $t('ui-address') }}</label>
            <textarea
              id="address-field"
              class="form-control"
              rows="3"
              placeholder="Enter address"
              v-model="form.address"
              :class="{ 'is-invalid': submitted && !form.address }"
            ></textarea>
            <div class="invalid-feedback">{{ $t('ui-please-enter-the-address') }}</div>
            <div v-if="form.errors.address" class="text-danger small mt-1">
              {{ form.errors.address }}
            </div>
          </div>
        </BCol>
      </BRow>

      <div class="modal-footer v-modal-footer mt-4">
        <div class="hstack gap-2 justify-content-end">
          <BButton
            type="button"
            variant="light"
            id="closemodal"
            @click="addModal = false"
          >
            {{ $t('ui-close') }}
          </BButton>

          <BButton
            type="submit"
            :disabled="form.processing"
            variant="success"
            id="add-btn"
          >
            <span
              v-if="form.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            {{ dataEdit ? 'Update' : 'Add' }}
          </BButton>
        </div>
      </div>
    </BForm>
  </BModal>
</template>

<style scoped>
.company-modal :deep(.modal-content) {
  border: none;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
}

.company-logo-wrapper {
  display: flex;
  justify-content: center;
}

.company-logo-card {
  width: 100%;
  padding: 24px;
  text-align: center;
  border: 1px dashed #d7dce3;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.company-logo-img {
  width: 128px;
  height: 128px;
  object-fit: cover;
  border-radius: 50%;
  border: 4px solid #ffffff;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.16);
  cursor: pointer;
  transition: all 0.25s ease;
  background-color: #f1f5f9;
}

.company-logo-img:hover {
  transform: scale(1.04);
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.22);
}

.logo-edit-btn,
.logo-delete-btn {
  position: absolute;
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 50%;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
  transition: all 0.2s ease;
}

.logo-edit-btn {
  right: 4px;
  bottom: 8px;
  background-color: #0ab39c;
}

.logo-delete-btn {
  right: 4px;
  top: 8px;
  background-color: #f06548;
}

.logo-edit-btn:hover,
.logo-delete-btn:hover {
  transform: translateY(-2px);
  opacity: 0.92;
}

.form-label {
  font-weight: 600;
  color: #334155;
}

.form-control,
.form-select {
  border-radius: 10px;
  min-height: 42px;
}

.form-control:focus,
.form-select:focus {
  border-color: #0ab39c;
  box-shadow: 0 0 0 0.18rem rgba(10, 179, 156, 0.16);
}

.v-modal-footer {
  border-top: 1px solid #eef2f7;
  padding-top: 18px;
}
</style>