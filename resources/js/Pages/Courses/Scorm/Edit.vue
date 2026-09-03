<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

import '../course-builder.css'
import CoursePlan from '../Partials/CoursePlan.vue'
import ScormBasicInfo from './Partials/ScormBasicInfo.vue'
import ScormReview from './Partials/ScormReview.vue'

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
  tags: {
    type: Array,
    default: () => [],
  },
  course: {
    type: Object,
    required: true,
  },
  step: {
    type: Number,
    default: 1,
  },
})

const toBoolean = (value) => {
  return value === true || value === 1 || value === '1' || value === 'true'
}

const steps = [
  { id: 1, title: 'Course Information', icon: 'ri-book-open-line' },
  { id: 2, title: 'Plan', icon: 'ri-stack-line' },
  { id: 3, title: 'Review', icon: 'ri-checkbox-circle-line' },
]

const currentStep = ref(Number(props.step || 1))

watch(
  () => props.step,
  (newStep) => {
    currentStep.value = Number(newStep || 1)
  }
)

const form = useForm({
  ref: '',
  title: '',
  description: '',
  category_id: '',
  type: '',
  training_type: '',
  method: '',
  duration_hours: '',
  passing_score: 80,
  skill_level: '',
  course_focus: '',
  language: 'English',
  price: 0,
  currency: 'MAD',
  is_published: false,
  tags: [],

  image: null,
  image_url: null,
  remove_image: false,

  syllabus: null,
  syllabus_url: null,
  syllabus_name: null,
  remove_syllabus: false,

  scorm_file: null,
  scorm_zip_path: null,
  scorm_extract_path: null,
  scorm_entry_file: null,
  scorm_version: null,
  scorm_identifier: null,
  scorm_title: null,
  scorm_launch_url: null,

  plan: {
    name: '',
    is_active: true,
    details: [],
  },
})

watch(
  () => props.course,
  (course) => {
    form.ref = course.ref || ''
    form.title = course.title || ''
    form.description = course.description || ''
    form.category_id = course.category_id || ''
    form.type = course.type || ''
    form.training_type = course.training_type || ''
    form.method = course.method || ''
    form.duration_hours = course.duration_hours ?? ''
    form.passing_score = course.passing_score ?? 80
    form.skill_level = course.skill_level || ''
    form.course_focus = course.course_focus || ''
    form.language = course.language || 'English'
    form.price = course.price || 0
    form.currency = course.currency || 'MAD'
    form.is_published = toBoolean(course.is_published)
    form.tags = course.tags?.map((tag) => tag.id) || []

    form.image = null
    form.image_url = course.image_url || null
    form.remove_image = false

    form.syllabus = null
    form.syllabus_url = course.syllabus_url || null
    form.syllabus_name = course.syllabus_name || null
    form.remove_syllabus = false

    form.scorm_file = null
    form.scorm_zip_path = course.scorm_zip_path || null
    form.scorm_extract_path = course.scorm_extract_path || null
    form.scorm_entry_file = course.scorm_entry_file || null
    form.scorm_version = course.scorm_version || null
    form.scorm_identifier = course.scorm_identifier || null
    form.scorm_title = course.scorm_title || null
    form.scorm_launch_url = course.scorm_launch_url || null

    form.plan = course.plan || {
      name: '',
      is_active: true,
      details: [],
    }
  },
  {
    immediate: true,
    deep: true,
  }
)

const isFirstStep = computed(() => currentStep.value === 1)
const isLastStep = computed(() => currentStep.value === steps.length)

const selectedCategory = computed(() => {
  return props.categories.find((category) => {
    return Number(category.id) === Number(form.category_id)
  })
})

const showSuccess = (message) => {
  Swal.fire({
    icon: 'success',
    title: 'Saved!',
    text: message,
    timer: 1800,
    showConfirmButton: false,
  })
}

const showError = (message = 'Please check the form and try again.') => {
  Swal.fire({
    icon: 'error',
    title: 'Error!',
    text: message,
    timer: 2600,
    showConfirmButton: false,
  })
}

const validateBasicInfo = () => {
  const durationMissing =
    form.duration_hours === '' ||
    form.duration_hours === null ||
    form.duration_hours === undefined

  const passingScoreMissing =
    form.passing_score === '' ||
    form.passing_score === null ||
    form.passing_score === undefined

  if (
    !form.ref ||
    !form.title ||
    !form.category_id ||
    !form.type ||
    !form.training_type ||
    !form.method ||
    durationMissing ||
    passingScoreMissing
  ) {
    Swal.fire({
      icon: 'warning',
      title: 'Required fields',
      text: 'Please fill in the required SCORM course information.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  const durationHours = Number(form.duration_hours)
  const passingScore = Number(form.passing_score)

  if (Number.isNaN(durationHours) || durationHours <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid duration',
      text: 'Duration must be greater than 0 hour.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (Number.isNaN(passingScore) || passingScore < 0 || passingScore > 100) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid passing score',
      text: 'Passing score must be between 0 and 100.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (!form.scorm_file && !form.scorm_zip_path) {
    Swal.fire({
      icon: 'warning',
      title: 'SCORM package required',
      text: 'Please upload a valid SCORM ZIP package.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  return true
}

const goToStep = (stepId) => {
  currentStep.value = stepId
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const nextStep = () => {
  if (currentStep.value < steps.length) {
    currentStep.value++
  }
}

const saveBasicInfo = (goNext = false) => {
  if (!validateBasicInfo()) return

  form
    .transform((data) => ({
      ...data,
      next_step: goNext ? 2 : 1,
    }))
    .post(route('courses.scorm.update', props.course.hashid), {
      preserveScroll: true,
      preserveState: false,
      forceFormData: true,
      onSuccess: () => {
        showSuccess('SCORM course information has been saved successfully.')
      },
      onError: () => {
        showError(form.errors.error || 'Please check the SCORM course information.')
      },
      onFinish: () => {
        form.transform((data) => data)
      },
    })
}

const savePlan = (goNext = false) => {
  form
    .transform(() => ({
      name: form.plan.name,
      is_active: form.plan.is_active,
      next_step: goNext ? 3 : 2,
    }))
    .post(route('courses.scorm.plan.update', props.course.hashid), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showSuccess('SCORM course plan has been saved successfully.')
      },
      onError: () => {
        showError('Please check the plan information.')
      },
      onFinish: () => {
        form.transform((data) => data)
      },
    })
}

const saveCurrentStep = (goNext = false) => {
  if (currentStep.value === 1) {
    saveBasicInfo(goNext)
    return
  }

  if (currentStep.value === 2) {
    savePlan(goNext)
  }
}
</script>

<template>
  <Layout>
    <Head title="Edit SCORM Course" />
    <PageHeader title="Edit SCORM Course" pageTitle="Courses" />

    <div class="course-builder-page">
      <BCard class="course-builder-card border-0 shadow-sm">
        <BCardHeader class="course-builder-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="course-title-box">
              <div class="course-main-icon">
                <i class="ri-code-box-line"></i>
              </div>

              <div>
                <h4 class="mb-1 fw-bold">Edit SCORM Course</h4>
                <p class="text-muted mb-0">
                  Configure the course information, assessment plan and final review.
                </p>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <Link
                :href="route('courses.scorm.show', course.hashid)"
                class="btn btn-soft-info back-btn"
              >
                <i class="ri-eye-line me-1"></i>
                View
              </Link>

              <Link :href="route('courses.scorm.index')" class="btn btn-light back-btn">
                <i class="ri-arrow-left-line me-1"></i>
                Back to SCORM Courses
              </Link>
            </div>
          </div>
        </BCardHeader>

        <BCardBody>
          <div class="builder-steps mb-4">
            <button
              v-for="stepItem in steps"
              :key="stepItem.id"
              type="button"
              class="builder-step"
              :class="{
                active: currentStep === stepItem.id,
                completed: currentStep > stepItem.id,
              }"
              @click="goToStep(stepItem.id)"
            >
              <span class="step-icon">
                <i
                  class="step-remix-icon"
                  :class="currentStep > stepItem.id ? 'ri-check-line' : stepItem.icon"
                ></i>
              </span>

              <span class="step-title">
                {{ stepItem.title }}
              </span>
            </button>
          </div>

          <ScormBasicInfo
            v-if="currentStep === 1"
            :form="form"
            :categories="categories"
            :tags="tags"
            :is-editing="true"
          />

          <CoursePlan
            v-if="currentStep === 2"
            :form="form"
            :course-id="course.id"
            :course-hashid="course.hashid"
            :course-route-key="course.hashid"
            :edit-route-key="course.hashid"
            route-prefix="courses.scorm"
            :plan-step="2"
          />

          <ScormReview
            v-if="currentStep === 3"
            :form="form"
            :selected-category="selectedCategory"
          />

          <div class="builder-footer">
            <BButton
              type="button"
              variant="light"
              class="footer-btn"
              :disabled="isFirstStep"
              @click="previousStep"
            >
              <i class="ri-arrow-left-line me-1"></i>
              Previous
            </BButton>

            <div class="d-flex flex-wrap gap-2 justify-content-end">
              <BButton
                v-if="!isLastStep"
                type="button"
                variant="success"
                class="footer-btn"
                :disabled="form.processing"
                @click="saveCurrentStep(true)"
              >
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                Save & Next
              </BButton>

              <BButton
                v-if="!isLastStep"
                type="button"
                variant="primary"
                class="footer-btn"
                @click="nextStep"
              >
                Next
                <i class="ri-arrow-right-line ms-1"></i>
              </BButton>

              <Link
                v-if="isLastStep"
                :href="route('courses.scorm.show', course.hashid)"
                class="btn btn-soft-info footer-btn"
              >
                <i class="ri-eye-line me-1"></i>
                View Course
              </Link>

              <Link
                v-if="isLastStep"
                :href="route('courses.scorm.index')"
                class="btn btn-success footer-btn"
              >
                Finish
                <i class="ri-check-line ms-1"></i>
              </Link>
            </div>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>
