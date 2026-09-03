<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import PlanDetailTree from './PlanDetailTree.vue'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  courseId: {
    type: [Number, String],
    required: true,
  },
  courseHashid: {
    type: [Number, String],
    required: true,
  },
  routePrefix: {
    type: String,
    default: 'courses',
  },
  planStep: {
    type: Number,
    default: 4,
  },
  courseRouteKey: {
    type: [Number, String],
    default: null,
  },
  editRouteKey: {
    type: [Number, String],
    default: null,
  },
})

const apiCourseKey = computed(() => props.courseRouteKey ?? props.courseId)
const editCourseKey = computed(() => props.editRouteKey ?? props.courseHashid)

const detailForm = ref({
  name: '',
  own_questions: 0,
})

const editingDetail = ref(null)
const parentDetail = ref(null)

const globalQuestions = computed(() => {
  return (props.form.plan.details || []).reduce((sum, detail) => {
    return sum + Number(detail.total_questions || 0)
  }, 0)
})

const goBackToPlanStep = () => {
  router.visit(route(`${props.routePrefix}.edit`, editCourseKey.value), {
    method: 'get',
    data: {
      step: props.planStep,
    },
    preserveScroll: true,
    preserveState: false,
    replace: true,
  })
}

const resetDetailForm = () => {
  detailForm.value = {
    name: '',
    own_questions: 0,
  }

  editingDetail.value = null
  parentDetail.value = null
}

const startAddRoot = () => {
  resetDetailForm()
}

const startAddChild = (detail) => {
  resetDetailForm()
  parentDetail.value = detail
}

const startEditDetail = (detail) => {
  editingDetail.value = detail
  parentDetail.value = null

  detailForm.value = {
    name: detail.name || '',
    own_questions: Number(detail.own_questions || 0),
  }
}

const saveDetail = () => {
  if (!detailForm.value.name) {
    Swal.fire({
      icon: 'warning',
      title: 'Required field',
      text: 'Plan detail name is required.',
      timer: 2200,
      showConfirmButton: false,
    })

    return
  }

  if (editingDetail.value) {
    router.post(
      route(`${props.routePrefix}.plan.details.update`, [
        apiCourseKey.value,
        editingDetail.value.id,
      ]),
      {
        name: detailForm.value.name,
        own_questions: detailForm.value.own_questions || 0,
      },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          resetDetailForm()
          goBackToPlanStep()
        },
      }
    )

    return
  }

  router.post(
    route(`${props.routePrefix}.plan.details.store`, apiCourseKey.value),
    {
      parent_id: parentDetail.value?.id || null,
      name: detailForm.value.name,
      own_questions: detailForm.value.own_questions || 0,
    },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        resetDetailForm()
        goBackToPlanStep()
      },
    }
  )
}

const deleteDetail = (detail) => {
  Swal.fire({
    icon: 'warning',
    title: 'Delete detail?',
    text: 'All children inside this detail will be deleted.',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  }).then((result) => {
    if (!result.isConfirmed) return

    router.delete(
      route(`${props.routePrefix}.plan.details.delete`, [
        apiCourseKey.value,
        detail.id,
      ]),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          goBackToPlanStep()
        },
      }
    )
  })
}
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>Plan</h5>
      <p>Create a recursive course plan with saved question totals.</p>
    </div>

    <BRow class="g-3">
      <BCol lg="5">
        <div class="builder-side-form">
          <h6 class="mb-3">Plan Information</h6>

          <div class="mb-3">
            <label class="form-label">Plan Name</label>

            <input
              v-model="form.plan.name"
              type="text"
              class="form-control modern-input"
              placeholder="Example: Default Plan"
            />
          </div>

          <div class="publish-card mb-3">
            <div>
              <h6 class="mb-1">Plan Status</h6>
              <p class="text-muted mb-0">
                {{ form.plan.is_active ? 'This plan is active.' : 'This plan is inactive.' }}
              </p>
            </div>

            <div class="form-check form-switch">
              <input
                v-model="form.plan.is_active"
                class="form-check-input"
                type="checkbox"
                id="planStatus"
              />

              <label class="form-check-label" for="planStatus">
                {{ form.plan.is_active ? 'Active' : 'Inactive' }}
              </label>
            </div>
          </div>

          <div class="global-question-card mb-3">
            <div class="global-question-icon">
              <i class="ri-questionnaire-line"></i>
            </div>

            <div>
              <span>Global Questions</span>
              <h3>{{ globalQuestions }}</h3>
            </div>
          </div>

          <hr />

          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
              {{
                editingDetail
                  ? 'Edit Detail'
                  : parentDetail
                    ? 'Add Child Detail'
                    : 'Add Root Detail'
              }}
            </h6>

            <BButton
              type="button"
              size="sm"
              variant="light"
              @click="startAddRoot"
            >
              Root
            </BButton>
          </div>

          <div v-if="parentDetail" class="alert alert-info py-2">
            Parent:
            <strong>{{ parentDetail.name }}</strong>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Name <span class="text-danger">*</span>
            </label>

            <input
              v-model="detailForm.name"
              type="text"
              class="form-control modern-input"
              placeholder="Example: Chapter 1 / Sub chapter 1.1"
            />
          </div>

          <div class="mb-3">
            <label class="form-label">Own Questions</label>

            <input
              v-model="detailForm.own_questions"
              type="number"
              min="0"
              class="form-control modern-input"
            />
          </div>

          <BButton
            type="button"
            variant="primary"
            class="w-100 builder-action-btn"
            @click="saveDetail"
          >
            <i :class="editingDetail ? 'ri-save-line me-1' : 'ri-add-line me-1'"></i>
            {{ editingDetail ? 'Update Detail' : parentDetail ? 'Add Child' : 'Add Detail' }}
          </BButton>

          <BButton
            v-if="editingDetail || parentDetail"
            type="button"
            variant="light"
            class="w-100 builder-action-btn mt-2"
            @click="resetDetailForm"
          >
            Cancel
          </BButton>
        </div>
      </BCol>

      <BCol lg="7">
        <PlanDetailTree
          v-if="form.plan.details && form.plan.details.length"
          :details="form.plan.details"
          @add-child="startAddChild"
          @edit-detail="startEditDetail"
          @delete-detail="deleteDetail"
        />

        <div v-else class="empty-box">
          <i class="ri-node-tree"></i>
          <h6>No plan details added yet</h6>
          <p>Add a root detail to start building your recursive plan.</p>
        </div>
      </BCol>
    </BRow>
  </div>
</template>