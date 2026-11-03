<script setup>
import { Head, Link } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import i18n from '@/i18n'

defineOptions({
  layout: LayoutAnonyme,
})

defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
  recentCourses: {
    type: Array,
    default: () => [],
  },
})

const t = (...args) => i18n.global.t(...args)

const getCourseImage = (course) => {
  if (!course) return null
  if (course.image_url) return course.image_url
  if (course.image) return `/storage/${course.image}`
  return null
}

const getCourseInitials = (course) => {
  return String(course?.title || 'CO')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getStatusClass = (status) => {
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

const getStatusLabel = (status) => {
  switch (status) {
    case 'completed':
      return t('t-completed')
    case 'in_progress':
      return t('t-in-progress')
    case 'not_started':
      return t('t-not-started')
    case 'expired':
      return t('t-expired')
    default:
      return status || '-'
  }
}

const getStatusIcon = (status) => {
  switch (status) {
    case 'completed':
      return 'ri-checkbox-circle-line'
    case 'in_progress':
      return 'ri-loader-4-line'
    case 'not_started':
      return 'ri-time-line'
    case 'expired':
      return 'ri-error-warning-line'
    default:
      return 'ri-information-line'
  }
}

const hasNoExamAttempts = (item) => {
  return Number(item?.attempts_rest ?? 0) <= 0
}

const hasPassedExam = (item) => {
  return Boolean(item?.has_passed_exam)
}

const isScormCourse = (item) => {
  return item?.course?.content_type === 'scorm' || item?.content_type === 'scorm'
}

const isEnrollmentExpired = item => {
  return Boolean(
    item?.is_expired ||
    item?.expiration?.is_expired ||
    item?.training_request?.expiration?.is_expired
  )
}

const getTrainingAccessState = (item) => {
  if (isEnrollmentExpired(item)) return 'expired'

  return item?.training_request?.access_state || 'unrestricted'
}

const isTrainingUnavailable = (item) => {
  return Boolean(
    item?.training_request &&
      item.training_request.can_access_course === false,
  )
}

const formatTrainingDate = (value) => {
  if (!value) return '-'

  const locale = i18n.global.locale?.value || i18n.global.locale || 'en'
  return new Date(`${value}T00:00:00`).toLocaleDateString(locale === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const getTrainingPeriodLabel = (item) => {
  const request = item?.training_request

  if (!request) return null

  return `${formatTrainingDate(request.start_date)} – ${formatTrainingDate(request.end_date)}`
}

const getTrainingStateLabel = (item) => {
  const state = getTrainingAccessState(item)

  if (state === 'expired') return t('t-expired')
  if (state === 'upcoming') return t('t-upcoming')
  if (state === 'ended') return t('t-ended')
  if (state === 'active') return t('t-active')

  return null
}

const getTrainingStateIcon = (item) => {
  const state = getTrainingAccessState(item)

  if (state === 'expired') return 'ri-calendar-close-line'
  if (state === 'upcoming') return 'ri-calendar-schedule-line'
  if (state === 'ended') return 'ri-calendar-close-line'

  return 'ri-calendar-check-line'
}

const isCourseAccessLocked = (item) => {
  return (
    isEnrollmentExpired(item) ||
    Boolean(item?.course_access_locked) ||
    isTrainingUnavailable(item) ||
    hasNoExamAttempts(item) ||
    hasPassedExam(item)
  )
}

const getCourseDestination = (item) => {
  if (!item?.hashid) return '#'

  if (isCourseAccessLocked(item)) {
    return route('learner-space.course-finish', item.hashid)
  }

  return route('learner-space.course-viewer', item.hashid)
}

const getButtonLabel = (item) => {
  if (isEnrollmentExpired(item)) return t('t-view-expired-training')
  if (hasPassedExam(item)) return t('t-view-exam-result')
  if (getTrainingAccessState(item) === 'upcoming') return t('t-view-availability')
  if (getTrainingAccessState(item) === 'ended') return t('t-view-summary')
  if (hasNoExamAttempts(item)) return t('t-view-course-summary')
  if (isScormCourse(item)) return t('t-open-course')
  if (item?.status === 'completed') return t('t-review-course')
  if (item?.status === 'not_started') return t('t-start-course')

  return t('t-continue-course')
}

const getButtonIcon = (item) => {
  if (isEnrollmentExpired(item)) return 'ri-calendar-close-line'
  if (hasPassedExam(item)) return 'ri-award-line'
  if (isTrainingUnavailable(item)) return getTrainingStateIcon(item)
  if (hasNoExamAttempts(item)) return 'ri-file-list-3-line'
  if (isScormCourse(item)) return 'ri-file-zip-line'
  if (item?.status === 'not_started') return 'ri-play-circle-line'

  return 'ri-arrow-right-line'
}

const formatPercent = (value) => {
  const percent = Number(value || 0)

  if (Number.isNaN(percent)) return 0

  return Math.min(Math.max(percent, 0), 100).toFixed(0)
}

const clampProgressPercent = (value) => {
  const percent = Number(value)

  if (!Number.isFinite(percent)) return 0

  return Math.min(100, Math.max(0, percent))
}

const progressMeasureToPercent = (value) => {
  const measure = Number(value)

  if (!Number.isFinite(measure)) return null

  return clampProgressPercent(
    measure <= 1 ? measure * 100 : measure,
  )
}

const hasCompletedScormStatus = (item) => {
  const tracking = item?.scorm_tracking || {}

  return [
    tracking.lesson_status,
    tracking.completion_status,
    tracking.success_status,
  ]
    .map((value) => String(value || '').trim().toLowerCase())
    .some((value) => ['completed', 'passed'].includes(value))
}

const getCourseProgress = (item) => {
  if (!item) return 0
  if (hasPassedExam(item)) return 100

  if (isScormCourse(item)) {
    if (
      Boolean(item.course_completed) ||
      Boolean(item.scorm_tracking?.is_completed) ||
      hasCompletedScormStatus(item)
    ) {
      return 100
    }

    const percentCandidates = [
      item.scorm_progress_percent,
      item.scorm_tracking?.progress_percent,
    ].map(clampProgressPercent)

    const measureCandidates = [
      item.scorm_tracking?.progress_measure,
      item.scorm_tracking?.cmi_data?.['cmi.progress_measure'],
      item.scorm_tracking?.cmi_data?.['cmi.core.progress_measure'],
    ]
      .map(progressMeasureToPercent)
      .filter((value) => value !== null)

    return Math.round(Math.max(
      clampProgressPercent(item.progress_percent),
      ...percentCandidates,
      ...measureCandidates,
    ))
  }

  if (
    Boolean(item.course_completed) ||
    item.status === 'completed'
  ) {
    return 100
  }

  return Math.round(
    clampProgressPercent(item.progress_percent),
  )
}

const formatDuration = (value) => {
  if (value === null || value === undefined || value === '') return null

  const hours = Number(value)

  if (Number.isNaN(hours)) return null

  const formatted = Number.isInteger(hours)
    ? hours.toFixed(0)
    : hours.toFixed(2).replace(/\.?0+$/, '')

  return t(hours === 1 ? 't-hour-singular' : 't-hour-plural', { count: formatted })
}

const formatPassingScore = (value) => {
  if (value === null || value === undefined || value === '') return null

  const score = Number(value)

  if (Number.isNaN(score)) return null

  return `${Math.min(Math.max(score, 0), 100).toFixed(0)}%`
}

const getSyllabusUrl = (course) => {
  if (!course) return null
  if (course.syllabus_url) return course.syllabus_url
  if (course.syllabus) return `/storage/${course.syllabus}`

  return null
}

</script>

<template>
  <Head :title="$t('t-learner-dashboard')" />

  <section class="learner-dashboard-page">
    <div class="dashboard-hero">
      <div class="dashboard-shell hero-grid">
        <div class="dashboard-hero-content">
          <h1>{{ $t('t-training-pickup-headline') }}</h1>

          <p>
            {{ $t('t-training-pickup-description') }}
          </p>

          <div class="hero-actions">
            <Link
              :href="route('learner-space.my-courses')"
              class="hero-primary-btn"
            >
              <i class="ri-book-open-line"></i>
              {{ $t('t-open-my-courses') }}
            </Link>

            <span class="hero-course-count">
              <i class="ri-stack-line"></i>
              {{ $t('t-assigned-courses-count', stats.total_courses || 0) }}
            </span>
          </div>
        </div>

        <div class="hero-overview-card">
          <div class="overview-card-top">
            <div>
              <span class="overview-kicker">{{ $t('t-learning-overview') }}</span>
              <strong>{{ stats.total_courses || 0 }}</strong>
              <small>{{ $t('t-total-assigned-courses') }}</small>
            </div>

            <div class="overview-main-icon">
              <i class="ri-line-chart-line"></i>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="dashboard-shell dashboard-content">
      <div class="stats-grid">
        <article class="stat-card">
          <div class="stat-icon warning">
            <i class="ri-time-line"></i>
          </div>

          <div class="stat-content">
            <span>{{ $t('t-not-started') }}</span>
            <strong>{{ stats.not_started || 0 }}</strong>
          </div>

          <i class="ri-arrow-right-up-line stat-arrow"></i>
        </article>

        <article class="stat-card">
          <div class="stat-icon info">
            <i class="ri-loader-4-line"></i>
          </div>

          <div class="stat-content">
            <span>{{ $t('t-in-progress') }}</span>
            <strong>{{ stats.in_progress || 0 }}</strong>
          </div>

          <i class="ri-arrow-right-up-line stat-arrow"></i>
        </article>

        <article class="stat-card">
          <div class="stat-icon success">
            <i class="ri-checkbox-circle-line"></i>
          </div>

          <div class="stat-content">
            <span>{{ $t('t-completed') }}</span>
            <strong>{{ stats.completed || 0 }}</strong>
          </div>

          <i class="ri-arrow-right-up-line stat-arrow"></i>
        </article>

        <article class="stat-card">
          <div class="stat-icon danger">
            <i class="ri-calendar-close-line"></i>
          </div>

          <div class="stat-content">
            <span>{{ $t('t-expired') }}</span>
            <strong>{{ stats.expired || 0 }}</strong>
          </div>

          <i class="ri-arrow-right-up-line stat-arrow"></i>
        </article>
      </div>

      <section class="recent-section-card">
        <div class="recent-header">
          <div class="recent-header-title">
            <div class="recent-header-icon">
              <i class="ri-history-line"></i>
            </div>

            <div>
              <span class="section-eyebrow">{{ $t('t-your-training') }}</span>
              <h2>{{ $t('t-continue-a-course') }}</h2>
              <p>{{ $t('t-pick-up-latest-courses') }}</p>
            </div>
          </div>

          <Link
            :href="route('learner-space.my-courses')"
            class="recent-all-btn"
          >
            {{ $t('t-view-all-courses') }}
            <i class="ri-arrow-right-line"></i>
          </Link>
        </div>

        <div v-if="recentCourses.length" class="recent-courses-list">
          <article
            v-for="(item, index) in recentCourses"
            :key="item.id"
            class="recent-course-card"
            :class="{ 'featured-course-card': index === 0 }"
          >
            <div class="recent-course-cover">
              <img
                v-if="getCourseImage(item.course)"
                :src="getCourseImage(item.course)"
                :alt="item.course?.title || 'Course image'"
              />

              <div v-else class="recent-course-fallback">
                {{ getCourseInitials(item.course) }}
              </div>

              <div class="cover-status">
                <i :class="getStatusIcon(item.status)"></i>
                {{ getStatusLabel(item.status) }}
              </div>
            </div>

            <div class="recent-course-main">
              <div class="course-title-row">
                <div class="course-title-copy">
                  <span class="course-category">
                    <i class="ri-folder-line"></i>
                    {{ item.course?.category?.name || $t('t-course') }}
                  </span>

                  <h3>{{ item.course?.title || '-' }}</h3>
                </div>

                <span v-if="isScormCourse(item)" class="content-type-badge">
                  <i class="ri-file-zip-line"></i>
                  {{ $t('ui-scorm') }}
                </span>
              </div>

              <div class="course-meta-grid">
                <span>
                  <i class="ri-global-line"></i>
                  {{ item.course?.language || $t('t-not-specified') }}
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
                  {{ $t('ui-pass') }} {{ formatPassingScore(item.course.passing_score) }}
                </span>
              </div>

              <div
                v-if="item.training_request"
                class="training-context"
                :class="getTrainingAccessState(item)"
              >
                <div class="training-period">
                  <i :class="getTrainingStateIcon(item)"></i>

                  <div>
                    <span>{{ $t('t-training-period') }}</span>
                    <strong>{{ getTrainingPeriodLabel(item) }}</strong>
                  </div>

                  <span class="training-state">
                    {{ getTrainingStateLabel(item) }}
                  </span>
                </div>

                <div
                  v-if="item.training_request.accreditations?.length"
                  class="training-accreditations"
                >
                  <span
                    v-for="accreditation in item.training_request.accreditations"
                    :key="accreditation.id"
                  >
                    <i class="ri-shield-check-line"></i>
                    {{ accreditation.name }}
                  </span>
                </div>
              </div>

              <div class="course-bottom-tags">
                <span>
                  <i class="ri-repeat-line"></i>
                  {{ $t('t-attempts-left', item.attempts_rest ?? 0) }}
                </span>

                <span v-if="item.expired_at || item.duree_validite_formation">
                  <i class="ri-calendar-check-line"></i>
                  {{ item.expired_at
                    ? $t('t-valid-until', { date: formatTrainingDate(String(item.expired_at).slice(0, 10)) })
                    : $t('t-validity-months', item.duree_validite_formation) }}
                </span>

                <a
                  v-if="getSyllabusUrl(item.course)"
                  :href="getSyllabusUrl(item.course)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="syllabus-link"
                >
                  <i class="ri-file-pdf-2-line"></i>
                  {{ $t('ui-syllabus') }}
                </a>
              </div>
            </div>

            <aside class="recent-course-actions">
              <div class="action-status-row">
                <span :class="getStatusClass(item.status)">
                  <i :class="getStatusIcon(item.status)"></i>
                  {{ getStatusLabel(item.status) }}
                </span>

                <strong>{{ getCourseProgress(item) }}%</strong>
              </div>

              <div class="course-progress-block">
                <div class="course-progress-track">
                  <div
                    class="course-progress-bar"
                    :style="{ width: `${getCourseProgress(item)}%` }"
                  ></div>
                </div>

                <small>
                  {{ isScormCourse(item) ? $t('t-scorm-completion') : $t('t-course-progress') }}
                </small>
              </div>

              <Link
                v-if="item.hashid"
                :href="getCourseDestination(item)"
                class="continue-btn"
                :class="{
                  'finish-destination': isCourseAccessLocked(item),
                  'passed-destination': hasPassedExam(item),
                }"
              >
                <span>
                  <i :class="getButtonIcon(item)"></i>
                  {{ getButtonLabel(item) }}
                </span>

                <i class="ri-arrow-right-line"></i>
              </Link>

              <button
                v-else
                type="button"
                class="continue-btn disabled"
                disabled
              >
                <span>
                  <i class="ri-error-warning-line"></i>
                  {{ $t('t-enrollment-unavailable') }}
                </span>
              </button>
            </aside>
          </article>
        </div>

        <div v-else class="empty-courses">
          <div class="empty-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <h3>{{ $t('t-no-assigned-courses') }}</h3>
          <p>{{ $t('t-assigned-courses-empty-description') }}</p>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.learner-dashboard-page {
  --layout-header-height: 82px;
  --layout-footer-height: 62px;
  --home-cyan: #37cced;
  --home-blue: #0d8fe9;
  --home-blue-dark: #087bc9;
  --home-navy: #071126;
  --home-soft: #eaf7ff;
  --home-border: #e4edf4;

  min-height: calc(
    100dvh -
    var(--layout-header-height) -
    var(--layout-footer-height)
  );
  padding-bottom: calc(var(--layout-footer-height) + 36px);
  background: linear-gradient(
    180deg,
    #f4fbff 0%,
    #ffffff 44%,
    #f8fafc 100%
  );
}

.dashboard-shell {
  width: min(1360px, calc(100% - 48px));
  margin-inline: auto;
}

/* HERO */
.dashboard-hero {
  position: relative;
  overflow: hidden;
  padding: 58px 0 82px;
  color: #ffffff;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
}

.dashboard-hero::before,
.dashboard-hero::after {
  content: none;
  display: none;
}

.hero-grid {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(340px, 0.75fr);
  align-items: center;
  gap: 70px;
}

.dashboard-hero-content {
  max-width: 760px;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  padding: 7px 12px;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.55);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.88);
  color: #071126;
  font-size: 12px;
  font-weight: 800;
  backdrop-filter: blur(10px);
}

.dashboard-hero h1 {
  max-width: 720px;
  margin: 0 0 18px;
  color: #ffffff;
  font-size: clamp(36px, 4.6vw, 62px);
  line-height: 1.05;
  font-weight: 900;
  letter-spacing: -0.035em;
}

.dashboard-hero p {
  max-width: 650px;
  margin: 0;
  color: rgba(255, 255, 255, 0.95);
  font-size: 17px;
  line-height: 1.7;
}

.hero-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 28px;
}

.hero-primary-btn {
  min-height: 48px;
  padding: 12px 20px;
  border-radius: 14px;
  background: #071126;
  color: #ffffff;
  text-decoration: none;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  box-shadow: 0 16px 34px rgba(4, 13, 31, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hero-primary-btn:hover {
  color: #ffffff;
  background: #102044;
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(4, 13, 31, 0.25);
}

.hero-course-count {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: rgba(255, 255, 255, 0.96);
  font-size: 13px;
  font-weight: 700;
}

/* HERO OVERVIEW */
.hero-overview-card {
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 24px;
  padding: 22px;
  background: rgba(255, 255, 255, 0.94);
  color: #071126;
  box-shadow: 0 22px 48px rgba(7, 17, 38, 0.15);
  backdrop-filter: blur(14px);
}

.overview-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid #e8eef5;
}

.overview-kicker,
.overview-card-top small {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.overview-card-top strong {
  display: block;
  margin: 6px 0 4px;
  color: #071126;
  font-size: 38px;
  line-height: 1;
  font-weight: 950;
}

.overview-main-icon {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 24px;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
}

.overview-list {
  display: grid;
  gap: 15px;
  padding-top: 18px;
}

.overview-row-heading {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
}

.overview-row-heading span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.overview-row-heading strong {
  color: #071126;
}

.overview-row-heading i.warning {
  color: #f7b84b;
}

.overview-row-heading i.info {
  color: #0d8fe9;
}

.overview-row-heading i.success {
  color: #0ab39c;
}

.overview-row-heading i.danger {
  color: #f06548;
}

.overview-track {
  height: 7px;
  border-radius: 999px;
  overflow: hidden;
  background: #edf2f7;
}

.overview-bar {
  height: 100%;
  border-radius: inherit;
}

.overview-bar.warning {
  background: #f7b84b;
}

.overview-bar.info {
  background: #0d8fe9;
}

.overview-bar.success {
  background: #0ab39c;
}

.overview-bar.danger {
  background: #f06548;
}

/* CONTENT */
.dashboard-content {
  position: relative;
  z-index: 5;
  margin-top: -38px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  min-height: 104px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #e7edf4;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.stat-icon {
  width: 50px;
  height: 50px;
  min-width: 50px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 23px;
}

.stat-icon.primary {
  background: #eaf7ff;
  color: #087bc9;
}

.stat-icon.warning {
  background: #fff7e6;
  color: #f7b84b;
}

.stat-icon.info {
  background: #e8f4ff;
  color: #0d8fe9;
}

.stat-icon.success {
  background: #e8f7f4;
  color: #0ab39c;
}

.stat-icon.danger {
  background: #fff0ed;
  color: #f06548;
}

.stat-content {
  min-width: 0;
  flex: 1;
}

.stat-content span {
  display: block;
  margin-bottom: 5px;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
}

.stat-content strong {
  color: #071126;
  font-size: 27px;
  line-height: 1;
  font-weight: 950;
}

.stat-arrow {
  color: #cbd5e1;
  font-size: 19px;
}

/* RECENT SECTION */
.recent-section-card {
  padding: 24px;
  border: 1px solid #e7edf4;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 18px 44px rgba(15, 23, 42, 0.07);
}

.recent-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 20px;
}

.recent-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
}

.recent-header-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 15px;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.section-eyebrow {
  display: block;
  margin-bottom: 2px;
  color: #0d8fe9;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.recent-header h2 {
  margin: 0 0 3px;
  color: #071126;
  font-size: 22px;
  font-weight: 900;
}

.recent-header p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.recent-all-btn {
  min-height: 42px;
  padding: 10px 16px;
  border: 1px solid #dce5ee;
  border-radius: 13px;
  background: #ffffff;
  color: #071126;
  text-decoration: none;
  font-size: 13px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.recent-all-btn:hover {
  color: #ffffff;
  border-color: #071126;
  background: #071126;
}

.recent-courses-list {
  display: grid;
  gap: 14px;
}

.recent-course-card {
  display: grid;
  grid-template-columns: 170px minmax(0, 1fr) 250px;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid #e5edf5;
  border-radius: 20px;
  background: #fbfdff;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.recent-course-card:hover {
  transform: translateY(-2px);
  border-color: rgba(13, 143, 233, 0.26);
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
}

.recent-course-cover {
  position: relative;
  min-height: 210px;
  overflow: hidden;
  background: #eaf1f7;
}

.recent-course-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.recent-course-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #37cced, #0d8fe9);
  color: #ffffff;
  font-size: 30px;
  font-weight: 950;
}

.cover-status {
  position: absolute;
  left: 12px;
  bottom: 12px;
  display: none;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border-radius: 999px;
  background: rgba(7, 17, 38, 0.82);
  color: #ffffff;
  font-size: 10px;
  font-weight: 850;
  backdrop-filter: blur(8px);
}

.recent-course-main {
  min-width: 0;
  padding: 18px;
}

.course-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.course-title-copy {
  min-width: 0;
}

.course-category {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 5px;
  color: #0d8fe9;
  font-size: 11px;
  font-weight: 850;
}

.recent-course-main h3 {
  margin: 0;
  color: #071126;
  font-size: 19px;
  line-height: 1.25;
  font-weight: 900;
}

.content-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 9px;
  border-radius: 999px;
  background: #eaf7ff;
  color: #087bc9;
  font-size: 9px;
  font-weight: 900;
}

.course-meta-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 13px;
}

.course-meta-grid > span,
.course-bottom-tags > span,
.syllabus-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-height: 28px;
  padding: 5px 8px;
  border: 1px solid #e6edf4;
  border-radius: 999px;
  background: #ffffff;
  color: #526174;
  font-size: 10.5px;
  font-weight: 750;
}

.course-meta-grid i,
.course-bottom-tags i {
  color: #0d8fe9;
}

.training-context {
  margin-top: 13px;
  padding: 11px;
  border: 1px solid rgba(10, 179, 156, 0.2);
  border-radius: 14px;
  background: rgba(10, 179, 156, 0.055);
}

.training-context.upcoming {
  border-color: rgba(247, 184, 75, 0.34);
  background: rgba(247, 184, 75, 0.07);
}

.training-context.ended,
.training-context.expired {
  border-color: rgba(240, 101, 72, 0.28);
  background: rgba(240, 101, 72, 0.06);
}

.training-period {
  display: flex;
  align-items: center;
  gap: 9px;
}

.training-period > i {
  color: #0d8fe9;
  font-size: 18px;
}

.training-period > div {
  min-width: 0;
  flex: 1;
}

.training-period span,
.training-period strong {
  display: block;
}

.training-period > div span {
  color: #64748b;
  font-size: 9px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.training-period > div strong {
  margin-top: 1px;
  color: #334155;
  font-size: 11px;
  font-weight: 900;
}

.training-state {
  padding: 5px 8px;
  border-radius: 999px;
  background: #ffffff;
  color: #087bc9;
  font-size: 9px;
  font-weight: 900;
  white-space: nowrap;
}

.training-accreditations {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 9px;
}

.training-accreditations span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 8px;
  border-radius: 999px;
  background: rgba(10, 179, 156, 0.12);
  color: #087f6f;
  font-size: 9px;
  font-weight: 850;
}

.course-bottom-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 11px;
}

.syllabus-link {
  text-decoration: none;
  border-color: rgba(240, 101, 72, 0.2);
  background: rgba(240, 101, 72, 0.06);
  color: #d94f34;
}

.syllabus-link i {
  color: #f06548;
}

.syllabus-link:hover {
  color: #ffffff;
  border-color: #f06548;
  background: #f06548;
}

.syllabus-link:hover i {
  color: #ffffff;
}

.recent-course-actions {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 14px;
  padding: 18px;
  border-left: 1px solid #e7edf4;
  background: #ffffff;
}

.action-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.action-status-row > strong {
  color: #071126;
  font-size: 22px;
  font-weight: 950;
}

.course-status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  width: fit-content;
  padding: 6px 9px;
  border-radius: 999px;
  font-size: 9.5px;
  font-weight: 900;
}

.course-status.completed {
  background: #e8f7f4;
  color: #0a8f7e;
}

.course-status.in-progress {
  background: #e8f4ff;
  color: #0d8fe9;
}

.course-status.not-started {
  background: #fff7e6;
  color: #d99a20;
}

.course-status.expired {
  background: #fdecea;
  color: #f06548;
}

.course-status.default {
  background: #f1f5f9;
  color: #64748b;
}

.course-progress-block small {
  display: block;
  margin-top: 6px;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 750;
}

.course-progress-track {
  height: 7px;
  overflow: hidden;
  border-radius: 999px;
  background: #edf2f7;
}

.course-progress-bar {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
}

.continue-btn {
  min-height: 44px;
  padding: 10px 13px;
  border: none;
  border-radius: 13px;
  background: #071126;
  color: #ffffff;
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  transition: all 0.2s ease;
}

.continue-btn > span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.continue-btn:hover {
  color: #ffffff;
  transform: translateY(-1px);
  background: #0d8fe9;
}

.continue-btn.finish-destination {
  background: linear-gradient(135deg, #087bc9, #0d8fe9);
}

.continue-btn.passed-destination {
  background: linear-gradient(135deg, #0ab39c, #087f6f);
}

.continue-btn.disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* EMPTY */
.empty-courses {
  padding: 64px 24px;
  text-align: center;
}

.empty-icon {
  width: 78px;
  height: 78px;
  margin: 0 auto 16px;
  border-radius: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #0d8fe9;
  font-size: 36px;
  background: linear-gradient(135deg, rgba(155, 220, 235, 0.34), rgba(13, 143, 233, 0.14));
}

.empty-courses h3 {
  margin-bottom: 7px;
  color: #071126;
  font-weight: 900;
}

.empty-courses p {
  margin: 0;
  color: #64748b;
}

/* RESPONSIVE */
@media (max-width: 1199.98px) {
  .hero-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .dashboard-hero-content {
    max-width: 820px;
  }

  .hero-overview-card {
    max-width: 720px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .recent-course-card {
    grid-template-columns: 150px minmax(0, 1fr) 220px;
  }
}

@media (max-width: 991.98px) {
  .dashboard-hero {
    padding: 48px 0 72px;
  }

  .dashboard-shell {
    width: min(100% - 34px, 900px);
  }

  .recent-course-card {
    grid-template-columns: 150px minmax(0, 1fr);
  }

  .recent-course-actions {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: auto minmax(180px, 1fr) minmax(190px, 240px);
    align-items: center;
    border-top: 1px solid #e7edf4;
    border-left: none;
  }

  .course-progress-block small {
    display: none;
  }
}

@media (max-width: 767.98px) {
  .learner-dashboard-page {
    padding-bottom: 64px;
  }

  .dashboard-hero h1 {
    font-size: clamp(34px, 10vw, 46px);
  }

  .dashboard-hero p {
    font-size: 15px;
  }

  .dashboard-content {
    margin-top: -28px;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .stat-card {
    min-height: 92px;
    padding: 14px;
  }

  .stat-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 14px;
    font-size: 20px;
  }

  .stat-arrow {
    display: none;
  }

  .recent-section-card {
    padding: 18px;
    border-radius: 20px;
  }

  .recent-header {
    align-items: flex-start;
  }

  .recent-course-card {
    grid-template-columns: 1fr;
  }

  .recent-course-cover {
    min-height: 210px;
  }

  .cover-status {
    display: inline-flex;
  }

  .recent-course-actions {
    grid-column: auto;
    grid-template-columns: 1fr;
  }

  .action-status-row .course-status {
    display: none;
  }
}

@media (max-width: 575.98px) {
  .dashboard-shell {
    width: calc(100% - 24px);
  }

  .dashboard-hero {
    padding: 38px 0 58px;
  }

  .hero-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .hero-primary-btn {
    justify-content: center;
  }

  .hero-course-count {
    justify-content: center;
  }

  .hero-overview-card {
    padding: 18px;
    border-radius: 20px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .recent-header {
    flex-direction: column;
  }

  .recent-all-btn {
    width: 100%;
    justify-content: center;
  }

  .recent-header-title {
    align-items: flex-start;
  }

  .recent-course-cover {
    min-height: 190px;
  }

  .course-title-row {
    flex-direction: column;
  }

  .training-period {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .training-state {
    margin-left: 27px;
  }
}


/* =========================================================
   HOME PAGE VISUAL SYSTEM
   Content and Vue logic remain unchanged.
   ========================================================= */

.dashboard-hero {
  padding: 62px 0 86px;
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.18);
}

.dashboard-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  display: block;
  pointer-events: none;
  background: linear-gradient(
    110deg,
    rgba(255, 255, 255, 0.15) 0%,
    rgba(255, 255, 255, 0) 42%
  );
}

.dashboard-hero::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  display: block;
  height: 1px;
  background: rgba(255, 255, 255, 0.24);
}

.hero-eyebrow {
  border-color: rgba(255, 255, 255, 0.62);
  background: rgba(255, 255, 255, 0.94);
  color: var(--home-navy);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.1);
}

.dashboard-hero h1 {
  text-shadow: 0 8px 24px rgba(7, 17, 38, 0.08);
}

.hero-primary-btn {
  background: var(--home-navy);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
}

.hero-primary-btn:hover {
  background: #132b4a;
}

.hero-overview-card {
  border-color: rgba(255, 255, 255, 0.7);
  border-radius: 26px;
  background: rgba(255, 255, 255, 0.97);
  box-shadow: 0 26px 58px rgba(7, 17, 38, 0.18);
}

.overview-main-icon,
.recent-header-icon {
  background: linear-gradient(
    135deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.2);
}

.dashboard-content {
  margin-top: -42px;
}

.stat-card,
.recent-section-card {
  border-color: var(--home-border);
  background: #ffffff;
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.075);
}

.stat-card {
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 18px;
  left: 18px;
  height: 3px;
  border-radius: 0 0 999px 999px;
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  opacity: 0;
  transition: opacity 0.2s ease;
}

.stat-card:hover::before {
  opacity: 1;
}

.stat-card:hover {
  border-color: rgba(13, 143, 233, 0.24);
  box-shadow: 0 20px 42px rgba(15, 23, 42, 0.1);
  transform: translateY(-2px);
}

.stat-card {
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.stat-icon.primary,
.stat-icon.info {
  background: var(--home-soft);
  color: var(--home-blue-dark);
}

.section-eyebrow,
.course-category,
.course-meta-grid i,
.course-bottom-tags i {
  color: var(--home-blue-dark);
}

.recent-all-btn:hover {
  border-color: var(--home-navy);
  background: var(--home-navy);
}

.recent-course-card {
  grid-template-columns: 190px minmax(0, 1fr) 250px;
  border-color: var(--home-border);
  border-radius: 22px;
  background: #ffffff;
}

.recent-course-card:hover {
  border-color: rgba(13, 143, 233, 0.3);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
}

.recent-course-cover {
  min-height: 218px;
  background: #eaf4fa;
}

.recent-course-cover::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    rgba(7, 17, 38, 0.02) 48%,
    rgba(7, 17, 38, 0.2) 100%
  );
}

.recent-course-fallback {
  background: linear-gradient(
    135deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
}

.content-type-badge {
  border: 1px solid rgba(13, 143, 233, 0.16);
  background: var(--home-soft);
  color: var(--home-blue-dark);
}

.course-meta-grid > span,
.course-bottom-tags > span,
.syllabus-link {
  border-color: #e3edf4;
  background: #f9fbfd;
}

.training-context {
  border-color: rgba(13, 143, 233, 0.2);
  background: rgba(13, 143, 233, 0.055);
}

.training-period > i {
  color: var(--home-blue);
}

.training-state {
  border: 1px solid rgba(13, 143, 233, 0.14);
  background: #ffffff;
  color: var(--home-blue-dark);
}

.recent-course-actions {
  border-left-color: var(--home-border);
  background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.course-progress-bar {
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
}

.continue-btn {
  background: var(--home-navy);
  box-shadow: 0 10px 22px rgba(7, 17, 38, 0.15);
}

.continue-btn:hover {
  background: #132b4a;
}

.continue-btn.finish-destination {
  background: linear-gradient(
    90deg,
    var(--home-cyan) 0%,
    var(--home-blue) 100%
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.2);
}

.continue-btn.passed-destination {
  background: linear-gradient(135deg, #0ab39c, #087f6f);
}

.empty-icon {
  background: var(--home-soft);
  color: var(--home-blue-dark);
}

@media (max-width: 1199.98px) {
  .recent-course-card {
    grid-template-columns: 170px minmax(0, 1fr) 230px;
  }
}

@media (max-width: 991.98px) {
  .dashboard-hero {
    padding: 50px 0 76px;
  }

  .recent-course-card {
    grid-template-columns: 165px minmax(0, 1fr);
  }

  .recent-course-actions {
    border-top-color: var(--home-border);
  }
}

@media (max-width: 767.98px) {
  .learner-dashboard-page {
    padding-bottom: calc(var(--layout-footer-height) + 24px);
  }

  .dashboard-content {
    margin-top: -30px;
  }

  .recent-course-card {
    grid-template-columns: 1fr;
  }

  .recent-course-cover {
    min-height: 220px;
  }
}

@media (max-width: 575.98px) {
  .dashboard-hero {
    padding: 40px 0 62px;
  }

  .hero-overview-card,
  .recent-section-card {
    border-radius: 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-primary-btn,
  .stat-card,
  .recent-course-card,
  .continue-btn,
  .recent-all-btn {
    transition: none !important;
  }

  .hero-primary-btn:hover,
  .stat-card:hover,
  .recent-course-card:hover,
  .continue-btn:hover {
    transform: none !important;
  }
}

/* Use the shared homepage palette throughout the learner dashboard. */
.learner-dashboard-page {
  --home-cyan: var(--jet);
  --home-blue: var(--signal);
  --home-blue-dark: var(--signal-deep);
  --home-navy: var(--ink);
  --home-soft: #eafaff;
  --home-border: var(--sand);
  background: var(--paper);
}

.dashboard-hero {
  padding: 112px 0 92px;
  background: radial-gradient(ellipse at 73% 42%, rgba(56, 225, 255, 0.13), transparent 34%), var(--ink);
}

.dashboard-hero::before {
  content: none;
}

.dashboard-hero::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 3px;
  display: block;
  background: var(--jet);
}

.hero-grid {
  gap: clamp(32px, 6vw, 88px);
}

.dashboard-hero h1 {
  max-width: 640px;
  font-size: clamp(38px, 5vw, 68px);
  font-weight: 760;
  letter-spacing: -0.045em;
}

.dashboard-hero p {
  max-width: 510px;
  color: #dce3ee;
}

.hero-primary-btn {
  border-radius: 8px;
  background: var(--jet);
  color: var(--ink);
  box-shadow: none;
}

.hero-primary-btn:hover {
  background: #74ebff;
  color: var(--ink);
  box-shadow: none;
}

.hero-course-count {
  color: #dce3ee;
}

.hero-overview-card {
  border: 1px solid rgba(255, 255, 255, 0.7);
  border-radius: 20px;
  box-shadow: 0 18px 42px rgba(5, 20, 24, 0.2);
}

.overview-main-icon,
.recent-header-icon {
  border-radius: 10px;
  background: var(--home-soft);
  color: var(--signal-deep);
  box-shadow: none;
}

.dashboard-content {
  margin-top: -42px;
}

.stats-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.stat-card,
.recent-section-card {
  border-color: var(--sand);
  border-radius: 20px;
  box-shadow: 0 5px 18px rgba(7, 11, 20, 0.055);
}

.stat-card {
  min-height: 88px;
  padding: 14px 16px;
}

.stat-card::before {
  display: none;
}

.stat-card:hover {
  border-color: rgba(31, 92, 255, 0.35);
  box-shadow: 0 8px 22px rgba(7, 11, 20, 0.1);
  transform: translateY(-1px);
}

.stat-card:nth-child(2) {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--chalk);
}

.stat-card:nth-child(2) .stat-content span,
.stat-card:nth-child(2) .stat-content strong,
.stat-card:nth-child(2) .stat-arrow {
  color: var(--chalk);
}

.stat-card:nth-child(2) .stat-icon {
  background: rgba(56, 225, 255, 0.14);
  color: var(--jet);
}

.stat-icon {
  border-radius: 10px;
}

.stat-icon.warning {
  background: #fff7e6;
  color: var(--warning-deep);
}

.stat-icon.info,
.stat-icon.primary {
  background: var(--home-soft);
  color: var(--signal-deep);
}

.stat-icon.success {
  background: #e8f7f4;
  color: var(--success-deep);
}

.stat-icon.danger {
  background: #fff0ed;
  color: var(--danger-deep);
}

.recent-section-card {
  padding: clamp(20px, 3vw, 34px);
}

.recent-header-icon {
  width: 46px;
  height: 46px;
  font-size: 20px;
}

.recent-course-card {
  border-color: var(--sand);
  border-radius: 20px;
  box-shadow: 0 3px 12px rgba(7, 11, 20, 0.055);
}

.recent-course-card:hover {
  border-color: rgba(31, 92, 255, 0.35);
  box-shadow: 0 8px 22px rgba(7, 11, 20, 0.1);
}

.recent-course-card.featured-course-card {
  border-color: rgba(31, 92, 255, 0.3);
  box-shadow: 0 8px 26px rgba(7, 11, 20, 0.08);
}

.recent-course-cover {
  background: var(--paper);
}

.recent-course-fallback {
  background: linear-gradient(145deg, var(--signal), var(--ink));
}

.continue-btn {
  border-radius: 8px;
  box-shadow: none;
}

.continue-btn:hover {
  background: var(--midnight);
}

.recent-all-btn {
  border-radius: 8px;
}

.hero-primary-btn:focus-visible,
.recent-all-btn:focus-visible,
.continue-btn:focus-visible,
.syllabus-link:focus-visible {
  outline: 3px solid var(--jet);
  outline-offset: 3px;
}

@media (max-width: 767.98px) {
  .dashboard-hero {
    padding: 104px 0 68px;
  }

  .dashboard-content {
    margin-top: -28px;
  }

  .stats-grid {
    gap: 10px;
  }
}

@media (max-width: 575.98px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .stat-card {
    min-height: 82px;
    padding: 12px;
    gap: 10px;
  }

  .stat-content span {
    font-size: 11px;
  }
}

</style>
