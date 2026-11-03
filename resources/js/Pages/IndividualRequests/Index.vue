<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  individualRequests: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
})

/* ================= STATE ================= */
const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
let searchTimer = null

/* ================= FILTERS ================= */
const applyFilters = () => {
  router.get(
    route('individual-requests.index'),
    {
      search: search.value || undefined,
      status: status.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

watch(search, () => {
  window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(applyFilters, 350)
})

watch(status, applyFilters)

const resetFilters = () => {
  search.value = ''
  status.value = ''
}

/* ================= HELPERS ================= */
const statusLabel = value => {
  const labels = {
    pending: t('ui-pending'),
    approved: t('ui-approved'),
    partially_approved: t('ui-partially-approved'),
    rejected: t('ui-rejected'),
  }

  return labels[value] || value || '-'
}

const statusClass = value => {
  if (value === 'approved') {
    return 'badge bg-success-subtle text-success'
  }

  if (value === 'rejected') {
    return 'badge bg-danger-subtle text-danger'
  }

  if (value === 'partially_approved') {
    return 'badge bg-info-subtle text-info'
  }

  return 'badge bg-warning-subtle text-warning'
}

const statusIcon = value => {
  if (value === 'approved') return 'ri-checkbox-circle-line'
  if (value === 'rejected') return 'ri-close-circle-line'
  if (value === 'partially_approved') return 'ri-progress-5-line'

  return 'ri-time-line'
}

const formatDate = value => {
  if (!value) return '-'

  return new Date(value).toLocaleString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getLearnerInitials = name => {
  if (!name) return 'LR'

  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word.charAt(0).toUpperCase())
    .join('')
}

const approvedCount = item => Number(item.approved_courses_count || 0)
const rejectedCount = item => Number(item.rejected_courses_count || 0)
const totalCourses = item => Number(item.items_count || 0)

const fromResult = () => {
  if (!props.individualRequests?.data?.length) return 0

  return Number(props.individualRequests.from || 1)
}

const toResult = () => {
  if (!props.individualRequests?.data?.length) return 0

  return Number(
    props.individualRequests.to ||
      fromResult() + props.individualRequests.data.length - 1
  )
}
</script>

<template>
  <Head :title="$t('ui-individual-requests')" />

  <PageHeader :title="$t('ui-individual-requests')" :page-title="$t('ui-lms')" />

  <div>
    <BCard class="request-card border-0 shadow-sm">
      <!-- Responsive Header -->
      <BCardHeader class="request-card-header border-0">
        <BRow class="g-3 align-items-center request-header-row">
          <BCol xl="5" lg="12" md="12" cols="12">
            <div class="request-header-title">
              <div class="request-header-icon">
                <i class="ri-user-received-2-line"></i>
              </div>

              <div class="min-width-0">
                <h5 class="mb-1 fw-semibold">{{ $t('ui-individual-requests') }}</h5>

                <p class="text-muted mb-0">
                  {{ $t('ui-review-authenticated-learner-requests-and-decide-course-by-course') }}
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="7" lg="12" md="12" cols="12">
            <div class="request-header-actions">
              <div class="status-filter-box">
                <select
                  v-model="status"
                  class="form-select status-filter-select"
                >
                  <option value="">{{ $t('ui-all-statuses') }}</option>
                  <option value="pending">{{ $t('ui-pending') }}</option>
                  <option value="approved">{{ $t('ui-approved') }}</option>
                  <option value="partially_approved">
                    {{ $t('ui-partially-approved') }}
                  </option>
                  <option value="rejected">{{ $t('ui-rejected') }}</option>
                </select>
              </div>

              <div class="search-box request-search-box">
                <input
                  v-model="search"
                  type="search"
                  class="form-control"
                  :placeholder="$t('ui-search-request')"
                />

                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                variant="light"
                class="request-reset-btn"
                type="button"
                :title="$t('t-reset-filters')"
                @click="resetFilters"
              >
                <i class="ri-refresh-line"></i>
                <span>{{ $t('ui-reset') }}</span>
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <!-- Desktop Table -->
        <div
          v-if="individualRequests.data?.length"
          class="request-table-wrapper d-none d-xl-block"
        >
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 request-table">
              <thead>
                <tr>
                  <th>{{ $t('ui-reference') }}</th>
                  <th>{{ $t('ui-learner') }}</th>
                  <th>{{ $t('ui-courses') }}</th>
                  <th class="text-center">{{ $t('ui-status') }}</th>
                  <th>{{ $t('ui-submitted') }}</th>
                  <th class="text-center">{{ $t('ui-actions') }}</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="item in individualRequests.data"
                  :key="item.id || item.hashid"
                >
                  <td>
                    <div>
                      <h6 class="mb-1 fw-semibold request-ref-text">
                        {{ item.reference || '-' }}
                      </h6>

                      <small class="text-muted">
                        {{ $t('ui-individual-request') }}
                      </small>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div class="learner-avatar">
                        {{ getLearnerInitials(item.learner?.name) }}
                      </div>

                      <div class="min-width-0">
                        <h6 class="mb-1 fw-semibold learner-name">
                          {{ item.learner?.name || '-' }}
                        </h6>

                        <small class="text-muted learner-email">
                          {{ item.learner?.email || '-' }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="course-summary-badges">
                      <span class="courses-count-badge">
                        <i class="ri-book-open-line"></i>
                        {{ totalCourses(item) }}
                        {{ $t('ui-course-label') }}{{ totalCourses(item) !== 1 ? 's' : '' }}
                      </span>

                      <span
                        v-if="approvedCount(item)"
                        class="course-state-badge approved"
                      >
                        <i class="ri-check-line"></i>
                        {{ approvedCount(item) }} {{ $t('t-approved') }}
                      </span>

                      <span
                        v-if="rejectedCount(item)"
                        class="course-state-badge rejected"
                      >
                        <i class="ri-close-line"></i>
                        {{ rejectedCount(item) }} {{ $t('t-rejected') }}
                      </span>
                    </div>
                  </td>

                  <td class="text-center">
                    <span :class="statusClass(item.status)">
                      <i :class="`${statusIcon(item.status)} me-1`"></i>
                      {{ statusLabel(item.status) }}
                    </span>
                  </td>

                  <td>
                    <span class="date-badge">
                      <i class="ri-calendar-line"></i>
                      {{ formatDate(item.submitted_at) }}
                    </span>
                  </td>

                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <Link
                        v-if="item.hashid"
                        :href="route('individual-requests.show', item.hashid)"
                        class="btn btn-sm btn-soft-info action-btn"
                        :title="$t('ui-view')"
                      >
                        <i class="ri-eye-line"></i>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tablet / Mobile Cards -->
        <div
          v-if="individualRequests.data?.length"
          class="request-mobile-list d-xl-none"
        >
          <div
            v-for="item in individualRequests.data"
            :key="item.id || item.hashid"
            class="request-mobile-card"
          >
            <div class="request-mobile-header">
              <div class="d-flex align-items-center gap-3 min-width-0">
                <div class="learner-avatar mobile-avatar">
                  {{ getLearnerInitials(item.learner?.name) }}
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold request-mobile-title">
                    {{ item.reference || '-' }}
                  </h6>

                  <span :class="statusClass(item.status)">
                    <i :class="`${statusIcon(item.status)} me-1`"></i>
                    {{ statusLabel(item.status) }}
                  </span>
                </div>
              </div>

              <div class="d-flex gap-2">
                <Link
                  v-if="item.hashid"
                  :href="route('individual-requests.show', item.hashid)"
                  class="btn btn-sm btn-soft-info action-btn"
                  :title="$t('ui-view')"
                >
                  <i class="ri-eye-line"></i>
                </Link>
              </div>
            </div>

            <div class="request-mobile-body">
              <div class="request-mobile-item">
                <span class="request-mobile-label">
                  <i class="ri-user-line me-1"></i>
                  {{ $t('ui-learner') }}
                </span>

                <div class="request-mobile-value-block">
                  <strong>{{ item.learner?.name || '-' }}</strong>
                  <small>{{ item.learner?.email || '-' }}</small>
                </div>
              </div>

              <div class="request-mobile-item">
                <span class="request-mobile-label">
                  <i class="ri-book-open-line me-1"></i>
                  {{ $t('ui-courses') }}
                </span>

                <div class="course-summary-badges mobile-course-badges">
                  <span class="courses-count-badge">
                    {{ totalCourses(item) }}
                    {{ $t('ui-course-label') }}{{ totalCourses(item) !== 1 ? 's' : '' }}
                  </span>

                  <span
                    v-if="approvedCount(item)"
                    class="course-state-badge approved"
                  >
                    {{ approvedCount(item) }} {{ $t('t-approved') }}
                  </span>

                  <span
                    v-if="rejectedCount(item)"
                    class="course-state-badge rejected"
                  >
                    {{ rejectedCount(item) }} {{ $t('t-rejected') }}
                  </span>
                </div>
              </div>

              <div class="request-mobile-item">
                <span class="request-mobile-label">
                  <i class="ri-calendar-line me-1"></i>
                  {{ $t('ui-submitted') }}
                </span>

                <span class="request-mobile-value">
                  {{ formatDate(item.submitted_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div
          v-if="!individualRequests.data?.length"
          class="text-center py-5"
        >
          <div class="empty-request-icon mx-auto">
            <i class="ri-inbox-2-line"></i>
          </div>

          <h5 class="mt-3 mb-1">{{ $t('ui-no-individual-requests-found') }}</h5>

          <p class="text-muted mb-0">
            {{ $t('ui-change-the-filters-or-wait-for-a-learner-to-submit-a-request') }}
          </p>
        </div>

        <!-- Server Pagination -->
        <div
          v-if="individualRequests.data?.length"
          class="request-pagination"
        >
          <div class="text-muted request-pagination-text">
            {{ $t('ui-showing') }}
            <span class="fw-semibold">{{ fromResult() }}</span>
            {{ $t('ui-to') }}
            <span class="fw-semibold">{{ toResult() }}</span>
            {{ $t('ui-of') }}
            <span class="fw-semibold">{{ individualRequests.total || 0 }}</span>
            {{ $t('ui-result-s') }}
          </div>

          <ul
            v-if="individualRequests.links?.length > 3"
            class="pagination listjs-pagination mb-0"
          >
            <li
              v-for="link in individualRequests.links"
              :key="link.label"
              class="page-item"
              :class="{
                active: link.active,
                disabled: !link.url,
              }"
            >
              <Link
                :href="link.url || '#'"
                class="page-link"
                preserve-scroll
                preserve-state
              >
                <span v-html="link.label"></span>
              </Link>
            </li>
          </ul>
        </div>
      </BCardBody>
    </BCard>
  </div>
</template>

<style scoped>
.request-card {
  border-radius: 18px;
  overflow: hidden;
}

/* ================= HEADER ================= */
.request-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.request-header-row {
  width: 100%;
}

.request-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.request-header-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.25);
}

.request-header-title h5 {
  color: #334155;
}

.request-header-title p {
  font-size: 13px;
}

.request-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.status-filter-box {
  width: 220px;
  min-width: 200px;
}

.status-filter-select {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
  color: #334155;
  background-color: #ffffff;
  font-weight: 600;
}

.status-filter-select:focus {
  border-color: #687cfe !important;
  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14) !important;
}

.request-search-box {
  width: 320px;
  min-width: 260px;
  position: relative;
}

.request-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
  padding-left: 38px;
}

.request-search-box .form-control:focus {
  border-color: #687cfe !important;
  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14) !important;
}

.request-search-box .search-icon {
  position: absolute;
  top: 50%;
  left: 13px;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 16px;
  pointer-events: none;
}

.request-reset-btn {
  height: 42px;
  min-width: 92px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  color: #475569;
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-weight: 700;
  white-space: nowrap;
}

.request-reset-btn:hover {
  border-color: #687cfe;
  color: #405189;
  background: #f8faff;
}

/* ================= TABLE ================= */
.request-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.request-table thead {
  background-color: #687cfe;
}

.request-table thead th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.request-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.request-table tbody tr:last-child td {
  border-bottom: 0;
}

.request-table tbody tr:hover {
  background-color: #f8fafc;
}

.request-ref-text {
  color: #334155;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ================= LEARNER ================= */
.learner-avatar {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
}

.learner-name {
  color: #334155;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.learner-email {
  display: block;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ================= BADGES ================= */
.badge {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.course-summary-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  max-width: 250px;
}

.courses-count-badge,
.course-state-badge,
.date-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  white-space: nowrap;
}

.courses-count-badge {
  padding: 7px 10px;
  border-radius: 12px;
  background: #eef2ff;
  color: #405189;
  font-size: 11.5px;
  font-weight: 800;
}

.course-state-badge {
  padding: 6px 9px;
  border-radius: 10px;
  font-size: 10.5px;
  font-weight: 800;
}

.course-state-badge.approved {
  background: rgba(10, 179, 156, 0.11);
  color: #078c79;
}

.course-state-badge.rejected {
  background: rgba(240, 101, 72, 0.11);
  color: #d94f35;
}

.date-badge {
  padding: 7px 10px;
  border-radius: 12px;
  background: rgba(41, 156, 219, 0.1);
  color: #247fae;
  font-size: 11.5px;
  font-weight: 750;
  line-height: 1.35;
}

.action-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* ================= MOBILE CARDS ================= */
.request-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.request-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.request-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-avatar {
  width: 56px;
  height: 56px;
  min-width: 56px;
  border-radius: 14px;
}

.request-mobile-title {
  color: #334155;
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.request-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.request-mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.request-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  min-width: 115px;
}

.request-mobile-value {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.request-mobile-value-block {
  min-width: 0;
  text-align: right;
}

.request-mobile-value-block strong,
.request-mobile-value-block small {
  display: block;
}

.request-mobile-value-block strong {
  color: #334155;
  font-size: 13px;
  font-weight: 700;
}

.request-mobile-value-block small {
  margin-top: 2px;
  color: #64748b;
  font-size: 11px;
  word-break: break-word;
}

.mobile-course-badges {
  justify-content: flex-end;
  max-width: 100%;
}

/* ================= PAGINATION ================= */
.request-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.request-pagination-text {
  font-size: 14px;
}

.pagination {
  flex-wrap: wrap;
}

.pagination .page-link {
  border-radius: 10px;
  margin: 0 3px;
  border: 1px solid #e5e7eb;
  color: #475569;
  min-width: 36px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.pagination .page-item.active .page-link {
  background-color: #687cfe;
  border-color: #687cfe;
  color: #ffffff;
}

.pagination .page-item.disabled .page-link {
  opacity: 0.45;
  pointer-events: none;
}

/* ================= EMPTY ================= */
.empty-request-icon {
  width: 86px;
  height: 86px;
  border-radius: 24px;
  background: #eef2ff;
  color: #405189;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
}

.min-width-0 {
  min-width: 0;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 1199.98px) {
  .request-header-actions {
    justify-content: flex-start;
  }

  .status-filter-box {
    width: 230px;
  }

  .request-search-box {
    flex: 1;
    width: auto;
    min-width: 240px;
  }
}

@media (max-width: 991.98px) {
  .request-card-header {
    padding: 18px;
  }

  .request-pagination {
    justify-content: center;
    text-align: center;
  }

  .request-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
  }
}

@media (max-width: 767.98px) {
  .request-header-title {
    align-items: flex-start;
  }

  .request-header-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .request-header-title h5 {
    font-size: 17px;
  }

  .request-header-title p {
    font-size: 13px;
  }

  .request-header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .status-filter-box,
  .status-filter-select,
  .request-search-box,
  .request-reset-btn {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 575.98px) {
  .request-card {
    border-radius: 14px;
  }

  .request-card-header {
    padding: 16px;
  }

  .request-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .request-mobile-header {
    flex-direction: column;
  }

  .request-mobile-header > .d-flex:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  .request-mobile-title {
    max-width: 220px;
  }

  .request-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .request-mobile-value,
  .request-mobile-value-block {
    text-align: left;
  }

  .mobile-course-badges {
    justify-content: flex-start;
  }

  .pagination .page-link {
    min-width: 34px;
    height: 34px;
    padding: 6px 10px;
  }
}

@media (max-width: 420px) {
  .request-header-title {
    flex-direction: column;
  }
}
</style>
