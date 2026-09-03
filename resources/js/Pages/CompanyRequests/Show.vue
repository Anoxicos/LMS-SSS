<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  companyRequest: {
    type: Object,
    required: true,
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
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

const getStatusClass = (status) => {
  if (status === 'approved') {
    return 'status-badge success'
  }

  if (status === 'rejected') {
    return 'status-badge danger'
  }

  return 'status-badge warning'
}

const getStatusIcon = (status) => {
  if (status === 'approved') return 'ri-checkbox-circle-line'
  if (status === 'rejected') return 'ri-close-circle-line'
  return 'ri-time-line'
}

const getAccreditationClass = (code) => {
  if (String(code).toLowerCase() === 'easa') {
    return 'accreditation-badge easa'
  }

  if (String(code).toLowerCase() === 'dac') {
    return 'accreditation-badge dac'
  }

  return 'accreditation-badge'
}

const getLearnerStatusClass = (status) => {
  if (status === 'approved') {
    return 'badge bg-success-subtle text-success'
  }

  if (status === 'rejected') {
    return 'badge bg-danger-subtle text-danger'
  }

  return 'badge bg-warning-subtle text-warning'
}

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatDateTime = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatTrainingDate = (date) => {
  if (!date) return '-'

  return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatValidityDuration = (months) => {
  if (months === null || months === undefined || months === '') {
    return 'No expiration'
  }

  const value = Number(months)

  if (!Number.isFinite(value) || value <= 0) {
    return 'No expiration'
  }

  return `${value} month${value > 1 ? 's' : ''}`
}

const approveRequest = () => {
  Swal.fire({
    icon: 'question',
    title: 'Approve request?',
    text: 'This will assign the selected course to all learners.',
    showCancelButton: true,
    confirmButtonText: 'Yes, approve',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#0ab39c',
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('company-requests.approve', props.companyRequest.hashid), {}, {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Approved',
            text: 'Request approved successfully.',
            timer: 2000,
            showConfirmButton: false,
          })
        },
      })
    }
  })
}

const rejectRequest = () => {
  Swal.fire({
    icon: 'warning',
    title: 'Reject request?',
    input: 'textarea',
    inputLabel: 'Reason / Notes',
    inputPlaceholder: 'Write the reason...',
    showCancelButton: true,
    confirmButtonText: 'Reject',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#f06548',
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('company-requests.reject', props.companyRequest.hashid), {
        notes: result.value,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Rejected',
            text: 'Request rejected successfully.',
            timer: 2000,
            showConfirmButton: false,
          })
        },
      })
    }
  })
}
</script>

<template>
  <Layout>
    <Head :title="companyRequest.reference" />
    <PageHeader title="Request Details" pageTitle="Company Requests" />

    <div class="request-show-page">
      <!-- HERO -->
      <div class="request-hero">
        <div class="request-hero-main">
          <div class="request-hero-icon">
            <i class="ri-file-list-3-line"></i>
          </div>

          <div class="min-width-0">
            <div class="hero-badges">
              <span :class="getStatusClass(companyRequest.status)">
                <i :class="`${getStatusIcon(companyRequest.status)} me-1`"></i>
                {{ companyRequest.status || 'pending' }}
              </span>

              <span class="hero-mini-badge">
                <i class="ri-calendar-line me-1"></i>
                {{ formatDate(companyRequest.created_at) }}
              </span>

              <span class="hero-mini-badge">
                <i class="ri-team-line me-1"></i>
                {{ companyRequest.learners?.length || 0 }} learners
              </span>

              <span class="hero-mini-badge">
                <i class="ri-calendar-event-line me-1"></i>
                {{ formatTrainingDate(companyRequest.start_date) }}
                -
                {{ formatTrainingDate(companyRequest.end_date) }}
              </span>

              <span class="hero-mini-badge">
                <i class="ri-timer-line me-1"></i>
                {{ formatValidityDuration(companyRequest.duree_validite_formation) }}
              </span>

              <span
                v-for="accreditation in companyRequest.accreditations || []"
                :key="accreditation.id"
                class="hero-mini-badge"
              >
                <i class="ri-shield-check-line me-1"></i>
                {{ accreditation.name }}
              </span>
            </div>

            <h2>{{ companyRequest.reference }}</h2>

            <p>
              Course assignment request created by
              <strong>{{ companyRequest.requested_by?.name || '-' }}</strong>
              for company
              <strong>{{ companyRequest.company?.name || '-' }}</strong>.
            </p>
          </div>
        </div>

        <div class="request-hero-actions">

            <Link :href="route('company-requests.index')" class="btn btn-light">
                <i class="ri-arrow-left-line me-1"></i>
                Back
            </Link>
           <Link
                v-if="companyRequest.status === 'pending' || canApprove"
                :href="route('company-requests.edit', companyRequest.hashid)"
                class="btn btn-warning"
                >
                <i class="ri-pencil-line me-1"></i>
                Edit
            </Link>
          <button
            v-if="canApprove && companyRequest.status === 'pending'"
            type="button"
            class="btn btn-success"
            @click="approveRequest"
          >
            <i class="ri-check-line me-1"></i>
            Approve
          </button>

          <button
            v-if="canApprove && companyRequest.status === 'pending'"
            type="button"
            class="btn btn-danger"
            @click="rejectRequest"
          >
            <i class="ri-close-line me-1"></i>
            Reject
          </button>
        </div>
      </div>

      <!-- QUICK STATS -->
      <div class="request-overview-grid">
        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-building-4-line"></i>
          </div>

          <div class="min-width-0">
            <span>Company</span>
            <strong>{{ companyRequest.company?.name || '-' }}</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <div class="min-width-0">
            <span>Course</span>
            <strong>{{ companyRequest.course?.title || '-' }}</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-team-line"></i>
          </div>

          <div>
            <span>Learners</span>
            <strong>{{ companyRequest.learners?.length || 0 }}</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-shield-check-line"></i>
          </div>

          <div class="min-width-0">
            <span>Accreditations</span>
            <strong>{{ companyRequest.accreditations?.length || 0 }} selected</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-calendar-2-line"></i>
          </div>

          <div>
            <span>Start Date</span>
            <strong>{{ formatTrainingDate(companyRequest.start_date) }}</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-calendar-check-line"></i>
          </div>

          <div>
            <span>End Date</span>
            <strong>{{ formatTrainingDate(companyRequest.end_date) }}</strong>
          </div>
        </div>

        <div class="overview-card">
          <div class="overview-icon">
            <i class="ri-timer-line"></i>
          </div>

          <div>
            <span>Validity</span>
            <strong>
              {{ formatValidityDuration(companyRequest.duree_validite_formation) }}
            </strong>
          </div>
        </div>
      </div>

      <BRow class="g-4">
        <!-- LEFT -->
        <BCol xl="8" lg="8">
          <!-- COURSE CARD -->
          <BCard class="show-card border-0 shadow-sm">
            <BCardHeader class="show-card-header border-0">
              <div class="show-card-title">
                <div class="show-card-icon">
                  <i class="ri-book-open-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-bold">Requested Course</h5>
                  <p class="text-muted mb-0">
                    Course selected by the company admin.
                  </p>
                </div>
              </div>
            </BCardHeader>

            <BCardBody>
              <div class="course-detail-card">
                <div class="course-detail-image">
                  <img
                    v-if="getCourseImage(companyRequest.course)"
                    :src="getCourseImage(companyRequest.course)"
                    alt="Course"
                  />

                  <div v-else class="course-detail-fallback">
                    {{ getCourseInitials(companyRequest.course?.title) }}
                  </div>
                </div>

                <div class="course-detail-content">
                  <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="course-category-badge">
                      <i class="ri-price-tag-3-line me-1"></i>
                      {{ companyRequest.course?.category || 'Course' }}
                    </span>

                    <span
                      v-for="accreditation in companyRequest.accreditations || []"
                      :key="accreditation.id"
                      :class="getAccreditationClass(accreditation.code)"
                    >
                      <i class="ri-shield-check-line"></i>
                      {{ accreditation.name }}
                    </span>
                  </div>

                  <h4>{{ companyRequest.course?.title || '-' }}</h4>

                  <p>
                    {{ companyRequest.course?.description || 'No description available.' }}
                  </p>
                </div>
              </div>
            </BCardBody>
          </BCard>

          <!-- LEARNERS -->
          <BCard class="show-card border-0 shadow-sm mt-4">
            <BCardHeader class="show-card-header border-0">
              <div class="show-card-title">
                <div class="show-card-icon">
                  <i class="ri-team-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-bold">Selected Learners</h5>
                  <p class="text-muted mb-0">
                    Learners included in this request.
                  </p>
                </div>
              </div>

              <span class="count-pill">
                {{ companyRequest.learners?.length || 0 }} learner(s)
              </span>
            </BCardHeader>

            <BCardBody>
              <div
                v-if="companyRequest.learners?.length"
                class="learners-grid"
              >
                <div
                  v-for="learner in companyRequest.learners"
                  :key="learner.id"
                  class="learner-card"
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

                  <span :class="getLearnerStatusClass(learner.status)">
                    {{ learner.status || companyRequest.status || 'pending' }}
                  </span>
                </div>
              </div>

              <div v-else class="empty-box">
                <i class="ri-team-line"></i>
                <h6>No learners found</h6>
                <p>This request does not contain learners.</p>
              </div>
            </BCardBody>
          </BCard>
        </BCol>

        <!-- RIGHT -->
        <BCol xl="4" lg="4">
          <!-- SUMMARY -->
          <BCard class="show-card border-0 shadow-sm">
            <BCardHeader class="show-card-header border-0">
              <div class="show-card-title">
                <div class="show-card-icon">
                  <i class="ri-file-info-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-bold">Request Summary</h5>
                  <p class="text-muted mb-0">
                    Main request information.
                  </p>
                </div>
              </div>
            </BCardHeader>

            <BCardBody>
              <div class="summary-list">
                <div class="summary-item">
                  <span>Reference</span>
                  <strong>{{ companyRequest.reference }}</strong>
                </div>

                <div class="summary-item">
                  <span>Status</span>
                  <strong>{{ companyRequest.status || 'pending' }}</strong>
                </div>

                <div class="summary-item">
                  <span>Training Start</span>
                  <strong>{{ formatTrainingDate(companyRequest.start_date) }}</strong>
                </div>

                <div class="summary-item">
                  <span>Training End</span>
                  <strong>{{ formatTrainingDate(companyRequest.end_date) }}</strong>
                </div>

                <div class="summary-item">
                  <span>Validity Duration</span>
                  <strong>
                    {{ formatValidityDuration(companyRequest.duree_validite_formation) }}
                  </strong>
                  <small>Calculated from the actual course completion date.</small>
                </div>

                <div class="summary-item">
                  <span>Accreditations</span>

                  <div
                    v-if="companyRequest.accreditations?.length"
                    class="summary-accreditations"
                  >
                    <span
                      v-for="accreditation in companyRequest.accreditations"
                      :key="accreditation.id"
                      :class="getAccreditationClass(accreditation.code)"
                    >
                      <i class="ri-shield-check-line"></i>
                      {{ accreditation.name }}
                    </span>
                  </div>

                  <strong v-else>-</strong>
                </div>

                <div class="summary-item">
                  <span>Created At</span>
                  <strong>{{ formatDateTime(companyRequest.created_at) }}</strong>
                </div>

                <div class="summary-item">
                  <span>Validated At</span>
                  <strong>{{ formatDateTime(companyRequest.validated_at) }}</strong>
                </div>

                <div class="summary-item">
                  <span>Requested By</span>
                  <strong>{{ companyRequest.requested_by?.name || '-' }}</strong>
                  <small>{{ companyRequest.requested_by?.email || '-' }}</small>
                </div>

                <div class="summary-item">
                  <span>Validated By</span>
                  <strong>{{ companyRequest.validated_by?.name || '-' }}</strong>
                  <small>{{ companyRequest.validated_by?.email || '-' }}</small>
                </div>
              </div>
            </BCardBody>
          </BCard>

          <!-- TIMELINE -->
          <BCard class="show-card border-0 shadow-sm mt-4">
            <BCardHeader class="show-card-header border-0">
              <div class="show-card-title">
                <div class="show-card-icon">
                  <i class="ri-route-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-bold">Timeline</h5>
                  <p class="text-muted mb-0">
                    Request workflow.
                  </p>
                </div>
              </div>
            </BCardHeader>

            <BCardBody>
              <div class="request-timeline">
                <div class="timeline-item completed">
                  <div class="timeline-dot">
                    <i class="ri-send-plane-line"></i>
                  </div>

                  <div>
                    <h6>Request created</h6>
                    <p>{{ formatDateTime(companyRequest.created_at) }}</p>
                  </div>
                </div>

                <div
                  class="timeline-item"
                  :class="{ completed: companyRequest.status !== 'pending' }"
                >
                  <div class="timeline-dot">
                    <i
                      :class="
                        companyRequest.status === 'rejected'
                          ? 'ri-close-line'
                          : 'ri-shield-check-line'
                      "
                    ></i>
                  </div>

                  <div>
                    <h6>
                      {{
                        companyRequest.status === 'pending'
                          ? 'Waiting for approval'
                          : companyRequest.status === 'approved'
                            ? 'Request approved'
                            : 'Request rejected'
                      }}
                    </h6>

                    <p>
                      {{ companyRequest.validated_at ? formatDateTime(companyRequest.validated_at) : 'Pending' }}
                    </p>
                  </div>
                </div>

                <div
                  class="timeline-item"
                  :class="{ completed: companyRequest.status === 'approved' }"
                >
                  <div class="timeline-dot">
                    <i class="ri-user-follow-line"></i>
                  </div>

                  <div>
                    <h6>Enrollments created</h6>
                    <p>
                      {{
                        companyRequest.status === 'approved'
                          ? `${companyRequest.accreditations?.length || 0} certificate type(s) configured`
                          : 'After approval'
                      }}
                    </p>
                  </div>
                </div>
              </div>
            </BCardBody>
          </BCard>

          <!-- NOTES -->
          <BCard class="show-card border-0 shadow-sm mt-4">
            <BCardHeader class="show-card-header border-0">
              <div class="show-card-title">
                <div class="show-card-icon">
                  <i class="ri-sticky-note-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-bold">Notes</h5>
                  <p class="text-muted mb-0">
                    Request notes or rejection reason.
                  </p>
                </div>
              </div>
            </BCardHeader>

            <BCardBody>
              <div class="notes-box">
                {{ companyRequest.notes || 'No notes available.' }}
              </div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </div>
  </Layout>
</template>

<style scoped>
.request-show-page {
  width: 100%;
}

/* ================= HERO ================= */
.request-hero {
  position: relative;
  overflow: hidden;
  border-radius: 24px;
  padding: 28px;
  margin-bottom: 24px;
  color: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  background: linear-gradient(
    115deg,
    #071126 0%,
    #132b4a 18%,
    #0d8fe9 62%,
    #37cced 100%
  );
  box-shadow:
    0 18px 45px rgba(7, 17, 38, 0.16),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

/* Structured decoration: no circles, bubbles or radial halos */
.request-hero::before {
  content: '';
  position: absolute;
  top: -20%;
  right: 17%;
  width: 1px;
  height: 140%;
  background: linear-gradient(
    to bottom,
    transparent,
    rgba(255, 255, 255, 0.22),
    transparent
  );
  transform: skewX(-18deg);
  pointer-events: none;
}

.request-hero::after {
  content: '';
  position: absolute;
  top: 0;
  right: -72px;
  width: 320px;
  height: 100%;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.09),
    rgba(255, 255, 255, 0.015)
  );
  clip-path: polygon(22% 0, 100% 0, 100% 100%, 0 100%);
  pointer-events: none;
}

.request-hero-main {
  position: relative;
  z-index: 2;
  display: flex;
  gap: 20px;
  align-items: center;
  flex: 1;
  min-width: 0;
}

.request-hero-icon {
  width: 76px;
  height: 76px;
  min-width: 76px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.16);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 34px;
  box-shadow:
    0 10px 25px rgba(7, 17, 38, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(6px);
}

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.status-badge,
.hero-mini-badge {
  display: inline-flex;
  align-items: center;
  min-height: 34px;
  padding: 8px 13px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
}

.status-badge.success {
  background: rgba(10, 179, 156, 0.24);
  color: #ffffff;
  border-color: rgba(10, 179, 156, 0.34);
}

.status-badge.warning {
  background: rgba(247, 184, 75, 0.24);
  color: #ffffff;
  border-color: rgba(247, 184, 75, 0.34);
}

.status-badge.danger {
  background: rgba(240, 101, 72, 0.24);
  color: #ffffff;
  border-color: rgba(240, 101, 72, 0.34);
}

.hero-mini-badge {
  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.96);
}

.request-hero h2 {
  color: #ffffff;
  font-weight: 950;
  font-size: clamp(24px, 3vw, 36px);
  line-height: 1.12;
  letter-spacing: -0.03em;
  margin-bottom: 8px;
}

.request-hero p {
  color: rgba(255, 255, 255, 0.82);
  margin-bottom: 0;
  max-width: 780px;
  font-size: 14px;
  line-height: 1.7;
}

.request-hero p strong {
  color: #ffffff;
  font-weight: 850;
}

.request-hero-actions {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.request-hero-actions .btn {
  min-height: 44px;
  border-radius: 12px;
  font-weight: 900;
  padding-inline: 18px;
  box-shadow: none;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease;
}

.request-hero-actions .btn:hover {
  transform: translateY(-1px);
}

.request-hero-actions .btn-light {
  background: rgba(255, 255, 255, 0.96);
  border-color: rgba(255, 255, 255, 0.96);
  color: #071126;
}

.request-hero-actions .btn-light:hover {
  background: #ffffff;
  border-color: #ffffff;
  color: #071126;
  box-shadow: 0 8px 20px rgba(7, 17, 38, 0.12);
}

.request-hero-actions .btn-warning {
  background: #f7b84b;
  border-color: #f7b84b;
  color: #071126;
}

.request-hero-actions .btn-warning:hover {
  background: #f5ac2c;
  border-color: #f5ac2c;
  color: #071126;
  box-shadow: 0 8px 20px rgba(247, 184, 75, 0.2);
}

.request-hero-actions .btn-success {
  background: #0ab39c;
  border-color: #0ab39c;
  color: #ffffff;
}

.request-hero-actions .btn-danger {
  background: #f06548;
  border-color: #f06548;
  color: #ffffff;
}

/* ================= OVERVIEW ================= */
.request-overview-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-bottom: 24px;
}

.overview-card {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
  border: 1px solid #edf2f7;
  border-radius: 20px;
  padding: 17px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
}

.overview-card::before {
  content: '';
  position: absolute;
  inset: 0 auto 0 0;
  width: 4px;
  background: linear-gradient(180deg, #687cfe, #0ab39c);
}

.overview-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 16px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.overview-card span {
  display: block;
  color: #64748b;
  font-size: 11.5px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.035em;
  margin-bottom: 3px;
}

.overview-card strong {
  display: block;
  color: #1e293b;
  font-size: 14px;
  font-weight: 950;
  text-transform: capitalize;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ================= CARDS ================= */
.show-card {
  border-radius: 20px;
  overflow: hidden;
}

.show-card-header {
  padding: 20px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #eef2f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.show-card-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.show-card-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
}

.count-pill {
  padding: 7px 12px;
  border-radius: 10px;
  background: #eef2ff;
  color: #405189;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

/* ================= COURSE ================= */
.course-detail-card {
  border: 1px solid #edf2f7;
  border-radius: 20px;
  padding: 16px;
  background: #ffffff;
  display: flex;
  gap: 18px;
}

.course-detail-image {
  width: 210px;
  height: 150px;
  min-width: 210px;
  border-radius: 20px;
  overflow: hidden;
  background: #eef2ff;
  border: 1px solid #edf2f7;
}

.course-detail-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-detail-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 38px;
  font-weight: 950;
}

.course-detail-content h4 {
  color: #334155;
  font-weight: 950;
  margin-bottom: 8px;
}

.course-detail-content p {
  color: #64748b;
  margin-bottom: 0;
  line-height: 1.7;
}

.accreditation-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 12px;
  border-radius: 10px;
  background: #f1f5f9;
  color: #475569;
  font-size: 12px;
  font-weight: 850;
}

.accreditation-badge.easa {
  background: rgba(104, 124, 254, 0.13);
  color: #5667d8;
}

.accreditation-badge.dac {
  background: rgba(10, 179, 156, 0.13);
  color: #079884;
}

.summary-accreditations {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.course-category-badge {
  display: inline-flex;
  align-items: center;
  padding: 7px 12px;
  border-radius: 10px;
  background: #eef2ff;
  color: #405189;
  font-size: 12px;
  font-weight: 800;
}

/* ================= LEARNERS ================= */
.learners-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.learner-card {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 12px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 12px;
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
  font-weight: 850;
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

/* ================= SUMMARY ================= */
.summary-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.summary-item {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 13px;
  background: #ffffff;
}

.summary-item span {
  display: block;
  color: #64748b;
  font-size: 11.5px;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.summary-item strong {
  display: block;
  color: #334155;
  font-weight: 900;
}

.summary-item small {
  color: #64748b;
  font-size: 12px;
}

/* ================= TIMELINE ================= */
.request-timeline {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.timeline-item {
  display: flex;
  gap: 12px;
  position: relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 42px;
  left: 19px;
  width: 2px;
  height: calc(100% + 2px);
  background: #e5e7eb;
}

.timeline-dot {
  width: 40px;
  height: 40px;
  min-width: 40px;
  border-radius: 14px;
  background: #f1f5f9;
  color: #94a3b8;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.timeline-item.completed .timeline-dot {
  background: linear-gradient(135deg, #687cfe, #405189);
  color: #ffffff;
}

.timeline-item h6 {
  color: #334155;
  font-weight: 900;
  margin-bottom: 3px;
}

.timeline-item p {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 0;
}

/* ================= NOTES ================= */
.notes-box {
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  color: #64748b;
  border-radius: 16px;
  padding: 15px;
  line-height: 1.7;
  font-weight: 600;
}

/* ================= EMPTY ================= */
.empty-box {
  min-height: 220px;
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

.min-width-0 {
  min-width: 0;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 1399.98px) {
  .request-overview-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .learners-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 991.98px) {
  .request-hero {
    flex-direction: column;
    align-items: stretch;
  }

  .request-hero::after {
    right: -110px;
    width: 250px;
  }

  .request-hero-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .request-hero-actions .btn {
    flex: 0 0 auto;
  }
}

@media (max-width: 767.98px) {
  .request-hero {
    padding: 22px;
    border-radius: 22px;
  }

  .request-hero-main {
    flex-direction: column;
    align-items: flex-start;
  }

  .request-overview-grid {
    grid-template-columns: 1fr;
  }

  .course-detail-card {
    flex-direction: column;
  }

  .course-detail-image {
    width: 100%;
    height: 190px;
    min-width: 100%;
  }

  .show-card-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 575.98px) {
  .request-hero {
    padding: 20px;
    border-radius: 18px;
  }

  .request-hero::before {
    display: none;
  }

  .request-hero::after {
    right: -165px;
    width: 250px;
  }

  .request-hero-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .request-hero-actions .btn {
    width: 100%;
  }

  .request-main-icon,
  .request-hero-icon {
    width: 58px;
    height: 58px;
    min-width: 58px;
    font-size: 27px;
  }

  .learner-card {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 420px) {
  .request-hero-actions {
    grid-template-columns: 1fr;
  }
}
</style>