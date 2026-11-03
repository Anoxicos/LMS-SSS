<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { ref, computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import BankModal from './BankModal.vue'
import Lottie from '@/Components/widgets/lottie.vue'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'
import { usePermissions } from '@/composables/permissions'

const props = defineProps({
  banks: {
    type: Array,
    required: true,
  },
  courses: {
    type: Array,
    default: () => [],
  },
})

const { hasPermission } = usePermissions()

const modalRef = ref(null)
const deleteModal = ref(false)
const selectedData = ref(null)

const direction = ref('asc')
const sortColumn = ref('title')

const dataLocal = ref([...props.banks])
const searchQuery = ref('')
const currentPageData = ref(1)
const perPageData = 10

const defaultOptions = {
  animationData,
}

const defaultOptions1 = ref({
  animationData: animationData1,
})

const triggershowEdit = (data) => {
  modalRef.value?.showEdit(data)
}

const toggleModals = () => {
  modalRef.value?.toggleModal()
}

const getCourseInitials = (course) => {
  return String(course?.title || 'CO')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const getCourseImage = (course) => {
  return course?.image_url || null
}

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const filtereData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  if (!search) {
    return dataLocal.value
  }

  return dataLocal.value.filter((data) => {
    return (
      String(data.ref ?? '').toLowerCase().includes(search) ||
      String(data.title ?? '').toLowerCase().includes(search) ||
      String(data.description ?? '').toLowerCase().includes(search) ||
      String(data.course?.title ?? '').toLowerCase().includes(search) ||
      String(data.course?.category?.name ?? '').toLowerCase().includes(search)
    )
  })
})

const paginatedData = computed(() => {
  const start = (currentPageData.value - 1) * perPageData
  return filtereData.value.slice(start, start + perPageData)
})

const totalPagesData = computed(() => {
  return Math.ceil(filtereData.value.length / perPageData) || 1
})

watch(searchQuery, () => {
  currentPageData.value = 1
})

watch(
  () => props.banks,
  (newVal) => {
    dataLocal.value = [...newVal]
  },
  { immediate: true }
)

const deleteData = (data) => {
  deleteModal.value = true
  selectedData.value = data.id
}

const handleDeleteData = () => {
  const formDelete = useForm({})

  formDelete.delete(route('banks.delete', selectedData.value), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-deleted'),
        text: t('ui-bank-deleted-successfully'),
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
      selectedData.value = null
    },
  })
}

const onSort = (column) => {
  sortColumn.value = column
  direction.value = direction.value === 'asc' ? 'desc' : 'asc'

  const sortedArray = [...dataLocal.value]

  sortedArray.sort((a, b) => {
    let valueA = a[column]
    let valueB = b[column]

    if (column === 'course') {
      valueA = a.course?.title || ''
      valueB = b.course?.title || ''
    }

    if (column === 'category') {
      valueA = a.course?.category?.name || ''
      valueB = b.course?.category?.name || ''
    }

    if (column === 'questions_count') {
      valueA = Number(a.questions_count || 0)
      valueB = Number(b.questions_count || 0)
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
  <div>
    <BCard class="company-card border-0 shadow-sm">
      <BCardHeader class="company-card-header border-0">
        <BRow class="g-3 align-items-center company-header-row">
          <BCol xl="5" lg="12" md="12" cols="12">
            <div class="company-header-title">
              <div class="company-header-icon bank-header-icon">
                <i class="ri-bank-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">{{ $t('ui-question-banks') }}</h5>
                <p class="text-muted mb-0">
                  {{ $t('ui-manage-global-banks-and-assign-them-to-courses') }}
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="7" lg="12" md="12" cols="12">
            <div class="company-header-actions">
              <div class="search-box company-search-box">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="form-control"
                  :placeholder="$t('ui-search-bank')"
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                v-if="hasPermission('create banks')"
                type="button"
                variant="primary"
                class="add-btn company-add-btn"
                @click="toggleModals"
              >
                <i class="ri-add-line align-bottom me-1"></i>
                {{ $t('ui-add-bank') }}
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <div class="company-table-wrapper d-none d-lg-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 company-table bank-table">
              <thead>
                <tr>
                  <th class="sort" @click="onSort('title')">
                    {{ $t('ui-bank') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('ref')">
                    {{ $t('ui-ref') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('course')">
                    {{ $t('ui-course') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('category')">
                    {{ $t('ui-category') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort text-center" @click="onSort('questions_count')">
                    {{ $t('ui-questions') }}
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
                  v-for="data in paginatedData"
                  :key="data.id"
                >
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div class="company-logo-box bank-logo-box">
                        <i class="ri-bank-card-line"></i>
                      </div>

                      <div class="min-width-0">
                        <h6 class="mb-1 fw-semibold bank-title">
                          {{ data.title || '-' }}
                        </h6>

                        <small class="text-muted bank-description">
                          {{ data.description || 'No description.' }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <span class="ref-badge">
                      {{ data.ref || '-' }}
                    </span>
                  </td>

                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="mini-course-avatar">
                        <img
                          v-if="getCourseImage(data.course)"
                          :src="getCourseImage(data.course)"
                          :alt="$t('ui-course')"
                        />

                        <span v-else>
                          {{ getCourseInitials(data.course) }}
                        </span>
                      </div>

                      <span class="course-name-small">
                        {{ data.course?.title || '-' }}
                      </span>
                    </div>
                  </td>

                  <td>
                    <span class="category-badge">
                      <i class="ri-price-tag-3-line me-1"></i>
                      {{ data.course?.category?.name || '-' }}
                    </span>
                  </td>

                  <td class="text-center">
                    <span class="questions-badge">
                      <i class="ri-questionnaire-line me-1"></i>
                      {{ data.questions_count || 0 }}
                    </span>
                  </td>

                  <td>
                    <span class="created-date">
                      {{ formatDate(data.created_at) }}
                    </span>
                  </td>

                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <Link
                        v-if="hasPermission('read question')"
                        :href="route('banks.questions', data.id)"
                        class="btn btn-sm btn-soft-info action-btn"
                        :title="$t('ui-questions')"
                      >
                        <i class="ri-questionnaire-line"></i>
                      </Link>

                      <BButton
                        v-if="hasPermission('update banks')"
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        :title="$t('ui-edit')"
                        @click="triggershowEdit(data)"
                      >
                        <i class="ri-pencil-fill"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('delete banks')"
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        :title="$t('ui-delete')"
                        @click="deleteData(data)"
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

        <div class="company-mobile-list d-lg-none">
          <div
            v-for="data in paginatedData"
            :key="data.id"
            class="company-mobile-card"
          >
            <div class="company-mobile-header">
              <div class="d-flex align-items-center gap-3">
                <div class="company-logo-box mobile-logo bank-logo-box">
                  <i class="ri-bank-card-line"></i>
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold company-mobile-title">
                    {{ data.title || '-' }}
                  </h6>

                  <div class="d-flex flex-wrap gap-2">
                    <span class="ref-badge">
                      {{ data.ref || '-' }}
                    </span>

                    <span class="questions-badge">
                      {{ data.questions_count || 0 }} {{ $t('ui-questions') }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2">
                
                <Link
                    v-if="hasPermission('read question')"
                    :href="route('banks.questions', data.id)"
                    class="btn btn-sm btn-soft-info action-btn"
                    :title="$t('ui-questions')"
                    >
                    <i class="ri-questionnaire-line"></i>
                    </Link>

                <BButton
                  v-if="hasPermission('update banks')"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  :title="$t('ui-edit')"
                  @click="triggershowEdit(data)"
                >
                  <i class="ri-pencil-fill"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('delete banks')"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  :title="$t('ui-delete')"
                  @click="deleteData(data)"
                >
                  <i class="ri-delete-bin-fill"></i>
                </BButton>
              </div>
            </div>

            <div class="company-mobile-body">
              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-book-open-line me-1"></i>
                  {{ $t('ui-course') }}
                </span>
                <span class="company-mobile-value">
                  {{ data.course?.title || '-' }}
                </span>
              </div>

              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-price-tag-3-line me-1"></i>
                  {{ $t('ui-category') }}
                </span>
                <span class="category-badge">
                  {{ data.course?.category?.name || '-' }}
                </span>
              </div>

              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-calendar-line me-1"></i>
                  {{ $t('ui-created') }}
                </span>
                <span class="company-mobile-value">
                  {{ formatDate(data.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="paginatedData.length === 0"
          class="text-center py-5"
        >
          <lottie
            class="avatar-xl mx-auto"
            colors="primary:#121331,secondary:#08a88a"
            :options="defaultOptions"
            :height="85"
            :width="85"
          />

          <h5 class="mt-3 mb-1">{{ $t('ui-no-banks-found') }}</h5>
          <p class="text-muted mb-0">
            {{ $t('ui-try-changing-your-search-keywords') }}
          </p>
        </div>

        <div
          v-if="filtereData.length > 0"
          class="company-pagination"
        >
          <div class="text-muted company-pagination-text">
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
              v-for="n in totalPagesData"
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

    <BankModal
      ref="modalRef"
      :courses="courses"
    />

    <BModal
      v-model="deleteModal"
      modal-class="zoomIn"
      hide-footer
      no-close-on-backdrop
      centered
    >
      <div class="mt-2 text-center">
        <lottie
          class="avatar-xl mx-auto"
          colors="primary:#f7b84b,secondary:#f06548"
          :options="defaultOptions1"
          :height="100"
          :width="100"
        />

        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
          <h4>{{ $t('ui-are-you-sure') }}</h4>
          <p class="text-muted mx-4 mb-0">
            {{ $t('ui-this-bank-and-all-related-questions-will-be-deleted') }}
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
</template>

<style scoped>
.company-card {
  border-radius: 18px;
  overflow: hidden;
}

.company-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.company-header-row {
  width: 100%;
}

.company-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.company-header-icon {
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

.bank-header-icon {
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.28);
}

.company-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.company-search-box {
  width: 320px;
  min-width: 260px;
}

.company-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.company-add-btn {
  height: 42px;
  border-radius: 12px;
  white-space: nowrap;
  padding-inline: 18px;
}

.company-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.bank-table thead,
.bank-table thead tr,
.bank-table thead th {
  background-color: #687cfe !important;
  background-image: none !important;
}

.bank-table thead th {
  padding: 16px !important;
  font-size: 13px;
  font-weight: 700;
  color: #ffffff !important;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important;
  white-space: nowrap;
}

.bank-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.bank-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.company-logo-box {
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

.bank-logo-box {
  color: #687cfe;
  background: rgba(104, 124, 254, 0.12);
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.28);
  font-size: 24px;
}

.bank-title {
  color: #334155;
  max-width: 250px;
}

.bank-description {
  display: block;
  max-width: 280px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ref-badge,
.category-badge,
.questions-badge,
.badge {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.ref-badge {
  display: inline-flex;
  align-items: center;
  background: #e0f2fe;
  color: #0284c7;
}

.category-badge {
  display: inline-flex;
  align-items: center;
  background: #eef2ff;
  color: #405189;
}

.questions-badge {
  display: inline-flex;
  align-items: center;
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
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

.mini-course-avatar {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 10px;
  overflow: hidden;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 800;
}

.mini-course-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-name-small {
  color: #334155;
  font-weight: 600;
  max-width: 230px;
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.company-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.company-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.company-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-logo {
  width: 56px;
  height: 56px;
  min-width: 56px;
}

.company-mobile-title {
  max-width: 170px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.company-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.company-mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.company-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  min-width: 95px;
}

.company-mobile-value {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.company-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.company-pagination-text {
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

.min-width-0 {
  min-width: 0;
}

@media (max-width: 1199.98px) {
  .company-header-actions {
    justify-content: flex-start;
  }

  .company-search-box {
    flex: 1;
    width: auto;
    min-width: 240px;
  }
}

@media (max-width: 991.98px) {
  .company-card-header {
    padding: 18px;
  }

  .company-pagination {
    justify-content: center;
    text-align: center;
  }

  .company-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 767.98px) {
  .company-header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .company-search-box,
  .company-add-btn {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 575.98px) {
  .company-mobile-header {
    flex-direction: column;
  }

  .company-mobile-header > .d-flex:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  .company-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .company-mobile-value {
    text-align: left;
  }
}
</style>