<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  enrollment: {
    type: Object,
    required: true,
  },
})

const isScormCourse = computed(() => {
  return props.enrollment.course?.content_type === 'scorm' ||
    Boolean(props.enrollment.is_scorm)
})

const courseCompleted = computed(() => {
  return Boolean(props.enrollment.course_completed)
})

const hasAttemptsRemaining = computed(() => {
  return Number(props.enrollment.attempts_rest || 0) > 0
})

const hasPassedExam = computed(() => {
  return Boolean(props.enrollment.has_passed_exam)
})

const certificates = computed(() => {
  if (!Array.isArray(props.enrollment.certificates)) {
    return []
  }

  /*
   * Only certificates generated from the approvals linked to the
   * company request are displayed. Legacy generic certificates are ignored.
   */
  return props.enrollment.certificates.filter(certificate => {
    return Boolean(
      certificate?.accreditation?.id ||
      certificate?.accreditation_id
    )
  })
})

const hasCertificates = computed(() => {
  return certificates.value.length > 0
})

const trainingRequest = computed(() => {
  return props.enrollment.training_request || null
})

const expiration = computed(() => {
  return props.enrollment.expiration ||
    trainingRequest.value?.expiration ||
    {}
})

const isExpired = computed(() => {
  return Boolean(
    props.enrollment.is_expired ||
    expiration.value?.is_expired
  )
})

const validityMonths = computed(() => {
  const value = props.enrollment.duree_validite_formation ??
    expiration.value?.duration_months ??
    trainingRequest.value?.duree_validite_formation

  if (value === null || value === undefined || value === '') {
    return null
  }

  const months = Number(value)
  return Number.isFinite(months) && months > 0 ? months : null
})

const validityLabel = computed(() => {
  if (!validityMonths.value) return 'No expiration'

  return `${validityMonths.value} month${validityMonths.value === 1 ? '' : 's'}`
})

const trainingAccessState = computed(() => {
  return trainingRequest.value?.access_state || 'unrestricted'
})

const trainingUnavailable = computed(() => {
  return Boolean(
    isExpired.value ||
    (
      trainingRequest.value &&
      trainingRequest.value.can_access_course === false
    )
  )
})

const canViewCourse = computed(() => {
  return Boolean(props.enrollment.can_view_course)
})

const accessLocked = computed(() => {
  return isExpired.value ||
    hasPassedExam.value ||
    !hasAttemptsRemaining.value ||
    trainingUnavailable.value
})

const hasInProgressExam = computed(() => {
  return Boolean(props.enrollment.has_in_progress_exam)
})

const canStartExam = computed(() => {
  return Boolean(props.enrollment.can_start_exam)
})

const examActionLabel = computed(() => {
  return hasInProgressExam.value
    ? 'Continue exam'
    : 'Pass exam'
})

const finishTitle = computed(() => {
  if (isExpired.value) {
    return 'Training validity expired'
  }

  if (hasPassedExam.value) {
    return 'Training completed'
  }

  if (!hasAttemptsRemaining.value) {
    return 'Course and exam summary'
  }

  if (trainingAccessState.value === 'upcoming') {
    return 'Training not started yet'
  }

  if (trainingAccessState.value === 'ended') {
    return 'Training period ended'
  }

  if (isScormCourse.value) {
    return 'Course and final exam'
  }

  return courseCompleted.value
    ? 'Course summary'
    : 'Complete your course'
})

const finishDescription = computed(() => {
  if (isExpired.value) {
    return `This training and its certificates expired on ${formatDate(props.enrollment.expired_at)}.`
  }

  if (hasPassedExam.value) {
    return 'You have passed the final exam. The course and exam are now closed for additional access.'
  }

  if (!hasAttemptsRemaining.value) {
    return 'All exam attempts have been used. You can only review the course and exam summary.'
  }

  if (trainingUnavailable.value) {
    return trainingRequest.value?.blocked_reason ||
      'Course and exam access are unavailable outside the approved training period.'
  }

  if (isScormCourse.value) {
    return 'Review the Course and start or resume the final exam whenever you are ready.'
  }

  if (!courseCompleted.value) {
    return 'You still have an exam attempt, but you must complete every active slide again before starting the next exam.'
  }

  return 'Review your course information, check your exam attempts history, and continue to the final exam when you are ready.'
})

const backButtonLabel = computed(() => {
  return canViewCourse.value
    ? (isScormCourse.value ? 'Back to course' : 'Back to course')
    : 'Back to my courses'
})

const backButtonIcon = computed(() => {
  return canViewCourse.value
    ? 'ri-arrow-left-line'
    : 'ri-book-open-line'
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

const formatPercent = value => {
  const percent = Number(value || 0)

  if (Number.isNaN(percent)) {
    return 0
  }

  return Math.min(Math.max(percent, 0), 100).toFixed(0)
}

/*
|--------------------------------------------------------------------------
| Course progress displayed in the summary
|--------------------------------------------------------------------------
|
| Standard courses use enrollment.progress_percent.
| SCORM courses must prefer the values reported by the SCORM runtime.
|
*/
const progressMeasureToPercent = value => {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const measure = Number(value)

  if (!Number.isFinite(measure)) {
    return null
  }

  return Math.min(
    Math.max(measure <= 1 ? measure * 100 : measure, 0),
    100,
  )
}

const normalizedScormStatus = value => {
  return String(value || '').trim().toLowerCase()
}

const courseProgressPercent = computed(() => {
  if (courseCompleted.value) {
    return 100
  }

  if (!isScormCourse.value) {
    return Number(formatPercent(props.enrollment.progress_percent))
  }

  const tracking = props.enrollment.scorm_tracking || {}
  const cmiData = tracking.cmi_data || {}

  const statuses = [
    tracking.completion_status,
    tracking.success_status,
    tracking.lesson_status,
    cmiData['cmi.completion_status'],
    cmiData['cmi.success_status'],
    cmiData['cmi.core.lesson_status'],
  ].map(normalizedScormStatus)

  if (statuses.some(status => ['completed', 'passed'].includes(status))) {
    return 100
  }

  const directPercentCandidates = [
    props.enrollment.scorm_progress_percent,
    tracking.progress_percent,
  ]

  for (const candidate of directPercentCandidates) {
    if (candidate === null || candidate === undefined || candidate === '') {
      continue
    }

    const percent = Number(candidate)

    if (Number.isFinite(percent)) {
      return Math.min(Math.max(percent, 0), 100)
    }
  }

  const progressMeasureCandidates = [
    tracking.progress_measure,
    cmiData['cmi.progress_measure'],
    cmiData['cmi.core.progress_measure'],
  ]

  for (const candidate of progressMeasureCandidates) {
    const percent = progressMeasureToPercent(candidate)

    if (percent !== null) {
      return percent
    }
  }

  return Number(formatPercent(props.enrollment.progress_percent))
})

const courseProgressLabel = computed(() => {
  return isScormCourse.value
    ? 'SCORM progress'
    : 'Course progress'
})

const formatNumber = value => {
  const number = Number(value)

  if (!Number.isFinite(number)) {
    return null
  }

  return Number.isInteger(number)
    ? String(number)
    : number.toFixed(2).replace(/\.?0+$/, '')
}

const formattedDuration = computed(() => {
  const duration = formatNumber(props.enrollment.course?.duration_hours)

  return duration ? `${duration} hour${Number(duration) === 1 ? '' : 's'}` : '-'
})

const formattedPassingScore = computed(() => {
  const passingScore = formatNumber(props.enrollment.course?.passing_score)

  return passingScore ? `${passingScore}%` : '-'
})

const syllabusUrl = computed(() => {
  const course = props.enrollment.course

  if (!course) return null

  if (course.syllabus_url) {
    return course.syllabus_url
  }

  if (course.syllabus) {
    return `/storage/${String(course.syllabus).replace(/^\/+/, '')}`
  }

  return null
})

const syllabusName = computed(() => {
  const course = props.enrollment.course

  if (!course) return 'View syllabus'

  return course.syllabus_name || 'View syllabus'
})

const formatDate = value => {
  if (!value) return '-'

  return new Date(value).toLocaleString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatTrainingDate = value => {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const getTrainingStateLabel = state => {
  if (state === 'expired') return 'Expired'
  if (state === 'upcoming') return 'Upcoming'
  if (state === 'ended') return 'Ended'
  if (state === 'active') return 'Active'

  return 'Unrestricted'
}

const getCertificateDownloadUrl = certificate => {
  if (!certificate?.hashid || !props.enrollment.hashid) return '#'

  const baseUrl = route(
    'learner-space.certificate.download',
    props.enrollment.hashid
  )

  return `${baseUrl}?certificate=${encodeURIComponent(certificate.hashid)}`
}

const getAccreditationCode = certificate => {
  return String(
    certificate?.accreditation?.code ||
    certificate?.accreditation?.name ||
    'Certificate'
  ).toUpperCase()
}

const getCertificateIssuer = certificate => {
  return certificate?.issuer_name ||
    certificate?.accreditation?.organization_name ||
    'Training organization'
}

const getCertificateApprovalReference = certificate => {
  return certificate?.approval_reference ||
    certificate?.accreditation?.approval_reference ||
    null
}

const getExamStatusClass = status => {
  switch (status) {
    case 'passed':
      return 'exam-status passed'
    case 'failed':
      return 'exam-status failed'
    case 'in_progress':
      return 'exam-status in-progress'
    case 'pending':
      return 'exam-status pending'
    default:
      return 'exam-status default'
  }
}

const getExamStatusLabel = status => {
  switch (status) {
    case 'passed':
      return 'Passed'
    case 'failed':
      return 'Failed'
    case 'in_progress':
      return 'In progress'
    case 'pending':
      return 'Pending'
    default:
      return status || '-'
  }
}

const goBackToCourse = () => {
  router.visit(route('learner-space.course-viewer', props.enrollment.hashid))
}

const goBackFromFinish = () => {
  if (!canViewCourse.value) {
    router.visit(route('learner-space.my-courses'))
    return
  }

  goBackToCourse()
}

const startExam = () => {
  if (!props.enrollment.hashid) return
  if (!canStartExam.value) return

  router.visit(route('learner-space.exam.start', props.enrollment.hashid))
}
</script>

<template>
  <Head :title="`${enrollment.course?.title || 'Course'} - Finish`" />

  <section class="course-finish-page">
    <div class="finish-hero">
      <div class="finish-hero-content">
        <div class="hero-badge">
          <i class="ri-flag-line"></i>
          {{ hasPassedExam ? 'Exam passed' : isScormCourse ? 'Course' : courseCompleted ? 'Course completed' : 'Course required' }}
        </div>

        <h1>
          {{ finishTitle }}
        </h1>

        <p>
          {{ finishDescription }}
        </p>

        <div class="hero-actions">
          <button
            type="button"
            class="hero-secondary-btn"
            @click="goBackFromFinish"
          >
            <i :class="backButtonIcon"></i>
            {{ backButtonLabel }}
          </button>

          <button
            v-if="canStartExam"
            type="button"
            class="hero-primary-btn"
            @click="startExam"
          >
            <i :class="hasInProgressExam ? 'ri-play-circle-line' : 'ri-edit-circle-line'"></i>
            {{ examActionLabel }}
          </button>

          <button
            v-else-if="!isScormCourse && !hasPassedExam && !courseCompleted && hasAttemptsRemaining"
            type="button"
            class="hero-primary-btn course-required"
            @click="goBackToCourse"
          >
            <i class="ri-book-open-line"></i>
            Complete course first
          </button>

          <button
            v-else
            type="button"
            class="hero-primary-btn disabled"
            disabled
          >
            <i :class="hasPassedExam ? 'ri-checkbox-circle-line' : 'ri-lock-line'"></i>
            {{
              isExpired
                ? 'Training expired'
                : hasPassedExam
                  ? 'Exam already passed'
                  : trainingUnavailable
                  ? getTrainingStateLabel(trainingAccessState)
                  : 'No attempts left'
            }}
          </button>
        </div>
      </div>

      <div
        class="attempts-card"
        :class="{ 'exam-passed-card': hasPassedExam }"
      >
        <div
          class="attempts-icon"
          :class="{ passed: hasPassedExam }"
        >
          <i :class="hasPassedExam ? 'ri-award-line' : 'ri-repeat-line'"></i>
        </div>

        <template v-if="hasPassedExam">
          <span class="final_exam_result">Final exam result</span>

          <strong class="exam-passed-label">
            Passed
          </strong>

          <p>
            Congratulations! You passed the final exam. The course and exam are
            now completed.
          </p>

          <div class="exam-passed-mini-badge">
            <i class="ri-checkbox-circle-fill"></i>
            Training completed
          </div>

          <div
            v-if="hasCertificates"
            class="certificate-section"
          >
            <div class="certificate-section-header">
              <div>
                <span>Issued certificates</span>
                <strong>
                  {{ certificates.length }} approval certificate{{ certificates.length > 1 ? 's' : '' }}
                </strong>
              </div>

              <i class="ri-shield-check-line"></i>
            </div>

            <div class="certificate-list">
              <a
                v-for="certificate in certificates"
                :key="certificate.id"
                :href="getCertificateDownloadUrl(certificate)"
                class="certificate-item"
              >
                <div class="certificate-item-icon">
                  <i class="ri-award-line"></i>
                </div>

                <div class="certificate-item-content">
                  <span>{{ getAccreditationCode(certificate) }}</span>
                  <strong>{{ certificate.certificate_number }}</strong>
                  <small>{{ getCertificateIssuer(certificate) }}</small>

                  <small class="certificate-validity">
                    <i class="ri-calendar-check-line"></i>
                    {{ certificate.expired_at
                      ? `Valid until ${formatDate(certificate.expired_at)}`
                      : 'No expiration date' }}
                  </small>

                  <small
                    v-if="getCertificateApprovalReference(certificate)"
                    class="certificate-approval-reference"
                  >
                    Approval: {{ getCertificateApprovalReference(certificate) }}
                  </small>
                </div>

                <i class="ri-download-2-line certificate-item-download"></i>
              </a>
            </div>
          </div>

          <div
            v-else
            class="certificate-unavailable"
          >
            <i class="ri-information-line"></i>
            <span>
              No certificate approval is currently linked to this training request.
            </span>
          </div>
        </template>

        <template v-else>
          <span class="final_exam_result">Attempts remaining</span>

          <strong>{{ enrollment.attempts_rest || 0 }}</strong>

          <p>
            {{
              !courseCompleted && hasAttemptsRemaining
                ? 'Complete every active slide before using the next attempt.'
                : hasAttemptsRemaining
                  ? 'You can start the exam after the course is fully completed.'
                  : 'No additional exam attempt is available.'
            }}
          </p>
        </template>
      </div>
    </div>

    <div class="finish-container">
      <div
        v-if="!isScormCourse && !hasPassedExam && !courseCompleted && hasAttemptsRemaining"
        class="course-required-alert"
      >
        <div class="course-required-alert-icon">
          <i class="ri-lock-unlock-line"></i>
        </div>

        <div class="finish-alert-content">
          <h3>Course unlocked for another learning cycle</h3>
          <p>
            Your previous exam was not successful. Your course progress has been
            reset, and you must complete all active slides again before starting
            the next exam attempt.
          </p>
        </div>

        <button
          type="button"
          @click="goBackToCourse"
        >
          Continue course
          <i class="ri-arrow-right-line"></i>
        </button>
      </div>

      <div class="course-summary-card">
        <div class="course-summary-media">
          <div class="course-cover">
            <img
              v-if="getCourseImage(enrollment.course)"
              :src="getCourseImage(enrollment.course)"
              :alt="enrollment.course?.title || 'Course image'"
            />

            <div v-else class="course-fallback">
              {{ getCourseInitials(enrollment.course) }}
            </div>
          </div>

          <div class="course-media-caption">
            <span>
              <i class="ri-book-open-line"></i>
              Course overview
            </span>
          </div>
        </div>

        <div class="course-summary-content">
          <div class="course-summary-header">
            <div class="course-heading-block">
              <div class="course-category">
                <span class="course-category-name">
                  <i class="ri-folder-line"></i>
                  {{ enrollment.course?.category?.name || 'Course' }}
                </span>
              </div>

              <h2>{{ enrollment.course?.title || '-' }}</h2>

              <p>
                {{ enrollment.course?.description || 'No description available.' }}
              </p>
            </div>

            <a
              v-if="syllabusUrl"
              :href="syllabusUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="course-summary-resource"
            >
              <span class="resource-icon">
                <i class="ri-file-pdf-2-line"></i>
              </span>

              <span class="resource-copy">
                <small>Course document</small>
                <strong>syllabus</strong>
              </span>

              <i class="ri-external-link-line resource-open-icon"></i>
            </a>
          </div>

          <div class="course-meta">
            <span>
              <i class="ri-global-line"></i>
              {{ enrollment.course?.language || '-' }}
            </span>

            <span>
              <i class="ri-computer-line"></i>
              {{ enrollment.course?.training_type || '-' }}
            </span>

            <span>
              <i class="ri-bar-chart-line"></i>
              {{ enrollment.course?.skill_level || '-' }}
            </span>

            <span>
              <i class="ri-focus-3-line"></i>
              {{ enrollment.course?.course_focus || '-' }}
            </span>

            <span v-if="enrollment.course?.method">
              <i class="ri-presentation-line"></i>
              {{ enrollment.course.method }}
            </span>

            <span v-if="enrollment.course?.duration_hours !== null && enrollment.course?.duration_hours !== undefined">
              <i class="ri-time-line"></i>
              {{ formattedDuration }}
            </span>

            <span v-if="enrollment.course?.passing_score !== null && enrollment.course?.passing_score !== undefined">
              <i class="ri-percent-line"></i>
              Passing score: {{ formattedPassingScore }}
            </span>
          </div>

          <div
            v-if="trainingRequest"
            class="training-overview"
            :class="trainingAccessState"
          >
            <div class="training-overview-main">
              <div class="training-overview-item period-item">
                <span class="training-overview-icon">
                  <i class="ri-calendar-check-line"></i>
                </span>

                <div class="training-overview-copy">
                  <small>Approved training period</small>
                  <strong>
                    {{ formatTrainingDate(trainingRequest.start_date) }}
                    <i class="ri-arrow-right-line"></i>
                    {{ formatTrainingDate(trainingRequest.end_date) }}
                  </strong>
                </div>
              </div>

              <span class="training-state-badge">
                <i class="ri-checkbox-blank-circle-fill"></i>
                {{ getTrainingStateLabel(trainingAccessState) }}
              </span>
            </div>

            <div
              v-if="trainingRequest.accreditations?.length"
              class="training-overview-approvals"
            >
              <div class="training-approval-label">
                <i class="ri-shield-check-line"></i>
                <span>Approvals</span>
              </div>

              <div class="training-context-accreditations">
                <span
                  v-for="accreditation in trainingRequest.accreditations || []"
                  :key="accreditation.id"
                >
                  <i class="ri-check-line"></i>
                  {{ accreditation.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon primary">
            <i class="ri-bar-chart-box-line"></i>
          </div>

          <div>
            <span>{{ courseProgressLabel }}</span>
            <strong>{{ formatPercent(courseProgressPercent) }}%</strong>
          </div>
        </div>

        

        <div class="stat-card">
          <div class="stat-icon warning">
            <i class="ri-repeat-line"></i>
          </div>

          <div>
            <span>Attempts rest</span>
            <strong>{{ enrollment.attempts_rest || 0 }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon info">
            <i class="ri-file-list-3-line"></i>
          </div>

          <div>
            <span>Exam history</span>
            <strong>{{ enrollment.exams?.length || 0 }}</strong>
          </div>
        </div>
      </div>

      <div class="details-grid">
        <div class="info-card">
          <div class="section-header">
            <div class="section-icon">
              <i class="ri-information-line"></i>
            </div>

            <div>
              <h3>Enrollment information</h3>
              <p>Details about your course access.</p>
            </div>
          </div>

          <div class="info-list">
            <div class="info-row">
              <span>Status</span>
              <strong>{{ enrollment.status || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>Access type</span>
              <strong>{{ enrollment.access_type || '-' }}</strong>
            </div>

            <template v-if="trainingRequest">
              <div class="info-row">
                <span>Request reference</span>
                <strong>{{ trainingRequest.reference || '-' }}</strong>
              </div>

              <div class="info-row">
                <span>Training start</span>
                <strong>{{ formatTrainingDate(trainingRequest.start_date) }}</strong>
              </div>

              <div class="info-row">
                <span>Training end</span>
                <strong>{{ formatTrainingDate(trainingRequest.end_date) }}</strong>
              </div>

              <div class="info-row">
                <span>Accreditations</span>
                <strong>
                  {{ (trainingRequest.accreditations || []).map(item => item.name).join(' + ') || '-' }}
                </strong>
              </div>
            </template>

            <div class="info-row">
              <span>Started at</span>
              <strong>{{ formatDate(enrollment.started_at) }}</strong>
            </div>

            <div class="info-row">
              <span>Completed at</span>
              <strong>{{ formatDate(enrollment.completed_at) }}</strong>
            </div>

            <div class="info-row">
              <span>Validity duration</span>
              <strong>{{ validityLabel }}</strong>
            </div>

            <div class="info-row">
              <span>Valid until</span>
              <strong>{{ enrollment.expired_at ? formatDate(enrollment.expired_at) : 'No expiration' }}</strong>
            </div>

            <div class="info-row">
              <span>Validity status</span>
              <strong>{{ isExpired ? 'Expired' : enrollment.expired_at ? 'Valid' : 'Unlimited' }}</strong>
            </div>
          </div>
        </div>

        <div class="exam-card">
          <div class="section-header">
            <div class="section-icon">
              <i class="ri-file-list-3-line"></i>
            </div>

            <div>
              <h3>Exam attempts history</h3>
              <p>Review previous exam attempts for this enrollment.</p>
            </div>
          </div>

          <div v-if="enrollment.exams?.length" class="exam-list">
            <div
              v-for="exam in enrollment.exams"
              :key="exam.id"
              class="exam-item"
            >
              <div class="exam-main">
                <span :class="getExamStatusClass(exam.status)">
                  {{ getExamStatusLabel(exam.status) }}
                </span>

                <strong>
                  Score:
                  <template v-if="exam.score !== null && exam.score !== undefined">
                    {{ exam.score }}%
                  </template>
                  <template v-else>
                    -
                  </template>
                </strong>
              </div>

              <div class="exam-details">
                <span>
                  <i class="ri-question-line"></i>
                  Questions: {{ exam.total_questions || '-' }}
                </span>

                <span>
                  <i class="ri-checkbox-circle-line"></i>
                  Correct: {{ exam.correct_answers || '-' }}
                </span>

                <span>
                  <i class="ri-time-line"></i>
                  Started: {{ formatDate(exam.started_at || exam.created_at) }}
                </span>

                <span>
                  <i class="ri-flag-line"></i>
                  Completed: {{ formatDate(exam.submitted_at) }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="empty-exams">
            <div class="empty-icon">
              <i class="ri-file-list-3-line"></i>
            </div>

            <h4>No exam attempts yet</h4>

            <p>
              You have not started any exam attempt for this course yet.
            </p>
          </div>
        </div>
      </div>

      <div class="bottom-actions">
        <Link
          :href="route('learner-space.my-courses')"
          class="outline-btn"
        >
          <i class="ri-book-open-line"></i>
          My courses
        </Link>

        <button
          v-if="canStartExam"
          type="button"
          class="main-btn"
          @click="startExam"
        >
          <i :class="hasInProgressExam ? 'ri-play-circle-line' : 'ri-edit-circle-line'"></i>
          {{ examActionLabel }}
        </button>

        <button
          v-else-if="!isScormCourse && !hasPassedExam && !courseCompleted && hasAttemptsRemaining"
          type="button"
          class="main-btn course-required"
          @click="goBackToCourse"
        >
          <i class="ri-book-open-line"></i>
          Finish course before exam
        </button>

        <button
          v-else
          type="button"
          class="main-btn disabled"
          disabled
        >
          <i :class="hasPassedExam ? 'ri-checkbox-circle-line' : 'ri-lock-line'"></i>
          {{
            isExpired
              ? 'Training expired'
              : hasPassedExam
                ? 'Final exam already passed'
                : trainingUnavailable
                ? getTrainingStateLabel(trainingAccessState)
                : 'No exam attempts remaining'
          }}
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.course-finish-page {
  min-height: calc(100vh - 90px);
  --layout-footer-height: 62px;
  min-height: calc(100dvh - 90px);
  background: linear-gradient(180deg, #f6fbff 0%, #ffffff 48%, #f8fafc 100%);
  padding-bottom: calc(var(--layout-footer-height) + 42px);
}

/* HERO */
.finish-hero {
  position: relative;
  overflow: hidden;
  min-height: 360px;
  padding: 90px 8% 78px;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 52px;
}

.finish-hero::before,
.finish-hero::after {
  content: none;
  display: none;
}

.finish-hero-content {
  position: relative;
  z-index: 2;
  max-width: 720px;
}

.hero-badge {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #071126;
  background-color: rgba(255, 255, 255, 0.88);
  padding: 10px 16px;
  border-radius: 999px;
  font-weight: 800;
  margin-bottom: 22px;
}

.finish-hero h1 {
  color: #ffffff;
  font-size: 56px;
  line-height: 1.05;
  font-weight: 900;
  margin-bottom: 22px;
}

.finish-hero p {
  color: rgba(255, 255, 255, 0.95);
  font-size: 18px;
  line-height: 1.7;
  max-width: 650px;
  margin-bottom: 0;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 32px;
  flex-wrap: wrap;
}

.hero-primary-btn,
.hero-secondary-btn {
  height: 52px;
  padding-inline: 22px;
  border-radius: 14px;
  border: none;
  text-decoration: none;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  transition: all 0.2s ease;
}

.hero-primary-btn {
  background-color: #071126;
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(7, 17, 38, 0.18);
}

.hero-secondary-btn {
  background-color: rgba(255, 255, 255, 0.92);
  color: #071126;
}

.hero-primary-btn:hover,
.hero-secondary-btn:hover {
  transform: translateY(-2px);
}

.hero-primary-btn.disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.attempts-card {
  position: relative;
  z-index: 2;
  width: 360px;
  min-width: 330px;
  background-color: rgba(255, 255, 255, 0.94);
  color: #071126;
  border-radius: 28px;
  padding: 28px;
  box-shadow: 0 18px 40px rgba(7, 17, 38, 0.14);
  text-align: center;
}

.attempts-icon {
  width: 74px;
  height: 74px;
  margin: 0 auto 18px;
  border-radius: 24px;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
}

.attempts-card.exam-passed-card {
  border: 1px solid rgba(10, 179, 156, 0.22);
  background: rgba(255, 255, 255, 0.97);
}

.attempts-icon.passed {
  background: linear-gradient(135deg, #0ab39c 0%, #087f6f 100%);
  box-shadow: 0 14px 28px rgba(10, 179, 156, 0.24);
}

.attempts-card .exam-passed-label {
  color: #087f6f;
  font-size: 42px;
  line-height: 1.05;
  margin-bottom: 14px;
}

.exam-passed-mini-badge {
  width: fit-content;
  margin: 18px auto 0;
  padding: 8px 12px;
  border-radius: 999px;
  color: #087f6f;
  background: rgba(10, 179, 156, 0.12);
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.certificate-download-btn {
  width: 100%;
  height: 46px;
  margin-top: 14px;
  border: 1px solid rgba(10, 179, 156, 0.24);
  border-radius: 14px;
  color: #ffffff;
  background: linear-gradient(135deg, #0ab39c 0%, #087f6f 100%);
  box-shadow: 0 10px 22px rgba(10, 179, 156, 0.22);
  text-decoration: none;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.certificate-download-btn:hover {
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(10, 179, 156, 0.3);
}

.certificate-download-btn i {
  font-size: 18px;
}

.certificate-reference {
  margin-top: 9px;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.attempts-card > span {
  display: block;
  color: #64748b;
  font-size: 14px;
  font-weight: 900;
  margin-bottom: 8px;
}

.attempts-card strong {
  display: block;
  color: #071126;
  font-size: 58px;
  line-height: 1;
  font-weight: 900;
  margin-bottom: 14px;
}

.attempts-card p {
  color: #64748b;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 0;
}

/* CONTENT */
.course-required-alert {
  width: 100%;
  margin: 0 0 22px;
  position: relative;
  z-index: 1;
  border: 1px solid rgba(247, 184, 75, 0.35);
  border-radius: 18px;
  background: #fff9ed;
  color: #7c5208;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 12px 28px rgba(124, 82, 8, 0.08);
}

.course-required-alert-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: #f7b84b;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.course-required-alert h3 {
  color: #6b4708;
  font-size: 16px;
  font-weight: 900;
  margin: 0 0 4px;
}

.course-required-alert p {
  color: #8a641e;
  font-size: 13px;
  line-height: 1.5;
  margin: 0;
}

.course-required-alert button {
  height: 40px;
  margin-left: auto;
  padding-inline: 14px;
  border: none;
  border-radius: 11px;
  background: #071126;
  color: #ffffff;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.hero-primary-btn.course-required,
.main-btn.course-required {
  background: linear-gradient(135deg, #f7b84b 0%, #d38a13 100%);
  color: #ffffff;
}

.finish-container {
  width: min(1240px, calc(100% - 40px));
  margin: -42px auto 0;
  position: relative;
  z-index: 5;
  display: flex;
  flex-direction: column;
}

.course-summary-card {
  position: relative;
  z-index: 1;
  margin-bottom: 22px;
  padding: 18px;
  border: 1px solid #dfe9f1;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(7, 17, 38, 0.09);
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 22px;
  overflow: hidden;
}

.course-summary-card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto 0;
  height: 4px;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
}

.course-summary-media {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.course-cover {
  width: 100%;
  height: 205px;
  border-radius: 18px;
  overflow: hidden;
  background: #eef7fb;
  border: 1px solid #dfe9f1;
}

.course-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-fallback {
  width: 100%;
  height: 100%;
  color: #ffffff;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 44px;
  font-weight: 900;
}

.course-media-caption {
  margin-top: 10px;
  min-height: 38px;
  padding: 0 12px;
  border: 1px solid #dfe9f1;
  border-radius: 12px;
  background: #f7fafc;
  display: flex;
  align-items: center;
}

.course-media-caption span {
  color: #476172;
  font-size: 11px;
  font-weight: 850;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.course-media-caption i {
  color: #0d8fe9;
  font-size: 15px;
}

.course-summary-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.course-summary-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
}

.course-heading-block {
  min-width: 0;
  flex: 1;
}

.course-category {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 7px;
}

.course-category-name {
  color: #0d8fe9;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.course-info h2,
.course-summary-content h2 {
  margin: 0 0 7px;
  color: #071126;
  font-size: clamp(22px, 2vw, 29px);
  line-height: 1.2;
  font-weight: 900;
}

.course-info p,
.course-summary-content > .course-summary-header p {
  margin: 0;
  max-width: 760px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.65;
}

.course-summary-resource {
  width: 225px;
  min-width: 225px;
  min-height: 58px;
  padding: 9px 11px;
  border: 1px solid rgba(13, 143, 233, 0.2);
  border-radius: 15px;
  background: #f7fbff;
  color: #0f4869;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.course-summary-resource:hover {
  color: #0f4869;
  border-color: rgba(13, 143, 233, 0.42);
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.1);
  transform: translateY(-1px);
}

.resource-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 11px;
  background: #eaf7ff;
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
}

.resource-copy {
  min-width: 0;
  flex: 1;
}

.resource-copy small,
.resource-copy strong {
  display: block;
}

.resource-copy small {
  color: #708496;
  font-size: 9px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.resource-copy strong {
  margin-top: 2px;
  color: #163e57;
  font-size: 11px;
  font-weight: 900;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.resource-open-icon {
  color: #0d8fe9;
  font-size: 16px;
}

.final_exam_result {
  color: #64748b !important;
}

.course-meta {
  margin-top: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.course-meta span {
  min-height: 32px;
  padding: 0 10px;
  border: 1px solid #e3ebf1;
  border-radius: 999px;
  background: #fbfdff;
  color: #475569;
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.course-meta span i {
  color: #0d8fe9;
  font-size: 14px;
}

.training-overview {
  margin-top: auto;
  padding: 13px;
  border: 1px solid #dfe9f1;
  border-radius: 16px;
  background: #fbfdff;
}

.training-overview.upcoming {
  border-color: rgba(247, 184, 75, 0.35);
  background: #fffaf0;
}

.training-overview.ended {
  border-color: rgba(240, 101, 72, 0.28);
  background: #fff7f5;
}

.training-overview-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.training-overview-item {
  min-width: 0;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
}

.training-overview-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 11px;
  background: #eaf7ff;
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.training-overview-copy {
  min-width: 0;
}

.training-overview-copy small,
.training-overview-copy strong {
  display: block;
}

.training-overview-copy small {
  color: #64748b;
  font-size: 9.5px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.035em;
}

.training-overview-copy strong {
  margin-top: 2px;
  color: #1e3f54;
  font-size: 12px;
  font-weight: 900;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
}

.training-overview-copy strong i {
  color: #8ca0ae;
  font-size: 13px;
}

.training-state-badge {
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eaf7ff;
  color: #0d8fe9;
  font-size: 10px;
  font-weight: 900;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.training-state-badge i {
  font-size: 7px;
}

.training-overview-approvals {
  margin-top: 11px;
  padding-top: 11px;
  border-top: 1px solid #e3ebf1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.training-approval-label {
  color: #64748b;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.training-approval-label i {
  color: #0d8fe9;
  font-size: 15px;
}

.training-context-accreditations {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.training-context-accreditations span {
  min-height: 27px;
  padding: 0 9px;
  border: 1px solid rgba(10, 179, 156, 0.16);
  border-radius: 999px;
  background: rgba(10, 179, 156, 0.08);
  color: #087f6f;
  font-size: 10px;
  font-weight: 850;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.training-context-accreditations span i {
  font-size: 12px;
}

.course-resource-actions,
.syllabus-btn {
  display: none;
}

/* STATS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 22px;
}

.stat-card {
  background-color: #ffffff;
  border: 1px solid #e5edf5;
  border-radius: 22px;
  padding: 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.stat-icon {
  width: 54px;
  height: 54px;
  min-width: 54px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}

.stat-icon.primary {
  background-color: #eaf5fb;
  color: #0d8fe9;
}

.stat-icon.success {
  background-color: #e8f7f4;
  color: #0ab39c;
}

.stat-icon.warning {
  background-color: #fff7e6;
  color: #f7b84b;
}

.stat-icon.info {
  background-color: #e8f4ff;
  color: #0d8fe9;
}

.stat-card span {
  display: block;
  color: #64748b;
  font-size: 13px;
  font-weight: 900;
  margin-bottom: 5px;
}

.stat-card strong {
  color: #071126;
  font-size: 24px;
  line-height: 1;
}

/* DETAILS */
.details-grid {
  display: grid;
  grid-template-columns: 0.85fr 1.15fr;
  gap: 22px;
}

.info-card,
.exam-card {
  background-color: #ffffff;
  border: 1px solid #e5edf5;
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 18px 42px rgba(7, 17, 38, 0.09);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 22px;
}

.section-icon {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.section-header h3 {
  color: #071126;
  font-size: 22px;
  font-weight: 900;
  margin-bottom: 4px;
}

.section-header p {
  color: #64748b;
  margin-bottom: 0;
}

.info-list {
  display: grid;
  gap: 12px;
}

.info-row {
  border: 1px solid #e5edf5;
  border-radius: 16px;
  padding: 14px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.info-row span {
  color: #64748b;
  font-size: 13px;
  font-weight: 800;
}

.info-row strong {
  color: #071126;
  font-size: 13px;
  font-weight: 900;
  text-align: right;
}

/* EXAMS */
.exam-list {
  display: grid;
  gap: 12px;
}

.exam-item {
  border: 1px solid #e5edf5;
  border-radius: 18px;
  padding: 16px;
  background-color: #ffffff;
}

.exam-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 12px;
}

.exam-main strong {
  color: #071126;
  font-size: 14px;
  font-weight: 900;
}

.exam-status {
  width: fit-content;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
}

.exam-status.passed {
  background-color: #e8f7f4;
  color: #0ab39c;
}

.exam-status.failed {
  background-color: #fdecea;
  color: #f06548;
}

.exam-status.in-progress {
  background-color: #e8f4ff;
  color: #0d8fe9;
}

.exam-status.pending {
  background-color: #fff7e6;
  color: #f7b84b;
}

.exam-status.default {
  background-color: #f1f5f9;
  color: #64748b;
}

.exam-details {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.exam-details span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background-color: #f8fafc;
  color: #475569;
  border: 1px solid #e5edf5;
  border-radius: 999px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 800;
}

.exam-details i {
  color: #0d8fe9;
}

.empty-exams {
  padding: 46px 18px;
  text-align: center;
  background-color: #f8fafc;
  border-radius: 20px;
}

.empty-icon {
  width: 76px;
  height: 76px;
  margin: 0 auto 16px;
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(36, 87, 117, 0.12), rgba(13, 143, 233, 0.18));
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 38px;
}

.empty-exams h4 {
  color: #071126;
  font-weight: 900;
  margin-bottom: 8px;
}

.empty-exams p {
  color: #64748b;
  margin-bottom: 0;
}

/* BOTTOM */
.bottom-actions {
  margin-top: 22px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.outline-btn,
.main-btn {
  height: 48px;
  padding-inline: 20px;
  border-radius: 14px;
  text-decoration: none;
  border: none;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.outline-btn {
  background-color: #ffffff;
  border: 1px solid #e5edf5;
  color: #071126;
}

.main-btn {
  background-color: #071126;
  color: #ffffff;
}

.main-btn.disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* RESPONSIVE */
@media (max-width: 1199.98px) {
  .finish-hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .attempts-card {
    width: 100%;
    min-width: 100%;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .finish-hero {
    padding: 70px 24px 80px;
  }

  .finish-hero h1 {
    font-size: 42px;
  }

  .finish-container {
    width: calc(100% - 28px);
    margin-top: -28px;
  }

  .course-summary-card {
    flex-direction: column;
  }

  .course-cover {
    width: 100%;
    min-width: 100%;
    height: 210px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .course-required-alert {
    width: 100%;
    align-items: flex-start;
    flex-direction: column;
    margin-bottom: 16px;
  }

  .course-required-alert button {
    width: 100%;
    margin-left: 0;
    justify-content: center;
  }

  .bottom-actions {
    flex-direction: column;
  }

  .outline-btn,
  .main-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 575.98px) {
  .finish-container {
    margin-top: -20px;
  }

  .attempts-card .exam-passed-label {
    font-size: 36px;
  }

  .exam-passed-mini-badge {
    margin-top: 14px;
  }

  .certificate-download-btn {
    height: 44px;
    margin-top: 12px;
  }

  .exam-passed-alert,
  .course-required-alert {
    padding: 14px;
    border-radius: 16px;
  }

  .exam-passed-alert-icon,
  .course-required-alert-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
  }

  .finish-hero h1 {
    font-size: 36px;
  }

  .course-info h2 {
    font-size: 24px;
  }

  .info-card,
  .exam-card {
    padding: 18px;
    border-radius: 18px;
  }

  .section-header {
    align-items: flex-start;
  }

  .exam-main {
    flex-direction: column;
    align-items: flex-start;
  }
}


.certificate-section {
  width: 100%;
  margin-top: 16px;
  padding: 12px;
  border: 1px solid #dbe7ef;
  border-radius: 16px;
  background: #fbfdff;
  text-align: left;
}

.certificate-section-header {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.certificate-section-header span,
.certificate-section-header strong {
  display: block;
}

.certificate-section-header span {
  color: #64748b;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.certificate-section-header strong {
  margin-top: 2px;
  color: #163e57;
  font-size: 11px;
  font-weight: 900;
}

.certificate-section-header > i {
  color: #0d8fe9;
  font-size: 20px;
}

.certificate-list {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.certificate-unavailable {
  width: 100%;
  margin-top: 16px;
  padding: 10px 12px;
  border: 1px solid rgba(247, 184, 75, 0.32);
  border-radius: 13px;
  background: #fffaf0;
  color: #8a641e;
  font-size: 10px;
  font-weight: 800;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  text-align: left;
}

.certificate-unavailable i {
  margin-top: 1px;
  font-size: 15px;
}

.certificate-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px;
  border: 1px solid #dbe7ef;
  border-radius: 14px;
  background: #fbfdff;
  color: #071126;
  text-decoration: none;
  transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.certificate-item:hover {
  color: #071126;
  background: #eaf7ff;
  border-color: rgba(13, 143, 233, 0.34);
  transform: translateY(-1px);
}

.certificate-item-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0d8fe9 0%, #245775 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.certificate-item-content {
  min-width: 0;
  flex: 1;
}

.certificate-item-content span,
.certificate-item-content strong,
.certificate-item-content small {
  display: block;
}

.certificate-item-content span {
  color: #0d8fe9;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.06em;
}

.certificate-item-content strong {
  color: #071126;
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.certificate-item-content small {
  color: #64748b;
  font-size: 10px;
}

.certificate-item-content .certificate-approval-reference {
  margin-top: 3px;
  color: #0d8fe9;
  font-size: 9px;
  font-weight: 850;
}

.certificate-item-download {
  color: #0d8fe9;
  font-size: 18px;
}




@media (max-width: 1199.98px) {
  .course-summary-card {
    grid-template-columns: 230px minmax(0, 1fr);
  }

  .course-summary-header {
    flex-direction: column;
  }

  .course-summary-resource {
    width: 100%;
    min-width: 0;
  }
}

@media (max-width: 767.98px) {
  .course-summary-card {
    grid-template-columns: 1fr;
    padding: 15px;
    gap: 16px;
  }

  .course-cover {
    height: 220px;
  }

  .course-media-caption {
    display: none;
  }

  .course-summary-header {
    gap: 14px;
  }

  .training-overview-main,
  .training-overview-approvals {
    align-items: flex-start;
    flex-direction: column;
  }

  .training-state-badge {
    margin-left: 48px;
  }
}

@media (max-width: 575.98px) {
  .course-cover {
    height: 185px;
  }

  .course-summary-content h2 {
    font-size: 22px;
  }

  .course-summary-resource {
    min-height: 54px;
  }

  .course-meta span {
    min-height: 30px;
    font-size: 10.5px;
  }

  .training-overview {
    padding: 12px;
  }

  .training-state-badge {
    margin-left: 0;
  }
}


/* =========================================================
   HOME PAGE VISUAL HARMONIZATION
   ========================================================= */
.course-finish-page {
  --home-cyan: #37cced;
  --home-blue: #0d8fe9;
  --home-dark: #071126;
  --home-soft: #eaf7ff;
  --home-border: #e5edf5;
}

.finish-hero {
  min-height: 330px;
  padding-top: 72px;
  padding-bottom: 72px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.hero-badge {
  border: 1px solid rgba(255, 255, 255, 0.62);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.12);
}

.hero-primary-btn {
  background: var(--home-dark);
  box-shadow: 0 14px 28px rgba(7, 17, 38, 0.22);
}

.hero-primary-btn:hover {
  background: #132b4a;
}

.hero-secondary-btn {
  border: 1px solid rgba(255, 255, 255, 0.62);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 10px 22px rgba(7, 17, 38, 0.12);
}

.attempts-card {
  border: 1px solid rgba(255, 255, 255, 0.72);
  border-radius: 24px;
  box-shadow: 0 24px 58px rgba(7, 17, 38, 0.18);
}

.attempts-icon:not(.passed),
.section-icon,
.certificate-item-icon {
  background: linear-gradient(135deg, var(--home-cyan), var(--home-blue));
}

.finish-container {
  margin-top: -34px;
}

.course-summary-card,
.stat-card,
.info-card,
.exam-card {
  border-color: var(--home-border);
  box-shadow: 0 16px 38px rgba(7, 17, 38, 0.075);
}

.course-summary-card::before {
  background: linear-gradient(90deg, var(--home-cyan), var(--home-blue));
}

.course-summary-resource,
.course-meta span,
.training-overview,
.info-row,
.exam-item,
.exam-details span {
  border-color: var(--home-border);
}

.course-summary-resource:hover {
  border-color: rgba(13, 143, 233, 0.42);
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.11);
}

.course-category-name,
.course-meta span i,
.training-overview-icon,
.training-approval-label i,
.resource-open-icon,
.certificate-section-header > i,
.certificate-item-content span,
.certificate-item-download,
.exam-details i {
  color: var(--home-blue);
}

.resource-icon,
.training-overview-icon,
.stat-icon.primary,
.training-state-badge {
  background: var(--home-soft);
  color: var(--home-blue);
}

.training-overview-copy strong,
.resource-copy strong,
.certificate-section-header strong {
  color: #163e57;
}

.stat-card {
  min-height: 98px;
  padding: 18px;
  border-radius: 18px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 15px;
  font-size: 22px;
}

.outline-btn {
  border-color: #dfe7ef;
}

.main-btn,
.course-required-alert button {
  background: var(--home-dark);
}

.main-btn:not(.disabled):hover,
.course-required-alert button:hover {
  color: #ffffff;
  background: #132b4a;
  transform: translateY(-1px);
}

.certificate-section {
  background: #fbfdff;
}

.certificate-item {
  background: #ffffff;
}

.certificate-item:hover {
  background: var(--home-soft);
}

@media (max-width: 1199.98px) {
  .finish-hero {
    padding-top: 58px;
    padding-bottom: 70px;
  }
}

@media (max-width: 767.98px) {
  .course-finish-page {
    padding-bottom: calc(var(--layout-footer-height) + 26px);
  }

  .finish-hero {
    padding-top: 46px;
    padding-bottom: 64px;
  }

  .finish-container {
    margin-top: -24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .course-finish-page *,
  .course-finish-page *::before,
  .course-finish-page *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}


.certificate-validity {
  margin-top: 4px;
  color: #0d8fe9 !important;
  display: inline-flex !important;
  align-items: center;
  gap: 5px;
}

</style>
