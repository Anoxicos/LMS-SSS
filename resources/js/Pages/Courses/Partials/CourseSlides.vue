<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  courseId: {
    type: [Number, String],
    required: true,
  },
  courseHashid: {
    type: [Number, String],
    required: true,
  },
  totalSlides: {
    type: Number,
    default: 0,
  },
})

const selectedChapterStorageKey = `course-${props.courseId}-selected-chapter`

const selectedChapterId = ref(
  localStorage.getItem(selectedChapterStorageKey) || null
)

const editingSlide = ref(null)
const selectedFileName = ref('')

const emptyQuestion = () => {
  return {
    question_type: 'qcm',
    question: '',
    option_a: '',
    option_b: '',
    option_c: '',
    correct_answer: '',
    explanation: '',
  }
}

const questionError = (questionIndex, field) => {
  return slideForm.errors[`questions.${questionIndex}.${field}`] || ''
}

const setQuestionType = (questionItem, type) => {
  questionItem.question_type = type

  if (type === 'true_false') {
    questionItem.option_a = ''
    questionItem.option_b = ''
    questionItem.option_c = ''

    if (!['true', 'false'].includes(String(questionItem.correct_answer))) {
      questionItem.correct_answer = ''
    }

    return
  }

  if (!['a', 'b', 'c'].includes(String(questionItem.correct_answer))) {
    questionItem.correct_answer = ''
  }
}

const chapters = computed(() => {
  return [...(props.form.chapters || [])].sort((a, b) => {
    return Number(a.sort_order || 0) - Number(b.sort_order || 0)
  })
})

const selectedChapter = computed(() => {
  return chapters.value.find((chapter) => {
    return Number(chapter.id) === Number(selectedChapterId.value)
  })
})

const slides = computed(() => {
  return [...(selectedChapter.value?.slides || [])].sort((a, b) => {
    return Number(a.sort_order || 0) - Number(b.sort_order || 0)
  })
})

const courseVoiceEnabled = computed(() => {
  return Boolean(props.form.support_voice_over)
})

const courseVoiceAutoPlayLabel = computed(() => {
  return props.form.voice_over_auto_play ? 'Enabled' : 'Disabled'
})

const courseVoiceProviderLabel = computed(() => {
  return props.form.voice_over_provider || 'voicerss'
})

const courseVoiceLangLabel = computed(() => {
  const labels = {
    'en-us': 'English - US',
    'en-gb': 'English - UK',
    'fr-fr': t('ui-french'),
    'ar-sa': t('ui-arabic'),
    'es-es': 'Spanish',
  }

  return labels[props.form.voice_over_lang] || props.form.voice_over_lang || 'English - US'
})

const courseVoiceNameLabel = computed(() => {
  return props.form.voice_over_voice || 'default'
})

watch(
  chapters,
  (newChapters) => {
    if (!newChapters.length) {
      selectedChapterId.value = null
      localStorage.removeItem(selectedChapterStorageKey)
      return
    }

    const savedChapterId = localStorage.getItem(selectedChapterStorageKey)

    const savedChapterExists = newChapters.some((chapter) => {
      return Number(chapter.id) === Number(savedChapterId)
    })

    if (savedChapterId && savedChapterExists) {
      selectedChapterId.value = savedChapterId
      return
    }

    const currentExists = newChapters.some((chapter) => {
      return Number(chapter.id) === Number(selectedChapterId.value)
    })

    if (!currentExists) {
      selectedChapterId.value = newChapters[0].id
    }
  },
  { immediate: true }
)

watch(
  selectedChapterId,
  (newChapterId) => {
    if (newChapterId) {
      localStorage.setItem(selectedChapterStorageKey, newChapterId)
    }
  },
  { immediate: true }
)

const slideForm = useForm({
  title: '',
  lesson_type: 'text',
  content: '',
  video_url: '',
  video_duration: '',
  file: null,
  file_path: null,
  file_name: null,
  file_type: null,
  file_size: null,
  external_url: '',
  is_preview: false,
  is_mandatory: true,
  is_downloadable: false,
  is_active: true,

  questions: [emptyQuestion()],

  voice_over_enabled: false,
  voice_over_text: '',
  voice_over_audio_path: null,
  voice_over_audio_url: null,
  voice_over_audio_name: null,
  voice_over_audio_type: null,
  voice_over_audio_size: null,
})

const isEditing = computed(() => {
  return Boolean(editingSlide.value)
})

const lessonTypes = [
  {
    value: 'text',
    label: 'Text',
    icon: 'ri-text-snippet',
    description: 'Written lesson content',
  },
  {
    value: 'video',
    label: 'Video',
    icon: 'ri-video-line',
    description: 'Video URL or embed link',
  },
  {
    value: 'file',
    label: t('ui-file'),
    icon: 'ri-file-3-line',
    description: 'PDF, image or document',
  },
  {
    value: 'link',
    label: 'External Link',
    icon: 'ri-links-line',
    description: 'External resource URL',
  },
  {
    value: 'quiz',
    label: 'Quiz',
    icon: 'ri-question-answer-line',
    description: 'Comprehension questions',
  },
]

const showSuccess = (message) => {
  Swal.fire({
    icon: 'success',
    title: t('ui-success'),
    text: message,
    timer: 1600,
    showConfirmButton: false,
  })
}

const showError = (message = 'Please check the form and try again.') => {
  Swal.fire({
    icon: 'error',
    title: t('t-error'),
    text: message,
    timer: 2200,
    showConfirmButton: false,
  })
}

const resetSlideForm = () => {
  editingSlide.value = null
  selectedFileName.value = ''

  slideForm.reset()
  slideForm.clearErrors()

  slideForm.title = ''
  slideForm.lesson_type = 'text'
  slideForm.content = ''
  slideForm.video_url = ''
  slideForm.video_duration = ''
  slideForm.file = null
  slideForm.file_path = null
  slideForm.file_name = null
  slideForm.file_type = null
  slideForm.file_size = null
  slideForm.external_url = ''

  slideForm.is_preview = false
  slideForm.is_mandatory = true
  slideForm.is_downloadable = false
  slideForm.is_active = true

  slideForm.questions = [emptyQuestion()]

  slideForm.voice_over_enabled = false
  slideForm.voice_over_text = ''
  slideForm.voice_over_audio_path = null
  slideForm.voice_over_audio_url = null
  slideForm.voice_over_audio_name = null
  slideForm.voice_over_audio_type = null
  slideForm.voice_over_audio_size = null
}

const selectLessonType = (type) => {
  slideForm.lesson_type = type
  slideForm.clearErrors()

  if (type !== 'text') {
    slideForm.content = ''
  }

  if (type !== 'video') {
    slideForm.video_url = ''
    slideForm.video_duration = ''
  }

  if (type !== 'file') {
    slideForm.file = null
    slideForm.file_path = null
    slideForm.file_name = null
    slideForm.file_type = null
    slideForm.file_size = null
    selectedFileName.value = ''
  }

  if (type !== 'link') {
    slideForm.external_url = ''
  }

  if (type !== 'quiz') {
    slideForm.questions = [emptyQuestion()]
  }
}

const addQuestion = () => {
  if (slideForm.questions.length >= 3) {
    showError('You can add maximum 3 questions per slide.')
    return
  }

  slideForm.questions.push(emptyQuestion())
}

const removeQuestion = (index) => {
  if (slideForm.questions.length <= 1) {
    showError('At least one question is required.')
    return
  }

  slideForm.questions.splice(index, 1)
}

const handleFileChange = (event) => {
  const file = event.target.files?.[0]

  if (!file) return

  slideForm.file = file
  slideForm.file_path = null
  slideForm.file_name = file.name
  slideForm.file_type = file.name.split('.').pop()
  slideForm.file_size = file.size
  selectedFileName.value = file.name
}

const editSlide = (slide, chapter) => {
  selectedChapterId.value = chapter.id
  localStorage.setItem(selectedChapterStorageKey, chapter.id)

  editingSlide.value = slide

  slideForm.title = slide.title || ''
  slideForm.lesson_type = slide.lesson_type || 'text'
  slideForm.content = slide.content || ''
  slideForm.video_url = slide.video_url || ''
  slideForm.video_duration = slide.video_duration || ''
  slideForm.file = null
  slideForm.file_path = slide.file_path || null
  slideForm.file_name = slide.file_name || null
  slideForm.file_type = slide.file_type || null
  slideForm.file_size = slide.file_size || null
  slideForm.external_url = slide.external_url || ''
  slideForm.is_preview = Boolean(slide.is_preview)
  slideForm.is_mandatory =
    slide.is_mandatory === undefined ? true : Boolean(slide.is_mandatory)
  slideForm.is_downloadable = Boolean(slide.is_downloadable)
  slideForm.is_active =
    slide.is_active === undefined ? true : Boolean(slide.is_active)

  slideForm.questions = slide.questions?.length
    ? slide.questions.map((question) => {
        return {
          question_type: question.question_type || 'qcm',
          question: question.question || '',
          option_a: question.option_a || '',
          option_b: question.option_b || '',
          option_c: question.option_c || '',
          correct_answer: String(question.correct_answer ?? ''),
          explanation: question.explanation || '',
        }
      })
    : [emptyQuestion()]

  slideForm.voice_over_enabled = Boolean(slide.voice_over_enabled)
  slideForm.voice_over_text = slide.voice_over_text || ''
  slideForm.voice_over_audio_path = slide.voice_over_audio_path || null
  slideForm.voice_over_audio_url = slide.voice_over_audio_url || null
  slideForm.voice_over_audio_name = slide.voice_over_audio_name || null
  slideForm.voice_over_audio_type = slide.voice_over_audio_type || null
  slideForm.voice_over_audio_size = slide.voice_over_audio_size || null

  selectedFileName.value = slide.file_name || ''
  slideForm.clearErrors()
}

const validateQuizQuestions = () => {
  if (slideForm.lesson_type !== 'quiz') return true

  if (!slideForm.questions.length) {
    showError('Please add at least one question.')
    return false
  }

  if (slideForm.questions.length > 3) {
    showError('You can add maximum 3 questions per slide.')
    return false
  }

  for (const question of slideForm.questions) {
    if (!question.question?.trim()) {
      showError('Each quiz item must contain a question.')
      return false
    }

    if (!['qcm', 'true_false'].includes(question.question_type)) {
      showError('Please select a valid question type.')
      return false
    }

    if (question.question_type === 'qcm') {
      if (!question.option_a?.trim() || !question.option_b?.trim()) {
        showError('Each QCM question requires at least options A and B.')
        return false
      }

      if (!['a', 'b', 'c'].includes(question.correct_answer)) {
        showError('Please select the correct answer for each QCM question.')
        return false
      }

      if (question.correct_answer === 'c' && !question.option_c?.trim()) {
        showError('Option C is required when it is selected as the correct answer.')
        return false
      }
    }

    if (
      question.question_type === 'true_false' &&
      !['true', 'false'].includes(String(question.correct_answer))
    ) {
      showError('Please select True or False as the correct answer.')
      return false
    }
  }

  return true
}

const submitSlide = () => {
  if (!selectedChapterId.value) {
    showError('Please create or select a chapter first.')
    return
  }

  if (!validateQuizQuestions()) {
    return
  }

  const currentChapterId = selectedChapterId.value
  localStorage.setItem(selectedChapterStorageKey, currentChapterId)

  if (isEditing.value) {
    slideForm.post(
      route('courses.slides.update', [props.courseId, editingSlide.value.id]),
      {
        preserveScroll: true,
        preserveState: false,
        forceFormData: true,
        onSuccess: () => {
          selectedChapterId.value = currentChapterId
          localStorage.setItem(selectedChapterStorageKey, currentChapterId)

          showSuccess('Slide has been updated successfully.')
          resetSlideForm()
        },
        onError: () => {
          showError('Please check slide information.')
        },
      }
    )
    return
  }

  slideForm.post(
    route('courses.slides.store', [props.courseId, currentChapterId]),
    {
      preserveScroll: true,
      preserveState: false,
      forceFormData: true,
      onSuccess: () => {
        selectedChapterId.value = currentChapterId
        localStorage.setItem(selectedChapterStorageKey, currentChapterId)

        showSuccess('Slide has been created successfully.')
        resetSlideForm()
      },
      onError: () => {
        showError('Please check slide information.')
      },
    }
  )
}

const generateVoiceOver = () => {
  if (!courseVoiceEnabled.value) {
    showError('Please enable voice over support in course settings first.')
    return
  }

  if (!isEditing.value || !editingSlide.value) {
    showError('Please save the slide before generating voice over.')
    return
  }

  if (!slideForm.voice_over_text) {
    showError('Please enter voice over text first.')
    return
  }

  router.post(
    route('courses.slides.voice-over.generate', [
      props.courseId,
      editingSlide.value.id,
    ]),
    {
      voice_over_text: slideForm.voice_over_text,
    },
    {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showSuccess('Voice over has been generated successfully.')
      },
      onError: () => {
        showError('Unable to generate voice over.')
      },
    }
  )
}

const deleteVoiceOver = () => {
  if (!isEditing.value || !editingSlide.value) {
    showError('Please select a slide first.')
    return
  }

  Swal.fire({
    title: t('ui-delete-voice-over'),
    text: t('ui-the-generated-audio-file-will-be-removed'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('ui-yes-delete'),
    cancelButtonText: t('ui-cancel'),
    confirmButtonColor: '#f06548',
  }).then((result) => {
    if (!result.isConfirmed) return

    router.delete(
      route('courses.slides.voice-over.delete', [
        props.courseId,
        editingSlide.value.id,
      ]),
      {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
          showSuccess('Voice over has been deleted successfully.')
        },
        onError: () => {
          showError('Unable to delete voice over.')
        },
      }
    )
  })
}

const deleteSlide = (slide) => {
  if (selectedChapterId.value) {
    localStorage.setItem(selectedChapterStorageKey, selectedChapterId.value)
  }

  Swal.fire({
    title: t('ui-delete-slide'),
    text: `Are you sure you want to delete "${slide.title}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('ui-yes-delete'),
    cancelButtonText: t('ui-cancel'),
    confirmButtonColor: '#f06548',
  }).then((result) => {
    if (!result.isConfirmed) return

    router.delete(route('courses.slides.delete', [props.courseId, slide.id]), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        selectedChapterId.value = selectedChapterId.value || slide.chapter_id
        showSuccess('Slide has been deleted successfully.')
      },
      onError: () => {
        showError('Unable to delete slide.')
      },
    })
  })
}

const reorderSlides = (chapter, orderedSlides) => {
  localStorage.setItem(selectedChapterStorageKey, chapter.id)

  const payload = orderedSlides.map((slide, index) => {
    return {
      id: slide.id,
      sort_order: index + 1,
    }
  })

  router.post(
    route('courses.slides.reorder', [props.courseId, chapter.id]),
    {
      slides: payload,
    },
    {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        selectedChapterId.value = chapter.id
        localStorage.setItem(selectedChapterStorageKey, chapter.id)

        showSuccess('Slides order has been updated successfully.')
      },
      onError: () => {
        showError('Unable to reorder slides.')
      },
    }
  )
}

const moveSlide = (chapter, index, direction) => {
  const ordered = [...(chapter.slides || [])].sort((a, b) => {
    return Number(a.sort_order || 0) - Number(b.sort_order || 0)
  })

  const targetIndex = index + direction

  if (targetIndex < 0 || targetIndex >= ordered.length) return

  const current = ordered[index]
  ordered[index] = ordered[targetIndex]
  ordered[targetIndex] = current

  reorderSlides(chapter, ordered)
}

const getSlideTypeIcon = (type) => {
  if (type === 'video') return 'ri-video-line'
  if (type === 'file') return 'ri-file-3-line'
  if (type === 'link') return 'ri-links-line'
  if (type === 'quiz') return 'ri-question-answer-line'

  return 'ri-text-snippet'
}

const getSlideTypeBadgeClass = (type) => {
  if (type === 'video') return 'warning'
  if (type === 'file') return 'file'
  if (type === 'link') return 'success'
  if (type === 'quiz') return 'info'

  return ''
}

const formatFileSize = (size) => {
  if (!size) return '-'

  const value = Number(size)

  if (value < 1024) return `${value} B`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`

  return `${(value / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>{{ $t('ui-course-slides') }}</h5>
      <p>{{ $t('ui-create-lessons-videos-files-links-and-comprehension-qu') }}</p>
    </div>

    <div v-if="!chapters.length" class="empty-box">
      <i class="ri-list-check-2"></i>
      <h6>{{ $t('ui-no-chapters-available') }}</h6>
      <p>{{ $t('ui-please-add-chapters-before-creating-slides') }}</p>
    </div>

    <BRow v-else class="g-4">
      <BCol xl="8">
        <div class="mb-3">
          <label class="form-label">{{ $t('ui-select-chapter') }}</label>

          <select v-model="selectedChapterId" class="form-select modern-input">
            <option
              v-for="chapter in chapters"
              :key="chapter.id"
              :value="chapter.id"
            >
              {{ chapter.sort_order }}. {{ chapter.title }}
            </option>
          </select>
        </div>

        <div v-if="selectedChapter" class="chapter-slides-box">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h6 class="chapter-slides-title">
              {{ selectedChapter.title }}
            </h6>

            <span class="mini-badge">
              {{ slides.length }} {{ $t('ui-slides-label') }}
            </span>
          </div>

          <div v-if="slides.length" class="items-list">
            <div
              v-for="(slide, index) in slides"
              :key="slide.id"
              class="builder-item-card"
            >
              <div class="builder-item-icon">
                <i :class="getSlideTypeIcon(slide.lesson_type)"></i>
              </div>

              <div class="flex-grow-1 min-width-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <h6 class="mb-0 text-truncate">
                    {{ index + 1 }}. {{ slide.title }}
                  </h6>

                  <span
                    class="mini-badge"
                    :class="getSlideTypeBadgeClass(slide.lesson_type)"
                  >
                    {{ slide.lesson_type }}
                  </span>

                  <span v-if="slide.is_preview" class="mini-badge success">
                    {{ $t('ui-preview') }}
                  </span>

                  <span v-if="!slide.is_active" class="mini-badge warning">
                    {{ $t('ui-inactive') }}
                  </span>

                  <span
                    v-if="slide.voice_over_enabled"
                    class="mini-badge info"
                  >
                    <i class="ri-mic-line me-1"></i>
                    {{ $t('ui-voice') }}
                  </span>
                </div>

                <p class="text-muted mb-0">
                  <template v-if="slide.lesson_type === 'video'">
                    {{ slide.video_url || 'No video URL.' }}
                  </template>

                  <template v-else-if="slide.lesson_type === 'file'">
                    {{ slide.file_name || 'No file selected.' }}
                    <span v-if="slide.file_size">
                      · {{ formatFileSize(slide.file_size) }}
                    </span>
                  </template>

                  <template v-else-if="slide.lesson_type === 'link'">
                    {{ slide.external_url || 'No external URL.' }}
                  </template>

                  <template v-else-if="slide.lesson_type === 'quiz'">
                    {{ slide.questions?.length || 0 }} {{ $t('ui-comprehension-question-s') }}
                  </template>

                  <template v-else>
                    {{ slide.content ? slide.content.slice(0, 110) : 'No content.' }}
                  </template>
                </p>

                <div
                  v-if="slide.voice_over_audio_name"
                  class="small text-muted mt-1"
                >
                  <i class="ri-volume-up-line me-1"></i>
                  {{ slide.voice_over_audio_name }}
                </div>
              </div>

              <div class="d-flex gap-2">
                <BButton
                  type="button"
                  variant="soft-secondary"
                  size="sm"
                  class="action-btn"
                  :disabled="index === 0"
                  :title="$t('ui-move-up')"
                  @click="moveSlide(selectedChapter, index, -1)"
                >
                  <i class="ri-arrow-up-line"></i>
                </BButton>

                <BButton
                  type="button"
                  variant="soft-secondary"
                  size="sm"
                  class="action-btn"
                  :disabled="index === slides.length - 1"
                  :title="$t('ui-move-down')"
                  @click="moveSlide(selectedChapter, index, 1)"
                >
                  <i class="ri-arrow-down-line"></i>
                </BButton>

                <BButton
                  type="button"
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  :title="$t('t-edit')"
                  @click="editSlide(slide, selectedChapter)"
                >
                  <i class="ri-edit-2-line"></i>
                </BButton>

                <BButton
                  type="button"
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  :title="$t('ui-delete')"
                  @click="deleteSlide(slide)"
                >
                  <i class="ri-delete-bin-6-line"></i>
                </BButton>
              </div>
            </div>
          </div>

          <div v-else class="empty-box">
            <i class="ri-slideshow-line"></i>
            <h6>{{ $t('ui-no-slides-in-this-chapter') }}</h6>
            <p>{{ $t('ui-add-the-first-slide-using-the-form-on-the-right') }}</p>
          </div>
        </div>
      </BCol>

      <BCol xl="4">
        <div class="builder-side-form">
          <h6 class="mb-3">
            <i :class="isEditing ? 'ri-edit-2-line' : 'ri-add-line'" class="me-1"></i>
            {{ isEditing ? 'Edit Slide' : 'Add Slide' }}
          </h6>

          <form @submit.prevent="submitSlide">
            <div class="mb-3">
              <label class="form-label">
                {{ $t('t-title') }} <span class="text-danger">*</span>
              </label>

              <input
                v-model="slideForm.title"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': slideForm.errors.title }"
                :placeholder="$t('ui-slide-title')"
              />

              <div v-if="slideForm.errors.title" class="invalid-feedback d-block">
                {{ slideForm.errors.title }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">
                {{ $t('ui-lesson-type') }} <span class="text-danger">*</span>
              </label>

              <div class="slide-options">
                <button
                  v-for="type in lessonTypes"
                  :key="type.value"
                  type="button"
                  class="tag-btn"
                  :class="{ active: slideForm.lesson_type === type.value }"
                  @click="selectLessonType(type.value)"
                >
                  <i :class="type.icon" class="me-1"></i>
                  {{ type.label }}
                </button>
              </div>

              <div
                v-if="slideForm.errors.lesson_type"
                class="invalid-feedback d-block"
              >
                {{ slideForm.errors.lesson_type }}
              </div>
            </div>

            <div v-if="slideForm.lesson_type === 'text'" class="mb-3">
              <label class="form-label">{{ $t('ui-content') }}</label>

              <textarea
                v-model="slideForm.content"
                rows="6"
                class="form-control modern-textarea"
                :class="{ 'is-invalid': slideForm.errors.content }"
                :placeholder="$t('ui-write-lesson-content')"
              ></textarea>

              <div v-if="slideForm.errors.content" class="invalid-feedback d-block">
                {{ slideForm.errors.content }}
              </div>
            </div>

            <div v-if="slideForm.lesson_type === 'video'" class="mb-3">
              <label class="form-label">{{ $t('ui-video-url') }}</label>

              <input
                v-model="slideForm.video_url"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': slideForm.errors.video_url }"
                placeholder="https://youtube.com/..."
              />

              <div
                v-if="slideForm.errors.video_url"
                class="invalid-feedback d-block"
              >
                {{ slideForm.errors.video_url }}
              </div>

              <label class="form-label mt-3">{{ $t('ui-duration-in-seconds') }}</label>

              <input
                v-model="slideForm.video_duration"
                type="number"
                min="0"
                class="form-control modern-input"
                :class="{ 'is-invalid': slideForm.errors.video_duration }"
                :placeholder="$t('ui-example-360')"
              />

              <div
                v-if="slideForm.errors.video_duration"
                class="invalid-feedback d-block"
              >
                {{ slideForm.errors.video_duration }}
              </div>
            </div>

            <div v-if="slideForm.lesson_type === 'file'" class="mb-3">
              <label class="form-label">{{ $t('ui-file') }}</label>

              <div class="file-upload-card">
                <div class="file-upload-icon">
                  <i class="ri-upload-cloud-2-line"></i>
                </div>

                <div class="flex-grow-1 min-width-0">
                  <label for="slideFileInput" class="file-upload-title">
                    {{ selectedFileName || 'Choose file' }}
                  </label>

                  <p class="text-muted mb-0">
                    {{ $t('ui-max-size-10-mb') }}
                  </p>
                </div>

                <label
                  for="slideFileInput"
                  class="btn btn-soft-primary btn-sm file-upload-btn mb-0"
                >
                  {{ $t('ui-browse') }}
                </label>

                <input
                  id="slideFileInput"
                  type="file"
                  class="d-none"
                  @change="handleFileChange"
                />
              </div>

              <div v-if="slideForm.file_name" class="mt-2 small text-muted">
                {{ $t('ui-current-file') }}
                <strong>{{ slideForm.file_name }}</strong>
                <span v-if="slideForm.file_size">
                  · {{ formatFileSize(slideForm.file_size) }}
                </span>
              </div>

              <div v-if="slideForm.errors.file" class="invalid-feedback d-block">
                {{ slideForm.errors.file }}
              </div>
            </div>

            <div v-if="slideForm.lesson_type === 'link'" class="mb-3">
              <label class="form-label">{{ $t('ui-external-url') }}</label>

              <input
                v-model="slideForm.external_url"
                type="text"
                class="form-control modern-input"
                :class="{ 'is-invalid': slideForm.errors.external_url }"
                placeholder="https://example.com/resource"
              />

              <div
                v-if="slideForm.errors.external_url"
                class="invalid-feedback d-block"
              >
                {{ slideForm.errors.external_url }}
              </div>
            </div>

            <div v-if="slideForm.lesson_type === 'quiz'" class="mb-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                  <label class="form-label mb-1">{{ $t('ui-quiz-questions') }}</label>
                  <p class="text-muted small mb-0">
                    {{ $t('ui-add-1-to-3-qcm-or-true-false-questions') }}
                  </p>
                </div>

                <BButton
                  type="button"
                  variant="soft-primary"
                  size="sm"
                  :disabled="slideForm.questions.length >= 3"
                  @click="addQuestion"
                >
                  <i class="ri-add-line me-1"></i>
                  {{ $t('ui-add') }}
                </BButton>
              </div>

              <div
                v-for="(questionItem, questionIndex) in slideForm.questions"
                :key="questionIndex"
                class="quiz-question-card mb-3"
              >
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">
                    <i class="ri-question-answer-line me-1"></i>
                    {{ $t('t-question') }} {{ questionIndex + 1 }}
                  </h6>

                  <BButton
                    type="button"
                    variant="soft-danger"
                    size="sm"
                    :disabled="slideForm.questions.length <= 1"
                    @click="removeQuestion(questionIndex)"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </BButton>
                </div>

                <div class="mb-3">
                  <label class="form-label">
                    {{ $t('ui-question-type') }} <span class="text-danger">*</span>
                  </label>

                  <div class="slide-options">
                    <button
                      type="button"
                      class="tag-btn"
                      :class="{ active: questionItem.question_type === 'qcm' }"
                      @click="setQuestionType(questionItem, 'qcm')"
                    >
                      <i class="ri-list-check-3 me-1"></i>
                      {{ $t('ui-qcm') }}
                    </button>

                    <button
                      type="button"
                      class="tag-btn"
                      :class="{ active: questionItem.question_type === 'true_false' }"
                      @click="setQuestionType(questionItem, 'true_false')"
                    >
                      <i class="ri-checkbox-circle-line me-1"></i>
                      {{ $t('ui-true-false') }}
                    </button>
                  </div>

                  <div
                    v-if="questionError(questionIndex, 'question_type')"
                    class="invalid-feedback d-block"
                  >
                    {{ questionError(questionIndex, 'question_type') }}
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">
                    {{ $t('t-question') }} <span class="text-danger">*</span>
                  </label>

                  <textarea
                    v-model="questionItem.question"
                    rows="3"
                    class="form-control modern-textarea"
                    :class="{
                      'is-invalid': questionError(questionIndex, 'question'),
                    }"
                    :placeholder="$t('ui-write-the-question')"
                  ></textarea>

                  <div
                    v-if="questionError(questionIndex, 'question')"
                    class="invalid-feedback d-block"
                  >
                    {{ questionError(questionIndex, 'question') }}
                  </div>
                </div>

                <template v-if="questionItem.question_type === 'qcm'">
                  <div class="mb-2">
                    <span class="mini-badge info">
                      <i class="ri-list-check-3 me-1"></i>
                      {{ $t('ui-multiple-choice') }}
                    </span>
                  </div>

                  <BRow class="g-2">
                    <BCol cols="12">
                      <input
                        v-model="questionItem.option_a"
                        type="text"
                        class="form-control modern-input"
                        :class="{
                          'is-invalid': questionError(questionIndex, 'option_a'),
                        }"
                        :placeholder="$t('ui-option-a-label')"
                      />

                      <div
                        v-if="questionError(questionIndex, 'option_a')"
                        class="invalid-feedback d-block"
                      >
                        {{ questionError(questionIndex, 'option_a') }}
                      </div>
                    </BCol>

                    <BCol cols="12">
                      <input
                        v-model="questionItem.option_b"
                        type="text"
                        class="form-control modern-input"
                        :class="{
                          'is-invalid': questionError(questionIndex, 'option_b'),
                        }"
                        :placeholder="$t('ui-option-b-label')"
                      />

                      <div
                        v-if="questionError(questionIndex, 'option_b')"
                        class="invalid-feedback d-block"
                      >
                        {{ questionError(questionIndex, 'option_b') }}
                      </div>
                    </BCol>

                    <BCol cols="12">
                      <input
                        v-model="questionItem.option_c"
                        type="text"
                        class="form-control modern-input"
                        :class="{
                          'is-invalid': questionError(questionIndex, 'option_c'),
                        }"
                        :placeholder="$t('ui-option-c-optional')"
                      />

                      <div
                        v-if="questionError(questionIndex, 'option_c')"
                        class="invalid-feedback d-block"
                      >
                        {{ questionError(questionIndex, 'option_c') }}
                      </div>
                    </BCol>
                  </BRow>

                  <div class="mt-3">
                    <label class="form-label">
                      {{ $t('ui-correct-answer') }} <span class="text-danger">*</span>
                    </label>

                    <select
                      v-model="questionItem.correct_answer"
                      class="form-select modern-input"
                      :class="{
                        'is-invalid': questionError(
                          questionIndex,
                          'correct_answer'
                        ),
                      }"
                    >
                      <option value="">{{ $t('ui-select-correct-answer') }}</option>
                      <option value="a">{{ $t('t-option-a') }}</option>
                      <option value="b">{{ $t('t-option-b') }}</option>
                      <option value="c">{{ $t('t-option-c') }}</option>
                    </select>

                    <div
                      v-if="questionError(questionIndex, 'correct_answer')"
                      class="invalid-feedback d-block"
                    >
                      {{ questionError(questionIndex, 'correct_answer') }}
                    </div>
                  </div>
                </template>

                <template v-else>
                  <div class="mb-2">
                    <span class="mini-badge success">
                      <i class="ri-checkbox-circle-line me-1"></i>
                      {{ $t('ui-true-false') }}
                    </span>
                  </div>

                  <label class="form-label">
                    {{ $t('ui-correct-answer') }} <span class="text-danger">*</span>
                  </label>

                  <div class="d-grid gap-2">
                    <label class="publish-card mb-0" role="button">
                      <div>
                        <h6 class="mb-1">
                          <i class="ri-checkbox-circle-line text-success me-1"></i>
                          {{ $t('t-true') }}
                        </h6>
                        <p class="text-muted mb-0">
                          {{ $t('ui-the-statement-is-correct') }}
                        </p>
                      </div>

                      <input
                        v-model="questionItem.correct_answer"
                        class="form-check-input"
                        type="radio"
                        value="true"
                        :name="`true-false-${questionIndex}`"
                      />
                    </label>

                    <label class="publish-card mb-0" role="button">
                      <div>
                        <h6 class="mb-1">
                          <i class="ri-close-circle-line text-danger me-1"></i>
                          {{ $t('t-false') }}
                        </h6>
                        <p class="text-muted mb-0">
                          {{ $t('ui-the-statement-is-incorrect') }}
                        </p>
                      </div>

                      <input
                        v-model="questionItem.correct_answer"
                        class="form-check-input"
                        type="radio"
                        value="false"
                        :name="`true-false-${questionIndex}`"
                      />
                    </label>
                  </div>

                  <div
                    v-if="questionError(questionIndex, 'correct_answer')"
                    class="invalid-feedback d-block"
                  >
                    {{ questionError(questionIndex, 'correct_answer') }}
                  </div>
                </template>

                <div class="mt-3">
                  <label class="form-label">{{ $t('ui-explanation') }}</label>

                  <textarea
                    v-model="questionItem.explanation"
                    rows="2"
                    class="form-control modern-textarea"
                    :class="{
                      'is-invalid': questionError(questionIndex, 'explanation'),
                    }"
                    :placeholder="$t('ui-optional-explanation')"
                  ></textarea>

                  <div
                    v-if="questionError(questionIndex, 'explanation')"
                    class="invalid-feedback d-block"
                  >
                    {{ questionError(questionIndex, 'explanation') }}
                  </div>
                </div>
              </div>

              <div
                v-if="slideForm.errors.questions"
                class="invalid-feedback d-block"
              >
                {{ slideForm.errors.questions }}
              </div>
            </div>

            <!-- Voice Over -->
            <div class="voice-over-card mb-3" >
              <div class="voice-over-header">
                <div>
                  <h6 class="mb-1">
                    <i class="ri-mic-line me-1"></i>
                    {{ $t('ui-voice-over') }}
                  </h6>
                  <p class="text-muted mb-0">
                    {{ $t('ui-add-narration-text-and-generate-audio-using-course-settings') }}
                  </p>
                </div>

                <div class="form-check form-switch">
                  <input
                    id="voiceOverEnabled"
                    v-model="slideForm.voice_over_enabled"
                    class="form-check-input"
                    type="checkbox"
                    :disabled="!courseVoiceEnabled"
                  />
                </div>
              </div>

              <div v-if="!courseVoiceEnabled" class="alert alert-warning py-2 mt-3 mb-0">
                <i class="ri-alert-line me-1"></i>
                {{ $t('ui-voice-over-is-disabled-in-course-settings') }}
              </div>

              <div v-if="slideForm.voice_over_enabled && courseVoiceEnabled" class="mt-3">
                <div class="publish-card mb-3">
                  <div>
                    <h6 class="mb-1">
                      <i class="ri-global-line me-1"></i>
                      {{ $t('ui-global-voice-settings') }}
                    </h6>
                    <p class="text-muted mb-0">
                      {{ $t('ui-this-slide-uses-the-course-voice-settings') }}
                    </p>
                  </div>

                  <span class="mini-badge info">
                    {{ courseVoiceLangLabel }} / {{ courseVoiceNameLabel }}
                  </span>
                </div>

                <div class="publish-card mb-3">
                  <div>
                    <h6 class="mb-1">{{ $t('ui-provider') }}</h6>
                    <p class="text-muted mb-0">
                      {{ $t('ui-tts-provider-used-to-generate-this-slide-audio') }}
                    </p>
                  </div>

                  <span class="mini-badge info">
                    {{ courseVoiceProviderLabel }}
                  </span>
                </div>

                <div class="mb-3">
                  <label class="form-label">{{ $t('ui-voice-over-text') }}</label>

                  <textarea
                    v-model="slideForm.voice_over_text"
                    rows="5"
                    class="form-control modern-textarea"
                    :class="{ 'is-invalid': slideForm.errors.voice_over_text }"
                    :placeholder="$t('ui-write-narration-text-for-this-slide')"
                  ></textarea>

                  <div
                    v-if="slideForm.errors.voice_over_text"
                    class="invalid-feedback d-block"
                  >
                    {{ slideForm.errors.voice_over_text }}
                  </div>
                </div>

                <div class="publish-card mb-3">
                  <div>
                    <h6 class="mb-1">{{ $t('ui-auto-play-label') }}</h6>
                    <p class="text-muted mb-0">
                      {{ $t('ui-this-option-is-controlled-from-course-settings') }}
                    </p>
                  </div>

                  <span class="mini-badge info">
                    {{ courseVoiceAutoPlayLabel }}
                  </span>
                </div>

                <div
                  v-if="slideForm.voice_over_audio_url"
                  class="voice-over-preview mb-3"
                >
                  <div class="voice-over-icon">
                    <i class="ri-volume-up-line"></i>
                  </div>

                  <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between gap-2 mb-2">
                      <h6 class="mb-0 text-truncate">
                        {{ slideForm.voice_over_audio_name || 'Generated audio' }}
                      </h6>

                      <span class="mini-badge success">
                        {{ courseVoiceProviderLabel }}
                      </span>
                    </div>

                    <audio
                      controls
                      class="w-100"
                      :src="slideForm.voice_over_audio_url"
                    ></audio>

                    <div class="small text-muted mt-1">
                      <span v-if="slideForm.voice_over_audio_size">
                        {{ $t('ui-size') }} {{ formatFileSize(slideForm.voice_over_audio_size) }}
                      </span>

                      <span>
                        {{ $t('ui-lang') }} {{ courseVoiceLangLabel }}
                      </span>

                      <span>
                        {{ $t('ui-voice-label') }} {{ courseVoiceNameLabel }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <BButton
                    type="button"
                    variant="soft-primary"
                    class="builder-action-btn flex-grow-1"
                    :disabled="!isEditing"
                    @click="generateVoiceOver"
                  >
                    <i class="ri-sparkling-line me-1"></i>
                    {{ $t('ui-generate-audio') }}
                  </BButton>

                  <BButton
                    v-if="slideForm.voice_over_audio_url"
                    type="button"
                    variant="soft-danger"
                    class="builder-action-btn"
                    @click="deleteVoiceOver"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </BButton>
                </div>

                <p v-if="!isEditing" class="text-muted small mt-2 mb-0">
                  {{ $t('ui-save-the-slide-first-then-generate-the-audio') }}
                </p>
              </div>
            </div>

            <div class="publish-card mb-3">
              <div>
                <h6 class="mb-1">{{ $t('t-preview') }}</h6>
                <p class="text-muted mb-0">{{ $t('ui-allow-learners-to-preview-this-slide') }}</p>
              </div>

              <div class="form-check form-switch">
                <input
                  id="slidePreview"
                  v-model="slideForm.is_preview"
                  class="form-check-input"
                  type="checkbox"
                />
              </div>
            </div>

            <div class="publish-card mb-3">
              <div>
                <h6 class="mb-1">{{ $t('ui-mandatory') }}</h6>
                <p class="text-muted mb-0">{{ $t('ui-learners-must-complete-this-slide') }}</p>
              </div>

              <div class="form-check form-switch">
                <input
                  id="slideMandatory"
                  v-model="slideForm.is_mandatory"
                  class="form-check-input"
                  type="checkbox"
                />
              </div>
            </div>

            <div class="publish-card mb-3">
              <div>
                <h6 class="mb-1">{{ $t('ui-downloadable') }}</h6>
                <p class="text-muted mb-0">{{ $t('ui-allow-file-download-when-available') }}</p>
              </div>

              <div class="form-check form-switch">
                <input
                  id="slideDownloadable"
                  v-model="slideForm.is_downloadable"
                  class="form-check-input"
                  type="checkbox"
                />
              </div>
            </div>

            <div class="publish-card mb-3">
              <div>
                <h6 class="mb-1">{{ $t('t-active') }}</h6>
                <p class="text-muted mb-0">{{ $t('ui-show-this-slide-in-the-course') }}</p>
              </div>

              <div class="form-check form-switch">
                <input
                  id="slideActive"
                  v-model="slideForm.is_active"
                  class="form-check-input"
                  type="checkbox"
                />
              </div>
            </div>

            <div class="d-flex gap-2">
              <BButton
                type="submit"
                variant="primary"
                class="builder-action-btn flex-grow-1"
                :disabled="slideForm.processing"
              >
                <span
                  v-if="slideForm.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                {{ isEditing ? 'Update Slide' : 'Add Slide' }}
              </BButton>

              <BButton
                v-if="isEditing"
                type="button"
                variant="light"
                class="builder-action-btn"
                @click="resetSlideForm"
              >
                {{ $t('ui-cancel') }}
              </BButton>
            </div>
          </form>
        </div>
      </BCol>
    </BRow>
  </div>
</template>