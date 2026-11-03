<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { usePermissions } from '@/composables/permissions'

/* ================= PERMISSIONS ================= */
const hasPermission = (permission) => {

  const { hasPermission } = usePermissions()

  return hasPermission(permission)

}

/* ================= PROPS ================= */
const props = defineProps({

  companyRequests: {
    type: Array,
    required: true,
  },

  companies: {
    type: Array,
    default: () => [],
  },

  canApprove: {
    type: Boolean,
    default: false,
  },

})

/* ================= STATE ================= */
const direction = ref('asc')

const sortColumn = ref('reference')

const dataLocal = ref([...props.companyRequests])

const searchQuery = ref('')

const selectedCompanyId = ref('')

const currentPageData = ref(1)

const perPageData = 10

/* ================= HELPERS ================= */
const getCourseImage = (request) => {

  if (request.course?.image_url) {

    return request.course.image_url

  }

  if (request.course?.image) {

    return `/storage/${request.course.image}`

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

const getStatusClass = (status) => {

  if (status === 'approved') {

    return 'badge bg-success-subtle text-success'

  }

  if (status === 'rejected') {

    return 'badge bg-danger-subtle text-danger'

  }

  return 'badge bg-warning-subtle text-warning'

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

const formatDate = (date) => {

  if (!date) return '-'

  return new Date(date).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {

    year: 'numeric',

    month: 'short',

    day: '2-digit',

  })

}

const formatDateTime = (date) => {

  if (!date) return '-'

  return new Date(date).toLocaleString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {

    year: 'numeric',

    month: 'short',

    day: '2-digit',

    hour: '2-digit',

    minute: '2-digit',

  })

}

const formatTrainingPeriod = (requestItem) => {

  const startDate = formatDate(requestItem.start_date)

  const endDate = formatDate(requestItem.end_date)

  if (startDate === '-' && endDate === '-') {

    return '-'

  }

  return `${startDate} - ${endDate}`

}

const formatValidityDuration = (months) => {

  if (months === null || months === undefined || months === '') {

    return t('t-no-expiration')

  }

  const value = Number(months)

  if (!Number.isFinite(value) || value <= 0) {

    return t('t-no-expiration')

  }

  return `${value} ${t(value > 1 ? 't-month-plural' : 't-month-singular')}`

}

const getCertificateVisibilityLabel = (requestItem) => {

  return requestItem?.certificate_visible_to_learner !== false
    ? 'Visible'
    : 'Hidden'

}

const getCertificateVisibilityClass = (requestItem) => {

  return requestItem?.certificate_visible_to_learner !== false
    ? 'certificate-visibility-badge visible'
    : 'certificate-visibility-badge hidden'

}

const getCertificateVisibilityIcon = (requestItem) => {

  return requestItem?.certificate_visible_to_learner !== false
    ? 'ri-eye-line'
    : 'ri-eye-off-line'

}

const safeRoute = (name, hashid) => {

  if (!hashid) return '#'

  return route(name, hashid)

}

/* ================= FILTER ================= */
const filtereData = computed(() => {

  const search = searchQuery.value.toLowerCase().trim()

  const companyId = selectedCompanyId.value

  return dataLocal.value.filter((item) => {

    const matchesCompany =

      !companyId || Number(item.company?.id) === Number(companyId)

    const matchesSearch =

      !search ||

      String(item.reference ?? '').toLowerCase().includes(search) ||

      String(item.status ?? '').toLowerCase().includes(search) ||

      String(item.notes ?? '').toLowerCase().includes(search) ||

      String(item.company?.name ?? '').toLowerCase().includes(search) ||

      String(item.course?.title ?? '').toLowerCase().includes(search) ||

      String(item.requested_by?.name ?? '').toLowerCase().includes(search) ||

      String(item.requested_by?.email ?? '').toLowerCase().includes(search) ||

      String(item.start_date ?? '').toLowerCase().includes(search) ||

      String(item.end_date ?? '').toLowerCase().includes(search) ||

      String(item.duree_validite_formation ?? '').toLowerCase().includes(search) ||

      formatValidityDuration(item.duree_validite_formation).toLowerCase().includes(search) ||

      getCertificateVisibilityLabel(item).toLowerCase().includes(search) ||

      (item.accreditations || []).some((accreditation) => {

        return (

          String(accreditation.name ?? '').toLowerCase().includes(search) ||

          String(accreditation.code ?? '').toLowerCase().includes(search) ||

          String(accreditation.organization_name ?? '').toLowerCase().includes(search)

        )

      })

    return matchesCompany && matchesSearch

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

watch([searchQuery, selectedCompanyId], () => {

  currentPageData.value = 1

})

watch(

  () => props.companyRequests,

  (newVal) => {

    dataLocal.value = [...newVal]

    currentPageData.value = 1

  },

  { immediate: true }

)

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

    if (column === 'company') {

      valueA = a.company?.name || ''

      valueB = b.company?.name || ''

    }

    if (column === 'course') {

      valueA = a.course?.title || ''

      valueB = b.course?.title || ''

    }

    if (column === 'requested_by') {

      valueA = a.requested_by?.name || ''

      valueB = b.requested_by?.name || ''

    }

    if (column === 'accreditations') {

      valueA = (a.accreditations || []).map((item) => item.name).join(' ')

      valueB = (b.accreditations || []).map((item) => item.name).join(' ')

    }

    if (column === 'period') {

      valueA = a.start_date || ''

      valueB = b.start_date || ''

    }

    if (column === 'validity') {

      valueA = Number(a.duree_validite_formation || 0)

      valueB = Number(b.duree_validite_formation || 0)

      const result = valueA < valueB ? -1 : valueA > valueB ? 1 : 0

      return direction.value === 'asc' ? result : -result

    }

    if (column === 'certificate_visibility') {

      valueA = a.certificate_visible_to_learner !== false ? 1 : 0

      valueB = b.certificate_visible_to_learner !== false ? 1 : 0

      const result = valueA < valueB ? -1 : valueA > valueB ? 1 : 0

      return direction.value === 'asc' ? result : -result

    }

    if (column === 'learners_count') {

      valueA = Number(a.learners_count || 0)

      valueB = Number(b.learners_count || 0)

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

/* ================= ACTIONS ================= */
const approveRequest = (requestItem) => {

  if (!requestItem.hashid) {

    Swal.fire({

      icon: 'error',

      title: t('t-missing-request-id'),

      text: t('t-request-hash-missing'),

    })

    return

  }

  Swal.fire({

    icon: 'question',

    title: t('t-approve-request-confirm'),

    text: t('t-assign-course-confirm'),

    showCancelButton: true,

    confirmButtonText: t('t-yes-approve'),

    cancelButtonText: t('ui-cancel'),

    confirmButtonColor: '#0ab39c',

  }).then((result) => {

    if (result.isConfirmed) {

      router.post(

        route('company-requests.approve', requestItem.hashid),

        {},

        {

          preserveScroll: true,

          onSuccess: () => {

            Swal.fire({

              icon: 'success',

              title: t('t-approved'),

              text: t('t-approved-success'),

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

        }

      )

    }

  })

}

const rejectRequest = (requestItem) => {

  if (!requestItem.hashid) {

    Swal.fire({

      icon: 'error',

      title: t('t-missing-request-id'),

      text: t('t-request-hash-missing'),

    })

    return

  }

  Swal.fire({

    icon: 'warning',

    title: t('t-reject-request-confirm'),

    input: 'textarea',

    inputLabel: t('ui-reason-notes'),

    inputPlaceholder: t('ui-write-the-reason'),

    showCancelButton: true,

    confirmButtonText: t('t-reject'),

    cancelButtonText: t('ui-cancel'),

    confirmButtonColor: '#f06548',

  }).then((result) => {

    if (result.isConfirmed) {

      router.post(

        route('company-requests.reject', requestItem.hashid),

        {

          notes: result.value,

        },

        {

          preserveScroll: true,

          onSuccess: () => {

            Swal.fire({

              icon: 'success',

              title: t('t-rejected'),

              text: t('t-rejected-success'),

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

        }

      )

    }

  })

}

</script>

<template>

  <div>

    <BCard class="request-card border-0 shadow-sm">

      <!-- Responsive Header -->

      <BCardHeader class="request-card-header border-0">

        <BRow class="g-3 align-items-center request-header-row">

          <BCol xl="4" lg="12" md="12" cols="12">

            <div class="request-header-title">

              <div class="request-header-icon">

                <i class="ri-file-list-3-line"></i>

              </div>

              <div>

                <h5 class="mb-1 fw-semibold">{{ $t('ui-company-requests') }}</h5>

                <p class="text-muted mb-0">

                  {{ $t('ui-manage-course-assignment-requests-from-companies') }}

                </p>

              </div>

            </div>

          </BCol>

          <BCol xl="8" lg="12" md="12" cols="12">

            <div class="request-header-actions">

              <div class="company-filter-box">

                <select

                  v-model="selectedCompanyId"

                  class="form-select company-filter-select"

                >

                  <option value="">{{ $t('ui-all-companies') }}</option>

                  <option

                    v-for="company in companies"

                    :key="company.id"

                    :value="company.id"

                  >

                    {{ company.name }}

                  </option>

                </select>

              </div>

              <div class="search-box request-search-box">

                <input

                  v-model="searchQuery"

                  type="text"

                  class="form-control"

                  :placeholder="$t('ui-search-request')"

                />

                <i class="ri-search-line search-icon"></i>

              </div>

              <Link

                v-if="hasPermission('create company request')"

                :href="route('company-requests.create')"

                class="btn btn-primary add-btn request-add-btn"

              >

                <i class="ri-add-line align-bottom me-1"></i>

                {{ $t('ui-new-request') }}

              </Link>

            </div>

          </BCol>

        </BRow>

      </BCardHeader>

      <BCardBody>

        <!-- Desktop Table -->

        <div class="request-table-wrapper d-none d-xl-block">

          <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 request-table">

              <thead>

                <tr>

                  <th class="sort" @click="onSort('reference')">

                    {{ $t('ui-reference') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort" @click="onSort('company')">

                    {{ $t('ui-company') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort" @click="onSort('course')">

                    {{ $t('ui-course') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort" @click="onSort('accreditations')">

                    {{ $t('ui-accreditations') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort" @click="onSort('period')">

                    {{ $t('t-training-period') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort" @click="onSort('validity')">

                    {{ $t('ui-validity') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort text-center" @click="onSort('certificate_visibility')">

                    {{ $t('ui-certificate') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort text-center" @click="onSort('learners_count')">

                    {{ $t('ui-learners') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>

                  <th class="sort text-center" @click="onSort('status')">

                    {{ $t('ui-status') }}

                    <i class="ri-arrow-up-down-line ms-1"></i>

                  </th>





                  <th class="text-center">

                    {{ $t('ui-actions') }}

                  </th>

                </tr>

              </thead>

              <tbody>

                <tr

                  v-for="requestItem in paginatedData"

                  :key="requestItem.id || requestItem.hashid"

                >

                  <td>

                    <div>

                      <h6 class="mb-1 fw-semibold request-ref-text">

                        {{ requestItem.reference || '-' }}

                      </h6>

                      <small class="text-muted">

                        {{ formatDateTime(requestItem.created_at) }}

                      </small>

                    </div>

                  </td>

                  <td>

                    <span class="company-badge">

                      <i class="ri-building-4-line me-1"></i>

                      {{ requestItem.company?.name || '-' }}

                    </span>

                  </td>

                  <td>

                    <div class="d-flex align-items-center gap-3">

                      <div class="request-course-image-box">

                        <img

                          v-if="getCourseImage(requestItem)"

                          :src="getCourseImage(requestItem)"

                          :alt="$t('t-course-image')"

                          class="request-course-image"

                        />

                        <div

                          v-else

                          class="request-course-image-fallback"

                        >

                          {{ getCourseInitials(requestItem.course?.title) }}

                        </div>

                      </div>

                      <div class="min-width-0">

                        <h6 class="mb-1 fw-semibold request-course-text">

                          {{ requestItem.course?.title || '-' }}

                        </h6>

                        <small class="text-muted">

                          {{ $t('ui-course-requested') }}

                        </small>

                      </div>

                    </div>

                  </td>

                  <td>

                    <div

                      v-if="requestItem.accreditations?.length"

                      class="accreditation-badges"

                    >

                      <span

                        v-for="accreditation in requestItem.accreditations"

                        :key="accreditation.id"

                        :class="getAccreditationClass(accreditation.code)"

                        :title="accreditation.organization_name || accreditation.name"

                      >

                        <i class="ri-shield-check-line"></i>

                        {{ accreditation.name }}

                      </span>

                    </div>

                    <span v-else class="text-muted small">-</span>

                  </td>

                  <td>

                    <span class="period-badge">

                      <i class="ri-calendar-event-line"></i>

                      {{ formatTrainingPeriod(requestItem) }}

                    </span>

                  </td>

                  <td>

                    <span class="validity-badge">

                      <i class="ri-timer-line"></i>

                      {{ formatValidityDuration(requestItem.duree_validite_formation) }}

                    </span>

                  </td>

                  <td class="text-center">

                    <span :class="getCertificateVisibilityClass(requestItem)">

                      <i :class="`${getCertificateVisibilityIcon(requestItem)} me-1`"></i>

                      {{ getCertificateVisibilityLabel(requestItem) }}

                    </span>

                  </td>

                  <td class="text-center">

                    <span class="learners-badge">

                      <i class="ri-team-line me-1"></i>

                      {{ requestItem.learners_count || 0 }}

                      {{ $t('t-learner') }}{{ Number(requestItem.learners_count || 0) > 1 ? 's' : '' }}

                    </span>

                  </td>

                  <td class="text-center">

                    <span :class="getStatusClass(requestItem.status)">

                      <i :class="`${getStatusIcon(requestItem.status)} me-1`"></i>

                      {{ requestItem.status || 'pending' }}

                    </span>

                  </td>

                  <td>

                    <div class="d-flex justify-content-center gap-2">

                      <Link

                        v-if="requestItem.hashid"

                        :href="safeRoute('company-requests.show', requestItem.hashid)"

                        class="btn btn-sm btn-soft-info action-btn"

                        :title="$t('ui-view')"

                      >

                        <i class="ri-eye-line"></i>

                      </Link>

                      <Link

                        v-if="requestItem.hashid && (requestItem.status === 'pending' || canApprove)"

                        :href="safeRoute('company-requests.edit', requestItem.hashid)"

                        class="btn btn-sm btn-soft-primary action-btn"

                        :title="$t('ui-edit')"

                      >

                        <i class="ri-pencil-fill"></i>

                      </Link>

                      <BButton

                        v-if="canApprove && requestItem.status === 'pending'"

                        variant="soft-success"

                        size="sm"

                        class="action-btn"

                        :title="$t('t-approve')"

                        @click="approveRequest(requestItem)"

                      >

                        <i class="ri-check-line"></i>

                      </BButton>

                      <BButton

                        v-if="canApprove && requestItem.status === 'pending'"

                        variant="soft-danger"

                        size="sm"

                        class="action-btn"

                        :title="$t('t-reject')"

                        @click="rejectRequest(requestItem)"

                      >

                        <i class="ri-close-line"></i>

                      </BButton>

                    </div>

                  </td>

                </tr>

              </tbody>

            </table>

          </div>

        </div>

        <!-- Mobile Cards -->

        <div class="request-mobile-list d-xl-none">

          <div

            v-for="requestItem in paginatedData"

            :key="requestItem.id || requestItem.hashid"

            class="request-mobile-card"

          >

            <div class="request-mobile-header">

              <div class="d-flex align-items-center gap-3">

                <div class="request-course-image-box mobile-image">

                  <img

                    v-if="getCourseImage(requestItem)"

                    :src="getCourseImage(requestItem)"

                    :alt="$t('t-course-image')"

                    class="request-course-image"

                  />

                  <div

                    v-else

                    class="request-course-image-fallback"

                  >

                    {{ getCourseInitials(requestItem.course?.title) }}

                  </div>

                </div>

                <div class="min-width-0">

                  <h6 class="mb-1 fw-semibold request-mobile-title">

                    {{ requestItem.reference || '-' }}

                  </h6>

                  <span :class="getStatusClass(requestItem.status)">

                    <i :class="`${getStatusIcon(requestItem.status)} me-1`"></i>

                    {{ requestItem.status || 'pending' }}

                  </span>

                </div>

              </div>

              <div class="d-flex gap-2">

                <Link

                  v-if="requestItem.hashid"

                  :href="safeRoute('company-requests.show', requestItem.hashid)"

                  class="btn btn-sm btn-soft-info action-btn"

                  :title="$t('ui-view')"

                >

                  <i class="ri-eye-line"></i>

                </Link>

                <Link

                  v-if="requestItem.hashid && (requestItem.status === 'pending' || canApprove)"

                  :href="safeRoute('company-requests.edit', requestItem.hashid)"

                  class="btn btn-sm btn-soft-primary action-btn"

                  :title="$t('ui-edit')"

                >

                  <i class="ri-pencil-fill"></i>

                </Link>

                <BButton

                  v-if="canApprove && requestItem.status === 'pending'"

                  variant="soft-success"

                  size="sm"

                  class="action-btn"

                  :title="$t('t-approve')"

                  @click="approveRequest(requestItem)"

                >

                  <i class="ri-check-line"></i>

                </BButton>

                <BButton

                  v-if="canApprove && requestItem.status === 'pending'"

                  variant="soft-danger"

                  size="sm"

                  class="action-btn"

                  :title="$t('t-reject')"

                  @click="rejectRequest(requestItem)"

                >

                  <i class="ri-close-line"></i>

                </BButton>

              </div>

            </div>

            <div class="request-mobile-body">

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-building-4-line me-1"></i>

                  {{ $t('ui-company') }}

                </span>

                <span class="company-badge">

                  {{ requestItem.company?.name || '-' }}

                </span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-book-open-line me-1"></i>

                  {{ $t('ui-course') }}

                </span>

                <span class="request-mobile-value">

                  {{ requestItem.course?.title || '-' }}

                </span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-shield-check-line me-1"></i>

                  {{ $t('ui-accreditations') }}

                </span>

                <div

                  v-if="requestItem.accreditations?.length"

                  class="accreditation-badges mobile-accreditations"

                >

                  <span

                    v-for="accreditation in requestItem.accreditations"

                    :key="accreditation.id"

                    :class="getAccreditationClass(accreditation.code)"

                  >

                    {{ accreditation.name }}

                  </span>

                </div>

                <span v-else class="request-mobile-value">-</span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-calendar-event-line me-1"></i>

                  {{ $t('t-training-period') }}

                </span>

                <span class="request-mobile-value">

                  {{ formatTrainingPeriod(requestItem) }}

                </span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-timer-line me-1"></i>

                  {{ $t('ui-validity') }}

                </span>

                <span class="validity-badge">

                  {{ formatValidityDuration(requestItem.duree_validite_formation) }}

                </span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i :class="`${getCertificateVisibilityIcon(requestItem)} me-1`"></i>

                  {{ $t('ui-certificate') }}

                </span>

                <span :class="getCertificateVisibilityClass(requestItem)">

                  {{ getCertificateVisibilityLabel(requestItem) }}

                </span>

              </div>

              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-team-line me-1"></i>

                  {{ $t('ui-learners') }}

                </span>

                <span class="learners-badge">

                  {{ requestItem.learners_count || 0 }}

                </span>

              </div>





              <div class="request-mobile-item">

                <span class="request-mobile-label">

                  <i class="ri-calendar-line me-1"></i>

                  {{ $t('ui-date') }}

                </span>

                <span class="date-badge">

                  {{ formatDate(requestItem.created_at) }}

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

          <div class="empty-request-icon mx-auto">

            <i class="ri-file-list-3-line"></i>

          </div>

          <h5 class="mt-3 mb-1">{{ $t('ui-no-requests-found') }}</h5>

          <p class="text-muted mb-0">

            {{ $t('ui-try-changing-search-keywords-or-company-filter') }}

          </p>

        </div>

        <!-- Pagination -->

        <div

          v-if="filtereData.length > 0"

          class="request-pagination"

        >

          <div class="text-muted request-pagination-text">

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

</template>

<style scoped>

.request-card {

  border-radius: 18px;

  overflow: hidden;

}

/* ================= RESPONSIVE HEADER ================= */
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

.request-header-actions {

  display: flex;

  align-items: center;

  justify-content: flex-end;

  gap: 12px;

  width: 100%;

}

.company-filter-box {

  width: 230px;

  min-width: 210px;

}

.company-filter-select {

  height: 42px;

  border-radius: 12px;

  border-color: #e5e7eb;

  color: #334155;

  font-weight: 600;

}

.company-filter-select:focus {

  border-color: #687cfe !important;

  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14) !important;

}

.request-search-box {

  width: 320px;

  min-width: 260px;

}

.request-search-box .form-control {

  height: 42px;

  border-radius: 12px;

  border-color: #e5e7eb;

}

.request-search-box .form-control:focus {

  border-color: #687cfe !important;

  box-shadow: 0 0 0 0.2rem rgba(104, 124, 254, 0.14) !important;

}

.request-add-btn {

  height: 42px;

  border-radius: 12px;

  white-space: nowrap;

  padding-inline: 18px;

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

.request-table tbody tr:hover {

  background-color: #f8fafc;

}

.sort {

  cursor: pointer;

  user-select: none;

}

.request-course-image-box {

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

.request-course-image {

  width: 100%;

  height: 100%;

  object-fit: cover;

}

.request-course-image-fallback {

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

.request-ref-text,

.request-course-text,

.request-user-text,

.request-mobile-title {

  color: #334155;

  max-width: 240px;

  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;

}

.company-badge,

.learners-badge,

.date-badge,

.badge {

  padding: 7px 12px;

  border-radius: 999px;

  font-size: 12px;

  font-weight: 600;

}

.company-badge,

.learners-badge {

  display: inline-flex;

  align-items: center;

  justify-content: center;

  background: #eef2ff;

  color: #405189;

}

.date-badge {

  display: inline-flex;

  align-items: center;

  justify-content: center;

  background: rgba(41, 156, 219, 0.12);

  color: #299cdb;

  font-weight: 700;

}

.accreditation-badges {

  display: flex;

  flex-wrap: wrap;

  gap: 6px;

  max-width: 210px;

}

.accreditation-badge {

  display: inline-flex;

  align-items: center;

  gap: 5px;

  padding: 6px 10px;

  border-radius: 999px;

  background: #f1f5f9;

  color: #475569;

  font-size: 11px;

  font-weight: 800;

  white-space: nowrap;

}

.accreditation-badge.easa {

  background: rgba(104, 124, 254, 0.13);

  color: #5667d8;

}

.accreditation-badge.dac {

  background: rgba(10, 179, 156, 0.13);

  color: #079884;

}

.validity-badge {

  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding: 7px 10px;

  border-radius: 12px;

  background: rgba(10, 179, 156, 0.1);

  color: #078c79;

  font-size: 11.5px;

  font-weight: 800;

  line-height: 1.35;

  white-space: nowrap;

}

.period-badge {

  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding: 7px 10px;

  border-radius: 12px;

  background: rgba(41, 156, 219, 0.1);

  color: #247fae;

  font-size: 11.5px;

  font-weight: 750;

  line-height: 1.35;

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

.certificate-visibility-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 82px;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 800;
  white-space: nowrap;
}

.certificate-visibility-badge.visible {
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.12);
  border: 1px solid rgba(10, 179, 156, 0.18);
}

.certificate-visibility-badge.hidden {
  color: #f7b84b;
  background: rgba(247, 184, 75, 0.12);
  border: 1px solid rgba(247, 184, 75, 0.2);
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

.mobile-image {

  width: 56px;

  height: 56px;

  min-width: 56px;

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

.mobile-accreditations {

  justify-content: flex-end;

  max-width: 100%;

}

.request-mobile-value {

  color: #334155;

  font-size: 13px;

  font-weight: 500;

  text-align: right;

  word-break: break-word;

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

.pagination .page-link {

  border-radius: 10px;

  margin: 0 3px;

  border: 1px solid #e5e7eb;

}

.pagination .page-item.active .page-link {

  background-color: #687cfe;

  border-color: #687cfe;

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

  .company-filter-box {

    width: 240px;

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

    flex-wrap: wrap;

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

  .company-filter-box,

  .company-filter-select,

  .request-search-box,

  .request-add-btn {

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

  .request-mobile-value {

    text-align: left;

  }

  .mobile-accreditations {

    justify-content: flex-start;

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

  .request-header-title {

    flex-direction: column;

  }

}

</style>