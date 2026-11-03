<script setup>
import { formatCourseLabel } from '@/composables/course-labels'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
  companyRequest: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  learners: {
    type: Array,
    default: () => [],
  },
  accreditations: {
    type: Array,
    default: () => [],
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  canChangeCourse: {
    type: Boolean,
    default: false,
  },
  canChangeLearners: {
    type: Boolean,
    default: false,
  },
  canChangeDetails: {
    type: Boolean,
    default: false,
  },
})

const currentStep = ref(1)
const selectedCategoryId = ref(props.companyRequest.course?.category_id || null)
const courseSearch = ref('')
const learnerSearch = ref('')
const selectedCourseLanguages = ref([])

const normalizeId = (value) => {
  const id = Number(value)

  return Number.isFinite(id) ? id : null
}

const normalizeIds = (values = []) => {
  return [...new Set(
    values
      .map((value) => normalizeId(value))
      .filter((value) => value !== null),
  )]
}

const initialLearnerIds = normalizeIds(
  props.companyRequest.learners?.map((learner) => learner.id) || [],
)

/*
|--------------------------------------------------------------------------
| Lock only learners attached to this exact company request
|--------------------------------------------------------------------------
|
| A learner can own another enrollment for the same course. The backend
| provides attached_to_request so another enrollment never locks this card.
|
*/
const lockedInitialLearnerIds = normalizeIds(
  props.learners
    .filter((learner) => {
      return Boolean(
        learner.attached_to_request &&
        learner.started_course,
      )
    })
    .map((learner) => learner.id),
)

const form = useForm({
  course_id:
    normalizeId(
      props.companyRequest.course_id ||
      props.companyRequest.course?.id,
    ) || null,
  accreditation_ids: normalizeIds(
    props.companyRequest.accreditation_ids ||
    props.companyRequest.accreditations?.map((accreditation) => accreditation.id) ||
    [],
  ),
  start_date: props.companyRequest.start_date || '',
  end_date: props.companyRequest.end_date || '',
  duree_validite_formation:
    props.companyRequest.duree_validite_formation ?? null,
  certificate_visible_to_learner:
    props.companyRequest.certificate_visible_to_learner !== false,
  learners: normalizeIds([
    ...initialLearnerIds,
    ...lockedInitialLearnerIds,
  ]),
  notes: props.companyRequest.notes || '',
})

const isApprovedRequest = computed(() => {
  return props.companyRequest.status === 'approved'
})

const steps = computed(() => [
  {
    id: 1,
    title: t('t-select-course'),
    description: t('t-step-course-details'),
    icon: 'ri-book-open-line',
  },
  {
    id: 2,
    title: t('t-training-details'),
    description: t('t-step-dates-approvals'),
    icon: 'ri-calendar-check-line',
  },
  {
    id: 3,
    title: t('t-add-learners'),
    description: t('t-step-manage-learners'),
    icon: 'ri-team-line',
  },
  {
    id: 4,
    title: t('t-request-summary'),
    description: t('t-step-review-changes'),
    icon: 'ri-file-check-line',
  },
])
const selectedCategory = computed(() => {
  return props.categories.find((category) => {
    return Number(category.id) === Number(selectedCategoryId.value)
  })
})

const categoryCourses = computed(() => {
  return selectedCategory.value?.courses || []
})

const normalizeLanguage = (value) => {
  return String(value ?? '').trim().toLowerCase()
}

const getCourseLanguageKey = (value) => {
  const language = normalizeLanguage(value)

  if (!language) {
    return 'unspecified'
  }

  if (['en', 'eng', 'english', 'anglais'].includes(language)) {
    return 'english'
  }

  if (
    ['fr', 'fra', 'fre', 'french', 'français', 'francais'].includes(language)
  ) {
    return 'french'
  }

  if (['ar', 'ara', 'arabic', 'arabe'].includes(language)) {
    return 'arabic'
  }

  return language
}

const getLanguageLabel = (value) => {
  const languageKey = getCourseLanguageKey(value)

  const labels = {
    english: t('ui-english'),
    french: t('ui-french'),
    arabic: t('ui-arabic'),
    unspecified: t('ui-not-specified'),
  }

  if (labels[languageKey]) {
    return labels[languageKey]
  }

  return languageKey
    .split(/[-_\s]+/)
    .filter(Boolean)
    .map((part) => {
      return part.charAt(0).toUpperCase() + part.slice(1)
    })
    .join(' ')
}

const languageFilterOptions = computed(() => {
  const options = new Map([
    ['english', { value: 'english', label: t('ui-english') }],
    ['french', { value: 'french', label: t('ui-french') }],
  ])

  let hasUnspecifiedLanguage = false

  categoryCourses.value.forEach((course) => {
    const languageKey = getCourseLanguageKey(course.language)

    if (languageKey === 'unspecified') {
      hasUnspecifiedLanguage = true
      return
    }

    if (!options.has(languageKey)) {
      options.set(languageKey, {
        value: languageKey,
        label: getLanguageLabel(course.language),
      })
    }
  })

  if (hasUnspecifiedLanguage) {
    options.set('unspecified', {
      value: 'unspecified',
      label: t('ui-not-specified'),
    })
  }

  return [...options.values()]
})

const allCourses = computed(() => {
  return props.categories.flatMap((category) => category.courses || [])
})

const filteredCourses = computed(() => {
  const search = courseSearch.value.toLowerCase().trim()
  const selectedLanguages = selectedCourseLanguages.value

  return categoryCourses.value.filter((course) => {
    const courseLanguageKey = getCourseLanguageKey(course.language)

    const matchesLanguage =
      selectedLanguages.length === 0 ||
      selectedLanguages.includes(courseLanguageKey)

    const matchesSearch =
      !search ||
      String(course.title ?? '').toLowerCase().includes(search) ||
      String(course.description ?? '').toLowerCase().includes(search) ||
      String(course.type ?? '').toLowerCase().includes(search) ||
      String(course.training_type ?? '').toLowerCase().includes(search) ||
      String(course.skill_level ?? '').toLowerCase().includes(search) ||
      String(course.language ?? '').toLowerCase().includes(search)

    return matchesSearch && matchesLanguage
  })
})

const filteredLearners = computed(() => {
  const search = learnerSearch.value.toLowerCase().trim()

  const source = isApprovedRequest.value
    ? props.learners.filter((learner) => learner.attached_to_request)
    : props.learners

  if (!search) {
    return source
  }

  return source.filter((learner) => {
    return (
      String(learner.name ?? '').toLowerCase().includes(search) ||
      String(learner.email ?? '').toLowerCase().includes(search)
    )
  })
})

const selectedCourse = computed(() => {
  const courseFromCategories = allCourses.value.find((course) => {
    return Number(course.id) === Number(form.course_id)
  })

  return courseFromCategories || props.companyRequest.course || null
})

const selectedLearners = computed(() => {
  return props.learners.filter((learner) => {
    return form.learners.some((learnerId) => {
      return Number(learnerId) === Number(learner.id)
    })
  })
})

const selectedAccreditations = computed(() => {
  return props.accreditations.filter((accreditation) => {
    return form.accreditation_ids.some((accreditationId) => {
      return Number(accreditationId) === Number(accreditation.id)
    })
  })
})

const isFirstStep = computed(() => currentStep.value === 1)
const isLastStep = computed(() => currentStep.value === steps.value.length)

const canSave = computed(() => {
  return (
    props.canChangeCourse ||
    props.canChangeLearners ||
    props.canChangeDetails ||
    props.canApprove
  )
})

const getCourseImage = (course) => {
  if (course?.image_url) {
    return course.image_url
  }

  if (course?.image) {
    return `/storage/${course.image}`
  }

  return null
}

const getInitials = (value, fallback = 'NA') => {
  if (!value) return fallback

  return String(value)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getTypeClass = (type) => {
  if (type === 'free') return 'mini-badge success'
  if (type === 'paid') return 'mini-badge primary'
  if (type === 'private') return 'mini-badge danger'

  return 'mini-badge'
}

const getStatusClass = (status) => {
  if (status === 'approved') return 'mini-badge success'
  if (status === 'rejected') return 'mini-badge danger'

  return 'mini-badge warning'
}

const formatPrice = (course) => {
  const price = Number(course?.price || 0)

  if (course?.type !== 'paid' || price === 0) {
    return t('t-free')
  }

  return `${price.toFixed(2)} ${course.currency || ''}`
}

const selectCategory = (category) => {
  if (!props.canChangeCourse) {
    return
  }

  selectedCategoryId.value = category.id
  form.course_id = null
  courseSearch.value = ''
  selectedCourseLanguages.value = []
}

const selectCourse = (course) => {
  if (!props.canChangeCourse) {
    return
  }

  form.course_id = course.id
}

const accreditationIsSelected = (accreditationId) => {
  return form.accreditation_ids.some((selectedId) => {
    return Number(selectedId) === Number(accreditationId)
  })
}

const toggleAccreditation = (accreditation) => {
  if (!props.canChangeDetails || !accreditation?.is_active) {
    return
  }

  if (accreditationIsSelected(accreditation.id)) {
    form.accreditation_ids = form.accreditation_ids.filter((selectedId) => {
      return Number(selectedId) !== Number(accreditation.id)
    })
    return
  }

  if (form.accreditation_ids.length >= 2) {
    showWarning('Maximum reached', 'You can select a maximum of two accreditations.')
    return
  }

  form.accreditation_ids = normalizeIds([
    ...form.accreditation_ids,
    accreditation.id,
  ])
}

const formatTrainingDate = (date) => {
  if (!date) return '-'

  return new Date(`${date}T00:00:00`).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const learnerIsLocked = (learner) => {
  if (isApprovedRequest.value) {
    return Boolean(learner?.attached_to_request)
  }

  return Boolean(
    learner?.attached_to_request &&
    learner?.started_course,
  )
}

const learnerIsSelected = (learner) => {
  return form.learners.some((learnerId) => {
    return Number(learnerId) === Number(learner.id)
  })
}

const getLockedLearnerIds = () => {
  return normalizeIds(
    props.learners
      .filter((learner) => learnerIsLocked(learner))
      .map((learner) => learner.id),
  )
}

const restoreLockedLearners = () => {
  form.learners = normalizeIds([
    ...form.learners,
    ...getLockedLearnerIds(),
  ])
}

const toggleLearner = (learner) => {
  if (!props.canChangeLearners) {
    return
  }

  if (learnerIsLocked(learner)) {
    if (!learnerIsSelected(learner)) {
      form.learners = normalizeIds([
        ...form.learners,
        learner.id,
      ])
    }

    Swal.fire({
      icon: 'info',
      title: t('t-learner-locked'),
      text: t('t-learner-course-started-locked'),
      timer: 2500,
      showConfirmButton: false,
    })

    return
  }

  if (learnerIsSelected(learner)) {
    form.learners = form.learners.filter((learnerId) => {
      return Number(learnerId) !== Number(learner.id)
    })

    restoreLockedLearners()
    return
  }

  form.learners = normalizeIds([
    ...form.learners,
    learner.id,
  ])
}

const selectAllFilteredLearners = () => {
  if (!props.canChangeLearners) {
    return
  }

  const ids = filteredLearners.value.map((learner) => learner.id)

  form.learners = normalizeIds([
    ...form.learners,
    ...ids,
  ])

  restoreLockedLearners()
}

const clearLearners = () => {
  if (!props.canChangeLearners) {
    return
  }

  const lockedIds = getLockedLearnerIds()

  form.learners = normalizeIds(lockedIds)

  Swal.fire({
    icon: 'info',
    title: t('t-locked-learners-kept'),
    text: t('t-started-learners-cannot-remove'),
    timer: 2200,
    showConfirmButton: false,
  })
}

const showWarning = (title, text) => {
  Swal.fire({
    icon: 'warning',
    title,
    text,
    timer: 2500,
    showConfirmButton: false,
  })
}

const validateStep = (step = currentStep.value) => {
  if (step === 1) {
    if (!form.course_id) {
      showWarning('Course required', 'Please select one course.')
      return false
    }
  }

  if (step === 2 && !isApprovedRequest.value) {
    if (!form.accreditation_ids.length) {
      showWarning('Accreditation required', 'Please select at least one accreditation.')
      return false
    }

    if (!form.start_date || !form.end_date) {
      showWarning('Training dates required', 'Please enter the start and end dates.')
      return false
    }

    if (form.end_date < form.start_date) {
      showWarning('Invalid period', 'The end date must be after or equal to the start date.')
      return false
    }

    if (
      form.duree_validite_formation !== null &&
      form.duree_validite_formation !== ''
    ) {
      const validityMonths = Number(form.duree_validite_formation)

      if (!Number.isInteger(validityMonths) || validityMonths < 1 || validityMonths > 1200) {
        showWarning(
          'Invalid validity duration',
          'Enter a whole number between 1 and 1200 months, or leave the field empty.'
        )
        return false
      }
    }
  }

  if (step === 3) {
    if (!form.learners.length) {
      showWarning('Learners required', 'Please select at least one learner.')
      return false
    }
  }

  return true
}

const validateLockedLearnersBeforeSubmit = () => {
  if (isApprovedRequest.value) {
    form.course_id = normalizeId(
      props.companyRequest.course_id || props.companyRequest.course?.id,
    )
    form.accreditation_ids = normalizeIds(
      props.companyRequest.accreditation_ids ||
      props.companyRequest.accreditations?.map((accreditation) => accreditation.id) ||
      [],
    )
    form.start_date = props.companyRequest.start_date || ''
    form.end_date = props.companyRequest.end_date || ''
    form.duree_validite_formation =
      props.companyRequest.duree_validite_formation ?? null
    form.certificate_visible_to_learner =
      props.companyRequest.certificate_visible_to_learner !== false
    form.learners = [...initialLearnerIds]

    return true
  }

  const lockedIds = getLockedLearnerIds()

  const missingLockedIds = lockedIds.filter((lockedId) => {
    return !form.learners.some((learnerId) => {
      return Number(learnerId) === Number(lockedId)
    })
  })

  if (missingLockedIds.length) {
    form.learners = normalizeIds([
      ...form.learners,
      ...lockedIds,
    ])

    Swal.fire({
      icon: 'warning',
      title: t('ui-locked-learners-restored'),
      text: t('t-started-learners-cannot-remove'),
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  return true
}

const goToStep = (stepId) => {
  if (stepId > currentStep.value) {
    if (!validateStep(currentStep.value)) return
  }

  currentStep.value = stepId
}

const nextStep = () => {
  if (!validateStep(currentStep.value)) return

  if (currentStep.value < steps.value.length) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const submit = () => {
  if (!canSave.value) {
    Swal.fire({
      icon: 'warning',
      title: t('t-request-locked'),
      text: t('ui-you-cannot-update-this-request'),
      timer: 2500,
      showConfirmButton: false,
    })

    return
  }

  if (!validateStep(1)) return
  if (!validateStep(2)) return
  if (!validateStep(3)) return
  if (!validateLockedLearnersBeforeSubmit()) return

  form.post(route('company-requests.update', props.companyRequest.hashid), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-updated-label'),
        text: t('ui-request-updated-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })
    },
    onError: (errors) => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: errors?.message || 'Please check the form and try again.',
      })
    },
  })
}

watch(selectedCategoryId, () => {
  if (props.canChangeCourse) {
    form.course_id = null
    selectedCourseLanguages.value = []
  }
})

watch(
  selectedCourseLanguages,
  () => {
    if (!props.canChangeCourse || !form.course_id) {
      return
    }

    const selectedCourseIsVisible = filteredCourses.value.some((course) => {
      return Number(course.id) === Number(form.course_id)
    })

    if (!selectedCourseIsVisible) {
      form.course_id = null
    }
  },
  { deep: true }
)

watch(
  () => props.learners,
  () => {
    restoreLockedLearners()
  },
  { deep: true }
)
</script>

<template>
  <Layout>
    <Head :title="$t('ui-edit-reference', { reference: companyRequest.reference })" />
    <PageHeader :title="$t('ui-edit-request')" :page-title="$t('ui-company-requests')" />

    <div class="request-edit-page">
      <BCard class="request-wizard-card border-0 shadow-sm">
        <BCardHeader class="request-wizard-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="request-title-box">
              <div class="request-main-icon">
                <i class="ri-pencil-line"></i>
              </div>

              <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <h4 class="mb-0 fw-bold">{{ $t('t-edit-request') }}</h4>

                  <span :class="getStatusClass(companyRequest.status)">
                    {{ companyRequest.status || 'pending' }}
                  </span>
                </div>

                <p class="text-muted mb-0">
                  {{ companyRequest.reference }} · {{ companyRequest.company?.name || '-' }}
                </p>
              </div>
            </div>

            <Link
              :href="route('company-requests.show', companyRequest.hashid)"
              class="btn btn-light back-btn"
            >
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('t-back-to-details') }}
            </Link>
          </div>
        </BCardHeader>

        <BCardBody>
          <div
            v-if="isApprovedRequest"
            class="alert alert-warning d-flex align-items-start gap-2 request-alert"
          >
            <i class="ri-lock-line fs-5"></i>
            <div>
              <strong>{{ $t('t-approved-request') }}</strong>
              <div>
                {{ $t('ui-the-course-accreditations-training-period-validity-dur') }}
              </div>
            </div>
          </div>

          <div
            v-else-if="canApprove"
            class="alert alert-info d-flex align-items-start gap-2 request-alert"
          >
            <i class="ri-information-line fs-5"></i>
            <div>
              <strong>{{ $t('t-super-admin-mode') }}</strong>
              <div>
                {{ $t('ui-you-can-modify-the-course-accreditations-training-peri') }}
              </div>
            </div>
          </div>

          <div
            v-else-if="companyRequest.status !== 'pending'"
            class="alert alert-warning d-flex align-items-start gap-2 request-alert"
          >
            <i class="ri-lock-line fs-5"></i>
            <div>
              <strong>{{ $t('t-request-locked') }}</strong>
              <div>
                {{ $t('ui-you-cannot-modify-this-request-because-it-has-already-') }}
              </div>
            </div>
          </div>

          <div class="request-steps mb-4">
            <button
              v-for="stepItem in steps"
              :key="stepItem.id"
              type="button"
              class="request-step"
              :class="{
                active: currentStep === stepItem.id,
                completed: currentStep > stepItem.id,
              }"
              @click="goToStep(stepItem.id)"
            >
              <span class="step-icon">
                <i
                  :class="currentStep > stepItem.id ? 'ri-check-line' : stepItem.icon"
                ></i>
              </span>

              <span class="step-content">
                <strong>{{ stepItem.title }}</strong>
                <small>{{ stepItem.description }}</small>
              </span>
            </button>
          </div>

          <!-- STEP 1 -->
          <div v-if="currentStep === 1" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>{{ $t('t-select-course') }}</h5>
                <p>
                  {{
                    canChangeCourse
                      ? 'Select a category and one course.'
                      : 'Course is locked because this request is approved.'
                  }}
                </p>
              </div>

              <span class="section-counter">
                {{ canChangeCourse ? 'Editable' : 'Locked' }}
              </span>
            </div>

            <BRow class="g-4">
              <BCol xl="4" lg="5">
                <div class="side-panel">
                  <div class="side-panel-title">
                    <i class="ri-folder-3-line"></i>
                    <span>{{ $t('t-categories') }}</span>
                  </div>

                  <div v-if="categories.length" class="category-list">
                    <button
                      v-for="category in categories"
                      :key="category.id"
                      type="button"
                      class="category-item"
                      :class="{
                        active: Number(selectedCategoryId) === Number(category.id),
                        disabled: !canChangeCourse,
                      }"
                      @click="selectCategory(category)"
                    >
                      <div class="category-icon">
                        <i class="ri-folder-line"></i>
                      </div>

                      <div class="min-width-0 flex-grow-1">
                        <h6>{{ category.name }}</h6>
                        <p>
                          {{ category.courses_count }}
                          {{ $t('ui-course-label') }}{{ Number(category.courses_count || 0) > 1 ? 's' : '' }}
                        </p>
                      </div>

                      <i
                        class="category-check"
                        :class="
                          Number(selectedCategoryId) === Number(category.id)
                            ? 'ri-checkbox-circle-fill'
                            : 'ri-arrow-right-s-line'
                        "
                      ></i>
                    </button>
                  </div>

                  <div v-else class="empty-small">
                    <i class="ri-folder-warning-line"></i>
                    <span>{{ $t('t-no-categories') }}</span>
                  </div>
                </div>
              </BCol>

              <BCol xl="8" lg="7">
                <div class="main-panel">
                  <div class="panel-top">
                    <div>
                      <h6 class="mb-1">
                        {{ selectedCategory?.name || 'Selected Course' }}
                      </h6>

                      <p class="text-muted mb-0">
                        {{
                          canChangeCourse
                            ? 'Choose one course for this request.'
                            : 'This course cannot be changed.'
                        }}
                      </p>
                    </div>

                    <span class="section-counter">
                      {{ filteredCourses.length }} {{ $t('ui-courses-label') }}
                    </span>
                  </div>

                  <div
                    v-if="canChangeCourse"
                    class="search-box request-search-box mb-3"
                  >
                    <input
                      v-model="courseSearch"
                      type="text"
                      class="form-control"
                      :placeholder="$t('ui-search-course')"
                    />
                    <i class="ri-search-line search-icon"></i>
                  </div>

                  <div
                    v-if="selectedCategory && canChangeCourse"
                    class="course-language-filter mb-3"
                  >
                    <div class="course-language-filter-header">
                      <div class="course-language-filter-title">
                        <i class="ri-translate-2"></i>

                        <div>
                          <strong>{{ $t('t-filter-by-language') }}</strong>
                          <small>{{ $t('t-select-languages') }}</small>
                        </div>
                      </div>

                      <button
                        v-if="selectedCourseLanguages.length"
                        type="button"
                        class="course-language-clear"
                        @click="selectedCourseLanguages = []"
                      >
                        <i class="ri-close-circle-line"></i>
                        {{ $t('t-clear') }}
                      </button>
                    </div>

                    <div class="course-language-options">
                      <label
                        v-for="language in languageFilterOptions"
                        :key="language.value"
                        class="course-language-option"
                        :class="{
                          active: selectedCourseLanguages.includes(language.value),
                        }"
                      >
                        <input
                          v-model="selectedCourseLanguages"
                          type="checkbox"
                          :value="language.value"
                        />

                        <span class="course-language-checkbox">
                          <i class="ri-check-line"></i>
                        </span>

                        <span class="course-language-name">
                          {{ language.label }}
                        </span>
                      </label>
                    </div>
                  </div>

                  <div
                    v-if="!selectedCategory && canChangeCourse"
                    class="empty-box"
                  >
                    <i class="ri-book-open-line"></i>
                    <h6>{{ $t('t-select-category') }}</h6>
                    <p>{{ $t('t-select-category-before-courses') }}</p>
                  </div>

                  <div v-else-if="filteredCourses.length" class="course-select-list">
                    <div
                      v-for="course in filteredCourses"
                      :key="course.id"
                      class="course-select-card"
                      :class="{
                        active: Number(form.course_id) === Number(course.id),
                        disabled: !canChangeCourse,
                      }"
                      @click="selectCourse(course)"
                    >
                      <div class="course-list-image">
                        <img
                          v-if="getCourseImage(course)"
                          :src="getCourseImage(course)"
                          :alt="$t('t-course-image')"
                        />

                        <div v-else class="course-list-fallback">
                          {{ getInitials(course.title, 'CO') }}
                        </div>
                      </div>

                      <div class="course-select-content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                          <h6 class="mb-0">{{ course.title }}</h6>

                          <span :class="getTypeClass(course.type)">
                            {{ course.type === 'paid' ? $t('ui-paid') : course.type ? $t('t-free') : $t('t-course') }}
                          </span>
                        </div>

                        <p>
                          {{ course.description || 'No description available.' }}
                        </p>

                        <div class="course-meta-row">
                          <span>
                            <i class="ri-computer-line me-1"></i>
                            {{ formatCourseLabel(course.training_type) }}
                          </span>

                          <span>
                            <i class="ri-bar-chart-line me-1"></i>
                            {{ formatCourseLabel(course.skill_level) }}
                          </span>

                          <span>
                            <i class="ri-list-check-2 me-1"></i>
                            {{ course.chapters_count || 0 }} {{ $t('ui-chapters-label') }}
                          </span>

                          <span>
                            <i class="ri-slideshow-line me-1"></i>
                            {{ course.slides_count || 0 }} {{ $t('ui-slides-label') }}
                          </span>

                          <span>
                            <i class="ri-translate-2 me-1"></i>
                            {{ getLanguageLabel(course.language) }}
                          </span>

                          <span>
                            <i class="ri-money-dollar-circle-line me-1"></i>
                            {{ formatPrice(course) }}
                          </span>
                        </div>
                      </div>

                      <div class="course-select-check">
                        <i
                          :class="
                            Number(form.course_id) === Number(course.id)
                              ? 'ri-checkbox-circle-fill'
                              : 'ri-checkbox-blank-circle-line'
                          "
                        ></i>
                      </div>
                    </div>
                  </div>

                  <div v-else class="empty-box">
                    <i class="ri-search-eye-line"></i>
                    <h6>{{ $t('t-no-courses') }}</h6>
                    <p>{{ $t('t-no-courses-match-search') }}</p>
                  </div>
                </div>
              </BCol>
            </BRow>
          </div>

          <!-- STEP 2 -->
          <div v-if="currentStep === 2" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>{{ $t('t-training-details') }}</h5>
                <p>
                  {{
                    canChangeDetails
                      ? 'Update the requested accreditations and training period.'
                      : 'Accreditations and training dates are locked after approval.'
                  }}
                </p>
              </div>

              <span class="section-counter">
                {{ canChangeDetails ? 'Editable' : 'Locked' }}
              </span>
            </div>

            <BRow class="g-4">
              <BCol xl="5" lg="5">
                <div class="details-panel h-100">
                  <div class="details-panel-title">
                    <div class="details-title-icon">
                      <i class="ri-calendar-event-line"></i>
                    </div>

                    <div>
                      <h6>{{ $t('t-training-period') }}</h6>
                      <p>{{ $t('t-planned-dates-help') }}</p>
                    </div>
                  </div>

                  <div class="training-date-grid">
                    <div class="form-field-box">
                      <label for="start_date">{{ $t('t-planned-start-date') }}</label>
                      <div class="date-input-wrap">
                        <i class="ri-calendar-2-line"></i>
                        <input
                          id="start_date"
                          v-model="form.start_date"
                          type="date"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.start_date }"
                          :disabled="!canChangeDetails"
                          :max="form.end_date || undefined"
                        />
                      </div>
                      <div v-if="form.errors.start_date" class="invalid-feedback d-block">
                        {{ form.errors.start_date }}
                      </div>
                    </div>

                    <div class="form-field-box">
                      <label for="end_date">{{ $t('t-planned-end-date') }}</label>
                      <div class="date-input-wrap">
                        <i class="ri-calendar-check-line"></i>
                        <input
                          id="end_date"
                          v-model="form.end_date"
                          type="date"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.end_date }"
                          :disabled="!canChangeDetails"
                          :min="form.start_date || undefined"
                        />
                      </div>
                      <div v-if="form.errors.end_date" class="invalid-feedback d-block">
                        {{ form.errors.end_date }}
                      </div>
                    </div>
                  </div>

                  <div class="form-field-box mt-3">
                    <label for="duree_validite_formation">
                      {{ $t('t-validity-after-completion') }}
                    </label>

                    <div class="date-input-wrap">
                      <i :class="canChangeDetails ? 'ri-timer-line' : 'ri-lock-line'"></i>
                      <input
                        id="duree_validite_formation"
                        v-model.number="form.duree_validite_formation"
                        type="number"
                        min="1"
                        max="1200"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.duree_validite_formation }"
                        :disabled="!canChangeDetails"
                        :placeholder="$t('ui-example-12')"
                      />
                    </div>

                    <small class="validity-help">
                      {{ $t('ui-leave-empty-when-the-course-or-certificate-has-no-expi') }}
                    </small>

                    <div
                      v-if="form.errors.duree_validite_formation"
                      class="invalid-feedback d-block"
                    >
                      {{ form.errors.duree_validite_formation }}
                    </div>
                  </div>

                  <div class="period-preview">
                    <i :class="canChangeDetails ? 'ri-time-line' : 'ri-lock-line'"></i>
                    <div>
                      <span>{{ $t('t-selected-period') }}</span>
                      <strong>
                        {{ formatTrainingDate(form.start_date) }}
                        <i class="ri-arrow-right-line mx-1"></i>
                        {{ formatTrainingDate(form.end_date) }}
                      </strong>
                    </div>
                  </div>

                  <div class="validity-preview">
                    <i class="ri-award-line"></i>
                    <div>
                      <span>{{ $t('t-course-certificate-validity') }}</span>
                      <strong>
                        {{
                          form.duree_validite_formation
                            ? `${form.duree_validite_formation} ${t(Number(form.duree_validite_formation) > 1 ? 't-month-plural' : 't-month-singular')}`
                            : t('t-no-expiration')
                        }}
                      </strong>
                    </div>
                  </div>

                  <div
                    class="certificate-visibility-box"
                    :class="{
                      enabled: form.certificate_visible_to_learner,
                      locked: !canChangeDetails,
                    }"
                  >
                    <div class="certificate-visibility-icon">
                      <i
                        :class="
                          !canChangeDetails
                            ? 'ri-lock-line'
                            : form.certificate_visible_to_learner
                              ? 'ri-eye-line'
                              : 'ri-eye-off-line'
                        "
                      ></i>
                    </div>

                    <div class="certificate-visibility-content">
                      <span>{{ $t('t-certificate-distribution') }}</span>
                      <strong>
                        {{
                          form.certificate_visible_to_learner
                            ? 'Visible to learners'
                            : 'Hidden from learners'
                        }}
                      </strong>
                      <small>
                        {{ $t('ui-certificates-are-still-generated-when-hidden-but-learn') }}
                      </small>
                    </div>

                    <div class="form-check form-switch certificate-switch">
                      <input
                        id="certificate_visible_to_learner"
                        v-model="form.certificate_visible_to_learner"
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        :disabled="!canChangeDetails"
                      />
                    </div>
                  </div>

                  <div
                    v-if="form.errors.certificate_visible_to_learner"
                    class="invalid-feedback d-block mt-2"
                  >
                    {{ form.errors.certificate_visible_to_learner }}
                  </div>
                </div>
              </BCol>

              <BCol xl="7" lg="7">
                <div class="details-panel h-100">
                  <div class="details-panel-title">
                    <div class="details-title-icon">
                      <i class="ri-shield-check-line"></i>
                    </div>

                    <div>
                      <h6>{{ $t('t-requested-accreditations') }}</h6>
                      <p>{{ $t('t-select-accreditations') }}</p>
                    </div>
                  </div>

                  <div v-if="accreditations.length" class="accreditation-grid">
                    <button
                      v-for="accreditation in accreditations"
                      :key="accreditation.id"
                      type="button"
                      class="accreditation-card"
                      :class="{
                        active: accreditationIsSelected(accreditation.id),
                        disabled: !canChangeDetails,
                        inactive: !accreditation.is_active,
                      }"
                      :disabled="!canChangeDetails || !accreditation.is_active"
                      @click="toggleAccreditation(accreditation)"
                    >
                      <div class="accreditation-card-top">
                        <div class="accreditation-logo">
                          <i class="ri-government-line"></i>
                        </div>

                        <i
                          class="accreditation-check"
                          :class="
                            !canChangeDetails
                              ? 'ri-lock-fill'
                              : accreditationIsSelected(accreditation.id)
                                ? 'ri-checkbox-circle-fill'
                                : 'ri-checkbox-blank-circle-line'
                          "
                        ></i>
                      </div>

                      <h6>{{ accreditation.name }}</h6>
                      <p>{{ accreditation.organization_name || '-' }}</p>

                      <div class="accreditation-meta">
                        <span>
                          <i class="ri-building-line"></i>
                          {{ accreditation.authority_name || 'Aviation authority' }}
                        </span>

                        <span v-if="accreditation.approval_reference">
                          <i class="ri-hashtag"></i>
                          {{ accreditation.approval_reference }}
                        </span>
                      </div>
                    </button>
                  </div>

                  <div v-else class="empty-small accreditation-empty">
                    <i class="ri-shield-cross-line"></i>
                    <span>{{ $t('t-no-accreditations') }}</span>
                  </div>

                  <div v-if="form.errors.accreditation_ids" class="text-danger small mt-2">
                    {{ form.errors.accreditation_ids }}
                  </div>
                </div>
              </BCol>
            </BRow>
          </div>

          <!-- STEP 3 -->
          <div v-if="currentStep === 3" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>{{ isApprovedRequest ? 'Learners' : t('t-add-learners') }}</h5>
                <p>
                  {{
                    isApprovedRequest
                      ? 'This request is approved. The learner list is read-only.'
                      : 'Select learners. Learners who already started this request are locked.'
                  }}
                </p>
              </div>

              <span class="section-counter">
                {{ form.learners.length }} {{ $t('ui-selected-label') }}
              </span>
            </div>

            <div class="learner-toolbar">
              <div class="search-box request-search-box">
                <input
                  v-model="learnerSearch"
                  type="text"
                  class="form-control"
                  :placeholder="$t('ui-search-learner')"
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <div v-if="canChangeLearners" class="learner-toolbar-actions">
                <button
                  type="button"
                  class="btn btn-light"
                  :disabled="!canChangeLearners"
                  @click="selectAllFilteredLearners"
                >
                  <i class="ri-check-double-line me-1"></i>
                  {{ $t('t-select-filtered') }}
                </button>

                <button
                  type="button"
                  class="btn btn-light"
                  :disabled="!canChangeLearners"
                  @click="clearLearners"
                >
                  <i class="ri-close-circle-line me-1"></i>
                  {{ $t('t-clear') }}
                </button>
              </div>
            </div>

            <div v-if="filteredLearners.length" class="learner-grid">
              <div
                v-for="learner in filteredLearners"
                :key="learner.id"
                class="learner-card"
                :class="{
                  active: learnerIsSelected(learner),
                  locked: learnerIsLocked(learner),
                  disabled: !canChangeLearners,
                }"
                @click="toggleLearner(learner)"
              >
                <div class="learner-avatar">
                  <img
                    v-if="learner.photo_url"
                    :src="learner.photo_url"
                    :alt="$t('ui-learner')"
                  />

                  <span v-else>
                    {{ getInitials(learner.name, 'LR') }}
                  </span>
                </div>

                <div class="min-width-0 flex-grow-1">
                  <h6>{{ learner.name }}</h6>
                  <p>{{ learner.email }}</p>

                  <small
                    v-if="isApprovedRequest"
                    class="text-warning fw-semibold"
                  >
                    {{ $t('t-approved-request-locked') }}
                  </small>

                  <small
                    v-else-if="learnerIsLocked(learner)"
                    class="text-danger fw-semibold"
                  >
                    {{ $t('t-request-started-locked') }}
                  </small>
                </div>

                <i
                  class="learner-check"
                  :class="
                    learnerIsLocked(learner)
                      ? 'ri-lock-fill'
                      : learnerIsSelected(learner)
                        ? 'ri-checkbox-circle-fill'
                        : 'ri-checkbox-blank-circle-line'
                  "
                ></i>
              </div>
            </div>

            <div v-else class="empty-box">
              <i class="ri-user-search-line"></i>
              <h6>{{ $t('t-no-learners') }}</h6>
              <p>{{ $t('t-no-learners-match-search') }}</p>
            </div>
          </div>

          <!-- STEP 4 -->
          <div v-if="currentStep === 4" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>{{ $t('t-request-summary') }}</h5>
                <p>{{ $t('t-review-before-saving') }}</p>
              </div>

              <span class="section-counter">
                {{ $t('t-update-request') }}
              </span>
            </div>

            <BRow class="g-4">
              <BCol lg="7">
                <div class="summary-panel">
                  <div class="summary-block">
                    <span>{{ $t('t-reference') }}</span>
                    <strong>{{ companyRequest.reference }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-status') }}</span>
                    <strong>{{ companyRequest.status }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-company') }}</span>
                    <strong>{{ companyRequest.company?.name || '-' }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-selected-course') }}</span>

                    <div v-if="selectedCourse" class="summary-course">
                      <div class="summary-course-image">
                        <img
                          v-if="getCourseImage(selectedCourse)"
                          :src="getCourseImage(selectedCourse)"
                          :alt="$t('ui-course')"
                        />

                        <div v-else>
                          {{ getInitials(selectedCourse.title, 'CO') }}
                        </div>
                      </div>

                      <div class="min-width-0">
                        <h6>{{ selectedCourse.title }}</h6>
                        <p>
                          {{ formatCourseLabel(selectedCourse.training_type) }}
                          ·
                          {{ formatCourseLabel(selectedCourse.skill_level) }}
                          ·
                          {{ formatPrice(selectedCourse) }}
                        </p>
                      </div>
                    </div>

                    <strong v-else>{{ $t('t-no-course-selected') }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-training-period') }}</span>
                    <strong>
                      {{ formatTrainingDate(form.start_date) }}
                      —
                      {{ formatTrainingDate(form.end_date) }}
                    </strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-validity-duration') }}</span>
                    <strong>
                      {{
                        form.duree_validite_formation
                          ? `${form.duree_validite_formation} ${t(Number(form.duree_validite_formation) > 1 ? 't-month-plural' : 't-month-singular')}`
                          : t('t-no-expiration')
                      }}
                    </strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-certificate-distribution-title') }}</span>
                    <strong
                      :class="
                        form.certificate_visible_to_learner
                          ? 'text-success'
                          : 'text-warning'
                      "
                    >
                      <i
                        :class="
                          form.certificate_visible_to_learner
                            ? 'ri-eye-line me-1'
                            : 'ri-eye-off-line me-1'
                        "
                      ></i>
                      {{
                        form.certificate_visible_to_learner
                          ? 'Visible to learners'
                          : 'Hidden from learners'
                      }}
                    </strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-requested-accreditations') }}</span>

                    <div v-if="selectedAccreditations.length" class="summary-accreditations">
                      <span
                        v-for="accreditation in selectedAccreditations"
                        :key="accreditation.id"
                        class="summary-accreditation-badge"
                      >
                        <i class="ri-shield-check-line"></i>
                        {{ accreditation.name }}
                      </span>
                    </div>

                    <strong v-else>{{ $t('t-not-selected') }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>{{ $t('t-notes') }}</span>

                    <textarea
                      v-model="form.notes"
                      class="form-control modern-textarea"
                      rows="5"
                      :placeholder="$t('ui-add-notes')"
                    ></textarea>
                  </div>
                </div>
              </BCol>

              <BCol lg="5">
                <div class="summary-panel">
                  <div class="summary-header-mini">
                    <div>
                      <h6>{{ $t('t-selected-learners') }}</h6>
                      <p>{{ selectedLearners.length }} {{ $t('ui-learner-s') }}</p>
                    </div>

                    <button
                      v-if="canChangeLearners"
                      type="button"
                      class="btn btn-sm btn-light"
                      @click="currentStep = 3"
                    >
                      {{ $t('t-edit') }}
                    </button>

                    <span
                      v-else
                      class="mini-badge warning"
                    >
                      {{ $t('t-locked') }}
                    </span>
                  </div>

                  <div v-if="selectedLearners.length" class="selected-learners-box">
                    <div
                      v-for="learner in selectedLearners"
                      :key="learner.id"
                      class="selected-learner-mini"
                    >
                      <div class="selected-learner-avatar">
                        <img
                          v-if="learner.photo_url"
                          :src="learner.photo_url"
                          :alt="$t('ui-learner')"
                        />

                        <span v-else>
                          {{ getInitials(learner.name, 'LR') }}
                        </span>
                      </div>

                      <div class="min-width-0 flex-grow-1">
                        <h6>{{ learner.name }}</h6>
                        <p>{{ learner.email }}</p>
                      </div>

                      <span
                        v-if="learnerIsLocked(learner)"
                        class="mini-badge danger"
                      >
                        {{ $t('t-locked') }}
                      </span>
                    </div>
                  </div>

                  <div v-else class="empty-small">
                    <i class="ri-team-line"></i>
                    <span>{{ $t('t-no-learners-selected') }}</span>
                  </div>

                  <div class="summary-info mt-3">
                    <i class="ri-information-line"></i>
                    <span>
                      {{
                        isApprovedRequest
                          ? 'The learner list cannot be changed after approval.'
                          : 'Learners who already started this request cannot be removed.'
                      }}
                    </span>
                  </div>
                </div>
              </BCol>
            </BRow>
          </div>

          <div class="wizard-footer">
            <BButton
              type="button"
              variant="light"
              class="footer-btn"
              :disabled="isFirstStep"
              @click="previousStep"
            >
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('t-previous') }}
            </BButton>

            <div class="d-flex flex-wrap gap-2 justify-content-end">
              <BButton
                v-if="!isLastStep"
                type="button"
                variant="primary"
                class="footer-btn"
                @click="nextStep"
              >
                {{ $t('t-next') }}
                <i class="ri-arrow-right-line ms-1"></i>
              </BButton>

              <BButton
                v-if="isLastStep"
                type="button"
                variant="success"
                class="footer-btn"
                :disabled="form.processing || !canSave"
                @click="submit"
              >
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>

                <i v-else class="ri-save-line me-1"></i>
                {{ $t('t-save-changes') }}
              </BButton>
            </div>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>

<style scoped>
.request-edit-page {
  width: 100%;
}

.request-wizard-card {
  border-radius: 18px;
  overflow: hidden;
  background-color: #ffffff;
}

.request-wizard-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #eef2f7;
}

.request-title-box {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.request-main-icon {
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
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.25);
}

.back-btn {
  height: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.request-alert {
  border-radius: 14px;
}

.request-steps {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.request-step {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  background: #ffffff;
  padding: 14px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  transition: all 0.2s ease;
  cursor: pointer;
}

.request-step:hover {
  border-color: rgba(104, 124, 254, 0.45);
  background: #f8fafc;
}

.request-step.active {
  border-color: #687cfe;
  background: #eef2ff;
  color: #405189;
  box-shadow: 0 12px 28px rgba(104, 124, 254, 0.13);
}

.request-step.completed {
  border-color: rgba(10, 179, 156, 0.25);
  background: rgba(10, 179, 156, 0.08);
  color: #0ab39c;
}

.step-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 14px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.request-step.active .step-icon {
  background: #687cfe;
  color: #ffffff;
}

.request-step.completed .step-icon {
  background: #0ab39c;
  color: #ffffff;
}

.step-content strong {
  display: block;
  color: #334155;
  font-weight: 900;
  margin-bottom: 2px;
}

.step-content small {
  color: #64748b;
  font-weight: 700;
}

.wizard-section {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  padding: 22px;
  background: #ffffff;
}

.section-heading {
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 14px;
}

.section-heading h5 {
  color: #334155;
  font-weight: 850;
  margin-bottom: 4px;
}

.section-heading p {
  color: #64748b;
  margin-bottom: 0;
}

.section-counter {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eef2ff;
  color: #405189;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.side-panel,
.main-panel,
.summary-panel {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  padding: 16px;
}

.side-panel-title {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #334155;
  font-weight: 900;
  margin-bottom: 14px;
}

.side-panel-title i {
  color: #405189;
  font-size: 18px;
}

.panel-top {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: flex-start;
  margin-bottom: 16px;
}

.panel-top h6 {
  color: #334155;
  font-weight: 900;
}

.category-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.category-item {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 12px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  transition: all 0.2s ease;
  cursor: pointer;
  width: 100%;
}

.category-item:hover {
  background: #f8fafc;
  border-color: rgba(104, 124, 254, 0.4);
}

.category-item.active {
  border-color: #687cfe;
  background: #eef2ff;
}

.category-icon {
  width: 40px;
  height: 40px;
  min-width: 40px;
  border-radius: 13px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.category-item.active .category-icon {
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
}

.category-item h6 {
  color: #334155;
  font-weight: 850;
  margin-bottom: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-item p {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 0;
}

.category-check {
  color: #687cfe;
  font-size: 22px;
}

.request-search-box {
  width: 100%;
}

.request-search-box .form-control {
  height: 44px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.request-search-box .form-control:focus,
.modern-textarea:focus {
  border-color: #687cfe !important;
  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14) !important;
}

.course-language-filter {
  border: 1px solid #e5eaf3;
  border-radius: 16px;
  background: #ffffff;
  padding: 14px;
}

.course-language-filter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.course-language-filter-title {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.course-language-filter-title > i {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 12px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
}

.course-language-filter-title strong {
  display: block;
  color: #334155;
  font-size: 13px;
  font-weight: 900;
  margin-bottom: 1px;
}

.course-language-filter-title small {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
}

.course-language-clear {
  border: 0;
  background: transparent;
  color: #f06548;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px;
}

.course-language-options {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
}

.course-language-option {
  position: relative;
  min-height: 38px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #f8fafc;
  color: #475569;
  padding: 8px 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
  transition: all 0.2s ease;
}

.course-language-option:hover {
  border-color: rgba(104, 124, 254, 0.5);
  background: #f4f6ff;
}

.course-language-option.active {
  border-color: #687cfe;
  background: #eef2ff;
  color: #405189;
  box-shadow: 0 8px 18px rgba(104, 124, 254, 0.12);
}

.course-language-option input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.course-language-checkbox {
  width: 20px;
  height: 20px;
  min-width: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  background: #ffffff;
  color: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: all 0.2s ease;
}

.course-language-option.active .course-language-checkbox {
  border-color: #687cfe;
  background: #687cfe;
  color: #ffffff;
}

.course-language-name {
  font-size: 12px;
  font-weight: 800;
}

.course-select-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.course-select-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  padding: 14px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.course-select-card:hover {
  background: #f8fafc;
  border-color: rgba(104, 124, 254, 0.4);
}

.course-select-card.active {
  border-color: #687cfe;
  background: #eef2ff;
  box-shadow: 0 12px 28px rgba(104, 124, 254, 0.13);
}

.course-list-image {
  width: 70px;
  height: 70px;
  min-width: 70px;
  border-radius: 18px;
  overflow: hidden;
  background: #eef2ff;
  border: 1px solid #edf2f7;
}

.course-list-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-list-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}

.course-select-content {
  min-width: 0;
  flex: 1;
}

.course-select-content h6 {
  color: #334155;
  font-weight: 850;
}

.course-select-content p {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.course-meta-row span {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.course-select-check {
  color: #687cfe;
  font-size: 26px;
}

/* ================= TRAINING DETAILS ================= */
.details-panel {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  padding: 18px;
}

.details-panel-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
}

.details-title-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 14px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
}

.details-panel-title h6 {
  color: #334155;
  font-weight: 900;
  margin-bottom: 2px;
}

.details-panel-title p {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 0;
}

.training-date-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}

.form-field-box label {
  display: block;
  color: #334155;
  font-size: 13px;
  font-weight: 800;
  margin-bottom: 7px;
}

.date-input-wrap {
  position: relative;
}

.date-input-wrap > i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #687cfe;
  font-size: 18px;
  z-index: 2;
  pointer-events: none;
}

.date-input-wrap .form-control {
  height: 46px;
  border-radius: 13px;
  border-color: #e5e7eb;
  padding-left: 43px;
  color: #334155;
  font-weight: 700;
}

.date-input-wrap .form-control:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14);
}

.date-input-wrap .form-control:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
}

.period-preview {
  margin-top: 16px;
  border: 1px solid rgba(41, 156, 219, 0.18);
  background: rgba(41, 156, 219, 0.08);
  color: #299cdb;
  border-radius: 15px;
  padding: 13px;
  display: flex;
  align-items: center;
  gap: 11px;
}

.period-preview > i {
  font-size: 22px;
}

.period-preview span {
  display: block;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.validity-help {
  display: block;
  margin-top: 7px;
  color: #64748b;
  font-size: 11.5px;
  font-weight: 600;
  line-height: 1.5;
}

.validity-preview {
  margin-top: 12px;
  border: 1px solid rgba(10, 179, 156, 0.2);
  background: rgba(10, 179, 156, 0.08);
  color: #0ab39c;
  border-radius: 15px;
  padding: 13px;
  display: flex;
  align-items: center;
  gap: 11px;
}

.validity-preview > i {
  font-size: 22px;
}

.validity-preview span {
  display: block;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.validity-preview strong {
  color: #334155;
  font-size: 13px;
  font-weight: 900;
}

.period-preview strong {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  color: #334155;
  font-size: 13px;
  font-weight: 900;
}

.accreditation-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.accreditation-card {
  min-height: 190px;
  border: 1px solid #e5e7eb;
  border-radius: 18px;
  background: #ffffff;
  padding: 16px;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.accreditation-card:hover:not(:disabled) {
  border-color: rgba(104, 124, 254, 0.55);
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(64, 81, 137, 0.1);
}

.accreditation-card.active {
  border-color: #687cfe;
  background: #eef2ff;
  box-shadow: 0 12px 28px rgba(104, 124, 254, 0.14);
}

.accreditation-card.disabled,
.accreditation-card.inactive {
  opacity: 0.65;
  cursor: not-allowed;
}

.accreditation-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.accreditation-logo {
  width: 46px;
  height: 46px;
  border-radius: 15px;
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.accreditation-check {
  color: #687cfe;
  font-size: 25px;
}

.accreditation-card h6 {
  color: #334155;
  font-weight: 900;
  margin-bottom: 4px;
}

.accreditation-card > p {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 13px;
}

.accreditation-meta {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.accreditation-meta span {
  color: #64748b;
  font-size: 11.5px;
  font-weight: 700;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.accreditation-meta i {
  color: #687cfe;
  font-size: 14px;
  margin-top: 1px;
}

.accreditation-empty {
  min-height: 190px;
}

.summary-accreditations {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-accreditation-badge {
  display: inline-flex !important;
  align-items: center;
  gap: 6px;
  margin-bottom: 0 !important;
  padding: 7px 11px;
  border-radius: 999px;
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c !important;
  font-size: 12px !important;
  font-weight: 850 !important;
  text-transform: none !important;
}

.certificate-visibility-box {
  margin-top: 14px;
  border: 1px solid rgba(13, 143, 233, 0.18);
  background: #f8fbff;
  border-radius: 15px;
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.certificate-visibility-box.enabled {
  border-color: rgba(10, 179, 156, 0.22);
  background: rgba(10, 179, 156, 0.06);
}

.certificate-visibility-box.locked { opacity: 0.88; }

.certificate-visibility-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eaf7ff;
  color: #0d8fe9;
  font-size: 20px;
}

.certificate-visibility-content { flex: 1; min-width: 0; }
.certificate-visibility-content span,
.certificate-visibility-content small { display: block; }
.certificate-visibility-content span {
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}
.certificate-visibility-content strong {
  display: block;
  color: #334155;
  font-size: 13px;
  margin: 2px 0;
}
.certificate-visibility-content small {
  color: #64748b;
  font-size: 11px;
  line-height: 1.45;
}
.certificate-switch { margin: 0; padding-left: 2.8em; }
.certificate-switch .form-check-input {
  width: 2.4em;
  height: 1.3em;
  cursor: pointer;
}

.learner-toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.learner-toolbar-actions {
  display: flex;
  gap: 10px;
}

.learner-toolbar-actions .btn {
  height: 44px;
  border-radius: 12px;
  white-space: nowrap;
}

.learner-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.learner-card {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 12px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.learner-card:hover {
  background: #f8fafc;
  border-color: rgba(104, 124, 254, 0.4);
}

.learner-card.active {
  border-color: #687cfe;
  background: #eef2ff;
}

.learner-card.locked {
  border-color: rgba(240, 101, 72, 0.32);
  background: rgba(240, 101, 72, 0.06);
  cursor: not-allowed;
}

.learner-card.locked:hover {
  border-color: rgba(240, 101, 72, 0.45);
  background: rgba(240, 101, 72, 0.08);
}

.learner-card.disabled {
  opacity: 0.7;
}

.learner-avatar,
.learner-avatar img,
.learner-avatar span {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 50%;
}

.learner-avatar img {
  object-fit: cover;
}

.learner-avatar span {
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
}

.learner-card h6 {
  color: #334155;
  font-weight: 800;
  margin-bottom: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.learner-card p {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.learner-check {
  color: #687cfe;
  font-size: 22px;
}

.learner-card.locked .learner-check {
  color: #f06548;
}

.summary-block {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 14px;
  background: #ffffff;
  margin-bottom: 14px;
}

.summary-block span {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 7px;
}

.summary-block strong {
  color: #334155;
  font-weight: 900;
}

.summary-course {
  display: flex;
  align-items: center;
  gap: 12px;
}

.summary-course-image {
  width: 58px;
  height: 58px;
  min-width: 58px;
  border-radius: 16px;
  overflow: hidden;
  background: #eef2ff;
}

.summary-course-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.summary-course-image div {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}

.summary-course h6 {
  color: #334155;
  font-weight: 900;
  margin-bottom: 3px;
}

.summary-course p {
  color: #64748b;
  margin-bottom: 0;
  font-size: 13px;
}

.summary-header-mini {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.summary-header-mini h6 {
  color: #334155;
  font-weight: 900;
  margin-bottom: 2px;
}

.summary-header-mini p {
  color: #64748b;
  margin-bottom: 0;
  font-size: 13px;
}

.selected-learners-box {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 350px;
  overflow-y: auto;
  padding-right: 4px;
}

.selected-learner-mini {
  border: 1px solid #edf2f7;
  border-radius: 14px;
  padding: 10px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 10px;
}

.selected-learner-avatar,
.selected-learner-avatar img,
.selected-learner-avatar span {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 50%;
}

.selected-learner-avatar img {
  object-fit: cover;
}

.selected-learner-avatar span {
  background: #eef2ff;
  color: #405189;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 11px;
}

.selected-learner-mini h6 {
  color: #334155;
  font-size: 13px;
  font-weight: 850;
  margin-bottom: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.selected-learner-mini p {
  color: #64748b;
  font-size: 11px;
  margin-bottom: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.summary-info {
  border: 1px solid rgba(41, 156, 219, 0.18);
  background: rgba(41, 156, 219, 0.08);
  color: #299cdb;
  border-radius: 16px;
  padding: 13px;
  display: flex;
  gap: 10px;
  font-size: 13px;
  font-weight: 700;
}

.summary-info i {
  font-size: 18px;
}

.modern-textarea {
  border-radius: 14px !important;
  border-color: #e5e7eb !important;
  color: #334155;
  background-color: #ffffff;
}

.mini-badge {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: #405189;
  font-size: 11px;
  font-weight: 800;
  text-transform: capitalize;
}

.mini-badge.success {
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
}

.mini-badge.primary {
  background: rgba(104, 124, 254, 0.12);
  color: #687cfe;
}

.mini-badge.danger {
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
}

.mini-badge.warning {
  background: rgba(247, 184, 75, 0.16);
  color: #d99a20;
}

.empty-box {
  min-height: 260px;
  border: 1px dashed #cbd5e1;
  border-radius: 18px;
  background: #f8fafc;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-box i {
  font-size: 42px;
  color: #94a3b8;
  margin-bottom: 10px;
}

.empty-box h6 {
  color: #334155;
  font-weight: 800;
}

.empty-box p {
  margin-bottom: 0;
  font-size: 13px;
}

.empty-small {
  min-height: 120px;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
  background: #f8fafc;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
}

.empty-small i {
  font-size: 28px;
  color: #94a3b8;
}

.wizard-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  border-top: 1px solid #f1f5f9;
  margin-top: 24px;
  padding-top: 18px;
}

.footer-btn {
  min-width: 140px;
  height: 42px;
  border-radius: 12px !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.disabled {
  opacity: 0.65;
  cursor: not-allowed !important;
}

.course-select-card.disabled,
.category-item.disabled {
  pointer-events: none;
}

.min-width-0 {
  min-width: 0;
}

@media (max-width: 1399.98px) {
  .request-steps {
    grid-template-columns: repeat(2, 1fr);
  }

  .learner-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 991.98px) {
  .request-wizard-header {
    padding: 20px;
  }

  .request-steps {
    grid-template-columns: 1fr;
  }

  .wizard-section {
    padding: 18px;
  }

  .learner-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .course-language-filter-header {
    align-items: flex-start;
  }

  .course-language-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .course-language-option {
    width: 100%;
  }

  .accreditation-grid {
    grid-template-columns: 1fr;
  }

  .request-wizard-card {
    border-radius: 14px;
  }

  .request-wizard-header {
    padding: 18px;
  }

  .request-title-box {
    align-items: flex-start;
  }

  .request-main-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    font-size: 21px;
  }

  .request-title-box h4 {
    font-size: 19px;
  }

  .request-title-box p {
    font-size: 13px;
  }

  .back-btn {
    width: 100%;
  }

  .wizard-section {
    padding: 16px;
    border-radius: 14px;
  }

  .section-heading {
    flex-direction: column;
  }

  .panel-top {
    flex-direction: column;
  }

  .course-select-card {
    flex-direction: column;
    align-items: stretch;
  }

  .course-list-image {
    width: 100%;
    height: 170px;
  }

  .course-select-check {
    align-self: flex-end;
  }

  .learner-toolbar {
    flex-direction: column;
  }

  .learner-toolbar-actions {
    flex-direction: column;
  }

  .learner-toolbar-actions .btn {
    width: 100%;
  }

  .wizard-footer {
    flex-direction: column-reverse;
  }

  .footer-btn {
    width: 100%;
  }

  .wizard-footer > .d-flex {
    width: 100%;
  }

  .wizard-footer > .d-flex .footer-btn {
    width: 100%;
  }
}

@media (max-width: 420px) {
  .course-language-filter-header {
    flex-direction: column;
  }

  .course-language-options {
    grid-template-columns: 1fr;
  }

  .request-title-box {
    flex-direction: column;
  }

  .request-step {
    flex-direction: column;
    align-items: flex-start;
  }

  .summary-course {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>