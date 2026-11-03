<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
  companies: {
    type: Array,
    default: () => [],
  },
  topCompanies: {
    type: Array,
    default: () => [],
  },
  pendingRequests: {
    type: Array,
    default: () => [],
  },
  recentEnrollments: {
    type: Array,
    default: () => [],
  },
  monthlyActivity: {
    type: Array,
    default: () => [],
  },
  links: {
    type: Object,
    default: () => ({}),
  },
  permissions: {
    type: Object,
    default: () => ({}),
  },
})

const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const perPage = 8

const companyStats = computed(() => props.stats.companies || {})
const userStats = computed(() => props.stats.users || {})
const requestStats = computed(() => props.stats.requests || {})
const enrollmentStats = computed(() => props.stats.enrollments || {})
const courseStats = computed(() => props.stats.courses || {})
const examStats = computed(() => props.stats.exams || {})

const filteredCompanies = computed(() => {
  const search = searchQuery.value.trim().toLowerCase()

  return props.companies.filter(company => {
    const matchesStatus =
      selectedStatus.value === 'all' ||
      company.status === selectedStatus.value

    const matchesSearch =
      !search ||
      String(company.name || '').toLowerCase().includes(search) ||
      String(company.email || '').toLowerCase().includes(search) ||
      String(company.phone || '').toLowerCase().includes(search) ||
      String(company.address || '').toLowerCase().includes(search)

    return matchesStatus && matchesSearch
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredCompanies.value.length / perPage))
})

const paginatedCompanies = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredCompanies.value.slice(start, start + perPage)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const start = Math.max(1, current - 2)
  const end = Math.min(total, start + 4)
  const adjustedStart = Math.max(1, end - 4)

  return Array.from(
    { length: end - adjustedStart + 1 },
    (_, index) => adjustedStart + index
  )
})

const maxActivityValue = computed(() => {
  const values = props.monthlyActivity.flatMap(item => [
    Number(item.companies || 0),
    Number(item.requests || 0),
    Number(item.enrollments || 0),
  ])

  return Math.max(...values, 1)
})

const formatPercent = value => {
  const number = Number(value || 0)

  if (Number.isNaN(number)) return 0

  return Math.min(Math.max(number, 0), 100).toFixed(0)
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

const getInitials = (value, fallback = 'NA') => {
  if (!value) return fallback

  return String(value)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word.charAt(0).toUpperCase())
    .join('')
}

const getCompanyStatusClass = status => {
  return status === 'active'
    ? 'company-status active'
    : 'company-status inactive'
}

const getEnrollmentStatusClass = status => {
  switch (status) {
    case 'completed':
      return 'status-pill completed'
    case 'in_progress':
      return 'status-pill in-progress'
    case 'not_started':
      return 'status-pill pending'
    case 'expired':
      return 'status-pill rejected'
    default:
      return 'status-pill default'
  }
}

const getEnrollmentStatusLabel = status => {
  switch (status) {
    case 'completed':
      return t('ui-completed')
    case 'in_progress':
      return t('ui-in-progress')
    case 'not_started':
      return t('ui-not-started')
    case 'expired':
      return 'Expired'
    default:
      return status || '-'
  }
}

const getActivityHeight = value => {
  return `${Math.max(8, (Number(value || 0) / maxActivityValue.value) * 100)}%`
}

const requestPercent = value => {
  const total = Number(requestStats.value.total || 0)
  if (!total) return 0

  return Math.round((Number(value || 0) / total) * 100)
}

const coursePercent = value => {
  const total = Number(courseStats.value.total || 0)
  if (!total) return 0

  return Math.round((Number(value || 0) / total) * 100)
}

const goToPage = page => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

watch([searchQuery, selectedStatus], () => {
  currentPage.value = 1
})

watch(totalPages, value => {
  if (currentPage.value > value) {
    currentPage.value = value
  }
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-super-admin-dashboard')" />

    <PageHeader
      :title="$t('ui-super-admin-dashboard')"
      :page-title="$t('ui-dashboard')"
    />

    <section class="super-admin-dashboard">
      <BCard class="platform-hero border-0">
        <div class="platform-hero-content">
          <div class="platform-intro">
            <div class="platform-hero-icon">
              <i class="ri-building-4-line"></i>
            </div>

            <div>
              <span class="platform-kicker">
                <i class="ri-shield-star-line"></i>
                {{ $t('ui-global-platform-administration') }}
              </span>

              <h1>{{ $t('ui-manage-every-company-from-one-dashboard') }}</h1>

              <p>
                {{ $t('ui-monitor-companies-administrators-learners-training-req') }}
              </p>

              <div class="platform-summary-chips">
                <span>
                  <i class="ri-building-line"></i>
                  {{ companyStats.total || 0 }} {{ $t('t-companies') }}
                </span>

                <span>
                  <i class="ri-team-line"></i>
                  {{ userStats.total || 0 }} {{ $t('t-users') }}
                </span>

                <span>
                  <i class="ri-book-open-line"></i>
                  {{ courseStats.total || 0 }} {{ $t('ui-courses-label') }}
                </span>
              </div>
            </div>
          </div>

          <div class="platform-actions">
            <Link
              v-if="permissions.read_companies"
              :href="links.companies || '#'"
              class="platform-action dark"
            >
              <i class="ri-building-line"></i>
              <span>
                <small>{{ $t('ui-company-portfolio') }}</small>
                {{ $t('ui-manage-companies') }}
              </span>
            </Link>

          

            <Link
              v-if="permissions.read_requests"
              :href="links.requests || '#'"
              class="platform-action primary"
            >
              <i class="ri-file-list-3-line"></i>
              <span>
                <small>{{ $t('ui-approval-center') }}</small>
                {{ $t('ui-review-requests') }}
              </span>
            </Link>
          </div>
        </div>
      </BCard>

      <div class="global-stats-grid">
        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon companies">
            <i class="ri-building-4-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-total-companies') }}</span>
            <strong>{{ companyStats.total || 0 }}</strong>
            <small>{{ companyStats.active || 0 }} {{ $t('t-active') }}</small>
          </div>

          <Link
            v-if="permissions.read_companies"
            :href="links.companies || '#'"
            class="global-stat-link"
          >
            <i class="ri-arrow-right-line"></i>
          </Link>
        </BCard>

        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon learners">
            <i class="ri-graduation-cap-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-total-learners') }}</span>
            <strong>{{ userStats.learners || 0 }}</strong>
            <small>{{ userStats.admins || 0 }} {{ $t('ui-company-admins') }}</small>
          </div>

          <Link
            v-if="permissions.read_users"
            :href="links.learners || '#'"
            class="global-stat-link"
          >
            <i class="ri-arrow-right-line"></i>
          </Link>
        </BCard>

        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon requests">
            <i class="ri-file-list-3-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-training-requests') }}</span>
            <strong>{{ requestStats.total || 0 }}</strong>
            <small>{{ requestStats.pending || 0 }} {{ $t('t-pending-approval') }}</small>
          </div>

          <Link
            v-if="permissions.read_requests"
            :href="links.requests || '#'"
            class="global-stat-link"
          >
            <i class="ri-arrow-right-line"></i>
          </Link>
        </BCard>

        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon enrollments">
            <i class="ri-bookmark-3-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-course-enrollments') }}</span>
            <strong>{{ enrollmentStats.total || 0 }}</strong>
            <small>{{ enrollmentStats.completed || 0 }} {{ $t('t-completed') }}</small>
          </div>

          <span class="global-stat-link static">
            <i class="ri-bar-chart-line"></i>
          </span>
        </BCard>

        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon courses">
            <i class="ri-book-open-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-course-catalog') }}</span>
            <strong>{{ courseStats.total || 0 }}</strong>
            <small>{{ courseStats.scorm || 0 }} {{ $t('t-scorm-courses') }}</small>
          </div>

          <Link
            v-if="permissions.read_courses"
            :href="links.courses || '#'"
            class="global-stat-link"
          >
            <i class="ri-arrow-right-line"></i>
          </Link>
        </BCard>

        <BCard class="global-stat-card border-0">
          <div class="global-stat-icon certificates">
            <i class="ri-award-line"></i>
          </div>

          <div class="global-stat-content">
            <span>{{ $t('ui-certificates-issued') }}</span>
            <strong>{{ stats.certificates || 0 }}</strong>
            <small>{{ examStats.passed || 0 }} {{ $t('ui-passed-exams') }}</small>
          </div>

          <span class="global-stat-link static">
            <i class="ri-medal-line"></i>
          </span>
        </BCard>
      </div>

      <BRow class="g-4 mb-4">
        <BCol xl="4">
          <BCard class="dashboard-panel border-0 h-100">
            <div class="panel-header">
              <div class="panel-title">
                <div class="panel-icon requests">
                  <i class="ri-file-chart-line"></i>
                </div>

                <div>
                  <h3>{{ $t('ui-request-status') }}</h3>
                  <p>{{ $t('ui-global-approval-workflow') }}</p>
                </div>
              </div>

              <span class="panel-count">{{ requestStats.total || 0 }}</span>
            </div>

            <div class="progress-overview-list">
              <div class="progress-overview-item">
                <div class="progress-overview-head">
                  <span><i class="ri-time-line pending"></i> {{ $t('t-pending') }}</span>
                  <strong>{{ requestStats.pending || 0 }}</strong>
                </div>
                <div class="overview-track">
                  <div
                    class="overview-bar pending"
                    :style="{ width: `${requestPercent(requestStats.pending)}%` }"
                  ></div>
                </div>
              </div>

              <div class="progress-overview-item">
                <div class="progress-overview-head">
                  <span><i class="ri-checkbox-circle-line approved"></i> {{ $t('ui-approved') }}</span>
                  <strong>{{ requestStats.approved || 0 }}</strong>
                </div>
                <div class="overview-track">
                  <div
                    class="overview-bar approved"
                    :style="{ width: `${requestPercent(requestStats.approved)}%` }"
                  ></div>
                </div>
              </div>

              <div class="progress-overview-item">
                <div class="progress-overview-head">
                  <span><i class="ri-close-circle-line rejected"></i> {{ $t('ui-rejected') }}</span>
                  <strong>{{ requestStats.rejected || 0 }}</strong>
                </div>
                <div class="overview-track">
                  <div
                    class="overview-bar rejected"
                    :style="{ width: `${requestPercent(requestStats.rejected)}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </BCard>
        </BCol>

        <BCol xl="4">
          <BCard class="dashboard-panel border-0 h-100">
            <div class="panel-header">
              <div class="panel-title">
                <div class="panel-icon courses">
                  <i class="ri-book-2-line"></i>
                </div>

                <div>
                  <h3>{{ $t('ui-course-catalog') }}</h3>
                  <p>{{ $t('ui-published-and-content-formats') }}</p>
                </div>
              </div>

              <span class="panel-count">{{ courseStats.total || 0 }}</span>
            </div>

            <div class="progress-overview-list">
              <div class="progress-overview-item">
                <div class="progress-overview-head">
                  <span><i class="ri-eye-line approved"></i> {{ $t('ui-published') }}</span>
                  <strong>{{ courseStats.published || 0 }}</strong>
                </div>
                <div class="overview-track">
                  <div
                    class="overview-bar approved"
                    :style="{ width: `${coursePercent(courseStats.published)}%` }"
                  ></div>
                </div>
              </div>

              <div class="progress-overview-item">
                <div class="progress-overview-head">
                  <span><i class="ri-draft-line pending"></i> {{ $t('ui-draft') }}</span>
                  <strong>{{ courseStats.draft || 0 }}</strong>
                </div>
                <div class="overview-track">
                  <div
                    class="overview-bar pending"
                    :style="{ width: `${coursePercent(courseStats.draft)}%` }"
                  ></div>
                </div>
              </div>

              <div class="format-stats-row">
                <div>
                  <i class="ri-slideshow-line"></i>
                  <span>{{ $t('ui-normal') }}</span>
                  <strong>{{ courseStats.normal || 0 }}</strong>
                </div>

                <div>
                  <i class="ri-file-zip-line"></i>
                  <span>{{ $t('ui-scorm') }}</span>
                  <strong>{{ courseStats.scorm || 0 }}</strong>
                </div>
              </div>
            </div>
          </BCard>
        </BCol>

        <BCol xl="4">
          <BCard class="dashboard-panel border-0 h-100">
            <div class="panel-header">
              <div class="panel-title">
                <div class="panel-icon outcomes">
                  <i class="ri-award-line"></i>
                </div>

                <div>
                  <h3>{{ $t('ui-learning-outcomes') }}</h3>
                  <p>{{ $t('ui-exams-and-certification') }}</p>
                </div>
              </div>

              <span class="panel-count">{{ examStats.total || 0 }}</span>
            </div>

            <div class="outcome-grid">
              <div class="outcome-item success">
                <i class="ri-checkbox-circle-line"></i>
                <span>{{ $t('ui-passed') }}</span>
                <strong>{{ examStats.passed || 0 }}</strong>
              </div>

              <div class="outcome-item danger">
                <i class="ri-close-circle-line"></i>
                <span>{{ $t('ui-failed') }}</span>
                <strong>{{ examStats.failed || 0 }}</strong>
              </div>

              <div class="outcome-item info">
                <i class="ri-loader-2-line"></i>
                <span>{{ $t('ui-in-progress') }}</span>
                <strong>{{ examStats.in_progress || 0 }}</strong>
              </div>

              <div class="outcome-item primary">
                <i class="ri-medal-line"></i>
                <span>{{ $t('ui-certificates') }}</span>
                <strong>{{ stats.certificates || 0 }}</strong>
              </div>
            </div>
          </BCard>
        </BCol>
      </BRow>

      <BCard class="dashboard-panel border-0 mb-4">
        <div class="panel-header activity-header">
          <div class="panel-title">
            <div class="panel-icon activity">
              <i class="ri-line-chart-line"></i>
            </div>

            <div>
              <h3>{{ $t('ui-platform-activity') }}</h3>
              <p>{{ $t('ui-new-companies-requests-and-enrollments-over-six-months') }}</p>
            </div>
          </div>

          <div class="activity-legend">
            <span><i class="legend-dot companies"></i> {{ $t('ui-companies') }}</span>
            <span><i class="legend-dot requests"></i> {{ $t('ui-requests') }}</span>
            <span><i class="legend-dot enrollments"></i> {{ $t('ui-enrollments') }}</span>
          </div>
        </div>

        <div v-if="monthlyActivity.length" class="activity-chart">
          <div
            v-for="month in monthlyActivity"
            :key="month.key"
            class="activity-month"
          >
            <div class="activity-bars">
              <div
                class="activity-bar companies"
                :style="{ height: getActivityHeight(month.companies) }"
                :title="`${month.companies} companies`"
              >
                <span>{{ month.companies }}</span>
              </div>

              <div
                class="activity-bar requests"
                :style="{ height: getActivityHeight(month.requests) }"
                :title="`${month.requests} requests`"
              >
                <span>{{ month.requests }}</span>
              </div>

              <div
                class="activity-bar enrollments"
                :style="{ height: getActivityHeight(month.enrollments) }"
                :title="`${month.enrollments} enrollments`"
              >
                <span>{{ month.enrollments }}</span>
              </div>
            </div>

            <strong>{{ month.label }}</strong>
          </div>
        </div>
      </BCard>

      <BCard class="dashboard-panel border-0 mb-4">
        <div class="company-management-header">
          <div class="panel-title">
            <div class="panel-icon companies">
              <i class="ri-building-line"></i>
            </div>

            <div>
              <h3>{{ $t('ui-company-management') }}</h3>
              <p>{{ $t('ui-search-monitor-and-open-every-registered-company') }}</p>
            </div>
          </div>

          <div class="company-controls">
            <div class="company-filter-box">
              <i class="ri-filter-3-line"></i>
              <select v-model="selectedStatus">
                <option value="all">{{ $t('ui-all-statuses') }}</option>
                <option value="active">{{ $t('ui-active') }}</option>
                <option value="inactive">{{ $t('ui-inactive') }}</option>
              </select>
            </div>

            <div class="company-search-box">
              <i class="ri-search-line"></i>
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="$t('ui-search-company')"
              />
            </div>

            <Link
              v-if="permissions.create_company"
              :href="links.create_company || '#'"
              class="add-company-btn"
            >
              <i class="ri-add-line"></i>
              {{ $t('ui-add-company') }}
            </Link>
          </div>
        </div>

        <div v-if="paginatedCompanies.length" class="table-responsive d-none d-lg-block">
          <table class="table company-table align-middle mb-0">
            <thead>
              <tr>
                <th>{{ $t('ui-company') }}</th>
                <th>{{ $t('ui-people') }}</th>
                <th>{{ $t('ui-requests') }}</th>
                <th>{{ $t('ui-training') }}</th>
                <th>{{ $t('ui-average-progress') }}</th>
                <th>{{ $t('ui-status') }}</th>
                <th class="text-center">{{ $t('ui-action') }}</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="company in paginatedCompanies" :key="company.id">
                <td>
                  <div class="company-cell">
                    <div class="company-logo-small">
                      <img
                        v-if="company.logo_url"
                        :src="company.logo_url"
                        :alt="company.name"
                      />
                      <span v-else>{{ getInitials(company.name, 'CO') }}</span>
                    </div>

                    <div>
                      <strong>{{ company.name }}</strong>
                      <span>{{ company.email }}</span>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="metric-stack">
                    <span><i class="ri-user-settings-line"></i> {{ company.admins_count }} {{ $t('ui-admin-s') }}</span>
                    <span><i class="ri-graduation-cap-line"></i> {{ company.learners_count }} {{ $t('ui-learner-s') }}</span>
                  </div>
                </td>

                <td>
                  <div class="metric-stack">
                    <span>{{ company.requests_count }} {{ $t('ui-total') }}</span>
                    <span class="warning-text">{{ company.pending_requests }} {{ $t('ui-pending') }}</span>
                  </div>
                </td>

                <td>
                  <div class="metric-stack">
                    <span>{{ company.enrollments_count }} {{ $t('ui-enrollments') }}</span>
                    <span>{{ company.assigned_courses }} {{ $t('ui-course-s') }}</span>
                  </div>
                </td>

                <td>
                  <div class="company-progress-cell">
                    <div>
                      <span>{{ $t('ui-progress') }}</span>
                      <strong>{{ formatPercent(company.average_progress) }}%</strong>
                    </div>
                    <div class="company-progress-track">
                      <div :style="{ width: `${formatPercent(company.average_progress)}%` }"></div>
                    </div>
                  </div>
                </td>

                <td>
                  <span :class="getCompanyStatusClass(company.status)">
                    {{ company.status || '-' }}
                  </span>
                </td>

                <td class="text-center">
                  <Link
                    v-if="company.action_url"
                    :href="company.action_url"
                    class="company-action-btn"
                    :title="$t('ui-open-company')"
                  >
                    <i class="ri-eye-line"></i>
                  </Link>

                  <span v-else class="company-action-btn disabled">
                    <i class="ri-lock-line"></i>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="paginatedCompanies.length" class="company-mobile-grid d-lg-none">
          <div
            v-for="company in paginatedCompanies"
            :key="company.id"
            class="company-mobile-card"
          >
            <div class="company-mobile-top">
              <div class="company-logo-small">
                <img
                  v-if="company.logo_url"
                  :src="company.logo_url"
                  :alt="company.name"
                />
                <span v-else>{{ getInitials(company.name, 'CO') }}</span>
              </div>

              <div class="company-mobile-heading">
                <h4>{{ company.name }}</h4>
                <p>{{ company.email }}</p>
              </div>

              <span :class="getCompanyStatusClass(company.status)">
                {{ company.status || '-' }}
              </span>
            </div>

            <div class="company-mobile-stats">
              <div><span>{{ $t('ui-learners') }}</span><strong>{{ company.learners_count }}</strong></div>
              <div><span>{{ $t('ui-requests') }}</span><strong>{{ company.requests_count }}</strong></div>
              <div><span>{{ $t('ui-enrollments') }}</span><strong>{{ company.enrollments_count }}</strong></div>
              <div><span>{{ $t('ui-completed') }}</span><strong>{{ company.completed_enrollments }}</strong></div>
            </div>

            <div class="company-progress-cell">
              <div>
                <span>{{ $t('ui-average-progress') }}</span>
                <strong>{{ formatPercent(company.average_progress) }}%</strong>
              </div>
              <div class="company-progress-track">
                <div :style="{ width: `${formatPercent(company.average_progress)}%` }"></div>
              </div>
            </div>

            <Link
              v-if="company.action_url"
              :href="company.action_url"
              class="company-mobile-action"
            >
              {{ $t('ui-open-company') }}
              <i class="ri-arrow-right-line"></i>
            </Link>
          </div>
        </div>

        <div v-if="!paginatedCompanies.length" class="dashboard-empty-state">
          <div class="empty-icon"><i class="ri-building-line"></i></div>
          <h4>{{ $t('ui-no-companies-found') }}</h4>
          <p>{{ $t('ui-no-company-matches-your-search-or-selected-status') }}</p>
        </div>

        <div v-if="filteredCompanies.length" class="company-pagination">
          <span>
            {{ $t('ui-showing') }} {{ paginatedCompanies.length }} {{ $t('ui-of') }} {{ filteredCompanies.length }} {{ $t('t-companies') }}
          </span>

          <div class="pagination-buttons">
            <button
              type="button"
              :disabled="currentPage === 1"
              @click="goToPage(currentPage - 1)"
            >
              <i class="ri-arrow-left-s-line"></i>
            </button>

            <button
              v-for="page in visiblePages"
              :key="page"
              type="button"
              :class="{ active: currentPage === page }"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>

            <button
              type="button"
              :disabled="currentPage === totalPages"
              @click="goToPage(currentPage + 1)"
            >
              <i class="ri-arrow-right-s-line"></i>
            </button>
          </div>
        </div>
      </BCard>

      <BRow class="g-4 mb-4">
        <BCol xl="7">
          <BCard class="dashboard-panel border-0 h-100">
            <div class="panel-header">
              <div class="panel-title">
                <div class="panel-icon requests">
                  <i class="ri-time-line"></i>
                </div>

                <div>
                  <h3>{{ $t('ui-pending-company-requests') }}</h3>
                  <p>{{ $t('ui-requests-waiting-for-super-admin-approval') }}</p>
                </div>
              </div>

              <Link
                v-if="permissions.read_requests"
                :href="links.requests || '#'"
                class="panel-link"
              >
                {{ $t('ui-view-all') }}
                <i class="ri-arrow-right-line"></i>
              </Link>
            </div>

            <div v-if="pendingRequests.length" class="pending-request-list">
              <div
                v-for="request in pendingRequests"
                :key="request.id"
                class="pending-request-item"
              >
                <div class="pending-company-logo">
                  <img
                    v-if="request.company?.logo_url"
                    :src="request.company.logo_url"
                    :alt="request.company.name"
                  />
                  <span v-else>{{ getInitials(request.company?.name, 'CO') }}</span>
                </div>

                <div class="pending-request-content">
                  <div class="pending-request-heading">
                    <div>
                      <span>{{ request.reference }}</span>
                      <h4>{{ request.company?.name || '-' }}</h4>
                    </div>

                    <small>{{ formatDate(request.created_at) }}</small>
                  </div>

                  <p>{{ request.course?.title || 'Course unavailable' }}</p>

                  <div class="pending-request-meta">
                    <span><i class="ri-team-line"></i> {{ request.learners_count }} {{ $t('ui-learners-label') }}</span>
                    <span><i class="ri-user-line"></i> {{ request.requested_by?.name || '-' }}</span>
                    <span>
                      <i class="ri-calendar-check-line"></i>
                      {{ formatValidityMonths(request.duree_validite_formation) }}
                    </span>
                    <span v-if="request.course?.content_type === 'scorm'" class="scorm-tag">{{ $t('ui-scorm') }}</span>
                  </div>
                </div>

                <Link
                  :href="route('company-requests.show', request.hashid)"
                  class="pending-request-action"
                  :title="$t('t-step-review-request')"
                >
                  <i class="ri-eye-line"></i>
                </Link>
              </div>
            </div>

            <div v-else class="dashboard-empty-state compact">
              <div class="empty-icon"><i class="ri-checkbox-circle-line"></i></div>
              <h4>{{ $t('ui-no-pending-requests') }}</h4>
              <p>{{ $t('ui-all-company-requests-have-been-processed') }}</p>
            </div>
          </BCard>
        </BCol>

        <BCol xl="5">
          <BCard class="dashboard-panel border-0 h-100">
            <div class="panel-header">
              <div class="panel-title">
                <div class="panel-icon outcomes">
                  <i class="ri-trophy-line"></i>
                </div>

                <div>
                  <h3>{{ $t('ui-top-companies') }}</h3>
                  <p>{{ $t('ui-ranked-by-course-enrollments') }}</p>
                </div>
              </div>
            </div>

            <div v-if="topCompanies.length" class="top-company-list">
              <div
                v-for="(company, index) in topCompanies"
                :key="company.id"
                class="top-company-item"
              >
                <span class="company-rank">{{ index + 1 }}</span>

                <div class="company-logo-small">
                  <img
                    v-if="company.logo_url"
                    :src="company.logo_url"
                    :alt="company.name"
                  />
                  <span v-else>{{ getInitials(company.name, 'CO') }}</span>
                </div>

                <div class="top-company-content">
                  <div>
                    <h4>{{ company.name }}</h4>
                    <span>{{ company.learners_count }} {{ $t('ui-learners-label') }}</span>
                  </div>

                  <div class="top-company-numbers">
                    <strong>{{ company.enrollments_count }}</strong>
                    <small>{{ $t('ui-enrollments') }}</small>
                  </div>
                </div>

                <div class="top-company-progress">
                  <div :style="{ width: `${formatPercent(company.completion_rate)}%` }"></div>
                </div>
              </div>
            </div>

            <div v-else class="dashboard-empty-state compact">
              <div class="empty-icon"><i class="ri-trophy-line"></i></div>
              <h4>{{ $t('ui-no-company-activity') }}</h4>
              <p>{{ $t('ui-no-enrollments-are-available-for-ranking') }}</p>
            </div>
          </BCard>
        </BCol>
      </BRow>

      <BCard class="dashboard-panel border-0">
        <div class="panel-header">
          <div class="panel-title">
            <div class="panel-icon activity">
              <i class="ri-bookmark-3-line"></i>
            </div>

            <div>
              <h3>{{ $t('ui-recent-course-assignments') }}</h3>
              <p>{{ $t('ui-latest-enrollments-across-all-companies') }}</p>
            </div>
          </div>

          <span class="panel-count">{{ enrollmentStats.total || 0 }}</span>
        </div>

        <div v-if="recentEnrollments.length" class="enrollment-grid">
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
              <span v-else>{{ getInitials(enrollment.course?.title, 'CO') }}</span>

              <small v-if="enrollment.course?.content_type === 'scorm'">{{ $t('ui-scorm') }}</small>
            </div>

            <div class="enrollment-card-body">
              <div class="enrollment-card-top">
                <span :class="getEnrollmentStatusClass(enrollment.status)">
                  {{ getEnrollmentStatusLabel(enrollment.status) }}
                </span>
                <small>{{ formatDate(enrollment.created_at) }}</small>
              </div>

              <h4>{{ enrollment.course?.title || '-' }}</h4>

              <div class="enrollment-company-row">
                <i class="ri-building-line"></i>
                <span>{{ enrollment.company?.name || '-' }}</span>
              </div>

              <div class="enrollment-learner-row">
                <div class="learner-avatar-small">
                  <img
                    v-if="enrollment.learner?.photo_url"
                    :src="enrollment.learner.photo_url"
                    :alt="enrollment.learner.name"
                  />
                  <span v-else>{{ getInitials(enrollment.learner?.name, 'LR') }}</span>
                </div>

                <div>
                  <strong>{{ enrollment.learner?.name || '-' }}</strong>
                  <small>{{ enrollment.learner?.email || '-' }}</small>
                </div>
              </div>

              <div class="enrollment-progress-head">
                <span>{{ $t('ui-progress') }}</span>
                <strong>{{ formatPercent(enrollment.progress_percent) }}%</strong>
              </div>

              <div class="company-progress-track">
                <div :style="{ width: `${formatPercent(enrollment.progress_percent)}%` }"></div>
              </div>

              <div class="enrollment-validity-row">
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

        <div v-else class="dashboard-empty-state">
          <div class="empty-icon"><i class="ri-bookmark-line"></i></div>
          <h4>{{ $t('ui-no-enrollments-found') }}</h4>
          <p>{{ $t('ui-no-company-learner-has-been-assigned-a-course-yet') }}</p>
        </div>
      </BCard>
    </section>
  </Layout>
</template>

<style scoped>
.super-admin-dashboard {
  --primary: #687cfe;
  --dark: #405189;
  --success: #0ab39c;
  --warning: #f7b84b;
  --danger: #f06548;
  --info: #299cdb;
  padding-bottom: 32px;
}

.platform-hero {
  overflow: hidden;
  border-radius: 24px;
  margin-bottom: 24px;
  color: #ffffff;
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 34%),
    linear-gradient(135deg, #1f2d5c 0%, #405189 44%, #687cfe 100%);
  box-shadow: 0 20px 45px rgba(64, 81, 137, 0.24);
}

.platform-hero :deep(.card-body) {
  padding: 0;
}

.platform-hero-content {
  min-height: 280px;
  padding: 34px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 36px;
}

.platform-intro {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 24px;
}

.platform-hero-icon {
  width: 112px;
  height: 112px;
  min-width: 112px;
  border-radius: 30px;
  color: #405189;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 18px 36px rgba(20, 29, 70, 0.24);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.platform-hero-icon i {
  font-size: 48px;
}

.platform-kicker {
  width: fit-content;
  padding: 7px 11px;
  border-radius: 999px;
  color: #405189;
  background: rgba(255, 255, 255, 0.93);
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
}

.platform-intro h1 {
  max-width: 760px;
  color: #ffffff;
  font-size: 36px;
  line-height: 1.18;
  font-weight: 900;
  margin-bottom: 12px;
}

.platform-intro p {
  max-width: 730px;
  color: rgba(255, 255, 255, 0.86);
  font-size: 14px;
  line-height: 1.7;
  margin-bottom: 15px;
}

.platform-summary-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
}

.platform-summary-chips span {
  padding: 7px 10px;
  border-radius: 999px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.12);
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.platform-actions {
  width: 300px;
  min-width: 300px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.platform-action {
  min-height: 62px;
  padding: 10px 14px;
  border-radius: 16px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.platform-action:hover {
  transform: translateY(-2px);
}

.platform-action > i {
  width: 40px;
  height: 40px;
  min-width: 40px;
  border-radius: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.platform-action span {
  font-size: 13px;
  font-weight: 900;
}

.platform-action small {
  display: block;
  margin-bottom: 2px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  opacity: 0.7;
}

.platform-action.primary {
  color: #405189;
  background: #ffffff;
  box-shadow: 0 12px 28px rgba(20, 28, 70, 0.18);
}

.platform-action.primary > i {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
}

.platform-action.dark {
  color: #ffffff;
  background: rgba(7, 17, 38, 0.32);
}

.platform-action.dark > i {
  color: #405189;
  background: #ffffff;
}

.platform-action.light {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.14);
}

.platform-action.light > i {
  background: rgba(255, 255, 255, 0.18);
}

.global-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.global-stat-card {
  position: relative;
  overflow: hidden;
  min-height: 135px;
  border-radius: 20px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.global-stat-card :deep(.card-body) {
  padding: 21px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.global-stat-icon {
  width: 58px;
  height: 58px;
  min-width: 58px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
}

.global-stat-icon.companies {
  color: #687cfe;
  background: rgba(104, 124, 254, 0.12);
}

.global-stat-icon.learners {
  color: #299cdb;
  background: rgba(41, 156, 219, 0.12);
}

.global-stat-icon.requests {
  color: #f7b84b;
  background: rgba(247, 184, 75, 0.15);
}

.global-stat-icon.enrollments {
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.12);
}

.global-stat-icon.courses {
  color: #405189;
  background: rgba(64, 81, 137, 0.11);
}

.global-stat-icon.certificates {
  color: #f06548;
  background: rgba(240, 101, 72, 0.11);
}

.global-stat-content {
  flex: 1;
  min-width: 0;
}

.global-stat-content span,
.global-stat-content small {
  display: block;
}

.global-stat-content span {
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  margin-bottom: 5px;
}

.global-stat-content strong {
  display: block;
  color: #111827;
  font-size: 30px;
  line-height: 1;
  font-weight: 900;
  margin-bottom: 7px;
}

.global-stat-content small {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.global-stat-link {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 11px;
  color: #687cfe;
  background: rgba(104, 124, 254, 0.09);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.global-stat-link.static {
  color: #94a3b8;
  background: #f1f5f9;
}

.dashboard-panel {
  overflow: hidden;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 13px 32px rgba(15, 23, 42, 0.07);
}

.dashboard-panel :deep(.card-body) {
  padding: 23px;
}

.panel-header,
.company-management-header {
  margin-bottom: 21px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.panel-title {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.panel-icon {
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

.panel-icon.requests {
  background: linear-gradient(135deg, #f7b84b, #d68d16);
  box-shadow: 0 9px 20px rgba(247, 184, 75, 0.22);
}

.panel-icon.courses,
.panel-icon.companies {
  background: linear-gradient(135deg, #687cfe, #405189);
  box-shadow: 0 9px 20px rgba(104, 124, 254, 0.22);
}

.panel-icon.outcomes {
  background: linear-gradient(135deg, #0ab39c, #087f6f);
  box-shadow: 0 9px 20px rgba(10, 179, 156, 0.22);
}

.panel-icon.activity {
  background: linear-gradient(135deg, #299cdb, #405189);
  box-shadow: 0 9px 20px rgba(41, 156, 219, 0.22);
}

.panel-title h3 {
  color: #111827;
  font-size: 18px;
  font-weight: 900;
  margin-bottom: 3px;
}

.panel-title p {
  color: #64748b;
  font-size: 11px;
  margin-bottom: 0;
}

.panel-count {
  padding: 7px 11px;
  border-radius: 999px;
  color: #405189;
  background: rgba(104, 124, 254, 0.1);
  font-size: 10px;
  font-weight: 900;
}

.panel-link {
  color: #687cfe;
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.progress-overview-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.progress-overview-head {
  margin-bottom: 7px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.progress-overview-head span {
  color: #475569;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.progress-overview-head strong {
  color: #111827;
  font-size: 13px;
  font-weight: 900;
}

.progress-overview-head i.pending { color: #f7b84b; }
.progress-overview-head i.approved { color: #0ab39c; }
.progress-overview-head i.rejected { color: #f06548; }

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

.overview-bar.pending { background: linear-gradient(90deg, #f7b84b, #d68d16); }
.overview-bar.approved { background: linear-gradient(90deg, #0ab39c, #087f6f); }
.overview-bar.rejected { background: linear-gradient(90deg, #f06548, #c2412d); }

.format-stats-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.format-stats-row > div {
  padding: 13px;
  border: 1px solid #edf2f7;
  border-radius: 15px;
  background: #f8fafc;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 8px;
}

.format-stats-row i { color: #687cfe; font-size: 18px; }
.format-stats-row span { color: #64748b; font-size: 11px; font-weight: 800; }
.format-stats-row strong { color: #111827; font-size: 16px; font-weight: 900; }

.outcome-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.outcome-item {
  min-height: 94px;
  padding: 14px;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
}

.outcome-item i { font-size: 22px; margin-bottom: 7px; }
.outcome-item span { font-size: 10px; font-weight: 800; margin-bottom: 3px; }
.outcome-item strong { font-size: 22px; font-weight: 900; }
.outcome-item.success { color: #087f6f; background: rgba(10, 179, 156, 0.1); }
.outcome-item.danger { color: #c2412d; background: rgba(240, 101, 72, 0.1); }
.outcome-item.info { color: #1878aa; background: rgba(41, 156, 219, 0.1); }
.outcome-item.primary { color: #405189; background: rgba(104, 124, 254, 0.1); }

.activity-header {
  align-items: flex-start;
}

.activity-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.activity-legend span {
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.legend-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
}

.legend-dot.companies { background: #687cfe; }
.legend-dot.requests { background: #f7b84b; }
.legend-dot.enrollments { background: #0ab39c; }

.activity-chart {
  min-height: 240px;
  padding: 18px 10px 0;
  border-radius: 17px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  align-items: end;
  gap: 14px;
}

.activity-month {
  height: 210px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 9px;
}

.activity-bars {
  width: 100%;
  height: 175px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 6px;
}

.activity-bar {
  width: 20%;
  min-width: 12px;
  max-width: 25px;
  border-radius: 8px 8px 3px 3px;
  position: relative;
  transition: height 0.3s ease;
}

.activity-bar span {
  position: absolute;
  top: -17px;
  left: 50%;
  transform: translateX(-50%);
  color: #64748b;
  font-size: 8px;
  font-weight: 900;
}

.activity-bar.companies { background: linear-gradient(180deg, #8b9afe, #687cfe); }
.activity-bar.requests { background: linear-gradient(180deg, #ffd178, #f7b84b); }
.activity-bar.enrollments { background: linear-gradient(180deg, #39cdb7, #0ab39c); }
.activity-month > strong { color: #64748b; font-size: 9px; font-weight: 900; }

.company-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.company-filter-box,
.company-search-box {
  position: relative;
}

.company-filter-box i,
.company-search-box i {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.company-filter-box select,
.company-search-box input {
  height: 42px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #ffffff;
  color: #334155;
  outline: none;
  padding-left: 38px;
  padding-right: 12px;
}

.company-filter-box select { min-width: 145px; }
.company-search-box input { min-width: 230px; }

.company-filter-box select:focus,
.company-search-box input:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.18rem rgba(104, 124, 254, 0.13);
}

.add-company-btn {
  height: 42px;
  padding: 0 14px;
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  box-shadow: 0 8px 18px rgba(104, 124, 254, 0.2);
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.add-company-btn:hover { color: #ffffff; }

.company-table thead th {
  padding: 11px 10px;
  border-bottom: none;
  color: #64748b;
  background: #f8fafc;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  white-space: nowrap;
}

.company-table tbody td {
  padding: 13px 10px;
  border-color: #f1f5f9;
}

.company-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.company-logo-small,
.company-logo-small img,
.company-logo-small > span {
  width: 43px;
  height: 43px;
  min-width: 43px;
  border-radius: 13px;
}

.company-logo-small {
  overflow: hidden;
  background: #eef2ff;
}

.company-logo-small img { object-fit: contain; }
.company-logo-small > span {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 900;
}

.company-cell strong,
.company-cell span {
  display: block;
  max-width: 190px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.company-cell strong { color: #334155; font-size: 11px; font-weight: 900; }
.company-cell span { color: #94a3b8; font-size: 9px; margin-top: 2px; }

.metric-stack {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.metric-stack span {
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.metric-stack i { color: #687cfe; }
.metric-stack .warning-text { color: #d68d16; }

.company-progress-cell {
  min-width: 135px;
}

.company-progress-cell > div:first-child,
.enrollment-progress-head {
  margin-bottom: 5px;
  display: flex;
  justify-content: space-between;
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
}

.company-progress-cell strong,
.enrollment-progress-head strong { color: #405189; }

.company-progress-track {
  height: 6px;
  border-radius: 999px;
  overflow: hidden;
  background: #edf2f7;
}

.company-progress-track div {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #687cfe, #405189);
}

.company-status,
.status-pill {
  padding: 6px 9px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 900;
  text-transform: capitalize;
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.company-status.active,
.status-pill.completed { color: #087f6f; background: rgba(10, 179, 156, 0.11); }
.company-status.inactive,
.status-pill.rejected { color: #c2412d; background: rgba(240, 101, 72, 0.11); }
.status-pill.in-progress { color: #1878aa; background: rgba(41, 156, 219, 0.11); }
.status-pill.pending { color: #b57208; background: rgba(247, 184, 75, 0.15); }
.status-pill.default { color: #64748b; background: #f1f5f9; }

.company-action-btn {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  color: #687cfe;
  background: rgba(104, 124, 254, 0.09);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.company-action-btn.disabled { color: #94a3b8; background: #f1f5f9; }

.company-mobile-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 13px;
}

.company-mobile-card {
  padding: 14px;
  border: 1px solid #edf2f7;
  border-radius: 17px;
}

.company-mobile-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.company-mobile-heading { flex: 1; min-width: 0; }
.company-mobile-heading h4,
.company-mobile-heading p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.company-mobile-heading h4 { color: #334155; font-size: 12px; font-weight: 900; margin-bottom: 2px; }
.company-mobile-heading p { color: #94a3b8; font-size: 9px; margin-bottom: 0; }

.company-mobile-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 7px;
  margin-bottom: 13px;
}

.company-mobile-stats div {
  padding: 8px;
  border-radius: 11px;
  background: #f8fafc;
  text-align: center;
}

.company-mobile-stats span { display: block; color: #94a3b8; font-size: 7px; font-weight: 800; }
.company-mobile-stats strong { color: #334155; font-size: 13px; font-weight: 900; }

.company-mobile-action {
  margin-top: 13px;
  height: 38px;
  border-radius: 11px;
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  text-decoration: none;
  font-size: 10px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.company-pagination {
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.company-pagination > span { color: #64748b; font-size: 10px; font-weight: 700; }
.pagination-buttons { display: flex; gap: 5px; }
.pagination-buttons button {
  width: 33px;
  height: 33px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #ffffff;
  color: #64748b;
  font-size: 10px;
  font-weight: 900;
}
.pagination-buttons button.active { color: #ffffff; border-color: #687cfe; background: #687cfe; }
.pagination-buttons button:disabled { opacity: 0.45; cursor: not-allowed; }

.pending-request-list,
.top-company-list {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.pending-request-item {
  padding: 12px;
  border: 1px solid #edf2f7;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 11px;
}

.pending-company-logo,
.pending-company-logo img,
.pending-company-logo span {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
}

.pending-company-logo { overflow: hidden; background: #eef2ff; }
.pending-company-logo img { object-fit: contain; }
.pending-company-logo span {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 900;
}

.pending-request-content { flex: 1; min-width: 0; }
.pending-request-heading { display: flex; justify-content: space-between; gap: 10px; }
.pending-request-heading span { color: #687cfe; font-size: 8px; font-weight: 900; }
.pending-request-heading h4 { color: #334155; font-size: 12px; font-weight: 900; margin: 2px 0 0; }
.pending-request-heading small { color: #94a3b8; font-size: 8px; white-space: nowrap; }
.pending-request-content > p { color: #64748b; font-size: 10px; margin: 5px 0 7px; }
.pending-request-meta { display: flex; flex-wrap: wrap; gap: 6px; }
.pending-request-meta span {
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
.pending-request-meta .scorm-tag { color: #405189; background: rgba(104, 124, 254, 0.1); }
.pending-request-action {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 11px;
  color: #687cfe;
  background: rgba(104, 124, 254, 0.09);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.top-company-item {
  position: relative;
  padding: 11px 11px 15px;
  border: 1px solid #edf2f7;
  border-radius: 15px;
  display: flex;
  align-items: center;
  gap: 9px;
  overflow: hidden;
}

.company-rank {
  width: 25px;
  height: 25px;
  min-width: 25px;
  border-radius: 8px;
  color: #405189;
  background: rgba(104, 124, 254, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  font-weight: 900;
}

.top-company-content {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.top-company-content h4 { color: #334155; font-size: 11px; font-weight: 900; margin-bottom: 2px; }
.top-company-content span { color: #94a3b8; font-size: 8px; }
.top-company-numbers { text-align: right; }
.top-company-numbers strong { display: block; color: #405189; font-size: 15px; font-weight: 900; }
.top-company-numbers small { display: block; color: #94a3b8; font-size: 7px; }
.top-company-progress {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4px;
  background: #edf2f7;
}
.top-company-progress div { height: 100%; background: linear-gradient(90deg, #0ab39c, #087f6f); }

.enrollment-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
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
  height: 105px;
  overflow: hidden;
  background: #eef2ff;
}

.enrollment-cover img,
.enrollment-cover > span {
  width: 100%;
  height: 100%;
}

.enrollment-cover img { object-fit: cover; }
.enrollment-cover > span {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe, #405189);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 25px;
  font-weight: 900;
}

.enrollment-cover small {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 5px 7px;
  border-radius: 999px;
  color: #ffffff;
  background: rgba(7, 17, 38, 0.82);
  font-size: 7px;
  font-weight: 900;
}

.enrollment-card-body { padding: 13px; }
.enrollment-card-top { display: flex; align-items: center; justify-content: space-between; gap: 7px; margin-bottom: 8px; }
.enrollment-card-top > small { color: #94a3b8; font-size: 7px; }
.enrollment-card-body > h4 {
  min-height: 34px;
  color: #334155;
  font-size: 12px;
  line-height: 1.35;
  font-weight: 900;
  margin-bottom: 8px;
}

.enrollment-company-row {
  margin-bottom: 9px;
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 5px;
}
.enrollment-company-row i { color: #687cfe; }

.enrollment-learner-row {
  margin-bottom: 11px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.learner-avatar-small,
.learner-avatar-small img,
.learner-avatar-small span {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 50%;
}

.learner-avatar-small img { object-fit: cover; }
.learner-avatar-small span {
  color: #405189;
  background: rgba(104, 124, 254, 0.11);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 8px;
  font-weight: 900;
}

.enrollment-learner-row strong,
.enrollment-learner-row small {
  display: block;
  max-width: 170px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.enrollment-learner-row strong { color: #475569; font-size: 9px; font-weight: 900; }
.enrollment-learner-row small { color: #94a3b8; font-size: 7px; }

.enrollment-validity-row {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.enrollment-validity-row span {
  padding: 5px 7px;
  border-radius: 9px;
  color: #64748b;
  background: #f8fafc;
  font-size: 8px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.enrollment-validity-row i {
  color: #687cfe;
}

.dashboard-empty-state {
  min-height: 220px;
  padding: 25px;
  border-radius: 18px;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.dashboard-empty-state.compact { min-height: 270px; }
.empty-icon {
  width: 60px;
  height: 60px;
  border-radius: 19px;
  color: #687cfe;
  background: rgba(104, 124, 254, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
  margin-bottom: 11px;
}
.dashboard-empty-state h4 { color: #334155; font-size: 15px; font-weight: 900; margin-bottom: 5px; }
.dashboard-empty-state p { color: #94a3b8; font-size: 11px; margin-bottom: 0; }

@media (max-width: 1399.98px) {
  .global-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .enrollment-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 1199.98px) {
  .platform-hero-content { align-items: flex-start; flex-direction: column; }
  .platform-actions { width: 100%; min-width: 100%; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .company-management-header { align-items: flex-start; flex-direction: column; }
  .company-controls { width: 100%; }
  .company-search-box { flex: 1; }
  .company-search-box input { width: 100%; }
  .enrollment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 991.98px) {
  .company-mobile-grid { grid-template-columns: 1fr; }
}

@media (max-width: 767.98px) {
  .platform-hero-content { padding: 24px; }
  .platform-intro { align-items: flex-start; flex-direction: column; }
  .platform-hero-icon { width: 88px; height: 88px; min-width: 88px; border-radius: 23px; }
  .platform-hero-icon i { font-size: 38px; }
  .platform-intro h1 { font-size: 27px; }
  .platform-actions { grid-template-columns: 1fr; }
  .global-stats-grid { grid-template-columns: 1fr; }
  .activity-header { flex-direction: column; }
  .activity-chart { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: 20px; }
  .company-controls { align-items: stretch; flex-direction: column; }
  .company-filter-box select,
  .company-search-box input,
  .add-company-btn { width: 100%; min-width: 100%; }
  .add-company-btn { justify-content: center; }
  .company-pagination { align-items: flex-start; flex-direction: column; }
  .enrollment-grid { grid-template-columns: 1fr; }
}

@media (max-width: 575.98px) {
  .platform-hero,
  .global-stat-card,
  .dashboard-panel { border-radius: 17px; }
  .dashboard-panel :deep(.card-body) { padding: 17px; }
  .global-stat-card :deep(.card-body) { padding: 17px; }
  .panel-header { align-items: flex-start; flex-direction: column; }
  .activity-chart { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .company-mobile-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .pending-request-item { align-items: flex-start; }
  .pending-request-action { margin-left: auto; }
}
</style>
