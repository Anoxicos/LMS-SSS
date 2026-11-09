<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

import Lottie from '@/Components/widgets/lottie.vue'
import animationData from '@/Components/widgets/msoeawqm.json'
import animationData1 from '@/Components/widgets/gsqxdxog.json'

import { usePermissions } from '@/composables/permissions'

const { hasPermission } = usePermissions()

const props = defineProps({
  bank: {
    type: Object,
    required: true,
  },
})

const dataLocal = ref([...(props.bank.questions || [])])

const searchQuery = ref('')
const currentPageData = ref(1)
const perPageData = 10

const questionModal = ref(false)
const deleteModal = ref(false)
const dataEdit = ref(false)
const submitted = ref(false)
const selectedQuestion = ref(null)

const direction = ref('asc')
const sortColumn = ref('question')

const importInput = ref(null)
const importDragging = ref(false)
const importFileName = ref('')
const importUploadError = ref('')
const importFailed = ref(false)

const importForm = useForm({
  file: null,
})

const defaultOptions = {
  animationData,
}

const defaultOptions1 = ref({
  animationData: animationData1,
})

const form = useForm({
  name_chapter: '',
  question: '',
  response1: '',
  response2: '',
  response3: '',
  correct_answer: 'response1',
})

watch(
  () => props.bank.questions,
  (newVal) => {
    dataLocal.value = [...(newVal || [])]
    currentPageData.value = 1
  },
  { immediate: true }
)

watch(searchQuery, () => {
  currentPageData.value = 1
})

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const getCorrectAnswerLabel = (question) => {
  if (question.correct_answer_key) {
    if (question.correct_answer_key === 'response1') return t('ui-response-1')
    if (question.correct_answer_key === 'response2') return t('ui-response-2')
    if (question.correct_answer_key === 'response3') return t('ui-response-3')
  }

  if (question.correct_answer === question.response2) return t('ui-response-2')
  if (question.correct_answer === question.response3) return t('ui-response-3')

  return t('ui-response-1')
}

const isCorrectResponse = (question, responseKey) => {
  if (question.correct_answer_key) {
    return question.correct_answer_key === responseKey
  }

  if (responseKey === 'response1') {
    return question.correct_answer === question.response1
  }

  if (responseKey === 'response2') {
    return question.correct_answer === question.response2
  }

  if (responseKey === 'response3') {
    return question.correct_answer === question.response3
  }

  return false
}

const getChapterLabel = (question) => {
  return question.name_chapter || 'Without chapter'
}

const filtereData = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  if (!search) {
    return dataLocal.value
  }

  return dataLocal.value.filter((data) => {
    return (
      String(data.question ?? '').toLowerCase().includes(search) ||
      String(data.response1 ?? '').toLowerCase().includes(search) ||
      String(data.response2 ?? '').toLowerCase().includes(search) ||
      String(data.response3 ?? '').toLowerCase().includes(search) ||
      String(data.correct_answer ?? '').toLowerCase().includes(search) ||
      String(data.name_chapter ?? '').toLowerCase().includes(search)
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

watch(totalPagesData, (total) => {
  if (currentPageData.value > total) {
    currentPageData.value = total || 1
  }
})

const onSort = (column) => {
  sortColumn.value = column
  direction.value = direction.value === 'asc' ? 'desc' : 'asc'

  const sortedArray = [...dataLocal.value]

  sortedArray.sort((a, b) => {
    let valueA = a[column]
    let valueB = b[column]

    if (column === 'name_chapter') {
      valueA = a.name_chapter || ''
      valueB = b.name_chapter || ''
    }

    if (column === 'correct_answer') {
      valueA = getCorrectAnswerLabel(a)
      valueB = getCorrectAnswerLabel(b)
    }

    const finalA = String(valueA ?? '').toLowerCase()
    const finalB = String(valueB ?? '').toLowerCase()

    const result = finalA < finalB ? -1 : finalA > finalB ? 1 : 0

    return direction.value === 'asc' ? result : -result
  })

  dataLocal.value = sortedArray
}

const resetForm = () => {
  form.reset()
  form.clearErrors()

  form.name_chapter = ''
  form.question = ''
  form.response1 = ''
  form.response2 = ''
  form.response3 = ''
  form.correct_answer = 'response1'
}

const openCreateQuestion = () => {
  dataEdit.value = false
  selectedQuestion.value = null
  submitted.value = false
  resetForm()
  questionModal.value = true
}

const openEditQuestion = (question) => {
  dataEdit.value = true
  selectedQuestion.value = question
  submitted.value = false

  form.clearErrors()

  form.name_chapter = question.name_chapter || ''
  form.question = question.question || ''
  form.response1 = question.response1 || ''
  form.response2 = question.response2 || ''
  form.response3 = question.response3 || ''

  if (question.correct_answer_key) {
    form.correct_answer = question.correct_answer_key
  } else if (question.correct_answer === question.response2) {
    form.correct_answer = 'response2'
  } else if (question.correct_answer === question.response3) {
    form.correct_answer = 'response3'
  } else {
    form.correct_answer = 'response1'
  }

  questionModal.value = true
}

const addData = () => {
  submitted.value = true

  form.post(route('banks.questions.store', props.bank.id), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-question-added-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
      submitted.value = false
      questionModal.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-please-check-the-question-information'),
      })
    },
  })
}

const updateData = () => {
  if (!selectedQuestion.value) return

  submitted.value = true

  form.post(route('banks.questions.update', [props.bank.id, selectedQuestion.value.id]), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-question-updated-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      resetForm()
      selectedQuestion.value = null
      submitted.value = false
      questionModal.value = false
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('ui-please-check-the-question-information'),
      })
    },
  })
}

const handleSubmit = () => {
  dataEdit.value ? updateData() : addData()
}

const deleteData = (question) => {
  selectedQuestion.value = question
  deleteModal.value = true
}

const handleDeleteData = () => {
  if (!selectedQuestion.value) return

  const formDelete = useForm({})

  formDelete.delete(route('banks.questions.delete', [props.bank.id, selectedQuestion.value.id]), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-deleted'),
        text: t('ui-question-deleted-successfully'),
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
      selectedQuestion.value = null
    },
  })
}

const openImportInput = () => {
  importInput.value?.click()
}

const handleImportFile = (event) => {
  const file = event.target.files?.[0] || event.dataTransfer?.files?.[0]
  if (event.target?.value !== undefined) event.target.value = ''

  if (!file) return

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['xlsx', 'xls', 'csv'].includes(ext)) {
    importUploadError.value = 'Choose an XLSX, XLS, or CSV file.'
    importFailed.value = false
    return
  }

  importForm.file = file
  importFileName.value = file.name
  importUploadError.value = ''
  importFailed.value = false

  startImport()
}

const startImport = () => {
  if (!importForm.file) return
  importFailed.value = false
  importUploadError.value = ''
  importForm.post(route('banks.questions.import', props.bank.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-imported'),
        text: t('ui-questions-imported-successfully'),
        timer: 2000,
        showConfirmButton: false,
      })

      importForm.reset()
      importForm.clearErrors()
      importFileName.value = ''
      importFailed.value = false

      if (importInput.value) {
        importInput.value.value = ''
      }
    },
    onError: () => {
      importFailed.value = true
      importUploadError.value = 'Import failed. Check the file format and content, then retry.'
      Swal.fire({
        icon: 'error',
        title: t('ui-import-error'),
        text: t('ui-please-check-your-excel-file'),
      })

      if (importInput.value) {
        importInput.value.value = ''
      }
    },
  })
}

const handleImportDrop = event => {
  importDragging.value = false
  handleImportFile(event)
}

const exportQuestions = () => {
  window.location.href = route('banks.questions.export', props.bank.id)
}
</script>

<template>
  <Layout>
    <Head :title="$t('ui-questions-bank', { bank: bank.title })" />

    <PageHeader
      :title="$t('ui-bank-questions')"
      :page-title="$t('ui-question-banks')"
    />

    <div>
      <BCard class="company-card border-0 shadow-sm">
        <BCardHeader class="company-card-header border-0">
          <BRow class="g-3 align-items-center company-header-row">
            <BCol
              xl="5"
              lg="12"
              md="12"
              cols="12"
            >
              <div class="company-header-title">
                <div class="company-header-icon question-header-icon">
                  <i class="ri-questionnaire-line"></i>
                </div>

                <div>
                  <h5 class="mb-1 fw-semibold">
                    {{ bank.title }}
                  </h5>

                  <p class="text-muted mb-0">
                    {{ $t('ui-manage-questions-for-bank') }}
                    <strong>{{ bank.ref }}</strong>.
                  </p>
                </div>
              </div>
            </BCol>

            <BCol
              xl="7"
              lg="12"
              md="12"
              cols="12"
            >
              <div
                class="company-header-actions media-drop-target"
                :class="{ 'is-dragging': importDragging }"
                @dragenter.prevent="importDragging = true"
                @dragover.prevent="importDragging = true"
                @dragleave.prevent="importDragging = false"
                @drop.prevent="handleImportDrop"
              >
                <div class="search-box company-search-box">
                  <input
                    v-model="searchQuery"
                    type="text"
                    class="form-control"
                    :placeholder="$t('ui-search-question')"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>

                <Link
                  :href="route('banks.index')"
                  class="btn btn-light add-btn company-add-btn"
                >
                  <i class="ri-arrow-left-line align-bottom me-1"></i>
                  {{ $t('ui-banks') }}
                </Link>

                <BButton
                  v-if="hasPermission('create question')"
                  type="button"
                  variant="soft-success"
                  class="add-btn company-add-btn"
                  @click="exportQuestions"
                >
                  <i class="ri-download-2-line align-bottom me-1"></i>
                  {{ $t('ui-export') }}
                </BButton>

                <BButton
                  v-if="hasPermission('create question')"
                  type="button"
                  variant="soft-info"
                  class="add-btn company-add-btn"
                  @click="openImportInput"
                >
                  <i class="ri-upload-2-line align-bottom me-1"></i>
                  {{ $t('ui-import') }}
                </BButton>

                <BButton
                  v-if="hasPermission('create question')"
                  type="button"
                  variant="primary"
                  class="add-btn company-add-btn"
                  @click="openCreateQuestion"
                >
                  <i class="ri-add-line align-bottom me-1"></i>
                  {{ $t('ui-add-question') }}
                </BButton>

                <input
                  ref="importInput"
                  type="file"
                  class="d-none"
                  accept=".xlsx,.xls,.csv"
                  @change="handleImportFile"
                />
              </div>
              <div v-if="importFileName" class="small mt-2" role="status">
                <i class="ri-check-line text-success" aria-hidden="true"></i> {{ importFileName }}
                <span v-if="importForm.processing"> · {{ importForm.progress?.percentage || 0 }}%</span>
                <button v-if="importFailed" type="button" class="btn btn-link btn-sm p-0 ms-2" @click="startImport">Retry</button>
              </div>
              <div v-if="importUploadError" class="small text-danger mt-1" role="alert">
                <i class="ri-error-warning-line" aria-hidden="true"></i> {{ importUploadError }}
              </div>
              <div v-if="importForm.processing && importForm.progress" class="progress mt-2" role="progressbar" :aria-valuenow="importForm.progress.percentage" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" :style="{ width: `${importForm.progress.percentage}%` }"></div>
              </div>
            </BCol>
          </BRow>
        </BCardHeader>

        <BCardBody>
          <!-- Desktop Table -->
          <div class="question-table-wrapper d-none d-lg-block">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 professional-question-table">
                <thead>
                  <tr>
                    <th class="question-id-col">
                      {{ $t('ui-id') }}
                    </th>

                    <th
                      class="sort"
                      @click="onSort('name_chapter')"
                    >
                      {{ $t('ui-chapter') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th
                      class="question-main-col sort"
                      @click="onSort('question')"
                    >
                      {{ $t('ui-question') }}
                      <i class="ri-arrow-up-down-line ms-1"></i>
                    </th>

                    <th class="text-center">
                      {{ $t('ui-created') }}
                    </th>

                    <th class="text-center actions-col">
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
                      <span class="question-id-badge">
                        #{{ data.id }}
                      </span>
                    </td>

                    <td>
                      <span class="chapter-badge">
                        <i class="ri-list-check-2 me-1"></i>
                        {{ getChapterLabel(data) }}
                      </span>
                    </td>

                    <td>
                      <div class="question-content">
                        <p class="question-text mb-2">
                          {{ data.question || '-' }}
                        </p>

                        <div class="answers-inline">
                          <span
                            class="answer-chip"
                            :class="{ correct: isCorrectResponse(data, 'response1') }"
                          >
                            <strong>{{ $t('ui-a') }}</strong>
                            {{ data.response1 || '-' }}
                          </span>

                          <span
                            class="answer-chip"
                            :class="{ correct: isCorrectResponse(data, 'response2') }"
                          >
                            <strong>{{ $t('ui-b') }}</strong>
                            {{ data.response2 || '-' }}
                          </span>

                          <span
                            class="answer-chip"
                            :class="{ correct: isCorrectResponse(data, 'response3') }"
                          >
                            <strong>{{ $t('ui-c') }}</strong>
                            {{ data.response3 || '-' }}
                          </span>
                        </div>
                      </div>
                    </td>

                    <td class="text-center">
                      <span class="created-date">
                        {{ formatDate(data.created_at) }}
                      </span>
                    </td>

                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <BButton
                          v-if="hasPermission('update question')"
                          variant="soft-primary"
                          size="sm"
                          class="question-action-btn"
                          :title="$t('ui-edit')"
                          @click="openEditQuestion(data)"
                        >
                          <i class="ri-pencil-fill"></i>
                        </BButton>

                        <BButton
                          v-if="hasPermission('delete question')"
                          variant="soft-danger"
                          size="sm"
                          class="question-action-btn"
                          :title="$t('ui-delete')"
                          @click="deleteData(data)"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </BButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Cards -->
          <div class="question-mobile-list d-lg-none">
            <div
              v-for="data in paginatedData"
              :key="data.id"
              class="question-pro-mobile-card"
            >
              <div class="question-pro-mobile-header">
                <div class="d-flex align-items-start gap-3">
                  <div class="mobile-question-icon">
                    <i class="ri-question-line"></i>
                  </div>

                  <div class="min-width-0">
                    <div class="d-flex flex-wrap gap-2">
                      <span class="question-id-badge">
                        #{{ data.id }}
                      </span>

                      <span class="chapter-badge">
                        {{ getChapterLabel(data) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <BButton
                    v-if="hasPermission('update question')"
                    variant="soft-primary"
                    size="sm"
                    class="question-action-btn"
                    :title="$t('ui-edit')"
                    @click="openEditQuestion(data)"
                  >
                    <i class="ri-pencil-fill"></i>
                  </BButton>

                  <BButton
                    v-if="hasPermission('delete question')"
                    variant="soft-danger"
                    size="sm"
                    class="question-action-btn"
                    :title="$t('ui-delete')"
                    @click="deleteData(data)"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </BButton>
                </div>
              </div>

              <div class="question-pro-mobile-body">
                <p class="question-text mb-0">
                  {{ data.question || '-' }}
                </p>

                <div class="answers-inline mobile-answers">
                  <span
                    class="answer-chip"
                    :class="{ correct: isCorrectResponse(data, 'response1') }"
                  >
                    <strong>{{ $t('ui-a') }}</strong>
                    {{ data.response1 || '-' }}
                  </span>

                  <span
                    class="answer-chip"
                    :class="{ correct: isCorrectResponse(data, 'response2') }"
                  >
                    <strong>{{ $t('ui-b') }}</strong>
                    {{ data.response2 || '-' }}
                  </span>

                  <span
                    class="answer-chip"
                    :class="{ correct: isCorrectResponse(data, 'response3') }"
                  >
                    <strong>{{ $t('ui-c') }}</strong>
                    {{ data.response3 || '-' }}
                  </span>
                </div>

                <div class="mobile-info-row">
                  <span>
                    <i class="ri-calendar-line me-1"></i>
                    {{ $t('ui-created') }}
                  </span>

                  <strong>
                    {{ formatDate(data.created_at) }}
                  </strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty -->
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

            <h5 class="mt-3 mb-1">
              {{ $t('ui-no-questions-found') }}
            </h5>

            <p class="text-muted mb-0">
              {{ $t('ui-add-the-first-question-to-this-bank') }}
            </p>
          </div>

          <!-- Pagination -->
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

      <!-- Question Modal -->
      <BModal
        v-model="questionModal"
        hide-footer
        title-class="exampleModalLabel"
        :title="dataEdit ? 'Edit Question' : 'Add Question'"
        header-class="bg-light p-3"
        size="lg"
        class="v-modal-custom question-modal"
        centered
      >
        <BForm
          class="tablelist-form"
          autocomplete="off"
          @submit.prevent="handleSubmit"
        >
          <div class="question-modal-hero mb-4">
            <div class="question-modal-icon">
              <i class="ri-questionnaire-line"></i>
            </div>

            <div>
              <h6 class="mb-1">
                {{ dataEdit ? 'Update Question' : 'Create Question' }}
              </h6>

              <p class="text-muted mb-0 small">
                {{ $t('ui-add-question-answers-and-select-the-correct-response') }}
              </p>
            </div>
          </div>

          <BRow class="g-3">
            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-chapter-name') }}
              </label>

              <input
                v-model="form.name_chapter"
                type="text"
                class="form-control"
                :placeholder="$t('ui-enter-chapter-name')"
              />

              <div
                v-if="form.errors.name_chapter"
                class="text-danger small mt-1"
              >
                {{ form.errors.name_chapter }}
              </div>
            </BCol>

            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-question') }} <span class="text-danger">*</span>
              </label>

              <textarea
                v-model="form.question"
                class="form-control"
                rows="3"
                :placeholder="$t('ui-enter-question')"
                :class="{ 'is-invalid': submitted && !form.question }"
              ></textarea>

              <div class="invalid-feedback">
                {{ $t('ui-please-enter-the-question') }}
              </div>

              <div
                v-if="form.errors.question"
                class="text-danger small mt-1"
              >
                {{ form.errors.question }}
              </div>
            </BCol>

            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-response-1') }} <span class="text-danger">*</span>
              </label>

              <textarea
                v-model="form.response1"
                class="form-control"
                rows="2"
                :placeholder="$t('ui-enter-response-1')"
                :class="{ 'is-invalid': submitted && !form.response1 }"
              ></textarea>

              <div class="invalid-feedback">
                {{ $t('ui-please-enter-response-1') }}
              </div>

              <div
                v-if="form.errors.response1"
                class="text-danger small mt-1"
              >
                {{ form.errors.response1 }}
              </div>
            </BCol>

            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-response-2') }} <span class="text-danger">*</span>
              </label>

              <textarea
                v-model="form.response2"
                class="form-control"
                rows="2"
                :placeholder="$t('ui-enter-response-2')"
                :class="{ 'is-invalid': submitted && !form.response2 }"
              ></textarea>

              <div class="invalid-feedback">
                {{ $t('ui-please-enter-response-2') }}
              </div>

              <div
                v-if="form.errors.response2"
                class="text-danger small mt-1"
              >
                {{ form.errors.response2 }}
              </div>
            </BCol>

            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-response-3') }} <span class="text-danger">*</span>
              </label>

              <textarea
                v-model="form.response3"
                class="form-control"
                rows="2"
                :placeholder="$t('ui-enter-response-3')"
                :class="{ 'is-invalid': submitted && !form.response3 }"
              ></textarea>

              <div class="invalid-feedback">
                {{ $t('ui-please-enter-response-3') }}
              </div>

              <div
                v-if="form.errors.response3"
                class="text-danger small mt-1"
              >
                {{ form.errors.response3 }}
              </div>
            </BCol>

            <BCol lg="12">
              <label class="form-label">
                {{ $t('ui-correct-answer') }} <span class="text-danger">*</span>
              </label>

              <div class="correct-answer-box">
                <label
                  class="correct-answer-option"
                  :class="{ active: form.correct_answer === 'response1' }"
                >
                  <input
                    v-model="form.correct_answer"
                    type="radio"
                    value="response1"
                  />
                  {{ $t('ui-response-1') }}
                </label>

                <label
                  class="correct-answer-option"
                  :class="{ active: form.correct_answer === 'response2' }"
                >
                  <input
                    v-model="form.correct_answer"
                    type="radio"
                    value="response2"
                  />
                  {{ $t('ui-response-2') }}
                </label>

                <label
                  class="correct-answer-option"
                  :class="{ active: form.correct_answer === 'response3' }"
                >
                  <input
                    v-model="form.correct_answer"
                    type="radio"
                    value="response3"
                  />
                  {{ $t('ui-response-3') }}
                </label>
              </div>

              <div
                v-if="form.errors.correct_answer"
                class="text-danger small mt-1"
              >
                {{ form.errors.correct_answer }}
              </div>
            </BCol>
          </BRow>

          <div class="modal-footer v-modal-footer mt-4">
            <div class="hstack gap-2 justify-content-end">
              <BButton
                type="button"
                variant="light"
                @click="questionModal = false"
              >
                {{ $t('ui-close') }}
              </BButton>

              <BButton
                type="submit"
                :disabled="form.processing"
                variant="success"
              >
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                {{ dataEdit ? 'Update' : 'Add' }}
              </BButton>
            </div>
          </div>
        </BForm>
      </BModal>

      <!-- Delete Modal -->
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
            <h4>
              {{ $t('ui-are-you-sure') }}
            </h4>

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
    </div>
  </Layout>
</template>

<style scoped>
.media-drop-target { transition: border-color 140ms ease, background-color 140ms ease; }
.media-drop-target.is-dragging { outline: 2px dashed var(--signal); outline-offset: 2px; background: rgba(31, 92, 255, 0.08); }

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

.question-header-icon {
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.28);
}

.company-header-actions {
  display: flex;
  flex-wrap: wrap;
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
  padding-inline: 16px;
}

.question-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.professional-question-table {
  margin-bottom: 0;
}

.professional-question-table thead,
.professional-question-table thead tr,
.professional-question-table thead th {
  background-color: #687cfe !important;
  background-image: none !important;
}

.professional-question-table thead th {
  padding: 16px !important;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6 !important;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb !important;
  white-space: nowrap;
  vertical-align: middle;
}

.professional-question-table thead th i {
  color: #f2f4f6 !important;
  opacity: 0.9;
}

.professional-question-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.professional-question-table tbody tr:hover {
  background-color: #f8fafc;
}

.question-id-col {
  width: 90px;
}

.question-main-col {
  min-width: 620px;
}

.actions-col {
  width: 110px;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.question-id-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 11px;
  border-radius: 999px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  color: #0f172a;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.chapter-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 8px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.question-content {
  min-width: 0;
}

.question-text {
  color: #0f172a;
  font-size: 13px;
  line-height: 1.45;
}

.answers-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.answer-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  max-width: 100%;
  padding: 7px 10px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  color: #475569;
  font-size: 12px;
  line-height: 1.3;
}

.answer-chip strong {
  color: #334155;
}

.answer-chip.correct {
  background: rgba(10, 179, 156, 0.1);
  border-color: rgba(10, 179, 156, 0.35);
  color: #008060;
  font-weight: 700;
}

.answer-chip.correct strong {
  color: #008060;
}

.created-date {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.question-action-btn {
  width: 34px;
  height: 34px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.question-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.question-pro-mobile-card {
  border: 1px solid #e8edf5;
  border-radius: 18px;
  background: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.question-pro-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #eef2f7;
}

.mobile-question-icon {
  width: 50px;
  height: 50px;
  min-width: 50px;
  border-radius: 14px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.question-pro-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.mobile-answers {
  flex-direction: column;
}

.mobile-answers .answer-chip {
  width: 100%;
}

.mobile-info-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.mobile-info-row span {
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.mobile-info-row strong {
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
  text-align: right;
}

.question-modal :deep(.modal-content) {
  border: none;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
}

.question-modal-hero {
  display: flex;
  gap: 14px;
  align-items: center;
  padding: 18px;
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  border: 1px solid #eef2f7;
}

.question-modal-icon {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.form-label {
  font-weight: 600;
  color: #334155;
}

.form-control,
.form-select {
  border-radius: 10px;
  min-height: 42px;
}

.form-control:focus,
.form-select:focus {
  border-color: #0ab39c;
  box-shadow: 0 0 0 0.18rem rgba(10, 179, 156, 0.16);
}

.correct-answer-box {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.correct-answer-option {
  border: 1px solid #edf2f7;
  border-radius: 14px;
  padding: 12px 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  transition: all 0.2s ease;
}

.correct-answer-option.active {
  border-color: #0ab39c;
  background: rgba(10, 179, 156, 0.1);
  color: #0ab39c;
  font-weight: 800;
}

.v-modal-footer {
  border-top: 1px solid #eef2f7;
  padding-top: 18px;
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

@media (max-width: 1399.98px) {
  .question-main-col {
    min-width: 520px;
  }

  .answer-chip {
    font-size: 11px;
  }
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

  .question-pro-mobile-header {
    flex-direction: column;
  }

  .question-pro-mobile-header > .d-flex:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  .mobile-info-row {
    flex-direction: column;
    gap: 4px;
  }

  .mobile-info-row strong {
    text-align: left;
  }
}

@media (max-width: 575.98px) {
  .company-card {
    border-radius: 14px;
  }

  .company-card-header {
    padding: 16px;
  }

  .question-pro-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .correct-answer-box {
    flex-direction: column;
  }

  .correct-answer-option {
    width: 100%;
  }
}
</style>
