<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  enrollments: {
    type: Array,
    default: () => [],
  },
})

const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedLanguages = ref([])

const normalizeLanguage = value => {
  const language = String(value || '').trim().toLowerCase()

  if (!language) return 'not_specified'
  if (['en', 'en-us', 'en-gb', 'english', 'anglais'].includes(language)) return 'english'
  if (['fr', 'fr-fr', 'french', 'français', 'francais'].includes(language)) return 'french'
  if (['ar', 'ar-ma', 'arabic', 'arabe'].includes(language)) return 'arabic'

  return language
}

const getLanguageLabel = value => {
  const language = normalizeLanguage(value)

  if (language === 'english') return 'English'
  if (language === 'french') return 'French'
  if (language === 'arabic') return 'Arabic'
  if (language === 'not_specified') return 'Not specified'

  return String(value || language)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, letter => letter.toUpperCase())
}

const languageOptions = computed(() => {
  const options = new Map([
    ['english', 'English'],
    ['french', 'French'],
  ])

  props.enrollments.forEach(item => {
    const value = normalizeLanguage(item.course?.language)
    options.set(value, getLanguageLabel(item.course?.language))
  })

  return [...options.entries()].map(([value, label]) => ({ value, label }))
})

const currentPage = ref(1)
const perPage = ref(9)

const filteredCourses = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()
  const status = selectedStatus.value

  return props.enrollments.filter(item => {
    const matchStatus = status === 'all' || item.status === status
    const courseLanguage = normalizeLanguage(item.course?.language)
    const matchLanguage = !selectedLanguages.value.length ||
      selectedLanguages.value.includes(courseLanguage)

    const matchSearch =
      !search ||
      String(item.course?.ref || '').toLowerCase().includes(search) ||
      String(item.course?.title || '').toLowerCase().includes(search) ||
      String(item.course?.description || '').toLowerCase().includes(search) ||
      String(item.course?.category?.name || '').toLowerCase().includes(search) ||
      String(item.course?.language || '').toLowerCase().includes(search) ||
      String(item.course?.training_type || '').toLowerCase().includes(search) ||
      String(item.course?.method || '').toLowerCase().includes(search) ||
      String(item.course?.skill_level || '').toLowerCase().includes(search) ||
      String(item.training_request?.reference || '').toLowerCase().includes(search) ||
      String(item.training_request?.accreditations
        ?.map(accreditation => accreditation.name)
        .join(' ') || '')
        .toLowerCase()
        .includes(search) ||
      String(item.status || '').toLowerCase().includes(search)

    return matchStatus && matchLanguage && matchSearch
  })
})

const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value

  return filteredCourses.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(filteredCourses.value.length / perPage.value)
  )
})

const displayedFrom = computed(() => {
  if (!filteredCourses.value.length) {
    return 0
  }

  return (currentPage.value - 1) * perPage.value + 1
})

const displayedTo = computed(() => {
  return Math.min(
    currentPage.value * perPage.value,
    filteredCourses.value.length
  )
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 5) {
    return Array.from(
      { length: total },
      (_, index) => index + 1
    )
  }

  const pages = [1]

  let start = Math.max(2, current - 1)
  let end = Math.min(total - 1, current + 1)

  if (current <= 3) {
    start = 2
    end = 4
  }

  if (current >= total - 2) {
    start = total - 3
    end = total - 1
  }

  if (start > 2) {
    pages.push('...')
  }

  for (let page = start; page <= end; page++) {
    pages.push(page)
  }

  if (end < total - 1) {
    pages.push('...')
  }

  pages.push(total)

  return pages
})

const goToPage = page => {
  if (page === '...') {
    return
  }

  currentPage.value = page
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

watch(
  [searchQuery, selectedStatus, selectedLanguages],
  () => {
    currentPage.value = 1
  }
)

watch(totalPages, value => {
  if (currentPage.value > value) {
    currentPage.value = value
  }
})

const stats = computed(() => {
  return {
    total: props.enrollments.length,
    not_started: props.enrollments.filter(item => item.status === 'not_started').length,
    in_progress: props.enrollments.filter(item => item.status === 'in_progress').length,
    completed: props.enrollments.filter(item => item.status === 'completed').length,
    expired: props.enrollments.filter(item => item.status === 'expired').length,
  }
})

const getCourseImage = course => {
  if (!course) return null

  if (course.image_url) return course.image_url

  if (course.image) {
    return `/storage/${course.image}`
  }

  return null
}

const getCourseInitials = course => {
  return String(course?.title || 'CO')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word.charAt(0).toUpperCase())
    .join('')
}

const getStatusClass = status => {
  switch (status) {
    case 'completed':
      return 'course-status completed'
    case 'in_progress':
      return 'course-status in-progress'
    case 'not_started':
      return 'course-status not-started'
    case 'expired':
      return 'course-status expired'
    default:
      return 'course-status default'
  }
}

const getStatusLabel = status => {
  switch (status) {
    case 'completed':
      return 'Completed'
    case 'in_progress':
      return 'In progress'
    case 'not_started':
      return 'Not started'
    case 'expired':
      return 'Expired'
    default:
      return status || '-'
  }
}

const hasNoExamAttempts = enrollment => {
  return Number(enrollment?.attempts_rest ?? 0) <= 0
}

const hasPassedExam = enrollment => {
  return Boolean(enrollment?.has_passed_exam)
}

const isScormCourse = enrollment => {
  return enrollment?.course?.content_type === 'scorm' ||
    enrollment?.content_type === 'scorm'
}

const isEnrollmentExpired = enrollment => {
  return Boolean(
    enrollment?.is_expired ||
    enrollment?.expiration?.is_expired ||
    enrollment?.training_request?.expiration?.is_expired
  )
}

const getTrainingAccessState = enrollment => {
  if (isEnrollmentExpired(enrollment)) return 'expired'

  return enrollment?.training_request?.access_state || 'unrestricted'
}

const isTrainingUnavailable = enrollment => {
  const trainingRequest = enrollment?.training_request

  return Boolean(
    trainingRequest &&
    trainingRequest.can_access_course === false
  )
}

const formatTrainingDate = value => {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const getTrainingPeriodLabel = enrollment => {
  const request = enrollment?.training_request

  if (!request) return null

  return `${formatTrainingDate(request.start_date)} - ${formatTrainingDate(request.end_date)}`
}

const getTrainingStateLabel = enrollment => {
  const state = getTrainingAccessState(enrollment)

  if (state === 'expired') return 'Training validity expired'
  if (state === 'upcoming') return 'Upcoming training'
  if (state === 'ended') return 'Training period ended'
  if (state === 'active') return 'Training active'

  return null
}

const getTrainingStateIcon = enrollment => {
  const state = getTrainingAccessState(enrollment)

  if (state === 'expired') return 'ri-calendar-close-line'
  if (state === 'upcoming') return 'ri-calendar-schedule-line'
  if (state === 'ended') return 'ri-calendar-close-line'

  return 'ri-calendar-check-line'
}

const isCourseAccessLocked = enrollment => {
  return (
    isEnrollmentExpired(enrollment) ||
    Boolean(enrollment?.course_access_locked) ||
    isTrainingUnavailable(enrollment) ||
    hasNoExamAttempts(enrollment) ||
    hasPassedExam(enrollment)
  )
}

const getCourseDestination = enrollment => {
  if (!enrollment?.hashid) {
    return '#'
  }

  if (isCourseAccessLocked(enrollment)) {
    return route('learner-space.course-finish', enrollment.hashid)
  }

  return route('learner-space.course-viewer', enrollment.hashid)
}

const getButtonLabel = enrollment => {
  if (isEnrollmentExpired(enrollment)) {
    return 'View expired training'
  }

  if (hasPassedExam(enrollment)) {
    return 'View exam result'
  }

  if (getTrainingAccessState(enrollment) === 'upcoming') {
    return 'View training details'
  }

  if (getTrainingAccessState(enrollment) === 'ended') {
    return 'View course summary'
  }

  if (hasNoExamAttempts(enrollment)) {
    return 'View course summary'
  }

  if (isScormCourse(enrollment)) {
    return 'Open course'
  }

  if (enrollment?.status === 'completed') {
    return 'Review course'
  }

  if (enrollment?.status === 'not_started') {
    return 'Start course'
  }

  return 'Continue course'
}

const getButtonIcon = enrollment => {
  if (isEnrollmentExpired(enrollment)) {
    return 'ri-calendar-close-line'
  }

  if (hasPassedExam(enrollment)) {
    return 'ri-award-line'
  }

  if (isTrainingUnavailable(enrollment)) {
    return getTrainingStateIcon(enrollment)
  }

  if (hasNoExamAttempts(enrollment)) {
    return 'ri-file-list-3-line'
  }

  if (isScormCourse(enrollment)) {
    return 'ri-file-zip-line'
  }

  if (enrollment?.status === 'completed') {
    return 'ri-eye-line'
  }

  return 'ri-play-circle-line'
}

const getAttemptsLabel = enrollment => {
  if (hasPassedExam(enrollment)) {
    return 'Final exam passed'
  }

  if (hasNoExamAttempts(enrollment)) {
    return 'No attempts remaining'
  }

  const attempts = Number(enrollment?.attempts_rest ?? 0)

  return `Attempts left: ${attempts}`
}

const formatPercent = value => {
  const percent = Number(value || 0)

  if (Number.isNaN(percent)) {
    return 0
  }

  return Math.min(Math.max(percent, 0), 100).toFixed(0)
}


const clampProgressPercent = value => {
  const percent = Number(value)

  if (!Number.isFinite(percent)) return 0

  return Math.min(100, Math.max(0, percent))
}

const progressMeasureToPercent = value => {
  const measure = Number(value)

  if (!Number.isFinite(measure)) return null

  return clampProgressPercent(
    measure <= 1 ? measure * 100 : measure
  )
}

const hasCompletedScormStatus = enrollment => {
  const tracking = enrollment?.scorm_tracking || {}

  return [
    tracking.lesson_status,
    tracking.completion_status,
    tracking.success_status,
  ]
    .map(value => String(value || '').trim().toLowerCase())
    .some(value => ['completed', 'passed'].includes(value))
}

const getCourseProgress = enrollment => {
  if (!enrollment) return 0

  if (hasPassedExam(enrollment)) return 100

  if (isScormCourse(enrollment)) {
    if (
      Boolean(enrollment.course_completed) ||
      Boolean(enrollment.scorm_tracking?.is_completed) ||
      hasCompletedScormStatus(enrollment)
    ) {
      return 100
    }

    const percentCandidates = [
      enrollment.scorm_progress_percent,
      enrollment.scorm_tracking?.progress_percent,
    ]
      .map(clampProgressPercent)

    const measureCandidates = [
      enrollment.scorm_tracking?.progress_measure,
      enrollment.scorm_tracking?.cmi_data?.['cmi.progress_measure'],
      enrollment.scorm_tracking?.cmi_data?.['cmi.core.progress_measure'],
    ]
      .map(progressMeasureToPercent)
      .filter(value => value !== null)

    const fallbackProgress = clampProgressPercent(
      enrollment.progress_percent
    )

    return Math.round(Math.max(
      fallbackProgress,
      ...percentCandidates,
      ...measureCandidates,
    ))
  }

  if (
    Boolean(enrollment.course_completed) ||
    enrollment.status === 'completed'
  ) {
    return 100
  }

  return Math.round(
    clampProgressPercent(enrollment.progress_percent)
  )
}

const getCourseProgressLabel = enrollment => {
  return isScormCourse(enrollment)
    ? 'SCORM progress'
    : 'Course progress'
}

const formatDuration = value => {
  if (value === null || value === undefined || value === '') return null

  const hours = Number(value)

  if (Number.isNaN(hours)) return null

  const formatted = Number.isInteger(hours)
    ? hours.toFixed(0)
    : hours.toFixed(2).replace(/\.?0+$/, '')

  return `${formatted} hour${hours === 1 ? '' : 's'}`
}

const formatPassingScore = value => {
  if (value === null || value === undefined || value === '') return null

  const score = Number(value)

  if (Number.isNaN(score)) return null

  return `${Math.min(Math.max(score, 0), 100).toFixed(0)}%`
}

const getSyllabusUrl = course => {
  if (!course) return null
  if (course.syllabus_url) return course.syllabus_url
  if (course.syllabus) return `/storage/${course.syllabus}`

  return null
}
</script>

<template>
  <Head title="My Courses" />

  <section class="learner-courses-page">
    <!-- Hero -->
    <div class="learner-hero">
      <div class="learner-hero-content">
        <div class="hero-badge">
          <i class="ri-graduation-cap-line"></i>
          Learner Space
        </div>

        <h1>My Courses</h1>

        <p>
          Access your assigned aviation training courses, follow your progress,
          and continue learning anytime, anywhere.
        </p>
      </div>

      <div class="hero-stats">
        <div class="hero-stat-card">
          <div class="hero-stat-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <div>
            <span>Total courses</span>
            <strong>{{ stats.total }}</strong>
          </div>
        </div>

        <div class="hero-stat-card">
          <div class="hero-stat-icon warning">
            <i class="ri-time-line"></i>
          </div>

          <div>
            <span>Not started</span>
            <strong>{{ stats.not_started }}</strong>
          </div>
        </div>

        <div class="hero-stat-card">
          <div class="hero-stat-icon info">
            <i class="ri-loader-2-line"></i>
          </div>

          <div>
            <span>In progress</span>
            <strong>{{ stats.in_progress }}</strong>
          </div>
        </div>

        <div class="hero-stat-card">
          <div class="hero-stat-icon success">
            <i class="ri-checkbox-circle-line"></i>
          </div>

          <div>
            <span>Completed</span>
            <strong>{{ stats.completed }}</strong>
          </div>
        </div>

        <div class="hero-stat-card">
          <div class="hero-stat-icon danger">
            <i class="ri-calendar-close-line"></i>
          </div>

          <div>
            <span>Expired</span>
            <strong>{{ stats.expired }}</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="courses-container">
      <div class="courses-header-card">
        <div class="courses-header-title">
          <div class="courses-header-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <div>
            <h2>Assigned courses</h2>

            <p>
              Showing {{ displayedFrom }} - {{ displayedTo }}
              of {{ filteredCourses.length }} course(s)
            </p>
          </div>
        </div>

        <div class="courses-controls">
          <div class="courses-filter-box">
            <i class="ri-filter-3-line"></i>

            <select v-model="selectedStatus">
              <option value="all">All status</option>
              <option value="not_started">Not started</option>
              <option value="in_progress">In progress</option>
              <option value="completed">Completed</option>
              <option value="expired">Expired</option>
            </select>
          </div>

          <div class="courses-search-box">
            <i class="ri-search-line"></i>

            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search course..."
            />
          </div>
        </div>

        <div class="course-language-filter-panel">
          <div class="language-filter-title">
            <i class="ri-translate-2"></i>
            <div>
              <strong>Filter by language</strong>
              <span>Select one or several languages.</span>
            </div>

            <button
              v-if="selectedLanguages.length"
              type="button"
              @click="selectedLanguages = []"
            >
              <i class="ri-close-circle-line"></i>
              Clear
            </button>
          </div>

          <div class="language-filter-options">
            <label
              v-for="language in languageOptions"
              :key="language.value"
              class="language-filter-option"
              :class="{ active: selectedLanguages.includes(language.value) }"
            >
              <input
                v-model="selectedLanguages"
                type="checkbox"
                :value="language.value"
              />

              <span class="language-filter-check">
                <i class="ri-check-line"></i>
              </span>

              <span>{{ language.label }}</span>
            </label>
          </div>
        </div>
      </div>

      <div v-if="paginatedCourses.length" class="courses-grid">
        <div
          v-for="item in paginatedCourses"
          :key="item.id"
          class="learner-course-card"
          :class="{
            'attempts-exhausted': hasNoExamAttempts(item),
            'exam-passed': hasPassedExam(item),
            'training-upcoming': getTrainingAccessState(item) === 'upcoming',
            'training-ended': getTrainingAccessState(item) === 'ended',
            'training-expired': isEnrollmentExpired(item),
          }"
        >
          <div class="course-cover">
            <img
              v-if="getCourseImage(item.course)"
              :src="getCourseImage(item.course)"
              :alt="item.course?.title || 'Course image'"
              class="course-img"
            />

            <div v-else class="course-fallback">
              {{ getCourseInitials(item.course) }}
            </div>

            <span :class="getStatusClass(item.status)">
              {{ getStatusLabel(item.status) }}
            </span>

            <span
              v-if="isCourseAccessLocked(item)"
              class="attempts-exhausted-badge"
              :class="{ passed: hasPassedExam(item) }"
            >
              <i
                :class="hasPassedExam(item)
                  ? 'ri-award-line'
                  : isTrainingUnavailable(item)
                    ? getTrainingStateIcon(item)
                    : 'ri-lock-line'"
              ></i>
              {{
                isEnrollmentExpired(item)
                  ? 'Training validity expired'
                  : hasPassedExam(item)
                    ? 'Final exam passed'
                    : isTrainingUnavailable(item)
                    ? getTrainingStateLabel(item)
                    : 'Exam attempts finished'
              }}
            </span>
          </div>

          <div class="course-body">
            <div class="course-card-content">
              <div class="course-heading-meta">
                <div class="course-category">
                  <i class="ri-folder-line"></i>
                  {{ item.course?.category?.name || 'Course' }}
                </div>

              
              </div>

              <h3>
                {{ item.course?.title || '-' }}
              </h3>

              <p>
                {{ item.course?.description || 'No description available.' }}
              </p>

              <div class="course-meta">
                <span>
                  <i class="ri-global-line"></i>
                  {{ item.course?.language || '-' }}
                </span>

                <span>
                  <i class="ri-computer-line"></i>
                  {{ item.course?.training_type || '-' }}
                </span>

                <span>
                  <i class="ri-bar-chart-line"></i>
                  {{ item.course?.skill_level || '-' }}
                </span>

                <span v-if="item.course?.method">
                  <i class="ri-route-line"></i>
                  {{ item.course.method }}
                </span>

                <span v-if="formatDuration(item.course?.duration_hours)">
                  <i class="ri-time-line"></i>
                  {{ formatDuration(item.course.duration_hours) }}
                </span>

                <span v-if="formatPassingScore(item.course?.passing_score)">
                  <i class="ri-medal-line"></i>
                  Passing score: {{ formatPassingScore(item.course.passing_score) }}
                </span>
              </div>

              <div
                v-if="item.training_request"
                class="training-request-panel"
                :class="getTrainingAccessState(item)"
              >
                <div class="training-period-line">
                  <i :class="getTrainingStateIcon(item)"></i>

                  <div>
                    <span>Training period</span>
                    <strong>{{ getTrainingPeriodLabel(item) }}</strong>
                  </div>
                </div>

                <div class="training-accreditations">
                  <span
                    v-for="accreditation in item.training_request.accreditations || []"
                    :key="accreditation.id"
                    class="training-accreditation-badge"
                  >
                    <i class="ri-shield-check-line"></i>
                    {{ accreditation.name }}
                  </span>
                </div>
              </div>
            </div>

            <div class="course-card-footer">
              <div
                class="course-progress-wrapper"
                :class="{
                  'scorm-progress': isScormCourse(item),
                  completed: getCourseProgress(item) >= 100,
                }"
              >
                <div class="course-progress-info">
                  <span>
                    <i :class="isScormCourse(item) ? 'ri-file-zip-line' : 'ri-bar-chart-line'"></i>
                    {{ getCourseProgressLabel(item) }}
                  </span>

                  <strong>{{ getCourseProgress(item) }}%</strong>
                </div>

                <div
                  class="course-progress"
                  role="progressbar"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-valuenow="getCourseProgress(item)"
                  :aria-label="`${getCourseProgressLabel(item)}: ${getCourseProgress(item)}%`"
                >
                  <div
                    class="course-progress-bar"
                    :style="{ width: `${getCourseProgress(item)}%` }"
                  ></div>
                </div>
              </div>

              <div class="enrollment-info">
                <span
                  v-if="item.attempts_rest !== null && item.attempts_rest !== undefined"
                  :class="{
                    'no-attempts': hasNoExamAttempts(item),
                    'exam-passed': hasPassedExam(item),
                  }"
                >
                  <i
                    :class="hasPassedExam(item)
                      ? 'ri-award-line'
                      : hasNoExamAttempts(item)
                        ? 'ri-lock-line'
                        : 'ri-repeat-line'"
                  ></i>

                  {{ getAttemptsLabel(item) }}
                </span>

                <span v-if="item.expired_at || item.duree_validite_formation">
                  <i :class="isEnrollmentExpired(item) ? 'ri-calendar-close-line' : 'ri-calendar-check-line'"></i>
                  {{ item.expired_at
                    ? `${isEnrollmentExpired(item) ? 'Expired' : 'Valid until'}: ${formatTrainingDate(String(item.expired_at).slice(0, 10))}`
                    : `${item.duree_validite_formation} month(s) validity` }}
                </span>

                <a
                  v-if="getSyllabusUrl(item.course)"
                  :href="getSyllabusUrl(item.course)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="syllabus-link"
                >
                  <i class="ri-file-pdf-2-line"></i>
                  View syllabus
                </a>
              </div>

              <Link
                v-if="item.hashid"
                :href="getCourseDestination(item)"
                class="course-action-btn"
                :class="{
                  'finish-destination': isCourseAccessLocked(item),
                  'passed-destination': hasPassedExam(item),
                }"
              >
                <i :class="getButtonIcon(item)"></i>
                {{ getButtonLabel(item) }}
              </Link>

              <button
                v-else
                type="button"
                class="course-action-btn disabled"
                disabled
              >
                <i class="ri-error-warning-line"></i>
                Enrollment unavailable
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-courses">
        <div class="empty-icon">
          <i class="ri-book-open-line"></i>
        </div>

        <h3>No courses found</h3>

        <p>
          No assigned course matches your search or selected status.
        </p>
      </div>

      <div
        v-if="filteredCourses.length > perPage"
        class="courses-pagination-wrapper"
      >
        <div class="pagination-summary">
          Showing
          <strong>{{ displayedFrom }}</strong>
          to
          <strong>{{ displayedTo }}</strong>
          of
          <strong>{{ filteredCourses.length }}</strong>
          courses
        </div>

        <div class="courses-pagination">
          <button
            type="button"
            class="pagination-navigation"
            :disabled="currentPage === 1"
            @click="previousPage"
          >
            <i class="ri-arrow-left-s-line"></i>
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="pagination-page"
            :class="{
              active: page === currentPage,
              dots: page === '...',
            }"
            :disabled="page === '...'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            type="button"
            class="pagination-navigation"
            :disabled="currentPage === totalPages"
            @click="nextPage"
          >
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.learner-courses-page {
  min-height: calc(100vh - 90px);
  background: linear-gradient(180deg, #f4f8fb 0%, #ffffff 52%, #f8fafc 100%);
  padding-bottom: 96px;
}

/* HERO */
.learner-hero {
  position: relative;
  overflow: hidden;
  min-height: 360px;
  padding: 90px 8% 70px;
  background: linear-gradient(100deg, #245775 0%, #176f9d 45%, #0d8fe9 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 48px;
}

.learner-hero::before,
.learner-hero::after {
  content: none;
  display: none;
}

.learner-hero {
  border-bottom: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow: inset 0 -1px 0 rgba(7, 17, 38, 0.08);
}

.learner-hero-content {
  position: relative;
  z-index: 2;
  max-width: 680px;
}

.hero-badge {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #071126;
  background-color: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.35);
  padding: 10px 16px;
  border-radius: 999px;
  font-weight: 800;
  margin-bottom: 22px;
}

.learner-hero h1 {
  color: #ffffff;
  font-size: 58px;
  line-height: 1.05;
  font-weight: 800;
  margin-bottom: 24px;
}

.learner-hero p {
  color: rgba(255, 255, 255, 0.95);
  font-size: 18px;
  line-height: 1.7;
  max-width: 620px;
  margin-bottom: 0;
}

.hero-stats {
  position: relative;
  z-index: 2;
  width: 430px;
  min-width: 380px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.hero-stat-card {
  background-color: rgba(255, 255, 255, 0.96);
  color: #071126;
  border-radius: 20px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 16px 36px rgba(7, 17, 38, 0.16);
}

.hero-stat-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 16px;
  background-color: #eef2ff;
  color: #245775;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.hero-stat-icon.warning {
  background-color: #fff7e6;
  color: #f7b84b;
}

.hero-stat-icon.info {
  background-color: #e8f4ff;
  color: #0d8fe9;
}

.hero-stat-icon.success {
  background-color: #e8f7f4;
  color: #0ab39c;
}

.hero-stat-icon.danger {
  background-color: #fff0ed;
  color: #f06548;
}

.hero-stat-card span {
  display: block;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 4px;
}

.hero-stat-card strong {
  display: block;
  color: #071126;
  font-size: 26px;
  line-height: 1;
}

/* CONTENT */
.courses-container {
  width: min(1240px, calc(100% - 40px));
  margin: -42px auto 0;
  position: relative;
  z-index: 5;
}

.courses-header-card {
  background-color: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.courses-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
}

.courses-header-icon {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #0d8fe9 0%, #071126 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.courses-header-title h2 {
  color: #071126;
  font-size: 24px;
  font-weight: 800;
  margin-bottom: 4px;
}

.courses-header-title p {
  color: #64748b;
  margin-bottom: 0;
}

.courses-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.courses-filter-box,
.courses-search-box {
  position: relative;
}

.courses-filter-box i,
.courses-search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  z-index: 2;
}

.courses-filter-box select,
.courses-search-box input {
  height: 46px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background-color: #ffffff;
  color: #334155;
  outline: none;
  padding-left: 40px;
  padding-right: 14px;
  min-width: 190px;
}

.courses-search-box input {
  min-width: 280px;
}

.courses-filter-box select:focus,
.courses-search-box input:focus {
  border-color: #0d8fe9;
  box-shadow: 0 0 0 0.18rem rgba(13, 143, 233, 0.14);
}

/* GRID */
.courses-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.learner-course-card {
  height: 100%;
  background-color: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
}

.learner-course-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 44px rgba(15, 23, 42, 0.12);
}

.learner-course-card.attempts-exhausted {
  border-color: rgba(36, 87, 117, 0.2);
}

.learner-course-card.attempts-exhausted:hover {
  border-color: rgba(23, 111, 157, 0.38);
}

.learner-course-card.exam-passed {
  border-color: rgba(10, 179, 156, 0.26);
}

.learner-course-card.exam-passed:hover {
  border-color: rgba(10, 179, 156, 0.48);
}

.course-cover {
  position: relative;
  height: 210px;
  background-color: #f1f5f9;
  overflow: hidden;
}

.course-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-fallback {
  width: 100%;
  height: 100%;
  color: #ffffff;
  background: linear-gradient(135deg, #245775 0%, #0d8fe9 100%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 44px;
  font-weight: 900;
}

.course-status {
  position: absolute;
  top: 16px;
  right: 16px;
  padding: 8px 13px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  backdrop-filter: blur(8px);
}

.course-status.completed {
  background-color: rgba(10, 179, 156, 0.95);
  color: #ffffff;
}

.course-status.in-progress {
  background-color: rgba(13, 143, 233, 0.95);
  color: #ffffff;
}

.course-status.not-started {
  background-color: rgba(247, 184, 75, 0.95);
  color: #071126;
}

.course-status.expired {
  background-color: rgba(240, 101, 72, 0.95);
  color: #ffffff;
}

.course-status.default {
  background-color: rgba(100, 116, 139, 0.95);
  color: #ffffff;
}

.attempts-exhausted-badge {
  position: absolute;
  left: 16px;
  bottom: 16px;
  max-width: calc(100% - 32px);
  padding: 8px 12px;
  border-radius: 999px;
  color: #ffffff;
  background: rgba(7, 17, 38, 0.9);
  backdrop-filter: blur(8px);
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.attempts-exhausted-badge.passed {
  background: rgba(10, 179, 156, 0.94);
}


.course-body {
  flex: 1;
  padding: 22px;
  display: flex;
  flex-direction: column;
}

.course-card-content {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.course-card-content .course-category {
  flex-shrink: 0;
}

.course-heading-meta {
  min-height: 28px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.course-category {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0d8fe9;
  font-size: 13px;
  font-weight: 800;
}

.course-ref-badge {
  padding: 5px 8px;
  border: 1px solid rgba(36, 87, 117, 0.16);
  border-radius: 999px;
  color: #245775;
  background: rgba(36, 87, 117, 0.07);
  font-size: 10px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.course-body h3 {
  height: 54px;
  margin-bottom: 10px;
  color: #071126;
  font-size: 20px;
  line-height: 1.35;
  font-weight: 800;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-body p {
  height: 48px;
  margin-bottom: 14px;
  color: #64748b;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-meta {
  min-height: 34px;
  margin-bottom: 0;
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
  gap: 9px;
}

.course-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background-color: #f8fafc;
  color: #475569;
  border: 1px solid #edf2f7;
  border-radius: 999px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 700;
}

.scorm-type-badge {
  width: fit-content;
  margin: -3px 0 10px;
  padding: 5px 8px;
  border-radius: 999px;
  color: #245775;
  background: rgba(23, 111, 157, 0.1);
  font-size: 9px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.scorm-direct-access {
  min-height: 60px;
  margin-bottom: 14px;
  padding: 10px;
  border: 1px solid rgba(10, 179, 156, 0.18);
  border-radius: 13px;
  color: #087f6f;
  background: rgba(10, 179, 156, 0.07);
  display: flex;
  align-items: center;
  gap: 9px;
}

.scorm-direct-icon {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 10px;
  color: #ffffff;
  background: #0ab39c;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.scorm-direct-access span,
.scorm-direct-access strong {
  display: block;
}

.scorm-direct-access span {
  margin-bottom: 2px;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
}

.scorm-direct-access strong {
  font-size: 10px;
  line-height: 1.35;
}

.course-card-footer {
  width: 100%;
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.course-progress-wrapper:last-child,
.enrollment-info:last-child {
  margin-bottom: 0;
}

.course-progress-wrapper {
  margin-bottom: 14px;
}

.course-progress-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #64748b;
  font-size: 13px;
  font-weight: 800;
  margin-bottom: 8px;
}

.course-progress-info strong {
  color: #071126;
}

.course-progress-info span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.course-progress-info span i {
  color: #0d8fe9;
  font-size: 14px;
}

.course-progress-wrapper.scorm-progress {
  padding: 11px 12px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 13px;
  background: #f7fbfe;
}

.course-progress-wrapper.scorm-progress .course-progress-info {
  margin-bottom: 9px;
}

.course-progress-wrapper.completed .course-progress-info strong,
.course-progress-wrapper.completed .course-progress-info span i {
  color: #087f6f;
}

.course-progress-wrapper.completed .course-progress-bar {
  background: linear-gradient(90deg, #087f6f 0%, #0ab39c 100%);
}

.course-progress {
  height: 9px;
  border-radius: 999px;
  background-color: #e9eef6;
  overflow: hidden;
}

.course-progress-bar {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #0d8fe9 0%, #071126 100%);
  transition: width 0.25s ease;
}

.enrollment-info {
  min-height: 34px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.enrollment-info span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #64748b;
  background-color: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 999px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 700;
}

.enrollment-info i {
  color: #0d8fe9;
  font-size: 14px;
}

.enrollment-info span.no-attempts {
  color: #245775;
  border-color: rgba(23, 111, 157, 0.2);
  background: rgba(104, 124, 254, 0.08);
}

.enrollment-info span.no-attempts i {
  color: #245775;
}

.enrollment-info .syllabus-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 10px;
  border: 1px solid rgba(240, 101, 72, 0.2);
  border-radius: 999px;
  color: #d94f34;
  background: rgba(240, 101, 72, 0.07);
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
}

.enrollment-info .syllabus-link i {
  color: #f06548;
}

.enrollment-info .syllabus-link:hover {
  color: #ffffff;
  background: #f06548;
  border-color: #f06548;
}

.enrollment-info .syllabus-link:hover i {
  color: #ffffff;
}

.course-action-btn {
  height: 46px;
  border-radius: 14px;
  background-color: #071126;
  color: #ffffff;
  text-decoration: none;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  border: none;
  transition: all 0.2s ease;
}

.course-action-btn:hover {
  color: #ffffff;
  background-color: #0d8fe9;
  transform: translateY(-1px);
}

.course-action-btn.finish-destination {
  background: linear-gradient(135deg, #245775 0%, #0d8fe9 100%);
  box-shadow: 0 9px 20px rgba(23, 111, 157, 0.22);
}

.course-action-btn.finish-destination:hover {
  color: #ffffff;
  background: linear-gradient(135deg, #1d4964 0%, #0b78c4 100%);
  box-shadow: 0 12px 24px rgba(23, 111, 157, 0.28);
}

.course-action-btn.passed-destination {
  background: linear-gradient(135deg, #0ab39c 0%, #087f6f 100%);
  box-shadow: 0 9px 20px rgba(10, 179, 156, 0.22);
}

.course-action-btn.passed-destination:hover {
  color: #ffffff;
  background: linear-gradient(135deg, #089683 0%, #06695d 100%);
  box-shadow: 0 12px 24px rgba(10, 179, 156, 0.28);
}

.course-action-btn.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* PAGINATION */
.courses-pagination-wrapper {
  margin-top: 28px;
  padding: 20px 22px;
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.pagination-summary {
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.pagination-summary strong {
  color: #071126;
  font-weight: 900;
}

.courses-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.courses-pagination button {
  min-width: 40px;
  height: 40px;
  padding: 0 10px;
  border: none;
  border-radius: 11px;
  color: #64748b;
  background-color: #f8fafc;
  font-size: 13px;
  font-weight: 900;
  cursor: pointer;
  transition: all 0.2s ease;
}

.courses-pagination button:hover:not(:disabled) {
  color: #ffffff;
  background-color: #0d8fe9;
}

.courses-pagination button.active {
  color: #ffffff;
  background-color: #071126;
  box-shadow: 0 8px 18px rgba(7, 17, 38, 0.16);
}

.courses-pagination button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.courses-pagination button.dots {
  color: #94a3b8;
  background-color: transparent;
}

/* EMPTY */
.empty-courses {
  background-color: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 24px;
  padding: 70px 24px;
  text-align: center;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
}

.empty-icon {
  width: 86px;
  height: 86px;
  margin: 0 auto 18px;
  border-radius: 28px;
  background: linear-gradient(135deg, rgba(36, 87, 117, 0.14), rgba(13, 143, 233, 0.12));
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
}

.empty-courses h3 {
  color: #071126;
  font-weight: 800;
  margin-bottom: 8px;
}

.empty-courses p {
  color: #64748b;
  margin-bottom: 0;
}

/* RESPONSIVE */
@media (max-width: 1199.98px) {
  .learner-hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .hero-stats {
    width: 100%;
    min-width: 100%;
  }

  .courses-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .courses-header-card {
    flex-direction: column;
    align-items: stretch;
  }

  .courses-controls {
    width: 100%;
  }

  .courses-filter-box,
  .courses-search-box {
    flex: 1;
  }

  .courses-filter-box select,
  .courses-search-box input {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 767.98px) {
  .courses-pagination-wrapper {
    padding: 16px;
    flex-direction: column;
    align-items: stretch;
  }

  .pagination-summary {
    text-align: center;
  }

  .courses-pagination {
    flex-wrap: wrap;
  }

  .learner-hero {
    padding: 70px 24px 80px;
  }

  .learner-hero h1 {
    font-size: 42px;
  }

  .learner-hero p {
    font-size: 16px;
  }

  .hero-stats {
    grid-template-columns: 1fr;
  }

  .courses-container {
    width: calc(100% - 28px);
  }

  .courses-grid {
    grid-template-columns: 1fr;
  }

  .courses-controls {
    flex-direction: column;
  }

  .courses-header-title {
    align-items: flex-start;
  }
}

@media (max-width: 575.98px) {
  .course-card-footer {
    margin-top: auto;
    padding-top: 12px;
  }

  .courses-pagination button {
    min-width: 36px;
    height: 36px;
  }

  .learner-hero h1 {
    font-size: 36px;
  }

  .courses-header-card {
    padding: 18px;
    border-radius: 18px;
  }

  .courses-header-title {
    flex-direction: column;
  }

  .course-cover {
    height: 180px;
  }

  .course-body h3 {
    height: auto;
    min-height: 27px;
  }

  .course-body p {
    height: auto;
    min-height: 24px;
  }
}


.training-request-panel {
  margin-top: 14px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}

.training-request-panel.upcoming {
  border-color: rgba(247, 184, 75, 0.35);
  background: rgba(247, 184, 75, 0.08);
}

.training-request-panel.ended {
  border-color: rgba(240, 101, 72, 0.28);
  background: rgba(240, 101, 72, 0.07);
}

.training-period-line {
  display: flex;
  align-items: center;
  gap: 10px;
}

.training-period-line > i {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 11px;
  background: #eaf4fa;
  color: #245775;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
}

.training-period-line span {
  display: block;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.training-period-line strong {
  color: #334155;
  font-size: 12px;
  font-weight: 850;
}

.training-accreditations {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 10px;
}

.training-accreditation-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 9px;
  border-radius: 999px;
  background: rgba(10, 179, 156, 0.12);
  color: #0a8f7e;
  font-size: 10.5px;
  font-weight: 800;
}

.learner-course-card.training-upcoming,
.learner-course-card.training-ended,
.learner-course-card.training-expired {
  border-color: #e2e8f0;
}



.course-language-filter-panel {
  margin-top: 14px;
  padding: 14px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #f8fafc;
}

.language-filter-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 11px;
}

.language-filter-title > i {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 11px;
  background: #eaf4fa;
  color: #245775;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.language-filter-title div {
  flex: 1;
}

.language-filter-title strong,
.language-filter-title span {
  display: block;
}

.language-filter-title strong {
  color: #334155;
  font-size: 12px;
  font-weight: 900;
}

.language-filter-title span {
  color: #64748b;
  font-size: 10.5px;
}

.language-filter-title button {
  border: 0;
  background: transparent;
  color: #f06548;
  font-size: 11px;
  font-weight: 850;
}

.language-filter-options {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.language-filter-option {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 11px;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #ffffff;
  color: #475569;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s ease;
}

.language-filter-option input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.language-filter-option.active {
  border-color: #176f9d;
  background: #eaf4fa;
  color: #245775;
}

.language-filter-check {
  width: 18px;
  height: 18px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: transparent;
}

.language-filter-option.active .language-filter-check {
  border-color: #176f9d;
  background: #176f9d;
  color: #ffffff;
}

@media (max-width: 575.98px) {
  .language-filter-title {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .language-filter-title button {
    width: 100%;
    text-align: left;
    padding-left: 46px;
  }
}



/* =========================================================
   HOME PAGE VISUAL SYSTEM + FOOTER SAFE LAYOUT
   ========================================================= */
.learner-courses-page {
  --layout-footer-height: 62px;
  --home-cyan: #37cced;
  --home-blue: #0d8fe9;
  --home-blue-deep: #087bc9;
  --home-night: #071126;
  --home-night-hover: #102044;
  --home-soft: #eaf7ff;
  --home-border: #e4edf5;

  min-height: calc(100dvh - 82px);
  padding-bottom: calc(var(--layout-footer-height) + 54px);
  background: linear-gradient(
    180deg,
    #f6fbff 0%,
    #ffffff 50%,
    #f8fafc 100%
  );
}

.learner-hero {
  min-height: 340px;
  padding: 72px 8% 68px;
  border-bottom: 0;
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: none;
}

.learner-hero::before,
.learner-hero::after {
  content: none;
  display: none;
}

.hero-badge {
  color: var(--home-night);
  background: rgba(255, 255, 255, 0.95);
  border-color: rgba(255, 255, 255, 0.5);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.1);
}

.learner-hero h1 {
  margin-bottom: 18px;
  font-size: clamp(42px, 5vw, 62px);
  font-weight: 900;
  letter-spacing: -0.035em;
}

.learner-hero p {
  max-width: 660px;
  color: rgba(255, 255, 255, 0.94);
  font-size: 16px;
}

.hero-stats {
  width: 440px;
  gap: 13px;
}

.hero-stat-card {
  min-height: 92px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.62);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.14);
}

.hero-stat-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  color: var(--home-blue-deep);
  background: var(--home-soft);
}

.courses-container {
  width: min(1320px, calc(100% - 44px));
  margin-top: -40px;
}

.courses-header-card {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 18px 24px;
  margin-bottom: 22px;
  padding: 22px;
  overflow: hidden;
  border: 1px solid var(--home-border);
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
}

.courses-header-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 22px;
  left: 22px;
  height: 3px;
  border-radius: 0 0 999px 999px;
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
}

.courses-header-icon {
  background: linear-gradient(
    135deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.2);
}

.courses-controls {
  justify-content: flex-end;
}

.courses-filter-box select,
.courses-search-box input {
  border-color: #dfe8f1;
  background: #fbfdff;
}

.courses-filter-box select:hover,
.courses-search-box input:hover {
  border-color: rgba(13, 143, 233, 0.28);
}

.courses-filter-box select:focus,
.courses-search-box input:focus {
  border-color: var(--home-blue);
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.12);
}

.course-language-filter-panel {
  grid-column: 1 / -1;
  width: 100%;
  margin-top: 0;
  padding: 13px 14px;
  border-color: #e1eaf2;
  background: #f8fbfe;
}

.language-filter-title > i {
  color: var(--home-blue-deep);
  background: var(--home-soft);
}

.language-filter-option {
  border-color: #dfe8f1;
  box-shadow: 0 3px 10px rgba(15, 23, 42, 0.025);
}

.language-filter-option:hover {
  color: var(--home-blue-deep);
  border-color: rgba(13, 143, 233, 0.28);
  background: #f5fbff;
}

.language-filter-option.active {
  color: var(--home-blue-deep);
  border-color: rgba(13, 143, 233, 0.42);
  background: var(--home-soft);
  box-shadow: 0 7px 16px rgba(13, 143, 233, 0.08);
}

.language-filter-option.active .language-filter-check {
  border-color: var(--home-blue);
  background: var(--home-blue);
}

.courses-grid {
  gap: 20px;
}

.learner-course-card {
  position: relative;
  border-color: var(--home-border);
  border-radius: 22px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.065);
}

.learner-course-card::before {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 2;
  height: 3px;
  opacity: 0;
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  transition: opacity 0.2s ease;
}

.learner-course-card:hover {
  border-color: rgba(13, 143, 233, 0.28);
  box-shadow: 0 20px 44px rgba(15, 23, 42, 0.11);
}

.learner-course-card:hover::before {
  opacity: 1;
}

.course-cover {
  height: 198px;
}

.course-fallback {
  background: linear-gradient(
    135deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
}

.course-body {
  padding: 19px;
}

.course-category,
.course-meta i,
.enrollment-info i {
  color: var(--home-blue-deep);
}

.course-meta span,
.enrollment-info span {
  border-color: #e4ecf3;
  background: #f8fafc;
}

.training-request-panel {
  border-color: #dfe9f1;
  background: #f8fbfe;
}

.training-period-line > i {
  color: var(--home-blue-deep);
  background: var(--home-soft);
}

.course-progress-bar {
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
}

.enrollment-info span.no-attempts {
  color: var(--home-blue-deep);
  border-color: rgba(13, 143, 233, 0.2);
  background: var(--home-soft);
}

.enrollment-info span.no-attempts i {
  color: var(--home-blue-deep);
}

.course-action-btn {
  color: #ffffff;
  background: var(--home-night);
  box-shadow: 0 10px 22px rgba(7, 17, 38, 0.16);
}

.course-action-btn:hover {
  color: #ffffff;
  background: var(--home-night-hover);
  box-shadow: 0 14px 28px rgba(7, 17, 38, 0.22);
}

.course-action-btn.finish-destination {
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.22);
}

.course-action-btn.finish-destination:hover {
  background: linear-gradient(
    90deg,
    #2dbdde 0%,
    #087bc9 100%
  );
  box-shadow: 0 14px 28px rgba(13, 143, 233, 0.28);
}

.courses-pagination-wrapper {
  border-color: var(--home-border);
}

.courses-pagination button:hover:not(:disabled) {
  background: var(--home-blue);
}

.courses-pagination button.active {
  background: var(--home-night);
}

.empty-icon {
  color: var(--home-blue);
  background: var(--home-soft);
}

@media (max-width: 1199.98px) {
  .learner-hero {
    padding: 58px 6% 76px;
  }

  .courses-header-card {
    grid-template-columns: 1fr;
  }

  .courses-controls {
    width: 100%;
    justify-content: stretch;
  }
}

@media (max-width: 767.98px) {
  .learner-courses-page {
    padding-bottom: calc(var(--layout-footer-height) + 36px);
  }

  .learner-hero {
    padding: 48px 22px 66px;
  }

  .courses-container {
    width: calc(100% - 24px);
    margin-top: -30px;
  }

  .courses-header-card {
    padding: 18px;
    border-radius: 19px;
  }

  .course-language-filter-panel {
    padding: 12px;
  }

  .course-cover {
    height: 205px;
  }
}

@media (max-width: 575.98px) {
  .learner-hero h1 {
    font-size: 36px;
  }

  .hero-stat-card {
    min-height: 84px;
  }

  .courses-header-title h2 {
    font-size: 21px;
  }

  .course-body {
    padding: 17px;
  }

  .language-filter-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .language-filter-option {
    justify-content: flex-start;
  }
}

@media (prefers-reduced-motion: reduce) {
  .learner-course-card,
  .learner-course-card::before,
  .course-action-btn,
  .language-filter-option {
    transition: none !important;
  }

  .learner-course-card:hover,
  .course-action-btn:hover {
    transform: none !important;
  }
}

</style>