<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import LearnerModal from './LearnerModal.vue'
import Lottie from '@/Components/widgets/lottie.vue'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'
import defaultImage from '@assets/images/default-user.png'
import { usePermissions } from '@/composables/permissions'

/* ================= PERMISSIONS ================= */
const { hasPermission } = usePermissions()

/* ================= PROPS ================= */
const props = defineProps({
  users: {
    type: Array,
    required: true,
  },
  roles: {
    type: Array,
    default: () => [],
  },
  companies: {
    type: Array,
    default: () => [],
  },
  authUser: {
    type: Object,
    default: () => ({}),
  },
})

/* ================= MODAL ================= */
const modalRef = ref(null)

const triggerShowEdit = data => {
  modalRef.value?.showEdit(data)
}

const toggleModal = () => {
  modalRef.value?.toggleModal()
}

/* ================= STATE ================= */
const deleteModal = ref(false)
const selectedData = ref(null)

const direction = ref('asc')
const sortColumn = ref('full_name')

const dataLocal = ref([...props.users])
const searchQuery = ref('')
const selectedCompany = ref('')
const currentPageData = ref(1)
const perPageData = 10

const defaultOptions = {
  animationData,
}

const defaultOptions1 = {
  animationData: animationData1,
}

/* ================= HELPERS ================= */
const getUserPhoto = user => {
  if (!user) return defaultImage

  if (user.profile_photo_path) {
    return `/storage/${user.profile_photo_path}`
  }

  if (user.photo) {
    return `/storage/${user.photo}`
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

const handleImageError = event => {
  event.target.src = defaultImage
}

const getCompanyName = user => {
  return user.company?.name || user.company_name || '-'
}

const getFullName = user => {
  const parts = [
    user?.first_name,
    user?.middle_name,
    user?.last_name,
  ].filter(Boolean)

  return parts.join(' ') || user?.full_name || user?.name || '-'
}

const getPassportUrl = user => {
  if (user?.passport_file_url) return user.passport_file_url
  if (user?.passport_file) return `/storage/${user.passport_file}`

  return null
}

const getRolesText = user => {
  if (!user.roles?.length) return '-'

  return user.roles.map(role => role.name).join(', ')
}

const getStatusClass = status => {
  return status === 'active'
    ? 'badge bg-success-subtle text-success'
    : 'badge bg-danger-subtle text-danger'
}

const getVerifiedClass = date => {
  return date
    ? 'badge bg-success-subtle text-success'
    : 'badge bg-warning-subtle text-warning'
}

const formatDate = date => {
  if (!date) return '-'

  const parsedDate = new Date(date)

  if (Number.isNaN(parsedDate.getTime())) return '-'

  return parsedDate.toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const getSortIcon = column => {
  if (sortColumn.value !== column) {
    return 'ri-arrow-up-down-line'
  }

  return direction.value === 'asc'
    ? 'ri-sort-asc'
    : 'ri-sort-desc'
}

/* ================= SEARCH + COMPANY FILTER ================= */
const filteredData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()
  const companyId = selectedCompany.value

  return dataLocal.value.filter(data => {
    const matchCompany =
      !companyId ||
      String(data.company_id ?? data.company?.id ?? '') === String(companyId)

    const matchSearch =
      !search ||
      String(getFullName(data)).toLowerCase().includes(search) ||
      String(data.first_name ?? '').toLowerCase().includes(search) ||
      String(data.middle_name ?? '').toLowerCase().includes(search) ||
      String(data.last_name ?? '').toLowerCase().includes(search) ||
      String(data.passport_number ?? '').toLowerCase().includes(search) ||
      String(data.email ?? '').toLowerCase().includes(search) ||
      String(data.status ?? '').toLowerCase().includes(search) ||
      String(data.date_of_birth ?? '').toLowerCase().includes(search) ||
      String(data.place_of_birth ?? '').toLowerCase().includes(search) ||
      String(data.country ?? '').toLowerCase().includes(search) ||
      String(getCompanyName(data)).toLowerCase().includes(search) ||
      String(getRolesText(data)).toLowerCase().includes(search)

    return matchCompany && matchSearch
  })
})

/* ================= PAGINATION ================= */
const paginatedData = computed(() => {
  const start = (currentPageData.value - 1) * perPageData

  return filteredData.value.slice(start, start + perPageData)
})

const totalPagesData = computed(() => {
  return Math.ceil(filteredData.value.length / perPageData) || 1
})

const visiblePagesData = computed(() => {
  const total = totalPagesData.value
  const current = currentPageData.value
  const pages = []

  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + 4)

  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return pages
})

watch([searchQuery, selectedCompany], () => {
  currentPageData.value = 1
})

watch(
  () => props.users,
  newValue => {
    dataLocal.value = [...newValue]

    if (currentPageData.value > totalPagesData.value) {
      currentPageData.value = totalPagesData.value
    }
  },
  { immediate: true }
)

/* ================= DELETE ================= */
const deleteData = data => {
  deleteModal.value = true
  selectedData.value = data.hashid || data.id
}

const closeDeleteModal = () => {
  deleteModal.value = false
  selectedData.value = null
}

const handleDeleteData = () => {
  if (!selectedData.value) return

  const formDelete = useForm({})

  formDelete.delete(route('learners.delete', selectedData.value), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Deleted',
        text: 'Learner deleted successfully.',
        timer: 2000,
        showConfirmButton: false,
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred while deleting the learner.',
      })
    },
    onFinish: closeDeleteModal,
  })
}

/* ================= SORT ================= */
const onSort = column => {
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

    if (column === 'full_name') {
      valueA = getFullName(a)
      valueB = getFullName(b)
    }

    if (column === 'company') {
      valueA = getCompanyName(a)
      valueB = getCompanyName(b)
    }

    if (column === 'roles') {
      valueA = getRolesText(a)
      valueB = getRolesText(b)
    }

    const finalA = String(valueA ?? '').toLowerCase()
    const finalB = String(valueB ?? '').toLowerCase()

    const result = finalA.localeCompare(finalB, undefined, {
      numeric: true,
      sensitivity: 'base',
    })

    return direction.value === 'asc' ? result : -result
  })

  dataLocal.value = sortedArray
  currentPageData.value = 1
}
</script>

<template>
  <div>
    <BCard class="user-card border-0 shadow-sm">
      <!-- Header -->
      <BCardHeader class="user-card-header border-0">
        <BRow class="g-3 align-items-center user-header-row">
          <BCol xl="4" lg="12" md="12" cols="12">
            <div class="user-header-title">
              <div class="user-header-icon">
                <i class="ri-team-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">Learners</h5>
                <p class="text-muted mb-0">
                  Manage learners, companies and account information.
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="8" lg="12" md="12" cols="12">
            <div class="user-header-actions">
              <div class="user-filter-box">
                <i class="ri-building-line user-control-icon"></i>

                <select
                  v-model="selectedCompany"
                  class="form-select company-filter"
                >
                  <option value="">All companies</option>

                  <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                  >
                    {{ company.name }}
                  </option>
                </select>
              </div>

              <div class="search-box user-search-box">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="form-control"
                  placeholder="Search learner..."
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                v-if="hasPermission('create learner')"
                id="create-btn"
                type="button"
                variant="primary"
                class="add-btn user-add-btn"
                @click="toggleModal"
              >
                <i class="ri-add-line align-bottom me-1"></i>
                Add Learner
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <!-- Desktop table -->
        <div class="user-table-wrapper d-none d-xl-block">
          <div class="table-responsive compact-table-responsive">
            <table class="table table-hover align-middle mb-0 user-table compact-user-table">
              <thead>
                <tr>
                  <th
                    class="sort learner-column"
                    @click="onSort('full_name')"
                  >
                    Learner
                    <i :class="getSortIcon('full_name')" class="ms-1"></i>
                  </th>

                  <th
                    class="sort organization-column"
                    @click="onSort('company')"
                  >
                    Organization
                    <i :class="getSortIcon('company')" class="ms-1"></i>
                  </th>

                  <th
                    class="sort personal-column"
                    @click="onSort('date_of_birth')"
                  >
                    Personal information
                    <i :class="getSortIcon('date_of_birth')" class="ms-1"></i>
                  </th>

                  <th
                    class="sort account-column text-center"
                    @click="onSort('status')"
                  >
                    Account
                    <i :class="getSortIcon('status')" class="ms-1"></i>
                  </th>

                  <th class="actions-column text-center">
                    Actions
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="data in paginatedData"
                  :key="data.id || data.hashid"
                >
                  <!-- Photo + name + email -->
                  <td>
                    <div class="compact-learner">
                      <div class="user-photo-box">
                        <img
                          :src="getUserPhoto(data)"
                          :alt="getFullName(data) || 'Learner photo'"
                          class="user-photo"
                          @error="handleImageError"
                        />
                      </div>

                      <div class="compact-learner-content">
                        <h6
                          class="mb-1 fw-semibold compact-user-name"
                          :title="getFullName(data)"
                        >
                          {{ getFullName(data) }}
                        </h6>

                        <div
                          class="compact-info-line"
                          :title="data.email || '-'"
                        >
                          <i class="ri-mail-line"></i>
                          <span>{{ data.email || '-' }}</span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Company + roles -->
                  <td>
                    <div class="organization-info">
                      <div
                        class="compact-info-line organization-name mb-2"
                        :title="getCompanyName(data)"
                      >
                        <i class="ri-building-line"></i>
                        <span class="fw-semibold">
                          {{ getCompanyName(data) }}
                        </span>
                      </div>

                     
                    </div>
                  </td>

                  <!-- Date/place of birth + country + passport -->
                  <td>
                    <div class="personal-information">
                      <div class="personal-info-grid">
                        <div class="compact-info-line">
                          <i class="ri-calendar-event-line"></i>
                          <span>{{ formatDate(data.date_of_birth) }}</span>
                        </div>

                        <div
                          class="compact-info-line"
                          :title="data.place_of_birth || '-'"
                        >
                          <i class="ri-map-pin-user-line"></i>
                          <span>{{ data.place_of_birth || '-' }}</span>
                        </div>
                      </div>

                      <div
                        class="compact-info-line mt-2"
                        :title="data.country || '-'"
                      >
                        <i class="ri-earth-line"></i>
                        <span>{{ data.country || '-' }}</span>
                      </div>

                      <div
                        class="compact-info-line mt-2"
                        :title="data.passport_number || '-'"
                      >
                        <i class="ri-passport-line"></i>
                        <span>
                          {{ data.passport_number || 'No passport number' }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- Status + email verification -->
                  <td class="text-center">
                    <div class="account-status-box">
                      <span :class="getStatusClass(data.status)">
                        <i
                          :class="data.status === 'active'
                            ? 'ri-checkbox-circle-line'
                            : 'ri-close-circle-line'"
                          class="me-1"
                        ></i>
                        {{ data.status || '-' }}
                      </span>

                      <span :class="getVerifiedClass(data.email_verified_at)">
                        <i
                          :class="data.email_verified_at
                            ? 'ri-mail-check-line'
                            : 'ri-time-line'"
                          class="me-1"
                        ></i>
                        {{ data.email_verified_at ? 'Verified' : 'Pending' }}
                      </span>

                     
                    </div>
                  </td>

                  <!-- Actions -->
                  <td>
                    <div class="actions-wrapper">
                      <BButton
                        v-if="getPassportUrl(data) && hasPermission('read learner')"
                        variant="soft-warning"
                        size="sm"
                        class="action-btn passport-action-btn"
                        :href="getPassportUrl(data)"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Open passport document"
                      >
                        <i class="ri-file-pdf-2-line"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('read learner')"
                        variant="soft-info"
                        size="sm"
                        class="action-btn"
                        :href="route('learners.show', data.hashid || data.id)"
                        title="View learner"
                      >
                        <i class="ri-eye-line"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('update learner')"
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        title="Edit learner"
                        @click="triggerShowEdit(data)"
                      >
                        <i class="ri-pencil-fill"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('delete learner')"
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        title="Delete learner"
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

        <!-- Tablet and mobile cards -->
        <div class="user-mobile-list d-xl-none">
          <div
            v-for="data in paginatedData"
            :key="data.id || data.hashid"
            class="user-mobile-card"
          >
            <div class="user-mobile-header">
              <div class="d-flex align-items-center gap-3 min-width-0">
                <div class="user-photo-box mobile-photo">
                  <img
                    :src="getUserPhoto(data)"
                    :alt="getFullName(data) || 'Learner photo'"
                    class="user-photo"
                    @error="handleImageError"
                  />
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold user-mobile-title">
                    {{ getFullName(data) }}
                  </h6>

                  <div class="user-mobile-email">
                    <i class="ri-mail-line me-1"></i>
                    {{ data.email || '-' }}
                  </div>
                </div>
              </div>

              <div class="mobile-actions">
                <BButton
                  v-if="getPassportUrl(data) && hasPermission('read learner')"
                  variant="soft-warning"
                  size="sm"
                  class="action-btn passport-action-btn"
                  :href="getPassportUrl(data)"
                  target="_blank"
                  rel="noopener noreferrer"
                  title="Open passport document"
                >
                  <i class="ri-file-pdf-2-line"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('read learner')"
                  variant="soft-info"
                  size="sm"
                  class="action-btn"
                  :href="route('learners.show', data.hashid || data.id)"
                  title="View learner"
                >
                  <i class="ri-eye-line"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('update learner')"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  title="Edit learner"
                  @click="triggerShowEdit(data)"
                >
                  <i class="ri-pencil-fill"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('delete learner')"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  title="Delete learner"
                  @click="deleteData(data)"
                >
                  <i class="ri-delete-bin-fill"></i>
                </BButton>
              </div>
            </div>

            <div class="mobile-status-row">
              <span :class="getStatusClass(data.status)">
                <i
                  :class="data.status === 'active'
                    ? 'ri-checkbox-circle-line'
                    : 'ri-close-circle-line'"
                  class="me-1"
                ></i>
                {{ data.status || '-' }}
              </span>

              <span :class="getVerifiedClass(data.email_verified_at)">
                <i
                  :class="data.email_verified_at
                    ? 'ri-mail-check-line'
                    : 'ri-time-line'"
                  class="me-1"
                ></i>
                {{ data.email_verified_at ? 'Verified' : 'Pending' }}
              </span>
            </div>

            <div class="user-mobile-body">
              <div class="mobile-information-group">
                <div class="mobile-group-title">
                  <i class="ri-building-line"></i>
                  Organization
                </div>

                <div class="user-mobile-item">
                  <span class="user-mobile-label">Company</span>
                  <span class="user-mobile-value">
                    {{ getCompanyName(data) }}
                  </span>
                </div>

              
              </div>

              <div class="mobile-information-group">
                <div class="mobile-group-title">
                  <i class="ri-profile-line"></i>
                  Personal information
                </div>

                <div class="user-mobile-item">
                  <span class="user-mobile-label">Date of birth</span>
                  <span class="user-mobile-value">
                    {{ formatDate(data.date_of_birth) }}
                  </span>
                </div>

                <div class="user-mobile-item">
                  <span class="user-mobile-label">Place of birth</span>
                  <span class="user-mobile-value">
                    {{ data.place_of_birth || '-' }}
                  </span>
                </div>

                <div class="user-mobile-item">
                  <span class="user-mobile-label">Country</span>
                  <span class="user-mobile-value">
                    {{ data.country || '-' }}
                  </span>
                </div>

                <div class="user-mobile-item">
                  <span class="user-mobile-label">Passport number</span>
                  <span class="user-mobile-value">
                    {{ data.passport_number || '-' }}
                  </span>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
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

          <h5 class="mt-3 mb-1">No learners found</h5>
          <p class="text-muted mb-0">
            Try changing your search or company filter.
          </p>
        </div>

        <!-- Pagination -->
        <div
          v-if="filteredData.length > 0"
          class="user-pagination"
        >
          <div class="text-muted user-pagination-text">
            Showing
            <span class="fw-semibold">{{ paginatedData.length }}</span>
            of
            <span class="fw-semibold">{{ filteredData.length }}</span>
            result(s)
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
              v-for="page in visiblePagesData"
              :key="page"
              class="page-item"
              :class="{ active: page === currentPageData }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="currentPageData = page"
              >
                {{ page }}
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

  <LearnerModal
    ref="modalRef"
    :roles="roles"
    :companies="companies"
    :auth-user="authUser"
  />

  <!-- Delete modal -->
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
        <h4>Are you sure?</h4>
        <p class="text-muted mx-4 mb-0">
          You will not be able to restore this learner.
        </p>
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
      <button
        type="button"
        class="btn w-sm btn-light"
        @click="closeDeleteModal"
      >
        Cancel
      </button>

      <button
        id="delete-record"
        type="button"
        class="btn w-sm btn-danger"
        @click="handleDeleteData"
      >
        Yes, delete
      </button>
    </div>
  </BModal>
</template>

<style scoped>
.user-card {
  border-radius: 18px;
  overflow: hidden;
}

.user-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.user-header-row {
  width: 100%;
}

.user-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.user-header-icon {
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

.user-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.user-filter-box {
  position: relative;
  width: 240px;
  min-width: 210px;
}

.user-filter-box .company-filter {
  height: 42px;
  border-radius: 12px;
  padding-left: 38px;
  border-color: #e5e7eb;
}

.user-control-icon {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #64748b;
  z-index: 2;
  font-size: 16px;
  pointer-events: none;
}

.user-search-box {
  width: 280px;
  min-width: 230px;
}

.user-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.user-add-btn {
  height: 42px;
  border-radius: 12px;
  white-space: nowrap;
  padding-inline: 18px;
}

/* ================= COMPACT DESKTOP TABLE ================= */
.user-table-wrapper {
  width: 100%;
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.compact-table-responsive {
  width: 100%;
  overflow-x: hidden;
}

.compact-user-table {
  width: 100%;
  table-layout: fixed;
}

.compact-user-table .learner-column {
  width: 24%;
}

.compact-user-table .organization-column {
  width: 17%;
}

.compact-user-table .personal-column {
  width: 32%;
}

.compact-user-table .account-column {
  width: 15%;
}

.compact-user-table .actions-column {
  width: 12%;
}

.user-table thead {
  background-color: #687cfe;
}

.user-table thead th {
  padding: 15px 14px;
  font-size: 12px;
  font-weight: 700;
  color: #f8fafc;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: none;
  white-space: nowrap;
}

.user-table tbody td {
  padding: 15px 14px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
  overflow: hidden;
}

.user-table tbody tr:last-child td {
  border-bottom: none;
}

.user-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.compact-learner {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.compact-learner-content,
.organization-info,
.personal-information {
  min-width: 0;
}

.compact-learner-content {
  flex: 1;
}

.compact-user-name {
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.compact-info-line {
  display: flex;
  align-items: center;
  gap: 7px;
  min-width: 0;
  color: #64748b;
  font-size: 13px;
}

.compact-info-line i {
  flex-shrink: 0;
  color: #8492a6;
  font-size: 15px;
}

.compact-info-line span {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.organization-name {
  color: #334155;
}

.personal-info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px 12px;
}

.user-photo-box {
  width: 50px;
  height: 50px;
  min-width: 50px;
  border-radius: 50%;
  overflow: hidden;
  background-color: #f1f5f9;
  border: 2px solid #ffffff;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.roles-list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
  max-width: 100%;
}

.role-badge {
  margin: 0;
  padding: 5px 9px;
  border-radius: 999px;
}

.account-status-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.account-status-box .badge {
  width: 100%;
  max-width: 105px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.actions-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 6px;
}

.action-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.passport-action-btn i {
  font-size: 17px;
}

.badge {
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

/* ================= MOBILE CARDS ================= */
.user-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.user-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.user-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-photo {
  width: 56px;
  height: 56px;
  min-width: 56px;
}

.user-mobile-title {
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-mobile-email {
  max-width: 260px;
  color: #64748b;
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 6px;
}

.mobile-status-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding-top: 14px;
}

.user-mobile-body {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  padding-top: 14px;
}

.mobile-information-group {
  padding: 14px;
  border: 1px solid #edf2f7;
  border-radius: 14px;
  background-color: #f8fafc;
}

.mobile-group-title {
  display: flex;
  align-items: center;
  gap: 7px;
  padding-bottom: 10px;
  margin-bottom: 10px;
  color: #405189;
  font-size: 13px;
  font-weight: 700;
  border-bottom: 1px solid #e9edf3;
}

.mobile-group-title i {
  font-size: 16px;
}

.user-mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 7px 0;
}

.user-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  min-width: 95px;
}

.user-mobile-value {
  min-width: 0;
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.user-mobile-value .role-badge {
  display: inline-flex;
  margin: 0 0 4px 4px;
}

/* ================= PAGINATION ================= */
.user-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.user-pagination-text {
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

/* ================= RESPONSIVE ================= */
@media (max-width: 1399.98px) {
  .user-table thead th,
  .user-table tbody td {
    padding-inline: 11px;
  }

  .compact-user-table .learner-column {
    width: 23%;
  }

  .compact-user-table .organization-column {
    width: 17%;
  }

  .compact-user-table .personal-column {
    width: 33%;
  }

  .compact-user-table .account-column {
    width: 15%;
  }

  .compact-user-table .actions-column {
    width: 12%;
  }

  .user-photo-box {
    width: 46px;
    height: 46px;
    min-width: 46px;
  }
}

@media (max-width: 1199.98px) {
  .user-header-actions {
    justify-content: flex-start;
  }

  .user-filter-box,
  .user-search-box {
    flex: 1;
    width: auto;
    min-width: 220px;
  }

  .user-mobile-body {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .user-card-header {
    padding: 18px;
  }

  .user-pagination {
    justify-content: center;
    text-align: center;
  }

  .user-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 767.98px) {
  .user-header-title {
    align-items: flex-start;
  }

  .user-header-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .user-header-title h5 {
    font-size: 17px;
  }

  .user-header-title p {
    font-size: 13px;
  }

  .user-header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .user-filter-box,
  .user-search-box,
  .user-add-btn {
    width: 100%;
    min-width: 100%;
  }

  .user-mobile-body {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .user-card {
    border-radius: 14px;
  }

  .user-card-header {
    padding: 16px;
  }

  .user-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .user-mobile-header {
    flex-direction: column;
  }

  .mobile-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .user-mobile-title,
  .user-mobile-email {
    max-width: 210px;
  }

  .mobile-information-group {
    padding: 12px;
  }

  .user-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .user-mobile-value {
    text-align: left;
  }

  .user-mobile-value .role-badge {
    margin-left: 0;
    margin-right: 4px;
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
  .user-header-title {
    flex-direction: column;
  }
}
</style>
