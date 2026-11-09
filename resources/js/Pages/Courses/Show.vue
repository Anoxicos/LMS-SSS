<script setup>
import { formatCourseLabel } from '@/composables/course-labels'
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

import './course-viewer.css'

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
})

const currentChapterIndex = ref(0)
const currentSlideIndex = ref(0)
const currentQuestionIndex = ref(0)
const voiceOverAudio = ref(null)

const chapters = computed(() => {
  return (props.course.chapters || []).filter((chapter) => {
    return Array.isArray(chapter.slides) && chapter.slides.length > 0
  })
})

const currentChapter = computed(() => {
  return chapters.value[currentChapterIndex.value] || null
})

const slides = computed(() => {
  return currentChapter.value?.slides || []
})

const currentSlide = computed(() => {
  return slides.value[currentSlideIndex.value] || null
})

const currentSlideQuestions = computed(() => {
  return currentSlide.value?.questions || []
})

const currentQuestion = computed(() => {
  return currentSlideQuestions.value[currentQuestionIndex.value] || null
})

const totalQuestions = computed(() => {
  return currentSlideQuestions.value.length
})

const hasQuizQuestions = computed(() => {
  return currentSlide.value?.lesson_type === 'quiz' && totalQuestions.value > 0
})

const isFirstQuestion = computed(() => {
  return currentQuestionIndex.value === 0
})

const isLastQuestion = computed(() => {
  return currentQuestionIndex.value === totalQuestions.value - 1
})

const totalSlides = computed(() => {
  return chapters.value.reduce((total, chapter) => {
    return total + (chapter.slides?.length || 0)
  }, 0)
})

const currentGlobalSlideNumber = computed(() => {
  if (!totalSlides.value || !currentSlide.value) {
    return 0
  }

  let count = 0

  for (let index = 0; index < currentChapterIndex.value; index++) {
    count += chapters.value[index]?.slides?.length || 0
  }

  const currentNumber = count + currentSlideIndex.value + 1

  return Math.min(currentNumber, totalSlides.value)
})

const progressPercent = computed(() => {
  if (!totalSlides.value || !currentSlide.value) {
    return 0
  }

  const progress = Math.round(
    (currentGlobalSlideNumber.value / totalSlides.value) * 100
  )

  return Math.min(100, Math.max(0, progress))
})

const courseImage = computed(() => {
  if (props.course.image_url) {
    return props.course.image_url
  }

  if (props.course.image) {
    return `/storage/${props.course.image}`
  }

  return null
})

const courseInitials = computed(() => {
  return String(props.course.title || 'CO')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
})

const formattedCreatedAt = computed(() => {
  if (!props.course.created_at) return '-'

  return new Date(props.course.created_at).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
})

const formattedPrice = computed(() => {
  const price = Number(props.course.price || 0)

  if (props.course.type !== 'paid' || price === 0) {
    return t('t-free')
  }

  return `${price.toFixed(2)} ${props.course.currency || ''}`
})

const courseStatusLabel = computed(() => {
  return props.course.is_published ? 'Published' : 'Draft'
})

const courseTypeLabel = computed(() => {
  return props.course.type === 'paid' ? t('ui-paid') : props.course.type ? t('t-free') : '-'
})

const voiceOverLabel = computed(() => {
  return props.course.support_voice_over ? t('ui-supported') : t('ui-not-supported')
})

const voiceOverAutoPlay = computed(() => {
  return Boolean(props.course.voice_over_auto_play)
})

const courseVoiceProviderLabel = computed(() => {
  return props.course.voice_over_provider || 'voicerss'
})

const courseVoiceLangLabel = computed(() => {
  const labels = {
    'en-us': 'English - US',
    'en-gb': 'English - UK',
    'fr-fr': 'French',
    'ar-sa': 'Arabic',
    'es-es': 'Spanish',
  }

  return labels[props.course.voice_over_lang] || props.course.voice_over_lang || 'English - US'
})

const courseVoiceNameLabel = computed(() => {
  return props.course.voice_over_voice || 'default'
})

const hasCurrentSlideVoiceOver = computed(() => {
  return Boolean(
    props.course.support_voice_over &&
      currentSlide.value &&
      currentSlide.value.voice_over_enabled &&
      currentSlide.value.voice_over_audio_url
  )
})

const currentSlideVoiceOverName = computed(() => {
  return currentSlide.value?.voice_over_audio_name || 'Generated voice over'
})

const selectChapter = (chapterIndex) => {
  currentChapterIndex.value = chapterIndex
  currentSlideIndex.value = 0
  currentQuestionIndex.value = 0
}

const selectSlide = (slideIndex) => {
  currentSlideIndex.value = slideIndex
  currentQuestionIndex.value = 0
}

const goNext = () => {
  if (!currentChapter.value) return

  currentQuestionIndex.value = 0

  if (currentSlideIndex.value < slides.value.length - 1) {
    currentSlideIndex.value++
    return
  }

  if (currentChapterIndex.value < chapters.value.length - 1) {
    currentChapterIndex.value++
    currentSlideIndex.value = 0
  }
}

const goPrevious = () => {
  currentQuestionIndex.value = 0

  if (currentSlideIndex.value > 0) {
    currentSlideIndex.value--
    return
  }

  if (currentChapterIndex.value > 0) {
    currentChapterIndex.value--

    const previousChapterSlides =
      chapters.value[currentChapterIndex.value]?.slides || []

    currentSlideIndex.value = previousChapterSlides.length
      ? previousChapterSlides.length - 1
      : 0
  }
}

const goNextQuestion = () => {
  if (currentQuestionIndex.value < totalQuestions.value - 1) {
    currentQuestionIndex.value++
  }
}

const goPreviousQuestion = () => {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--
  }
}

const selectQuestion = (index) => {
  currentQuestionIndex.value = index
}

const isFirstSlide = computed(() => {
  return currentChapterIndex.value === 0 && currentSlideIndex.value === 0
})

const isLastSlide = computed(() => {
  return (
    currentChapterIndex.value === chapters.value.length - 1 &&
    currentSlideIndex.value === slides.value.length - 1
  )
})

const getSlideIcon = (type) => {
  if (type === 'video') return 'ri-video-line'
  if (type === 'file') return 'ri-file-3-line'
  if (type === 'link') return 'ri-links-line'
  if (type === 'quiz') return 'ri-question-answer-line'

  return 'ri-text-snippet'
}

const normalizeQuestionType = (question) => {
  return question?.question_type === 'true_false'
    ? 'true_false'
    : 'qcm'
}

const getQuestionTypeLabel = (question) => {
  return normalizeQuestionType(question) === 'true_false'
    ? 'True / False'
    : 'QCM'
}

const getQuestionTypeIcon = (question) => {
  return normalizeQuestionType(question) === 'true_false'
    ? 'ri-toggle-line'
    : 'ri-list-check-3'
}

const getQuestionTypeBadgeClass = (question) => {
  return normalizeQuestionType(question) === 'true_false'
    ? 'true-false'
    : 'qcm'
}

const getCorrectAnswerLabel = (answer) => {
  const normalizedAnswer = String(answer || '').toLowerCase()

  if (normalizedAnswer === 'a') return 'Option A'
  if (normalizedAnswer === 'b') return 'Option B'
  if (normalizedAnswer === 'c') return 'Option C'
  if (normalizedAnswer === 'true') return 'True'
  if (normalizedAnswer === 'false') return 'False'

  return '-'
}

const getCorrectAnswerText = (question) => {
  if (!question) return '-'

  const questionType = normalizeQuestionType(question)
  const correctAnswer = String(question.correct_answer || '').toLowerCase()

  if (questionType === 'true_false') {
    if (correctAnswer === 'true') return 'True'
    if (correctAnswer === 'false') return 'False'

    return '-'
  }

  if (correctAnswer === 'a') return question.option_a || '-'
  if (correctAnswer === 'b') return question.option_b || '-'
  if (correctAnswer === 'c') return question.option_c || '-'

  return '-'
}

const getQuestionOptions = (question) => {
  if (!question) return []

  if (normalizeQuestionType(question) === 'true_false') {
    return [
      {
        key: 'true',
        label: 'T',
        value: 'True',
      },
      {
        key: 'false',
        label: 'F',
        value: 'False',
      },
    ]
  }

  return [
    {
      key: 'a',
      label: 'A',
      value: question.option_a,
    },
    {
      key: 'b',
      label: 'B',
      value: question.option_b,
    },
    {
      key: 'c',
      label: 'C',
      value: question.option_c,
    },
  ].filter((option) => Boolean(option.value))
}

const isImageFile = (slide) => {
  if (!slide) return false

  const fileType = String(slide.file_type || '').toLowerCase()
  const fileName = String(slide.file_name || '').toLowerCase()

  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']

  return imageExtensions.some((ext) => {
    return fileType === ext || fileName.endsWith(`.${ext}`)
  })
}

const getVideoEmbedUrl = (url) => {
  if (!url) return ''

  try {
    const parsedUrl = new URL(url)

    if (parsedUrl.hostname.includes('youtube.com')) {
      const videoId = parsedUrl.searchParams.get('v')

      if (videoId) {
        return `https://www.youtube.com/embed/${videoId}`
      }
    }

    if (parsedUrl.hostname.includes('youtu.be')) {
      const videoId = parsedUrl.pathname.replace('/', '')

      if (videoId) {
        return `https://www.youtube.com/embed/${videoId}`
      }
    }

    return url
  } catch (error) {
    return url
  }
}

const resetVoiceOverAudio = () => {
  if (!voiceOverAudio.value) return

  voiceOverAudio.value.pause()
  voiceOverAudio.value.currentTime = 0
}

const playVoiceOverIfNeeded = async () => {
  await nextTick()

  if (!voiceOverAudio.value) return
  if (!hasCurrentSlideVoiceOver.value) return
  if (!voiceOverAutoPlay.value) return

  try {
    voiceOverAudio.value.currentTime = 0
    await voiceOverAudio.value.play()
  } catch (error) {
    // Le navigateur peut bloquer autoplay avant une interaction utilisateur.
  }
}

watch(
  chapters,
  (availableChapters) => {
    if (!availableChapters.length) {
      currentChapterIndex.value = 0
      currentSlideIndex.value = 0
      currentQuestionIndex.value = 0

      return
    }

    if (currentChapterIndex.value >= availableChapters.length) {
      currentChapterIndex.value = 0
      currentSlideIndex.value = 0
      currentQuestionIndex.value = 0

      return
    }

    const availableSlides =
      availableChapters[currentChapterIndex.value]?.slides || []

    if (currentSlideIndex.value >= availableSlides.length) {
      currentSlideIndex.value = 0
      currentQuestionIndex.value = 0
    }
  },
  {
    immediate: true,
    deep: true,
  }
)


watch(
  () => currentSlide.value?.id,
  () => {
    currentQuestionIndex.value = 0
    resetVoiceOverAudio()
    playVoiceOverIfNeeded()
  }
)

watch(
  () => currentSlide.value?.voice_over_audio_url,
  () => {
    resetVoiceOverAudio()
    playVoiceOverIfNeeded()
  }
)

const blockActions = (event) => {
  event.preventDefault()
}

const blockKeyboardShortcuts = (event) => {
  const key = event.key.toLowerCase()

  if (
    event.ctrlKey &&
    ['c', 'v', 'x', 's', 'u', 'p', 'a'].includes(key)
  ) {
    event.preventDefault()
  }

  if (event.key === 'F12') {
    event.preventDefault()
  }

  if (
    event.ctrlKey &&
    event.shiftKey &&
    ['i', 'j', 'c'].includes(key)
  ) {
    event.preventDefault()
  }

  if (event.key === 'PrintScreen') {
    event.preventDefault()

    if (navigator.clipboard) {
      navigator.clipboard.writeText('')
    }
  }
}

onMounted(() => {
  document.addEventListener('contextmenu', blockActions)
  document.addEventListener('copy', blockActions)
  document.addEventListener('cut', blockActions)
  document.addEventListener('paste', blockActions)
  document.addEventListener('dragstart', blockActions)
  document.addEventListener('keydown', blockKeyboardShortcuts)

  playVoiceOverIfNeeded()
})

onBeforeUnmount(() => {
  resetVoiceOverAudio()

  document.removeEventListener('contextmenu', blockActions)
  document.removeEventListener('copy', blockActions)
  document.removeEventListener('cut', blockActions)
  document.removeEventListener('paste', blockActions)
  document.removeEventListener('dragstart', blockActions)
  document.removeEventListener('keydown', blockKeyboardShortcuts)
})
</script>

<template>
  <Layout>
    <Head :title="course.title" />
    <PageHeader :title="$t('ui-course-details')" :page-title="$t('ui-courses')" />

    <div class="course-viewer-page protected-content">
      <div class="course-admin-hero">
        <div class="course-admin-hero__body">
          <div class="course-admin-hero__cover">
            <img
              v-if="courseImage"
              :src="courseImage"
              :alt="course.title"
              draggable="false"
              @contextmenu.prevent
            />

            <div v-else class="course-admin-hero__fallback">
              {{ courseInitials }}
            </div>
          </div>

          <div class="course-admin-hero__content">
            <div class="course-admin-badges">
              <span class="course-admin-badge">
                <i class="ri-price-tag-3-line"></i>
                {{ course.category?.name || 'Course' }}
                <template v-if="course.ref">
                  <span class="course-admin-badge__separator">·</span>
                  <i class="ri-hashtag"></i>
                  {{ course.ref }}
                </template>
              </span>

              <span
                class="course-admin-badge"
                :class="course.is_published ? 'course-admin-badge--success' : 'course-admin-badge--warning'"
              >
                <i :class="course.is_published ? 'ri-checkbox-circle-line' : 'ri-draft-line'"></i>
                {{ courseStatusLabel }}
              </span>

              <span
                class="course-admin-badge"
                :class="course.support_voice_over ? 'course-admin-badge--info' : 'course-admin-badge--muted'"
              >
                <i class="ri-mic-line"></i>
                {{ voiceOverLabel }}
              </span>

              <span
                v-if="course.support_voice_over"
                class="course-admin-badge"
                :class="voiceOverAutoPlay ? 'course-admin-badge--info' : 'course-admin-badge--muted'"
              >
                <i :class="voiceOverAutoPlay ? 'ri-play-circle-line' : 'ri-pause-circle-line'"></i>
                {{ voiceOverAutoPlay ? 'Auto Play' : 'Manual Play' }}
              </span>

              <span
                v-if="course.support_voice_over"
                class="course-admin-badge course-admin-badge--info"
              >
                <i class="ri-user-voice-line"></i>
                {{ courseVoiceLangLabel }} / {{ courseVoiceNameLabel }}
              </span>

              <span class="course-admin-badge">
                <i class="ri-calendar-line"></i>
                {{ formattedCreatedAt }}
              </span>
            </div>

            <h2>{{ course.title }}</h2>

            <p>
              {{ course.description || 'No description available for this course.' }}
            </p>

            <div class="course-admin-stats">
              <div class="course-admin-stat">
                <i class="ri-graduation-cap-line"></i>

                <div>
                  <span>{{ $t('ui-type') }}</span>
                  <strong>{{ courseTypeLabel }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-computer-line"></i>

                <div>
                  <span>{{ $t('t-training') }}</span>
                  <strong>{{ formatCourseLabel(course.training_type) }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-bar-chart-line"></i>

                <div>
                  <span>{{ $t('ui-level') }}</span>
                  <strong>{{ formatCourseLabel(course.skill_level) }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-translate-2"></i>

                <div>
                  <span>{{ $t('t-language') }}</span>
                  <strong>{{ formatCourseLabel(course.language) }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-money-dollar-circle-line"></i>

                <div>
                  <span>{{ $t('t-price') }}</span>
                  <strong>{{ formattedPrice }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-list-check-2"></i>

                <div>
                  <span>{{ $t('t-chapters') }}</span>
                  <strong>{{ chapters.length }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-slideshow-line"></i>

                <div>
                  <span>{{ $t('ui-slides') }}</span>
                  <strong>{{ totalSlides }}</strong>
                </div>
              </div>

              <div class="course-admin-stat">
                <i class="ri-line-chart-line"></i>

                <div>
                  <span>{{ $t('t-progress') }}</span>
                  <strong>{{ progressPercent }}%</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="course-admin-hero__actions">
          <Link
            :href="route('courses.index')"
            class="course-admin-action course-admin-action--light"
          >
            <i class="ri-arrow-left-line me-1"></i>
            {{ $t('t-back') }}
          </Link>

          <a
            v-if="course.syllabus_url"
            :href="course.syllabus_url"
            target="_blank"
            rel="noopener noreferrer"
            class="course-admin-action course-admin-action--outline course-admin-action--syllabus"
            :title="$t('ui-open-syllabus-pdf')"
          >
            <i class="ri-file-pdf-2-line me-1"></i>
              {{ $t('t-syllabus') }}
          </a>

          <Link
            v-if="course.hashid"
            :href="route('courses.edit', course.hashid)"
            class="course-admin-action course-admin-action--outline"
          >
            <i class="ri-edit-2-line me-1"></i>
            {{ $t('t-edit') }}
          </Link>
        </div>
      </div>

      <div class="course-admin-overview">
        <div class="course-admin-overview__card">
          <div class="course-admin-overview__icon">
            <i class="ri-focus-3-line"></i>
          </div>

          <div>
            <span>{{ $t('t-course-focus') }}</span>
            <strong>{{ course.course_focus || 'Not specified' }}</strong>
          </div>
        </div>

        <div class="course-admin-overview__card">
          <div class="course-admin-overview__icon">
            <i class="ri-mic-line"></i>
          </div>

          <div>
            <span>{{ $t('ui-voice-over') }}</span>
            <strong>{{ voiceOverLabel }}</strong>
          </div>
        </div>

        <div class="course-admin-overview__card">
          <div class="course-admin-overview__icon">
            <i class="ri-play-circle-line"></i>
          </div>

          <div>
            <span>{{ $t('ui-audio-mode') }}</span>
            <strong>
              {{
                course.support_voice_over
                  ? voiceOverAutoPlay
                    ? 'Auto Play'
                    : 'Manual Play'
                  : 'Disabled'
              }}
            </strong>
          </div>
        </div>

        <div class="course-admin-overview__card">
          <div class="course-admin-overview__icon">
            <i class="ri-user-voice-line"></i>
          </div>

          <div>
            <span>{{ $t('ui-voice') }}</span>
            <strong>
              {{
                course.support_voice_over
                  ? `${courseVoiceLangLabel} / ${courseVoiceNameLabel}`
                  : 'Disabled'
              }}
            </strong>
          </div>
        </div>
      </div>

      <BRow class="g-4">
        <BCol lg="4" xl="3">
          <div class="chapters-sidebar">
            <div class="sidebar-title">
              <div>
                <h6>{{ $t('ui-course-content') }}</h6>
                <span>{{ totalSlides }} {{ $t('ui-total-slides-label') }}</span>
              </div>
            </div>

            <div class="viewer-progress">
              <div class="viewer-progress-info">
                <span>{{ $t('t-progress') }}</span>
                <strong>{{ progressPercent }}%</strong>
              </div>

              <div class="viewer-progress-bar">
                <div :style="{ width: `${progressPercent}%` }"></div>
              </div>
            </div>

            <div
              v-for="(chapter, chapterIndex) in chapters"
              :key="chapter.id"
              class="chapter-block"
            >
              <div
                class="chapter-item"
                :class="{ active: currentChapterIndex === chapterIndex }"
                @click="selectChapter(chapterIndex)"
              >
                <div class="chapter-number">
                  {{ chapterIndex + 1 }}
                </div>

                <div class="min-width-0 flex-grow-1">
                  <h6>{{ chapter.title }}</h6>

                  <p>
                    {{ chapter.slides?.length || 0 }}
                    {{ $t('ui-slide') }}{{ Number(chapter.slides?.length || 0) > 1 ? 's' : '' }}
                  </p>
                </div>
              </div>

              <div
                v-if="currentChapterIndex === chapterIndex"
                class="chapter-slides-mini"
              >
                <button
                  v-for="(slide, slideIndex) in chapter.slides"
                  :key="slide.id"
                  type="button"
                  class="mini-slide-item"
                  :class="{ active: currentSlideIndex === slideIndex }"
                  @click="selectSlide(slideIndex)"
                >
                  <i :class="getSlideIcon(slide.lesson_type)"></i>

                  <span>{{ slide.title }}</span>

                  <i
                    v-if="slide.voice_over_enabled && slide.voice_over_audio_url"
                    class="ri-volume-up-line mini-voice-icon"
                    :title="$t('ui-voice-over-available')"
                  ></i>

                  <i
                    v-if="slide.lesson_type === 'quiz'"
                    class="ri-question-answer-line mini-voice-icon"
                    :title="$t('ui-quiz-slide')"
                  ></i>
                </button>
              </div>
            </div>
          </div>
        </BCol>

        <BCol lg="8" xl="9">
          <div v-if="currentSlide" class="slide-view-card">
            <div class="slide-topbar">
              <div class="slide-type">
                <i :class="getSlideIcon(currentSlide.lesson_type)"></i>
                {{ currentSlide.lesson_type || 'text' }}
              </div>

              <div class="slide-progress-text">
                {{ $t('ui-slide') }} {{ currentGlobalSlideNumber }} / {{ totalSlides }}
              </div>
            </div>

            <div class="chapter-context">
              <span>{{ currentChapter?.title || 'Chapter' }}</span>
              <h5>{{ currentSlide.title }}</h5>
            </div>

            <div
              v-if="hasCurrentSlideVoiceOver"
              class="slide-voice-over-card"
            >
              <div class="voice-over-main">
                <div class="voice-over-icon">
                  <i class="ri-volume-up-line"></i>
                </div>

                <div class="flex-grow-1 min-width-0">
                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="min-width-0">
                      <h6 class="mb-1 text-truncate">
                        {{ currentSlideVoiceOverName }}
                      </h6>

                      <div class="voice-over-meta">
                        <span>
                          <i class="ri-translate-2 me-1"></i>
                          {{ courseVoiceLangLabel }}
                        </span>

                        <span>
                          <i class="ri-user-voice-line me-1"></i>
                          {{ courseVoiceNameLabel }}
                        </span>

                        <span>
                          <i class="ri-sparkling-line me-1"></i>
                          {{ courseVoiceProviderLabel }}
                        </span>
                      </div>
                    </div>

                    <span
                      class="voice-mode-badge"
                      :class="voiceOverAutoPlay ? 'auto' : 'manual'"
                    >
                      <i :class="voiceOverAutoPlay ? 'ri-play-circle-line' : 'ri-pause-circle-line'"></i>
                      {{ voiceOverAutoPlay ? 'Auto Play' : 'Manual Play' }}
                    </span>
                  </div>

                  <audio
                    ref="voiceOverAudio"
                    class="voice-over-audio"
                    controls
                    controlsList="nodownload"
                    :src="currentSlide.voice_over_audio_url"
                    @contextmenu.prevent
                  ></audio>
                </div>
              </div>
            </div>

            <div
              v-else-if="course.support_voice_over"
              class="slide-voice-over-empty"
            >
              <i class="ri-mic-off-line"></i>
              <span>{{ $t('ui-no-voice-over-for-this-slide') }}</span>
            </div>

            <div class="slide-content-box">
              <div
                v-if="currentSlide.lesson_type === 'text'"
                class="slide-rich-content protected-text"
                v-html="currentSlide.content || 'No content available.'"
              ></div>

              <div v-else-if="currentSlide.lesson_type === 'video'" class="video-box">
                <iframe
                  v-if="currentSlide.video_url"
                  :src="getVideoEmbedUrl(currentSlide.video_url)"
                  frameborder="0"
                  allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen
                  sandbox="allow-same-origin allow-scripts allow-presentation"
                ></iframe>

                <div v-else class="empty-slide-content">
                  <i class="ri-video-off-line"></i>
                  <p>{{ $t('t-no-video-url') }}</p>
                </div>

                <div
                  v-if="currentSlide.content"
                  class="slide-rich-content mt-3 protected-text"
                  v-html="currentSlide.content"
                ></div>
              </div>

              <div v-else-if="currentSlide.lesson_type === 'file'">
                <div
                  v-if="isImageFile(currentSlide)"
                  class="image-preview-box protected-media"
                >
                  <img
                    :src="currentSlide.file_url"
                    :alt="currentSlide.file_name || currentSlide.title"
                    draggable="false"
                    @contextmenu.prevent
                  />
                </div>

                <div v-else class="file-box protected-file-box">
                  <div class="file-icon">
                    <i class="ri-file-lock-line"></i>
                  </div>

                  <div class="flex-grow-1">
                    <h6>{{ currentSlide.file_name || 'Protected file' }}</h6>

                    <p class="text-muted mb-0">
                      {{ $t('ui-download-is-disabled-for-this-course-file') }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-else-if="currentSlide.lesson_type === 'link'" class="file-box">
                <div class="file-icon">
                  <i class="ri-links-line"></i>
                </div>

                <div>
                  <h6>{{ $t('ui-external-resource') }}</h6>

                  <a
                    v-if="currentSlide.external_url"
                    :href="currentSlide.external_url"
                    target="_blank"
                    class="btn btn-primary btn-sm"
                  >
                    <i class="ri-external-link-line me-1"></i>
                    {{ $t('ui-open-link') }}
                  </a>

                  <p v-else class="text-muted mb-0">
                    {{ $t('t-no-external-url') }}
                  </p>
                </div>
              </div>

              <div v-else-if="currentSlide.lesson_type === 'quiz'" class="admin-quiz-box">
                <div class="admin-quiz-header">
                  <div>
                    <span class="admin-quiz-kicker">
                      <i class="ri-question-answer-line"></i>
                      {{ $t('ui-quiz-structure') }}
                    </span>

                    <h5>{{ $t('ui-comprehension-questions') }}</h5>
                  </div>

                  <div class="admin-quiz-count">
                    <span>{{ $t('ui-questions') }}</span>
                    <strong>{{ totalQuestions }}</strong>
                  </div>
                </div>

                <div v-if="!hasQuizQuestions" class="empty-slide-content">
                  <i class="ri-question-line"></i>
                  <p>{{ $t('ui-no-questions-available-for-this-quiz-slide') }}</p>
                </div>

                <div v-else>
                  <div class="admin-question-progress">
                    <span>
                      {{ $t('t-question') }} {{ currentQuestionIndex + 1 }} {{ $t('ui-of') }} {{ totalQuestions }}
                    </span>

                    <div class="admin-question-dots">
                      <button
                        v-for="(question, index) in currentSlideQuestions"
                        :key="question.id"
                        type="button"
                        :class="{ active: currentQuestionIndex === index }"
                        @click="selectQuestion(index)"
                      >
                        {{ index + 1 }}
                      </button>
                    </div>
                  </div>

                  <div class="admin-question-card">
                    <div class="admin-question-title">
                      <div class="admin-question-number">
                        {{ currentQuestionIndex + 1 }}
                      </div>

                      <div class="flex-grow-1 min-width-0">
                        <div class="admin-question-heading-meta">
                          <span>{{ $t('t-question') }}</span>

                          <span
                            class="admin-question-type-badge"
                            :class="getQuestionTypeBadgeClass(currentQuestion)"
                          >
                            <i :class="getQuestionTypeIcon(currentQuestion)"></i>
                            {{ getQuestionTypeLabel(currentQuestion) }}
                          </span>
                        </div>

                        <h6>{{ currentQuestion.question }}</h6>
                      </div>
                    </div>

                    <div
                      class="admin-options-list"
                      :class="{
                        'true-false-options':
                          normalizeQuestionType(currentQuestion) === 'true_false',
                      }"
                    >
                      <div
                        v-for="option in getQuestionOptions(currentQuestion)"
                        :key="option.key"
                        class="admin-option-row"
                        :class="{
                          correct:
                            String(currentQuestion.correct_answer || '').toLowerCase() ===
                            option.key,
                        }"
                      >
                        <div class="admin-option-letter">
                          {{ option.label }}
                        </div>

                        <div class="admin-option-text">
                          {{ option.value }}
                        </div>

                        <div
                          v-if="
                            String(currentQuestion.correct_answer || '').toLowerCase() ===
                            option.key
                          "
                          class="admin-correct-badge"
                        >
                          <i class="ri-checkbox-circle-line"></i>
                          {{ $t('t-correct') }}
                        </div>
                      </div>
                    </div>

                    <div
                      v-if="currentQuestion.explanation"
                      class="admin-explanation-box"
                    >
                      <span>
                        <i class="ri-lightbulb-line"></i>
                        {{ $t('ui-explanation') }}
                      </span>

                      <p>{{ currentQuestion.explanation }}</p>
                    </div>
                  </div>

                  <div class="admin-question-navigation">
                    <BButton
                      variant="light"
                      class="nav-slide-btn"
                      :disabled="isFirstQuestion"
                      @click="goPreviousQuestion"
                    >
                      <i class="ri-arrow-left-line me-1"></i>
                      {{ $t('ui-previous-question') }}
                    </BButton>

                    <BButton
                      variant="primary"
                      class="nav-slide-btn"
                      :disabled="isLastQuestion"
                      @click="goNextQuestion"
                    >
                      {{ $t('ui-next-question') }}
                      <i class="ri-arrow-right-line ms-1"></i>
                    </BButton>
                  </div>
                </div>
              </div>

              <div v-else class="slide-rich-content protected-text">
                {{ currentSlide.content || 'No content available.' }}
              </div>
            </div>

            <div class="slide-navigation">
              <BButton
                variant="light"
                class="nav-slide-btn"
                :disabled="isFirstSlide"
                @click="goPrevious"
              >
                <i class="ri-arrow-left-line me-1"></i>
                {{ $t('ui-previous-slide') }}
              </BButton>

              <div class="slide-dots">
                <button
                  v-for="(slide, index) in slides"
                  :key="slide.id"
                  type="button"
                  :class="{ active: currentSlideIndex === index }"
                  :aria-label="$t('ui-go-to-slide-number', { number: index + 1 })"
                  :aria-pressed="currentSlideIndex === index"
                  @click="selectSlide(index)"
                ></button>
              </div>

              <BButton
                variant="primary"
                class="nav-slide-btn"
                :disabled="isLastSlide"
                @click="goNext"
              >
                {{ $t('ui-next-slide') }}
                <i class="ri-arrow-right-line ms-1"></i>
              </BButton>
            </div>
          </div>

          <div v-else class="empty-viewer">
            <i class="ri-slideshow-line"></i>
            <h5>{{ $t('ui-no-slides-available') }}</h5>
            <p>{{ $t('ui-this-course-does-not-have-slides-yet') }}</p>
          </div>
        </BCol>
      </BRow>
    </div>
  </Layout>
</template>


<style scoped>
/* =========================================================
   PAGE
========================================================= */

.course-viewer-page,
.course-viewer-page * {
  box-sizing: border-box;
}

.course-viewer-page {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

/* =========================================================
   HERO DU COURS
   Container queries = adaptation selon la largeur réellement
   disponible après la sidebar de l'application.
========================================================= */

.course-admin-hero {
  container-name: courseAdminHero;
  container-type: inline-size;

  position: relative;
  isolation: isolate;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  padding: 28px;
  overflow: hidden;

  border-radius: 24px;
  background: linear-gradient(
    115deg,
    #405189 0%,
    #5e70ef 54%,
    #16b5a8 100%
  );
  box-shadow: 0 18px 40px rgba(48, 61, 121, 0.15);
}

.course-admin-hero::before,
.course-admin-hero::after,
.course-admin-hero__body::before,
.course-admin-hero__body::after,
.course-admin-hero__content::before,
.course-admin-hero__content::after {
  content: none !important;
  display: none !important;
  visibility: hidden !important;
  width: 0 !important;
  height: 0 !important;
  background: none !important;
  background-image: none !important;
  border: 0 !important;
  box-shadow: none !important;
  opacity: 0 !important;
  transform: none !important;
  filter: none !important;
}

.course-admin-hero__body {
  display: grid;
  grid-template-columns: 205px minmax(0, 1fr);
  align-items: start;
  gap: 24px;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  padding-right: 310px;
}

.course-admin-hero__cover {
  width: 205px;
  max-width: 205px;
  height: 162px;
  overflow: hidden;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.12);
  box-shadow: 0 14px 34px rgba(20, 31, 83, 0.18);
}

.course-admin-hero__cover img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.course-admin-hero__fallback {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  height: 100%;

  color: #ffffff;
  font-size: 42px;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.course-admin-hero__content {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

.course-admin-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin-bottom: 16px;
}

.course-admin-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  width: auto;
  max-width: 100%;
  min-height: 32px;
  padding: 7px 12px;

  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 999px;

  color: #ffffff;
  background: rgba(255, 255, 255, 0.11);

  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}

.course-admin-badge--success {
  background: rgba(10, 179, 156, 0.25);
}

.course-admin-badge--warning {
  background: rgba(247, 184, 75, 0.24);
}

.course-admin-badge--info {
  background: rgba(41, 156, 219, 0.24);
}

.course-admin-badge--muted {
  background: rgba(255, 255, 255, 0.09);
  opacity: 0.9;
}

.course-admin-badge__separator {
  margin-inline: 2px;
  opacity: 0.65;
}

.course-admin-hero__content h2 {
  display: block;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin: 0 0 10px;

  color: #ffffff;
  font-size: clamp(27px, 2.1vw, 38px);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: normal;
  text-align: left;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
  writing-mode: horizontal-tb;
}

.course-admin-hero__content > p {
  width: 100%;
  max-width: 760px;
  margin: 0 0 18px;

  color: rgba(255, 255, 255, 0.86);
  font-size: 14px;
  line-height: 1.65;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   INFORMATIONS DU COURS
========================================================= */

.course-admin-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(145px, 1fr));
  gap: 10px;

  width: 100%;
  max-width: 930px;
  min-width: 0;
}

.course-admin-stat {
  display: flex;
  align-items: center;
  gap: 11px;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  min-height: 66px;
  padding: 11px 13px;
  overflow: hidden;

  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.11);
  backdrop-filter: blur(8px);
}

.course-admin-stat > i {
  display: inline-flex;
  flex: 0 0 36px;
  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;
  border-radius: 11px;

  color: #ffffff;
  background: rgba(255, 255, 255, 0.15);
  font-size: 18px;
}

.course-admin-stat > div {
  width: calc(100% - 47px);
  min-width: 0;
}

.course-admin-stat span {
  display: block;
  margin-bottom: 3px;

  color: rgba(255, 255, 255, 0.72);
  font-size: 10px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.course-admin-stat strong {
  display: block;
  width: 100%;

  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.3;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   ACTIONS DU HERO
========================================================= */

.course-admin-hero__actions {
  position: absolute;
  top: 26px;
  right: 26px;

  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;

  width: auto;
  max-width: 290px;
}

.course-admin-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;

  min-height: 42px;
  padding: 10px 17px;

  border: 1px solid transparent;
  border-radius: 13px;

  font-size: 13px;
  font-weight: 700;
  line-height: 1;
  text-decoration: none;
  white-space: nowrap;

  transition:
    transform 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.course-admin-action:hover {
  transform: translateY(-1px);
}

.course-admin-action--light {
  color: #17233d;
  background: #ffffff;
  border-color: #ffffff;
}

.course-admin-action--outline {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.55);
}

.course-admin-action--outline:hover {
  color: #405189;
  background: #ffffff;
  border-color: #ffffff;
}

/* =========================================================
   CARTES DE RÉSUMÉ
========================================================= */

.course-admin-overview {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin: 20px 0 24px;
}

.course-admin-overview__card {
  display: flex;
  align-items: center;
  gap: 13px;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  min-height: 78px;
  padding: 16px;

  border: 1px solid #e9edf5;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(31, 45, 86, 0.05);
}

.course-admin-overview__icon {
  display: inline-flex;
  flex: 0 0 44px;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;
  border-radius: 14px;

  color: #405189;
  background: #eef2ff;
  font-size: 21px;
}

.course-admin-overview__card > div:last-child {
  min-width: 0;
}

.course-admin-overview__card span {
  display: block;
  margin-bottom: 4px;

  color: #68728a;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.course-admin-overview__card strong {
  display: block;
  width: 100%;

  color: #17233d;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.35;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   RESPONSIVE BASÉ SUR LA LARGEUR RÉELLE DU HERO
========================================================= */

@container courseAdminHero (max-width: 980px) {
  .course-admin-hero__body {
    padding-right: 0;
  }

  .course-admin-hero__actions {
    position: static;
    justify-content: flex-start;

    width: 100%;
    max-width: none;
    margin-top: 20px;
  }

  .course-admin-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: none;
  }
}

@container courseAdminHero (max-width: 820px) {
  .course-admin-hero {
    padding: 22px;
  }

  .course-admin-hero__body {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .course-admin-hero__cover {
    width: 180px;
    max-width: 180px;
    height: 140px;
  }

  .course-admin-hero__content h2 {
    font-size: 29px;
  }
}

@container courseAdminHero (max-width: 560px) {
  .course-admin-hero {
    padding: 18px;
    border-radius: 19px;
  }

  .course-admin-hero__cover {
    width: 145px;
    max-width: 145px;
    height: 114px;
    border-radius: 18px;
  }

  .course-admin-hero__content h2 {
    font-size: 24px;
  }

  .course-admin-stats {
    grid-template-columns: 1fr;
  }

  .course-admin-hero__actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .course-admin-action {
    width: 100%;
  }
}

/* =========================================================
   RESPONSIVE DU RESTE DE LA PAGE
========================================================= */

@media (max-width: 1199.98px) {
  .course-admin-hero__body {
    padding-right: 0;
  }

  .course-admin-hero__actions {
    position: static;
    justify-content: flex-start;

    width: 100%;
    max-width: none;
    margin-top: 20px;
  }

  .course-admin-overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .course-admin-hero {
    padding: 22px;
  }

  .course-admin-hero__body {
    grid-template-columns: 1fr;
  }

  .course-admin-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: none;
  }

  .chapters-sidebar {
    margin-bottom: 4px;
  }
}

@media (max-width: 767.98px) {
  .course-admin-overview {
    grid-template-columns: 1fr;
    gap: 11px;
    margin: 16px 0 20px;
  }

  .slide-topbar {
    flex-wrap: wrap;
    gap: 10px;
  }

  .chapter-context {
    padding-inline: 16px;
  }

  .slide-content-box {
    padding: 14px;
  }

  .slide-navigation {
    flex-wrap: wrap;
    gap: 12px;
  }

  .slide-dots {
    order: 3;
    width: 100%;
  }

  .voice-over-main {
    align-items: flex-start;
  }

  .voice-over-audio {
    width: 100%;
  }

  .admin-quiz-header,
  .admin-question-navigation {
    flex-wrap: wrap;
    gap: 12px;
  }
}

@media (max-width: 575.98px) {
  .course-admin-hero {
    padding: 16px;
    border-radius: 17px;
  }

  .course-admin-hero__cover {
    width: 132px;
    max-width: 132px;
    height: 106px;
  }

  .course-admin-hero__content h2 {
    font-size: 23px;
  }

  .course-admin-stats {
    grid-template-columns: 1fr;
  }

  .course-admin-hero__actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .course-admin-action {
    width: 100%;
  }

  .course-admin-overview__card {
    padding: 13px;
  }

  .slide-view-card {
    border-radius: 18px;
  }

  .slide-topbar {
    padding: 12px 14px;
  }

  .chapter-context {
    padding: 16px 14px 8px;
  }

  .slide-voice-over-card,
  .slide-voice-over-empty {
    margin-inline: 14px;
  }

  .slide-content-box {
    padding: 10px;
  }

  .slide-navigation {
    padding: 14px 10px;
  }

  .nav-slide-btn {
    flex: 1 1 100%;
    width: 100%;
  }

  .voice-over-main {
    flex-direction: column;
  }

  .voice-over-icon {
    flex: 0 0 auto;
  }

  .admin-quiz-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .admin-question-title {
    align-items: flex-start;
  }

  .admin-question-heading-meta {
    align-items: flex-start;
    flex-direction: column;
  }

  .admin-question-navigation {
    flex-direction: column;
  }

  .true-false-options {
    grid-template-columns: 1fr;
  }

  .admin-option-row {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .admin-correct-badge {
    margin-left: 46px;
  }
}

@media (max-width: 399.98px) {
  .course-admin-hero {
    padding: 14px;
  }

  .course-admin-hero__cover {
    width: 116px;
    max-width: 116px;
    height: 94px;
  }

  .course-admin-hero__content h2 {
    font-size: 21px;
  }
}

/* =========================================================
   QUIZ ADMIN
========================================================= */

.admin-question-heading-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.admin-question-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: 0.02em;
}

.admin-question-type-badge.qcm {
  color: #405189;
  background: rgba(64, 81, 137, 0.12);
  border: 1px solid rgba(64, 81, 137, 0.18);
}

.admin-question-type-badge.true-false {
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.12);
  border: 1px solid rgba(10, 179, 156, 0.2);
}

.true-false-options {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.true-false-options .admin-option-row {
  min-height: 72px;
}
</style>
