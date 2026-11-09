<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { computed, ref, watch } from 'vue'
import { formatCourseLabel } from '@/composables/course-labels'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
  tags: {
    type: Array,
    default: () => [],
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
})

const imagePreview = ref(null)
const syllabusInput = ref(null)
const scormDragging = ref(false)
const scormUploadError = ref('')
const imageDragging = ref(false)
const syllabusDragging = ref(false)
const imageUploadError = ref('')
const syllabusUploadError = ref('')

const methodOptions = computed(() => [
  { value: 'Online e-learning (LMS)', label: t('ui-online-e-learning-lms') },
  { value: 'Virtual classroom', label: t('ui-virtual-classroom') },
  { value: 'On-site classroom training', label: t('ui-on-site-classroom-training') },
  { value: 'Blended learning', label: t('ui-blended-learning') },
  { value: 'Self-study', label: t('ui-self-study') },
])

watch(
  () => props.form.image_url,
  (newUrl) => {
    if (!props.form.image) {
      imagePreview.value = newUrl || null
    }
  },
  { immediate: true }
)

const selectedTags = computed(() => {
  return Array.isArray(props.form.tags) ? props.form.tags : []
})

const syllabusFileName = computed(() => {
  if (props.form.syllabus?.name) {
    return props.form.syllabus.name
  }

  return props.form.syllabus_name || 'Course syllabus.pdf'
})

const hasSyllabus = computed(() => {
  return Boolean(props.form.syllabus || props.form.syllabus_url)
})

const uploadFileNames = computed(() => {
  return [props.form.image?.name, props.form.syllabus?.name, props.form.scorm_file?.name]
    .filter(Boolean)
    .join(', ')
})

const selectedScormFileName = computed(() => {
  if (props.form.scorm_file) {
    return props.form.scorm_file.name
  }

  if (props.form.scorm_zip_path) {
    return 'Current SCORM package uploaded'
  }

  return 'Choose SCORM ZIP'
})

const toggleTag = (tagId) => {
  if (!Array.isArray(props.form.tags)) {
    props.form.tags = []
  }

  if (props.form.tags.includes(tagId)) {
    props.form.tags = props.form.tags.filter((id) => id !== tagId)
  } else {
    props.form.tags.push(tagId)
  }
}

const handleImageChange = (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''

  if (!file) return

  const extension = file.name.split('.').pop()?.toLowerCase()
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(extension) || file.size > 2 * 1024 * 1024) {
    imageUploadError.value = 'Choose a JPG, PNG, or WebP image up to 2 MB.'
    return
  }

  imageUploadError.value = ''
  if (imagePreview.value?.startsWith('blob:')) URL.revokeObjectURL(imagePreview.value)
  props.form.image = file
  props.form.remove_image = false
  imagePreview.value = URL.createObjectURL(file)
}

const handleImageDrop = event => {
  imageDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handleImageChange({ target: { files: [file], value: '' } })
}

const removeImage = () => {
  props.form.image = null
  props.form.remove_image = true
  props.form.image_url = null
  imagePreview.value = null
}

const openSyllabusInput = () => {
  syllabusInput.value?.click()
}

const handleSyllabusChange = (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''

  if (!file) return

  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    syllabusUploadError.value = 'Choose a PDF file.'
    return
  }

  if (file.size > 10 * 1024 * 1024) {
    syllabusUploadError.value = 'The PDF must be 10 MB or smaller.'
    return
  }

  syllabusUploadError.value = ''
  props.form.syllabus = file
  props.form.remove_syllabus = false
}

const handleSyllabusDrop = event => {
  syllabusDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handleSyllabusChange({ target: { files: [file], value: '' } })
}

const removeSyllabus = () => {
  props.form.syllabus = null
  props.form.syllabus_url = null
  props.form.syllabus_name = null
  props.form.remove_syllabus = true

  if (syllabusInput.value) {
    syllabusInput.value.value = ''
  }
}

const handleScormFileChange = (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''

  if (!file) return

  if (!file.name.toLowerCase().endsWith('.zip')) {
    scormUploadError.value = 'Choose a ZIP package.'
    return
  }

  if (file.size > 500 * 1024 * 1024) {
    scormUploadError.value = 'The ZIP package must be 500 MB or smaller.'
    return
  }

  scormUploadError.value = ''
  props.form.scorm_file = file
}

const handleScormDrop = event => {
  scormDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handleScormFileChange({ target: { files: [file], value: '' } })
}
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>{{ $t('ui-scorm-course-information') }}</h5>
      <p>{{ $t('ui-fill-in-the-course-details-and-upload-a-valid-scorm-package') }}</p>
    </div>

    <BRow class="g-3">
      <BCol xl="8">
        <BRow class="g-3">
          <BCol lg="3">
            <label class="form-label">
              {{ $t('ui-reference') }} <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-hashtag input-icon"></i>

              <input
                v-model="form.ref"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.ref }"
                :placeholder="$t('ui-example-ewis-001')"
              />
            </div>

            <div v-if="form.errors.ref" class="invalid-feedback d-block">
              {{ form.errors.ref }}
            </div>
          </BCol>

          <BCol lg="6">
            <label class="form-label">
              {{ $t('ui-title') }} <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-book-open-line input-icon"></i>

              <input
                v-model="form.title"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.title }"
                placeholder="Enter SCORM course title"
              />
            </div>

            <div v-if="form.errors.title" class="invalid-feedback d-block">
              {{ form.errors.title }}
            </div>
          </BCol>

          <BCol lg="3">
            <label class="form-label">
              {{ $t('ui-category') }} <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.category_id"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.category_id }"
            >
              <option value="">{{ $t('ui-select-category') }}</option>

              <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>

            <div v-if="form.errors.category_id" class="invalid-feedback d-block">
              {{ form.errors.category_id }}
            </div>
          </BCol>

          <BCol cols="12">
            <label class="form-label">{{ $t('ui-description') }}</label>

            <textarea
              v-model="form.description"
              rows="4"
              class="form-control modern-textarea"
              :class="{ 'is-invalid': form.errors.description }"
              placeholder="Enter SCORM course description"
            ></textarea>

            <div v-if="form.errors.description" class="invalid-feedback d-block">
              {{ form.errors.description }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              {{ $t('ui-type') }} <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.type"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.type }"
            >
              <option value="">{{ $t('ui-select-type') }}</option>
              <option value="free">{{ $t('ui-free') }}</option>
              <option value="paid">{{ $t('ui-paid') }}</option>
              <option value="private">{{ $t('ui-private') }}</option>
            </select>

            <div v-if="form.errors.type" class="invalid-feedback d-block">
              {{ form.errors.type }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              {{ $t('ui-training-type') }} <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.training_type"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.training_type }"
            >
              <option value="">{{ $t('ui-select-training-type') }}</option>
              <option value="online">{{ $t('ui-online') }}</option>
              <option value="onsite">{{ $t('ui-onsite') }}</option>
              <option value="hybrid">{{ $t('ui-hybrid') }}</option>
            </select>

            <div v-if="form.errors.training_type" class="invalid-feedback d-block">
              {{ form.errors.training_type }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              {{ $t('ui-method') }} <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.method"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.method }"
            >
              <option value="" disabled>{{ $t('ui-select-training-method') }}</option>

              <option
                v-for="methodOption in methodOptions"
                :key="methodOption.value"
                :value="methodOption.value"
              >
                {{ methodOption.label }}
              </option>
            </select>

            <div v-if="form.errors.method" class="invalid-feedback d-block">
              {{ form.errors.method }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              {{ $t('ui-duration-hours') }} <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-time-line input-icon"></i>

              <input
                v-model="form.duration_hours"
                type="number"
                min="0.01"
                step="0.01"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.duration_hours }"
                :placeholder="$t('ui-example-12')"
              />
            </div>

            <div v-if="form.errors.duration_hours" class="invalid-feedback d-block">
              {{ form.errors.duration_hours }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              {{ $t('ui-passing-score') }} <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-percent-line input-icon"></i>

              <input
                v-model="form.passing_score"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.passing_score }"
                :placeholder="$t('ui-example-80')"
              />
            </div>

            <div v-if="form.errors.passing_score" class="invalid-feedback d-block">
              {{ form.errors.passing_score }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">{{ $t('ui-course-type') }}</label>

            <select
              v-model="form.skill_level"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.skill_level }"
            >
              <option value="" disabled>{{ $t('ui-select-a-training-type') }}</option>
              <option value="initial">{{ $t('ui-initial') }}</option>
              <option value="recurrent">{{ $t('ui-recurrent') }}</option>
            </select>

            <div v-if="form.errors.skill_level" class="invalid-feedback d-block">
              {{ form.errors.skill_level }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">{{ $t('ui-course-focus') }}</label>

            <div class="input-icon-box">
              <i class="ri-focus-3-line input-icon"></i>

              <input
                v-model="form.course_focus"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.course_focus }"
                :placeholder="$t('ui-example-safety-training')"
              />
            </div>

            <div v-if="form.errors.course_focus" class="invalid-feedback d-block">
              {{ form.errors.course_focus }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">{{ $t('ui-language') }}</label>

            <select
              v-model="form.language"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.language }"
            >
              <option value="">{{ $t('ui-select-language') }}</option>
              <option value="English">{{ $t('ui-english') }}</option>
              <option value="French">{{ $t('ui-french') }}</option>
              <option value="Arabic">{{ $t('ui-arabic') }}</option>
            </select>

            <div v-if="form.errors.language" class="invalid-feedback d-block">
              {{ form.errors.language }}
            </div>
          </BCol>

          <BCol md="2" v-if="form.type === 'paid'">
            <label class="form-label">{{ $t('ui-price') }}</label>

            <input
              v-model="form.price"
              type="number"
              min="0"
              step="0.01"
              class="form-control modern-input"
              :class="{ 'is-invalid': form.errors.price }"
              placeholder="0.00"
            />

            <div v-if="form.errors.price" class="invalid-feedback d-block">
              {{ form.errors.price }}
            </div>
          </BCol>

          <BCol md="2" v-if="form.type === 'paid'">
            <label class="form-label">{{ $t('ui-currency') }}</label>

            <select
              v-model="form.currency"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.currency }"
            >
              <option value="MAD">MAD</option>
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
            </select>

            <div v-if="form.errors.currency" class="invalid-feedback d-block">
              {{ form.errors.currency }}
            </div>
          </BCol>

          <BCol cols="12" v-if="tags.length">
            <label class="form-label">{{ $t('ui-tags') }}</label>

            <div class="tags-box">
              <button
                v-for="tag in tags"
                :key="tag.id"
                type="button"
                class="tag-btn"
                :class="{ active: selectedTags.includes(tag.id) }"
                @click="toggleTag(tag.id)"
              >
                <i class="ri-price-tag-3-line me-1"></i>
                {{ tag.name }}
              </button>
            </div>

            <div v-if="form.errors.tags" class="invalid-feedback d-block">
              {{ form.errors.tags }}
            </div>
          </BCol>

          <BCol cols="12">
            <div class="publish-card">
              <div>
                <h6 class="mb-1">{{ $t('ui-publication-status') }}</h6>
                <p class="text-muted mb-0">
                  {{
                    form.is_published
                      ? 'This SCORM course will be published.'
                      : 'This SCORM course will be saved as draft.'
                  }}
                </p>
              </div>

              <div class="form-check form-switch">
                <input
                  v-model="form.is_published"
                  class="form-check-input"
                  type="checkbox"
                  id="isPublished"
                />

                <label class="form-check-label" for="isPublished">
                  {{ form.is_published ? $t('ui-published') : $t('ui-draft') }}
                </label>
              </div>
            </div>

            <div v-if="form.errors.is_published" class="invalid-feedback d-block">
              {{ form.errors.is_published }}
            </div>
          </BCol>

          <BCol cols="12">
            <div class="voice-over-card">
              <div class="voice-over-header mb-3">
                <div>
                  <h6 class="mb-1">
                    <i class="ri-code-box-line me-1"></i>
                    {{ $t('ui-scorm-package') }}
                  </h6>

                  <p class="text-muted mb-0">
                    {{ isEditing && form.scorm_zip_path ? 'Keep the existing package or upload a replacement ZIP.' : 'Upload a SCORM ZIP package containing imsmanifest.xml.' }}
                  </p>
                </div>

                <span class="mini-badge warning">
                  {{ isEditing && form.scorm_zip_path ? 'Optional replacement' : 'Required' }}
                </span>
              </div>

              <div
                class="file-upload-card media-drop-target"
                :class="{ 'is-dragging': scormDragging }"
                @dragenter.prevent="scormDragging = true"
                @dragover.prevent="scormDragging = true"
                @dragleave.prevent="scormDragging = false"
                @drop.prevent="handleScormDrop"
              >
                <div class="file-upload-icon">
                  <i class="ri-upload-cloud-2-line"></i>
                </div>

                <div class="flex-grow-1 min-width-0">
                  <label for="scormFileInput" class="file-upload-title">
                    {{ selectedScormFileName }}
                  </label>

                  <p class="text-muted mb-0">
                    ZIP · up to 500 MB · include imsmanifest.xml
                  </p>
                  <p v-if="form.scorm_file" class="small text-muted mb-0">
                    {{ (form.scorm_file.size / (1024 * 1024)).toFixed(1) }} MB selected
                  </p>
                </div>

                <label
                  for="scormFileInput"
                  class="btn btn-soft-primary btn-sm file-upload-btn mb-0"
                >
                  {{ $t('ui-browse') }}
                </label>

                <input
                  id="scormFileInput"
                  type="file"
                  class="d-none"
                  accept=".zip"
                  @change="handleScormFileChange"
                />
              </div>

              <div v-if="scormUploadError" class="text-danger small mt-2" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ scormUploadError }}</div>

              <div v-if="form.scorm_title || form.scorm_version" class="mt-3">
                <div class="publish-card">
                  <div>
                    <h6 class="mb-1">
                      {{ form.scorm_title || 'SCORM Package' }}
                    </h6>

                    <p class="text-muted mb-0">
                      {{ form.scorm_entry_file || 'Launch file detected after upload.' }}
                    </p>
                  </div>

                  <span class="mini-badge info">
                    {{ form.scorm_version || 'SCORM' }}
                  </span>
                </div>
              </div>

              <div v-if="form.errors.scorm_file" class="invalid-feedback d-block">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                {{ form.errors.scorm_file }}
              </div>

              <div v-if="form.processing && form.progress" class="upload-progress mt-3" role="status" aria-live="polite">
                <div class="d-flex justify-content-between small mb-1">
                  <span>{{ uploadFileNames || selectedScormFileName }}</span><strong>{{ form.progress.percentage }}%</strong>
                </div>
                <div class="progress" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar" :style="{ width: `${form.progress.percentage}%` }"></div>
                </div>
              </div>

              <div v-if="form.errors.error" class="invalid-feedback d-block">
                {{ form.errors.error }}
              </div>
            </div>
          </BCol>
        </BRow>
      </BCol>

      <BCol xl="4">
        <div class="builder-side-form h-100">
          <div class="course-image-card mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
              <label class="form-label mb-0">{{ $t('ui-course-syllabus') }}</label>

              <span class="badge bg-danger-subtle text-danger syllabus-type-badge">
                {{ $t('ui-pdf') }}
              </span>
            </div>

            <input
              ref="syllabusInput"
              type="file"
              class="d-none"
              accept="application/pdf,.pdf"
              @change="handleSyllabusChange"
            />

            <div v-if="hasSyllabus" class="syllabus-file-box mb-3 media-drop-target" :class="{ 'is-dragging': syllabusDragging }" @dragenter.prevent="syllabusDragging = true" @dragover.prevent="syllabusDragging = true" @dragleave.prevent="syllabusDragging = false" @drop.prevent="handleSyllabusDrop">
              <div class="syllabus-file-icon">
                <i class="ri-file-pdf-2-line"></i>
              </div>

              <div class="syllabus-file-content">
                <strong :title="syllabusFileName">
                  {{ syllabusFileName }}
                </strong>

                <small>
                  <i v-if="form.syllabus" class="ri-check-line text-success" aria-hidden="true"></i>
                  {{ form.syllabus ? 'New PDF selected' : 'Current syllabus' }}
                </small>
              </div>
            </div>

            <div v-else class="course-image-empty syllabus-empty mb-3 media-drop-target" :class="{ 'is-dragging': syllabusDragging }" @dragenter.prevent="syllabusDragging = true" @dragover.prevent="syllabusDragging = true" @dragleave.prevent="syllabusDragging = false" @drop.prevent="handleSyllabusDrop">
              <i class="ri-file-pdf-2-line"></i>
              <span>Drop a PDF here or choose a file</span>
              <small>PDF · up to 10 MB</small>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <button
                type="button"
                class="btn btn-soft-primary btn-sm"
                @click="openSyllabusInput"
              >
                <i class="ri-upload-cloud-2-line me-1"></i>
                {{ hasSyllabus ? 'Replace PDF' : 'Choose PDF' }}
              </button>

              <a
                v-if="form.syllabus_url && !form.syllabus"
                :href="form.syllabus_url"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-soft-info btn-sm"
              >
                <i class="ri-eye-line me-1"></i>
                {{ $t('ui-view') }}
              </a>

              <button
                v-if="hasSyllabus"
                type="button"
                class="btn btn-soft-danger btn-sm"
                @click="removeSyllabus"
              >
                <i class="ri-delete-bin-line me-1"></i>
                {{ $t('ui-remove') }}
              </button>
            </div>

            <p class="text-muted small mb-0 mt-3">
              {{ $t('ui-pdf-only-maximum-size-10-mb') }}
            </p>

            <div v-if="form.errors.syllabus" class="invalid-feedback d-block">
              <i class="ri-error-warning-line" aria-hidden="true"></i>
              {{ form.errors.syllabus }}
            </div>
            <div v-if="syllabusUploadError" class="text-danger small" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ syllabusUploadError }}</div>
          </div>

          <div class="course-image-card mb-3">
            <label class="form-label">{{ $t('ui-course-image') }}</label>

            <div v-if="imagePreview" class="course-image-preview mb-3 media-drop-target" :class="{ 'is-dragging': imageDragging }" @dragenter.prevent="imageDragging = true" @dragover.prevent="imageDragging = true" @dragleave.prevent="imageDragging = false" @drop.prevent="handleImageDrop">
              <img :src="imagePreview" :alt="$t('t-course-image')" />
            </div>
            <p v-if="form.image" class="text-success small mb-2" role="status"><i class="ri-check-line" aria-hidden="true"></i> {{ form.image.name }}</p>

            <div v-else class="course-image-empty mb-3 media-drop-target" :class="{ 'is-dragging': imageDragging }" @dragenter.prevent="imageDragging = true" @dragover.prevent="imageDragging = true" @dragleave.prevent="imageDragging = false" @drop.prevent="handleImageDrop">
              <i class="ri-image-add-line"></i>
              <span>Drop an image here or choose a file</span>
              <small>JPG, PNG, or WebP · up to 2 MB</small>
            </div>

            <input
              id="courseImageInput"
              type="file"
              class="d-none"
              accept=".jpg,.jpeg,.png,.webp"
              @change="handleImageChange"
            />

            <div class="d-flex gap-2 flex-wrap">
              <label for="courseImageInput" class="btn btn-soft-primary btn-sm mb-0">
                <i class="ri-upload-cloud-2-line me-1"></i>
                {{ $t('ui-choose-image') }}
              </label>

              <button
                v-if="imagePreview"
                type="button"
                class="btn btn-soft-danger btn-sm"
                @click="removeImage"
              >
                <i class="ri-delete-bin-line me-1"></i>
                {{ $t('ui-remove') }}
              </button>
            </div>

            <div v-if="form.errors.image" class="invalid-feedback d-block">
              <i class="ri-error-warning-line" aria-hidden="true"></i>
              {{ form.errors.image }}
            </div>
            <div v-if="imageUploadError" class="text-danger small" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ imageUploadError }}</div>
          </div>

          <h6 class="mb-3">
            <i class="ri-information-line me-1"></i>
            {{ $t('ui-scorm-summary') }}
          </h6>

          <div class="publish-card mb-3">
            <div>
              <h6 class="mb-1">{{ $t('ui-course-type') }}</h6>

              <p class="text-muted mb-0">
                {{ $t('ui-this-course-uses-a-scorm-package') }}
              </p>
            </div>

            <span class="mini-badge warning">
              {{ $t('ui-scorm') }}
            </span>
          </div>

          <div class="publish-card mb-3">
            <div>
              <h6 class="mb-1">{{ $t('ui-status') }}</h6>

              <p class="text-muted mb-0">
                {{ form.is_published ? 'Visible to learners' : 'Draft mode' }}
              </p>
            </div>

            <span
              class="mini-badge"
              :class="form.is_published ? 'success' : 'warning'"
            >
              {{ form.is_published ? $t('ui-published') : $t('ui-draft') }}
            </span>
          </div>

          <div class="small text-muted">
            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-type') }}</span>
              <strong class="text-dark text-capitalize">
                {{ form.type === 'paid' ? $t('ui-paid') : form.type ? $t('t-free') : '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-training') }}</span>
              <strong class="text-dark text-capitalize">
                {{ formatCourseLabel(form.training_type) }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-method') }}</span>
              <strong class="text-dark text-end">
                {{ formatCourseLabel(form.method) }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-duration') }}</span>
              <strong class="text-dark">
                {{ form.duration_hours ? `${form.duration_hours} h` : '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-passing-score') }}</span>
              <strong class="text-dark">
                {{ form.passing_score !== '' && form.passing_score !== null
                  ? `${form.passing_score}%`
                  : '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-level') }}</span>
              <strong class="text-dark text-capitalize">
                {{ formatCourseLabel(form.skill_level) }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-language') }}</span>
              <strong class="text-dark">
                {{ formatCourseLabel(form.language) }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-tags') }}</span>
              <strong class="text-dark">
                {{ selectedTags.length }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-scorm-version') }}</span>
              <strong class="text-dark">
                {{ form.scorm_version || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>{{ $t('ui-package') }}</span>
              <strong class="text-dark">
                {{ form.scorm_file || form.scorm_zip_path ? 'Selected' : 'Not uploaded' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between" v-if="form.type === 'paid'">
              <span>{{ $t('ui-price') }}</span>
              <strong class="text-dark">
                {{ form.price || 0 }} {{ form.currency || 'MAD' }}
              </strong>
            </div>
          </div>
        </div>
      </BCol>
    </BRow>
  </div>
</template>

<style scoped>
.media-drop-target { transition: border-color 140ms ease, background-color 140ms ease; }
.media-drop-target.is-dragging { border: 2px dashed var(--bs-primary, #1f5cff); background: rgba(31, 92, 255, 0.08); }
.upload-progress .progress { height: 7px; }

.syllabus-type-badge {
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 11px;
}

.syllabus-file-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border: 1px solid #fee2e2;
  border-radius: 14px;
  background: #fff7f7;
}

.syllabus-file-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #f06548;
  background: rgba(240, 101, 72, 0.12);
  font-size: 22px;
}

.syllabus-file-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.syllabus-file-content strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #334155;
}

.syllabus-file-content small {
  color: #64748b;
}

.syllabus-empty {
  min-height: 125px;
}

.text-end {
  max-width: 64%;
  text-align: right;
}
</style>
