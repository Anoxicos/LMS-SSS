<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})

const addModal = ref(false)
const dataEdit = ref(false)
const submitted = ref(false)
const selectedData = ref(null)

const form = useForm({
  course_id: '',
  ref: '',
  title: '',
  description: '',
})

const resetForm = () => {
  form.reset()
  form.clearErrors()
  form.course_id = ''
  form.ref = ''
  form.title = ''
  form.description = ''
}

const toggleModal = () => {
  addModal.value = true
  dataEdit.value = false
  selectedData.value = null
  submitted.value = false
  resetForm()
}

const showEdit = (data) => {
  selectedData.value = data.id
  dataEdit.value = true
  addModal.value = true
  submitted.value = false

  form.clearErrors()
  form.course_id = data.course_id || ''
  form.ref = data.ref || ''
  form.title = data.title || ''
  form.description = data.description || ''
}

const addData = () => {
  submitted.value = true

  form.post(route('banks.store'), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Bank added successfully',
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
      addModal.value = false
      submitted.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred while adding the bank',
      })
    },
  })
}

const updateData = () => {
  submitted.value = true

  form.post(route('banks.update', selectedData.value), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Bank updated successfully',
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
      addModal.value = false
      submitted.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred while updating the bank',
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
    hide-footer
    title-class="exampleModalLabel"
    :title="dataEdit ? 'Edit Bank' : 'Add Bank'"
    header-class="bg-light p-3"
    size="lg"
    class="v-modal-custom bank-modal"
    centered
  >
    <BForm
      class="tablelist-form"
      autocomplete="off"
      @submit.prevent="handleSubmit"
    >
      <div class="bank-modal-hero mb-4">
        <div class="bank-modal-icon">
          <i class="ri-bank-line"></i>
        </div>

        <div>
          <h6 class="mb-1">
            {{ dataEdit ? 'Update Question Bank' : 'Create Question Bank' }}
          </h6>
          <p class="text-muted mb-0 small">
            Select a course and define the bank information.
          </p>
        </div>
      </div>

      <BRow class="g-3">
        <BCol lg="6">
          <div>
            <label class="form-label">
              Course <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.course_id"
              class="form-select"
              :class="{ 'is-invalid': submitted && !form.course_id }"
            >
              <option value="">Select course</option>

              <option
                v-for="course in courses"
                :key="course.id"
                :value="course.id"
              >
                {{ course.title }}
                <template v-if="course.category">
                  - {{ course.category.name }}
                </template>
              </option>
            </select>

            <div class="invalid-feedback">Please select a course.</div>

            <div v-if="form.errors.course_id" class="text-danger small mt-1">
              {{ form.errors.course_id }}
            </div>
          </div>
        </BCol>

        <BCol lg="6">
          <div>
            <label class="form-label">Reference</label>

            <input
              v-model="form.ref"
              type="text"
              class="form-control"
              placeholder="Auto generated if empty"
            />

            <div v-if="form.errors.ref" class="text-danger small mt-1">
              {{ form.errors.ref }}
            </div>
          </div>
        </BCol>

        <BCol lg="12">
          <div>
            <label class="form-label">
              Title <span class="text-danger">*</span>
            </label>

            <input
              v-model="form.title"
              type="text"
              class="form-control"
              placeholder="Enter bank title"
              :class="{ 'is-invalid': submitted && !form.title }"
            />

            <div class="invalid-feedback">Please enter the bank title.</div>

            <div v-if="form.errors.title" class="text-danger small mt-1">
              {{ form.errors.title }}
            </div>
          </div>
        </BCol>

        <BCol lg="12">
          <div>
            <label class="form-label">Description</label>

            <textarea
              v-model="form.description"
              class="form-control"
              rows="4"
              placeholder="Enter bank description"
            ></textarea>

            <div v-if="form.errors.description" class="text-danger small mt-1">
              {{ form.errors.description }}
            </div>
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
            variant="success"
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
.bank-modal :deep(.modal-content) {
  border: none;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
}

.bank-modal-hero {
  display: flex;
  gap: 14px;
  align-items: center;
  padding: 18px;
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  border: 1px solid #eef2f7;
}

.bank-modal-icon {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
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