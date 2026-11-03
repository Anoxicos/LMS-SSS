<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'
import { normalizeCourseChoice } from '@/composables/course-labels'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

import './course-builder.css'

import CourseBasicInfo from './Partials/CourseBasicInfo.vue'
import CourseChapters from './Partials/CourseChapters.vue'
import CourseSlides from './Partials/CourseSlides.vue'
import CoursePlan from './Partials/CoursePlan.vue'
import CourseReview from './Partials/CourseReview.vue'

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

const currentStep = ref(Number(props.step || 1))

watch(
  () => props.step,
  (newStep) => {
    currentStep.value = Number(newStep || 1)
  }
)

const steps = computed(() => [
  { id: 1, title: t('ui-course-information'), icon: 'ri-book-open-line' },
  { id: 2, title: t('ui-chapters'), icon: 'ri-list-check-2' },
  { id: 3, title: t('ui-slides'), icon: 'ri-slideshow-line' },
  { id: 4, title: t('ui-plan'), icon: 'ri-stack-line' },
  { id: 5, title: t('ui-review'), icon: 'ri-checkbox-circle-line' },
])

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

  chapters: [],

  plan: {
    name: '',
    is_active: true,
    details: [],
  },

  image: null,
  image_url: null,
  remove_image: false,

  syllabus: null,
  syllabus_url: null,
  syllabus_name: null,
  remove_syllabus: false,

  support_voice_over: false,
  voice_over_auto_play: false,
  voice_over_provider: 'voicerss',
  voice_over_lang: 'en-us',
  voice_over_voice: 'default',
})

watch(
  () => props.course,
  (course) => {
    form.ref = course.ref || ''
    form.title = course.title || ''
    form.description = course.description || ''
    form.category_id = course.category_id || ''
    form.type = normalizeCourseChoice('type', course.type || '')
    form.training_type = normalizeCourseChoice('training_type', course.training_type || '')
    form.method = course.method || ''
    form.duration_hours = course.duration_hours ?? ''
    form.passing_score = course.passing_score ?? 80
    form.skill_level = normalizeCourseChoice('skill_level', course.skill_level || '')
    form.course_focus = course.course_focus || ''
    form.language = normalizeCourseChoice('language', course.language || 'English')
    form.price = course.price || 0
    form.currency = course.currency || 'MAD'
    form.is_published = toBoolean(course.is_published)
    form.tags = course.tags?.map((tag) => tag.id) || []

    form.chapters = course.chapters || []

    form.plan = course.plan || {
      name: '',
      is_active: true,
      details: [],
    }

    form.image = null
    form.image_url = course.image_url || null
    form.remove_image = false

    form.syllabus = null
    form.syllabus_url = course.syllabus_url || null
    form.syllabus_name = course.syllabus_name || null
    form.remove_syllabus = false

    form.support_voice_over = toBoolean(course.support_voice_over)
    form.voice_over_auto_play = toBoolean(course.voice_over_auto_play)
    form.voice_over_provider = course.voice_over_provider || 'voicerss'
    form.voice_over_lang = course.voice_over_lang || 'en-us'
    form.voice_over_voice = course.voice_over_voice || 'default'
  },
  {
    immediate: true,
    deep: true,
  }
)

const isFirstStep = computed(() => currentStep.value === 1)
const isLastStep = computed(() => currentStep.value === steps.value.length)

const selectedCategory = computed(() => {
  return props.categories.find((category) => {
    return Number(category.id) === Number(form.category_id)
  })
})

const totalSlides = computed(() => {
  return form.chapters.reduce((total, chapter) => {
    return total + (chapter.slides?.length || 0)
  }, 0)
})

const showSuccess = (message) => {
  Swal.fire({
    icon: 'success',
    title: t('ui-saved'),
    text: message,
    timer: 1800,
    showConfirmButton: false,
  })
}

const showError = (message = 'Please check the form and try again.') => {
  Swal.fire({
    icon: 'error',
    title: t('ui-error-label'),
    text: message,
    timer: 2500,
    showConfirmButton: false,
  })
}

const validateBasicInfo = () => {
  const passingScoreMissing =
    form.passing_score === '' ||
    form.passing_score === null ||
    form.passing_score === undefined

  const durationMissing =
    form.duration_hours === '' ||
    form.duration_hours === null ||
    form.duration_hours === undefined

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
      title: t('ui-required-fields'),
      text: t('ui-please-fill-in-the-required-course-information'),
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  const passingScore = Number(form.passing_score)
  const durationHours = Number(form.duration_hours)

  if (Number.isNaN(durationHours) || durationHours <= 0) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-invalid-duration'),
      text: t('ui-duration-must-be-greater-than-0-hour'),
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (Number.isNaN(passingScore) || passingScore < 0 || passingScore > 100) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-invalid-passing-score'),
      text: t('ui-passing-score-must-be-between-0-and-100'),
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
  if (currentStep.value < steps.value.length) {
    currentStep.value++
  }
}

const saveBasicInfo = (goNext = false) => {
  if (!validateBasicInfo()) return

  form.post(route('courses.update', props.course.id), {
    preserveScroll: true,
    preserveState: false,
    forceFormData: true,
    onSuccess: () => {
      showSuccess('Course information has been saved successfully.')

      if (goNext && currentStep.value < steps.value.length) {
        currentStep.value++
      }
    },
    onError: () => {
      showError('Please check the course information.')
    },
  })
}

const savePlan = (goNext = false) => {
  form
    .transform(() => ({
      name: form.plan.name,
      is_active: form.plan.is_active,
      next_step: goNext ? 5 : 4,
    }))
    .post(route('courses.plan.update', props.course.id), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showSuccess('Plan has been saved successfully.')
      },
      onError: () => {
        showError('Please check plan and try again.')
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
    if (goNext && currentStep.value < steps.value.length) {
      currentStep.value++
    }
    return
  }

  if (currentStep.value === 3) {
    if (goNext && currentStep.value < steps.value.length) {
      currentStep.value++
    }
    return
  }

  if (currentStep.value === 4) {
    savePlan(goNext)
    return
  }

  if (currentStep.value === 5) {
    showSuccess('Course review completed.')
  }
}
</script>

<template>
  <Layout>
    <Head :title="$t('ui-edit-course')" />
    <PageHeader :title="$t('ui-edit-course')" :page-title="$t('ui-courses')" />

    <div class="course-builder-page">
      <BCard class="course-builder-card border-0 shadow-sm">
        <BCardHeader class="course-builder-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="course-title-box">
              <div class="course-main-icon">
                <i class="ri-edit-2-line"></i>
              </div>

              <div>
                <h4 class="mb-1 fw-bold">{{ $t('ui-edit-course') }}</h4>
                <p class="text-muted mb-0">
                  {{ $t('ui-complete-or-update-course-chapters-slides-and-plan') }}
                </p>
              </div>
            </div>

            <Link :href="route('courses.index')" class="btn btn-light back-btn">
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('ui-back-to-courses') }}
            </Link>
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

          <CourseBasicInfo
            v-if="currentStep === 1"
            :form="form"
            :categories="categories"
            :tags="tags"
          />

          <CourseChapters
            v-if="currentStep === 2"
            :form="form"
            :course-id="course.id"
          />

          <CourseSlides
            v-if="currentStep === 3"
            :form="form"
            :course-id="course.id"
            :total-slides="totalSlides"
            :course-hashid="course.hashid"
          />

          <CoursePlan
            v-if="currentStep === 4"
            :form="form"
            :course-id="course.id"
            :course-hashid="course.hashid"
          />

          <CourseReview
            v-if="currentStep === 5"
            :form="form"
            :selected-category="selectedCategory"
            :total-slides="totalSlides"
            :course-id="course.id"
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
              {{ $t('ui-previous') }}
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
                {{ $t('ui-save-next') }}
              </BButton>

              <BButton
                v-if="!isLastStep"
                type="button"
                variant="primary"
                class="footer-btn"
                @click="nextStep"
              >
                {{ $t('ui-next') }}
                <i class="ri-arrow-right-line ms-1"></i>
              </BButton>

              <BButton
                v-if="isLastStep"
                type="button"
                variant="success"
                class="footer-btn"
                :disabled="form.processing"
                @click="saveCurrentStep(false)"
              >
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                {{ $t('ui-save-changes') }}
              </BButton>

              <Link
                v-if="isLastStep"
                :href="route('courses.index')"
                class="btn btn-primary footer-btn"
              >
                {{ $t('ui-finish') }}
                <i class="ri-check-line ms-1"></i>
              </Link>
            </div>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>
