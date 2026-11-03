<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'
import Lottie from '@/Components/widgets/lottie.vue'
import { EventBusCategories } from './EventBusCategories'
import { usePermissions } from '@/composables/permissions'

/* ================= PERMISSIONS ================= */
const hasPermission = (permission) => {
  const { hasPermission } = usePermissions()
  return hasPermission(permission)
}

/* ================= PROPS ================= */
const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
})

/* ================= STATE ================= */
const deleteModal = ref(false)
const selectedData = ref(null)

const direction = ref('asc')
const sortColumn = ref('name')

const dataLocal = ref([...props.categories])
const searchQuery = ref('')
const currentPageData = ref(1)
const perPageData = 10

const defaultOptions = {
  animationData,
}

const defaultOptions1 = ref({
  animationData: animationData1,
})

/* ================= HELPERS ================= */
const getInitials = (name) => {
  if (!name) return 'CA'

  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
}

const formatDate = (value) => {
  if (!value) return '-'

  return new Date(value).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB')
}

/* ================= SEARCH ================= */
const filtereData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  return dataLocal.value.filter((data) => {
    const statusText = data.is_active ? 'active' : 'inactive'

    const matchSearch =
      !search ||
      String(data.name ?? '').toLowerCase().includes(search) ||
      statusText.includes(search)

    return matchSearch
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
  () => props.categories,
  (newVal) => {
    dataLocal.value = [...newVal]
    currentPageData.value = 1
  },
  { immediate: true }
)

/* ================= ACTIONS ================= */
const showEdit = (data) => {
  EventBusCategories.dataEdit = data
  EventBusCategories['edit-data'] = true
}

const deleteData = (data) => {
  deleteModal.value = true
  selectedData.value = data.hashid
}

const handleDeleteData = () => {
  const formDelete = useForm({})

  formDelete.delete(route('categories.delete', selectedData.value), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-deleted'),
        text: t('ui-category-deleted-successfully'),
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

    if (column === 'is_active') {
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
  <div>
    <BCard class="category-type-card border-0 shadow-sm">
      <BCardHeader class="category-type-header border-0">
        <BRow class="g-3 align-items-center category-type-header-row">
          <BCol xl="5" lg="12" cols="12">
            <div class="category-type-title">
              <div class="category-type-icon">
                <i class="ri-price-tag-3-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">{{ $t('ui-categories') }}</h5>
                <p class="text-muted mb-0">
                  {{ $t('ui-manage-categories') }}
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="7" lg="12" cols="12">
            <div class="category-type-actions">
              <div class="category-count-pill">
                <i class="ri-price-tag-3-line me-1"></i>
                {{ filtereData.length }} {{ $t('t-category') }}{{ filtereData.length > 1 ? 'ies' : '' }}
              </div>

              <div class="search-box category-search-box">
                <input
                  type="text"
                  class="form-control"
                  v-model="searchQuery"
                  :placeholder="$t('ui-search-category')"
                />
                <i class="ri-search-line search-icon"></i>
              </div>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <!-- Desktop table -->
        <div class="category-table-wrapper d-none d-lg-block" v-if="paginatedData.length">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 category-table">
              <thead>
                <tr>
                  <th class="sort" @click="onSort('name')">
                    {{ $t('ui-name') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort text-center" @click="onSort('is_active')">
                    {{ $t('ui-status') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="text-center">{{ $t('ui-actions') }}</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="data in paginatedData"
                  :key="data.id"
                >
                  <td>
                    <div class="category-name-cell">
                      <div class="category-avatar">
                        {{ getInitials(data.name) }}
                      </div>

                      <div class="min-width-0">
                        <h6 class="mb-1 fw-semibold category-name-text">
                          {{ data.name || '-' }}
                        </h6>
                      </div>
                    </div>
                  </td>

                  <td class="text-center">
                    <span
                      class="status-badge"
                      :class="data.is_active ? 'status-badge-active' : 'status-badge-inactive'"
                    >
                      <i :class="data.is_active ? 'ri-checkbox-circle-line me-1' : 'ri-close-circle-line me-1'"></i>
                      {{ data.is_active ? $t('t-active') : $t('ui-inactive') }}
                    </span>
                  </td>

                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <BButton
                        v-if="hasPermission('update category')"
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        @click="showEdit(data)"
                        :title="$t('ui-edit')"
                      >
                        <i class="ri-edit-2-line"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('delete category')"
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        @click="deleteData(data)"
                        :title="$t('ui-delete')"
                      >
                        <i class="ri-delete-bin-6-line"></i>
                      </BButton>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mobile cards -->
        <div class="category-mobile-list d-lg-none" v-if="paginatedData.length">
          <div
            v-for="data in paginatedData"
            :key="data.id"
            class="category-mobile-card"
          >
            <div class="category-mobile-header">
              <div class="category-name-cell">
                <div class="category-avatar mobile-avatar">
                  {{ getInitials(data.name) }}
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold category-mobile-title">
                    {{ data.name || '-' }}
                  </h6>
                </div>
              </div>

              <div class="d-flex gap-2">
                <BButton
                  v-if="hasPermission('update category')"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  @click="showEdit(data)"
                  :title="$t('ui-edit')"
                >
                  <i class="ri-edit-2-line"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('delete category')"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  @click="deleteData(data)"
                  :title="$t('ui-delete')"
                >
                  <i class="ri-delete-bin-6-line"></i>
                </BButton>
              </div>
            </div>

            <div class="category-mobile-body">
              <div class="category-mobile-item">
                <span class="category-mobile-label">
                  <i class="ri-toggle-line me-1"></i>
                  {{ $t('ui-status') }}
                </span>

                <span
                  class="status-badge"
                  :class="data.is_active ? 'status-badge-active' : 'status-badge-inactive'"
                >
                  <i :class="data.is_active ? 'ri-checkbox-circle-line me-1' : 'ri-close-circle-line me-1'"></i>
                  {{ data.is_active ? $t('t-active') : $t('ui-inactive') }}
                </span>
              </div>

            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div class="category-empty-state" v-if="paginatedData.length === 0">
          <lottie
            class="avatar-xl mx-auto"
            colors="primary:#121331,secondary:#08a88a"
            :options="defaultOptions"
            :height="85"
            :width="85"
          />

          <h5 class="mt-3 mb-1">{{ $t('ui-no-categories-found') }}</h5>
          <p class="text-muted mb-0">
            {{ $t('ui-try-changing-your-search-keywords') }}
          </p>
        </div>

        <!-- Pagination -->
        <div class="category-pagination" v-if="filtereData.length > 0">
          <div class="text-muted category-pagination-text">
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
              class="page-item"
              v-for="n in visiblePagesData"
              :key="n"
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
  </div>

  <!-- Delete modal -->
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
          {{ $t('ui-you-won-t-be-able-to-revert-this-action') }}
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
</template>

<style scoped>
.category-type-card {
  border-radius: 18px;
  overflow: hidden;
  background-color: #ffffff;
}

.category-type-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.category-type-header-row {
  width: 100%;
}

.category-type-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.category-type-icon {
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

.category-type-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.category-count-pill {
  height: 42px;
  padding: 0 16px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  color: #405189;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.category-search-box {
  width: 320px;
  min-width: 260px;
}

.category-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.category-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.category-table thead {
  background-color: #687cfe;
}

.category-table thead th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.category-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.category-table tbody tr:last-child td {
  border-bottom: 0;
}

.category-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.category-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.category-avatar {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
  box-shadow: 0 8px 18px rgba(104, 124, 254, 0.22);
}

.category-name-text,
.category-mobile-title {
  color: #334155;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-subtext {
  color: #64748b;
  font-size: 13px;
}

.category-info {
  display: flex;
  align-items: center;
  color: #334155;
  white-space: nowrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.status-badge-active {
  background: rgba(10, 179, 156, 0.12);
  color: #0ab39c;
}

.status-badge-inactive {
  background: rgba(240, 101, 72, 0.12);
  color: #f06548;
}

.action-btn {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.category-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.category-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.category-mobile-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-avatar {
  width: 52px;
  height: 52px;
  min-width: 52px;
}

.category-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.category-mobile-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.category-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
  min-width: 105px;
}

.category-mobile-value {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.category-empty-state {
  padding: 54px 16px;
  text-align: center;
}

.category-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.category-pagination-text {
  font-size: 14px;
}

.pagination {
  flex-wrap: wrap;
  gap: 4px;
}

.pagination .page-link {
  border-radius: 10px;
  margin: 0 3px;
  border: 1px solid #e5e7eb;
  color: #405189;
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
  color: #94a3b8;
  background-color: #f8fafc;
}

.min-width-0 {
  min-width: 0;
}

.erreur-messages {
  font-size: 0.875em;
  color: var(--vz-form-invalid-color);
}

@media (max-width: 1199.98px) {
  .category-type-actions {
    justify-content: flex-start;
  }

  .category-search-box {
    flex: 1;
    width: auto;
    min-width: 220px;
  }
}

@media (max-width: 991.98px) {
  .category-type-header {
    padding: 18px;
  }

  .category-pagination {
    justify-content: center;
    text-align: center;
  }

  .category-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
  }
}

@media (max-width: 767.98px) {
  .category-type-card {
    border-radius: 14px;
  }

  .category-type-header {
    padding: 18px;
  }

  .category-type-title {
    align-items: flex-start;
  }

  .category-type-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .category-type-title h5 {
    font-size: 17px;
  }

  .category-type-title p {
    font-size: 13px;
  }

  .category-type-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .category-count-pill,
  .category-search-box {
    width: 100%;
    min-width: 100%;
  }

  .category-mobile-header {
    flex-direction: column;
  }

  .category-mobile-card {
    border-radius: 16px;
    padding: 14px;
  }

  .category-mobile-title {
    max-width: 220px;
  }

  .category-mobile-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .category-mobile-value {
    text-align: left;
  }
}

@media (max-width: 420px) {
  .category-type-title {
    flex-direction: column;
  }

  .category-empty-state {
    padding: 40px 12px;
  }
}
</style>