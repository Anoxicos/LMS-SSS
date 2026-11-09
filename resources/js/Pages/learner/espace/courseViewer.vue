<script setup>
import { Head, router } from '@inertiajs/vue3'
import {
  ref,
  computed,
  nextTick,
  onMounted,
  onBeforeUnmount,
  watch,
} from 'vue'

import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  enrollment: {
    type: Object,
    required: true,
  },
})

const selectedChapterIndex = ref(0)
const selectedSlideIndex = ref(0)
const voiceOverAudio = ref(null)

const audioFinished = ref(false)
const isAudioPlaying = ref(false)
const isCompletingSlide = ref(false)

const audioCurrentTime = ref(0)
const audioDuration = ref(0)

const isSidebarOpen = ref(false)
const isMobileViewport = ref(false)
const sidebarCloseButton = ref(null)
const mobileMenuButton = ref(null)
const initialPositionRestored = ref(false)
let mobileSidebarQuery = null

const quizAnswers = ref({})
const quizValidation = ref({})
const currentQuizQuestionIndex = ref(0)
const showQuizReview = ref(false)

/*
|--------------------------------------------------------------------------
| Chapters / Slides
|--------------------------------------------------------------------------
*/
const chapters = computed(() => {
  return (props.enrollment.course?.chapters || []).filter(chapter => {
    return chapter.slides && chapter.slides.length > 0
  })
})

const selectedChapter = computed(() => {
  return chapters.value[selectedChapterIndex.value] || null
})

const slides = computed(() => {
  return selectedChapter.value?.slides || []
})

const selectedSlide = computed(() => {
  return slides.value[selectedSlideIndex.value] || null
})

const totalSlides = computed(() => {
  return chapters.value.reduce((total, chapter) => {
    return total + (chapter.slides?.length || 0)
  }, 0)
})

const enrollmentHashid = computed(() => {
  return props.enrollment.hashid || null
})

const trainingRequest = computed(() => {
  return props.enrollment.training_request || null
})

const formatTrainingDate = value => {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const trainingPeriodLabel = computed(() => {
  if (!trainingRequest.value) return '-'

  return `${formatTrainingDate(trainingRequest.value.start_date)} - ${formatTrainingDate(trainingRequest.value.end_date)}`
})

/*
|--------------------------------------------------------------------------
| Progress
|--------------------------------------------------------------------------
*/
const slideProgressList = computed(() => {
  return props.enrollment.slide_progress ||
    props.enrollment.slideProgress ||
    props.enrollment.slide_progresses ||
    []
})

const chapterProgressList = computed(() => {
  return props.enrollment.chapter_progress ||
    props.enrollment.chapterProgress ||
    props.enrollment.chapter_progresses ||
    []
})

const getSlideProgress = slide => {
  if (!slide) return null

  return slideProgressList.value.find(item => {
    return Number(item.slide_id) === Number(slide.id)
  })
}

const isSlideCompleted = slide => {
  const progress = getSlideProgress(slide)

  return Boolean(progress?.is_completed)
}

const isCurrentSlideCompleted = computed(() => {
  if (!selectedSlide.value) return false

  return isSlideCompleted(selectedSlide.value)
})

const progressPercent = computed(() => {
  const percent = Number(props.enrollment.progress_percent || 0)

  if (Number.isNaN(percent)) {
    return 0
  }

  return Math.min(Math.max(percent, 0), 100).toFixed(0)
})

const currentGlobalSlideNumber = computed(() => {
  if (!selectedSlide.value) return 0

  let count = 0

  for (let i = 0; i < selectedChapterIndex.value; i++) {
    count += chapters.value[i]?.slides?.length || 0
  }

  return count + selectedSlideIndex.value + 1
})

/*
|--------------------------------------------------------------------------
| Quiz
|--------------------------------------------------------------------------
*/
const selectedSlideQuestions = computed(() => {
  return selectedSlide.value?.questions || []
})

const currentQuizQuestion = computed(() => {
  return selectedSlideQuestions.value[currentQuizQuestionIndex.value] || null
})

const isFirstQuizQuestion = computed(() => {
  return currentQuizQuestionIndex.value === 0
})

const isLastQuizQuestion = computed(() => {
  return currentQuizQuestionIndex.value === selectedSlideQuestions.value.length - 1
})

const currentQuizQuestionAnswered = computed(() => {
  if (!currentQuizQuestion.value) return false

  return Boolean(
    selectedSlideQuizAnswers.value[currentQuizQuestion.value.id]
  )
})

const quizNavigationPercent = computed(() => {
  const total = selectedSlideQuestions.value.length

  if (!total) return 0

  return Math.min(
    100,
    Math.round(((currentQuizQuestionIndex.value + 1) / total) * 100)
  )
})

const isQuizSlide = computed(() => {
  return selectedSlide.value?.lesson_type === 'quiz'
})

const hasQuizQuestions = computed(() => {
  return isQuizSlide.value && selectedSlideQuestions.value.length > 0
})

const selectedSlideQuizAnswers = computed(() => {
  if (!selectedSlide.value) return {}

  return quizAnswers.value[selectedSlide.value.id] || {}
})

const selectedSlideQuizValidation = computed(() => {
  if (!selectedSlide.value) return null

  return quizValidation.value[selectedSlide.value.id] ?? null
})

const normalizeQuestionType = question => {
  return question?.question_type === 'true_false'
    ? 'true_false'
    : 'qcm'
}

const normalizeQuizAnswer = answer => {
  return String(answer ?? '')
    .trim()
    .toLowerCase()
}

const getQuestionTypeLabel = question => {
  return normalizeQuestionType(question) === 'true_false'
    ? 'True / False'
    : 'QCM'
}

const getQuestionTypeIcon = question => {
  return normalizeQuestionType(question) === 'true_false'
    ? 'ri-toggle-line'
    : 'ri-list-check-3'
}

const getQuestionOptions = question => {
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
  ].filter(option => Boolean(option.value))
}

const isQuizAnswered = computed(() => {
  if (!hasQuizQuestions.value) return true

  return selectedSlideQuestions.value.every(question => {
    return Boolean(selectedSlideQuizAnswers.value[question.id])
  })
})

const isQuizCorrect = computed(() => {
  if (!hasQuizQuestions.value) return true

  return selectedSlideQuestions.value.every(question => {
    return normalizeQuizAnswer(selectedSlideQuizAnswers.value[question.id]) ===
      normalizeQuizAnswer(question.correct_answer)
  })
})

const isQuizChecked = computed(() => {
  return selectedSlideQuizValidation.value !== null
})

const isQuizValid = computed(() => {
  return selectedSlideQuizValidation.value === true
})

const isQuizInvalid = computed(() => {
  return selectedSlideQuizValidation.value === false
})

const quizCorrectCount = computed(() => {
  if (!hasQuizQuestions.value) return 0

  return selectedSlideQuestions.value.reduce((total, question) => {
    return isSelectedQuizAnswer(question, question.correct_answer)
      ? total + 1
      : total
  }, 0)
})

const quizIncorrectCount = computed(() => {
  if (!hasQuizQuestions.value) return 0

  return Math.max(
    selectedSlideQuestions.value.length - quizCorrectCount.value,
    0
  )
})

const quizScorePercent = computed(() => {
  const total = selectedSlideQuestions.value.length

  if (!total) return 0

  return Math.round((quizCorrectCount.value / total) * 100)
})

const showQuizSummary = computed(() => {
  return isQuizChecked.value && !showQuizReview.value
})

const selectQuizAnswer = (questionId, answer) => {
  if (!selectedSlide.value) return
  if (isQuizChecked.value) return

  const slideId = selectedSlide.value.id

  quizAnswers.value = {
    ...quizAnswers.value,
    [slideId]: {
      ...(quizAnswers.value[slideId] || {}),
      [questionId]: normalizeQuizAnswer(answer),
    },
  }

  quizValidation.value = {
    ...quizValidation.value,
    [slideId]: null,
  }
}

const checkQuizAnswers = () => {
  if (!selectedSlide.value) return
  if (!isQuizAnswered.value) return

  const slideId = selectedSlide.value.id
  const quizIsCorrect = isQuizCorrect.value

  quizValidation.value = {
    ...quizValidation.value,
    [slideId]: quizIsCorrect,
  }

  showQuizReview.value = false

  nextTick(() => {
    if (
      quizIsCorrect &&
      !isCurrentSlideCompleted.value &&
      canCompleteCurrentSlide.value
    ) {
      markSlideCompleted()
    }
  })
}

const resetQuiz = () => {
  if (!selectedSlide.value) return

  const slideId = selectedSlide.value.id

  quizAnswers.value = {
    ...quizAnswers.value,
    [slideId]: {},
  }

  quizValidation.value = {
    ...quizValidation.value,
    [slideId]: null,
  }

  currentQuizQuestionIndex.value = 0
  showQuizReview.value = false
}

const reviewQuizAnswers = () => {
  currentQuizQuestionIndex.value = 0
  showQuizReview.value = true
}

const showResultSummary = () => {
  showQuizReview.value = false
}

const goToPreviousQuizQuestion = () => {
  if (currentQuizQuestionIndex.value > 0) {
    currentQuizQuestionIndex.value--
  }
}

const goToNextQuizQuestion = () => {
  if (!currentQuizQuestionAnswered.value) return

  if (
    currentQuizQuestionIndex.value <
    selectedSlideQuestions.value.length - 1
  ) {
    currentQuizQuestionIndex.value++
  }
}

const selectQuizQuestion = index => {
  if (index < 0 || index >= selectedSlideQuestions.value.length) {
    return
  }

  currentQuizQuestionIndex.value = index
}

const getSelectedQuizAnswer = question => {
  if (!question) return ''

  return normalizeQuizAnswer(
    selectedSlideQuizAnswers.value[question.id] || ''
  )
}

const isSelectedQuizAnswer = (question, answer) => {
  return getSelectedQuizAnswer(question) === normalizeQuizAnswer(answer)
}

const isCorrectQuizAnswer = (question, answer) => {
  return normalizeQuizAnswer(question?.correct_answer) ===
    normalizeQuizAnswer(answer)
}

const isWrongSelectedQuizAnswer = (question, answer) => {
  return isQuizChecked.value &&
    isSelectedQuizAnswer(question, answer) &&
    !isCorrectQuizAnswer(question, answer)
}

const getQuizOptionClass = (question, answer) => {
  return {
    selected: isSelectedQuizAnswer(question, answer),
    correct: isQuizChecked.value && isCorrectQuizAnswer(question, answer),
    wrong: isWrongSelectedQuizAnswer(question, answer),
  }
}

const getCorrectAnswerLabel = answer => {
  const normalizedAnswer = normalizeQuizAnswer(answer)

  if (normalizedAnswer === 'a') return t('t-option-a')
  if (normalizedAnswer === 'b') return t('t-option-b')
  if (normalizedAnswer === 'c') return t('t-option-c')
  if (normalizedAnswer === 'true') return t('t-true')
  if (normalizedAnswer === 'false') return t('t-false')

  return '-'
}

const getCorrectAnswerText = question => {
  if (!question) return '-'

  const correctAnswer = normalizeQuizAnswer(question.correct_answer)

  if (normalizeQuestionType(question) === 'true_false') {
    if (correctAnswer === 'true') return 'True'
    if (correctAnswer === 'false') return 'False'

    return '-'
  }

  if (correctAnswer === 'a') return question.option_a || '-'
  if (correctAnswer === 'b') return question.option_b || '-'
  if (correctAnswer === 'c') return question.option_c || '-'

  return '-'
}

/*
|--------------------------------------------------------------------------
| Finish Course
|--------------------------------------------------------------------------
*/
const canFinishCourse = computed(() => {
  return isLastSlide.value && isCurrentSlideCompleted.value
})

const canAccessCourseFinish = computed(() => {
  const enrollmentStatus = String(props.enrollment.status || '').toLowerCase()
  const enrollmentProgress = Number(props.enrollment.progress_percent || 0)

  return (
    enrollmentStatus === 'completed' ||
    enrollmentProgress >= 100 ||
    canFinishCourse.value
  )
})

const goToCourseFinish = () => {
  if (!enrollmentHashid.value) return

  router.visit(route('learner-space.course-finish', enrollmentHashid.value))
}

/*
|--------------------------------------------------------------------------
| Restore Last Slide From Database
|--------------------------------------------------------------------------
*/
const getLastSlideIdFromDatabase = () => {
  const directLastSlideId =
    props.enrollment.last_slide_id ||
    props.enrollment.current_slide_id ||
    null

  if (directLastSlideId) {
    return directLastSlideId
  }

  const lastProgress = [...chapterProgressList.value]
    .filter(item => item.last_slide_id)
    .sort((a, b) => {
      const dateA = new Date(a.updated_at || a.started_at || 0).getTime()
      const dateB = new Date(b.updated_at || b.started_at || 0).getTime()

      if (dateA !== dateB) {
        return dateB - dateA
      }

      return Number(b.id || 0) - Number(a.id || 0)
    })[0]

  return lastProgress?.last_slide_id || null
}

const restoreLastSlideFromDatabase = () => {
  const lastSlideId = getLastSlideIdFromDatabase()

  if (!lastSlideId) return false

  for (let chapterIndex = 0; chapterIndex < chapters.value.length; chapterIndex++) {
    const chapterSlides = chapters.value[chapterIndex]?.slides || []

    const slideIndex = chapterSlides.findIndex(slide => {
      return Number(slide.id) === Number(lastSlideId)
    })

    if (slideIndex !== -1) {
      selectedChapterIndex.value = chapterIndex
      selectedSlideIndex.value = slideIndex
      initialPositionRestored.value = true
      return true
    }
  }

  return false
}

/*
|--------------------------------------------------------------------------
| Voice Over
|--------------------------------------------------------------------------
*/
const hasCurrentSlideVoiceOver = computed(() => {
  return Boolean(
    props.enrollment.course?.support_voice_over &&
      selectedSlide.value &&
      selectedSlide.value.voice_over_enabled &&
      selectedSlide.value.voice_over_audio_url
  )
})

const voiceOverAutoPlay = computed(() => {
  return Boolean(props.enrollment.course?.voice_over_auto_play)
})

const currentSlideHasBlockingAudio = computed(() => {
  if (isCurrentSlideCompleted.value) return false

  return hasCurrentSlideVoiceOver.value &&
    !audioFinished.value
})

const audioProgressPercent = computed(() => {
  if (!audioDuration.value) return 0

  return Math.min(
    Math.round((audioCurrentTime.value / audioDuration.value) * 100),
    100
  )
})

const isAudioAtStart = computed(() => {
  return Number(audioCurrentTime.value || 0) <= 0.2
})

const isAudioStopped = computed(() => {
  return !isAudioPlaying.value &&
    !audioFinished.value &&
    !isAudioAtStart.value
})

const formatAudioTime = seconds => {
  const value = Number(seconds || 0)
  const minutes = Math.floor(value / 60)
  const restSeconds = Math.floor(value % 60)

  return `${minutes}:${String(restSeconds).padStart(2, '0')}`
}

const audioCurrentTimeLabel = computed(() => {
  return formatAudioTime(audioCurrentTime.value)
})

const audioDurationLabel = computed(() => {
  return formatAudioTime(audioDuration.value)
})

const audioButtonIcon = computed(() => {
  if (audioFinished.value || isAudioAtStart.value) {
    return 'bx bx-revision'
  }

  if (isAudioPlaying.value) {
    return 'bx bx-play'
  }

  return 'bx bx-stop'
})

const audioButtonTitle = computed(() => {
  if (audioFinished.value || isAudioAtStart.value) {
    return t('t-replay-audio')
  }

  if (isAudioPlaying.value) {
    return t('t-audio-playing')
  }

  return t('t-audio-stopped')
})

const audioLabelText = computed(() => {
  if (isCurrentSlideCompleted.value) {
    return t('t-slide-completed')
  }

  if (audioFinished.value) {
    return t('t-audio-completed')
  }

  if (isAudioAtStart.value && voiceOverAutoPlay.value) {
    return t('t-auto-play-enabled')
  }

  if (isAudioAtStart.value) {
    return t('t-replay-from-start')
  }

  if (isAudioPlaying.value) {
    return 'Audio playing'
  }

  if (isAudioStopped.value) {
    return 'Audio stopped'
  }

  return t('t-start-voice-over')
})

const audioButtonStateClass = computed(() => {
  return {
    replay: audioFinished.value || isAudioAtStart.value,
    playing: isAudioPlaying.value,
    stopped: isAudioStopped.value,
    done: audioFinished.value || isCurrentSlideCompleted.value,
  }
})

/*
|--------------------------------------------------------------------------
| Navigation Rules
|--------------------------------------------------------------------------
*/
const isFirstSlide = computed(() => {
  return selectedChapterIndex.value === 0 && selectedSlideIndex.value === 0
})

const isLastSlide = computed(() => {
  if (!selectedChapter.value || !selectedSlide.value) return true

  return selectedChapterIndex.value === chapters.value.length - 1 &&
    selectedSlideIndex.value === slides.value.length - 1
})

const canCompleteCurrentSlide = computed(() => {
  if (!selectedSlide.value) return false

  if (isCurrentSlideCompleted.value) {
    return false
  }

  if (hasCurrentSlideVoiceOver.value && !audioFinished.value) {
    return false
  }

  if (hasQuizQuestions.value && !isQuizValid.value) {
    return false
  }

  return true
})

const canGoNext = computed(() => {
  if (!selectedSlide.value) return false
  if (isLastSlide.value) return false

  if (isCurrentSlideCompleted.value) {
    return true
  }

  if (currentSlideHasBlockingAudio.value) {
    return false
  }

  if (hasQuizQuestions.value && !isQuizValid.value) {
    return false
  }

  return isCurrentSlideCompleted.value
})

const getGlobalSlideIndex = (chapterIndex, slideIndex) => {
  let index = 0

  for (let i = 0; i < chapterIndex; i++) {
    index += chapters.value[i]?.slides?.length || 0
  }

  return index + slideIndex
}

const getCurrentGlobalSlideIndex = () => {
  return getGlobalSlideIndex(
    selectedChapterIndex.value,
    selectedSlideIndex.value
  )
}

const findSlideByGlobalIndex = targetIndex => {
  let count = 0

  for (const chapter of chapters.value) {
    for (const slide of chapter.slides || []) {
      if (count === targetIndex) {
        return slide
      }

      count++
    }
  }

  return null
}

const isSlideLocked = (chapterIndex, slideIndex) => {
  const targetIndex = getGlobalSlideIndex(chapterIndex, slideIndex)
  const currentIndex = getCurrentGlobalSlideIndex()

  if (targetIndex <= currentIndex) {
    return false
  }

  const previousSlide = findSlideByGlobalIndex(targetIndex - 1)

  if (!previousSlide) {
    return false
  }

  return !isSlideCompleted(previousSlide)
}

const isChapterLocked = chapterIndex => {
  if (chapterIndex === 0) return false

  const previousChapter = chapters.value[chapterIndex - 1]

  if (!previousChapter?.slides?.length) {
    return false
  }

  return previousChapter.slides.some(slide => !isSlideCompleted(slide))
}

/*
|--------------------------------------------------------------------------
| Audio Actions
|--------------------------------------------------------------------------
*/
const resetAudioState = () => {
  audioFinished.value = false
  isAudioPlaying.value = false
  audioCurrentTime.value = 0
  audioDuration.value = 0
}

const resetVoiceOverAudio = () => {
  if (!voiceOverAudio.value) {
    resetAudioState()
    return
  }

  try {
    voiceOverAudio.value.pause()
    voiceOverAudio.value.currentTime = 0
    voiceOverAudio.value.load()
  } catch (error) {
    // Ignore audio reset errors
  }

  resetAudioState()
}

const restartAudioForCurrentSlide = async () => {
  resetVoiceOverAudio()

  await nextTick()

  if (!voiceOverAudio.value) return
  if (!hasCurrentSlideVoiceOver.value) return
  if (isCurrentSlideCompleted.value) return

  try {
    voiceOverAudio.value.currentTime = 0
    audioCurrentTime.value = 0
    audioFinished.value = false

    if (voiceOverAutoPlay.value) {
      await voiceOverAudio.value.play()
      isAudioPlaying.value = true
    }
  } catch (error) {
    isAudioPlaying.value = false
  }
}

const playVoiceOverIfNeeded = async () => {
  await nextTick()

  if (!voiceOverAudio.value) return
  if (!hasCurrentSlideVoiceOver.value) return
  if (!voiceOverAutoPlay.value) return
  if (isCurrentSlideCompleted.value) return

  try {
    voiceOverAudio.value.currentTime = 0
    audioCurrentTime.value = 0
    audioFinished.value = false
    await voiceOverAudio.value.play()
    isAudioPlaying.value = true
  } catch (error) {
    isAudioPlaying.value = false
  }
}

const toggleAudio = async () => {
  if (!voiceOverAudio.value) return

  try {
    if (isAudioPlaying.value) {
      voiceOverAudio.value.pause()
      isAudioPlaying.value = false
      return
    }

    if (
      audioFinished.value ||
      isAudioAtStart.value ||
      voiceOverAudio.value.currentTime >= voiceOverAudio.value.duration
    ) {
      voiceOverAudio.value.currentTime = 0
      audioCurrentTime.value = 0
      audioFinished.value = false
    }

    await voiceOverAudio.value.play()
    isAudioPlaying.value = true
  } catch (error) {
    isAudioPlaying.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/
const goBackToCourses = () => {
  router.visit(route('learner-space.my-courses'))
}

const closeSidebar = () => {
  isSidebarOpen.value = false

  if (isMobileViewport.value) {
    nextTick(() => mobileMenuButton.value?.focus({ preventScroll: true }))
  }
}

const toggleSidebar = async () => {
  isSidebarOpen.value = !isSidebarOpen.value

  if (isSidebarOpen.value && isMobileViewport.value) {
    await nextTick()
    sidebarCloseButton.value?.focus({ preventScroll: true })
  }
}

const handleMobileSidebarViewport = event => {
  isMobileViewport.value = event.matches
  if (!event.matches) isSidebarOpen.value = false
}

const markSlideViewed = () => {
  if (!selectedSlide.value || !enrollmentHashid.value) return

  router.post(
    route('learner-space.slide.viewed', {
      enrollmentHashid: enrollmentHashid.value,
      slide: selectedSlide.value.id,
    }),
    {},
    {
      preserveScroll: true,
      preserveState: true,
    }
  )
}

const goToSlide = async (chapterIndex, slideIndex) => {
  selectedChapterIndex.value = chapterIndex
  selectedSlideIndex.value = slideIndex

  closeSidebar()

  await nextTick()

  await restartAudioForCurrentSlide()
  markSlideViewed()
}

const selectChapter = async chapterIndex => {
  if (isChapterLocked(chapterIndex)) return

  await goToSlide(chapterIndex, 0)
}

const selectSlide = async slideIndex => {
  if (isSlideLocked(selectedChapterIndex.value, slideIndex)) return

  await goToSlide(selectedChapterIndex.value, slideIndex)
}

const prevSlide = async () => {
  if (!selectedChapter.value || !selectedSlide.value) return

  if (selectedSlideIndex.value > 0) {
    await goToSlide(selectedChapterIndex.value, selectedSlideIndex.value - 1)
    return
  }

  if (selectedChapterIndex.value > 0) {
    const previousChapterIndex = selectedChapterIndex.value - 1
    const previousChapterSlides =
      chapters.value[previousChapterIndex]?.slides || []

    const previousSlideIndex = previousChapterSlides.length
      ? previousChapterSlides.length - 1
      : 0

    await goToSlide(previousChapterIndex, previousSlideIndex)
  }
}

const nextSlide = async () => {
  if (!canGoNext.value) return
  if (!selectedChapter.value || !selectedSlide.value) return

  if (selectedSlideIndex.value < selectedChapter.value.slides.length - 1) {
    await goToSlide(selectedChapterIndex.value, selectedSlideIndex.value + 1)
    return
  }

  if (selectedChapterIndex.value < chapters.value.length - 1) {
    await goToSlide(selectedChapterIndex.value + 1, 0)
  }
}

const markSlideCompleted = () => {
  if (!selectedSlide.value || !enrollmentHashid.value) return
  if (isCompletingSlide.value) return

  if (isCurrentSlideCompleted.value) {
    return
  }

  if (hasCurrentSlideVoiceOver.value && !audioFinished.value) {
    return
  }

  if (hasQuizQuestions.value && !isQuizValid.value) {
    return
  }

  isCompletingSlide.value = true

  router.post(
    route('learner-space.slide.completed', {
      enrollmentHashid: enrollmentHashid.value,
      slide: selectedSlide.value.id,
    }),
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        isCompletingSlide.value = false
      },
    }
  )
}

const handleAudioLoaded = () => {
  if (!voiceOverAudio.value) return

  audioDuration.value = voiceOverAudio.value.duration || 0
}

const handleAudioTimeUpdate = () => {
  if (!voiceOverAudio.value) return

  audioCurrentTime.value = voiceOverAudio.value.currentTime || 0
}

const handleAudioPlay = () => {
  isAudioPlaying.value = true
}

const handleAudioPause = () => {
  isAudioPlaying.value = false
}

const handleAudioEnded = () => {
  isAudioPlaying.value = false
  audioFinished.value = true
  audioCurrentTime.value = audioDuration.value

  if (!selectedSlide.value) return
  if (isCurrentSlideCompleted.value) return

  if (hasQuizQuestions.value && !isQuizValid.value) {
    return
  }

  markSlideCompleted()
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
const getSlideIcon = slide => {
  const type = slide?.lesson_type || slide

  if (type === 'video') return 'ri-video-line'
  if (type === 'file') return 'ri-file-3-line'
  if (type === 'link') return 'ri-links-line'
  if (type === 'quiz') return 'ri-question-answer-line'

  return 'ri-text-snippet'
}

const getVideoEmbedUrl = url => {
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

const isImageFile = slide => {
  if (!slide) return false

  const fileType = String(slide.file_type || '').toLowerCase()
  const fileName = String(slide.file_name || '').toLowerCase()

  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']

  return imageExtensions.some(ext => {
    return fileType === ext || fileName.endsWith(`.${ext}`)
  })
}

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/
watch(
  chapters,
  newChapters => {
    if (!newChapters.length) {
      selectedChapterIndex.value = 0
      selectedSlideIndex.value = 0
      return
    }

    if (!initialPositionRestored.value) {
      const restored = restoreLastSlideFromDatabase()

      if (restored) {
        return
      }

      initialPositionRestored.value = true
    }

    if (selectedChapterIndex.value >= newChapters.length) {
      selectedChapterIndex.value = 0
      selectedSlideIndex.value = 0
      return
    }

    const currentSlides = newChapters[selectedChapterIndex.value]?.slides || []

    if (selectedSlideIndex.value >= currentSlides.length) {
      selectedSlideIndex.value = 0
    }
  },
  { immediate: true }
)

watch(
  () => selectedSlide.value?.id,
  async () => {
    currentQuizQuestionIndex.value = 0
    showQuizReview.value = false
    resetVoiceOverAudio()

    await nextTick()

    if (selectedSlide.value) {
      markSlideViewed()
      playVoiceOverIfNeeded()
    }
  }
)

watch(isSidebarOpen, value => {
  document.body.style.overflow = value ? 'hidden' : ''
})

/*
|--------------------------------------------------------------------------
| Protection
|--------------------------------------------------------------------------
*/
const blockActions = event => {
  event.preventDefault()
}

const blockKeyboardShortcuts = event => {
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
  mobileSidebarQuery = window.matchMedia('(max-width: 991.98px)')
  isMobileViewport.value = mobileSidebarQuery.matches
  mobileSidebarQuery.addEventListener('change', handleMobileSidebarViewport)

  document.addEventListener('contextmenu', blockActions)
  document.addEventListener('copy', blockActions)
  document.addEventListener('cut', blockActions)
  document.addEventListener('paste', blockActions)
  document.addEventListener('dragstart', blockActions)
  document.addEventListener('keydown', blockKeyboardShortcuts)

  restoreLastSlideFromDatabase()

  nextTick(() => {
    markSlideViewed()
    playVoiceOverIfNeeded()
  })
})

onBeforeUnmount(() => {
  mobileSidebarQuery?.removeEventListener('change', handleMobileSidebarViewport)
  resetVoiceOverAudio()
  document.body.style.overflow = ''

  document.removeEventListener('contextmenu', blockActions)
  document.removeEventListener('copy', blockActions)
  document.removeEventListener('cut', blockActions)
  document.removeEventListener('paste', blockActions)
  document.removeEventListener('dragstart', blockActions)
  document.removeEventListener('keydown', blockKeyboardShortcuts)
})
</script>

<template>
  <Head :title="enrollment.course?.title || $t('ui-course-viewer')" />

  <section class="compact-player-page">
    <button
      type="button"
      class="sidebar-overlay"
      :class="{ show: isSidebarOpen }"
      :aria-label="$t('ui-close-menu')"
      :aria-hidden="!isSidebarOpen"
      :disabled="!isSidebarOpen"
      @click="closeSidebar"
    ></button>

    <div class="compact-player-shell">
      <aside
        id="course-contents"
        class="compact-sidebar"
        :class="{ open: isSidebarOpen }"
        :aria-label="$t('ui-chapters')"
        :aria-hidden="isMobileViewport && !isSidebarOpen"
        :inert="isMobileViewport && !isSidebarOpen"
        @keydown.esc.stop.prevent="closeSidebar"
      >
        <div class="compact-sidebar-header">
          <button
            type="button"
            class="back-btn"
            :aria-label="$t('ui-back-to-courses')"
            @click="goBackToCourses"
          >
            <i class="ri-arrow-left-line"></i>
          </button>

          <div class="min-width-0">
            <h6>{{ enrollment.course?.title || 'Course' }}</h6>
            <span>{{ $t('t-percent-completed', { percent: progressPercent }) }}</span>
          </div>

          <button
            type="button"
            class="sidebar-close-btn"
            ref="sidebarCloseButton"
            :aria-label="$t('ui-close-menu')"
            @click="closeSidebar"
          >
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div
          class="compact-progress"
          role="progressbar"
          :aria-label="$t('t-course-progress')"
          aria-valuemin="0"
          aria-valuemax="100"
          :aria-valuenow="Number(progressPercent)"
        >
          <div :style="{ width: `${progressPercent}%` }"></div>
        </div>

        <div v-if="!chapters.length" class="compact-empty-chapters">
          <i class="ri-book-open-line" aria-hidden="true"></i>
          <p>{{ $t('t-course-content-unavailable') }}</p>
        </div>

        <div v-else class="compact-chapters">
          <div
            v-for="(chapter, chapterIndex) in chapters"
            :key="chapter.id"
            class="compact-chapter"
          >
            <button
              type="button"
              class="compact-chapter-btn"
              :disabled="isChapterLocked(chapterIndex)"
              :class="{
                active: selectedChapterIndex === chapterIndex,
                locked: isChapterLocked(chapterIndex),
              }"
              @click="selectChapter(chapterIndex)"
            >
              <span class="chapter-index">
                <i
                  v-if="isChapterLocked(chapterIndex)"
                  class="ri-lock-line"
                ></i>

                <template v-else>
                  {{ chapterIndex + 1 }}
                </template>
              </span>

              <span class="chapter-title">
                {{ chapter.title }}
              </span>
            </button>

            <div
              v-if="selectedChapterIndex === chapterIndex"
              class="compact-slides"
            >
              <button
                v-for="(slide, slideIndex) in chapter.slides"
                :key="slide.id"
                type="button"
                class="compact-slide-btn"
                :disabled="isSlideLocked(chapterIndex, slideIndex)"
                :class="{
                  active: selectedChapterIndex === chapterIndex && selectedSlideIndex === slideIndex,
                  completed: isSlideCompleted(slide),
                  locked: isSlideLocked(chapterIndex, slideIndex),
                }"
                @click="selectSlide(slideIndex)"
              >
                <i
                  v-if="isSlideLocked(chapterIndex, slideIndex)"
                  class="ri-lock-line"
                ></i>

                <i
                  v-else-if="isSlideCompleted(slide)"
                  class="ri-checkbox-circle-fill"
                ></i>

                <i
                  v-else
                  :class="getSlideIcon(slide)"
                ></i>

                <span>{{ slide.title }}</span>

                <i
                  v-if="slide.voice_over_enabled && slide.voice_over_audio_url"
                  class="ri-volume-up-line voice-dot"
                ></i>

                <i
                  v-if="slide.lesson_type === 'quiz'"
                  class="ri-question-answer-line voice-dot"
                ></i>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <main class="compact-main">
        <div class="compact-topbar">
          <div class="topbar-left">
            <button
              type="button"
              class="mobile-menu-btn"
              ref="mobileMenuButton"
              :aria-label="$t('ui-open-menu')"
              :aria-expanded="isSidebarOpen"
              aria-controls="course-contents"
              @click="toggleSidebar"
            >
              <i class="ri-menu-2-line"></i>
            </button>

            <div class="min-width-0">
              <span v-if="selectedSlide" class="slide-counter" aria-live="polite">
                {{ $t('ui-slide') }} {{ currentGlobalSlideNumber }} / {{ totalSlides }}
              </span>

              <h1>{{ selectedSlide?.title || enrollment.course?.title || $t('ui-course-viewer') }}</h1>
            </div>
          </div>

          <div class="topbar-actions">
            <span
              v-if="selectedSlide"
              class="status-pill"
              :class="isCurrentSlideCompleted ? 'done' : 'pending'"
              role="status"
              aria-live="polite"
            >
              <i
                :class="isCurrentSlideCompleted
                  ? 'ri-checkbox-circle-line'
                  : 'ri-time-line'"
              ></i>

              {{ isCurrentSlideCompleted ? $t('t-slide-completed') : $t('t-in-progress') }}
            </span>

            <span v-else class="status-pill unavailable" role="status">
              <i class="ri-information-line" aria-hidden="true"></i>
              {{ $t('t-course-content-unavailable') }}
            </span>

            <BButton
              v-if="canAccessCourseFinish"
              type="button"
              variant="primary"
              class="topbar-finish-course-btn"
              :title="$t('t-finish-course-exam')"
              @click="goToCourseFinish"
            >
              <i class="ri-flag-line"></i>

              <span>{{ $t('t-finish-course') }}</span>
            </BButton>
          </div>
        </div>

        <div
          v-if="trainingRequest"
          class="viewer-training-strip"
        >
          <div class="viewer-training-period">
            <i class="ri-calendar-check-line"></i>

            <div>
              <span>{{ $t('t-training-period') }}</span>
              <strong>{{ trainingPeriodLabel }}</strong>
            </div>
          </div>

          <div class="viewer-training-accreditations">
            <span
              v-for="accreditation in trainingRequest.accreditations || []"
              :key="accreditation.id"
            >
              <i class="ri-shield-check-line"></i>
              {{ accreditation.name }}
            </span>
          </div>
        </div>

        <div class="compact-content-card">
          <div v-if="selectedSlide" class="compact-slide-content">
            <div
              v-if="selectedSlide.lesson_type === 'text'"
              class="compact-rich-content protected-text"
              tabindex="0"
              :aria-label="$t('t-lesson-content')"
              v-html="selectedSlide.content || 'No content available.'"
            ></div>

            <div
              v-else-if="selectedSlide.lesson_type === 'video'"
              class="compact-video-box"
            >
              <iframe
                v-if="selectedSlide.video_url"
                :src="getVideoEmbedUrl(selectedSlide.video_url)"
                :title="selectedSlide.title || $t('t-course-video')"
                frameborder="0"
                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                sandbox="allow-same-origin allow-scripts allow-presentation"
              ></iframe>

              <div v-else class="compact-empty-content">
                <i class="ri-video-off-line"></i>
                <p>{{ $t('t-no-video-url') }}</p>
              </div>
            </div>

            <div
              v-else-if="selectedSlide.lesson_type === 'file'"
              class="compact-file-area"
            >
              <div
                v-if="isImageFile(selectedSlide)"
                class="compact-image-box protected-media"
              >
                <img
                  :src="selectedSlide.file_url"
                  :alt="selectedSlide.title"
                  draggable="false"
                  @contextmenu.prevent
                />
              </div>

              <div v-else class="compact-empty-content">
                <i class="ri-file-lock-line"></i>
                <p>{{ $t('t-protected-file') }}</p>
              </div>
            </div>

            <div
              v-else-if="selectedSlide.lesson_type === 'link'"
              class="compact-empty-content"
            >
              <i class="ri-links-line"></i>

              <a
                v-if="selectedSlide.external_url"
                :href="selectedSlide.external_url"
                target="_blank"
                rel="noopener noreferrer"
                class="open-link-btn"
              >
                {{ $t('t-open-resource') }}
              </a>

              <p v-else>{{ $t('t-no-external-url') }}</p>
            </div>

            <div
              v-else-if="selectedSlide.lesson_type === 'quiz'"
              class="compact-quiz-area"
            >
              <div class="quiz-card">
                <div class="quiz-simple-header">
                  <div class="quiz-simple-title">
                    <div class="quiz-header-icon">
                      <i class="ri-question-answer-line"></i>
                    </div>

                    <div class="min-width-0">
                      <span class="quiz-kicker">
                        {{ $t('ui-comprehension-quiz') }}
                      </span>

                      <h2>{{ selectedSlide.title }}</h2>

                      <p>
                        {{
                        isCurrentSlideCompleted
                            ? $t('t-quiz-already-completed-description')
                            : $t('t-quiz-completion-description')
                        }}
                      </p>
                    </div>
                  </div>

                  <div class="quiz-header-controls">
                    <span
                      class="quiz-slide-state"
                      :class="isCurrentSlideCompleted ? 'completed' : 'pending'"
                    >
                      <i
                        :class="isCurrentSlideCompleted
                          ? 'ri-checkbox-circle-line'
                          : 'ri-time-line'"
                      ></i>

                      {{
                        isCurrentSlideCompleted
                          ? $t('t-quiz-slide-completed')
                          : $t('t-quiz-completion-required')
                      }}
                    </span>

                    <div
                      v-if="selectedSlideQuestions.length && !showQuizSummary"
                      class="quiz-header-navigation"
                    >
                      <BButton
                        type="button"
                        variant="light"
                        class="quiz-header-nav-btn previous"
                        :disabled="isFirstQuizQuestion"
                        @click="goToPreviousQuizQuestion"
                      >
                        <i class="ri-arrow-left-line"></i>
                        <span>{{ $t('t-previous-feminine') }}</span>
                      </BButton>

                      <BButton
                        type="button"
                        variant="primary"
                        class="quiz-header-nav-btn next"
                        :disabled="
                          isLastQuizQuestion ||
                          !currentQuizQuestionAnswered
                        "
                        @click="goToNextQuizQuestion"
                      >
                        <span>{{ $t('t-next-feminine') }}</span>
                        <i class="ri-arrow-right-line"></i>
                      </BButton>

                      <BButton
                        v-if="!isQuizChecked"
                        type="button"
                        variant="success"
                        class="quiz-header-nav-btn verify"
                        :disabled="
                          !isLastQuizQuestion ||
                          !isQuizAnswered
                        "
                        @click="checkQuizAnswers"
                      >
                        <i class="ri-check-double-line"></i>
                        <span>{{ $t('t-check-answers') }}</span>
                      </BButton>

                      <BButton
                        v-else
                        type="button"
                        variant="primary"
                        class="quiz-header-nav-btn summary"
                        @click="showResultSummary"
                      >
                        <i class="ri-bar-chart-box-line"></i>
                        <span>{{ $t('t-summary') }}</span>
                      </BButton>

                      <button
                        type="button"
                        class="quiz-header-reset-btn"
                        :title="$t('t-reset-quiz')"
                        @click="resetQuiz"
                      >
                        <i class="ri-restart-line"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div
                  v-if="!selectedSlideQuestions.length"
                  class="quiz-empty-state"
                >
                  <div class="quiz-empty-icon">
                    <i class="ri-question-line"></i>
                  </div>

                  <h5>{{ $t('t-no-quiz-questions') }}</h5>
                  <p>{{ $t('t-quiz-empty') }}</p>
                </div>

                <div
                  v-else-if="showQuizSummary"
                  class="quiz-result-summary"
                  :class="isQuizValid ? 'success' : 'failed'"
                >
                  <div class="quiz-result-main">
                    <div class="quiz-result-icon">
                      <i
                        :class="isQuizValid
                          ? 'ri-trophy-line'
                          : 'ri-error-warning-line'"
                      ></i>
                    </div>

                    <span class="quiz-result-kicker">
                      {{ $t('ui-resultat-du-quiz') }}
                    </span>

                    <h3>
                      {{
                        isQuizValid
                          ? $t('t-quiz-passed')
                          : $t('t-quiz-retry')
                      }}
                    </h3>

                    <p>
                      {{
                        isQuizValid
                          ? $t('t-quiz-all-answers-correct')
                          : $t('t-quiz-incorrect-feedback')
                      }}
                    </p>
                  </div>

                  <div class="quiz-result-score">
                    <span>{{ $t('t-your-score') }}</span>

                    <div class="quiz-result-score-value">
                      <strong>{{ quizCorrectCount }}</strong>
                      <small>/ {{ selectedSlideQuestions.length }}</small>
                    </div>

                    <div class="quiz-result-score-percent">
                      {{ quizScorePercent }}%
                    </div>
                  </div>

                  <div class="quiz-result-stats">
                    <div class="quiz-result-stat correct">
                      <div class="quiz-result-stat-icon">
                        <i class="ri-checkbox-circle-line"></i>
                      </div>

                      <div>
                        <span>{{ $t('t-correct-answers') }}</span>
                        <strong>{{ quizCorrectCount }}</strong>
                      </div>
                    </div>

                    <div class="quiz-result-stat wrong">
                      <div class="quiz-result-stat-icon">
                        <i class="ri-close-circle-line"></i>
                      </div>

                      <div>
                        <span>{{ $t('t-incorrect-answers') }}</span>
                        <strong>{{ quizIncorrectCount }}</strong>
                      </div>
                    </div>

                    <div class="quiz-result-stat total">
                      <div class="quiz-result-stat-icon">
                        <i class="ri-question-answer-line"></i>
                      </div>

                      <div>
                        <span>{{ $t('t-total-questions') }}</span>
                        <strong>{{ selectedSlideQuestions.length }}</strong>
                      </div>
                    </div>
                  </div>

                  <div class="quiz-result-actions">
                    <BButton
                      type="button"
                      variant="light"
                      class="quiz-result-btn review"
                      @click="reviewQuizAnswers"
                    >
                      <i class="ri-arrow-left-line"></i>
                      {{ $t('ui-retour-aux-questions') }}
                    </BButton>

                    <BButton
                      type="button"
                      variant="primary"
                      class="quiz-result-btn restart"
                      @click="resetQuiz"
                    >
                      <i class="ri-restart-line"></i>
                      {{ $t('t-reset-quiz') }}
                    </BButton>
                  </div>
                </div>

                <template v-else>
                  <div class="quiz-compact-progress">
                    <div class="quiz-compact-progress-head">
                      <span>
                        {{ $t('t-question') }} {{ currentQuizQuestionIndex + 1 }}
                        / {{ selectedSlideQuestions.length }}
                      </span>

                      <strong>{{ quizNavigationPercent }}%</strong>
                    </div>

                    <div class="quiz-compact-progress-track">
                      <div :style="{ width: `${quizNavigationPercent}%` }"></div>
                    </div>

                    <div class="quiz-compact-dots">
                      <button
                        v-for="(question, index) in selectedSlideQuestions"
                        :key="question.id"
                        type="button"
                        class="quiz-compact-dot"
                        :class="{
                          active: currentQuizQuestionIndex === index,
                          answered: Boolean(selectedSlideQuizAnswers[question.id]),
                          correct:
                            isQuizChecked &&
                            isSelectedQuizAnswer(question, question.correct_answer),
                          wrong:
                            isQuizChecked &&
                            Boolean(selectedSlideQuizAnswers[question.id]) &&
                            !isSelectedQuizAnswer(question, question.correct_answer),
                        }"
                        @click="selectQuizQuestion(index)"
                      >
                        {{ index + 1 }}
                      </button>
                    </div>
                  </div>

                  <div
                    v-if="currentQuizQuestion"
                    class="quiz-single-question"
                  >
                    <div class="quiz-question-top">
                      <div class="quiz-question-number">
                        {{ currentQuizQuestionIndex + 1 }}
                      </div>

                      <div class="quiz-question-heading">
                        <div class="quiz-question-meta">
                          <span class="quiz-question-label">
                            {{ $t('t-question') }}
                          </span>

                          <span
                            class="quiz-question-type"
                            :class="normalizeQuestionType(currentQuizQuestion)"
                          >
                            <i :class="getQuestionTypeIcon(currentQuizQuestion)"></i>
                            {{ getQuestionTypeLabel(currentQuizQuestion) }}
                          </span>
                        </div>

                        <h3>{{ currentQuizQuestion.question }}</h3>
                      </div>
                    </div>

                    <div
                      class="quiz-options"
                      :class="{
                        'quiz-options-true-false':
                          normalizeQuestionType(currentQuizQuestion) === 'true_false',
                      }"
                    >
                      <button
                        v-for="option in getQuestionOptions(currentQuizQuestion)"
                        :key="option.key"
                        type="button"
                        class="quiz-option"
                        :disabled="isQuizChecked"
                        :class="getQuizOptionClass(currentQuizQuestion, option.key)"
                        @click="selectQuizAnswer(currentQuizQuestion.id, option.key)"
                      >
                        <span class="quiz-option-letter">
                          {{ option.label }}
                        </span>

                        <span class="quiz-option-text">
                          {{ option.value }}
                        </span>

                        <span class="quiz-option-indicator">
                          <i
                            v-if="
                              isQuizChecked &&
                              isCorrectQuizAnswer(currentQuizQuestion, option.key)
                            "
                            class="ri-checkbox-circle-fill"
                          ></i>

                          <i
                            v-else-if="
                              isWrongSelectedQuizAnswer(currentQuizQuestion, option.key)
                            "
                            class="ri-close-circle-fill"
                          ></i>

                          <i
                            v-else-if="
                              isSelectedQuizAnswer(currentQuizQuestion, option.key)
                            "
                            class="ri-radio-button-line"
                          ></i>

                          <i
                            v-else
                            class="ri-checkbox-blank-circle-line"
                          ></i>
                        </span>
                      </button>
                    </div>

                   
                  </div>

                </template>
              </div>
            </div>

            <div
              v-else
              class="compact-rich-content protected-text"
              tabindex="0"
              :aria-label="$t('t-lesson-content')"
            >
              {{ selectedSlide.content || 'No content available.' }}
            </div>
          </div>

          <div v-else class="compact-empty-content compact-empty-state">
            <div class="compact-empty-state-icon">
              <i class="ri-slideshow-line" aria-hidden="true"></i>
            </div>
            <span class="compact-empty-state-kicker">{{ $t('t-course-content-unavailable') }}</span>
            <h2>{{ $t('t-no-slide-available') }}</h2>
            <p>{{ $t('t-course-content-unavailable-description') }}</p>
            <button type="button" class="compact-empty-return" @click="goBackToCourses">
              <i class="ri-arrow-left-line" aria-hidden="true"></i>
              {{ $t('ui-back-to-courses') }}
            </button>
          </div>
        </div>

        <div class="compact-bottom-bar">
          <div v-if="selectedSlide" class="compact-audio-box">
            <template v-if="hasCurrentSlideVoiceOver">
              <button
                type="button"
                class="audio-icon-btn"
                :class="audioButtonStateClass"
                :title="audioButtonTitle"
                @click="toggleAudio"
              >
                <i :class="audioButtonIcon"></i>
              </button>

              <div class="audio-progress-wrap">
                <div class="audio-title">
                  <span>{{ audioLabelText }}</span>

                  <small>
                    {{ audioCurrentTimeLabel }} / {{ audioDurationLabel }}
                  </small>
                </div>

                <div class="audio-progress-track">
                  <div :style="{ width: `${audioProgressPercent}%` }"></div>
                </div>
              </div>

              <audio
                :key="selectedSlide.id"
                ref="voiceOverAudio"
                class="hidden-audio"
                :src="selectedSlide.voice_over_audio_url"
                preload="metadata"
                @loadedmetadata="handleAudioLoaded"
                @timeupdate="handleAudioTimeUpdate"
                @play="handleAudioPlay"
                @pause="handleAudioPause"
                @ended="handleAudioEnded"
                @contextmenu.prevent
              ></audio>
            </template>

            <template v-else>
              <div class="audio-icon-btn muted">
                <i class="bx bx-microphone-off"></i>
              </div>

              <span class="no-audio-text">
                {{ $t('t-no-voice-over') }}
              </span>
            </template>
          </div>

          <div v-if="selectedSlide" class="compact-nav-actions">
            <BButton
              variant="light"
              class="compact-nav-btn"
              :disabled="isFirstSlide"
              @click="prevSlide"
            >
              <i class="ri-arrow-left-line"></i>
              <span>{{ $t('t-previous') }}</span>
            </BButton>

            <BButton
              variant="success"
              class="compact-complete-btn"
              :disabled="!canCompleteCurrentSlide || isCompletingSlide"
              @click="markSlideCompleted"
            >
              <span
                v-if="isCompletingSlide"
                class="spinner-border spinner-border-sm"
              ></span>

              <i
                v-else
                class="ri-checkbox-circle-line"
              ></i>

              <span>
                {{
                  isCurrentSlideCompleted
                    ? $t('t-slide-completed')
                    : hasCurrentSlideVoiceOver && !audioFinished
                      ? $t('t-audio-required')
                      : hasQuizQuestions && !isQuizValid
                        ? $t('t-quiz-required')
                        : $t('t-complete-slide')
                }}
              </span>
            </BButton>

            <BButton
              v-if="canFinishCourse"
              variant="primary"
              class="compact-nav-btn finish-course-btn"
              @click="goToCourseFinish"
            >
              <span>{{ $t('t-finish-course') }}</span>
              <i class="ri-flag-line"></i>
            </BButton>

            <BButton
              v-else
              variant="primary"
              class="compact-nav-btn"
              :disabled="!canGoNext"
              @click="nextSlide"
            >
              <span>{{ $t('t-next') }}</span>
              <i class="ri-arrow-right-line"></i>
            </BButton>
          </div>

          <div v-else class="compact-empty-bottom">
            <span>{{ $t('t-course-content-unavailable-description') }}</span>
            <button type="button" @click="goBackToCourses">
              {{ $t('ui-back-to-courses') }}
              <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </main>
    </div>
  </section>
</template>

<style scoped>
.compact-player-page {
  --navbar-height: 82px;
  --footer-height: 58px;
  --player-surface: var(--paper, #f2efe8);
  --player-border: var(--sand, #e2ded3);
  --player-accent: var(--signal, #1f5cff);
  --player-accent-hover: var(--signal-deep, #1546cc);

  height: calc(100vh - var(--navbar-height) - var(--footer-height));
  height: calc(100dvh - var(--navbar-height) - var(--footer-height));
  min-height: 0;
  background: var(--player-surface);
  overflow: hidden;
  position: relative;
}

.compact-player-shell {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: 310px minmax(0, 1fr);
}

.sidebar-overlay {
  display: none;
  border: none;
  padding: 0;
  background: rgba(7, 17, 38, 0.42);
}

.compact-sidebar {
  height: 100%;
  min-height: 0;
  background: var(--chalk, #ffffff);
  border-right: 1px solid var(--player-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 20;
}

.compact-sidebar-header {
  height: 70px;
  min-height: 70px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--player-border);
  display: flex;
  align-items: center;
  gap: 12px;
}

.back-btn,
.sidebar-close-btn,
.mobile-menu-btn {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border: none;
  border-radius: 12px;
  background: #071126;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.sidebar-close-btn {
  display: none;
  margin-left: auto;
  background: #f1f5f9;
  color: #071126;
}

.mobile-menu-btn {
  display: none;
  background: #eef8ff;
  color: #0d8fe9;
}

.compact-sidebar-header h6 {
  color: #071126;
  font-size: 15px;
  font-weight: 900;
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.compact-sidebar-header span {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.compact-progress {
  height: 5px;
  min-height: 5px;
  background: var(--player-border);
}

.compact-progress div {
  height: 100%;
  background: var(--player-accent);
  transition: width 220ms ease-out;
}

.compact-chapters {
  flex: 1;
  min-height: 0;
  padding: 14px;
  overflow-y: auto;
}

.compact-chapter {
  margin-bottom: 10px;
}

.compact-chapter-btn {
  width: 100%;
  border: 1px solid #edf2f7;
  background: #ffffff;
  border-radius: 14px;
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}

.compact-chapter-btn.active {
  border-color: var(--player-accent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.compact-chapter-btn.locked {
  opacity: 0.55;
  cursor: not-allowed;
}

.compact-chapter-btn:disabled {
  pointer-events: none;
}

.chapter-index {
  width: 30px;
  height: 30px;
  min-width: 30px;
  border-radius: 10px;
  background: #0d8fe9;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 900;
}

.chapter-title {
  color: #071126;
  font-size: 13px;
  font-weight: 800;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.compact-slides {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 8px 0 0 40px;
}

.compact-slide-btn {
  width: 100%;
  height: 36px;
  border: 1px solid #edf2f7;
  border-radius: 11px;
  background: #ffffff;
  color: #475569;
  padding: 0 9px;
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  font-weight: 700;
  text-align: left;
  transition: all 0.2s ease;
}

.compact-slide-btn span {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.compact-slide-btn:hover:not(:disabled) {
  border-color: var(--player-accent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
  color: var(--player-accent-hover);
}

.compact-slide-btn.completed {
  color: #0ab39c;
  border-color: rgba(10, 179, 156, 0.35);
  background: rgba(10, 179, 156, 0.06);
}

.compact-slide-btn.active {
  color: #ffffff;
  border-color: var(--player-accent);
  background: var(--player-accent);
}

.compact-slide-btn.active i,
.compact-slide-btn.active .voice-dot {
  color: #ffffff;
}

.compact-slide-btn.active.completed {
  color: #ffffff;
  border-color: var(--player-accent);
  background: var(--player-accent);
}

.compact-slide-btn.active.completed i,
.compact-slide-btn.active.completed .voice-dot {
  color: #ffffff;
}

.compact-slide-btn.locked {
  opacity: 0.55;
  cursor: not-allowed;
}

.compact-slide-btn:disabled {
  pointer-events: none;
}

.voice-dot {
  color: #0d8fe9;
}

.compact-main {
  height: 100%;
  min-height: 0;
  min-width: 0;
  display: grid;
  grid-template-rows: 70px auto minmax(0, 1fr) 76px;
  overflow: hidden;
}

.compact-topbar {
  min-height: 70px;
  background: var(--chalk, #ffffff);
  border-bottom: 1px solid var(--player-border);
  padding: 10px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.slide-counter {
  color: var(--player-accent);
  font-size: 12px;
  font-weight: 900;
}

.compact-topbar h1 {
  color: #071126;
  font-size: 20px;
  font-weight: 900;
  margin: 4px 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.topbar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
}

.topbar-finish-course-btn {
  height: 38px;
  padding: 0 15px;
  border: none;
  border-radius: 12px;
  color: #ffffff;
  background: var(--player-accent);
  box-shadow: none;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  white-space: nowrap;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.topbar-finish-course-btn:hover {
  color: #ffffff;
  transform: translateY(-1px);
  background: var(--player-accent-hover);
  box-shadow: none;
}

.topbar-finish-course-btn i {
  font-size: 16px;
}

.status-pill {
  height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.status-pill.done {
  background: #e8f7f4;
  color: #0ab39c;
}

.status-pill.pending {
  background: #fff7e6;
  color: var(--warning-deep, #7a4b00);
}

.status-pill.unavailable {
  background: color-mix(in srgb, var(--danger, #ff4d4d) 10%, white);
  color: var(--danger-deep, #b42318);
}

.compact-content-card {
  min-height: 0;
  height: 100%;
  padding: 14px 18px;
  overflow: hidden;
}

.compact-slide-content {
  height: 100%;
  min-height: 0;
  background: var(--chalk, #ffffff);
  border: 1px solid var(--player-border);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 8px 22px rgba(7, 11, 20, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
}

.compact-rich-content {
  width: 100%;
  height: 100%;
  padding: 28px;
  color: var(--slate, #3a4256);
  line-height: 1.75;
  font-size: 16px;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-color: var(--stone, #8a8f9c) transparent;
}

.compact-rich-content :deep(h1),
.compact-rich-content :deep(h2),
.compact-rich-content :deep(h3),
.compact-rich-content :deep(h4) {
  color: var(--ink, #070b14);
  line-height: 1.3;
}

.compact-rich-content :deep(img),
.compact-rich-content :deep(video) {
  max-width: 100%;
  height: auto;
}

.compact-rich-content :deep(table) {
  display: block;
  max-width: 100%;
  overflow-x: auto;
}

.compact-video-box {
  position: relative;
  width: 100%;
  height: 100%;
  background: #0f172a;
}

.compact-video-box iframe {
  width: 100%;
  height: 100%;
}

.compact-file-area,
.compact-image-box {
  width: 100%;
  height: 100%;
}

.compact-image-box {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
}

.compact-image-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.compact-empty-content {
  width: 100%;
  height: 100%;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
}

.compact-empty-content i {
  font-size: 42px;
  color: #0d8fe9;
}

.compact-empty-state {
  padding: 28px;
  border: 1px solid var(--player-border);
  border-radius: 14px;
  background: var(--chalk, #ffffff);
  text-align: center;
}

.compact-empty-state-icon {
  width: 62px;
  height: 62px;
  display: grid;
  place-items: center;
  border-radius: 18px;
  background: color-mix(in srgb, var(--player-accent) 9%, white);
}

.compact-empty-state-icon i {
  color: var(--player-accent);
  font-size: 30px;
}

.compact-empty-state-kicker {
  color: var(--player-accent-hover);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.compact-empty-state h2 {
  max-width: 26ch;
  margin: 0;
  color: var(--ink, #070b14);
  font-size: clamp(20px, 2vw, 28px);
  font-weight: 800;
}

.compact-empty-state p {
  max-width: 54ch;
  margin: 0;
  color: var(--slate, #3a4256);
  line-height: 1.6;
}

.compact-empty-return,
.compact-empty-bottom button {
  min-height: 44px;
  padding: 0 16px;
  border: 0;
  border-radius: 10px;
  color: #fff;
  background: var(--player-accent);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 700;
}

.compact-empty-return:hover,
.compact-empty-bottom button:hover {
  background: var(--player-accent-hover);
}

.compact-empty-chapters {
  padding: 22px 18px;
  color: var(--stone, #8a8f9c);
  text-align: center;
}

.compact-empty-chapters i {
  color: var(--player-accent);
  font-size: 24px;
}

.compact-empty-chapters p {
  margin: 8px 0 0;
  color: var(--slate, #3a4256);
  font-size: 13px;
  line-height: 1.5;
}

.open-link-btn {
  height: 42px;
  padding: 0 18px;
  border-radius: 12px;
  background: #071126;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  font-weight: 800;
}

.compact-quiz-area {
  width: 100%;
  height: 100%;
  min-height: 0;
  overflow: hidden;
  padding: 10px;
  background:
    radial-gradient(circle at top right, rgba(104, 124, 254, 0.1), transparent 32%),
    #f5f7fb;
}

.quiz-card {
  height: 100%;
  min-height: 0;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e6eaf2;
  border-radius: 22px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  display: grid;
  grid-template-rows: auto auto minmax(0, 1fr);
}

.quiz-simple-header {
  padding: 12px 16px;
  border-bottom: 1px solid #eef1f6;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  background:
    linear-gradient(135deg, rgba(104, 124, 254, 0.08), rgba(255, 255, 255, 0));
}

.quiz-simple-title {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.quiz-header-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 12px 24px rgba(104, 124, 254, 0.28);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.quiz-header-icon i {
  font-size: 18px;
}

.quiz-kicker {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 999px;
  color: #5264d9;
  background: rgba(104, 124, 254, 0.1);
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.quiz-simple-header h2 {
  color: #111827;
  font-size: 18px;
  font-weight: 900;
  margin: 0 0 4px;
}

.quiz-simple-header p {
  color: #64748b;
  font-size: 10px;
  line-height: 1.35;
  margin: 0;
}

.quiz-slide-state {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}

.quiz-slide-state.completed {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.11);
}

.quiz-slide-state.pending {
  color: #c17a09;
  background: rgba(247, 184, 75, 0.15);
}


.quiz-header-controls {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  flex-shrink: 0;
}

.quiz-header-navigation {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.quiz-header-nav-btn {
  height: 32px;
  border-radius: 9px;
  padding-inline: 10px;
  font-size: 10px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  white-space: nowrap;
}

.quiz-header-nav-btn.previous {
  color: #475569;
  border-color: #dfe5ef;
  background: #ffffff;
}

.quiz-header-nav-btn.next {
  border-color: transparent;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 6px 14px rgba(104, 124, 254, 0.2);
}

.quiz-header-nav-btn.verify {
  border-color: transparent;
  background: var(--success, #19c39a);
  box-shadow: none;
}

.quiz-header-nav-btn.summary {
  border-color: transparent;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 6px 14px rgba(104, 124, 254, 0.2);
}

.quiz-header-nav-btn:disabled {
  opacity: 0.48;
  box-shadow: none;
}

.quiz-header-reset-btn {
  width: 32px;
  height: 32px;
  min-width: 32px;
  border: 1px solid #dfe5ef;
  border-radius: 9px;
  background: #ffffff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.18s ease;
}

.quiz-header-reset-btn:hover {
  color: #405189;
  border-color: #687cfe;
  background: rgba(104, 124, 254, 0.06);
}

.quiz-header-reset-btn i {
  font-size: 15px;
}

.quiz-compact-progress {
  padding: 5px 16px;
  border-bottom: 1px solid #eef1f6;
  background: #fbfcff;
}

.quiz-compact-progress-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 5px;
  color: #64748b;
  font-size: 9px;
  font-weight: 900;
}

.quiz-compact-progress-head strong {
  color: #5264d9;
}

.quiz-compact-progress-track {
  height: 3px;
  border-radius: 999px;
  background: #e9edf6;
  overflow: hidden;
}

.quiz-compact-progress-track div {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #687cfe 0%, #405189 100%);
  transition: width 0.2s ease;
}

.quiz-compact-dots {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 4px;
}

.quiz-compact-dot {
  width: 23px;
  height: 23px;
  border: 1px solid #dfe5ef;
  border-radius: 7px;
  background: #ffffff;
  color: #64748b;
  font-size: 9px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.quiz-compact-dot.active {
  color: #ffffff;
  border-color: #687cfe;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
}

.quiz-compact-dot.answered:not(.active) {
  color: #5264d9;
  border-color: rgba(104, 124, 254, 0.3);
  background: rgba(104, 124, 254, 0.08);
}

.quiz-compact-dot.correct {
  color: #ffffff;
  border-color: #0ab39c;
  background: #0ab39c;
}

.quiz-compact-dot.wrong {
  color: #ffffff;
  border-color: #f06548;
  background: #f06548;
}

.quiz-single-question {
  min-height: 0;
  overflow: hidden;
  padding: 12px 18px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.quiz-question-top {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  margin-bottom: 8px;
}

.quiz-question-number {
  width: 32px;
  height: 32px;
  min-width: 32px;
  border-radius: 10px;
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 8px 17px rgba(104, 124, 254, 0.23);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 900;
}

.quiz-question-heading {
  flex: 1;
  min-width: 0;
}

.quiz-question-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  margin-bottom: 4px;
}

.quiz-question-label {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.quiz-question-type {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 900;
  line-height: 1;
  white-space: nowrap;
}

.quiz-question-type.qcm {
  color: #405189;
  background: rgba(104, 124, 254, 0.1);
  border: 1px solid rgba(104, 124, 254, 0.18);
}

.quiz-question-type.true_false {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.1);
  border: 1px solid rgba(10, 179, 156, 0.2);
}

.quiz-question-heading h3 {
  color: #111827;
  font-size: 16px;
  font-weight: 900;
  line-height: 1.35;
  margin: 0;
}

.quiz-options {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}

.quiz-options-true-false {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.quiz-option {
  width: 100%;
  min-height: 40px;
  border: 1px solid #dfe5ef;
  border-radius: 12px;
  background: #ffffff;
  padding: 6px 10px;
  display: flex;
  align-items: center;
  gap: 9px;
  text-align: left;
  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.quiz-option:hover:not(:disabled) {
  transform: translateY(-1px);
  border-color: rgba(104, 124, 254, 0.68);
  background: rgba(104, 124, 254, 0.045);
  box-shadow: 0 8px 18px rgba(64, 81, 137, 0.08);
}

.quiz-option.selected {
  border-color: #687cfe;
  background: rgba(104, 124, 254, 0.08);
}

.quiz-option.correct {
  border-color: rgba(10, 179, 156, 0.48);
  background: rgba(10, 179, 156, 0.09);
}

.quiz-option.wrong {
  border-color: rgba(240, 101, 72, 0.48);
  background: rgba(240, 101, 72, 0.08);
}

.quiz-option:disabled {
  cursor: default;
}

.quiz-option-letter {
  width: 30px;
  height: 30px;
  min-width: 30px;
  border-radius: 9px;
  color: #405189;
  background: #eef1f6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 900;
}

.quiz-option.selected .quiz-option-letter {
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
}

.quiz-option.correct .quiz-option-letter {
  color: #ffffff;
  background: #0ab39c;
}

.quiz-option.wrong .quiz-option-letter {
  color: #ffffff;
  background: #f06548;
}

.quiz-option-text {
  flex: 1;
  min-width: 0;
  color: #334155;
  font-size: 12px;
  font-weight: 800;
  line-height: 1.3;
}

.quiz-option-indicator {
  width: 19px;
  height: 19px;
  min-width: 19px;
  color: #b6c0cf;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.quiz-option-indicator i {
  font-size: 17px;
}

.quiz-option.selected .quiz-option-indicator {
  color: #687cfe;
}

.quiz-option.correct .quiz-option-indicator {
  color: #0ab39c;
}

.quiz-option.wrong .quiz-option-indicator {
  color: #f06548;
}

.quiz-correction {
  margin-top: 8px;
  border-radius: 11px;
  padding: 8px 10px;
  font-size: 10px;
  line-height: 1.4;
}

.quiz-correction.correct {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.1);
  border: 1px solid rgba(10, 179, 156, 0.16);
}

.quiz-correction.wrong {
  color: #b42318;
  background: rgba(240, 101, 72, 0.09);
  border: 1px solid rgba(240, 101, 72, 0.16);
}

.quiz-correction-title {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 5px;
}

.quiz-correction-title i {
  font-size: 17px;
}

.quiz-explanation {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding-top: 6px;
  border-top: 1px solid currentColor;
  opacity: 0.88;
}

.quiz-result-summary {
  grid-row: 2 / 4;
  min-height: 0;
  padding: 20px 24px;
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) 190px;
  grid-template-areas:
    "main score"
    "stats stats"
    "actions actions";
  align-content: center;
  gap: 16px 22px;
  overflow-y: auto;
  background:
    radial-gradient(circle at top right, rgba(104, 124, 254, 0.11), transparent 32%),
    #ffffff;
}

.quiz-result-summary.success {
  background:
    radial-gradient(circle at top right, rgba(10, 179, 156, 0.12), transparent 34%),
    #ffffff;
}

.quiz-result-summary.failed {
  background:
    radial-gradient(circle at top right, rgba(240, 101, 72, 0.1), transparent 34%),
    #ffffff;
}

.quiz-result-main {
  grid-area: main;
  align-self: center;
}

.quiz-result-icon {
  width: 54px;
  height: 54px;
  border-radius: 17px;
  color: #ffffff;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 12px 25px rgba(104, 124, 254, 0.24);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 11px;
}

.quiz-result-summary.success .quiz-result-icon {
  background: var(--success, #19c39a);
  box-shadow: none;
}

.quiz-result-summary.failed .quiz-result-icon {
  background: var(--danger, #ff4d4d);
  box-shadow: none;
}

.quiz-result-icon i {
  font-size: 26px;
}

.quiz-result-kicker {
  display: block;
  color: #687cfe;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 5px;
}

.quiz-result-summary.success .quiz-result-kicker {
  color: #0a8f7d;
}

.quiz-result-summary.failed .quiz-result-kicker {
  color: #d64d34;
}

.quiz-result-main h3 {
  color: #111827;
  font-size: 22px;
  font-weight: 900;
  margin: 0 0 6px;
}

.quiz-result-main p {
  max-width: 620px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.55;
  margin: 0;
}

.quiz-result-score {
  grid-area: score;
  align-self: center;
  min-height: 150px;
  border: 1px solid rgba(104, 124, 254, 0.2);
  border-radius: 20px;
  background: rgba(104, 124, 254, 0.08);
  color: #405189;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.quiz-result-summary.success .quiz-result-score {
  color: #087f6f;
  border-color: rgba(10, 179, 156, 0.22);
  background: rgba(10, 179, 156, 0.09);
}

.quiz-result-summary.failed .quiz-result-score {
  color: #b42318;
  border-color: rgba(240, 101, 72, 0.22);
  background: rgba(240, 101, 72, 0.08);
}

.quiz-result-score > span {
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.quiz-result-score-value {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
  margin: 5px 0;
}

.quiz-result-score-value strong {
  font-size: 42px;
  font-weight: 900;
  line-height: 1;
}

.quiz-result-score-value small {
  font-size: 15px;
  font-weight: 900;
}

.quiz-result-score-percent {
  font-size: 12px;
  font-weight: 900;
}

.quiz-result-stats {
  grid-area: stats;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.quiz-result-stat {
  min-height: 68px;
  border: 1px solid #e5eaf2;
  border-radius: 15px;
  background: #ffffff;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.quiz-result-stat-icon {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.quiz-result-stat-icon i {
  font-size: 18px;
}

.quiz-result-stat.correct .quiz-result-stat-icon {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.11);
}

.quiz-result-stat.wrong .quiz-result-stat-icon {
  color: #b42318;
  background: rgba(240, 101, 72, 0.1);
}

.quiz-result-stat.total .quiz-result-stat-icon {
  color: #405189;
  background: rgba(104, 124, 254, 0.1);
}

.quiz-result-stat span {
  display: block;
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
  margin-bottom: 2px;
}

.quiz-result-stat strong {
  color: #111827;
  font-size: 18px;
  font-weight: 900;
}

.quiz-result-actions {
  grid-area: actions;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
}

.quiz-result-btn {
  height: 39px;
  border-radius: 11px;
  padding-inline: 14px;
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.quiz-result-btn.review {
  color: #475569;
  border-color: #dfe5ef;
  background: #ffffff;
}

.quiz-result-btn.restart {
  border-color: transparent;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  box-shadow: 0 7px 16px rgba(104, 124, 254, 0.22);
}

.quiz-empty-state {
  min-height: 0;
  height: 100%;
  padding: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #64748b;
  text-align: center;
}

.quiz-empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  color: #687cfe;
  background: rgba(104, 124, 254, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 13px;
}

.quiz-empty-icon i {
  font-size: 29px;
}

.quiz-empty-state h5 {
  color: #111827;
  font-weight: 900;
  margin-bottom: 5px;
}

.quiz-empty-state p {
  margin: 0;
  font-size: 13px;
}

.compact-bottom-bar {
  min-height: 76px;
  background: #ffffff;
  border-top: 1px solid #e8edf3;
  padding: 10px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  z-index: 5;
}

.compact-empty-bottom {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  color: var(--slate, #3a4256);
  font-size: 13px;
}

.compact-audio-box {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.audio-icon-btn {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border: none;
  border-radius: 14px;
  background: #eef8ff;
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.audio-icon-btn i {
  font-size: 26px;
  line-height: 1;
}

.audio-icon-btn:hover {
  transform: translateY(-1px);
}

.audio-icon-btn.playing {
  background: #071126;
  color: #ffffff;
}

.audio-icon-btn.stopped {
  background: #fff7e6;
  color: #f7b84b;
}

.audio-icon-btn.replay {
  background: #eef8ff;
  color: #0d8fe9;
}

.audio-icon-btn.done {
  background: #e8f7f4;
  color: #0ab39c;
}

.audio-icon-btn.muted {
  background: #f1f5f9;
  color: #94a3b8;
}

.audio-progress-wrap {
  flex: 1;
  min-width: 0;
}

.audio-title {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #071126;
  font-size: 12px;
  font-weight: 900;
  margin-bottom: 7px;
}

.audio-title small {
  color: #64748b;
  font-weight: 800;
  white-space: nowrap;
}

.audio-progress-track {
  height: 6px;
  border-radius: 999px;
  background: #e9eef6;
  overflow: hidden;
}

.audio-progress-track div {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #0d8fe9 0%, #071126 100%);
}

.hidden-audio {
  display: none;
}

.no-audio-text {
  color: #64748b;
  font-size: 13px;
  font-weight: 800;
}

.compact-nav-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.compact-nav-btn,
.compact-complete-btn {
  height: 42px;
  border-radius: 13px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.compact-nav-btn:disabled,
.compact-complete-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.finish-course-btn {
  background: var(--success, #19c39a) !important;
  border-color: var(--success, #19c39a) !important;
  color: #ffffff !important;
}

.protected-content,
.protected-text,
.protected-media {
  user-select: none;
  -webkit-user-select: none;
}

.min-width-0 {
  min-width: 0;
}

@media (max-width: 991.98px) {
  .compact-player-shell {
    grid-template-columns: 1fr;
  }

  .mobile-menu-btn {
    display: inline-flex;
  }

  .sidebar-close-btn {
    display: inline-flex;
  }

  .compact-sidebar {
    position: fixed;
    top: var(--navbar-height);
    bottom: var(--footer-height);
    left: 0;
    width: min(350px, 88vw);
    height: auto;
    transform: translateX(-105%);
    transition: transform 0.25s ease;
    border-right: 1px solid #e8edf3;
    box-shadow: 20px 0 40px rgba(15, 23, 42, 0.18);
    z-index: 1004;
  }

  .compact-sidebar.open {
    transform: translateX(0);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    top: var(--navbar-height);
    bottom: var(--footer-height);
    left: 0;
    right: 0;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
    z-index: 1003;
  }

  .sidebar-overlay.show {
    opacity: 1;
    pointer-events: auto;
  }

  .compact-main {
    grid-template-rows: 64px auto minmax(0, 1fr) 112px;
  }

  .compact-topbar {
    min-height: 64px;
    padding: 9px 16px;
  }

  .topbar-finish-course-btn {
    height: 36px;
    padding-inline: 12px;
  }

  .compact-topbar h1 {
    font-size: 18px;
  }

  .compact-bottom-bar {
    min-height: 112px;
    flex-direction: column;
    align-items: stretch;
    padding: 10px 16px;
  }

  .compact-nav-actions {
    width: 100%;
  }

  .compact-nav-btn,
  .compact-complete-btn {
    flex: 1;
    justify-content: center;
  }
}

@media (max-width: 575.98px) {
  .compact-player-page {
    --navbar-height: 82px;
    --footer-height: 58px;

    height: calc(100dvh - var(--navbar-height) - var(--footer-height));
  }

  .compact-main {
    grid-template-rows: 62px auto minmax(0, 1fr) 118px;
  }

  .compact-topbar {
    min-height: 62px;
    padding: 8px 12px;
  }

  .compact-topbar h1 {
    font-size: 16px;
  }

  .slide-counter {
    font-size: 11px;
  }

  .status-pill {
    display: none;
  }

  .topbar-actions {
    gap: 6px;
  }

  .topbar-finish-course-btn {
    width: 40px;
    height: 40px;
    min-width: 40px;
    padding: 0;
    border-radius: 12px;
  }

  .topbar-finish-course-btn span {
    display: none;
  }

  .topbar-finish-course-btn i {
    font-size: 18px;
  }

  .compact-content-card {
    padding: 8px;
  }

  .compact-slide-content {
    border-radius: 16px;
  }

  .compact-rich-content {
    padding: 18px;
    font-size: 14px;
  }

  .compact-quiz-area {
    padding: 7px;
  }

  .quiz-card {
    border-radius: 16px;
  }

  .quiz-simple-header {
    padding: 10px;
    flex-direction: column;
    gap: 8px;
  }

  .quiz-header-controls {
    width: 100%;
    align-items: stretch;
  }

  .quiz-header-navigation {
    width: 100%;
    justify-content: stretch;
  }

  .quiz-header-nav-btn {
    flex: 1;
    padding-inline: 7px;
  }

  .quiz-header-reset-btn {
    flex: 0 0 32px;
  }

  .quiz-simple-title {
    gap: 10px;
  }

  .quiz-header-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 13px;
  }

  .quiz-simple-header h2 {
    font-size: 17px;
  }

  .quiz-slide-state {
    align-self: flex-start;
  }

  .quiz-result-summary {
    padding: 14px;
    grid-template-columns: 1fr;
    grid-template-areas:
      "main"
      "score"
      "stats"
      "actions";
    align-content: start;
  }

  .quiz-result-score {
    min-height: 115px;
  }

  .quiz-result-stats {
    grid-template-columns: 1fr;
  }

  .quiz-result-actions {
    flex-direction: column;
  }

  .quiz-result-btn {
    width: 100%;
  }

  .quiz-compact-progress {
    padding: 6px 10px;
  }

  .quiz-single-question {
    padding: 10px;
  }

  .quiz-option {
    min-height: 42px;
    padding: 5px 8px;
  }

  .quiz-option-letter {
    width: 28px;
    height: 28px;
    min-width: 28px;
  }

  .quiz-question-heading h3 {
    font-size: 16px;
  }

  .quiz-question-meta {
    align-items: flex-start;
    flex-direction: column;
  }

  .quiz-options-true-false {
    grid-template-columns: 1fr;
  }

  .compact-bottom-bar {
    min-height: 118px;
    padding: 10px 12px;
  }

  .compact-audio-box {
    gap: 9px;
  }

  .audio-icon-btn {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 12px;
  }

  .audio-icon-btn i {
    font-size: 24px;
  }

  .audio-title {
    font-size: 11px;
  }

  .compact-nav-actions {
    gap: 7px;
  }

  .compact-nav-btn,
  .compact-complete-btn {
    height: 40px;
    padding-inline: 8px;
    font-size: 12px;
  }

  .compact-nav-btn span,
  .compact-complete-btn span {
    white-space: nowrap;
  }
}


/* ========================================================================== */
/* Mobile quiz responsive corrections                                         */
/* ========================================================================== */
@media (max-width: 575.98px) {
  .compact-player-page {
    --navbar-height: 82px;
    --footer-height: 58px;
  }

  .compact-main {
    grid-template-rows: 56px auto minmax(0, 1fr) 94px;
  }

  .compact-topbar {
    min-height: 56px;
    padding: 6px 10px;
  }

  .compact-topbar h1 {
    margin-top: 2px;
    font-size: 15px;
  }

  .slide-counter {
    font-size: 10px;
  }

  .compact-content-card {
    padding: 5px;
  }

  .compact-slide-content {
    border-radius: 14px;
  }

  .compact-quiz-area {
    padding: 4px;
  }

  .quiz-card {
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
  }

  .quiz-simple-header {
    padding: 8px;
    gap: 6px;
  }

  .quiz-simple-title {
    gap: 8px;
  }

  .quiz-header-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 10px;
  }

  .quiz-header-icon i {
    font-size: 17px;
  }

  .quiz-kicker {
    padding: 3px 7px;
    margin-bottom: 3px;
    font-size: 8px;
  }

  .quiz-simple-header h2 {
    margin-bottom: 0;
    font-size: 16px;
    line-height: 1.15;
  }

  .quiz-simple-header p {
    display: none;
  }

  .quiz-header-controls {
    width: 100%;
    gap: 5px;
    align-items: stretch;
  }

  .quiz-slide-state {
    align-self: flex-start;
    padding: 4px 8px;
    font-size: 9px;
  }

  .quiz-header-navigation {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr)) 32px;
    gap: 5px;
  }

  .quiz-header-nav-btn {
    width: 100%;
    height: 32px;
    min-width: 0;
    padding: 0 4px;
    border-radius: 9px;
    font-size: 9px;
    gap: 3px;
  }

  .quiz-header-nav-btn i {
    font-size: 12px;
  }

  .quiz-header-reset-btn {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
  }

  .quiz-compact-progress {
    padding: 4px 8px;
  }

  .quiz-compact-progress-head {
    margin-bottom: 3px;
    font-size: 8px;
  }

  .quiz-compact-progress-track {
    height: 3px;
  }

  .quiz-compact-dots {
    gap: 4px;
    margin-top: 3px;
  }

  .quiz-compact-dot {
    width: 21px;
    height: 21px;
    border-radius: 6px;
    font-size: 8px;
  }

  .quiz-single-question {
    padding: 8px;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .quiz-question-top {
    gap: 7px;
    margin-bottom: 6px;
  }

  .quiz-question-number {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 9px;
    font-size: 11px;
  }

  .quiz-question-meta {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 5px;
    margin-bottom: 3px;
  }

  .quiz-question-label {
    font-size: 8px;
  }

  .quiz-question-type {
    padding: 4px 7px;
    font-size: 8px;
  }

  .quiz-question-heading h3 {
    font-size: 15px;
    line-height: 1.3;
  }

  .quiz-options {
    gap: 5px;
  }

  .quiz-options-true-false {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .quiz-option {
    min-height: 38px;
    padding: 4px 7px;
    gap: 7px;
    border-radius: 10px;
  }

  .quiz-option-letter {
    width: 26px;
    height: 26px;
    min-width: 26px;
    border-radius: 8px;
    font-size: 10px;
  }

  .quiz-option-text {
    font-size: 11px;
    line-height: 1.2;
  }

  .quiz-option-indicator {
    width: 18px;
    height: 18px;
    min-width: 18px;
  }

  .quiz-option-indicator i {
    font-size: 16px;
  }

  .quiz-correction {
    margin-top: 6px;
    padding: 7px 9px;
    border-radius: 10px;
    font-size: 9px;
  }

  .quiz-correction-title {
    margin-bottom: 3px;
  }

  .quiz-result-summary {
    padding: 10px;
    gap: 9px;
    overflow-y: auto;
    grid-template-columns: 1fr;
    grid-template-areas:
      "main"
      "score"
      "stats"
      "actions";
  }

  .quiz-result-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    margin-bottom: 7px;
  }

  .quiz-result-icon i {
    font-size: 21px;
  }

  .quiz-result-main h3 {
    font-size: 17px;
  }

  .quiz-result-main p {
    font-size: 10px;
  }

  .quiz-result-score {
    min-height: 82px;
    border-radius: 14px;
  }

  .quiz-result-score-value strong {
    font-size: 30px;
  }

  .quiz-result-stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 5px;
  }

  .quiz-result-stat {
    min-height: 56px;
    padding: 7px;
    gap: 6px;
    border-radius: 11px;
  }

  .quiz-result-stat-icon {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 8px;
  }

  .quiz-result-stat span {
    font-size: 7px;
  }

  .quiz-result-stat strong {
    font-size: 14px;
  }

  .quiz-result-actions {
    flex-direction: row;
    gap: 6px;
  }

  .quiz-result-btn {
    width: auto;
    flex: 1;
    height: 34px;
    padding-inline: 7px;
    font-size: 9px;
  }

  .compact-bottom-bar {
    min-height: 94px;
    padding: 7px 9px;
    gap: 6px;
  }

  .compact-audio-box {
    min-height: 32px;
    flex: 0 0 auto;
    gap: 7px;
  }

  .audio-icon-btn {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 10px;
  }

  .audio-icon-btn i {
    font-size: 20px;
  }

  .no-audio-text,
  .audio-title {
    font-size: 10px;
  }

  .compact-nav-actions {
    width: 100%;
    gap: 5px;
  }

  .compact-nav-btn,
  .compact-complete-btn {
    height: 36px;
    padding-inline: 6px;
    border-radius: 10px;
    font-size: 10px;
  }
}

@media (max-width: 390px) {
  .quiz-header-nav-btn {
    font-size: 0;
  }

  .quiz-header-nav-btn i {
    font-size: 14px;
  }

  .quiz-slide-state {
    font-size: 8px;
  }

  .quiz-result-stats {
    grid-template-columns: 1fr;
  }

  .quiz-result-actions {
    flex-direction: column;
  }

  .quiz-result-btn {
    width: 100%;
  }
}



.viewer-training-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: 14px 18px 0;
  padding: 11px 13px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}

.viewer-training-period {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.viewer-training-period > i {
  width: 34px;
  height: 34px;
  min-width: 34px;
  border-radius: 11px;
  background: #eef2ff;
  color: #405189;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
}

.viewer-training-period span,
.viewer-training-period strong {
  display: block;
}

.viewer-training-period span {
  color: #64748b;
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
}

.viewer-training-period strong {
  color: #334155;
  font-size: 12px;
  font-weight: 900;
}

.viewer-training-accreditations {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 7px;
}

.viewer-training-accreditations span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 9px;
  border-radius: 999px;
  background: rgba(10, 179, 156, 0.12);
  color: #0a8f7e;
  font-size: 10.5px;
  font-weight: 850;
}

@media (max-width: 767.98px) {
  .viewer-training-strip {
    align-items: flex-start;
    flex-direction: column;
    margin-inline: 12px;
  }

  .viewer-training-accreditations {
    justify-content: flex-start;
  }
}

.compact-quiz-area {
  background: var(--player-surface);
}

.quiz-simple-header {
  background: var(--chalk, #ffffff);
}

.quiz-header-icon,
.quiz-header-nav-btn.next,
.quiz-header-nav-btn.summary,
.quiz-result-btn.restart {
  background: var(--player-accent);
  box-shadow: none;
}

.quiz-question-number,
.quiz-result-icon {
  background: var(--player-accent);
  box-shadow: none;
}

.quiz-question-type.qcm {
  color: var(--player-accent-hover);
  border-color: color-mix(in srgb, var(--player-accent) 24%, transparent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-option:hover:not(:disabled) {
  border-color: var(--player-accent);
  background: color-mix(in srgb, var(--player-accent) 4%, white);
  box-shadow: none;
}

.quiz-option.selected {
  border-color: var(--player-accent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-option.selected .quiz-option-indicator {
  color: var(--player-accent);
}

.quiz-result-stat.total .quiz-result-stat-icon {
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-empty-icon {
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-kicker,
.quiz-compact-dot.answered:not(.active) {
  color: var(--player-accent-hover);
  border-color: color-mix(in srgb, var(--player-accent) 30%, transparent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-compact-progress-head strong,
.quiz-result-kicker,
.quiz-result-stat.total .quiz-result-stat-icon,
.quiz-empty-icon {
  color: var(--player-accent);
}

.quiz-compact-progress-track div,
.quiz-compact-dot.active,
.quiz-option.selected .quiz-option-letter {
  border-color: var(--player-accent);
  background: var(--player-accent);
}

.quiz-header-reset-btn:hover {
  color: var(--player-accent-hover);
  border-color: var(--player-accent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
}

.quiz-result-score {
  border-color: color-mix(in srgb, var(--player-accent) 24%, transparent);
  background: color-mix(in srgb, var(--player-accent) 8%, white);
  color: var(--player-accent-hover);
}

.quiz-result-summary,
.quiz-result-summary.success,
.quiz-result-summary.failed {
  background: var(--chalk, #ffffff);
}

.compact-bottom-bar {
  background: var(--chalk, #ffffff);
  border-top-color: var(--player-border);
}

.viewer-training-strip {
  border-color: var(--player-border);
  background: var(--chalk, #ffffff);
}

.viewer-training-period > i {
  background: color-mix(in srgb, var(--player-accent) 8%, white);
  color: var(--player-accent-hover);
}

@media (max-width: 575.98px) {
  .compact-main {
    grid-template-rows: 56px auto minmax(0, 1fr) auto;
  }

  .compact-bottom-bar {
    min-height: 0;
  }

  .back-btn,
  .sidebar-close-btn,
  .mobile-menu-btn,
  .audio-icon-btn,
  .compact-nav-btn,
  .compact-complete-btn,
  .quiz-header-nav-btn,
  .quiz-header-reset-btn {
    min-height: 44px;
  }

  .back-btn,
  .sidebar-close-btn,
  .mobile-menu-btn {
    width: 44px;
    min-width: 44px;
  }

  .audio-icon-btn {
    width: 44px;
    min-width: 44px;
  }

  .compact-slide-btn {
    height: auto;
    min-height: 44px;
  }

  .compact-nav-btn,
  .compact-complete-btn,
  .quiz-header-nav-btn {
    height: auto;
  }

  .quiz-header-navigation {
    grid-template-columns: repeat(3, minmax(0, 1fr)) 44px;
  }

  .quiz-header-reset-btn {
    width: 44px;
    min-width: 44px;
    height: 44px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .compact-progress div,
  .compact-sidebar,
  .sidebar-overlay,
  .compact-slide-btn,
  .topbar-finish-course-btn,
  .audio-icon-btn,
  .quiz-header-reset-btn {
    transition: none;
  }
}

.compact-player-page {
  --navbar-height: 100px;
  margin-top: var(--navbar-height);
  height: calc(100dvh - var(--navbar-height) - var(--footer-height));
}

@media (max-width: 991.98px) {
  .compact-player-page {
    --navbar-height: 88px;
  }
}

@media (max-width: 575.98px) {
  .compact-empty-state {
    padding: 20px 16px;
  }

  .compact-empty-bottom {
    justify-content: center;
  }

  .compact-empty-bottom > span {
    display: none;
  }
}

</style>
