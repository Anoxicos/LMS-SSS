<script setup>

import { computed, ref } from 'vue'

import { Head, router } from '@inertiajs/vue3'

import VueApexCharts from 'vue3-apexcharts'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import PageHeader from '@/Components/page-header.vue'

import defaultImage from '@assets/images/default-company.jpg'

const props = defineProps({

  company: {

    type: Object,

    required: true,

  },

  stats: {

    type: Object,

    required: true,

  },

  monthlyActivity: {

    type: Array,

    default: () => [],

  },

  availableYears: {

    type: Array,

    default: () => [],

  },

  trainingPeriod: {

    type: Object,

    default: () => ({

      mode: 'last_12_months',

      selected_year: null,

    }),

  },

  monthlyRequests: {

    type: Array,

    default: () => [],

  },

  recentRequests: {

    type: Array,

    default: () => [],

  },

  courseStats: {

    type: Array,

    default: () => [],

  },

  recentCertificates: {

    type: Array,

    default: () => [],

  },

})

const companyLogo = computed(() => {

  if (props.company.logo_url) return props.company.logo_url

  if (props.company.logo) return `/storage/${props.company.logo}`

  return defaultImage

})

const formatDate = date => {

  if (!date) return '-'

  return new Date(date).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {

    year: 'numeric',

    month: 'short',

    day: '2-digit',

  })

}

const formatPercent = value => {

  const number = Number(value || 0)

  return `${number.toFixed(Number.isInteger(number) ? 0 : 1)}%`

}

const clampPercent = value => {

  const number = Number(value || 0)

  return Math.min(Math.max(number, 0), 100)

}

const statusClass = status => {

  const value = String(status || '').toLowerCase()

  if (['approved', 'completed', 'active', 'passed'].includes(value)) {

    return 'bg-success-subtle text-success'

  }

  if (['rejected', 'failed', 'inactive'].includes(value)) {

    return 'bg-danger-subtle text-danger'

  }

  if (['pending', 'not_started'].includes(value)) {

    return 'bg-warning-subtle text-warning'

  }

  if (value === 'in_progress') {

    return 'bg-info-subtle text-info'

  }

  return 'bg-secondary-subtle text-secondary'

}

const goBack = () => {
  router.get(route('companies.index'))
}

/*
|--------------------------------------------------------------------------
| Company enrollment Excel export
|--------------------------------------------------------------------------
*/

const showEnrollmentExportModal = ref(false)
const exportStartDate = ref('')
const exportEndDate = ref('')
const exportError = ref('')

const todayDate = new Date().toISOString().slice(0, 10)

const openEnrollmentExportModal = () => {
  exportError.value = ''

  if (!exportStartDate.value) {
    const now = new Date()

    exportStartDate.value = [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      '01',
    ].join('-')
  }

  if (!exportEndDate.value) {
    exportEndDate.value = todayDate
  }

  showEnrollmentExportModal.value = true
}

const closeEnrollmentExportModal = () => {
  showEnrollmentExportModal.value = false
  exportError.value = ''
}

const downloadEnrollmentExport = () => {
  exportError.value = ''

  if (!exportStartDate.value || !exportEndDate.value) {
    exportError.value = 'Please select a start date and an end date.'
    return
  }

  if (exportEndDate.value < exportStartDate.value) {
    exportError.value = 'The end date must be after or equal to the start date.'
    return
  }

  window.location.href = route(
    'companies.enrollments.export',
    {
      hashid: props.company.hashid,
      start_date: exportStartDate.value,
      end_date: exportEndDate.value,
    }
  )

  showEnrollmentExportModal.value = false
}


const selectedTrainingYear = ref(

  props.trainingPeriod?.selected_year || ''

)

const changeTrainingPeriod = () => {

  const params = {}

  if (selectedTrainingYear.value) {

    params.year = selectedTrainingYear.value

  }

  router.get(

    route('companies.show', props.company.hashid),

    params,

    {

      preserveScroll: true,

      preserveState: true,

      replace: true,

    }

  )

}

const trainingChartCategories = computed(() =>

  props.monthlyActivity.map(item =>

    props.trainingPeriod?.mode === 'year'

      ? item.label

      : (item.full_label || item.label)

  )

)

const statCards = computed(() => [

  {

    label: t('ui-enrolled-learners'),

    value: props.stats.learners_enrolled_count || 0,

    helper: `${formatPercent(props.stats.training_coverage_rate)} ${t('ui-of-company-users')}`,

    icon: 'ri-user-star-line',

    tone: 'primary',

  },

  {

    label: t('ui-total-enrollments'),

    value: props.stats.enrollments_count || 0,

    helper: `${props.stats.assigned_courses_count || 0} ${t('ui-assigned-course-s')}`,

    icon: 'ri-graduation-cap-line',

    tone: 'blue',

  },

  {

    label: t('ui-in-progress'),

    value: props.stats.in_progress_enrollments_count || 0,

    helper: `${formatPercent(props.stats.average_progress)} ${t('ui-average-progress')}`,

    icon: 'ri-loader-4-line',

    tone: 'info',

  },

  {

    label: t('ui-completion-rate'),

    value: formatPercent(props.stats.completion_rate),

    helper: `${props.stats.completed_enrollments_count || 0} ${t('ui-completed-enrollment-s')}`,

    icon: 'ri-checkbox-circle-line',

    tone: 'success',

  },

  {

    label: t('ui-certificates-issued'),

    value: props.stats.certificates_issued_count || 0,

    helper: `${props.stats.accreditations_used_count || 0} ${t('ui-accreditation-s-used')}`,

    icon: 'ri-award-line',

    tone: 'success',

  },

  {

    label: t('ui-exam-pass-rate'),

    value: formatPercent(props.stats.exam_pass_rate),

    helper: `${props.stats.exam_attempts_count || 0} ${t('ui-submitted-attempt-s')}`,

    icon: 'ri-medal-line',

    tone: 'purple',

  },

  {

    label: t('ui-expiring-soon'),

    value: props.stats.expiring_soon_enrollments_count || 0,

    helper: t('ui-within-the-next-30-days'),

    icon: 'ri-timer-line',

    tone: 'warning',

  },

  {

    label: t('ui-low-progress'),

    value: props.stats.low_progress_enrollments_count || 0,

    helper: t('ui-in-progress-below-50'),

    icon: 'ri-error-warning-line',

    tone: 'danger',

  },

])

const managementAlerts = computed(() => [

  {

    label: t('ui-pending-requests'),

    description: t('ui-training-requests-waiting-for-action'),

    value: props.stats.pending_requests_count || 0,

    icon: 'ri-time-line',

    tone: Number(props.stats.pending_requests_count || 0) > 0 ? 'warning' : 'success',

  },

  {

    label: t('ui-low-progress-label'),

    description: t('ui-active-enrollments-below-50-progress'),

    value: props.stats.low_progress_enrollments_count || 0,

    icon: 'ri-speed-line',

    tone: Number(props.stats.low_progress_enrollments_count || 0) > 0 ? 'danger' : 'success',

  },

  {

    label: t('ui-expiring-soon-label'),

    description: t('ui-enrollments-expiring-in-the-next-30-days'),

    value: props.stats.expiring_soon_enrollments_count || 0,

    icon: 'ri-timer-line',

    tone: Number(props.stats.expiring_soon_enrollments_count || 0) > 0 ? 'warning' : 'success',

  },

  {

    label: t('ui-expired'),

    description: t('ui-enrollments-whose-access-period-has-expired'),

    value: props.stats.expired_enrollments_count || 0,

    icon: 'ri-calendar-close-line',

    tone: Number(props.stats.expired_enrollments_count || 0) > 0 ? 'danger' : 'success',

  },

])

const trainingActivitySeries = computed(() => [

  {

    name: t('ui-enrollments'),

    data: props.monthlyActivity.map(item => Number(item.enrollments || 0)),

  },

  {

    name: t('ui-learners'),

    data: props.monthlyActivity.map(item => Number(item.learners || 0)),

  },

  {

    name: t('ui-completed'),

    data: props.monthlyActivity.map(item => Number(item.completed || 0)),

  },

  {

    name: t('ui-certificates'),

    data: props.monthlyActivity.map(item => Number(item.certificates || 0)),

  },

])

const trainingActivityOptions = computed(() => ({

  chart: {

    type: 'area',

    toolbar: { show: false },

    zoom: { enabled: false },

    fontFamily: 'inherit',

  },

  colors: ['#0D8FE9', '#37CCED', '#0AB39C', '#6559CC'],

  dataLabels: {

    enabled: false,

  },

  stroke: {

    curve: 'smooth',

    width: 3,

  },

  markers: {

    size: 0,

    hover: {

      sizeOffset: 4,

    },

  },

  fill: {

    type: 'gradient',

    gradient: {

      shadeIntensity: 1,

      opacityFrom: 0.2,

      opacityTo: 0.02,

      stops: [0, 90, 100],

    },

  },

  grid: {

    borderColor: '#E4EDF4',

    strokeDashArray: 4,

    padding: {

      left: 5,

      right: 10,

    },

  },

  xaxis: {

    categories: trainingChartCategories.value,

    axisBorder: { show: false },

    axisTicks: { show: false },

    labels: {

      style: {

        colors: '#8A94A6',

        fontSize: '11px',

      },

    },

  },

  yaxis: {

    min: 0,

    forceNiceScale: true,

    labels: {

      formatter: value => Math.round(value),

      style: {

        colors: '#8A94A6',

      },

    },

  },

  legend: {

    position: 'top',

    horizontalAlign: 'right',

    fontSize: '12px',

  },

  tooltip: {

    shared: true,

    intersect: false,

  },

  noData: {

    text: t('ui-no-training-activity-available'),

  },

}))

const requestChartSeries = computed(() => [

  {

    name: t('ui-approved'),

    data: props.monthlyRequests.map(item => Number(item.approved || 0)),

  },

  {

    name: t('ui-pending-label'),

    data: props.monthlyRequests.map(item => Number(item.pending || 0)),

  },

  {

    name: t('ui-rejected'),

    data: props.monthlyRequests.map(item => Number(item.rejected || 0)),

  },

])

const requestChartOptions = computed(() => ({

  chart: {

    type: 'bar',

    stacked: true,

    toolbar: { show: false },

    fontFamily: 'inherit',

  },

  colors: ['#0AB39C', '#F7B84B', '#F06548'],

  plotOptions: {

    bar: {

      borderRadius: 5,

      columnWidth: '48%',

    },

  },

  dataLabels: {

    enabled: false,

  },

  grid: {

    borderColor: '#E4EDF4',

    strokeDashArray: 4,

  },

  xaxis: {

    categories: props.monthlyRequests.map(item => item.label),

    axisBorder: { show: false },

    axisTicks: { show: false },

    labels: {

      rotate: -35,

      style: {

        colors: '#8A94A6',

        fontSize: '10px',

      },

    },

  },

  yaxis: {

    min: 0,

    forceNiceScale: true,

    labels: {

      formatter: value => Math.round(value),

      style: {

        colors: '#8A94A6',

      },

    },

  },

  legend: {

    position: 'top',

    horizontalAlign: 'right',

    fontSize: '12px',

  },

  tooltip: {

    shared: true,

    intersect: false,

  },

  noData: {

    text: t('ui-no-company-request-activity-available'),

  },

}))

const enrollmentStatusSeries = computed(() => [

  Number(props.stats.not_started_enrollments_count || 0),

  Number(props.stats.in_progress_enrollments_count || 0),

  Number(props.stats.completed_enrollments_count || 0),

])

const enrollmentStatusOptions = computed(() => ({

  chart: {

    type: 'donut',

    fontFamily: 'inherit',

  },

  labels: ['Not Started', 'In Progress', t('ui-completed')],

  colors: ['#F7B84B', '#0D8FE9', '#0AB39C'],

  stroke: {

    width: 4,

    colors: ['#ffffff'],

  },

  dataLabels: {

    enabled: false,

  },

  legend: {

    position: 'bottom',

    fontSize: '12px',

    itemMargin: {

      horizontal: 8,

      vertical: 5,

    },

  },

  plotOptions: {

    pie: {

      donut: {

        size: '72%',

        labels: {

          show: true,

          name: {

            show: true,

            fontSize: '12px',

            color: '#64748B',

          },

          value: {

            show: true,

            fontSize: '24px',

            fontWeight: 700,

            color: '#071126',

          },

          total: {

            show: true,

            label: t('ui-enrollments'),

            color: '#64748B',

            formatter: () => String(props.stats.enrollments_count || 0),

          },

        },

      },

    },

  },

  tooltip: {

    y: {

      formatter: value => `${value} enrollment(s)`,

    },

  },

}))

</script>

<template>

  <Layout>

    <Head :title="company.name || $t('ui-company-details')" />

    <PageHeader :title="$t('ui-company-dashboard')" :page-title="$t('ui-companies')" />

    <div class="company-dashboard">

      <BCard class="company-profile-card border-0 shadow-sm mb-4">

        <div class="company-profile-content">

          <div class="company-profile-main">

            <div class="company-profile-logo">

              <img :src="companyLogo" :alt="company.name || 'Company'" />

            </div>

            <div class="company-profile-info">

              <div class="company-profile-title">

                <h3>{{ company.name || '-' }}</h3>

                <span

                  class="badge rounded-pill"

                  :class="statusClass(company.status)"

                >

                  {{ company.status || '-' }}

                </span>

              </div>

              <div class="company-contact-grid">

                <div class="company-contact-item">

                  <i class="ri-mail-line"></i>

                  <span>{{ company.email || '-' }}</span>

                </div>

                <div class="company-contact-item">

                  <i class="ri-phone-line"></i>

                  <span>{{ company.phone || '-' }}</span>

                </div>

                <div class="company-contact-item company-address-item">

                  <i class="ri-map-pin-line"></i>

                  <span>{{ company.address || '-' }}</span>

                </div>

                <div class="company-contact-item">

                  <i class="ri-calendar-line"></i>

                  <span>{{ $t('ui-added') }} {{ formatDate(company.created_at) }}</span>

                </div>

              </div>

            </div>

          </div>
          <div class="company-profile-actions">
            <BButton
              variant="success"
              class="export-enrollments-btn"
              @click="openEnrollmentExportModal"
            >
              <i class="ri-file-excel-2-line me-1"></i>
              {{ $t('ui-export-enrollments') }}
            </BButton>

            <BButton
              variant="light"
              class="back-btn"
              @click="goBack"
            >
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('ui-back-to-companies') }}
            </BButton>
          </div>

        </div>

      </BCard>

      <BRow class="g-3 mb-4">

        <BCol

          v-for="card in statCards"

          :key="card.label"

          xxl="3"

          xl="3"

          lg="4"

          md="6"

          cols="12"

        >

          <div class="stat-card">

            <div class="stat-icon" :class="`stat-${card.tone}`">

              <i :class="card.icon"></i>

            </div>

            <div class="stat-content">

              <span class="stat-label">{{ card.label }}</span>

              <strong class="stat-value">{{ card.value }}</strong>

              <small class="stat-helper">{{ card.helper }}</small>

            </div>

          </div>

        </BCol>

      </BRow>

      <BCard class="dashboard-section-card border-0 shadow-sm mb-4">

        <div class="section-header">

          <div class="section-title">

            <div class="section-icon">

              <i class="ri-line-chart-line"></i>

            </div>

            <div>

              <h5>{{ $t('ui-training-activity') }}</h5>

              <p>

                {{ $t('ui-monthly-enrollments-learners-completions-and-certificates') }}

              </p>

            </div>

          </div>

          <div class="training-filter">

            <div class="training-filter-label">

              <i class="ri-calendar-2-line"></i>

              <span>{{ $t('ui-period') }}</span>

            </div>

            <select

              v-model="selectedTrainingYear"

              class="training-year-select"

              @change="changeTrainingPeriod"

            >

              <option value="">

                {{ $t('ui-last-12-months') }}

              </option>

              <option

                v-for="year in availableYears"

                :key="year"

                :value="year"

              >

                {{ year }}

              </option>

            </select>

          </div>

        </div>

        <VueApexCharts

          type="area"

          height="360"

          :options="trainingActivityOptions"

          :series="trainingActivitySeries"

        />

      </BCard>

      <BRow class="g-4 mb-4">

        <BCol xl="8" lg="7" cols="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-header">

              <div class="section-title">

                <div class="section-icon">

                  <i class="ri-bar-chart-grouped-line"></i>

                </div>

                <div>

                  <h5>{{ $t('ui-company-requests') }}</h5>

                  <p>

                    {{ $t('ui-approved-pending-and-rejected-requests-by-creation-mon-label') }}

                  </p>

                </div>

              </div>

              <span class="period-badge">{{ $t('ui-last-12-months') }}</span>

            </div>

            <VueApexCharts

              type="bar"

              height="325"

              :options="requestChartOptions"

              :series="requestChartSeries"

            />

          </BCard>

        </BCol>

        <BCol xl="4" lg="5" cols="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-title">

              <div class="section-icon">

                <i class="ri-pie-chart-line"></i>

              </div>

              <div>

                <h5>{{ $t('ui-enrollment-status') }}</h5>

                <p>{{ $t('ui-current-training-distribution') }}</p>

              </div>

            </div>

            <VueApexCharts

              type="donut"

              height="285"

              :options="enrollmentStatusOptions"

              :series="enrollmentStatusSeries"

            />

            <div class="status-summary-grid">

              <div>

                <span>{{ $t('ui-not-started') }}</span>

                <strong>{{ stats.not_started_enrollments_count || 0 }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-in-progress') }}</span>

                <strong>{{ stats.in_progress_enrollments_count || 0 }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-completed') }}</span>

                <strong>{{ stats.completed_enrollments_count || 0 }}</strong>

              </div>

            </div>

          </BCard>

        </BCol>

      </BRow>

      <BRow class="g-4 mb-4">

        <BCol xl="8" lg="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-title">

              <div class="section-icon">

                <i class="ri-funds-line"></i>

              </div>

              <div>

                <h5>{{ $t('ui-performance-overview') }}</h5>

                <p>{{ $t('ui-global-training-request-and-exam-performance') }}</p>

              </div>

            </div>

            <div class="performance-list">

              <div class="performance-item">

                <div class="performance-header">

                  <div>

                    <span class="performance-label">{{ $t('ui-average-learning-progress') }}</span>

                    <small>{{ $t('ui-across-all-company-enrollments') }}</small>

                  </div>

                  <strong>{{ formatPercent(stats.average_progress) }}</strong>

                </div>

                <div class="performance-track">

                  <div

                    class="performance-fill"

                    :style="{ width: `${clampPercent(stats.average_progress)}%` }"

                  ></div>

                </div>

              </div>

              <div class="performance-item">

                <div class="performance-header">

                  <div>

                    <span class="performance-label">{{ $t('ui-course-completion-rate') }}</span>

                    <small>{{ $t('ui-completed-enrollments-all-enrollments') }}</small>

                  </div>

                  <strong>{{ formatPercent(stats.completion_rate) }}</strong>

                </div>

                <div class="performance-track">

                  <div

                    class="performance-fill success-fill"

                    :style="{ width: `${clampPercent(stats.completion_rate)}%` }"

                  ></div>

                </div>

              </div>

              <div class="performance-item">

                <div class="performance-header">

                  <div>

                    <span class="performance-label">{{ $t('ui-training-coverage') }}</span>

                    <small>{{ $t('ui-enrolled-learners-company-users') }}</small>

                  </div>

                  <strong>{{ formatPercent(stats.training_coverage_rate) }}</strong>

                </div>

                <div class="performance-track">

                  <div

                    class="performance-fill info-fill"

                    :style="{ width: `${clampPercent(stats.training_coverage_rate)}%` }"

                  ></div>

                </div>

              </div>

              <div class="performance-item">

                <div class="performance-header">

                  <div>

                    <span class="performance-label">{{ $t('ui-request-approval-rate') }}</span>

                    <small>{{ $t('ui-approved-requests-all-requests') }}</small>

                  </div>

                  <strong>{{ formatPercent(stats.request_approval_rate) }}</strong>

                </div>

                <div class="performance-track">

                  <div

                    class="performance-fill warning-fill"

                    :style="{ width: `${clampPercent(stats.request_approval_rate)}%` }"

                  ></div>

                </div>

              </div>

              <div class="performance-item">

                <div class="performance-header">

                  <div>

                    <span class="performance-label">{{ $t('ui-exam-pass-rate') }}</span>

                    <small>

                      {{ stats.passed_exam_attempts_count || 0 }} {{ $t('ui-passed-label') }}

                      {{ stats.exam_attempts_count || 0 }} {{ $t('ui-submitted-attempts') }}

                    </small>

                  </div>

                  <strong>{{ formatPercent(stats.exam_pass_rate) }}</strong>

                </div>

                <div class="performance-track">

                  <div

                    class="performance-fill purple-fill"

                    :style="{ width: `${clampPercent(stats.exam_pass_rate)}%` }"

                  ></div>

                </div>

              </div>

            </div>

          </BCard>

        </BCol>

        <BCol xl="4" lg="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-title">

              <div class="section-icon alert-section-icon">

                <i class="ri-alarm-warning-line"></i>

              </div>

              <div>

                <h5>{{ $t('ui-management-alerts') }}</h5>

                <p>{{ $t('ui-items-requiring-attention') }}</p>

              </div>

            </div>

            <div class="management-alert-list">

              <div

                v-for="alert in managementAlerts"

                :key="alert.label"

                class="management-alert"

                :class="`management-alert-${alert.tone}`"

              >

                <div class="management-alert-icon">

                  <i :class="alert.icon"></i>

                </div>

                <div class="management-alert-content">

                  <strong>{{ alert.label }}</strong>

                  <small>{{ alert.description }}</small>

                </div>

                <div class="management-alert-value">

                  {{ alert.value }}

                </div>

              </div>

            </div>

            <div class="secondary-summary">

              <div>

                <span>{{ $t('ui-company-users') }}</span>

                <strong>{{ stats.users_count || 0 }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-assigned-courses') }}</span>

                <strong>{{ stats.assigned_courses_count || 0 }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-requested-courses') }}</span>

                <strong>{{ stats.requested_courses_count || 0 }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-accreditations') }}</span>

                <strong>{{ stats.accreditations_used_count || 0 }}</strong>

              </div>

            </div>

          </BCard>

        </BCol>

      </BRow>

      <BCard class="dashboard-section-card border-0 shadow-sm mb-4">

        <div class="section-header">

          <div class="section-title">

            <div class="section-icon">

              <i class="ri-book-open-line"></i>

            </div>

            <div>

              <h5>{{ $t('ui-course-performance') }}</h5>

              <p>

                {{ $t('ui-learners-enrollments-completions-certificates-and-prog') }}

              </p>

            </div>

          </div>

          <div class="course-count-badge">

            {{ courseStats.length }} {{ $t('ui-course-s') }}

          </div>

        </div>

        <div v-if="courseStats.length" class="table-responsive d-none d-lg-block">

          <table class="table company-details-table align-middle mb-0">

            <thead>

              <tr>

                <th>{{ $t('ui-course') }}</th>

                <th class="text-center">{{ $t('ui-requests') }}</th>

                <th class="text-center">{{ $t('ui-approved') }}</th>

                <th class="text-center">{{ $t('ui-learners') }}</th>

                <th class="text-center">{{ $t('ui-enrollments') }}</th>

                <th class="text-center">{{ $t('ui-completed') }}</th>

                <th class="text-center">{{ $t('ui-certificates') }}</th>

                <th>{{ $t('ui-progress') }}</th>

              </tr>

            </thead>

            <tbody>

              <tr v-for="course in courseStats" :key="course.id">

                <td>

                  <div class="course-cell">

                    <span class="course-ref">{{ course.ref || 'COURSE' }}</span>

                    <strong>{{ course.title }}</strong>

                  </div>

                </td>

                <td class="text-center">{{ course.requests_count }}</td>

                <td class="text-center">{{ course.approved_requests_count }}</td>

                <td class="text-center">{{ course.learners_count }}</td>

                <td class="text-center">{{ course.enrollments_count }}</td>

                <td class="text-center">{{ course.completed_count }}</td>

                <td class="text-center">

                  <span class="certificate-count">

                    <i class="ri-award-line"></i>

                    {{ course.certificates_count }}

                  </span>

                </td>

                <td>

                  <div class="table-progress">

                    <div class="table-progress-header">

                      <span>{{ formatPercent(course.average_progress) }}</span>

                      <small>{{ formatPercent(course.completion_rate) }} {{ $t('t-completed') }}</small>

                    </div>

                    <div class="table-progress-track">

                      <div

                        class="table-progress-fill"

                        :style="{ width: `${clampPercent(course.average_progress)}%` }"

                      ></div>

                    </div>

                  </div>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

        <div v-if="courseStats.length" class="course-mobile-list d-lg-none">

          <div

            v-for="course in courseStats"

            :key="course.id"

            class="course-mobile-card"

          >

            <div class="course-mobile-header">

              <span class="course-ref">{{ course.ref || 'COURSE' }}</span>

              <strong>{{ course.title }}</strong>

            </div>

            <div class="course-mobile-grid">

              <div>

                <span>{{ $t('ui-requests') }}</span>

                <strong>{{ course.requests_count }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-approved') }}</span>

                <strong>{{ course.approved_requests_count }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-learners') }}</span>

                <strong>{{ course.learners_count }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-enrollments') }}</span>

                <strong>{{ course.enrollments_count }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-completed') }}</span>

                <strong>{{ course.completed_count }}</strong>

              </div>

              <div>

                <span>{{ $t('ui-certificates') }}</span>

                <strong>{{ course.certificates_count }}</strong>

              </div>

            </div>

            <div class="mt-3">

              <div class="table-progress-header">

                <span>{{ $t('ui-average-progress') }}</span>

                <strong>{{ formatPercent(course.average_progress) }}</strong>

              </div>

              <div class="table-progress-track">

                <div

                  class="table-progress-fill"

                  :style="{ width: `${clampPercent(course.average_progress)}%` }"

                ></div>

              </div>

            </div>

          </div>

        </div>

        <div v-if="!courseStats.length" class="empty-section">

          <i class="ri-book-open-line"></i>

          <h6>{{ $t('ui-no-course-data-available') }}</h6>

          <p>{{ $t('ui-no-enrollment-has-been-created-for-this-company-yet') }}</p>

        </div>

      </BCard>

      <BRow class="g-4">

        <BCol xl="6" cols="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-title">

              <div class="section-icon">

                <i class="ri-file-list-3-line"></i>

              </div>

              <div>

                <h5>{{ $t('ui-recent-requests') }}</h5>

                <p>{{ $t('ui-latest-company-training-requests') }}</p>

              </div>

            </div>

            <div v-if="recentRequests.length" class="activity-list">

              <div

                v-for="request in recentRequests"

                :key="request.id"

                class="activity-item"

              >

                <div class="activity-main">

                  <div class="activity-title-row">

                    <strong>{{ request.reference }}</strong>

                    <span

                      class="badge rounded-pill"

                      :class="statusClass(request.status)"

                    >

                      {{ request.status }}

                    </span>

                  </div>

                  <span class="activity-course">

                    {{ request.course_title || '-' }}

                  </span>

                  <div class="activity-meta">

                    <span>

                      <i class="ri-group-line"></i>

                      {{ request.learners_count }} {{ $t('ui-learner-s') }}

                    </span>

                    <span>

                      <i class="ri-verified-badge-line"></i>

                      {{ request.accreditations_count }} {{ $t('ui-accreditation-s') }}

                    </span>

                    <span>

                      <i class="ri-calendar-line"></i>

                      {{ formatDate(request.created_at) }}

                    </span>

                  </div>

                </div>

              </div>

            </div>

            <div v-else class="empty-section small-empty">

              <i class="ri-file-list-3-line"></i>

              <h6>{{ $t('ui-no-requests') }}</h6>

              <p>{{ $t('ui-this-company-has-no-training-request-yet') }}</p>

            </div>

          </BCard>

        </BCol>

        <BCol xl="6" cols="12">

          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">

            <div class="section-title">

              <div class="section-icon">

                <i class="ri-award-line"></i>

              </div>

              <div>

                <h5>{{ $t('ui-recent-certificates') }}</h5>

                <p>{{ $t('ui-latest-certificates-delivered-to-company-learners') }}</p>

              </div>

            </div>

            <div v-if="recentCertificates.length" class="activity-list">

              <div

                v-for="certificate in recentCertificates"

                :key="certificate.id"

                class="activity-item certificate-activity"

              >

                <div class="certificate-icon">

                  <i class="ri-award-fill"></i>

                </div>

                <div class="activity-main">

                  <div class="activity-title-row">

                    <strong>{{ certificate.learner_name || '-' }}</strong>

                    <span class="certificate-date">

                      {{ formatDate(certificate.issued_at) }}

                    </span>

                  </div>

                  <span class="activity-course">

                    {{ certificate.course_title || '-' }}

                  </span>

                  <div class="activity-meta">

                    <span v-if="certificate.certificate_number">

                      <i class="ri-hashtag"></i>

                      {{ certificate.certificate_number }}

                    </span>

                    <span v-if="certificate.accreditation_name">

                      <i class="ri-verified-badge-line"></i>

                      {{ certificate.accreditation_name }}

                    </span>

                  </div>

                </div>

              </div>

            </div>

            <div v-else class="empty-section small-empty">

              <i class="ri-award-line"></i>

              <h6>{{ $t('ui-no-certificates') }}</h6>

              <p>{{ $t('ui-no-certificate-has-been-issued-yet') }}</p>

            </div>

          </BCard>

        </BCol>

      </BRow>

    </div>

  

    <!-- Company enrollments Excel export modal -->
    <div
      v-if="showEnrollmentExportModal"
      class="export-modal-backdrop"
      @click.self="closeEnrollmentExportModal"
    >
      <div
        class="export-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="company-enrollment-export-title"
      >
        <div class="export-modal-header">
          <div class="export-modal-title-wrap">
            <div class="export-modal-icon">
              <i class="ri-file-excel-2-line"></i>
            </div>

            <div>
              <h5 id="company-enrollment-export-title">{{ $t('ui-export-enrollments') }}</h5>
              <p>
                {{ $t('ui-download-a-detailed-monthly-excel-report-for') }}
                <strong>{{ company.name }}</strong>.
              </p>
            </div>
          </div>

          <button
            type="button"
            class="export-modal-close"
            :aria-label="$t('ui-close')"
            @click="closeEnrollmentExportModal"
          >
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="export-modal-body">
          <div class="export-info-box">
            <i class="ri-information-line"></i>
            <div>
              <strong>{{ $t('ui-monthly-excel-sheets') }}</strong>
              <span>
                {{ $t('ui-one-worksheet-will-be-generated-for-every-month-betwee') }}
              </span>
            </div>
          </div>

          <div class="export-date-grid">
            <div class="export-field">
              <label for="export-start-date">{{ $t('ui-start-date') }}</label>
              <div class="export-input-wrap">
                <i class="ri-calendar-event-line"></i>
                <input
                  id="export-start-date"
                  v-model="exportStartDate"
                  type="date"
                  class="export-date-input"
                  :max="exportEndDate || undefined"
                />
              </div>
            </div>

            <div class="export-field">
              <label for="export-end-date">{{ $t('ui-end-date') }}</label>
              <div class="export-input-wrap">
                <i class="ri-calendar-check-line"></i>
                <input
                  id="export-end-date"
                  v-model="exportEndDate"
                  type="date"
                  class="export-date-input"
                  :min="exportStartDate || undefined"
                />
              </div>
            </div>
          </div>

          <div v-if="exportError" class="export-error">
            <i class="ri-error-warning-line"></i>
            <span>{{ exportError }}</span>
          </div>

          <div class="export-preview">
            <div class="export-preview-icon">
              <i class="ri-file-excel-2-line"></i>
            </div>
            <div>
              <span>{{ $t('ui-export-file') }}</span>
              <strong>{{ $t('ui-enrollments-label') }} {{ company.name || 'Company' }}</strong>
              <small v-if="exportStartDate && exportEndDate">
                {{ formatDate(exportStartDate) }} → {{ formatDate(exportEndDate) }}
              </small>
              <small v-else>{{ $t('ui-select-the-reporting-period') }}</small>
            </div>
          </div>
        </div>

        <div class="export-modal-footer">
          <BButton
            variant="light"
            class="export-cancel-btn"
            @click="closeEnrollmentExportModal"
          >
            {{ $t('ui-cancel') }}
          </BButton>

          <BButton
            variant="success"
            class="export-download-btn"
            @click="downloadEnrollmentExport"
          >
            <i class="ri-download-2-line me-1"></i>
            {{ $t('ui-download-excel') }}
          </BButton>
        </div>
      </div>
    </div>

  </Layout>

</template>

<style scoped>

.company-dashboard {

  padding-bottom: 28px;

}

.company-profile-card {

  border-radius: 20px;

  overflow: hidden;

}

.company-profile-card :deep(.card-body) {

  padding: 0;

}

.company-profile-content {

  padding: 28px;

  background: linear-gradient(135deg, #ffffff 0%, #f4fbff 52%, #eaf7ff 100%);

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 24px;

}

.company-profile-main {

  display: flex;

  align-items: center;

  gap: 22px;

  min-width: 0;

}

.company-profile-logo {

  width: 92px;

  height: 92px;

  min-width: 92px;

  border-radius: 20px;

  overflow: hidden;

  background: #ffffff;

  border: 1px solid #e4edf4;

  box-shadow: 0 8px 24px rgba(7, 17, 38, 0.08);

}

.company-profile-logo img {

  width: 100%;

  height: 100%;

  object-fit: cover;

}

.company-profile-info {

  min-width: 0;

}

.company-profile-title {

  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 12px;

  margin-bottom: 14px;

}

.company-profile-title h3 {

  margin: 0;

  color: #071126;

  font-size: 25px;

  font-weight: 750;

}

.company-contact-grid {

  display: flex;

  align-items: center;

  flex-wrap: wrap;

  gap: 10px 22px;

}

.company-contact-item {

  display: flex;

  align-items: center;

  gap: 7px;

  color: #64748b;

  font-size: 13px;

}

.company-contact-item i {

  color: #0d8fe9;

  font-size: 16px;

}

.company-address-item {

  max-width: 420px;

}

.back-btn {

  height: 42px;

  padding: 0 18px;

  border-radius: 12px;

  white-space: nowrap;

  border: 1px solid #e4edf4;

}

/* Company profile actions */
.company-profile-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
}

.export-enrollments-btn {
  height: 42px;
  padding: 0 17px;
  border: 0;
  border-radius: 12px;
  white-space: nowrap;
  background: linear-gradient(135deg, #20c997 0%, #0ab39c 100%);
  box-shadow: 0 7px 18px rgba(10, 179, 156, 0.18);
  font-size: 12px;
  font-weight: 650;
}

.export-enrollments-btn:hover,
.export-enrollments-btn:focus {
  background: linear-gradient(135deg, #1eb98c 0%, #099984 100%);
  box-shadow: 0 9px 22px rgba(10, 179, 156, 0.24);
}

/* Enrollment export modal */
.export-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1100;
  padding: 24px;
  background: rgba(7, 17, 38, 0.5);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.export-modal-card {
  width: min(620px, 100%);
  max-height: calc(100vh - 48px);
  border: 1px solid #e4edf4;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 24px 70px rgba(7, 17, 38, 0.22);
  overflow-y: auto;
}

.export-modal-header {
  padding: 20px 22px;
  border-bottom: 1px solid #e4edf4;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
}

.export-modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 13px;
  min-width: 0;
}

.export-modal-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 13px;
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.export-modal-title-wrap h5 {
  margin: 0;
  color: #071126;
  font-size: 17px;
  font-weight: 750;
}

.export-modal-title-wrap p {
  margin: 4px 0 0;
  color: #8a94a6;
  font-size: 11px;
  line-height: 1.45;
}

.export-modal-title-wrap p strong { color: #475569; }

.export-modal-close {
  width: 34px;
  height: 34px;
  min-width: 34px;
  padding: 0;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
}

.export-modal-close:hover {
  background: #f1f5f9;
  color: #071126;
}

.export-modal-body { padding: 22px; }

.export-info-box {
  margin-bottom: 20px;
  padding: 13px 14px;
  border: 1px solid #d9effc;
  border-radius: 12px;
  background: #f4fbff;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.export-info-box > i {
  margin-top: 1px;
  color: #0d8fe9;
  font-size: 17px;
}

.export-info-box > div {
  display: flex;
  flex-direction: column;
}

.export-info-box strong {
  color: #334155;
  font-size: 11.5px;
}

.export-info-box span {
  margin-top: 3px;
  color: #7c8a9a;
  font-size: 10.5px;
  line-height: 1.45;
}

.export-date-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.export-field {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.export-field label {
  color: #475569;
  font-size: 11px;
  font-weight: 700;
}

.export-input-wrap { position: relative; }

.export-input-wrap > i {
  position: absolute;
  top: 50%;
  left: 13px;
  transform: translateY(-50%);
  color: #0d8fe9;
  font-size: 16px;
  pointer-events: none;
}

.export-date-input {
  width: 100%;
  height: 45px;
  padding: 0 12px 0 39px;
  border: 1px solid #dce7ef;
  border-radius: 11px;
  background: #ffffff;
  color: #334155;
  font-size: 12px;
  outline: none;
}

.export-date-input:focus {
  border-color: #0d8fe9;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.1);
}

.export-error {
  margin-top: 13px;
  padding: 10px 12px;
  border: 1px solid rgba(240, 101, 72, 0.22);
  border-radius: 10px;
  background: rgba(240, 101, 72, 0.06);
  color: #d94f33;
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10.5px;
  font-weight: 600;
}

.export-preview {
  margin-top: 18px;
  padding: 13px;
  border: 1px solid #e4edf4;
  border-radius: 12px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  gap: 11px;
}

.export-preview-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 10px;
  background: rgba(10, 179, 156, 0.1);
  color: #0ab39c;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.export-preview > div:last-child {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.export-preview span { color: #94a3b8; font-size: 9.5px; }
.export-preview strong {
  margin-top: 1px;
  color: #334155;
  font-size: 11.5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.export-preview small { margin-top: 2px; color: #64748b; font-size: 10px; }

.export-modal-footer {
  padding: 16px 22px;
  border-top: 1px solid #e4edf4;
  background: #fbfdff;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.export-cancel-btn,
.export-download-btn {
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  font-size: 11.5px;
  font-weight: 650;
}

.export-cancel-btn { border: 1px solid #e4edf4; }
.export-download-btn {
  border: 0;
  background: linear-gradient(135deg, #20c997 0%, #0ab39c 100%);
  box-shadow: 0 6px 15px rgba(10, 179, 156, 0.16);
}
.export-download-btn:hover,
.export-download-btn:focus {
  background: linear-gradient(135deg, #1eb98c 0%, #099984 100%);
}

.stat-card {

  min-height: 126px;

  height: 100%;

  padding: 20px;

  border: 1px solid #e4edf4;

  border-radius: 18px;

  background: #ffffff;

  display: flex;

  align-items: center;

  gap: 16px;

  box-shadow: 0 6px 20px rgba(7, 17, 38, 0.045);

  transition: transform 0.2s ease, box-shadow 0.2s ease;

}

.stat-card:hover {

  transform: translateY(-2px);

  box-shadow: 0 12px 28px rgba(7, 17, 38, 0.075);

}

.stat-icon {

  width: 52px;

  height: 52px;

  min-width: 52px;

  border-radius: 15px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 23px;

}

.stat-primary {

  color: #0d8fe9;

  background: rgba(13, 143, 233, 0.12);

}

.stat-blue {

  color: #087bc9;

  background: rgba(8, 123, 201, 0.11);

}

.stat-info {

  color: #37cced;

  background: rgba(55, 204, 237, 0.14);

}

.stat-success {

  color: #0ab39c;

  background: rgba(10, 179, 156, 0.12);

}

.stat-warning {

  color: #d99816;

  background: rgba(247, 184, 75, 0.16);

}

.stat-purple {

  color: #6559cc;

  background: rgba(101, 89, 204, 0.12);

}

.stat-danger {

  color: #f06548;

  background: rgba(240, 101, 72, 0.12);

}

.stat-content {

  display: flex;

  flex-direction: column;

  min-width: 0;

}

.stat-label {

  color: #64748b;

  font-size: 12px;

  font-weight: 650;

}

.stat-value {

  margin-top: 3px;

  color: #071126;

  font-size: 27px;

  line-height: 1.2;

  font-weight: 750;

}

.stat-helper {

  margin-top: 5px;

  color: #94a3b8;

  font-size: 10.5px;

  line-height: 1.35;

}

.dashboard-section-card {

  border-radius: 18px;

  overflow: hidden;

}

.dashboard-section-card :deep(.card-body) {

  padding: 24px;

}

.section-header {

  display: flex;

  align-items: flex-start;

  justify-content: space-between;

  gap: 18px;

  margin-bottom: 8px;

}

.section-title {

  display: flex;

  align-items: center;

  gap: 13px;

  margin-bottom: 24px;

}

.section-header .section-title {

  margin-bottom: 0;

}

.section-icon {

  width: 44px;

  height: 44px;

  min-width: 44px;

  border-radius: 13px;

  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);

  color: #ffffff;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 20px;

  box-shadow: 0 7px 18px rgba(13, 143, 233, 0.18);

}

.alert-section-icon {

  background: linear-gradient(135deg, #f7b84b 0%, #f06548 100%);

}

.section-title h5 {

  margin: 0 0 3px;

  color: #071126;

  font-size: 16px;

  font-weight: 750;

}

.section-title p {

  margin: 0;

  color: #8a94a6;

  font-size: 12px;

  line-height: 1.45;

}

.period-badge,

.course-count-badge {

  padding: 7px 11px;

  border-radius: 999px;

  background: #eaf7ff;

  color: #087bc9;

  font-size: 10px;

  font-weight: 750;

  white-space: nowrap;

}

.status-summary-grid {

  display: grid;

  grid-template-columns: repeat(3, minmax(0, 1fr));

  gap: 8px;

  margin-top: 5px;

}

.status-summary-grid > div {

  padding: 11px 8px;

  border-radius: 11px;

  background: #f8fafc;

  text-align: center;

}

.status-summary-grid span {

  display: block;

  color: #94a3b8;

  font-size: 9.5px;

}

.status-summary-grid strong {

  display: block;

  margin-top: 3px;

  color: #071126;

  font-size: 17px;

}

.performance-list {

  display: flex;

  flex-direction: column;

  gap: 23px;

}

.performance-header {

  display: flex;

  align-items: flex-end;

  justify-content: space-between;

  gap: 16px;

  margin-bottom: 9px;

}

.performance-header > div {

  display: flex;

  flex-direction: column;

}

.performance-label {

  color: #334155;

  font-size: 13px;

  font-weight: 700;

}

.performance-header small {

  margin-top: 2px;

  color: #94a3b8;

  font-size: 11px;

}

.performance-header strong {

  color: #0d8fe9;

  font-size: 14px;

}

.performance-track,

.table-progress-track {

  height: 7px;

  border-radius: 999px;

  background: #eef2f7;

  overflow: hidden;

}

.performance-fill,

.table-progress-fill {

  height: 100%;

  border-radius: inherit;

  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);

  transition: width 0.4s ease;

}

.success-fill {

  background: linear-gradient(90deg, #35d6bd 0%, #0ab39c 100%);

}

.info-fill {

  background: linear-gradient(90deg, #37cced 0%, #087bc9 100%);

}

.warning-fill {

  background: linear-gradient(90deg, #ffd47a 0%, #f7b84b 100%);

}

.purple-fill {

  background: linear-gradient(90deg, #8a7ce6 0%, #6559cc 100%);

}

.management-alert-list {

  display: flex;

  flex-direction: column;

  gap: 10px;

}

.management-alert {

  min-height: 66px;

  padding: 11px 12px;

  border: 1px solid #e4edf4;

  border-radius: 13px;

  display: flex;

  align-items: center;

  gap: 11px;

}

.management-alert-icon {

  width: 39px;

  height: 39px;

  min-width: 39px;

  border-radius: 11px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 17px;

}

.management-alert-content {

  flex: 1;

  min-width: 0;

  display: flex;

  flex-direction: column;

}

.management-alert-content strong {

  color: #334155;

  font-size: 12px;

}

.management-alert-content small {

  margin-top: 2px;

  color: #94a3b8;

  font-size: 9.5px;

  line-height: 1.35;

}

.management-alert-value {

  min-width: 34px;

  text-align: right;

  color: #071126;

  font-size: 20px;

  font-weight: 750;

}

.management-alert-warning {

  background: rgba(247, 184, 75, 0.055);

}

.management-alert-warning .management-alert-icon {

  color: #d99816;

  background: rgba(247, 184, 75, 0.15);

}

.management-alert-danger {

  background: rgba(240, 101, 72, 0.045);

}

.management-alert-danger .management-alert-icon {

  color: #f06548;

  background: rgba(240, 101, 72, 0.12);

}

.management-alert-success {

  background: rgba(10, 179, 156, 0.04);

}

.management-alert-success .management-alert-icon {

  color: #0ab39c;

  background: rgba(10, 179, 156, 0.12);

}

.secondary-summary {

  display: grid;

  grid-template-columns: repeat(2, minmax(0, 1fr));

  gap: 9px;

  margin-top: 16px;

  padding-top: 16px;

  border-top: 1px solid #e4edf4;

}

.secondary-summary > div {

  padding: 11px;

  border-radius: 11px;

  background: #f8fafc;

  display: flex;

  flex-direction: column;

}

.secondary-summary span {

  color: #94a3b8;

  font-size: 9.5px;

}

.secondary-summary strong {

  margin-top: 2px;

  color: #334155;

  font-size: 17px;

}

.company-details-table {

  border: 1px solid #e4edf4;

  border-radius: 14px;

  overflow: hidden;

}

.company-details-table thead {

  background: linear-gradient(90deg, #087bc9 0%, #0d8fe9 100%);

}

.company-details-table thead th {

  padding: 14px 16px;

  color: #ffffff;

  border: 0;

  font-size: 10.5px;

  font-weight: 700;

  text-transform: uppercase;

  letter-spacing: 0.04em;

  white-space: nowrap;

}

.company-details-table tbody td {

  padding: 15px 16px;

  border-color: #e4edf4;

  color: #475569;

  font-size: 12.5px;

}

.company-details-table tbody tr:hover {

  background: #f8fcff;

}

.course-cell {

  display: flex;

  flex-direction: column;

  gap: 4px;

  min-width: 180px;

}

.course-cell strong {

  color: #253149;

}

.course-ref {

  width: fit-content;

  padding: 3px 7px;

  border-radius: 6px;

  background: #eaf7ff;

  color: #087bc9;

  font-size: 9.5px;

  font-weight: 750;

}

.certificate-count {

  color: #0ab39c;

  font-weight: 700;

}

.table-progress {

  min-width: 175px;

}

.table-progress-header {

  display: flex;

  justify-content: space-between;

  gap: 10px;

  margin-bottom: 6px;

  color: #475569;

  font-size: 10.5px;

}

.table-progress-header small {

  color: #94a3b8;

}

.course-mobile-list {

  display: flex;

  flex-direction: column;

  gap: 14px;

}

.course-mobile-card {

  padding: 16px;

  border: 1px solid #e4edf4;

  border-radius: 15px;

}

.course-mobile-header {

  display: flex;

  flex-direction: column;

  gap: 6px;

  margin-bottom: 14px;

}

.course-mobile-header strong {

  color: #27364f;

}

.course-mobile-grid {

  display: grid;

  grid-template-columns: repeat(2, minmax(0, 1fr));

  gap: 9px;

}

.course-mobile-grid > div {

  padding: 11px;

  border-radius: 10px;

  background: #f8fafc;

  display: flex;

  flex-direction: column;

}

.course-mobile-grid span {

  color: #94a3b8;

  font-size: 10px;

}

.course-mobile-grid strong {

  margin-top: 2px;

  color: #334155;

  font-size: 15px;

}

.activity-list {

  display: flex;

  flex-direction: column;

}

.activity-item {

  display: flex;

  align-items: flex-start;

  gap: 12px;

  padding: 16px 0;

  border-bottom: 1px solid #e4edf4;

}

.activity-item:first-child {

  padding-top: 0;

}

.activity-item:last-child {

  border-bottom: 0;

  padding-bottom: 0;

}

.activity-main {

  flex: 1;

  min-width: 0;

}

.activity-title-row {

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;

}

.activity-title-row strong {

  color: #27364f;

  font-size: 13px;

}

.activity-course {

  display: block;

  margin-top: 5px;

  color: #64748b;

  font-size: 12px;

}

.activity-meta {

  display: flex;

  flex-wrap: wrap;

  gap: 8px 15px;

  margin-top: 8px;

}

.activity-meta span {

  color: #94a3b8;

  font-size: 10.5px;

}

.activity-meta i {

  margin-right: 3px;

}

.certificate-icon {

  width: 39px;

  height: 39px;

  min-width: 39px;

  border-radius: 11px;

  background: rgba(10, 179, 156, 0.1);

  color: #0ab39c;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 18px;

}

.certificate-date {

  color: #94a3b8;

  font-size: 10.5px;

}

.empty-section {

  min-height: 190px;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  text-align: center;

  color: #94a3b8;

}

.empty-section > i {

  margin-bottom: 10px;

  color: #c5ccda;

  font-size: 37px;

}

.empty-section h6 {

  margin-bottom: 4px;

  color: #475569;

}

.empty-section p {

  margin: 0;

  font-size: 12px;

}

.small-empty {

  min-height: 220px;

}

/*

\|--------------------------------------------------------------------------

\| Training year filter

\|--------------------------------------------------------------------------

*/

.training-filter {

  display: flex;

  align-items: center;

  gap: 10px;

}

.training-filter-label {

  display: flex;

  align-items: center;

  gap: 6px;

  color: #64748b;

  font-size: 11px;

  font-weight: 600;

}

.training-filter-label i {

  color: #0d8fe9;

  font-size: 16px;

}

.training-year-select {

  min-width: 150px;

  height: 39px;

  padding: 0 36px 0 13px;

  border: 1px solid #dce7ef;

  border-radius: 10px;

  background-color: #ffffff;

  color: #334155;

  font-size: 12px;

  font-weight: 600;

  outline: none;

  cursor: pointer;

  transition:

    border-color 0.2s ease,

    box-shadow 0.2s ease;

}

.training-year-select:hover {

  border-color: #9ed9f8;

}

.training-year-select:focus {

  border-color: #0d8fe9;

  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.1);

}

@media (max-width: 991.98px) {

  .company-profile-content {

    align-items: flex-start;

  }

  .company-profile-main {

    align-items: flex-start;

  }

}

@media (max-width: 767.98px) {

  .company-profile-actions {
    width: 100%;
    justify-content: stretch;
  }

  .company-profile-actions .btn { flex: 1; }
  .export-date-grid { grid-template-columns: 1fr; }
  .export-modal-backdrop { padding: 14px; }
  .export-modal-header,
  .export-modal-body,
  .export-modal-footer {
    padding-left: 17px;
    padding-right: 17px;
  }


  .training-filter {

    width: 100%;

  }

  .training-filter-label {

    min-width: fit-content;

  }

  .training-year-select {

    flex: 1;

  }

  .company-profile-content {

    padding: 20px;

    flex-direction: column;

  }

  .company-profile-main {

    width: 100%;

  }

  .company-profile-logo {

    width: 72px;

    height: 72px;

    min-width: 72px;

    border-radius: 16px;

  }

  .company-profile-title h3 {

    font-size: 21px;

  }

  .back-btn {

    width: 100%;

  }

  .dashboard-section-card :deep(.card-body) {

    padding: 18px;

  }

  .section-header {

    flex-direction: column;

  }

}

@media (max-width: 575.98px) {

  .export-modal-footer { flex-direction: column-reverse; }
  .export-modal-footer .btn { width: 100%; }
  .export-modal-title-wrap { align-items: flex-start; }


  .company-profile-main {

    flex-direction: column;

  }

  .company-contact-grid {

    flex-direction: column;

    align-items: flex-start;

  }

  .stat-card {

    min-height: 108px;

    padding: 16px;

  }

  .stat-value {

    font-size: 23px;

  }

  .performance-header {

    align-items: flex-start;

  }

  .activity-title-row {

    align-items: flex-start;

    flex-direction: column;

    gap: 6px;

  }

  .status-summary-grid,

  .secondary-summary {

    grid-template-columns: 1fr;

  }

}

</style>