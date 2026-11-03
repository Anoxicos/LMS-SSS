<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

const props = defineProps({
  missingCompany: {
    type: Boolean,
    default: false,
  },
  company: {
    type: Object,
    default: null,
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  recentRequests: {
    type: Array,
    default: () => [],
  },
  recentLearners: {
    type: Array,
    default: () => [],
  },
  recentEnrollments: {
    type: Array,
    default: () => [],
  },
  links: {
    type: Object,
    default: () => ({
      learners: '#',
      requests: '#',
      create_request: '#',
    }),
  },
  permissions: {
    type: Object,
    default: () => ({}),
  },
})

const learnerStats = computed(() => props.stats?.learners || {})
const requestStats = computed(() => props.stats?.requests || {})
const enrollmentStats = computed(() => props.stats?.enrollments || {})
const examStats = computed(() => props.stats?.exams || {})

const getInitials = (value, fallback = 'NA') => {
  if (!value) return fallback

  return String(value)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word.charAt(0).toUpperCase())
    .join('')
}

const formatPercent = value => {
  const percent = Number(value || 0)

  if (Number.isNaN(percent)) return 0

  return Math.min(Math.max(percent, 0), 100).toFixed(0)
}

const formatValidityMonths = value => {
  if (value === null || value === undefined || value === '') {
    return 'No expiration'
  }

  const months = Number(value)

  if (!Number.isFinite(months) || months <= 0) {
    return 'No expiration'
  }

  return `${months} month${months > 1 ? 's' : ''}`
}

const formatDate = value => {
  if (!value) return '-'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return '-'

  return date.toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatDateTime = value => {
  if (!value) return '-'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return '-'

  return date.toLocaleString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getRequestStatusClass = status => {
  if (status === 'approved') return 'status-pill approved'
  if (status === 'rejected') return 'status-pill rejected'
  if (status === 'pending') return 'status-pill pending'

  return 'status-pill default'
}

const getEnrollmentStatusClass = status => {
  if (status === 'completed') return 'status-pill approved'
  if (status === 'in_progress') return 'status-pill in-progress'
  if (status === 'not_started') return 'status-pill pending'
  if (status === 'expired') return 'status-pill rejected'

  return 'status-pill default'
}

const getStatusLabel = status => {
  const labels = {
    approved: t('ui-approved'),
    rejected: t('ui-rejected'),
    pending: t('ui-pending'),
    completed: t('ui-completed'),
    in_progress: t('ui-in-progress'),
    not_started: t('ui-not-started'),
    expired: t('t-expired'),
    active: t('t-active'),
    inactive: t('ui-inactive'),
  }

  return labels[status] || status || '-'
}

const getUserStatusClass = status => {
  return status === 'active'
    ? 'user-status active'
    : 'user-status inactive'
}

const requestPercent = value => {
  const total = Number(requestStats.value.total || 0)

  if (!total) return 0

  return Math.round((Number(value || 0) / total) * 100)
}

const enrollmentPercent = value => {
  const total = Number(enrollmentStats.value.total || 0)

  if (!total) return 0

  return Math.round((Number(value || 0) / total) * 100)
}

const canReadLearners = computed(() => Boolean(props.permissions?.read_learners))
const canReadRequests = computed(() => Boolean(props.permissions?.read_requests))
const canCreateRequest = computed(() => Boolean(props.permissions?.create_request))
</script>

<template>
  <Layout>
    <Head :title="$t('ui-company-dashboard')" />

    <PageHeader
      :title="$t('ui-company-dashboard')"
      :page-title="$t('ui-dashboard')"
    />

    <section class="company-dashboard-page">
      <BCard
        v-if="missingCompany"
        class="missing-company-card border-0"
      >
        <div class="missing-company-icon">
          <i class="ri-building-4-line"></i>
        </div>

        <h3>{{ $t('ui-no-company-linked') }}</h3>

        <p>
          {{ $t('ui-your-administrator-account-is-not-linked-to-a-company-') }}
        </p>
      </BCard>

      <template v-else>
        <!-- Hero -->
        <BCard class="company-hero-card border-0">
          <div class="company-hero-content">
            <div class="company-identity">
              <div class="company-logo-box">
                <img
                  v-if="company?.logo_url"
                  :src="company.logo_url"
                  :alt="company.name"
                />

                <span v-else>
                  {{ getInitials(company?.name, 'CO') }}
                </span>
              </div>

              <div class="company-main-info">
                <div class="hero-badges">
                  <span class="company-mode-badge">
                    <i class="ri-building-4-line"></i>
                    {{ $t('ui-company-administration') }}
                  </span>

                  <span
                    class="company-status-badge"
                    :class="company?.status === 'active' ? 'active' : 'inactive'"
                  >
                    <i
                      :class="company?.status === 'active'
                        ? 'ri-checkbox-circle-line'
                        : 'ri-close-circle-line'"
                    ></i>
                    {{ getStatusLabel(company?.status) }}
                  </span>
                </div>

                <h1>{{ company?.name || '-' }}</h1>

                <p>
                  {{ $t('ui-manage-learners-training-requests-course-assignments-a') }}
                </p>

                <div class="company-contact-list">
                  <span v-if="company?.email">
                    <i class="ri-mail-line"></i>
                    {{ company.email }}
                  </span>

                  <span v-if="company?.phone">
                    <i class="ri-phone-line"></i>
                    {{ company.phone }}
                  </span>

                  <span v-if="company?.address">
                    <i class="ri-map-pin-line"></i>
                    {{ company.address }}
                  </span>
                </div>
              </div>
            </div>

            <div class="quick-actions">
              <Link
                v-if="canReadLearners && links.learners !== '#'"
                :href="links.learners"
                class="quick-action-btn dark"
              >
                <span class="quick-action-icon">
                  <i class="ri-team-line"></i>
                </span>

                <span>
                  <small>{{ $t('ui-company-users') }}</small>
                  {{ $t('ui-manage-learners') }}
                </span>
              </Link>

              <Link
                v-if="canReadRequests && links.requests !== '#'"
                :href="links.requests"
                class="quick-action-btn glass"
              >
                <span class="quick-action-icon">
                  <i class="ri-file-list-3-line"></i>
                </span>

                <span>
                  <small>{{ $t('ui-training-access') }}</small>
                  {{ $t('ui-view-requests') }}
                </span>
              </Link>

              <Link
                v-if="canCreateRequest && links.create_request !== '#'"
                :href="links.create_request"
                class="quick-action-btn light"
              >
                <span class="quick-action-icon">
                  <i class="ri-add-circle-line"></i>
                </span>

                <span>
                  <small>{{ $t('ui-new-assignment') }}</small>
                  {{ $t('ui-create-request') }}
                </span>
              </Link>
            </div>
          </div>
        </BCard>

        <!-- Main stats -->
        <div class="dashboard-stats-grid">
          <BCard class="stat-card border-0">
            <div class="stat-icon learners">
              <i class="ri-graduation-cap-line"></i>
            </div>

            <div class="stat-content">
              <span>{{ $t('ui-total-learners') }}</span>
              <strong>{{ learnerStats.total || 0 }}</strong>
              <small>{{ learnerStats.active || 0 }} {{ $t('ui-active-learner-s') }}</small>
            </div>

            <Link
              v-if="canReadLearners && links.learners !== '#'"
              :href="links.learners"
              class="stat-link"
            >
              <i class="ri-arrow-right-line"></i>
            </Link>
          </BCard>

          <BCard class="stat-card border-0">
            <div class="stat-icon requests">
              <i class="ri-file-list-3-line"></i>
            </div>

            <div class="stat-content">
              <span>{{ $t('ui-training-requests') }}</span>
              <strong>{{ requestStats.total || 0 }}</strong>
              <small>{{ requestStats.pending || 0 }} {{ $t('ui-pending-request-s') }}</small>
            </div>

            <Link
              v-if="canReadRequests && links.requests !== '#'"
              :href="links.requests"
              class="stat-link"
            >
              <i class="ri-arrow-right-line"></i>
            </Link>
          </BCard>

          <BCard class="stat-card border-0">
            <div class="stat-icon courses">
              <i class="ri-book-open-line"></i>
            </div>

            <div class="stat-content">
              <span>{{ $t('ui-course-enrollments') }}</span>
              <strong>{{ enrollmentStats.total || 0 }}</strong>
              <small>
                {{ enrollmentStats.assigned_courses || 0 }} {{ $t('ui-assigned-course-s') }}
              </small>
            </div>

            <span class="stat-link static">
              <i class="ri-bookmark-line"></i>
            </span>
          </BCard>

          <BCard class="stat-card border-0">
            <div class="stat-icon completed">
              <i class="ri-award-line"></i>
            </div>

            <div class="stat-content">
              <span>{{ $t('ui-completed-training') }}</span>
              <strong>{{ enrollmentStats.completed || 0 }}</strong>
              <small>
                {{ $t('ui-average-progress-label') }}
                {{ formatPercent(enrollmentStats.average_progress) }}%
              </small>
            </div>

            <span class="stat-link static success">
              <i class="ri-checkbox-circle-line"></i>
            </span>
          </BCard>
        </div>

        <!-- Overview cards -->
        <BRow class="g-4 mb-4">
          <BCol xl="6">
            <BCard class="dashboard-section-card border-0 h-100">
              <div class="section-header">
                <div class="section-title-box">
                  <div class="section-icon requests">
                    <i class="ri-file-chart-line"></i>
                  </div>

                  <div>
                    <h3>{{ $t('ui-request-overview') }}</h3>
                    <p>{{ $t('ui-current-status-of-company-training-requests') }}</p>
                  </div>
                </div>

                <Link
                  v-if="canReadRequests && links.requests !== '#'"
                  :href="links.requests"
                  class="view-link"
                >
                  {{ $t('ui-view-all') }}
                  <i class="ri-arrow-right-line"></i>
                </Link>
              </div>

              <div class="overview-list">
                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-time-line pending"></i>
                      {{ $t('t-pending') }}
                    </span>
                    <strong>{{ requestStats.pending || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar pending"
                      :style="{ width: `${requestPercent(requestStats.pending)}%` }"
                    ></div>
                  </div>

                  <small>
                    {{ requestPercent(requestStats.pending) }}{{ $t('ui-of-requests') }}
                  </small>
                </div>

                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-checkbox-circle-line approved"></i>
                      {{ $t('ui-approved') }}
                    </span>
                    <strong>{{ requestStats.approved || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar approved"
                      :style="{ width: `${requestPercent(requestStats.approved)}%` }"
                    ></div>
                  </div>

                  <small>
                    {{ requestPercent(requestStats.approved) }}{{ $t('ui-of-requests') }}
                  </small>
                </div>

                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-close-circle-line rejected"></i>
                      {{ $t('ui-rejected') }}
                    </span>
                    <strong>{{ requestStats.rejected || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar rejected"
                      :style="{ width: `${requestPercent(requestStats.rejected)}%` }"
                    ></div>
                  </div>

                  <small>
                    {{ requestPercent(requestStats.rejected) }}{{ $t('ui-of-requests') }}
                  </small>
                </div>
              </div>
            </BCard>
          </BCol>

          <BCol xl="6">
            <BCard class="dashboard-section-card border-0 h-100">
              <div class="section-header">
                <div class="section-title-box">
                  <div class="section-icon progress">
                    <i class="ri-bar-chart-box-line"></i>
                  </div>

                  <div>
                    <h3>{{ $t('ui-training-progress') }}</h3>
                    <p>{{ $t('ui-course-progress-across-company-enrollments') }}</p>
                  </div>
                </div>

                <span class="total-badge">
                  {{ enrollmentStats.total || 0 }} {{ $t('ui-enrollment-s') }}
                </span>
              </div>

              <div class="overview-list">
                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-time-line pending"></i>
                      {{ $t('ui-not-started') }}
                    </span>
                    <strong>{{ enrollmentStats.not_started || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar pending"
                      :style="{
                        width: `${enrollmentPercent(enrollmentStats.not_started)}%`,
                      }"
                    ></div>
                  </div>
                </div>

                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-loader-2-line in-progress"></i>
                      {{ $t('ui-in-progress') }}
                    </span>
                    <strong>{{ enrollmentStats.in_progress || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar in-progress"
                      :style="{
                        width: `${enrollmentPercent(enrollmentStats.in_progress)}%`,
                      }"
                    ></div>
                  </div>
                </div>

                <div class="overview-item">
                  <div class="overview-heading">
                    <span>
                      <i class="ri-award-line approved"></i>
                      {{ $t('ui-completed') }}
                    </span>
                    <strong>{{ enrollmentStats.completed || 0 }}</strong>
                  </div>

                  <div class="overview-track">
                    <div
                      class="overview-bar approved"
                      :style="{
                        width: `${enrollmentPercent(enrollmentStats.completed)}%`,
                      }"
                    ></div>
                  </div>
                </div>
              </div>
            </BCard>
          </BCol>
        </BRow>

        <!-- Recent requests and learners -->
        <BRow class="g-4 mb-4">
          <BCol xl="8">
            <BCard class="dashboard-section-card border-0 h-100">
              <div class="section-header">
                <div class="section-title-box">
                  <div class="section-icon requests">
                    <i class="ri-file-list-3-line"></i>
                  </div>

                  <div>
                    <h3>{{ $t('ui-recent-requests') }}</h3>
                    <p>{{ $t('ui-latest-training-requests-submitted-by-your-company') }}</p>
                  </div>
                </div>

                <Link
                  v-if="canCreateRequest && links.create_request !== '#'"
                  :href="links.create_request"
                  class="primary-section-btn"
                >
                  <i class="ri-add-line"></i>
                  {{ $t('ui-new-request') }}
                </Link>
              </div>

              <div
                v-if="recentRequests.length"
                class="table-responsive d-none d-lg-block"
              >
                <table class="table dashboard-table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>{{ $t('ui-request') }}</th>
                      <th>{{ $t('ui-course') }}</th>
                      <th>{{ $t('ui-learners') }}</th>
                      <th>{{ $t('ui-status') }}</th>
                      <th>{{ $t('ui-date') }}</th>
                      <th class="text-center">{{ $t('ui-action') }}</th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr
                      v-for="item in recentRequests"
                      :key="item.id"
                    >
                      <td>
                        <div class="request-reference-cell">
                          <div class="request-icon">
                            <i class="ri-file-text-line"></i>
                          </div>

                          <div class="min-width-0">
                            <strong>{{ item.reference }}</strong>
                            <span>{{ item.requested_by?.name || '-' }}</span>
                          </div>
                        </div>
                      </td>

                      <td>
                        <div class="course-cell">
                          <div class="course-avatar">
                            <img
                              v-if="item.course?.image_url"
                              :src="item.course.image_url"
                              :alt="item.course.title"
                            />

                            <span v-else>
                              {{ getInitials(item.course?.title, 'CO') }}
                            </span>
                          </div>

                          <div class="min-width-0">
                            <strong>{{ item.course?.title || '-' }}</strong>
                            <span>
                              {{ formatValidityMonths(item.duree_validite_formation) }}
                            </span>
                          </div>
                        </div>
                      </td>

                      <td>
                        <span class="count-badge">
                          <i class="ri-team-line"></i>
                          {{ item.learners_count || 0 }}
                        </span>
                      </td>

                      <td>
                        <span :class="getRequestStatusClass(item.status)">
                          {{ getStatusLabel(item.status) }}
                        </span>
                      </td>

                      <td>
                        <span class="table-date">
                          {{ formatDate(item.created_at) }}
                        </span>
                      </td>

                      <td class="text-center">
                        <Link
                          :href="route('company-requests.show', item.hashid)"
                          class="table-action-btn"
                          :title="$t('ui-view-request')"
                        >
                          <i class="ri-eye-line"></i>
                        </Link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div
                v-if="recentRequests.length"
                class="request-mobile-list d-lg-none"
              >
                <div
                  v-for="item in recentRequests"
                  :key="item.id"
                  class="request-mobile-card"
                >
                  <div class="request-mobile-header">
                    <div>
                      <span>{{ $t('ui-request') }}</span>
                      <strong>{{ item.reference }}</strong>
                    </div>

                    <span :class="getRequestStatusClass(item.status)">
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </div>

                  <div class="request-mobile-course">
                    <div class="course-avatar">
                      <img
                        v-if="item.course?.image_url"
                        :src="item.course.image_url"
                        :alt="item.course.title"
                      />

                      <span v-else>
                        {{ getInitials(item.course?.title, 'CO') }}
                      </span>
                    </div>

                    <div class="min-width-0">
                      <strong>{{ item.course?.title || '-' }}</strong>
                      <span>{{ item.learners_count || 0 }} {{ $t('ui-learner-s') }}</span>
                      <span>{{ formatValidityMonths(item.duree_validite_formation) }}</span>
                    </div>
                  </div>

                  <div class="request-mobile-footer">
                    <span>
                      <i class="ri-calendar-line"></i>
                      {{ formatDate(item.created_at) }}
                    </span>

                    <Link
                      :href="route('company-requests.show', item.hashid)"
                    >
                      {{ $t('ui-view-details') }}
                      <i class="ri-arrow-right-line"></i>
                    </Link>
                  </div>
                </div>
              </div>

              <div
                v-if="!recentRequests.length"
                class="empty-state"
              >
                <div class="empty-icon">
                  <i class="ri-file-list-3-line"></i>
                </div>

                <h4>{{ $t('ui-no-requests-found') }}</h4>
                <p>{{ $t('ui-no-training-request-has-been-created-for-this-company') }}</p>

                <Link
                  v-if="canCreateRequest && links.create_request !== '#'"
                  :href="links.create_request"
                  class="primary-section-btn"
                >
                  <i class="ri-add-line"></i>
                  {{ $t('ui-create-first-request') }}
                </Link>
              </div>
            </BCard>
          </BCol>

          <BCol xl="4">
            <BCard class="dashboard-section-card border-0 h-100">
              <div class="section-header">
                <div class="section-title-box">
                  <div class="section-icon learners">
                    <i class="ri-team-line"></i>
                  </div>

                  <div>
                    <h3>{{ $t('ui-recent-learners') }}</h3>
                    <p>{{ $t('ui-newest-learners-linked-to-the-company') }}</p>
                  </div>
                </div>

                <Link
                  v-if="canReadLearners && links.learners !== '#'"
                  :href="links.learners"
                  class="icon-link"
                >
                  <i class="ri-arrow-right-line"></i>
                </Link>
              </div>

              <div
                v-if="recentLearners.length"
                class="recent-learners-list"
              >
                <div
                  v-for="learner in recentLearners"
                  :key="learner.id"
                  class="recent-learner-item"
                >
                  <div class="learner-avatar">
                    <img
                      v-if="learner.photo_url"
                      :src="learner.photo_url"
                      :alt="learner.name"
                    />

                    <span v-else>
                      {{ getInitials(learner.name, 'LR') }}
                    </span>
                  </div>

                  <div class="learner-content">
                    <div class="learner-heading">
                      <div class="min-width-0">
                        <h5>{{ learner.name }}</h5>
                        <p>{{ learner.email }}</p>
                      </div>

                      <span :class="getUserStatusClass(learner.status)">
                        {{ getStatusLabel(learner.status) }}
                      </span>
                    </div>

                    <div class="learner-progress-heading">
                      <span>
                        {{ learner.enrollments_count || 0 }} {{ $t('ui-course-s') }}
                      </span>

                      <strong>
                        {{ formatPercent(learner.average_progress) }}%
                      </strong>
                    </div>

                    <div class="learner-progress-track">
                      <div
                        :style="{
                          width: `${formatPercent(learner.average_progress)}%`,
                        }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-else
                class="empty-state compact"
              >
                <div class="empty-icon">
                  <i class="ri-user-add-line"></i>
                </div>

                <h4>{{ $t('ui-no-learners-found') }}</h4>
                <p>{{ $t('ui-no-learner-is-linked-to-this-company') }}</p>
              </div>
            </BCard>
          </BCol>
        </BRow>

        <!-- Enrollments -->
        <BCard class="dashboard-section-card border-0 mb-4">
          <div class="section-header">
            <div class="section-title-box">
              <div class="section-icon progress">
                <i class="ri-book-open-line"></i>
              </div>

              <div>
                <h3>{{ $t('ui-recent-course-assignments') }}</h3>
                <p>{{ $t('ui-latest-enrollments-for-company-learners') }}</p>
              </div>
            </div>

            <span class="total-badge">
              {{ enrollmentStats.total || 0 }} {{ $t('ui-total') }}
            </span>
          </div>

          <div
            v-if="recentEnrollments.length"
            class="enrollment-grid"
          >
            <div
              v-for="enrollment in recentEnrollments"
              :key="enrollment.id"
              class="enrollment-card"
            >
              <div class="enrollment-cover">
                <img
                  v-if="enrollment.course?.image_url"
                  :src="enrollment.course.image_url"
                  :alt="enrollment.course.title"
                />

                <span v-else>
                  {{ getInitials(enrollment.course?.title, 'CO') }}
                </span>

              
              </div>

              <div class="enrollment-content">
                <div class="enrollment-status-row">
                  <span :class="getEnrollmentStatusClass(enrollment.status)">
                    {{ getStatusLabel(enrollment.status) }}
                  </span>

                  <small>{{ formatDate(enrollment.created_at) }}</small>
                </div>

                <h4>{{ enrollment.course?.title || '-' }}</h4>

                <div class="enrollment-learner">
                  <div class="small-avatar">
                    <img
                      v-if="enrollment.learner?.photo_url"
                      :src="enrollment.learner.photo_url"
                      :alt="enrollment.learner.name"
                    />

                    <span v-else>
                      {{ getInitials(enrollment.learner?.name, 'LR') }}
                    </span>
                  </div>

                  <div class="min-width-0">
                    <strong>{{ enrollment.learner?.name || '-' }}</strong>
                    <small>{{ enrollment.learner?.email || '-' }}</small>
                  </div>
                </div>

                <div class="enrollment-progress-heading">
                  <span>{{ $t('ui-course-progress') }}</span>
                  <strong>{{ formatPercent(enrollment.progress_percent) }}%</strong>
                </div>

                <div class="enrollment-progress-track">
                  <div
                    :style="{
                      width: `${formatPercent(enrollment.progress_percent)}%`,
                    }"
                  ></div>
                </div>

                <div class="enrollment-meta">
                  <span>
                    <i class="ri-repeat-line"></i>
                    {{ $t('ui-attempts') }} {{ enrollment.attempts_rest || 0 }}
                  </span>

                  <span>
                    <i class="ri-time-line"></i>
                    {{ formatDateTime(enrollment.started_at) }}
                  </span>

                  <span>
                    <i class="ri-calendar-check-line"></i>
                    {{ formatValidityMonths(enrollment.duree_validite_formation) }}
                  </span>

                  <span v-if="enrollment.expired_at">
                    <i class="ri-calendar-event-line"></i>
                    {{ $t('ui-expires') }} {{ formatDate(enrollment.expired_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div
            v-else
            class="empty-state"
          >
            <div class="empty-icon">
              <i class="ri-book-open-line"></i>
            </div>

            <h4>{{ $t('ui-no-enrollments-found') }}</h4>
            <p>{{ $t('ui-no-course-has-been-assigned-to-company-learners-yet') }}</p>
          </div>
        </BCard>

        <!-- Exam summary -->
        <div class="exam-summary-grid">
          <BCard class="exam-summary-card border-0">
            <div class="exam-summary-icon total">
              <i class="ri-file-list-3-line"></i>
            </div>

            <div>
              <span>{{ $t('ui-total-exams') }}</span>
              <strong>{{ examStats.total || 0 }}</strong>
            </div>
          </BCard>

          <BCard class="exam-summary-card border-0">
            <div class="exam-summary-icon passed">
              <i class="ri-checkbox-circle-line"></i>
            </div>

            <div>
              <span>{{ $t('ui-passed-exams') }}</span>
              <strong>{{ examStats.passed || 0 }}</strong>
            </div>
          </BCard>

          <BCard class="exam-summary-card border-0">
            <div class="exam-summary-icon failed">
              <i class="ri-close-circle-line"></i>
            </div>

            <div>
              <span>{{ $t('ui-failed-exams') }}</span>
              <strong>{{ examStats.failed || 0 }}</strong>
            </div>
          </BCard>

          <BCard class="exam-summary-card border-0">
            <div class="exam-summary-icon certificates">
              <i class="ri-award-line"></i>
            </div>

            <div>
              <span>{{ $t('ui-certificates') }}</span>
              <strong>{{ stats.certificates || 0 }}</strong>
            </div>
          </BCard>
        </div>
      </template>
    </section>
  </Layout>
</template>

<style scoped>
.company-dashboard-page {
  --primary: #687cfe;
  --primary-dark: #405189;
  --success: #0ab39c;
  --warning: #f7b84b;
  --danger: #f06548;
  --info: #299cdb;
  padding-bottom: 30px;
}

.min-width-0 {
  min-width: 0;
}

.missing-company-card {
  min-height: 360px;
  border-radius: 24px;
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
}

.missing-company-card :deep(.card-body) {
  padding: 42px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.missing-company-icon {
  width: 88px;
  height: 88px;
  border-radius: 28px;
  color: var(--primary);
  background: rgba(104, 124, 254, 0.11);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
  margin-bottom: 18px;
}

.missing-company-card h3 {
  color: #111827;
  font-size: 24px;
  font-weight: 900;
  margin-bottom: 8px;
}

.missing-company-card p {
  max-width: 620px;
  color: #64748b;
  line-height: 1.7;
  margin-bottom: 0;
}

.company-hero-card {
  overflow: hidden;
  border-radius: 24px;
  color: #ffffff;
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 34%),
    linear-gradient(135deg, #405189 0%, #687cfe 62%, #8292ff 100%);
  box-shadow: 0 18px 42px rgba(64, 81, 137, 0.22);
  margin-bottom: 24px;
}

.company-hero-card :deep(.card-body) {
  padding: 0;
}

.company-hero-content {
  min-height: 270px;
  padding: 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 34px;
}

.company-identity {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 24px;
}

.company-logo-box {
  width: 118px;
  height: 118px;
  min-width: 118px;
  border-radius: 30px;
  padding: 8px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 16px 34px rgba(29, 37, 78, 0.22);
}

.company-logo-box img,
.company-logo-box span {
  width: 100%;
  height: 100%;
  border-radius: 23px;
}

.company-logo-box img {
  object-fit: contain;
}

.company-logo-box span {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 38px;
  font-weight: 900;
}

.company-main-info {
  min-width: 0;
}

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.company-mode-badge,
.company-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 900;
}

.company-mode-badge {
  color: var(--primary-dark);
  background: rgba(255, 255, 255, 0.93);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.company-status-badge.active {
  color: #ffffff;
  background: rgba(10, 179, 156, 0.9);
}

.company-status-badge.inactive {
  color: #ffffff;
  background: rgba(240, 101, 72, 0.9);
}

.company-main-info h1 {
  color: #ffffff;
  font-size: 36px;
  font-weight: 900;
  margin-bottom: 10px;
}

.company-main-info > p {
  max-width: 670px;
  color: rgba(255, 255, 255, 0.88);
  font-size: 14px;
  line-height: 1.65;
  margin-bottom: 15px;
}

.company-contact-list {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
}

.company-contact-list span {
  padding: 7px 10px;
  border-radius: 999px;
  color: rgba(255, 255, 255, 0.94);
  background: rgba(255, 255, 255, 0.12);
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.quick-actions {
  width: 305px;
  min-width: 305px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quick-action-btn {
  min-height: 62px;
  padding: 10px 14px;
  border-radius: 16px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.quick-action-btn:hover {
  transform: translateY(-2px);
}

.quick-action-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
}

.quick-action-btn > span:last-child {
  font-size: 13px;
  font-weight: 900;
}

.quick-action-btn small {
  display: block;
  margin-bottom: 2px;
  font-size: 9px;
  font-weight: 800;
  opacity: 0.72;
  text-transform: uppercase;
}

.quick-action-btn.dark {
  color: #ffffff;
  background: rgba(7, 17, 38, 0.28);
}

.quick-action-btn.dark .quick-action-icon {
  color: var(--primary-dark);
  background: #ffffff;
}

.quick-action-btn.glass {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.15);
}

.quick-action-btn.glass .quick-action-icon {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.18);
}

.quick-action-btn.light {
  color: var(--primary-dark);
  background: #ffffff;
  box-shadow: 0 12px 26px rgba(30, 40, 90, 0.16);
}

.quick-action-btn.light .quick-action-icon {
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.dashboard-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card {
  min-height: 140px;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
}

.stat-card :deep(.card-body) {
  padding: 21px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 58px;
  height: 58px;
  min-width: 58px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
}

.stat-icon.learners {
  color: var(--primary);
  background: rgba(104, 124, 254, 0.12);
}

.stat-icon.requests {
  color: var(--info);
  background: rgba(41, 156, 219, 0.12);
}

.stat-icon.courses {
  color: var(--warning);
  background: rgba(247, 184, 75, 0.14);
}

.stat-icon.completed {
  color: var(--success);
  background: rgba(10, 179, 156, 0.12);
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-content span,
.stat-content small {
  display: block;
}

.stat-content span {
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  margin-bottom: 5px;
}

.stat-content strong {
  display: block;
  color: #111827;
  font-size: 29px;
  line-height: 1;
  font-weight: 900;
  margin-bottom: 7px;
}

.stat-content small {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.stat-link {
  width: 33px;
  height: 33px;
  min-width: 33px;
  border-radius: 11px;
  color: var(--primary);
  background: rgba(104, 124, 254, 0.09);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.stat-link.static {
  color: #94a3b8;
  background: #f1f5f9;
}

.stat-link.static.success {
  color: var(--success);
  background: rgba(10, 179, 156, 0.09);
}

.dashboard-section-card {
  overflow: hidden;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 13px 32px rgba(15, 23, 42, 0.07);
}

.dashboard-section-card :deep(.card-body) {
  padding: 23px;
}

.section-header {
  margin-bottom: 21px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.section-title-box {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 15px;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
}

.section-icon.requests {
  background: linear-gradient(135deg, var(--info), var(--primary-dark));
  box-shadow: 0 9px 20px rgba(41, 156, 219, 0.22);
}

.section-icon.progress {
  background: linear-gradient(135deg, var(--warning), #d68d16);
  box-shadow: 0 9px 20px rgba(247, 184, 75, 0.23);
}

.section-icon.learners {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  box-shadow: 0 9px 20px rgba(104, 124, 254, 0.23);
}

.section-title-box h3 {
  color: #111827;
  font-size: 18px;
  font-weight: 900;
  margin-bottom: 3px;
}

.section-title-box p {
  color: #64748b;
  font-size: 11px;
  margin-bottom: 0;
}

.view-link,
.icon-link {
  color: var(--primary);
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.icon-link {
  width: 34px;
  height: 34px;
  border-radius: 11px;
  justify-content: center;
  background: rgba(104, 124, 254, 0.09);
}

.primary-section-btn {
  height: 39px;
  padding: 0 13px;
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  box-shadow: 0 8px 18px rgba(104, 124, 254, 0.21);
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.primary-section-btn:hover {
  color: #ffffff;
}

.total-badge {
  padding: 7px 11px;
  border-radius: 999px;
  color: var(--primary-dark);
  background: rgba(104, 124, 254, 0.1);
  font-size: 10px;
  font-weight: 900;
}

.overview-list {
  display: flex;
  flex-direction: column;
  gap: 19px;
}

.overview-heading {
  margin-bottom: 7px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.overview-heading span {
  color: #475569;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.overview-heading i {
  font-size: 16px;
}

.overview-heading i.pending {
  color: var(--warning);
}

.overview-heading i.approved {
  color: var(--success);
}

.overview-heading i.rejected {
  color: var(--danger);
}

.overview-heading i.in-progress {
  color: var(--info);
}

.overview-heading strong {
  color: #111827;
  font-size: 13px;
  font-weight: 900;
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
  transition: width 0.3s ease;
}

.overview-bar.pending {
  background: linear-gradient(90deg, var(--warning), #d68d16);
}

.overview-bar.approved {
  background: linear-gradient(90deg, var(--success), #087f6f);
}

.overview-bar.rejected {
  background: linear-gradient(90deg, var(--danger), #c2412d);
}

.overview-bar.in-progress {
  background: linear-gradient(90deg, var(--info), var(--primary-dark));
}

.overview-item small {
  display: block;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 700;
  margin-top: 5px;
}

.dashboard-table thead th {
  padding: 11px 10px;
  border-bottom: none;
  color: #64748b;
  background: #f8fafc;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  white-space: nowrap;
}

.dashboard-table tbody td {
  padding: 13px 10px;
  border-color: #f1f5f9;
}

.request-reference-cell,
.course-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.request-icon {
  width: 37px;
  height: 37px;
  min-width: 37px;
  border-radius: 12px;
  color: var(--info);
  background: rgba(41, 156, 219, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
}

.request-reference-cell strong,
.course-cell strong {
  display: block;
  max-width: 190px;
  color: #334155;
  font-size: 11px;
  font-weight: 900;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.request-reference-cell span,
.course-cell span {
  display: block;
  max-width: 190px;
  color: #94a3b8;
  font-size: 9px;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.course-avatar {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 12px;
  overflow: hidden;
  background: #eef2ff;
}

.course-avatar img,
.course-avatar > span {
  width: 100%;
  height: 100%;
}

.course-avatar img {
  object-fit: cover;
}

.course-avatar > span {
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 900;
}

.count-badge {
  padding: 6px 9px;
  border-radius: 999px;
  color: var(--primary-dark);
  background: rgba(104, 124, 254, 0.09);
  font-size: 10px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.status-pill {
  padding: 6px 9px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  text-transform: capitalize;
  white-space: nowrap;
}

.status-pill.approved {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.11);
}

.status-pill.pending {
  color: #b57208;
  background: rgba(247, 184, 75, 0.15);
}

.status-pill.rejected {
  color: #c2412d;
  background: rgba(240, 101, 72, 0.11);
}

.status-pill.in-progress {
  color: #1878aa;
  background: rgba(41, 156, 219, 0.11);
}

.status-pill.default {
  color: #64748b;
  background: #f1f5f9;
}

.table-date {
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
}

.table-action-btn {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  color: var(--primary);
  background: rgba(104, 124, 254, 0.09);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.request-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.request-mobile-card {
  padding: 13px;
  border: 1px solid #edf2f7;
  border-radius: 16px;
}

.request-mobile-header,
.request-mobile-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.request-mobile-header > div span {
  display: block;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 800;
}

.request-mobile-header > div strong {
  color: #334155;
  font-size: 12px;
  font-weight: 900;
}

.request-mobile-course {
  margin: 12px 0;
  padding: 10px;
  border-radius: 13px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  gap: 10px;
}

.request-mobile-course strong,
.request-mobile-course span {
  display: block;
}

.request-mobile-course strong {
  color: #334155;
  font-size: 11px;
  font-weight: 900;
}

.request-mobile-course span {
  color: #94a3b8;
  font-size: 9px;
}

.request-mobile-footer span,
.request-mobile-footer a {
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
  text-decoration: none;
}

.request-mobile-footer a {
  color: var(--primary);
}

.recent-learners-list {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.recent-learner-item {
  padding: 11px;
  border: 1px solid #edf2f7;
  border-radius: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.learner-avatar,
.learner-avatar img,
.learner-avatar span {
  width: 43px;
  height: 43px;
  min-width: 43px;
  border-radius: 50%;
}

.learner-avatar img {
  object-fit: cover;
}

.learner-avatar span {
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 900;
}

.learner-content {
  min-width: 0;
  flex: 1;
}

.learner-heading {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 7px;
}

.learner-heading h5,
.learner-heading p {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.learner-heading h5 {
  max-width: 150px;
  color: #334155;
  font-size: 11px;
  font-weight: 900;
  margin-bottom: 2px;
}

.learner-heading p {
  max-width: 150px;
  color: #94a3b8;
  font-size: 9px;
  margin-bottom: 0;
}

.user-status {
  height: fit-content;
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 8px;
  font-weight: 900;
}

.user-status.active {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.11);
}

.user-status.inactive {
  color: #c2412d;
  background: rgba(240, 101, 72, 0.11);
}

.learner-progress-heading,
.enrollment-progress-heading {
  margin-bottom: 5px;
  display: flex;
  justify-content: space-between;
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
}

.learner-progress-heading strong,
.enrollment-progress-heading strong {
  color: var(--primary-dark);
}

.learner-progress-track,
.enrollment-progress-track {
  height: 5px;
  border-radius: 999px;
  overflow: hidden;
  background: #edf2f7;
}

.learner-progress-track div,
.enrollment-progress-track div {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
}

.empty-state {
  min-height: 230px;
  padding: 25px;
  border-radius: 18px;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.empty-state.compact {
  min-height: 300px;
}

.empty-icon {
  width: 60px;
  height: 60px;
  border-radius: 19px;
  color: var(--primary);
  background: rgba(104, 124, 254, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
  margin-bottom: 11px;
}

.empty-state h4 {
  color: #334155;
  font-size: 15px;
  font-weight: 900;
  margin-bottom: 5px;
}

.empty-state p {
  color: #94a3b8;
  font-size: 11px;
  margin-bottom: 13px;
}

.enrollment-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.enrollment-card {
  overflow: hidden;
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background: #ffffff;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.enrollment-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 13px 28px rgba(15, 23, 42, 0.08);
}

.enrollment-cover {
  position: relative;
  height: 112px;
  overflow: hidden;
  background: #eef2ff;
}

.enrollment-cover img,
.enrollment-cover > span {
  width: 100%;
  height: 100%;
}

.enrollment-cover img {
  object-fit: cover;
}

.enrollment-cover > span {
  color: #ffffff;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
  font-weight: 900;
}

.scorm-badge {
  position: absolute;
  top: 9px;
  right: 9px;
  padding: 5px 8px;
  border-radius: 999px;
  color: #ffffff;
  background: rgba(7, 17, 38, 0.84);
  font-size: 8px;
  font-weight: 900;
}

.enrollment-content {
  padding: 14px;
}

.enrollment-status-row {
  margin-bottom: 8px;
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.enrollment-status-row > small {
  color: #94a3b8;
  font-size: 8px;
  font-weight: 700;
}

.enrollment-content > h4 {
  min-height: 34px;
  color: #334155;
  font-size: 13px;
  line-height: 1.35;
  font-weight: 900;
  margin-bottom: 11px;
}

.enrollment-learner {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.small-avatar,
.small-avatar img,
.small-avatar span {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 50%;
}

.small-avatar img {
  object-fit: cover;
}

.small-avatar span {
  color: var(--primary-dark);
  background: rgba(104, 124, 254, 0.11);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  font-weight: 900;
}

.enrollment-learner strong,
.enrollment-learner small {
  display: block;
  max-width: 190px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.enrollment-learner strong {
  color: #475569;
  font-size: 10px;
  font-weight: 900;
}

.enrollment-learner small {
  color: #94a3b8;
  font-size: 8px;
}

.enrollment-meta {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.enrollment-meta span {
  padding: 5px 7px;
  border-radius: 999px;
  color: #64748b;
  background: #f8fafc;
  font-size: 8px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.enrollment-meta i {
  color: var(--primary);
}

.exam-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.exam-summary-card {
  border-radius: 18px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
}

.exam-summary-card :deep(.card-body) {
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.exam-summary-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.exam-summary-icon.total {
  color: var(--primary);
  background: rgba(104, 124, 254, 0.11);
}

.exam-summary-icon.passed {
  color: var(--success);
  background: rgba(10, 179, 156, 0.11);
}

.exam-summary-icon.failed {
  color: var(--danger);
  background: rgba(240, 101, 72, 0.11);
}

.exam-summary-icon.certificates {
  color: var(--warning);
  background: rgba(247, 184, 75, 0.14);
}

.exam-summary-card span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
  margin-bottom: 4px;
}

.exam-summary-card strong {
  color: #111827;
  font-size: 24px;
  line-height: 1;
  font-weight: 900;
}

@media (max-width: 1399.98px) {
  .dashboard-stats-grid,
  .exam-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .enrollment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .company-hero-content {
    align-items: flex-start;
    flex-direction: column;
  }

  .quick-actions {
    width: 100%;
    min-width: 100%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767.98px) {
  .company-hero-content {
    padding: 23px;
  }

  .company-identity {
    align-items: flex-start;
    flex-direction: column;
  }

  .company-logo-box {
    width: 88px;
    height: 88px;
    min-width: 88px;
    border-radius: 23px;
  }

  .company-logo-box img,
  .company-logo-box span {
    border-radius: 17px;
  }

  .company-main-info h1 {
    font-size: 27px;
  }

  .quick-actions,
  .dashboard-stats-grid,
  .exam-summary-grid,
  .enrollment-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .primary-section-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 575.98px) {
  .company-hero-card,
  .stat-card,
  .dashboard-section-card,
  .exam-summary-card {
    border-radius: 17px;
  }

  .company-contact-list {
    align-items: stretch;
    flex-direction: column;
  }

  .company-contact-list span {
    border-radius: 12px;
  }

  .dashboard-section-card :deep(.card-body) {
    padding: 17px;
  }

  .stat-card :deep(.card-body) {
    padding: 17px;
  }

  .section-title-box {
    align-items: flex-start;
  }
}
</style>
