<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { onMounted, ref, watch, computed } from 'vue'
import { EventBusCategories } from './EventBusCategories'

/* ================= PROPS ================= */
const props = defineProps({
 
})

/* ================= STATE ================= */
const categoryUpdate = ref(null)
const actionAdd = ref(true)

const form = useForm({
  name: '',
  is_active: true,
})

const isFormInvalid = computed(() => {
  return !form.name || form.processing
})

/* ================= EVENTS ================= */
onMounted(() => {
  watch(
    () => EventBusCategories['edit-data'],
    (newValue) => {
      if (newValue) {
        showEditCategory(EventBusCategories.dataEdit)
        EventBusCategories['edit-data'] = false
        EventBusCategories.dataEdit = null
      }
    }
  )
})

const showEditCategory = (data) => {
  form.clearErrors()
  form.name = data.name || ''
  form.is_active = data.is_active ?? true

  actionAdd.value = false
  categoryUpdate.value = data
}

const resetForm = () => {
  form.reset()
  form.clearErrors()
  form.name = ''
  form.is_active = true

  actionAdd.value = true
  categoryUpdate.value = null
}

/* ================= SUBMIT ================= */
const handleSubmit = () => {
  if (!form.name) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-required-field'),
      text: t('ui-category-name-is-required'),
      timer: 2500,
      showConfirmButton: false,
    })
    return
  }

  if (actionAdd.value) {
    addData()
  } else {
    updateData()
  }
}

const addData = () => {
  form.post(route('categories.store'), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-created-label-label'),
        text: t('ui-category-has-been-created-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('ui-error-label'),
        text: t('ui-an-error-occurred-while-creating-the-category-please-t'),
        timer: 2500,
        showConfirmButton: false,
      })
    },
  })
}

const updateData = () => {

  form.post(route('categories.update', categoryUpdate.value.hashid), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-updated'),
        text: t('ui-category-has-been-updated-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('ui-error-label'),
        text: t('ui-an-error-occurred-while-updating-the-category-please-t'),
        timer: 2500,
        showConfirmButton: false,
      })
    },
  })
}
</script>

<template>
  <BCard class="operation-form-card border-0 shadow-sm">
    <BCardHeader class="operation-form-header border-0">
      <div class="operation-form-title">
        <div class="operation-form-icon">
          <i :class="actionAdd ? 'ri-add-circle-line' : 'ri-edit-2-line'"></i>
        </div>

        <div>
          <h5 class="mb-1 fw-semibold">
            {{ actionAdd ? 'Add Category' : 'Edit Category' }}
          </h5>
          <p class="text-muted mb-0">
            {{ actionAdd ? 'Create a new category.' : 'Update the selected category.' }}
          </p>
        </div>
      </div>
    </BCardHeader>

    <BCardBody>
      <form @submit.prevent="handleSubmit">
        <div class="operation-form-grid">
          <div class="form-group-modern">
            <label class="form-label">
              {{ $t('ui-name') }} <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-price-tag-3-line input-icon"></i>
              <input
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.name }"
                v-model="form.name"
                :placeholder="$t('ui-enter-category-name')"
              />
            </div>

            <div v-if="form.errors.name" class="erreur-messages mt-1">
              {{ form.errors.name }}
            </div>
          </div>

          <div class="form-group-modern">
            <label class="form-label">
              {{ $t('ui-status') }} <span class="text-danger">*</span>
            </label>

            <div class="status-card">
              <div class="status-info">
                <div
                  class="status-icon"
                  :class="form.is_active ? 'is-active' : 'is-inactive'"
                >
                  <i :class="form.is_active ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'"></i>
                </div>

                <div>
                  <h6 class="mb-1">
                    {{ form.is_active ? 'Active category' : 'Inactive category' }}
                  </h6>
                  <p class="mb-0 text-muted">
                    {{ form.is_active ? 'This category is visible and available.' : 'This category is hidden and unavailable.' }}
                  </p>
                </div>
              </div>

              <div class="form-check form-switch mb-0">
                <input
                  id="categoryStatus"
                  v-model="form.is_active"
                  class="form-check-input status-switch"
                  type="checkbox"
                />
              </div>
            </div>

            <div v-if="form.errors.is_active" class="erreur-messages mt-1">
              {{ form.errors.is_active }}
            </div>
          </div>
        </div>

        <div class="operation-preview-card mt-4">
          <div
            class="preview-icon"
            :class="form.is_active ? 'preview-active' : 'preview-inactive'"
          >
            <i class="ri-price-tag-3-line"></i>
          </div>

          <div class="min-width-0">
            <span class="preview-label">{{ $t('ui-preview') }}</span>
            <h6 class="preview-title mb-1">
              {{ form.name || 'Category' }}
            </h6>

            <span
              class="status-badge"
              :class="form.is_active ? 'status-badge-active' : 'status-badge-inactive'"
            >
              <i :class="form.is_active ? 'ri-checkbox-circle-line me-1' : 'ri-close-circle-line me-1'"></i>
              {{ form.is_active ? $t('t-active') : $t('ui-inactive') }}
            </span>
          </div>
        </div>

        <div class="operation-form-footer">
          <BButton
            v-if="!actionAdd"
            type="button"
            variant="light"
            class="form-action-btn"
            @click="resetForm"
          >
            <i class="ri-close-line me-1"></i>
            {{ $t('ui-cancel') }}
          </BButton>

          <BButton
            type="submit"
            variant="primary"
            class="form-action-btn flex-grow-1"
            :disabled="isFormInvalid"
          >
            <span
              v-if="form.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            <i v-else :class="actionAdd ? 'ri-add-line me-1' : 'ri-save-line me-1'"></i>
            {{ actionAdd ? 'Add' : 'Update' }}
          </BButton>
        </div>
      </form>
    </BCardBody>
  </BCard>
</template>

<style scoped>
.operation-form-card {
  border-radius: 18px;
  overflow: hidden;
  background-color: #ffffff;
}

.operation-form-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.operation-form-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.operation-form-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.25);
}

.operation-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}

.form-group-modern .form-label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
}

.input-icon-box {
  position: relative;
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #64748b;
  z-index: 2;
  font-size: 16px;
}

.modern-input {
  height: 44px;
  border-radius: 12px;
  border-color: #e5e7eb;
  padding-left: 40px;
  color: #334155;
  background-color: #ffffff;
}

.modern-input:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14);
}

.status-card {
  min-height: 74px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 16px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.status-info {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.status-info h6 {
  color: #334155;
  font-size: 14px;
  font-weight: 800;
}

.status-info p {
  font-size: 12px;
}

.status-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.status-icon.is-active {
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
}

.status-icon.is-inactive {
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
}

.status-switch {
  width: 44px;
  height: 24px;
  cursor: pointer;
}

.operation-preview-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.preview-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 23px;
}

.preview-active {
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
}

.preview-inactive {
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
}

.preview-label {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 4px;
}

.preview-title {
  color: #334155;
  font-weight: 800;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.status-badge-active {
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
}

.status-badge-inactive {
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
}

.operation-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid #f1f5f9;
}

.form-action-btn {
  min-width: 130px;
  height: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.erreur-messages {
  font-size: 0.875em;
  color: var(--vz-form-invalid-color);
}

.min-width-0 {
  min-width: 0;
}

@media (max-width: 767.98px) {
  .operation-form-card {
    border-radius: 14px;
  }

  .operation-form-header {
    padding: 18px;
  }

  .operation-form-title {
    align-items: flex-start;
  }

  .operation-form-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .operation-form-title h5 {
    font-size: 17px;
  }

  .operation-form-title p {
    font-size: 13px;
  }

  .status-card {
    align-items: flex-start;
  }

  .operation-form-footer {
    flex-direction: column-reverse;
  }

  .form-action-btn {
    width: 100%;
  }
}

@media (max-width: 420px) {
  .operation-form-title {
    flex-direction: column;
  }

  .status-card {
    flex-direction: column;
  }

  .operation-preview-card {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>