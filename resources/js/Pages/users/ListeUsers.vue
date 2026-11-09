<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import UserModal from './UserModal.vue'
import Lottie from '@/Components/widgets/lottie.vue'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'
import defaultImage from '@assets/images/default-user.png'
import { usePermissions } from '@/composables/permissions'

const { hasPermission } = usePermissions()

const props = defineProps({
  users: {
    type: Array,
    default: () => [],
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

const modalRef = ref(null)
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

const toggleModal = () => {
  modalRef.value?.toggleModal()
}

const triggerShowEdit = data => {
  modalRef.value?.showEdit(data)
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

const getFullName = user => {
  const parts = [
    user?.first_name,
    user?.middle_name,
    user?.last_name,
  ].filter(Boolean)

  return parts.join(' ') ||
    user?.full_name ||
    user?.name ||
    '-'
}

const getCompanyName = user => {
  return user?.company?.name ||
    user?.company_name ||
    '-'
}

const getRolesText = user => {
  if (!user?.roles?.length) return '-'

  return user.roles
    .map(role => role.name)
    .join(', ')
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

const handleImageError = event => {
  event.target.src = defaultImage
}

const formatDate = date => {
  if (!date) return '-'

  const parsed = new Date(date)

  if (Number.isNaN(parsed.getTime())) {
    return '-'
  }

  return parsed.toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
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

const filteredData = computed(() => {
  const search = searchQuery.value
    .toLowerCase()
    .trim()

  const companyId = selectedCompany.value

  return dataLocal.value.filter(data => {
    const matchCompany =
      !companyId ||
      String(
        data.company_id ??
        data.company?.id ??
        ''
      ) === String(companyId)

    const matchSearch =
      !search ||
      String(getFullName(data))
        .toLowerCase()
        .includes(search) ||
      String(data.first_name ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.middle_name ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.last_name ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.email ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.country ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.place_of_birth ?? '')
        .toLowerCase()
        .includes(search) ||
      String(data.status ?? '')
        .toLowerCase()
        .includes(search) ||
      String(getCompanyName(data))
        .toLowerCase()
        .includes(search) ||
      String(getRolesText(data))
        .toLowerCase()
        .includes(search)

    return matchCompany && matchSearch
  })
})

const paginatedData = computed(() => {
  const start =
    (currentPageData.value - 1) *
    perPageData

  return filteredData.value.slice(
    start,
    start + perPageData
  )
})

const totalPagesData = computed(() => {
  return Math.ceil(
    filteredData.value.length /
    perPageData
  ) || 1
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

  for (
    let page = start;
    page <= end;
    page += 1
  ) {
    pages.push(page)
  }

  return pages
})

watch(
  [searchQuery, selectedCompany],
  () => {
    currentPageData.value = 1
  }
)

watch(
  () => props.users,
  newValue => {
    dataLocal.value = [...newValue]

    if (
      currentPageData.value >
      totalPagesData.value
    ) {
      currentPageData.value =
        totalPagesData.value
    }
  },
  {
    immediate: true,
  }
)

const onSort = column => {
  if (sortColumn.value === column) {
    direction.value =
      direction.value === 'asc'
        ? 'desc'
        : 'asc'
  } else {
    sortColumn.value = column
    direction.value = 'asc'
  }

  const sorted = [...dataLocal.value]

  sorted.sort((a, b) => {
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

    const finalA = String(valueA ?? '')
      .toLowerCase()
    const finalB = String(valueB ?? '')
      .toLowerCase()

    const result = finalA.localeCompare(
      finalB,
      undefined,
      {
        numeric: true,
        sensitivity: 'base',
      }
    )

    return direction.value === 'asc'
      ? result
      : -result
  })

  dataLocal.value = sorted
  currentPageData.value = 1
}

const deleteData = data => {
  deleteModal.value = true
  selectedData.value =
    data.hashid ||
    data.id
}

const closeDeleteModal = () => {
  deleteModal.value = false
  selectedData.value = null
}

const handleDeleteData = () => {
  if (!selectedData.value) return

  const deleteForm = useForm({})

  deleteForm.delete(
    route('users.delete', selectedData.value),
    {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon: 'success',
          title: t('ui-deleted'),
          text: t('ui-user-deleted-successfully'),
          timer: 2000,
          showConfirmButton: false,
        })
      },
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: t('t-error'),
          text: t('ui-an-error-occurred-while-deleting-the-user'),
        })
      },
      onFinish: closeDeleteModal,
    }
  )
}
</script>

<template>
  <div>
    <BCard class="user-card border-0 shadow-sm">
      <BCardHeader class="user-card-header border-0">
        <BRow class="g-3 align-items-center">
          <BCol xl="4" cols="12">
            <div class="header-title">
              <div class="header-icon">
                <i class="ri-team-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">
                  {{ $t('ui-users') }}
                </h5>
                <p class="text-muted mb-0">
                  {{ $t('ui-manage-users-companies-roles-and-accounts') }}
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="8" cols="12">
            <div class="header-actions">
              <div class="filter-box">
                <i class="ri-building-line control-icon"></i>

                <select
                  v-model="selectedCompany"
                  class="form-select"
                >
                  <option value="">
                    {{ $t('ui-all-companies') }}
                  </option>

                  <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                  >
                    {{ company.name }}
                  </option>
                </select>
              </div>

              <div class="search-box user-search">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="form-control"
                  :placeholder="$t('ui-search-user')"
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                v-if="hasPermission('create user')"
                type="button"
                variant="primary"
                class="add-user-btn"
                @click="toggleModal"
              >
                <i class="ri-add-line me-1"></i>
                {{ $t('ui-add-user') }}
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <div class="table-wrapper d-none d-xl-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 user-table">
              <thead>
                <tr>
                  <th
                    class="sort user-column"
                    @click="onSort('full_name')"
                  >
                    {{ $t('ui-user') }}
                    <i
                      :class="getSortIcon('full_name')"
                      class="ms-1"
                    ></i>
                  </th>

                  <th
                    class="sort organization-column"
                    @click="onSort('company')"
                  >
                    {{ $t('ui-organization') }}
                    <i
                      :class="getSortIcon('company')"
                      class="ms-1"
                    ></i>
                  </th>

                  <th
                    class="sort identity-column"
                    @click="onSort('date_of_birth')"
                  >
                    {{ $t('ui-identity') }}
                    <i
                      :class="getSortIcon('date_of_birth')"
                      class="ms-1"
                    ></i>
                  </th>

                  <th
                    class="sort account-column text-center"
                    @click="onSort('status')"
                  >
                    {{ $t('ui-account') }}
                    <i
                      :class="getSortIcon('status')"
                      class="ms-1"
                    ></i>
                  </th>

                  <th class="actions-column text-center">
                    {{ $t('ui-actions') }}
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="data in paginatedData"
                  :key="data.id || data.hashid"
                >
                  <td>
                    <div class="user-summary">
                      <div class="user-photo-box">
                        <img
                          :src="getUserPhoto(data)"
                          :alt="getFullName(data)"
                          class="user-photo"
                          @error="handleImageError"
                        />
                      </div>

                      <div class="min-width-0">
                        <h6
                          class="mb-1 user-name"
                          :title="getFullName(data)"
                        >
                          {{ getFullName(data) }}
                        </h6>

                        <div
                          class="info-line"
                          :title="data.email || '-'"
                        >
                          <i class="ri-mail-line"></i>
                          <span>
                            {{ data.email || '-' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div
                      class="info-line mb-2"
                      :title="getCompanyName(data)"
                    >
                      <i class="ri-building-line"></i>
                      <span class="fw-semibold">
                        {{ getCompanyName(data) }}
                      </span>
                    </div>

                  
                  </td>

                  <td>
                    <div class="identity-grid">
                      <div class="info-line">
                        <i class="ri-calendar-event-line"></i>
                        <span>
                          {{ formatDate(data.date_of_birth) }}
                        </span>
                      </div>

                      <div
                        class="info-line"
                        :title="data.place_of_birth || '-'"
                      >
                        <i class="ri-map-pin-user-line"></i>
                        <span>
                          {{ data.place_of_birth || '-' }}
                        </span>
                      </div>
                    </div>

                    <div class="info-line mt-2">
                      <i class="ri-earth-line"></i>
                      <span>
                        {{ data.country || '-' }}
                      </span>
                    </div>
                  </td>

                  <td class="text-center">
                    <div class="account-box">
                      <span :class="getStatusClass(data.status)">
                        <i
                          :class="
                            data.status === 'active'
                              ? 'ri-checkbox-circle-line'
                              : 'ri-close-circle-line'
                          "
                          class="me-1"
                        ></i>
                        {{ data.status || '-' }}
                      </span>

                      <span
                        :class="getVerifiedClass(
                          data.email_verified_at
                        )"
                      >
                        <i
                          :class="
                            data.email_verified_at
                              ? 'ri-mail-check-line'
                              : 'ri-time-line'
                          "
                          class="me-1"
                        ></i>
                        {{
                          data.email_verified_at
                            ? $t('ui-verified')
                            : $t('t-pending')
                        }}
                      </span>

                        <div
                          v-if="data.roles?.length"
                          class="roles-list"
                        >
                          <span
                            v-for="role in data.roles"
                            :key="role.id"
                            class="badge bg-primary-subtle text-primary role-badge"
                          >
                            {{ role.name }}
                          </span>
                        </div>

                        <span v-else class="text-muted small">
                          {{ $t('ui-no-role') }}
                        </span>
                        
                    </div>
                  </td>

                  <td>
                    <div class="actions-wrapper">

                      <BButton
                        v-if="hasPermission('update user')"
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        :title="$t('ui-edit-user')"
                        @click="triggerShowEdit(data)"
                      >
                        <i class="ri-pencil-fill"></i>
                      </BButton>

                      <BButton
                        v-if="hasPermission('delete user')"
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        :title="$t('ui-delete-user')"
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

        <div class="mobile-list d-xl-none">
          <div
            v-for="data in paginatedData"
            :key="data.id || data.hashid"
            class="mobile-card"
          >
            <div class="mobile-header">
              <div class="d-flex align-items-center gap-3 min-width-0">
                <div class="user-photo-box mobile-photo">
                  <img
                    :src="getUserPhoto(data)"
                    :alt="getFullName(data)"
                    class="user-photo"
                    @error="handleImageError"
                  />
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 user-name">
                    {{ getFullName(data) }}
                  </h6>
                  <div class="mobile-email">
                    <i class="ri-mail-line me-1"></i>
                    {{ data.email || '-' }}
                  </div>
                </div>
              </div>

              <div class="mobile-actions">

                <BButton
                  v-if="hasPermission('update user')"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  :title="$t('ui-edit-user')"
                  @click="triggerShowEdit(data)"
                >
                  <i class="ri-pencil-fill"></i>
                </BButton>

                <BButton
                  v-if="hasPermission('delete user')"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  :title="$t('ui-delete-user')"
                  @click="deleteData(data)"
                >
                  <i class="ri-delete-bin-fill"></i>
                </BButton>
              </div>
            </div>

            <div class="mobile-status-row">
              <span :class="getStatusClass(data.status)">
                {{ data.status || '-' }}
              </span>

              <span
                :class="getVerifiedClass(
                  data.email_verified_at
                )"
              >
                {{
                  data.email_verified_at
                    ? $t('ui-verified')
                    : $t('t-pending')
                }}
              </span>

              <div
                  v-if="data.roles?.length"
                  class="roles-list"
                >
                  <span
                    v-for="role in data.roles"
                    :key="role.id"
                    class="badge bg-primary-subtle text-primary role-badge"
                  >
                    {{ role.name }}
                  </span>
              </div>

              <span v-else class="text-muted small">
                {{ $t('ui-no-role') }}
              </span>
            </div>

            <div class="mobile-body">
              <div class="mobile-group">
                <div class="mobile-group-title">
                  <i class="ri-building-line"></i>
                  {{ $t('ui-organization') }}
                </div>

                <div class="mobile-item">
                  <span>{{ $t('ui-company') }}</span>
                  <strong>
                    {{ getCompanyName(data) }}
                  </strong>
                </div>

                
              </div>

              <div class="mobile-group">
                <div class="mobile-group-title">
                  <i class="ri-profile-line"></i>
                  {{ $t('ui-identity') }}
                </div>

                <div class="mobile-item">
                  <span>{{ $t('ui-date-of-birth') }}</span>
                  <strong>
                    {{ formatDate(data.date_of_birth) }}
                  </strong>
                </div>

                <div class="mobile-item">
                  <span>{{ $t('ui-place-of-birth') }}</span>
                  <strong>
                    {{ data.place_of_birth || '-' }}
                  </strong>
                </div>

                <div class="mobile-item">
                  <span>{{ $t('ui-country') }}</span>
                  <strong>
                    {{ data.country || '-' }}
                  </strong>
                </div>
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

          <h5 class="mt-3 mb-1">
            {{ $t('ui-no-users-found') }}
          </h5>
          <p class="text-muted mb-0">
            {{ $t('ui-try-changing-your-search-or-company-filter') }}
          </p>
        </div>

        <div
          v-if="filteredData.length > 0"
          class="pagination-wrapper"
        >
          <div class="text-muted">
            {{ $t('ui-showing') }}
            <span class="fw-semibold">
              {{ paginatedData.length }}
            </span>
            {{ $t('ui-of') }}
            <span class="fw-semibold">
              {{ filteredData.length }}
            </span>
            {{ $t('ui-result-s') }}
          </div>

          <ul class="pagination mb-0">
            <li
              class="page-item"
              :class="{
                disabled: currentPageData === 1,
              }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="
                  currentPageData > 1 &&
                  currentPageData--
                "
              >
                <i class="ri-arrow-left-s-line" aria-hidden="true"></i><span class="visually-hidden">{{ $t('ui-previous') }}</span>
              </BLink>
            </li>

            <li
              v-for="page in visiblePagesData"
              :key="page"
              class="page-item"
              :class="{
                active: page === currentPageData,
              }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="
                  currentPageData = page
                "
              >
                {{ page }}
              </BLink>
            </li>

            <li
              class="page-item"
              :class="{
                disabled:
                  currentPageData === totalPagesData,
              }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="
                  currentPageData < totalPagesData &&
                  currentPageData++
                "
              >
                <i class="ri-arrow-right-s-line" aria-hidden="true"></i><span class="visually-hidden">{{ $t('ui-next') }}</span>
              </BLink>
            </li>
          </ul>
        </div>
      </BCardBody>
    </BCard>
  </div>

  <UserModal
    ref="modalRef"
    :roles="roles"
    :companies="companies"
    :auth-user="authUser"
  />

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
          {{ $t('ui-you-will-not-be-able-to-restore-this-user') }}
        </p>
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
      <button
        type="button"
        class="btn w-sm btn-light"
        @click="closeDeleteModal"
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
.user-card {
  border-radius: 18px;
  overflow: hidden;
}

.user-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
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
}

.header-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
}

.filter-box {
  position: relative;
  width: 240px;
}

.filter-box .form-select {
  height: 42px;
  padding-left: 38px;
  border-radius: 12px;
}

.control-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  z-index: 2;
  color: #64748b;
  transform: translateY(-50%);
  pointer-events: none;
}

.user-search {
  width: 280px;
}

.user-search .form-control,
.add-user-btn {
  height: 42px;
  border-radius: 12px;
}

.add-user-btn {
  white-space: nowrap;
  padding-inline: 18px;
}

.table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
}

.user-table {
  width: 100%;
  table-layout: fixed;
}

.user-column {
  width: 24%;
}

.organization-column {
  width: 20%;
}

.identity-column {
  width: 31%;
}

.account-column {
  width: 15%;
}

.actions-column {
  width: 10%;
}

.user-table thead {
  background-color: #687cfe;
}

.user-table thead th {
  padding: 15px 14px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  white-space: nowrap;
  border-bottom: none;
}

.user-table tbody td {
  padding: 15px 14px;
  border-bottom: 1px solid #f1f5f9;
}

.user-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.user-summary {
  display: flex;
  align-items: center;
  gap: 12px;
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
}

.user-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-name {
  color: #1e293b;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.info-line {
  display: flex;
  align-items: center;
  gap: 7px;
  min-width: 0;
  color: #64748b;
  font-size: 13px;
}

.info-line i {
  flex-shrink: 0;
  color: #8492a6;
}

.info-line span {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.identity-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.roles-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.role-badge {
  padding: 5px 9px;
  border-radius: 999px;
}

.account-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.account-box .badge {
  width: 100%;
  max-width: 105px;
}

.actions-wrapper,
.mobile-actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 6px;
}

.action-btn {
  width: 34px;
  height: 34px;
  padding: 0;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.mobile-card {
  padding: 16px;
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.mobile-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.mobile-photo {
  width: 56px;
  height: 56px;
  min-width: 56px;
}

.mobile-email {
  max-width: 250px;
  color: #64748b;
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-status-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding-top: 14px;
}

.mobile-body {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  padding-top: 14px;
}

.mobile-group {
  padding: 14px;
  border: 1px solid #edf2f7;
  border-radius: 14px;
  background: #f8fafc;
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

.mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 7px 0;
  font-size: 13px;
}

.mobile-item span {
  color: #64748b;
  font-weight: 600;
}

.mobile-item strong {
  color: #334155;
  text-align: right;
  word-break: break-word;
}

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.pagination .page-link {
  margin: 0 3px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}

.pagination .page-item.active .page-link {
  background-color: #687cfe;
  border-color: #687cfe;
}

.badge {
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.min-width-0 {
  min-width: 0;
}

@media (max-width: 1199.98px) {
  .header-actions {
    justify-content: flex-start;
  }

  .filter-box,
  .user-search {
    flex: 1;
    width: auto;
    min-width: 220px;
  }
}

@media (max-width: 991.98px) {
  .user-card-header {
    padding: 18px;
  }

  .pagination-wrapper {
    justify-content: center;
    flex-wrap: wrap;
    text-align: center;
  }

  .pagination-wrapper > div {
    width: 100%;
  }
}

@media (max-width: 767.98px) {
  .header-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-box,
  .user-search,
  .add-user-btn {
    width: 100%;
    min-width: 100%;
  }

  .mobile-body {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .user-card {
    border-radius: 14px;
  }

  .mobile-card {
    padding: 14px;
  }

  .mobile-header {
    flex-direction: column;
  }

  .mobile-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .mobile-item strong {
    text-align: left;
  }
}
</style>
