<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
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
})

const currentStep = ref(1)
const selectedCategoryId = ref(null)
const courseSearch = ref('')
const learnerSearch = ref('')
const selectedCourseLanguages = ref([])

const form = useForm({
  course_id: null,
  accreditation_ids: [],
  start_date: '',
  end_date: '',
  duree_validite_formation: null,
  learners: [],
  notes: '',
})

const steps = [
  {
    id: 1,
    title: 'Select Course',
    description: 'Category and course',
    icon: 'ri-book-open-line',
  },
  {
    id: 2,
    title: 'Training Details',
    description: 'Dates and approvals',
    icon: 'ri-calendar-check-line',
  },
  {
    id: 3,
    title: 'Add Learners',
    description: 'Choose learners',
    icon: 'ri-team-line',
  },
  {
    id: 4,
    title: 'Summary',
    description: 'Review request',
    icon: 'ri-file-check-line',
  },
]
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
    english: 'English',
    french: 'French',
    arabic: 'Arabic',
    unspecified: 'Not specified',
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
  /*
  |--------------------------------------------------------------------------
  | Always-visible common languages
  |--------------------------------------------------------------------------
  |
  | English, French and Arabic are always displayed, even when the current
  | category has no course using one of these languages.
  |
  */
  const options = new Map([
    ['english', { value: 'english', label: 'English' }],
    ['french', { value: 'french', label: 'French' }],
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
      label: 'Not specified',
    })
  }

  return [...options.values()]
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

  if (!search) {
    return props.learners
  }

  return props.learners.filter((learner) => {
    return (
      String(learner.name ?? '').toLowerCase().includes(search) ||
      String(learner.email ?? '').toLowerCase().includes(search)
    )
  })
})

const selectedCourse = computed(() => {
  return categoryCourses.value.find((course) => {
    return Number(course.id) === Number(form.course_id)
  })
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
const isLastStep = computed(() => currentStep.value === steps.length)

const getCourseImage = (course) => {
  if (course?.image_url) {
    return course.image_url
  }

  if (course?.image) {
    return `/storage/${course.image}`
  }

  return null
}

const getCourseInitials = (title) => {
  if (!title) return 'CO'

  return String(title)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getLearnerInitials = (name) => {
  if (!name) return 'LR'

  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getTypeClass = (type) => {
  if (type === 'free') {
    return 'mini-badge success'
  }

  if (type === 'paid') {
    return 'mini-badge primary'
  }

  if (type === 'private') {
    return 'mini-badge danger'
  }

  return 'mini-badge'
}

const formatPrice = (course) => {
  const price = Number(course?.price || 0)

  if (course?.type !== 'paid' || price === 0) {
    return 'Free'
  }

  return `${price.toFixed(2)} ${course.currency || ''}`
}

const selectCategory = (category) => {
  selectedCategoryId.value = category.id
  form.course_id = null
  courseSearch.value = ''
  selectedCourseLanguages.value = []
}

const selectCourse = (course) => {
  form.course_id = course.id
}

const accreditationIsSelected = (accreditationId) => {
  return form.accreditation_ids.some((selectedId) => {
    return Number(selectedId) === Number(accreditationId)
  })
}

const toggleAccreditation = (accreditationId) => {
  if (accreditationIsSelected(accreditationId)) {
    form.accreditation_ids = form.accreditation_ids.filter((selectedId) => {
      return Number(selectedId) !== Number(accreditationId)
    })
    return
  }

  if (form.accreditation_ids.length >= 2) {
    showWarning('Maximum reached', 'You can select a maximum of two accreditations.')
    return
  }

  form.accreditation_ids = [
    ...form.accreditation_ids,
    Number(accreditationId),
  ]
}

const formatTrainingDate = (date) => {
  if (!date) return '-'

  return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const toggleLearner = (learnerId) => {
  if (form.learners.includes(learnerId)) {
    form.learners = form.learners.filter((id) => id !== learnerId)
    return
  }

  form.learners.push(learnerId)
}

const selectAllFilteredLearners = () => {
  const ids = filteredLearners.value.map((learner) => learner.id)
  form.learners = [...new Set([...form.learners, ...ids])]
}

const clearLearners = () => {
  form.learners = []
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
    if (!selectedCategoryId.value) {
      showWarning('Category required', 'Please select a category first.')
      return false
    }

    if (!form.course_id) {
      showWarning('Course required', 'Please select one course.')
      return false
    }
  }

  if (step === 2) {
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

const goToStep = (stepId) => {
  if (stepId > currentStep.value) {
    if (!validateStep(currentStep.value)) return
  }

  currentStep.value = stepId
}

const nextStep = () => {
  if (!validateStep(currentStep.value)) return

  if (currentStep.value < steps.length) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const submit = () => {
  if (!validateStep(1)) return
  if (!validateStep(2)) return
  if (!validateStep(3)) return

  form.post(route('company-requests.store'), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Request sent',
        text: 'Your request has been sent successfully.',
        timer: 2000,
        showConfirmButton: false,
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Please check the form and try again.',
        timer: 3000,
        showConfirmButton: false,
      })
    },
  })
}

watch(selectedCategoryId, () => {
  form.course_id = null
  selectedCourseLanguages.value = []
})

watch(
  selectedCourseLanguages,
  () => {
    if (!form.course_id) {
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
</script>

<template>
  <Layout>
    <Head title="Create Company Request" />
    <PageHeader title="Create Company Request" pageTitle="Company Requests" />

    <div class="request-wizard-page">
      <BCard class="request-wizard-card border-0 shadow-sm">
        <BCardHeader class="request-wizard-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="request-title-box">
              <div class="request-main-icon">
                <i class="ri-send-plane-line"></i>
              </div>

              <div>
                <h4 class="mb-1 fw-bold">New Course Request</h4>
                <p class="text-muted mb-0">
                  Create a request in four steps: course, training details, learners, and summary.
                </p>
              </div>
            </div>

            <Link :href="route('company-requests.index')" class="btn btn-light back-btn">
              <i class="ri-arrow-left-line me-1"></i>
              Back to Requests
            </Link>
          </div>
        </BCardHeader>

        <BCardBody>
          <!-- STEPPER -->
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
                <h5>Select Course</h5>
                <p>Select a category first, then choose one published course.</p>
              </div>

              <span class="section-counter">
                {{ props.categories.length }} categories
              </span>
            </div>

            <BRow class="g-4">
              <BCol xl="4" lg="5">
                <div class="side-panel">
                  <div class="side-panel-title">
                    <i class="ri-folder-3-line"></i>
                    <span>Categories</span>
                  </div>

                  <div v-if="props.categories.length" class="category-list">
                    <button
                      v-for="category in props.categories"
                      :key="category.id"
                      type="button"
                      class="category-item"
                      :class="{ active: Number(selectedCategoryId) === Number(category.id) }"
                      @click="selectCategory(category)"
                    >
                      <div class="category-icon">
                        <i class="ri-folder-line"></i>
                      </div>

                      <div class="min-width-0 flex-grow-1">
                        <h6>{{ category.name }}</h6>
                        <p>
                          {{ category.courses_count }}
                          course{{ Number(category.courses_count || 0) > 1 ? 's' : '' }}
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
                    <span>No categories found</span>
                  </div>
                </div>
              </BCol>

              <BCol xl="8" lg="7">
                <div class="main-panel">
                  <div class="panel-top">
                    <div>
                      <h6 class="mb-1">
                        {{ selectedCategory?.name || 'Courses' }}
                      </h6>
                      <p class="text-muted mb-0">
                        {{
                          selectedCategory
                            ? 'Choose one course for this request.'
                            : 'Please select a category to show courses.'
                        }}
                      </p>
                    </div>

                    <span class="section-counter">
                      {{ filteredCourses.length }} courses
                    </span>
                  </div>

                  <div v-if="selectedCategory" class="search-box request-search-box mb-3">
                    <input
                      v-model="courseSearch"
                      type="text"
                      class="form-control"
                      placeholder="Search course..."
                    />
                    <i class="ri-search-line search-icon"></i>
                  </div>

                  <div
                    v-if="selectedCategory"
                    class="course-language-filter mb-3"
                  >
                    <div class="course-language-filter-header">
                      <div class="course-language-filter-title">
                        <i class="ri-translate-2"></i>

                        <div>
                          <strong>Filter by language</strong>
                          <small>Select one or more languages</small>
                        </div>
                      </div>

                      <button
                        v-if="selectedCourseLanguages.length"
                        type="button"
                        class="course-language-clear"
                        @click="selectedCourseLanguages = []"
                      >
                        <i class="ri-close-circle-line"></i>
                        Clear
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

                  <div v-if="!selectedCategory" class="empty-box">
                    <i class="ri-book-open-line"></i>
                    <h6>Select category</h6>
                    <p>Please select a category to display courses.</p>
                  </div>

                  <div v-else-if="filteredCourses.length" class="course-select-list">
                    <div
                      v-for="course in filteredCourses"
                      :key="course.id"
                      class="course-select-card"
                      :class="{ active: Number(form.course_id) === Number(course.id) }"
                      @click="selectCourse(course)"
                    >
                      <div class="course-list-image">
                        <img
                          v-if="getCourseImage(course)"
                          :src="getCourseImage(course)"
                          alt="Course image"
                        />

                        <div v-else class="course-list-fallback">
                          {{ getCourseInitials(course.title) }}
                        </div>
                      </div>

                      <div class="course-select-content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                          <h6 class="mb-0">{{ course.title }}</h6>

                          <span :class="getTypeClass(course.type)">
                            {{ course.type || 'course' }}
                          </span>
                        </div>

                        <p>
                          {{ course.description || 'No description available.' }}
                        </p>

                        <div class="course-meta-row">
                          <span>
                            <i class="ri-computer-line me-1"></i>
                            {{ course.training_type || '-' }}
                          </span>

                          <span>
                            <i class="ri-bar-chart-line me-1"></i>
                            {{ course.skill_level || '-' }}
                          </span>

                          <span>
                            <i class="ri-translate-2 me-1"></i>
                            {{ getLanguageLabel(course.language) }}
                          </span>

                          <span>
                            <i class="ri-list-check-2 me-1"></i>
                            {{ course.chapters_count || 0 }} chapters
                          </span>

                          <span>
                            <i class="ri-slideshow-line me-1"></i>
                            {{ course.slides_count || 0 }} slides
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
                    <h6>No courses found</h6>
                    <p>No courses match your search.</p>
                  </div>
                </div>
              </BCol>
            </BRow>
          </div>

          <!-- STEP 2 -->
          <div v-if="currentStep === 2" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>Training Details</h5>
                <p>Select the requested accreditation(s) and training period.</p>
              </div>

              <span class="section-counter">
                {{ form.accreditation_ids.length }} accreditation(s)
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
                      <h6>Training Period</h6>
                      <p>Define the planned start and end dates.</p>
                    </div>
                  </div>

                  <div class="training-date-grid">
                    <div class="form-field-box">
                      <label for="start_date">Start date</label>
                      <div class="date-input-wrap">
                        <i class="ri-calendar-2-line"></i>
                        <input
                          id="start_date"
                          v-model="form.start_date"
                          type="date"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.start_date }"
                          :max="form.end_date || undefined"
                        />
                      </div>
                      <div v-if="form.errors.start_date" class="invalid-feedback d-block">
                        {{ form.errors.start_date }}
                      </div>
                    </div>

                    <div class="form-field-box">
                      <label for="end_date">End date</label>
                      <div class="date-input-wrap">
                        <i class="ri-calendar-check-line"></i>
                        <input
                          id="end_date"
                          v-model="form.end_date"
                          type="date"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.end_date }"
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
                      Validity duration after completion
                    </label>

                    <div class="date-input-wrap">
                      <i class="ri-timer-line"></i>
                      <input
                        id="duree_validite_formation"
                        v-model.number="form.duree_validite_formation"
                        type="number"
                        min="1"
                        max="1200"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.duree_validite_formation }"
                        placeholder="Example: 12"
                      />
                    </div>

                    <small class="validity-help">
                      Leave empty when the course or certificate has no expiration date.
                    </small>

                    <div
                      v-if="form.errors.duree_validite_formation"
                      class="invalid-feedback d-block"
                    >
                      {{ form.errors.duree_validite_formation }}
                    </div>
                  </div>

                  <div class="period-preview">
                    <i class="ri-time-line"></i>
                    <div>
                      <span>Selected period</span>
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
                      <span>Course / certificate validity</span>
                      <strong>
                        {{
                          form.duree_validite_formation
                            ? `${form.duree_validite_formation} month${Number(form.duree_validite_formation) > 1 ? 's' : ''}`
                            : 'No expiration'
                        }}
                      </strong>
                    </div>
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
                      <h6>Requested Accreditations</h6>
                      <p>Select EASA, DAC Morocco, or both.</p>
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
                        inactive: !accreditation.is_active,
                      }"
                      :disabled="!accreditation.is_active"
                      @click="toggleAccreditation(accreditation.id)"
                    >
                      <div class="accreditation-card-top">
                        <div class="accreditation-logo">
                          <i class="ri-government-line"></i>
                        </div>

                        <i
                          class="accreditation-check"
                          :class="
                            accreditationIsSelected(accreditation.id)
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
                    <span>No active accreditations available</span>
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
                <h5>Add Learners</h5>
                <p>Select the learners who will receive the course after approval.</p>
              </div>

              <span class="section-counter">
                {{ form.learners.length }} selected
              </span>
            </div>

            <div class="learner-toolbar">
              <div class="search-box request-search-box">
                <input
                  v-model="learnerSearch"
                  type="text"
                  class="form-control"
                  placeholder="Search learner..."
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <div class="learner-toolbar-actions">
                <button
                  type="button"
                  class="btn btn-light"
                  @click="selectAllFilteredLearners"
                >
                  <i class="ri-check-double-line me-1"></i>
                  Select filtered
                </button>

                <button
                  type="button"
                  class="btn btn-light"
                  @click="clearLearners"
                >
                  <i class="ri-close-circle-line me-1"></i>
                  Clear
                </button>
              </div>
            </div>

            <div v-if="filteredLearners.length" class="learner-grid">
              <div
                v-for="learner in filteredLearners"
                :key="learner.id"
                class="learner-card"
                :class="{ active: form.learners.includes(learner.id) }"
                @click="toggleLearner(learner.id)"
              >
                <div class="learner-avatar">
                  <img
                    v-if="learner.photo_url"
                    :src="learner.photo_url"
                    alt="Learner"
                  />

                  <span v-else>
                    {{ getLearnerInitials(learner.name) }}
                  </span>
                </div>

                <div class="min-width-0 flex-grow-1">
                  <h6>{{ learner.name }}</h6>
                  <p>{{ learner.email }}</p>
                </div>

                <i
                  class="learner-check"
                  :class="
                    form.learners.includes(learner.id)
                      ? 'ri-checkbox-circle-fill'
                      : 'ri-checkbox-blank-circle-line'
                  "
                ></i>
              </div>
            </div>

            <div v-else class="empty-box">
              <i class="ri-user-search-line"></i>
              <h6>No learners found</h6>
              <p>No learners match your search.</p>
            </div>
          </div>

          <!-- STEP 4 -->
          <div v-if="currentStep === 4" class="wizard-section">
            <div class="section-heading">
              <div>
                <h5>Summary</h5>
                <p>Review your request before sending it to super-admin.</p>
              </div>

              <span class="section-counter">
                Pending approval
              </span>
            </div>

            <BRow class="g-4">
              <BCol lg="7">
                <div class="summary-panel">
                  <div class="summary-block">
                    <span>Selected Category</span>
                    <strong>{{ selectedCategory?.name || 'Not selected' }}</strong>
                  </div>

                  <div class="summary-block">
                    <span>Selected Course</span>

                    <div v-if="selectedCourse" class="summary-course">
                      <div class="summary-course-image">
                        <img
                          v-if="getCourseImage(selectedCourse)"
                          :src="getCourseImage(selectedCourse)"
                          alt="Course"
                        />

                        <div v-else>
                          {{ getCourseInitials(selectedCourse.title) }}
                        </div>
                      </div>

                      <div class="min-width-0">
                        <h6>{{ selectedCourse.title }}</h6>
                        <p>
                          {{ selectedCourse.training_type || '-' }}
                          ·
                          {{ selectedCourse.skill_level || '-' }}
                          ·
                          {{ formatPrice(selectedCourse) }}
                        </p>
                      </div>
                    </div>

                    <strong v-else>Not selected</strong>
                  </div>

                  <div class="summary-block">
                    <span>Training Period</span>
                    <strong>
                      {{ formatTrainingDate(form.start_date) }}
                      —
                      {{ formatTrainingDate(form.end_date) }}
                    </strong>
                  </div>

                  <div class="summary-block">
                    <span>Validity Duration</span>
                    <strong>
                      {{
                        form.duree_validite_formation
                          ? `${form.duree_validite_formation} month${Number(form.duree_validite_formation) > 1 ? 's' : ''}`
                          : 'No expiration'
                      }}
                    </strong>
                  </div>

                  <div class="summary-block">
                    <span>Requested Accreditations</span>

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

                    <strong v-else>Not selected</strong>
                  </div>

                  <div class="summary-block">
                    <span>Notes</span>

                    <textarea
                      v-model="form.notes"
                      class="form-control modern-textarea"
                      rows="5"
                      placeholder="Add optional notes for super-admin..."
                    ></textarea>
                  </div>
                </div>
              </BCol>

              <BCol lg="5">
                <div class="summary-panel">
                  <div class="summary-header-mini">
                    <div>
                      <h6>Selected Learners</h6>
                      <p>{{ selectedLearners.length }} learner(s)</p>
                    </div>

                    <button
                      type="button"
                      class="btn btn-sm btn-light"
                      @click="currentStep = 3"
                    >
                      Edit
                    </button>
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
                          alt="Learner"
                        />

                        <span v-else>
                          {{ getLearnerInitials(learner.name) }}
                        </span>
                      </div>

                      <div class="min-width-0">
                        <h6>{{ learner.name }}</h6>
                        <p>{{ learner.email }}</p>
                      </div>
                    </div>
                  </div>

                  <div v-else class="empty-small">
                    <i class="ri-team-line"></i>
                    <span>No learners selected</span>
                  </div>

                  <div class="summary-info mt-3">
                    <i class="ri-information-line"></i>
                    <span>
                      After approval, enrollments will be created automatically.
                    </span>
                  </div>
                </div>
              </BCol>
            </BRow>
          </div>

          <!-- FOOTER -->
          <div class="wizard-footer">
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
                variant="primary"
                class="footer-btn"
                @click="nextStep"
              >
                Next
                <i class="ri-arrow-right-line ms-1"></i>
              </BButton>

              <BButton
                v-if="isLastStep"
                type="button"
                variant="success"
                class="footer-btn"
                :disabled="form.processing"
                @click="submit"
              >
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>

                <i v-else class="ri-send-plane-line me-1"></i>
                Send Request
              </BButton>
            </div>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>

<style scoped>
.request-wizard-page {
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

/* ================= STEPS ================= */
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

/* ================= SECTION ================= */
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

/* ================= PANELS ================= */
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

/* ================= CATEGORY ================= */
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

/* ================= SEARCH ================= */
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

/* ================= COURSES ================= */
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

.accreditation-card:hover {
  border-color: rgba(104, 124, 254, 0.55);
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(64, 81, 137, 0.1);
}

.accreditation-card.active {
  border-color: #687cfe;
  background: #eef2ff;
  box-shadow: 0 12px 28px rgba(104, 124, 254, 0.14);
}

.accreditation-card.inactive {
  opacity: 0.55;
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

/* ================= LEARNERS ================= */
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

/* ================= SUMMARY ================= */
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

/* ================= BADGES ================= */
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

/* ================= EMPTY ================= */
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

/* ================= FOOTER ================= */
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

.min-width-0 {
  min-width: 0;
}

/* ================= RESPONSIVE ================= */
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
  .course-language-options {
    grid-template-columns: 1fr;
  }

  .course-language-filter-header {
    flex-direction: column;
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