<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import defaultImage from '@assets/images/default-user.png'

const props = defineProps({
  learner: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  enrollments: {
    type: Array,
    default: () => [],
  },
  authUser: {
    type: Object,
    default: () => ({}),
  },
})

const selectedCategory = ref('all')
const detailsModal = ref(false)
const selectedEnrollment = ref(null)

const learnerDisplayName = computed(() => {
  const parts = [
    props.learner?.first_name,
    props.learner?.middle_name,
    props.learner?.last_name,
  ].filter(Boolean)

  return parts.join(' ') || props.learner?.full_name || props.learner?.name || 'Learner details'
})

const filteredEnrollments = computed(() => {
  if (selectedCategory.value === 'all') {
    return props.enrollments
  }

  return props.enrollments.filter(enrollment => {
    return String(enrollment.course?.category?.id ?? '') === String(selectedCategory.value)
  })
})

const totalCourses = computed(() => props.enrollments.length)

const completedCourses = computed(() => {
  return props.enrollments.filter(item => item.status === 'completed').length
})

const inProgressCourses = computed(() => {
  return props.enrollments.filter(item => item.status === 'in_progress').length
})

const openDetails = enrollment => {
  selectedEnrollment.value = enrollment
  detailsModal.value = true
}

const getUserPhoto = user => {
  if (!user) return defaultImage

  if (user.profile_photo_path) {
    return `/storage/${user.profile_photo_path}`
  }

  if (
    user.profile_photo_url &&
    !user.profile_photo_url.includes('default-user.png') &&
    !user.profile_photo_url.includes('profile-photos/default')
  ) {
    return user.profile_photo_url
  }

  return defaultImage
}

const getCourseImage = course => {
  if (!course) return null

  if (course.image_url) return course.image_url

  if (course.image) {
    return `/storage/${course.image}`
  }

  return null
}

const getPassportUrl = user => {
  if (user?.passport_file_url) return user.passport_file_url
  if (user?.passport_file) return `/storage/${user.passport_file}`

  return null
}

const handleImageError = event => {
  event.target.src = defaultImage
}

const formatDate = date => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatPercent = value => {
  const number = Number(value || 0)
  return `${number.toFixed(0)}%`
}

const formatPrice = course => {
  if (!course?.price) return 'Free'

  return `${course.price} ${course.currency || ''}`
}

const getLearnerStatusClass = status => {
  return status === 'active'
    ? 'badge bg-success-subtle text-success'
    : 'badge bg-danger-subtle text-danger'
}

const getEnrollmentStatusClass = status => {
  switch (status) {
    case 'completed':
      return 'badge bg-success-subtle text-success'
    case 'in_progress':
      return 'badge bg-info-subtle text-info'
    case 'not_started':
      return 'badge bg-warning-subtle text-warning'
    case 'expired':
      return 'badge bg-danger-subtle text-danger'
    default:
      return 'badge bg-secondary-subtle text-secondary'
  }
}

const getChapterStatusClass = status => {
  switch (status) {
    case 'completed':
      return 'badge bg-success-subtle text-success'
    case 'in_progress':
      return 'badge bg-info-subtle text-info'
    case 'not_started':
      return 'badge bg-warning-subtle text-warning'
    default:
      return 'badge bg-secondary-subtle text-secondary'
  }
}

const getSlideStatusClass = slide => {
  if (slide.is_completed) {
    return 'badge bg-success-subtle text-success'
  }

  if (slide.is_viewed) {
    return 'badge bg-info-subtle text-info'
  }

  return 'badge bg-warning-subtle text-warning'
}

const getSlideStatusText = slide => {
  if (slide.is_completed) return 'Completed'
  if (slide.is_viewed) return 'Viewed'
  return 'Not started'
}
</script>

<template>
  <Layout>
    <Head :title="learnerDisplayName" />

    <PageHeader
      :title="learnerDisplayName"
      pageTitle="Learners"
    />

    <BRow class="g-4">
      <!-- Learner side -->
      <BCol xl="3" lg="4">
        <BCard class="learner-profile-card border-0 shadow-sm">
          <div class="learner-profile-header">
            <img
              :src="getUserPhoto(learner)"
              :alt="learnerDisplayName"
              class="learner-avatar"
              @error="handleImageError"
            />

            <h5 class="mb-1 fw-semibold mt-3">
              {{ learnerDisplayName }}
            </h5>

            <p class="text-muted mb-2">
              {{ learner.email || '-' }}
            </p>

            <span :class="getLearnerStatusClass(learner.status)">
              {{ learner.status || '-' }}
            </span>
          </div>

          <div class="learner-profile-body">
            <div class="info-row">
              <span>
                <i class="ri-user-line me-1"></i>
                First name
              </span>
              <strong>{{ learner.first_name || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-user-line me-1"></i>
                Middle name
              </span>
              <strong>{{ learner.middle_name || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-user-line me-1"></i>
                Last name
              </span>
              <strong>{{ learner.last_name || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-building-line me-1"></i>
                Company
              </span>
              <strong>{{ learner.company?.name || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-calendar-event-line me-1"></i>
                Date of birth
              </span>
              <strong>{{ formatDate(learner.date_of_birth) }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-map-pin-user-line me-1"></i>
                Place of birth
              </span>
              <strong>{{ learner.place_of_birth || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-earth-line me-1"></i>
                Country
              </span>
              <strong>{{ learner.country || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-passport-line me-1"></i>
                Passport number
              </span>
              <strong>{{ learner.passport_number || '-' }}</strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-file-text-line me-1"></i>
                Passport file
              </span>
              <strong>
                <a
                  v-if="getPassportUrl(learner)"
                  :href="getPassportUrl(learner)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="passport-document-link"
                >
                  View document
                  <i class="ri-external-link-line ms-1"></i>
                </a>
                <template v-else>-</template>
              </strong>
            </div>

            <div class="info-row">
              <span>
                <i class="ri-book-open-line me-1"></i>
                Courses
              </span>
              <strong>{{ totalCourses }}</strong>
            </div>
          </div>

          <div class="p-3 pt-0">
            <Link
              :href="route('learners.index')"
              class="btn btn-light w-100 learner-back-btn"
            >
              <i class="ri-arrow-left-line me-1"></i>
              Back
            </Link>
          </div>
        </BCard>

        <BCard class="mt-4 border-0 shadow-sm learner-stats-card">
          <BCardBody>
            <h6 class="mb-3 fw-semibold">
              <i class="ri-bar-chart-box-line me-1"></i>
              Summary
            </h6>

            <div class="mini-stat">
              <span>Total courses</span>
              <strong>{{ totalCourses }}</strong>
            </div>

            <div class="mini-stat">
              <span>In progress</span>
              <strong>{{ inProgressCourses }}</strong>
            </div>

            <div class="mini-stat">
              <span>Completed</span>
              <strong>{{ completedCourses }}</strong>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <!-- Main content -->
      <BCol xl="9" lg="8">
        <BCard class="learner-main-card border-0 shadow-sm">
          <BCardHeader class="learner-main-header border-0">
            <BRow class="g-3 align-items-center">
              <BCol xl="5" lg="12">
                <div class="main-header-title">
                  <div class="main-header-icon">
                    <i class="ri-folder-open-line"></i>
                  </div>

                  <div>
                    <h5 class="mb-1 fw-semibold">Learner courses</h5>
                    <p class="text-muted mb-0">
                      Select a category to display affected courses.
                    </p>
                  </div>
                </div>
              </BCol>

              <BCol xl="7" lg="12">
                <div class="category-tabs">
                  <button
                    type="button"
                    class="category-tab"
                    :class="{ active: selectedCategory === 'all' }"
                    @click="selectedCategory = 'all'"
                  >
                    <i class="ri-apps-line me-1"></i>
                    All
                    <span>{{ totalCourses }}</span>
                  </button>

                  <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="category-tab"
                    :class="{ active: String(selectedCategory) === String(category.id) }"
                    @click="selectedCategory = category.id"
                  >
                    <i class="ri-folder-line me-1"></i>
                    {{ category.name }}
                    <span>{{ category.courses_count }}</span>
                  </button>
                </div>
              </BCol>
            </BRow>
          </BCardHeader>

          <BCardBody>
            <div v-if="filteredEnrollments.length" class="courses-grid">
              <div
                v-for="enrollment in filteredEnrollments"
                :key="enrollment.id"
                class="course-card"
              >
                <div class="course-cover">
                  <img
                    v-if="getCourseImage(enrollment.course)"
                    :src="getCourseImage(enrollment.course)"
                    :alt="enrollment.course?.title || 'Course image'"
                    class="course-img"
                  />

                  <div v-else class="course-img-fallback">
                    <i class="ri-book-open-line"></i>
                  </div>

                  <span :class="getEnrollmentStatusClass(enrollment.status)" class="course-status">
                    {{ enrollment.status || '-' }}
                  </span>
                </div>

                <div class="course-body">
                  <div class="course-category">
                    <i class="ri-folder-line me-1"></i>
                    {{ enrollment.course?.category?.name || '-' }}
                  </div>

                  <h5 class="course-title">
                    {{ enrollment.course?.title || '-' }}
                  </h5>

                  <div class="course-meta">
                    <span>
                      <i class="ri-global-line me-1"></i>
                      {{ enrollment.course?.language || '-' }}
                    </span>

                    <span>
                      <i class="ri-price-tag-3-line me-1"></i>
                      {{ formatPrice(enrollment.course) }}
                    </span>
                  </div>

                  <div class="progress course-progress mt-3">
                    <div
                      class="progress-bar"
                      role="progressbar"
                      :style="{ width: formatPercent(enrollment.progress_percent) }"
                    >
                      {{ formatPercent(enrollment.progress_percent) }}
                    </div>
                  </div>

                  <div class="course-simple-stats">
                    <div>
                      <span>Chapters</span>
                      <strong>
                        {{ enrollment.stats.completed_chapters }}
                        /
                        {{ enrollment.stats.total_chapters }}
                      </strong>
                    </div>

                    <div>
                      <span>Slides</span>
                      <strong>
                        {{ enrollment.stats.completed_slides }}
                        /
                        {{ enrollment.stats.total_slides }}
                      </strong>
                    </div>
                  </div>

                  <BButton
                    type="button"
                    variant="primary"
                    class="w-100 details-btn"
                    @click="openDetails(enrollment)"
                  >
                    <i class="ri-eye-line me-1"></i>
                    Details
                  </BButton>
                </div>
              </div>
            </div>

            <div v-else class="empty-state">
              <div class="empty-icon">
                <i class="ri-book-open-line"></i>
              </div>

              <h5 class="mt-3 mb-1">No courses found</h5>
              <p class="text-muted mb-0">
                No affected courses for this selected category.
              </p>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Course details modal -->
    <BModal
      v-model="detailsModal"
      hide-footer
      size="xl"
      centered
      class="course-details-modal"
      :title="selectedEnrollment?.course?.title || 'Course details'"
    >
      <div v-if="selectedEnrollment" class="course-details-content">
        <BRow class="g-4">
          <BCol lg="4">
            <div class="modal-course-card">
              <div class="modal-course-image">
                <img
                  v-if="getCourseImage(selectedEnrollment.course)"
                  :src="getCourseImage(selectedEnrollment.course)"
                  :alt="selectedEnrollment.course?.title || 'Course image'"
                />

                <div v-else class="course-img-fallback">
                  <i class="ri-book-open-line"></i>
                </div>
              </div>

              <div class="modal-course-body">
                <span :class="getEnrollmentStatusClass(selectedEnrollment.status)">
                  {{ selectedEnrollment.status || '-' }}
                </span>

                <h5 class="mt-3 mb-2 fw-semibold">
                  {{ selectedEnrollment.course?.title || '-' }}
                </h5>

                <p class="text-muted">
                  {{ selectedEnrollment.course?.description || 'No description available.' }}
                </p>

                <div class="progress course-progress mb-3">
                  <div
                    class="progress-bar"
                    role="progressbar"
                    :style="{ width: formatPercent(selectedEnrollment.progress_percent) }"
                  >
                    {{ formatPercent(selectedEnrollment.progress_percent) }}
                  </div>
                </div>

                <div class="modal-info-list">
                  <div>
                    <span>Category</span>
                    <strong>{{ selectedEnrollment.course?.category?.name || '-' }}</strong>
                  </div>

                  <div>
                    <span>Language</span>
                    <strong>{{ selectedEnrollment.course?.language || '-' }}</strong>
                  </div>

                  <div>
                    <span>Type</span>
                    <strong>{{ selectedEnrollment.course?.type || '-' }}</strong>
                  </div>

                  <div>
                    <span>Training type</span>
                    <strong>{{ selectedEnrollment.course?.training_type || '-' }}</strong>
                  </div>

                  <div>
                    <span>Course Type</span>
                    <strong>{{ selectedEnrollment.course?.skill_level || '-' }}</strong>
                  </div>

                  <div>
                    <span>Price</span>
                    <strong>{{ formatPrice(selectedEnrollment.course) }}</strong>
                  </div>

                  <div>
                    <span>Started at</span>
                    <strong>{{ formatDate(selectedEnrollment.started_at) }}</strong>
                  </div>

                  <div>
                    <span>Completed at</span>
                    <strong>{{ formatDate(selectedEnrollment.completed_at) }}</strong>
                  </div>

                  <div>
                    <span>Current chapter</span>
                    <strong>{{ selectedEnrollment.current_chapter?.title || '-' }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </BCol>

          <BCol lg="8">
            <div class="modal-section">
              <h5 class="mb-3 fw-semibold">
                <i class="ri-list-check-2 me-1"></i>
                Chapters progress
              </h5>

              <div
                v-if="selectedEnrollment.chapters?.length"
                class="chapters-wrapper"
              >
                <div
                  v-for="chapter in selectedEnrollment.chapters"
                  :key="chapter.id"
                  class="chapter-card"
                >
                  <div class="chapter-header">
                    <div>
                      <h6 class="mb-1 fw-semibold">
                        {{ chapter.title }}
                      </h6>

                      <small class="text-muted">
                        {{ chapter.slides_count }} slide(s)
                      </small>
                    </div>

                    <span :class="getChapterStatusClass(chapter.status)">
                      {{ chapter.status }}
                    </span>
                  </div>

                  <div class="progress chapter-progress mt-3">
                    <div
                      class="progress-bar"
                      role="progressbar"
                      :style="{ width: formatPercent(chapter.progress_percent) }"
                    ></div>
                  </div>

                  <div
                    v-if="chapter.slides?.length"
                    class="slides-list mt-3"
                  >
                    <div
                      v-for="slide in chapter.slides"
                      :key="slide.id"
                      class="slide-row"
                    >
                      <div class="slide-title">
                        <i
                          :class="slide.is_completed
                            ? 'ri-checkbox-circle-line text-success'
                            : slide.is_viewed
                              ? 'ri-eye-line text-info'
                              : 'ri-time-line text-warning'"
                        ></i>

                        <div>
                          <strong>{{ slide.title }}</strong>
                          <small class="text-muted d-block">
                            {{ slide.lesson_type || '-' }}
                            <template v-if="slide.is_mandatory">
                              · Mandatory
                            </template>
                            <template v-if="slide.is_preview">
                              · Preview
                            </template>
                          </small>
                        </div>
                      </div>

                      <span :class="getSlideStatusClass(slide)">
                        {{ getSlideStatusText(slide) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="empty-state small-empty">
                <div class="empty-icon">
                  <i class="ri-list-check-2"></i>
                </div>

                <h6 class="mt-3 mb-1">No chapters found</h6>
              </div>
            </div>
          </BCol>
        </BRow>
      </div>
    </BModal>
  </Layout>
</template>

<style scoped>
.learner-profile-card,
.learner-stats-card,
.learner-main-card {
  border-radius: 18px;
  overflow: hidden;
}

.learner-profile-header {
  padding: 26px 20px;
  text-align: center;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #eef2f7;
}

.learner-avatar {
  width: 112px;
  height: 112px;
  object-fit: cover;
  border-radius: 50%;
  border: 5px solid #ffffff;
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.18);
  background-color: #f1f5f9;
}

.learner-profile-body {
  padding: 18px;
}

.info-row,
.mini-stat {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child,
.mini-stat:last-child {
  border-bottom: none;
}

.info-row span,
.mini-stat span {
  color: #64748b;
  font-weight: 600;
}

.info-row strong,
.mini-stat strong {
  color: #334155;
  text-align: right;
}

.passport-document-link {
  color: #405189;
  font-weight: 700;
  text-decoration: none;
}

.passport-document-link:hover {
  color: #687cfe;
}

.learner-back-btn {
  border-radius: 12px;
}

.learner-main-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.main-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
}

.main-header-icon {
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

.category-tabs {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.category-tab {
  border: 1px solid #e5e7eb;
  background-color: #ffffff;
  color: #334155;
  border-radius: 999px;
  padding: 9px 14px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.category-tab span {
  margin-left: 6px;
  background-color: #f1f5f9;
  color: #64748b;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 12px;
}

.category-tab:hover,
.category-tab.active {
  background-color: #687cfe;
  border-color: #687cfe;
  color: #ffffff;
}

.category-tab.active span,
.category-tab:hover span {
  background-color: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.course-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  overflow: hidden;
  background-color: #ffffff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
  transition: all 0.2s ease;
}

.course-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1);
}

.course-cover {
  position: relative;
  height: 175px;
  background-color: #f1f5f9;
}

.course-img,
.modal-course-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-img-fallback {
  width: 100%;
  height: 100%;
  color: #687cfe;
  background: linear-gradient(135deg, rgba(104, 124, 254, 0.12), rgba(64, 81, 137, 0.12));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
}

.course-status {
  position: absolute;
  top: 14px;
  right: 14px;
}

.course-body {
  padding: 18px;
}

.course-category {
  color: #687cfe;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 8px;
}

.course-title {
  color: #1e293b;
  font-weight: 700;
  margin-bottom: 12px;
  min-height: 48px;
}

.course-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  color: #64748b;
  font-size: 13px;
}

.course-progress {
  height: 22px;
  border-radius: 999px;
  background-color: #eef2ff;
}

.course-progress .progress-bar {
  background-color: #687cfe;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.course-simple-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 16px;
  margin-bottom: 16px;
}

.course-simple-stats div {
  padding: 12px;
  border-radius: 14px;
  background-color: #f8fafc;
  border: 1px solid #edf2f7;
}

.course-simple-stats span {
  display: block;
  color: #64748b;
  font-size: 12px;
  margin-bottom: 4px;
}

.course-simple-stats strong {
  color: #1e293b;
}

.details-btn {
  height: 42px;
  border-radius: 12px;
}

.badge {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
}

.small-empty {
  padding: 30px 20px;
}

.empty-icon {
  width: 76px;
  height: 76px;
  margin: 0 auto;
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(104, 124, 254, 0.12), rgba(64, 81, 137, 0.12));
  color: #687cfe;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 34px;
}

/* Modal */
.course-details-modal :deep(.modal-content) {
  border: none;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
}

.modal-course-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  overflow: hidden;
  background-color: #ffffff;
}

.modal-course-image {
  height: 210px;
  background-color: #f1f5f9;
}

.modal-course-body {
  padding: 18px;
}

.modal-info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.modal-info-list div {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-info-list span {
  color: #64748b;
  font-weight: 600;
}

.modal-info-list strong {
  color: #334155;
  text-align: right;
}

.modal-section {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  padding: 18px;
  background-color: #ffffff;
}

.chapters-wrapper {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.chapter-card {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 16px;
  background-color: #f8fafc;
}

.chapter-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.chapter-progress {
  height: 9px;
  border-radius: 999px;
  background-color: #eef2ff;
}

.chapter-progress .progress-bar {
  background-color: #687cfe;
  border-radius: 999px;
}

.slides-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.slide-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px;
  border-radius: 14px;
  background-color: #ffffff;
  border: 1px solid #edf2f7;
}

.slide-title {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.slide-title i {
  font-size: 18px;
  margin-top: 2px;
}

@media (max-width: 1199.98px) {
  .category-tabs {
    justify-content: flex-start;
  }
}

@media (max-width: 991.98px) {
  .courses-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .learner-main-header {
    padding: 18px;
  }

  .main-header-title {
    align-items: flex-start;
  }

  .category-tabs {
    flex-direction: column;
  }

  .category-tab {
    width: 100%;
    text-align: left;
  }

  .course-cover {
    height: 150px;
  }

  .course-simple-stats {
    grid-template-columns: 1fr;
  }

  .modal-info-list div,
  .slide-row,
  .chapter-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .modal-info-list strong {
    text-align: left;
  }
}
</style>