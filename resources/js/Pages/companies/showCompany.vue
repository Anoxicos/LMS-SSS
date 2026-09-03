<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'

import Layout from '@/Layouts/main.vue'
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

const statCards = computed(() => [
  {
    label: 'Company Users',
    value: props.stats.users_count || 0,
    icon: 'ri-group-line',
    tone: 'primary',
  },
  {
    label: 'Enrolled Learners',
    value: props.stats.learners_enrolled_count || 0,
    icon: 'ri-user-star-line',
    tone: 'info',
  },
  {
    label: 'Assigned Courses',
    value: props.stats.assigned_courses_count || 0,
    icon: 'ri-book-open-line',
    tone: 'purple',
  },
  {
    label: 'Certificates Issued',
    value: props.stats.certificates_issued_count || 0,
    icon: 'ri-award-line',
    tone: 'success',
  },
  {
    label: 'Company Requests',
    value: props.stats.requests_count || 0,
    icon: 'ri-file-list-3-line',
    tone: 'warning',
  },
  {
    label: 'Enrollments',
    value: props.stats.enrollments_count || 0,
    icon: 'ri-graduation-cap-line',
    tone: 'blue',
  },
  {
    label: 'Completed',
    value: props.stats.completed_enrollments_count || 0,
    icon: 'ri-checkbox-circle-line',
    tone: 'success',
  },
  {
    label: 'Accreditations',
    value: props.stats.accreditations_used_count || 0,
    icon: 'ri-verified-badge-line',
    tone: 'purple',
  },
])

const goBack = () => {
  router.get(route('companies.index'))
}

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatPercent = (value) => {
  const number = Number(value || 0)

  return `${number.toFixed(Number.isInteger(number) ? 0 : 1)}%`
}

const clampPercent = (value) => {
  const number = Number(value || 0)
  return Math.min(Math.max(number, 0), 100)
}

const statusClass = (status) => {
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
</script>

<template>
  <Layout>
    <Head :title="company.name || 'Company Details'" />
    <PageHeader title="Company Details" pageTitle="Companies" />

    <div class="company-details-page">
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
                  <span>Added {{ formatDate(company.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <BButton
            variant="light"
            class="back-btn"
            @click="goBack"
          >
            <i class="ri-arrow-left-line me-1"></i>
            Back to Companies
          </BButton>
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
            </div>
          </div>
        </BCol>
      </BRow>

      <BRow class="g-4 mb-4">
        <BCol xl="8" lg="12">
          <BCard class="dashboard-section-card h-100 border-0 shadow-sm">
            <div class="section-title">
              <div class="section-icon">
                <i class="ri-bar-chart-box-line"></i>
              </div>

              <div>
                <h5>Performance Overview</h5>
                <p>Training, request and exam performance.</p>
              </div>
            </div>

            <div class="performance-list">
              <div class="performance-item">
                <div class="performance-header">
                  <div>
                    <span class="performance-label">Average learning progress</span>
                    <small>Across all company enrollments</small>
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
                    <span class="performance-label">Course completion rate</span>
                    <small>Completed enrollments / all enrollments</small>
                  </div>
                  <strong>{{ formatPercent(stats.completion_rate) }}</strong>
                </div>

                <div class="performance-track">
                  <div
                    class="performance-fill"
                    :style="{ width: `${clampPercent(stats.completion_rate)}%` }"
                  ></div>
                </div>
              </div>

              <div class="performance-item">
                <div class="performance-header">
                  <div>
                    <span class="performance-label">Request approval rate</span>
                    <small>Approved requests / all requests</small>
                  </div>
                  <strong>{{ formatPercent(stats.request_approval_rate) }}</strong>
                </div>

                <div class="performance-track">
                  <div
                    class="performance-fill"
                    :style="{ width: `${clampPercent(stats.request_approval_rate)}%` }"
                  ></div>
                </div>
              </div>

              <div class="performance-item">
                <div class="performance-header">
                  <div>
                    <span class="performance-label">Exam pass rate</span>
                    <small>
                      {{ stats.passed_exam_attempts_count || 0 }} passed /
                      {{ stats.exam_attempts_count || 0 }} submitted attempts
                    </small>
                  </div>
                  <strong>{{ formatPercent(stats.exam_pass_rate) }}</strong>
                </div>

                <div class="performance-track">
                  <div
                    class="performance-fill"
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
              <div class="section-icon">
                <i class="ri-file-chart-line"></i>
              </div>

              <div>
                <h5>Activity Summary</h5>
                <p>Requests and training status.</p>
              </div>
            </div>

            <div class="summary-subtitle">Requests</div>

            <div class="request-summary">
              <div class="request-summary-row">
                <div class="request-summary-left">
                  <span class="request-dot pending"></span>
                  Pending
                </div>
                <strong>{{ stats.pending_requests_count || 0 }}</strong>
              </div>

              <div class="request-summary-row">
                <div class="request-summary-left">
                  <span class="request-dot approved"></span>
                  Approved
                </div>
                <strong>{{ stats.approved_requests_count || 0 }}</strong>
              </div>

              <div class="request-summary-row">
                <div class="request-summary-left">
                  <span class="request-dot rejected"></span>
                  Rejected
                </div>
                <strong>{{ stats.rejected_requests_count || 0 }}</strong>
              </div>
            </div>

            <div class="summary-subtitle summary-subtitle-spaced">Training</div>

            <div class="request-secondary-stats">
              <div>
                <span>Not started</span>
                <strong>{{ stats.not_started_enrollments_count || 0 }}</strong>
              </div>

              <div>
                <span>In progress</span>
                <strong>{{ stats.in_progress_enrollments_count || 0 }}</strong>
              </div>

              <div>
                <span>Completed</span>
                <strong>{{ stats.completed_enrollments_count || 0 }}</strong>
              </div>

              <div>
                <span>Expired</span>
                <strong>{{ stats.expired_enrollments_count || 0 }}</strong>
              </div>
            </div>

            <div class="compact-info-grid">
              <div>
                <span>Requested courses</span>
                <strong>{{ stats.requested_courses_count || 0 }}</strong>
              </div>

              <div>
                <span>Approved courses</span>
                <strong>{{ stats.approved_courses_count || 0 }}</strong>
              </div>

              <div>
                <span>Exam passed</span>
                <strong>{{ stats.passed_exam_attempts_count || 0 }}</strong>
              </div>

              <div>
                <span>Exam failed</span>
                <strong>{{ stats.failed_exam_attempts_count || 0 }}</strong>
              </div>
            </div>
          </BCard>
        </BCol>
      </BRow>

      <BCard class="dashboard-section-card border-0 shadow-sm mb-4">
        <div class="section-title">
          <div class="section-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <div>
            <h5>Course Performance</h5>
            <p>Assignments, learners, completions, certificates and progress.</p>
          </div>
        </div>

        <div v-if="courseStats.length" class="table-responsive d-none d-lg-block">
          <table class="table company-details-table align-middle mb-0">
            <thead>
              <tr>
                <th>Course</th>
                <th class="text-center">Requests</th>
                <th class="text-center">Approved</th>
                <th class="text-center">Learners</th>
                <th class="text-center">Enrollments</th>
                <th class="text-center">Completed</th>
                <th class="text-center">Certificates</th>
                <th>Progress</th>
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
                      <small>{{ formatPercent(course.completion_rate) }} completed</small>
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
                <span>Requests</span>
                <strong>{{ course.requests_count }}</strong>
              </div>

              <div>
                <span>Approved</span>
                <strong>{{ course.approved_requests_count }}</strong>
              </div>

              <div>
                <span>Learners</span>
                <strong>{{ course.learners_count }}</strong>
              </div>

              <div>
                <span>Enrollments</span>
                <strong>{{ course.enrollments_count }}</strong>
              </div>

              <div>
                <span>Completed</span>
                <strong>{{ course.completed_count }}</strong>
              </div>

              <div>
                <span>Certificates</span>
                <strong>{{ course.certificates_count }}</strong>
              </div>
            </div>

            <div class="mt-3">
              <div class="table-progress-header">
                <span>Average progress</span>
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
          <h6>No course data available</h6>
          <p>No enrollment has been created for this company yet.</p>
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
                <h5>Recent Requests</h5>
                <p>Latest company training requests.</p>
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
                      {{ request.learners_count }} learner(s)
                    </span>

                    <span>
                      <i class="ri-verified-badge-line"></i>
                      {{ request.accreditations_count }} accreditation(s)
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
              <h6>No requests</h6>
              <p>This company has no training request yet.</p>
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
                <h5>Recent Certificates</h5>
                <p>Latest certificates delivered to company learners.</p>
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
              <h6>No certificates</h6>
              <p>No certificate has been issued yet.</p>
            </div>
          </BCard>
        </BCol>
      </BRow>
    </div>
  </Layout>
</template>

<style scoped>
.company-details-page {
  padding-bottom: 24px;
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
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 55%, #f5f7ff 100%);
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
  border: 1px solid #e9edf5;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
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
  color: #172033;
  font-size: 25px;
  font-weight: 700;
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
  color: #687cfe;
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
  border: 1px solid #e4e9f2;
}

.stat-card {
  min-height: 112px;
  padding: 20px;
  border: 1px solid #edf0f6;
  border-radius: 18px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.045);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.075);
}

.stat-icon {
  width: 50px;
  height: 50px;
  min-width: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 23px;
}

.stat-primary {
  color: #687cfe;
  background: rgba(104, 124, 254, 0.12);
}

.stat-info {
  color: #299cdb;
  background: rgba(41, 156, 219, 0.12);
}

.stat-success {
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.12);
}

.stat-warning {
  color: #f7b84b;
  background: rgba(247, 184, 75, 0.15);
}

.stat-purple {
  color: #6559cc;
  background: rgba(101, 89, 204, 0.12);
}

.stat-blue {
  color: #405189;
  background: rgba(64, 81, 137, 0.1);
}

.stat-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.stat-value {
  color: #172033;
  font-size: 27px;
  line-height: 1.2;
  margin-top: 4px;
}

.dashboard-section-card {
  border-radius: 18px;
  overflow: hidden;
}

.dashboard-section-card :deep(.card-body) {
  padding: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 24px;
}

.section-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 13px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.section-title h5 {
  margin: 0 0 3px;
  color: #172033;
  font-size: 16px;
  font-weight: 700;
}

.section-title p {
  margin: 0;
  color: #8a94a6;
  font-size: 13px;
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
}

.performance-header strong {
  color: #405189;
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
  background: linear-gradient(90deg, #687cfe 0%, #405189 100%);
  transition: width 0.4s ease;
}

.summary-subtitle {
  margin-bottom: 8px;
  color: #687cfe;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.summary-subtitle-spaced {
  margin-top: 20px;
}

.request-summary {
  border: 1px solid #edf0f5;
  border-radius: 14px;
  overflow: hidden;
}

.request-summary-row {
  min-height: 52px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #edf0f5;
}

.request-summary-row:last-child {
  border-bottom: 0;
}

.request-summary-left {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.request-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
}

.request-dot.pending {
  background: #f7b84b;
}

.request-dot.approved {
  background: #0ab39c;
}

.request-dot.rejected {
  background: #f06548;
}

.request-secondary-stats,
.compact-info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.request-secondary-stats > div,
.compact-info-grid > div {
  padding: 13px;
  border-radius: 12px;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.request-secondary-stats span,
.compact-info-grid span {
  color: #7b8798;
  font-size: 11px;
  font-weight: 600;
}

.request-secondary-stats strong,
.compact-info-grid strong {
  color: #27364f;
  font-size: 18px;
}

.compact-info-grid {
  margin-top: 10px;
}

.company-details-table {
  border: 1px solid #edf0f5;
  border-radius: 14px;
  overflow: hidden;
}

.company-details-table thead {
  background: #687cfe;
}

.company-details-table thead th {
  padding: 14px 16px;
  color: #ffffff;
  border: 0;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.company-details-table tbody td {
  padding: 15px 16px;
  border-color: #edf0f5;
  color: #475569;
  font-size: 13px;
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
  background: #eef0ff;
  color: #687cfe;
  font-size: 10px;
  font-weight: 700;
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
  font-size: 11px;
}

.table-progress-header small {
  color: #94a3b8;
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
  border-bottom: 1px solid #edf0f5;
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
  font-size: 11px;
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
  font-size: 11px;
}

.course-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.course-mobile-card {
  padding: 16px;
  border: 1px solid #edf0f5;
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
  font-size: 37px;
  color: #c5ccda;
  margin-bottom: 10px;
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

@media (max-width: 991.98px) {
  .company-profile-content {
    align-items: flex-start;
  }

  .company-profile-main {
    align-items: flex-start;
  }
}

@media (max-width: 767.98px) {
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
}

@media (max-width: 575.98px) {
  .company-profile-main {
    flex-direction: column;
  }

  .company-contact-grid {
    flex-direction: column;
    align-items: flex-start;
  }

  .company-profile-title {
    align-items: flex-start;
  }

  .stat-card {
    min-height: 96px;
    padding: 16px;
  }

  .stat-value {
    font-size: 23px;
  }

  .activity-title-row {
    align-items: flex-start;
    flex-direction: column;
    gap: 6px;
  }
}

@media (max-width: 420px) {
  .request-secondary-stats,
  .compact-info-grid,
  .course-mobile-grid {
    grid-template-columns: 1fr;
  }
}
</style>
