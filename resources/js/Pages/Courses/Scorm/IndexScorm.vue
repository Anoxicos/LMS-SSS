<script setup>
import { formatCourseLabel } from '@/composables/course-labels'
import { ref, computed, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

import { usePermissions } from '@/composables/permissions'

/* ================= PERMISSIONS ================= */
const hasPermission = (permission) => {
  const { hasPermission } = usePermissions()
  return hasPermission(permission)
}

/* ================= PROPS ================= */
const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})

/* ================= STATE ================= */
const deleteModal = ref(false)
const selectedCourse = ref(null)

const direction = ref('asc')
const sortColumn = ref('title')

const dataLocal = ref([...props.courses])
const searchQuery = ref('')
const currentPageData = ref(1)
const perPageData = 10

/* ================= HELPERS ================= */
const getCourseImage = (course) => {
  if (course.image_url) {
    return course.image_url
  }

  if (course.image) {
    return `/storage/${course.image}`
  }

  return null
}

const getCourseInitials = (title) => {
  if (!title) return 'SC'

  return String(title)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getStatusClass = (isPublished) => {
  return isPublished
    ? 'badge bg-success-subtle text-success'
    : 'badge bg-warning-subtle text-warning'
}

const getTypeClass = (type) => {
  if (type === 'free') {
    return 'badge bg-success-subtle text-success'
  }

  if (type === 'paid') {
    return 'badge bg-primary-subtle text-primary'
  }

  if (type === 'private') {
    return 'badge bg-danger-subtle text-danger'
  }

  return 'badge bg-secondary-subtle text-secondary'
}

const getScormClass = () => {
  return 'badge bg-warning-subtle text-warning'
}

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const formatPrice = (course) => {
  const price = Number(course.price || 0)

  if (course.type !== 'paid' || price === 0) {
    return t('t-free')
  }

  return `${price.toFixed(2)} ${course.currency || 'MAD'}`
}

/* ================= SEARCH ================= */
const filtereData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  if (!search) {
    return dataLocal.value
  }

  return dataLocal.value.filter((course) => {
    const publishedText = course.is_published ? 'published' : 'draft'
    const tagsText = (course.tags || []).map((tag) => tag.name).join(' ')

    return (
      String(course.title ?? '').toLowerCase().includes(search) ||
      String(course.description ?? '').toLowerCase().includes(search) ||
      String(course.category?.name ?? '').toLowerCase().includes(search) ||
      String(course.type ?? '').toLowerCase().includes(search) ||
      String(course.training_type ?? '').toLowerCase().includes(search) ||
      String(course.skill_level ?? '').toLowerCase().includes(search) ||
      String(course.language ?? '').toLowerCase().includes(search) ||
      String(course.scorm_title ?? '').toLowerCase().includes(search) ||
      String(course.scorm_version ?? '').toLowerCase().includes(search) ||
      String(tagsText ?? '').toLowerCase().includes(search) ||
      publishedText.includes(search)
    )
  })
})

/* ================= PAGINATION ================= */
const paginatedData = computed(() => {
  const start = (currentPageData.value - 1) * perPageData
  return filtereData.value.slice(start, start + perPageData)
})

const totalPagesData = computed(() => {
  return Math.ceil(filtereData.value.length / perPageData) || 1
})

const visiblePagesData = computed(() => {
  const total = totalPagesData.value
  const current = currentPageData.value

  if (total <= 5) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const pages = [1]

  let start = Math.max(2, current - 1)
  let end = Math.min(total - 1, current + 1)

  if (current <= 3) {
    start = 2
    end = 4
  }

  if (current >= total - 2) {
    start = total - 3
    end = total - 1
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  pages.push(total)

  return [...new Set(pages)]
})

watch(searchQuery, () => {
  currentPageData.value = 1
})

watch(
  () => props.courses,
  (newVal) => {
    dataLocal.value = [...newVal]
    currentPageData.value = 1
  },
  { immediate: true }
)

/* ================= DELETE ================= */
const deleteData = (course) => {
  deleteModal.value = true
  selectedCourse.value = course
}

const handleDeleteData = () => {
  if (!selectedCourse.value) return

  const formDelete = useForm({})

  formDelete.delete(route('courses.scorm.delete', selectedCourse.value.hashid), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-deleted'),
        text: t('ui-scorm-course-deleted-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('t-try-again'),
        timer: 3000,
        showConfirmButton: false,
      })
    },
    onFinish: () => {
      deleteModal.value = false
      selectedCourse.value = null
    },
  })
}

/* ================= SORT ================= */
const onSort = (column) => {
  if (sortColumn.value === column) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    direction.value = 'asc'
  }

  const sortedArray = [...dataLocal.value]

  sortedArray.sort((a, b) => {
    let valueA = a[column]
    let valueB = b[column]

    if (column === 'category') {
      valueA = a.category?.name || ''
      valueB = b.category?.name || ''
    }

    if (column === 'scorm') {
      valueA = a.scorm_title || a.scorm_version || ''
      valueB = b.scorm_title || b.scorm_version || ''
    }

    if (column === 'price') {
      valueA = Number(valueA || 0)
      valueB = Number(valueB || 0)

      const result = valueA < valueB ? -1 : valueA > valueB ? 1 : 0
      return direction.value === 'asc' ? result : -result
    }

    if (column === 'is_published') {
      valueA = valueA ? 1 : 0
      valueB = valueB ? 1 : 0

      const result = valueA < valueB ? -1 : valueA > valueB ? 1 : 0
      return direction.value === 'asc' ? result : -result
    }

    const finalA = String(valueA ?? '').toLowerCase()
    const finalB = String(valueB ?? '').toLowerCase()
    const result = finalA < finalB ? -1 : finalA > finalB ? 1 : 0

    return direction.value === 'asc' ? result : -result
  })

  dataLocal.value = sortedArray
}
</script>

<template>
  <Layout>
    <Head :title="$t('ui-scorm-courses')" />
    <PageHeader :title="$t('ui-scorm-courses')" :page-title="$t('ui-lms')" />

    <div>
      <BCard class="course-card border-0 shadow-sm">
        <!-- Responsive Header -->
        <BCardHeader class="course-card-header border-0">
          <BRow class="g-3 align-items-center course-header-row">
            <BCol xl="5" lg="12" md="12" cols="12">
              <div class="course-header-title">
                <div class="course-header-icon scorm-header-icon">
                  <i class="ri-code-box-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-semibold">{{ $t('ui-scorm-courses') }}</h5>
                  <p class="text-muted mb-0">
                    {{ $t('ui-manage-scorm-packages-launch-files-versions-and-public') }}
                  </p>
                </div>
              </div>
            </BCol>

            <BCol xl="7" lg="12" md="12" cols="12">
              <div class="course-header-actions">
                <div class="search-box course-search-box">
                  <input
                    v-model="searchQuery"
                    type="text"
                    class="form-control"
                    :placeholder="$t('ui-search-course')"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>

                <Link
                  v-if="hasPermission('create course')"
                  :href="route('courses.scorm.create')"
                  class="btn btn-primary add-btn course-add-btn"
                >
                  <i class="ri-add-line align-bottom me-1"></i>
                  {{ $t('ui-add') }} 
                </Link>
              </div>
            </BCol>
          </BRow>
        </BCardHeader>

        <BCardBody>
          <!-- Desktop Table -->
          <div class="course-table-wrapper d-none d-lg-block">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 course-table">
                <thead class="course-head">
                  <tr>
                    <th class="sort" @click="onSort('title')">
                      {{ $t('ui-course') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="sort" @click="onSort('category')">
                      {{ $t('ui-category') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                   

                    <th class="sort" @click="onSort('type')">
                      {{ $t('ui-type') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="sort" @click="onSort('price')">
                      {{ $t('ui-price') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="sort text-center" @click="onSort('is_published')">
                      {{ $t('ui-status') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="sort" @click="onSort('created_at')">
                      {{ $t('ui-created') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="text-center">
                      {{ $t('ui-actions') }}
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="course in paginatedData"
                    :key="course.id || course.hashid"
                  >
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <div class="course-image-box">
                          <img
                            v-if="getCourseImage(course)"
                            :src="getCourseImage(course)"
                            :alt="$t('t-course-image')"
                            class="course-image"
                          />

                          <div
                            v-else
                            class="course-image-fallback"
                          >
                            {{ getCourseInitials(course.title) }}
                          </div>
                        </div>

                        <div class="min-width-0">
                          <h6 class="mb-1 fw-semibold course-name-text">
                            {{ course.title || '-' }}
                          </h6>

                          <small class="text-muted">
                            {{ formatCourseLabel(course.training_type) }}
                            <span v-if="course.skill_level">
                              · {{ formatCourseLabel(course.skill_level) }}
                            </span>
                          </small>
                        </div>
                      </div>
                    </td>

                    <td>
                      <span class="category-badge">
                        <i class="ri-price-tag-3-line me-1"></i>
                        {{ course.category?.name || '-' }}
                      </span>
                    </td>

                    <td>
                      <span :class="getTypeClass(course.type)">
                        <i class="ri-graduation-cap-line me-1"></i>
                        {{ course.type === 'paid' ? $t('ui-paid') : course.type ? $t('t-free') : '-' }}
                      </span>
                    </td>

                    <td>
                      <span class="price-badge">
                        {{ formatPrice(course) }}
                      </span>
                    </td>

                    <td class="text-center">
                      <span :class="getStatusClass(course.is_published)">
                        <i
                          :class="course.is_published ? 'ri-checkbox-circle-line me-1' : 'ri-draft-line me-1'"
                        ></i>
                        {{ course.is_published ? $t('ui-published') : $t('ui-draft') }}
                      </span>
                    </td>

                    <td>
                      <span class="created-date">
                        {{ formatDate(course.created_at) }}
                      </span>
                    </td>

                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <Link
                          :href="route('courses.scorm.show', course.hashid)"
                          class="btn btn-sm btn-soft-info action-btn"
                          :title="$t('ui-view')"
                        >
                          <i class="ri-eye-line"></i>
                        </Link>

                        <Link
                          v-if="hasPermission('update course')"
                          :href="route('courses.scorm.edit', course.hashid)"
                          class="btn btn-sm btn-soft-primary action-btn"
                          :title="$t('ui-edit')"
                        >
                          <i class="ri-pencil-fill"></i>
                        </Link>

                        <BButton
                          v-if="hasPermission('delete course')"
                          variant="soft-danger"
                          size="sm"
                          class="action-btn"
                          :title="$t('ui-delete')"
                          @click="deleteData(course)"
                        >
                          <i class="ri-delete-bin-fill"></i>
                        </BButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Cards -->
          <div class="course-mobile-list d-lg-none">
            <div
              v-for="course in paginatedData"
              :key="course.id || course.hashid"
              class="course-mobile-card"
            >
              <div class="course-mobile-header">
                <div class="d-flex align-items-center gap-3">
                  <div class="course-image-box mobile-image">
                    <img
                      v-if="getCourseImage(course)"
                      :src="getCourseImage(course)"
                      :alt="$t('t-course-image')"
                      class="course-image"
                    />

                    <div
                      v-else
                      class="course-image-fallback"
                    >
                      {{ getCourseInitials(course.title) }}
                    </div>
                  </div>

                  <div class="min-width-0">
                    <h6 class="mb-1 fw-semibold course-mobile-title">
                      {{ course.title || '-' }}
                    </h6>

                    <div class="d-flex flex-wrap gap-2">
                      <span :class="getStatusClass(course.is_published)">
                        {{ course.is_published ? $t('ui-published') : $t('ui-draft') }}
                      </span>

                      <span :class="getScormClass()">
                        {{ $t('ui-scorm') }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <Link
                    :href="route('courses.scorm.show', course.hashid)"
                    class="btn btn-sm btn-soft-info action-btn"
                    :title="$t('ui-view')"
                  >
                    <i class="ri-eye-line"></i>
                  </Link>

                  <Link
                    v-if="hasPermission('update course')"
                    :href="route('courses.scorm.edit', course.hashid)"
                    class="btn btn-sm btn-soft-primary action-btn"
                    :title="$t('ui-edit')"
                  >
                    <i class="ri-pencil-fill"></i>
                  </Link>

                  <BButton
                    v-if="hasPermission('delete course')"
                    variant="soft-danger"
                    size="sm"
                    class="action-btn"
                    :title="$t('ui-delete')"
                    @click="deleteData(course)"
                  >
                    <i class="ri-delete-bin-fill"></i>
                  </BButton>
                </div>
              </div>

              <div class="course-mobile-body">
                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-price-tag-3-line me-1"></i>
                    {{ $t('ui-category') }}
                  </span>

                  <span class="category-badge">
                    {{ course.category?.name || '-' }}
                  </span>
                </div>

                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-code-box-line me-1"></i>
                    {{ $t('ui-version') }}
                  </span>

                  <span :class="getScormClass()">
                    {{ course.scorm_version || 'SCORM' }}
                  </span>
                </div>

                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-file-zip-line me-1"></i>
                    {{ $t('ui-package') }}
                  </span>

                  <span class="course-mobile-value">
                    {{ course.scorm_title || '-' }}
                  </span>
                </div>

                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-graduation-cap-line me-1"></i>
                    {{ $t('ui-type') }}
                  </span>

                  <span :class="getTypeClass(course.type)">
                    {{ course.type === 'paid' ? $t('ui-paid') : course.type ? $t('t-free') : '-' }}
                  </span>
                </div>

                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-money-dollar-circle-line me-1"></i>
                    {{ $t('ui-price') }}
                  </span>

                  <span class="price-badge">
                    {{ formatPrice(course) }}
                  </span>
                </div>

                <div class="course-mobile-item">
                  <span class="course-mobile-label">
                    <i class="ri-calendar-line me-1"></i>
                    {{ $t('ui-created') }}
                  </span>

                  <span class="course-mobile-value">
                    {{ formatDate(course.created_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- No results -->
          <div
            v-if="paginatedData.length === 0"
            class="text-center py-5"
          >
            <div class="empty-course-icon mx-auto">
              <i class="ri-code-box-line"></i>
            </div>

            <h5 class="mt-3 mb-1">{{ $t('ui-no-scorm-courses-found') }}</h5>
            <p class="text-muted mb-0">
              {{ $t('ui-try-changing-your-search-keywords-or-create-your-first-scorm-course') }}
            </p>

            <Link
              v-if="hasPermission('create course')"
              :href="route('courses.scorm.create')"
              class="btn btn-primary mt-3"
            >
              <i class="ri-add-line me-1"></i>
              {{ $t('ui-add-scorm-course') }}
            </Link>
          </div>

          <!-- Pagination -->
          <div
            v-if="filtereData.length > 0"
            class="course-pagination"
          >
            <div class="text-muted course-pagination-text">
              {{ $t('ui-showing') }}
              <span class="fw-semibold">{{ paginatedData.length }}</span>
              {{ $t('ui-of') }}
              <span class="fw-semibold">{{ filtereData.length }}</span>
              {{ $t('ui-result-s') }}
            </div>

            <ul class="pagination listjs-pagination mb-0">
              <li
                class="page-item"
                :class="{ disabled: currentPageData === 1 }"
              >
                <BLink
                  href="#"
                  class="page-link"
                  @click.prevent="currentPageData > 1 && currentPageData--"
                >
                  <i class="ri-arrow-left-s-line"></i>
                </BLink>
              </li>

              <li
                v-for="n in visiblePagesData"
                :key="n"
                class="page-item"
                :class="{ active: n === currentPageData }"
              >
                <BLink
                  href="#"
                  class="page-link"
                  @click.prevent="currentPageData = n"
                >
                  {{ n }}
                </BLink>
              </li>

              <li
                class="page-item"
                :class="{ disabled: currentPageData === totalPagesData }"
              >
                <BLink
                  href="#"
                  class="page-link"
                  @click.prevent="currentPageData < totalPagesData && currentPageData++"
                >
                  <i class="ri-arrow-right-s-line"></i>
                </BLink>
              </li>
            </ul>
          </div>
        </BCardBody>
      </BCard>

      <!-- Delete Modal -->
      <BModal
        v-model="deleteModal"
        modal-class="zoomIn"
        hide-footer
        no-close-on-backdrop
        centered
      >
        <div class="mt-2 text-center">
          <div class="delete-icon mx-auto">
            <i class="ri-delete-bin-6-line"></i>
          </div>

          <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
            <h4>{{ $t('ui-are-you-sure') }}</h4>
            <p class="text-muted mx-4 mb-0">
              {{ $t('ui-this-scorm-course-and-its-package-files-will-be-deleted') }}
            </p>
          </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
          <button
            type="button"
            class="btn w-sm btn-light"
            @click="deleteModal = false"
          >
            {{ $t('ui-cancel') }}
          </button>

          <button
            type="button"
            class="btn w-sm btn-danger"
            @click="handleDeleteData"
          >
            {{ $t('ui-yes-delete') }}
          </button>
        </div>
      </BModal>
    </div>
  </Layout>
</template>

<style scoped>
.course-card {
  border-radius: 18px;
  overflow: hidden;
}

/* ================= RESPONSIVE HEADER ================= */
.course-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.course-header-row {
  width: 100%;
}

.course-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.course-header-icon {
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


.course-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.course-search-box {
  width: 320px;
  min-width: 260px;
}

.course-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.course-add-btn {
  height: 42px;
  border-radius: 12px;
  white-space: nowrap;
  padding-inline: 18px;
}

/* ================= TABLE ================= */
.course-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.course-table thead {
  background-color: #687cfe;
}

.course-head {
  background-color: #687cfe;
}

.course-table thead th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.course-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.course-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.course-image-box {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 14px;
  overflow: hidden;
  background-color: #f1f5f9;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
}

.course-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-image-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 14px;
}

.course-name-text,
.course-mobile-title {
  color: #334155;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-badge,
.price-badge,
.content-badge,
.badge {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.category-badge,
.content-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  color: #405189;
}

.price-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
  font-weight: 700;
}

.content-pills {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 6px;
}

.created-date {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
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
.course-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.course-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.course-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-image {
  width: 56px;
  height: 56px;
  min-width: 56px;
}

.course-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.course-mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.course-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  min-width: 105px;
}

.course-mobile-value {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.course-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.course-pagination-text {
  font-size: 14px;
}

.pagination .page-link {
  border-radius: 10px;
  margin: 0 3px;
  border: 1px solid #e5e7eb;
}

.pagination .page-item.active .page-link {
  background-color: #687cfe;
  border-color: #687cfe;
}

.delete-icon {
  width: 82px;
  height: 82px;
  border-radius: 24px;
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
}

.empty-course-icon {
  width: 86px;
  height: 86px;
  border-radius: 24px;
  background: rgba(104, 124, 254, 0.12);
  color: #687cfe;
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
  .course-header-actions {
    justify-content: flex-start;
  }

  .course-search-box {
    flex: 1;
    width: auto;
    min-width: 240px;
  }
}

@media (max-width: 991.98px) {
  .course-card-header {
    padding: 18px;
  }

  .course-pagination {
    justify-content: center;
    text-align: center;
  }

  .course-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 767.98px) {
  .course-header-title {
    align-items: flex-start;
  }

  .course-header-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .course-header-title h5 {
    font-size: 17px;
  }

  .course-header-title p {
    font-size: 13px;
  }

  .course-header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .course-search-box,
  .course-add-btn {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 575.98px) {
  .course-card {
    border-radius: 14px;
  }

  .course-card-header {
    padding: 16px;
  }

  .course-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .course-mobile-header {
    flex-direction: column;
  }

  .course-mobile-header > .d-flex:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  .course-mobile-title {
    max-width: 220px;
  }

  .course-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .course-mobile-value {
    text-align: left;
  }

  .pagination .page-link {
    min-width: 34px;
    height: 34px;
    padding: 6px 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

@media (max-width: 420px) {
  .course-header-title {
    flex-direction: column;
  }
}
</style>