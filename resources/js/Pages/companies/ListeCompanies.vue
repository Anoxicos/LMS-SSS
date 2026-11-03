<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import CompanyModal from './CompanyModal.vue'
import Lottie from '@/Components/widgets/lottie.vue'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'
import defaultImage from '@assets/images/default-company.jpg'
import { usePermissions } from '@/composables/permissions'

const { hasPermission } = usePermissions()

const props = defineProps({
  companies: {
    type: Array,
    default: () => [],
  },
})

const modalRef = ref(null)

const triggershowEdit = (data) => {
  modalRef.value?.showEdit(data)
}

const toggleModals = () => {
  modalRef.value?.toggleModal()
}

const showCompany = (data) => {
  if (!data?.hashid) return

  router.get(route('companies.show', data.hashid))
}

const deleteModal = ref(false)
const selectedData = ref(null)

const direction = ref('asc')
const sortColumn = ref('name')

const dataLocal = ref([])
const searchQuery = ref('')
const currentPageData = ref(1)
const perPageData = 10

const defaultOptions = {
  animationData,
}

const defaultOptions1 = ref({
  animationData: animationData1,
})

const getCompanyLogo = (company) => {
  if (company.logo_url) return company.logo_url
  if (company.logo) return `/storage/${company.logo}`
  return defaultImage
}

const getStatusClass = (status) => {
  return status === 'active'
    ? 'badge bg-success-subtle text-success'
    : 'badge bg-danger-subtle text-danger'
}

const filtereData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  if (!search) return dataLocal.value

  return dataLocal.value.filter((data) =>
    String(data.name ?? '').toLowerCase().includes(search) ||
    String(data.email ?? '').toLowerCase().includes(search) ||
    String(data.phone ?? '').toLowerCase().includes(search) ||
    String(data.address ?? '').toLowerCase().includes(search) ||
    String(data.status ?? '').toLowerCase().includes(search)
  )
})

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
    return Array.from({ length: total }, (_, index) => index + 1)
  }

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

  const pages = [1]

  for (let page = start; page <= end; page++) {
    pages.push(page)
  }

  if (!pages.includes(total)) {
    pages.push(total)
  }

  return pages
})

watch(searchQuery, () => {
  currentPageData.value = 1
})

watch(
  () => props.companies,
  (newVal) => {
    dataLocal.value = [...(newVal || [])]
    currentPageData.value = 1
  },
  { immediate: true }
)

watch(totalPagesData, (total) => {
  if (currentPageData.value > total) {
    currentPageData.value = total
  }
})

const deleteData = (data) => {
  deleteModal.value = true
  selectedData.value = data.hashid
}

const handleDeleteData = () => {
  if (!selectedData.value) return

  const formDelete = useForm({})

  formDelete.delete(route('companies.delete', selectedData.value), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-deleted'),
        text: t('ui-company-deleted-successfully'),
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
  if (sortColumn.value === column) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    direction.value = 'asc'
  }

  const sortedArray = [...dataLocal.value]

  sortedArray.sort((a, b) => {
    const valueA = String(a[column] ?? '').toLowerCase()
    const valueB = String(b[column] ?? '').toLowerCase()

    const result = valueA < valueB ? -1 : valueA > valueB ? 1 : 0

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
              <div class="company-header-icon">
                <i class="ri-building-4-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">{{ $t('ui-companies') }}</h5>
                <p class="text-muted mb-0">
                  {{ $t('ui-manage-company-information-and-open-detailed-statistics') }}
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
                  :placeholder="$t('ui-search-company')"
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                v-if="hasPermission('create company')"
                type="button"
                variant="primary"
                class="add-btn company-add-btn"
                id="create-btn"
                @click="toggleModals"
              >
                <i class="ri-add-line align-bottom me-1"></i>
                {{ $t('ui-add-company') }}
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <div class="company-table-wrapper d-none d-lg-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 company-table">
              <thead>
                <tr>
                  <th class="sort" @click="onSort('name')">
                    {{ $t('ui-company') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('email')">
                    {{ $t('ui-email') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('phone')">
                    {{ $t('ui-phone') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort" @click="onSort('address')">
                    {{ $t('ui-address') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="sort text-center" @click="onSort('status')">
                    {{ $t('ui-status') }}
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="text-center">{{ $t('ui-actions') }}</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="data in paginatedData"
                  :key="data.id || data.hashid"
                >
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div class="company-logo-box">
                        <img
                          :src="getCompanyLogo(data)"
                          :alt="$t('ui-company-logo')"
                          class="company-logo"
                        />
                      </div>

                      <div class="min-width-0">
                        <h6 class="mb-1 fw-semibold company-name-cell">
                          {{ data.name || '-' }}
                        </h6>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="company-info">
                      <i class="ri-mail-line text-muted me-1"></i>
                      <span>{{ data.email || '-' }}</span>
                    </div>
                  </td>

                  <td>
                    <div class="company-info">
                      <i class="ri-phone-line text-muted me-1"></i>
                      <span>{{ data.phone || '-' }}</span>
                    </div>
                  </td>

                  <td>
                    <div class="company-address">
                      <i class="ri-map-pin-line text-muted me-1"></i>
                      <span>{{ data.address || '-' }}</span>
                    </div>
                  </td>

                  <td class="text-center">
                    <span :class="getStatusClass(data.status)">
                      {{ data.status || '-' }}
                    </span>
                  </td>

                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <BButton
                        v-if="hasPermission('read company')"
                        variant="soft-info"
                        size="sm"
                        class="action-btn"
                        @click="showCompany(data)"
                        :title="$t('ui-view-details')"
                      >
                        <i class="ri-eye-line"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('update company')"
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        @click="triggershowEdit(data)"
                        :title="$t('ui-edit')"
                      >
                        <i class="ri-pencil-fill"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('delete company')"
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        @click="deleteData(data)"
                        :title="$t('ui-delete')"
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
            :key="data.id || data.hashid"
            class="company-mobile-card"
          >
            <div class="company-mobile-header">
              <div class="d-flex align-items-center gap-3 min-width-0">
                <div class="company-logo-box mobile-logo">
                  <img
                    :src="getCompanyLogo(data)"
                    :alt="$t('ui-company-logo')"
                    class="company-logo"
                  />
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold company-mobile-title">
                    {{ data.name || '-' }}
                  </h6>

                  <span :class="getStatusClass(data.status)">
                    {{ data.status || '-' }}
                  </span>
                </div>
              </div>

              <div class="d-flex gap-2 company-mobile-actions">
                <BButton
                  v-if="hasPermission('read company')"
                  variant="soft-info"
                  size="sm"
                  class="action-btn"
                  @click="showCompany(data)"
                  :title="$t('ui-view-details')"
                >
                  <i class="ri-eye-line"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('update company')"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  @click="triggershowEdit(data)"
                  :title="$t('ui-edit')"
                >
                  <i class="ri-pencil-fill"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('delete company')"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  @click="deleteData(data)"
                  :title="$t('ui-delete')"
                >
                  <i class="ri-delete-bin-fill"></i>
                </BButton>
              </div>
            </div>

            <div class="company-mobile-body">
              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-mail-line me-1"></i>
                  {{ $t('ui-email') }}
                </span>
                <span class="company-mobile-value">
                  {{ data.email || '-' }}
                </span>
              </div>

              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-phone-line me-1"></i>
                  {{ $t('ui-phone') }}
                </span>
                <span class="company-mobile-value">
                  {{ data.phone || '-' }}
                </span>
              </div>

              <div class="company-mobile-item">
                <span class="company-mobile-label">
                  <i class="ri-map-pin-line me-1"></i>
                  {{ $t('ui-address') }}
                </span>
                <span class="company-mobile-value">
                  {{ data.address || '-' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="paginatedData.length === 0"
          class="text-center py-5"
        >
          <Lottie
            class="avatar-xl mx-auto"
            colors="primary:#121331,secondary:#08a88a"
            :options="defaultOptions"
            :height="85"
            :width="85"
          />

          <h5 class="mt-3 mb-1">{{ $t('ui-no-companies-found') }}</h5>
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
  </div>

  <CompanyModal ref="modalRef" />

  <BModal
    v-model="deleteModal"
    modal-class="zoomIn"
    hide-footer
    no-close-on-backdrop
    centered
  >
    <div class="mt-2 text-center">
      <Lottie
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
        id="delete-record"
        @click="handleDeleteData"
      >
        {{ $t('ui-yes-delete') }}
      </button>
    </div>
  </BModal>
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

.company-table thead {
  background-color: #687cfe;
}

.company-table thead th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.company-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.company-table tbody tr:hover {
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

.company-logo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.company-name-cell {
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.company-info {
  display: flex;
  align-items: center;
  color: #334155;
  white-space: nowrap;
}

.company-address {
  max-width: 260px;
  color: #334155;
  display: flex;
  align-items: center;
}

.company-address span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.action-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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

.badge {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
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
  .company-header-title {
    align-items: flex-start;
  }

  .company-header-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .company-header-title h5 {
    font-size: 17px;
  }

  .company-header-title p {
    font-size: 13px;
  }

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
  .company-card {
    border-radius: 14px;
  }

  .company-card-header {
    padding: 16px;
  }

  .company-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .company-mobile-header {
    flex-direction: column;
  }

  .company-mobile-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .company-mobile-title {
    max-width: 220px;
  }

  .company-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .company-mobile-value {
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
  .company-header-title {
    flex-direction: column;
  }
}
</style>
