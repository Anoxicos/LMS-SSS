<script setup>
import { computed, ref, watch } from 'vue'

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
})

const imagePreview = ref(null)
const syllabusInput = ref(null)

const methodOptions = [
  {
    value: 'Online e-learning (LMS)',
    label: 'Online e-learning (LMS)',
  },
  {
    value: 'Virtual classroom',
    label: 'Virtual classroom',
  },
  {
    value: 'On-site classroom training',
    label: 'On-site classroom training',
  },
  {
    value: 'Blended learning',
    label: 'Blended learning',
  },
  {
    value: 'Self-study',
    label: 'Self-study',
  },
]

const voiceLanguageOptions = [
  { value: 'en-us', label: 'English - US' },
  { value: 'en-gb', label: 'English - UK' },
  { value: 'fr-fr', label: 'French' },
  { value: 'ar-sa', label: 'Arabic' },
  { value: 'es-es', label: 'Spanish' },
]

const voiceOptionsByLanguage = {
  'en-us': [
    { value: 'default', label: 'Default' },
    { value: 'John', label: 'John - Male' },
    { value: 'Linda', label: 'Linda - Female' },
    { value: 'Amy', label: 'Amy - Female' },
    { value: 'Mary', label: 'Mary - Female' },
  ],

  'en-gb': [
    { value: 'default', label: 'Default' },
    { value: 'Alice', label: 'Alice - Female' },
    { value: 'Nancy', label: 'Nancy - Female' },
    { value: 'Lily', label: 'Lily - Female' },
  ],

  'fr-fr': [
    { value: 'default', label: 'Default' },
    { value: 'Bette', label: 'Bette - Female' },
    { value: 'Iva', label: 'Iva - Female' },
    { value: 'Zola', label: 'Zola - Female' },
  ],

  'ar-sa': [
    { value: 'default', label: 'Default' },
  ],

  'es-es': [
    { value: 'default', label: 'Default' },
    { value: 'Camila', label: 'Camila - Female' },
    { value: 'Sofia', label: 'Sofia - Female' },
    { value: 'Luna', label: 'Luna - Female' },
  ],
}

const availableVoiceOptions = computed(() => {
  return voiceOptionsByLanguage[props.form.voice_over_lang] || [
    { value: 'default', label: 'Default' },
  ]
})

watch(
  () => props.form.image_url,
  (newUrl) => {
    if (!props.form.image) {
      imagePreview.value = newUrl || null
    }
  },
  { immediate: true }
)

watch(
  () => props.form.support_voice_over,
  (enabled) => {
    if (!enabled) {
      props.form.voice_over_auto_play = false
      props.form.voice_over_provider = 'voicerss'
      props.form.voice_over_lang = 'en-us'
      props.form.voice_over_voice = 'default'
    }
  }
)

watch(
  () => props.form.voice_over_lang,
  () => {
    const voiceExists = availableVoiceOptions.value.some((voice) => {
      return voice.value === props.form.voice_over_voice
    })

    if (!voiceExists) {
      props.form.voice_over_voice = 'default'
    }
  }
)

const selectedTags = computed(() => {
  return Array.isArray(props.form.tags) ? props.form.tags : []
})

const selectedVoiceLabel = computed(() => {
  const selectedVoice = availableVoiceOptions.value.find((voice) => {
    return voice.value === props.form.voice_over_voice
  })

  return selectedVoice?.label || 'Default'
})

const selectedLanguageLabel = computed(() => {
  const selectedLanguage = voiceLanguageOptions.find((language) => {
    return language.value === props.form.voice_over_lang
  })

  return selectedLanguage?.label || 'English - US'
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

  if (!file) return

  props.form.image = file
  props.form.remove_image = false
  imagePreview.value = URL.createObjectURL(file)
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

  if (!file) return

  const isPdf =
    file.type === 'application/pdf' ||
    file.name.toLowerCase().endsWith('.pdf')

  if (!isPdf) {
    event.target.value = ''
    return
  }

  props.form.syllabus = file
  props.form.remove_syllabus = false
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
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>Course Information</h5>
      <p>Fill in the main course details.</p>
    </div>

    <BRow class="g-3">
      <BCol xl="8">
        <BRow class="g-3">
          <BCol lg="3">
            <label class="form-label">
              Reference <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-hashtag input-icon"></i>

              <input
                v-model="form.ref"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.ref }"
                placeholder="Example: EWIS-001"
              />
            </div>

            <div v-if="form.errors.ref" class="invalid-feedback d-block">
              {{ form.errors.ref }}
            </div>
          </BCol>

          <BCol lg="6">
            <label class="form-label">
              Title <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-book-open-line input-icon"></i>

              <input
                v-model="form.title"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.title }"
                placeholder="Enter course title"
              />
            </div>

            <div v-if="form.errors.title" class="invalid-feedback d-block">
              {{ form.errors.title }}
            </div>
          </BCol>

          <BCol lg="3">
            <label class="form-label">
              Category <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.category_id"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.category_id }"
            >
              <option value="">Select category</option>

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
            <label class="form-label">Description</label>

            <textarea
              v-model="form.description"
              rows="4"
              class="form-control modern-textarea"
              :class="{ 'is-invalid': form.errors.description }"
              placeholder="Enter course description"
            ></textarea>

            <div v-if="form.errors.description" class="invalid-feedback d-block">
              {{ form.errors.description }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              Type <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.type"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.type }"
            >
              <option value="">Select type</option>
              <option value="free">Free</option>
              <option value="paid">Paid</option>
              <option value="private">Private</option>
            </select>

            <div v-if="form.errors.type" class="invalid-feedback d-block">
              {{ form.errors.type }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">
              Training Type <span class="text-danger">*</span>
            </label>

            <select
              v-model="form.training_type"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.training_type }"
            >
              <option value="">Select training type</option>
              <option value="online">Online</option>
              <option value="onsite">Onsite</option>
              <option value="hybrid">Hybrid</option>
            </select>

            <div v-if="form.errors.training_type" class="invalid-feedback d-block">
              {{ form.errors.training_type }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">Course Type</label>

            <select
              v-model="form.skill_level"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.skill_level }"
            >
              <option value="" disabled>Select a training type</option>
              <option value="initial">Initial</option>
              <option value="recurrent">Recurrent</option>
            </select>

            <div v-if="form.errors.skill_level" class="invalid-feedback d-block">
              {{ form.errors.skill_level }}
            </div>
          </BCol>

          <BCol md="6">
            <label for="method-field" class="form-label">
              Method <span class="text-danger">*</span>
            </label>

            <select
              id="method-field"
              v-model="form.method"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.method }"
            >
              <option value="" disabled>
                Select training method
              </option>

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

          <BCol md="3">
            <label class="form-label">
              Duration (hours) <span class="text-danger">*</span>
            </label>

            <div class="input-icon-box">
              <i class="ri-time-line input-icon"></i>

              <input
                v-model="form.duration_hours"
                type="number"
                min="0.01"
                step="0.25"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.duration_hours }"
                placeholder="Example: 8"
              />
            </div>

            <div
              v-if="form.errors.duration_hours"
              class="invalid-feedback d-block"
            >
              {{ form.errors.duration_hours }}
            </div>
          </BCol>

          <BCol md="3">
            <label class="form-label">
              Passing Score (%) <span class="text-danger">*</span>
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
                placeholder="Example: 80"
              />
            </div>

            <div
              v-if="form.errors.passing_score"
              class="invalid-feedback d-block"
            >
              {{ form.errors.passing_score }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">Course Focus</label>

            <div class="input-icon-box">
              <i class="ri-focus-3-line input-icon"></i>

              <input
                v-model="form.course_focus"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': form.errors.course_focus }"
                placeholder="Example: Web development"
              />
            </div>

            <div v-if="form.errors.course_focus" class="invalid-feedback d-block">
              {{ form.errors.course_focus }}
            </div>
          </BCol>

          <BCol md="4">
            <label class="form-label">Language</label>

            <select
              v-model="form.language"
              class="form-select modern-input"
              :class="{ 'is-invalid': form.errors.language }"
            >
              <option value="">Select language</option>
              <option value="English">English</option>
              <option value="French">French</option>
              <option value="Arabic">Arabic</option>
            </select>

            <div v-if="form.errors.language" class="invalid-feedback d-block">
              {{ form.errors.language }}
            </div>
          </BCol>

          <BCol md="2" v-if="form.type === 'paid'">
            <label class="form-label">Price</label>

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
            <label class="form-label">Currency</label>

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
            <label class="form-label">Tags</label>

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
                <h6 class="mb-1">Publication Status</h6>
                <p class="text-muted mb-0">
                  {{
                    form.is_published
                      ? 'This course will be published.'
                      : 'This course will be saved as draft.'
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
                  {{ form.is_published ? 'Published' : 'Draft' }}
                </label>
              </div>
            </div>

            <div v-if="form.errors.is_published" class="invalid-feedback d-block">
              {{ form.errors.is_published }}
            </div>
          </BCol>
        </BRow>
      </BCol>

      <BCol xl="4">
        <div class="builder-side-form h-100">
          <div class="course-image-card mb-3">
            <label class="form-label">Course Image</label>

            <div v-if="imagePreview" class="course-image-preview mb-3">
              <img :src="imagePreview" alt="Course image" />
            </div>

            <div v-else class="course-image-empty mb-3">
              <i class="ri-image-add-line"></i>
              <span>No image selected</span>
            </div>

            <input
              id="courseImageInput"
              type="file"
              class="d-none"
              accept="image/*"
              @change="handleImageChange"
            />

            <div class="d-flex gap-2 flex-wrap">
              <label for="courseImageInput" class="btn btn-soft-primary btn-sm mb-0">
                <i class="ri-upload-cloud-2-line me-1"></i>
                Choose Image
              </label>

              <button
                v-if="imagePreview"
                type="button"
                class="btn btn-soft-danger btn-sm"
                @click="removeImage"
              >
                <i class="ri-delete-bin-line me-1"></i>
                Remove
              </button>
            </div>

            <div v-if="form.errors.image" class="invalid-feedback d-block">
              {{ form.errors.image }}
            </div>
          </div>

          <div class="course-image-card mb-3">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <div>
                <label class="form-label mb-1">Course Syllabus</label>
                <p class="text-muted small mb-0">
                  Upload the official syllabus as a PDF file (maximum 10 MB).
                </p>
              </div>

              <span class="badge bg-danger-subtle text-danger syllabus-type-badge">
                PDF
              </span>
            </div>

            <input
              ref="syllabusInput"
              type="file"
              class="d-none"
              accept="application/pdf,.pdf"
              @change="handleSyllabusChange"
            />

            <div v-if="hasSyllabus" class="syllabus-file-box mb-3">
              <div class="syllabus-file-icon">
                <i class="ri-file-pdf-2-line"></i>
              </div>

              <div class="syllabus-file-content">
                <strong :title="syllabusFileName">
                  {{ syllabusFileName }}
                </strong>
                <small>
                  {{ form.syllabus ? 'New PDF selected' : 'Current syllabus' }}
                </small>
              </div>
            </div>

            <div v-else class="course-image-empty syllabus-empty mb-3">
              <i class="ri-file-pdf-2-line"></i>
              <span>No syllabus selected</span>
            </div>

            <div class="d-flex gap-2 flex-wrap">
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
                View PDF
              </a>

              <button
                v-if="hasSyllabus"
                type="button"
                class="btn btn-soft-danger btn-sm"
                @click="removeSyllabus"
              >
                <i class="ri-delete-bin-line me-1"></i>
                Remove
              </button>
            </div>

            <div v-if="form.errors.syllabus" class="invalid-feedback d-block">
              {{ form.errors.syllabus }}
            </div>
          </div>

          <div class="publish-card mb-3">
            <div>
              <h6 class="mb-1">
                <i class="ri-mic-line me-1"></i>
                Voice Over Support
              </h6>

              <p class="text-muted mb-0">
                Enable this if the course supports narrated content.
              </p>
            </div>

            <div class="form-check form-switch">
              <input
                v-model="form.support_voice_over"
                class="form-check-input"
                type="checkbox"
                id="supportVoiceOver"
              />

              <label class="form-check-label" for="supportVoiceOver">
                {{ form.support_voice_over ? 'Yes' : 'No' }}
              </label>
            </div>
          </div>

          <div
            v-if="form.errors.support_voice_over"
            class="invalid-feedback d-block mb-3"
          >
            {{ form.errors.support_voice_over }}
          </div>

          <div v-if="form.support_voice_over" class="voice-over-card mb-3">
            <div class="voice-over-header mb-3">
              <div>
                <h6 class="mb-1">
                  <i class="ri-settings-3-line me-1"></i>
                  Global Voice Settings
                </h6>

                <p class="text-muted mb-0">
                  These settings will be used for all slide voice generation.
                </p>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Provider</label>

              <select
                v-model="form.voice_over_provider"
                class="form-select modern-input"
                :class="{ 'is-invalid': form.errors.voice_over_provider }"
              >
                <option value="voicerss">VoiceRSS</option>
              </select>

              <div
                v-if="form.errors.voice_over_provider"
                class="invalid-feedback d-block"
              >
                {{ form.errors.voice_over_provider }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Voice Language</label>

              <select
                v-model="form.voice_over_lang"
                class="form-select modern-input"
                :class="{ 'is-invalid': form.errors.voice_over_lang }"
              >
                <option
                  v-for="language in voiceLanguageOptions"
                  :key="language.value"
                  :value="language.value"
                >
                  {{ language.label }}
                </option>
              </select>

              <div
                v-if="form.errors.voice_over_lang"
                class="invalid-feedback d-block"
              >
                {{ form.errors.voice_over_lang }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Voice</label>

              <select
                v-model="form.voice_over_voice"
                class="form-select modern-input"
                :class="{ 'is-invalid': form.errors.voice_over_voice }"
              >
                <option
                  v-for="voice in availableVoiceOptions"
                  :key="voice.value"
                  :value="voice.value"
                >
                  {{ voice.label }}
                </option>
              </select>

              <div
                v-if="form.errors.voice_over_voice"
                class="invalid-feedback d-block"
              >
                {{ form.errors.voice_over_voice }}
              </div>
            </div>

            <div class="publish-card mb-0">
              <div>
                <h6 class="mb-1">
                  <i class="ri-play-circle-line me-1"></i>
                  Voice Over Auto Play
                </h6>

                <p class="text-muted mb-0">
                  Start voice over automatically when the learner opens a slide.
                </p>
              </div>

              <div class="form-check form-switch">
                <input
                  v-model="form.voice_over_auto_play"
                  class="form-check-input"
                  type="checkbox"
                  id="voiceOverAutoPlay"
                />

                <label class="form-check-label" for="voiceOverAutoPlay">
                  {{ form.voice_over_auto_play ? 'Yes' : 'No' }}
                </label>
              </div>
            </div>
          </div>

          <h6 class="mb-3">
            <i class="ri-information-line me-1"></i>
            Course Summary
          </h6>

          <div class="publish-card mb-3">
            <div>
              <h6 class="mb-1">Status</h6>

              <p class="text-muted mb-0">
                {{ form.is_published ? 'Visible to learners' : 'Draft mode' }}
              </p>
            </div>

            <span
              class="mini-badge"
              :class="form.is_published ? 'success' : 'warning'"
            >
              {{ form.is_published ? 'Published' : 'Draft' }}
            </span>
          </div>

          <div class="small text-muted">
            <div class="d-flex justify-content-between mb-2 gap-3">
              <span>Reference</span>
              <strong class="text-dark text-end">
                {{ form.ref || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2 gap-3">
              <span>Method</span>
              <strong class="text-dark text-end summary-truncate">
                {{ form.method || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Duration</span>
              <strong class="text-dark">
                {{ form.duration_hours ? `${form.duration_hours} h` : '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Passing Score</span>
              <strong class="text-dark">
                {{ form.passing_score !== '' && form.passing_score !== null
                  ? `${form.passing_score}%`
                  : '-'
                }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Syllabus</span>
              <strong :class="hasSyllabus ? 'text-success' : 'text-muted'">
                {{ hasSyllabus ? 'Available' : 'Not uploaded' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Type</span>
              <strong class="text-dark text-capitalize">
                {{ form.type || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Training</span>
              <strong class="text-dark text-capitalize">
                {{ form.training_type || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Level</span>
              <strong class="text-dark text-capitalize">
                {{ form.skill_level || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Language</span>
              <strong class="text-dark">
                {{ form.language || '-' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Tags</span>
              <strong class="text-dark">
                {{ selectedTags.length }}
              </strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span>Voice Over</span>
              <strong class="text-dark">
                {{ form.support_voice_over ? 'Supported' : 'Not supported' }}
              </strong>
            </div>

            <div
              v-if="form.support_voice_over"
              class="d-flex justify-content-between mb-2"
            >
              <span>Voice Lang</span>
              <strong class="text-dark">
                {{ selectedLanguageLabel }}
              </strong>
            </div>

            <div
              v-if="form.support_voice_over"
              class="d-flex justify-content-between mb-2"
            >
              <span>Voice</span>
              <strong class="text-dark">
                {{ selectedVoiceLabel }}
              </strong>
            </div>

            <div
              v-if="form.support_voice_over"
              class="d-flex justify-content-between mb-2"
            >
              <span>Auto Play</span>
              <strong class="text-dark">
                {{ form.voice_over_auto_play ? 'Enabled' : 'Disabled' }}
              </strong>
            </div>

            <div class="d-flex justify-content-between" v-if="form.type === 'paid'">
              <span>Price</span>
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
.syllabus-type-badge {
  flex-shrink: 0;
  border-radius: 999px;
  padding: 7px 10px;
}

.syllabus-file-box {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  padding: 13px;
  border: 1px solid #fee2e2;
  border-radius: 14px;
  background: #fff7f7;
}

.syllabus-file-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #f06548;
  background: #ffffff;
  font-size: 22px;
  box-shadow: 0 6px 16px rgba(240, 101, 72, 0.12);
}

.syllabus-file-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.syllabus-file-content strong {
  color: #334155;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.syllabus-file-content small {
  color: #64748b;
}

.syllabus-empty {
  min-height: 110px;
}

.summary-truncate {
  max-width: 190px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
