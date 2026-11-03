<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  individualRequest: {
    type: Object,
    required: true,
  },
  passportDownloadUrl: {
    type: String,
    default: null,
  },
})

const isPending = computed(() => {
  return props.individualRequest.status === 'pending'
})

const approvedCount = computed(() => {
  return (props.individualRequest.items || []).filter(item => item.status === 'approved').length
})

const rejectedCount = computed(() => {
  return (props.individualRequest.items || []).filter(item => item.status === 'rejected').length
})

const form = useForm({
  admin_note: props.individualRequest.admin_note || '',
  decisions: (props.individualRequest.items || []).map(item => ({
    item_id: item.id,
    status: item.status === 'pending' ? '' : item.status,
    note: item.admin_note || '',
    start_date: item.start_date || '',
    end_date: item.end_date || '',
    duree_validite_formation: item.duree_validite_formation || '',
  })),
})

const approveAll = () => {
  form.decisions.forEach(decision => {
    decision.status = 'approved'
  })
}

const rejectAll = () => {
  form.decisions.forEach(decision => {
    decision.status = 'rejected'
  })
}

const hasIncompleteDecisions = computed(() => {
  return form.decisions.some(
    decision => !['approved', 'rejected'].includes(decision.status)
  )
})

const submitReview = () => {
  form.post(
    route('individual-requests.review', props.individualRequest.hashid),
    {
      preserveScroll: true,
    }
  )
}

const formatDateTime = value => {
  if (!value) return '-'

  return new Date(value).toLocaleString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatDate = value => {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const statusLabel = status => {
  const labels = {
    pending: t('t-pending'),
    approved: t('t-approved'),
    partially_approved: t('t-partially-approved'),
    rejected: t('t-rejected'),
  }

  return labels[status] || status || '-'
}

const getStatusClass = status => {
  if (status === 'approved') return 'status-badge success'
  if (status === 'partially_approved') return 'status-badge info'
  if (status === 'rejected') return 'status-badge danger'
  return 'status-badge warning'
}

const getStatusIcon = status => {
  if (status === 'approved') return 'ri-checkbox-circle-line'
  if (status === 'partially_approved') return 'ri-checkbox-multiple-line'
  if (status === 'rejected') return 'ri-close-circle-line'
  return 'ri-time-line'
}

const getItemStatusClass = status => {
  if (status === 'approved') return 'item-status approved'
  if (status === 'rejected') return 'item-status rejected'
  return 'item-status pending'
}

const getCourseImage = course => {
  if (course?.image_url) return course.image_url
  if (course?.image) return `/storage/${course.image}`
  return null
}

const getCourseInitials = title => {
  if (!title) return 'CO'

  return String(title)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word.charAt(0).toUpperCase())
    .join('')
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

const formatPrice = value => {
  const number = Number(value || 0)
  return Number.isFinite(number) ? number.toFixed(2) : '0.00'
}
</script>

<template>
  <Head :title="$t('ui-individual-request-reference', { reference: individualRequest.reference })" />
  <PageHeader :title="$t('ui-request-details')" :page-title="$t('ui-individual-requests')" />

  <section class="request-show-page">
    <!-- HERO -->
    <header class="request-hero">
      <div class="request-hero-main">
        <div class="request-hero-icon">
          <i class="ri-user-star-line"></i>
        </div>

        <div class="min-width-0">
          <div class="hero-badges">
            <span :class="getStatusClass(individualRequest.status)">
              <i :class="`${getStatusIcon(individualRequest.status)} me-1`"></i>
              {{ statusLabel(individualRequest.status) }}
            </span>

            <span class="hero-mini-badge">
              <i class="ri-calendar-line me-1"></i>
              {{ formatDateTime(individualRequest.submitted_at) }}
            </span>

            <span class="hero-mini-badge">
              <i class="ri-book-open-line me-1"></i>
              {{ individualRequest.items?.length || 0 }} {{ $t('ui-course-s') }}
            </span>

            <span class="hero-mini-badge">
              <i class="ri-user-line me-1"></i>
              {{ individualRequest.learner?.name || '-' }}
            </span>
          </div>

          <h1>{{ individualRequest.reference }}</h1>

          <p>
            {{ $t('t-individual-request-intro') }}
            <strong>{{ individualRequest.learner?.name || '-' }}</strong>
            {{ $t('t-individual-request-description') }}
          </p>
        </div>
      </div>

      <div class="request-hero-actions">
        <Link :href="route('individual-requests.index')" class="btn btn-light">
          <i class="ri-arrow-left-line me-1"></i>
          {{ $t('t-back') }}
        </Link>

        <a
          v-if="passportDownloadUrl"
          :href="passportDownloadUrl"
          class="btn btn-passport"
        >
          <i class="ri-download-2-line me-1"></i>
          {{ $t('t-passport') }}
        </a>
      </div>
    </header>

    <!-- OVERVIEW -->
    <div class="request-overview-grid">
      <div class="overview-card">
        <div class="overview-icon">
          <i class="ri-user-3-line"></i>
        </div>
        <div class="min-width-0">
          <span>{{ $t('t-learner') }}</span>
          <strong>{{ individualRequest.learner?.name || '-' }}</strong>
        </div>
      </div>

      <div class="overview-card">
        <div class="overview-icon">
          <i class="ri-mail-line"></i>
        </div>
        <div class="min-width-0">
          <span>{{ $t('t-email') }}</span>
          <strong>{{ individualRequest.learner?.email || '-' }}</strong>
        </div>
      </div>

      <div class="overview-card">
        <div class="overview-icon">
          <i class="ri-book-open-line"></i>
        </div>
        <div>
          <span>{{ $t('t-courses') }}</span>
          <strong>{{ individualRequest.items?.length || 0 }}</strong>
        </div>
      </div>

      <div class="overview-card">
        <div class="overview-icon">
          <i class="ri-checkbox-circle-line"></i>
        </div>
        <div>
          <span>{{ $t('t-approved') }}</span>
          <strong>{{ approvedCount }}</strong>
        </div>
      </div>

      <div class="overview-card">
        <div class="overview-icon danger-icon">
          <i class="ri-close-circle-line"></i>
        </div>
        <div>
          <span>{{ $t('t-rejected') }}</span>
          <strong>{{ rejectedCount }}</strong>
        </div>
      </div>

      <div class="overview-card">
        <div class="overview-icon">
          <i class="ri-earth-line"></i>
        </div>
        <div class="min-width-0">
          <span>{{ $t('t-country') }}</span>
          <strong>{{ individualRequest.learner?.country || '-' }}</strong>
        </div>
      </div>
    </div>

    <div class="request-layout">
      <!-- LEFT COLUMN -->
      <aside class="request-left-column">
        <section class="show-card learner-card">
          <header class="show-card-header">
            <div class="show-card-title">
              <div class="show-card-icon">
                <i class="ri-user-star-line"></i>
              </div>

              <div>
                <h2>{{ $t('t-learner-account') }}</h2>
                <p>{{ $t('t-personal-info-linked-request') }}</p>
              </div>
            </div>
          </header>

          <div class="show-card-body">
            <div class="learner-profile">
              <div class="learner-avatar">
                <img
                  v-if="individualRequest.learner?.photo_url"
                  :src="individualRequest.learner.photo_url"
                  :alt="$t('ui-learner')"
                >
                <span v-else>
                  {{ getLearnerInitials(individualRequest.learner?.name) }}
                </span>
              </div>

              <div class="min-width-0">
                <h3>{{ individualRequest.learner?.name || '-' }}</h3>
                <p>{{ individualRequest.learner?.email || '-' }}</p>
              </div>
            </div>

            <div class="summary-list learner-summary-list">
              <div class="summary-item">
                <span>{{ $t('t-country') }}</span>
                <strong>{{ individualRequest.learner?.country || '-' }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-date-of-birth') }}</span>
                <strong>{{ individualRequest.learner?.date_of_birth || '-' }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-place-of-birth') }}</span>
                <strong>{{ individualRequest.learner?.place_of_birth || '-' }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-passport-number') }}</span>
                <strong>{{ individualRequest.learner?.passport_number || '-' }}</strong>
              </div>
            </div>

            <a
              v-if="passportDownloadUrl"
              :href="passportDownloadUrl"
              class="passport-button"
            >
              <i class="ri-download-2-line"></i>
              {{ $t('t-download-passport') }}
            </a>
          </div>
        </section>

        <section class="show-card mt-4">
          <header class="show-card-header">
            <div class="show-card-title">
              <div class="show-card-icon">
                <i class="ri-file-info-line"></i>
              </div>

              <div>
                <h2>{{ $t('t-request-summary') }}</h2>
                <p>{{ $t('t-main-request-information') }}</p>
              </div>
            </div>
          </header>

          <div class="show-card-body">
            <div class="summary-list">
              <div class="summary-item">
                <span>{{ $t('t-reference') }}</span>
                <strong>{{ individualRequest.reference }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-status') }}</span>
                <strong>{{ statusLabel(individualRequest.status) }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-submitted-at') }}</span>
                <strong>{{ formatDateTime(individualRequest.submitted_at) }}</strong>
              </div>

              <div class="summary-item">
                <span>{{ $t('t-requested-courses') }}</span>
                <strong>{{ individualRequest.items?.length || 0 }}</strong>
              </div>
            </div>
          </div>
        </section>

        <section v-if="individualRequest.learner_note" class="show-card mt-4">
          <header class="show-card-header">
            <div class="show-card-title">
              <div class="show-card-icon">
                <i class="ri-sticky-note-line"></i>
              </div>

              <div>
                <h2>{{ $t('t-learner-note') }}</h2>
                <p>{{ $t('ui-message-submitted-with-the-request') }}</p>
              </div>
            </div>
          </header>

          <div class="show-card-body">
            <div class="notes-box">
              {{ individualRequest.learner_note }}
            </div>
          </div>
        </section>
      </aside>

      <!-- RIGHT COLUMN -->
      <form class="request-right-column" @submit.prevent="submitReview">
        <section class="show-card courses-card">
          <header class="show-card-header courses-header">
            <div class="show-card-title">
              <div class="show-card-icon">
                <i class="ri-book-open-line"></i>
              </div>

              <div>
                <h2>{{ $t('t-requested-courses') }}</h2>
                <p>{{ $t('t-review-course-enrollment-conditions') }}</p>
              </div>
            </div>

            <div v-if="isPending" class="bulk-actions">
              <button type="button" class="approve-all" @click="approveAll">
                <i class="ri-check-double-line"></i>
                {{ $t('t-approve-all') }}
              </button>

              <button type="button" class="reject-all" @click="rejectAll">
                <i class="ri-close-circle-line"></i>
                {{ $t('t-reject-all') }}
              </button>
            </div>
          </header>

          <div class="show-card-body course-list">
            <article
              v-for="(item, index) in individualRequest.items"
              :key="item.id"
              class="course-decision-card"
            >
              <div class="course-summary">
                <div class="course-image">
                  <img
                    v-if="getCourseImage(item.course)"
                    :src="getCourseImage(item.course)"
                    :alt="item.course?.title || 'Course'"
                  >

                  <span v-else class="course-image-fallback">
                    {{ getCourseInitials(item.course?.title) }}
                  </span>
                </div>

                <div class="course-copy min-width-0">
                  <div class="course-meta-row">
                    <span class="course-category">
                      <i class="ri-price-tag-3-line"></i>
                      {{ item.course?.category?.name || 'Course' }}
                    </span>

                    <span :class="getItemStatusClass(item.status)">
                      {{ item.status || 'pending' }}
                    </span>
                  </div>

                  <h3>{{ item.course?.title || '-' }}</h3>

                  <p>
                    {{ formatPrice(item.price_snapshot) }} {{ item.currency_snapshot }}
                    <span class="separator">·</span>
                    {{ item.course?.content_type || 'normal' }}
                  </p>
                </div>

                <div class="decision-buttons">
                  <label
                    :class="{ selected: form.decisions[index].status === 'approved' }"
                  >
                    <input
                      v-model="form.decisions[index].status"
                      type="radio"
                      value="approved"
                      :disabled="!isPending"
                    >
                    <i class="ri-check-line"></i>
                    {{ $t('t-approve') }}
                  </label>

                  <label
                    class="reject-choice"
                    :class="{ selected: form.decisions[index].status === 'rejected' }"
                  >
                    <input
                      v-model="form.decisions[index].status"
                      type="radio"
                      value="rejected"
                      :disabled="!isPending"
                    >
                    <i class="ri-close-line"></i>
                    {{ $t('t-reject') }}
                  </label>
                </div>
              </div>

              <div class="decision-fields">
                <label>
                  <span>
                    <i class="ri-calendar-event-line"></i>
                    {{ $t('t-planned-start-date') }}
                  </span>
                  <input
                    v-model="form.decisions[index].start_date"
                    type="date"
                    :disabled="!isPending"
                  >
                </label>

                <label>
                  <span>
                    <i class="ri-calendar-check-line"></i>
                    {{ $t('t-planned-end-date') }}
                  </span>
                  <input
                    v-model="form.decisions[index].end_date"
                    type="date"
                    :disabled="!isPending"
                  >
                </label>

                <label>
                  <span>
                    <i class="ri-timer-line"></i>
                    {{ $t('t-validity-in-months') }}
                  </span>
                  <input
                    v-model="form.decisions[index].duree_validite_formation"
                    type="number"
                    min="1"
                    max="1200"
                    :disabled="!isPending"
                  >
                </label>

                <label class="note-field">
                  <span>
                    <i class="ri-sticky-note-line"></i>
                    {{ $t('t-admin-note-course') }}
                  </span>
                  <textarea
                    v-model="form.decisions[index].note"
                    rows="3"
                    :disabled="!isPending"
                    :placeholder="$t('ui-add-an-administrative-note')"
                  ></textarea>
                </label>
              </div>

              <div v-if="item.enrollment" class="enrollment-line">
                <i class="ri-checkbox-circle-line"></i>
                <div>
                  <strong>{{ $t('t-enrollment-created') }}</strong>
                  <span>
                    {{ item.enrollment.status }} ·
                    {{ item.enrollment.attempts_rest }} {{ $t('ui-exam-attempt-s') }}
                  </span>
                </div>
              </div>
            </article>
          </div>
        </section>

        <section class="show-card mt-4">
          <header class="show-card-header">
            <div class="show-card-title">
              <div class="show-card-icon">
                <i class="ri-admin-line"></i>
              </div>

              <div>
                <h2>{{ $t('t-super-admin-note') }}</h2>
                <p>{{ $t('t-general-admin-note-request') }}</p>
              </div>
            </div>
          </header>

          <div class="show-card-body">
            <label class="general-note">
              <textarea
                v-model="form.admin_note"
                rows="4"
                :disabled="!isPending"
                :placeholder="$t('ui-write-a-general-note-for-this-request')"
              ></textarea>
            </label>

            <div
              v-if="form.errors.request || form.errors.decisions || form.errors.course"
              class="form-error"
            >
              <i class="ri-error-warning-line"></i>
              {{ form.errors.request || form.errors.decisions || form.errors.course }}
            </div>
          </div>

          <footer v-if="isPending" class="form-footer">
            <div class="footer-info">
              <i class="ri-information-line"></i>
              {{ $t('t-decision-required-each-course') }}
            </div>

            <button
              type="submit"
              :disabled="form.processing || hasIncompleteDecisions"
            >
              <i
                :class="form.processing ? 'ri-loader-4-line spinner' : 'ri-check-double-line'"
              ></i>
              {{ form.processing ? 'Saving decisions...' : 'Confirm all decisions' }}
            </button>
          </footer>
        </section>
      </form>
    </div>
  </section>
</template>

<style scoped>
.request-show-page {
  width: 100%;
  min-height: 100%;
  padding-bottom: 28px;
  color: #071126;
}

.min-width-0 {
  min-width: 0;
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

.request-hero::before {
  content: '';
  position: absolute;
  top: 0;
  right: 13%;
  width: 1px;
  height: 100%;
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
  right: -85px;
  width: 320px;
  height: 100%;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.08),
    rgba(255, 255, 255, 0.015)
  );
  clip-path: polygon(22% 0, 100% 0, 100% 100%, 0 100%);
  pointer-events: none;
}

.request-hero-main {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 20px;
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
  justify-content: center;
  min-height: 34px;
  padding: 7px 13px;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1;
  font-weight: 800;
  border: 1px solid rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
}

.status-badge.success {
  background: rgba(10, 179, 156, 0.25);
  color: #ffffff;
  border-color: rgba(10, 179, 156, 0.35);
}

.status-badge.info {
  background: rgba(55, 204, 237, 0.24);
  color: #ffffff;
  border-color: rgba(55, 204, 237, 0.35);
}

.status-badge.warning {
  background: rgba(247, 184, 75, 0.22);
  color: #ffffff;
  border-color: rgba(247, 184, 75, 0.32);
}

.status-badge.danger {
  background: rgba(240, 101, 72, 0.23);
  color: #ffffff;
  border-color: rgba(240, 101, 72, 0.32);
}

.hero-mini-badge {
  background: rgba(255, 255, 255, 0.11);
  color: rgba(255, 255, 255, 0.96);
}

.request-hero h1 {
  color: #ffffff;
  font-weight: 900;
  font-size: clamp(26px, 3vw, 38px);
  line-height: 1.12;
  letter-spacing: -0.025em;
  margin: 0 0 9px;
}

.request-hero p {
  max-width: 780px;
  color: rgba(255, 255, 255, 0.82);
  margin: 0;
  font-size: 14px;
  line-height: 1.7;
}

.request-hero p strong {
  color: #ffffff;
  font-weight: 800;
}

.request-hero-actions {
  position: relative;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
  flex-shrink: 0;
}

.request-hero-actions .btn {
  min-height: 46px;
  border-radius: 12px;
  padding: 10px 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
  border-width: 1px;
  box-shadow: none;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease;
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
  box-shadow: 0 8px 20px rgba(7, 17, 38, 0.12);
}

.btn-passport {
  background: #f7b84b;
  border-color: #f7b84b;
  color: #071126;
}

.btn-passport:hover {
  background: #f5ac2c;
  border-color: #f5ac2c;
  color: #071126;
}

/* ================= OVERVIEW ================= */
.request-overview-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}

.overview-card {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
  border: 1px solid #e4edf4;
  border-radius: 18px;
  padding: 17px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.055);
}

.overview-card::before {
  content: '';
  position: absolute;
  inset: 0 auto 0 0;
  width: 4px;
  background: linear-gradient(180deg, #37cced, #0d8fe9);
}

.overview-icon {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 14px;
  background: #eaf7ff;
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.overview-icon.danger-icon {
  background: #fff1ef;
  color: #f06548;
}

.overview-card span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: 0.035em;
  margin-bottom: 3px;
}

.overview-card strong {
  display: block;
  color: #071126;
  font-size: 14px;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ================= LAYOUT ================= */
.request-layout {
  display: grid;
  grid-template-columns: 360px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.request-left-column {
  position: sticky;
  top: 88px;
  min-width: 0;
}

.request-right-column {
  min-width: 0;
}

/* ================= CARDS ================= */
.show-card {
  overflow: hidden;
  border: 1px solid #e4edf4;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
}

.show-card-header {
  padding: 19px 20px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #e4edf4;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.show-card-title {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.show-card-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 13px;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
  box-shadow: 0 8px 18px rgba(13, 143, 233, 0.16);
}

.show-card-title h2 {
  margin: 0 0 3px;
  color: #071126;
  font-size: 17px;
  font-weight: 900;
}

.show-card-title p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.show-card-body {
  padding: 18px;
}

/* ================= LEARNER ================= */
.learner-profile {
  padding: 14px;
  margin-bottom: 16px;
  border: 1px solid #e4edf4;
  border-radius: 16px;
  background: #f8fbfd;
  display: flex;
  align-items: center;
  gap: 12px;
}

.learner-avatar,
.learner-avatar img,
.learner-avatar span {
  width: 54px;
  height: 54px;
  min-width: 54px;
  border-radius: 50%;
}

.learner-avatar img {
  object-fit: cover;
}

.learner-avatar span {
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 900;
}

.learner-profile h3 {
  margin: 0 0 3px;
  color: #071126;
  font-size: 15px;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.learner-profile p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.summary-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.summary-item {
  border: 1px solid #e4edf4;
  border-radius: 13px;
  padding: 12px 13px;
  background: #ffffff;
}

.summary-item span {
  display: block;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 4px;
}

.summary-item strong {
  display: block;
  color: #071126;
  font-size: 12px;
  font-weight: 850;
  word-break: break-word;
}

.passport-button {
  width: 100%;
  min-height: 44px;
  margin-top: 16px;
  border-radius: 11px;
  color: #ffffff;
  background: #071126;
  text-decoration: none;
  font-size: 12px;
  font-weight: 850;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  transition: 0.2s ease;
}

.passport-button:hover {
  color: #ffffff;
  background: #132b4a;
  transform: translateY(-1px);
}

.notes-box {
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  color: #475569;
  border-radius: 14px;
  padding: 14px;
  line-height: 1.7;
  font-size: 12px;
  font-weight: 600;
}

/* ================= COURSES ================= */
.courses-header {
  align-items: center;
}

.bulk-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.bulk-actions button {
  min-height: 40px;
  padding: 0 13px;
  border: 0;
  border-radius: 10px;
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: 0.2s ease;
}

.bulk-actions button:hover {
  transform: translateY(-1px);
}

.approve-all {
  background: #0ab39c;
}

.reject-all {
  background: #f06548;
}

.course-list {
  display: grid;
  gap: 16px;
}

.course-decision-card {
  overflow: hidden;
  border: 1px solid #e4edf4;
  border-radius: 17px;
  background: #ffffff;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.course-decision-card:hover {
  border-color: #cfe8f9;
  box-shadow: 0 10px 24px rgba(13, 143, 233, 0.06);
}

.course-summary {
  padding: 15px;
  display: grid;
  grid-template-columns: 92px minmax(0, 1fr) auto;
  gap: 14px;
  align-items: center;
}

.course-image,
.course-image img,
.course-image-fallback {
  width: 92px;
  height: 76px;
  border-radius: 13px;
}

.course-image {
  overflow: hidden;
  border: 1px solid #e4edf4;
  background: #eaf7ff;
}

.course-image img {
  object-fit: cover;
}

.course-image-fallback {
  color: #ffffff;
  background: linear-gradient(135deg, #37cced 0%, #0d8fe9 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 900;
}

.course-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 7px;
  margin-bottom: 5px;
}

.course-category,
.item-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 27px;
  padding: 5px 8px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 850;
}

.course-category {
  color: #087bc9;
  background: #eaf7ff;
}

.item-status.approved {
  color: #087e6f;
  background: #effcf9;
}

.item-status.rejected {
  color: #b42318;
  background: #fff1ef;
}

.item-status.pending {
  color: #8a5500;
  background: #fff8e9;
}

.course-copy h3 {
  margin: 0 0 5px;
  color: #071126;
  font-size: 16px;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.course-copy p {
  margin: 0;
  color: #64748b;
  font-size: 11.5px;
  font-weight: 600;
}

.separator {
  margin-inline: 4px;
}

.decision-buttons {
  display: flex;
  gap: 7px;
}

.decision-buttons label {
  min-height: 40px;
  padding: 0 12px;
  border: 1px solid rgba(10, 179, 156, 0.3);
  border-radius: 10px;
  color: #087e6f;
  background: #ffffff;
  font-size: 11px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  transition: 0.2s ease;
}

.decision-buttons label:hover:not(.selected) {
  background: #effcf9;
}

.decision-buttons label.reject-choice {
  border-color: rgba(240, 101, 72, 0.3);
  color: #b42318;
}

.decision-buttons label.reject-choice:hover:not(.selected) {
  background: #fff1ef;
}

.decision-buttons label.selected {
  border-color: #0ab39c;
  color: #ffffff;
  background: #0ab39c;
}

.decision-buttons label.reject-choice.selected {
  border-color: #f06548;
  background: #f06548;
}

.decision-buttons label:has(input:disabled) {
  cursor: default;
  opacity: 0.72;
}

.decision-buttons input {
  display: none;
}

.decision-fields {
  padding: 15px;
  border-top: 1px solid #e4edf4;
  background: #f8fafc;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.decision-fields label > span {
  margin-bottom: 6px;
  color: #64748b;
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 5px;
}

.decision-fields input,
.decision-fields textarea,
.general-note textarea {
  width: 100%;
  border: 1px solid #dce5ef;
  border-radius: 10px;
  background: #ffffff;
  color: #071126;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.decision-fields input {
  height: 42px;
  padding: 0 11px;
}

.decision-fields textarea,
.general-note textarea {
  padding: 10px 11px;
  resize: vertical;
}

.decision-fields input:focus,
.decision-fields textarea:focus,
.general-note textarea:focus {
  border-color: #0d8fe9;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.1);
}

.decision-fields input:disabled,
.decision-fields textarea:disabled,
.general-note textarea:disabled {
  color: #64748b;
  background: #f1f5f9;
}

.note-field {
  grid-column: 1 / -1;
}

.enrollment-line {
  padding: 11px 15px;
  border-top: 1px solid rgba(10, 179, 156, 0.2);
  color: #087e6f;
  background: #effcf9;
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 9px;
}

.enrollment-line > i {
  font-size: 18px;
}

.enrollment-line strong,
.enrollment-line span {
  display: block;
}

.enrollment-line strong {
  font-weight: 900;
}

.enrollment-line span {
  margin-top: 1px;
  opacity: 0.85;
}

/* ================= NOTE / FOOTER ================= */
.general-note {
  display: block;
}

.general-note textarea {
  min-height: 105px;
}

.form-error {
  margin-top: 14px;
  padding: 12px 13px;
  border: 1px solid rgba(240, 101, 72, 0.2);
  border-radius: 11px;
  color: #b42318;
  background: #fff1ef;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 12px;
  font-weight: 700;
}

.form-footer {
  padding: 16px 18px;
  border-top: 1px solid #e4edf4;
  background: #fbfdff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.footer-info {
  color: #64748b;
  font-size: 11px;
  font-weight: 650;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-footer button {
  min-height: 46px;
  padding: 0 18px;
  border: 0;
  border-radius: 11px;
  color: #ffffff;
  background: #071126;
  font-size: 12px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  transition: 0.2s ease;
}

.form-footer button:hover:not(:disabled) {
  background: #132b4a;
  transform: translateY(-1px);
}

.form-footer button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ================= RESPONSIVE ================= */
@media (max-width: 1399.98px) {
  .request-overview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .request-layout {
    grid-template-columns: 330px minmax(0, 1fr);
  }

  .course-summary {
    grid-template-columns: 82px minmax(0, 1fr);
  }

  .course-image,
  .course-image img,
  .course-image-fallback {
    width: 82px;
    height: 72px;
  }

  .decision-buttons {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}

@media (max-width: 1199.98px) {
  .request-layout {
    grid-template-columns: 1fr;
  }

  .request-left-column {
    position: static;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .request-left-column > .show-card {
    margin-top: 0 !important;
  }

  .learner-card {
    grid-row: span 2;
  }
}

@media (max-width: 991.98px) {
  .request-hero {
    flex-direction: column;
    align-items: stretch;
    padding: 24px;
  }

  .request-hero::after {
    width: 240px;
    right: -110px;
  }

  .request-hero-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .request-left-column {
    grid-template-columns: 1fr;
  }

  .learner-card {
    grid-row: auto;
  }

  .decision-fields {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .decision-fields > label:nth-child(3) {
    grid-column: 1 / -1;
  }
}

@media (max-width: 767.98px) {
  .request-hero {
    padding: 20px;
    border-radius: 20px;
  }

  .request-hero-main {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .request-hero-icon {
    width: 62px;
    height: 62px;
    min-width: 62px;
    border-radius: 16px;
    font-size: 28px;
  }

  .hero-badges {
    gap: 6px;
  }

  .status-badge,
  .hero-mini-badge {
    min-height: 31px;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 11px;
  }

  .request-hero h1 {
    font-size: clamp(23px, 7vw, 31px);
    word-break: break-word;
  }

  .request-overview-grid {
    grid-template-columns: 1fr;
  }

  .courses-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .bulk-actions {
    width: 100%;
  }

  .bulk-actions button {
    flex: 1;
  }

  .course-summary {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .course-image,
  .course-image img,
  .course-image-fallback {
    width: 100%;
    height: 180px;
  }

  .course-copy {
    width: 100%;
  }

  .course-copy h3 {
    white-space: normal;
  }

  .decision-buttons {
    width: 100%;
  }

  .decision-buttons label {
    flex: 1;
  }

  .decision-fields {
    grid-template-columns: 1fr;
  }

  .decision-fields > label:nth-child(3),
  .note-field {
    grid-column: auto;
  }

  .form-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .form-footer button {
    width: 100%;
  }
}

@media (max-width: 575.98px) {
  .request-hero {
    padding: 18px;
    border-radius: 18px;
  }

  .request-hero::before {
    display: none;
  }

  .request-hero::after {
    right: -160px;
    width: 250px;
  }

  .request-hero-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .request-hero-actions .btn {
    width: 100%;
    min-height: 44px;
    padding-inline: 12px;
  }

  .show-card {
    border-radius: 16px;
  }

  .show-card-header,
  .show-card-body {
    padding: 15px;
  }

  .show-card-title {
    align-items: flex-start;
  }

  .show-card-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    font-size: 19px;
  }

  .bulk-actions {
    flex-direction: column;
  }

  .decision-buttons {
    flex-direction: column;
  }

  .course-list {
    gap: 12px;
  }

  .course-summary,
  .decision-fields {
    padding: 12px;
  }
}

@media (max-width: 420px) {
  .request-hero-actions {
    grid-template-columns: 1fr;
  }
}
</style>
