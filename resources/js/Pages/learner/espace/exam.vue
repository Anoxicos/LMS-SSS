<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import i18n from '@/i18n'

defineOptions({
  layout: LayoutAnonyme,
})

const t = (...args) => i18n.global.t(...args)

const props = defineProps({
  exam: {
    type: Object,
    required: true,
  },
})

const currentQuestionIndex = ref(0)

const form = useForm({
  answers: {},
  auto_submit: false,
})

const remainingSeconds = ref(
  Number(props.exam.remaining_seconds || 0)
)

const isAutoSubmitting = ref(false)
const answerSaveError = ref('')
const serverClockOffsetMs = ref(0)

let timerInterval = null
let autoSubmitTriggered = false

const normalizeText = value => {
  return String(value || '').trim().toLowerCase()
}

const getInitialAnswerKey = question => {
  if (!question?.response_user) return ''

  if (normalizeText(question.response_user) === normalizeText(question.response1)) {
    return 'response1'
  }

  if (normalizeText(question.response_user) === normalizeText(question.response2)) {
    return 'response2'
  }

  if (normalizeText(question.response_user) === normalizeText(question.response3)) {
    return 'response3'
  }

  if (['response1', 'response2', 'response3'].includes(question.response_user)) {
    return question.response_user
  }

  return ''
}

;(props.exam.questions || []).forEach(question => {
  form.answers[question.id] = getInitialAnswerKey(question)
})

const questions = computed(() => {
  return props.exam.questions || []
})

const currentQuestion = computed(() => {
  return questions.value[currentQuestionIndex.value] || null
})

const totalQuestions = computed(() => {
  return Number(props.exam.total_questions || questions.value.length || 0)
})

const isSubmitted = computed(() => {
  return props.exam.status !== 'in_progress'
})

const requiresCourseRetake = computed(() => {
  return Boolean(props.exam.requires_course_retake)
})

const hasAttemptsRemaining = computed(() => {
  return Number(props.exam.enrollment?.attempts_rest || 0) > 0
})

const canRetryExam = computed(() => {
  return Boolean(props.exam.can_retry_exam)
})

const hasPassedExam = computed(() => {
  return Boolean(props.exam.has_passed_exam)
})

const currentAnswer = computed(() => {
  if (!currentQuestion.value) return ''

  return form.answers[currentQuestion.value.id] || ''
})

const answeredQuestions = computed(() => {
  return questions.value.filter(question => {
    return Boolean(form.answers[question.id])
  }).length
})

const unansweredQuestions = computed(() => {
  return Math.max(totalQuestions.value - answeredQuestions.value, 0)
})

const canSubmit = computed(() => {
  return answeredQuestions.value === totalQuestions.value &&
    totalQuestions.value > 0 &&
    !isSubmitted.value
})

const progressPercent = computed(() => {
  if (!totalQuestions.value) return 0

  return Math.round((answeredQuestions.value / totalQuestions.value) * 100)
})

const resultPercent = computed(() => {
  return Number(props.exam.score || 0).toFixed(0)
})

const isLastQuestion = computed(() => {
  return currentQuestionIndex.value === totalQuestions.value - 1
})

const timerIsWarning = computed(() => {
  return remainingSeconds.value > 60 &&
    remainingSeconds.value <= 300
})

const timerIsDanger = computed(() => {
  return remainingSeconds.value <= 60
})

const timerIsExpired = computed(() => {
  return remainingSeconds.value <= 0
})

const formattedRemainingTime = computed(() => {
  const total = Math.max(0, remainingSeconds.value)
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const seconds = total % 60

  const paddedMinutes = String(minutes).padStart(2, '0')
  const paddedSeconds = String(seconds).padStart(2, '0')

  if (hours > 0) {
    return `${String(hours).padStart(2, '0')}:${paddedMinutes}:${paddedSeconds}`
  }

  return `${paddedMinutes}:${paddedSeconds}`
})

const answerOptions = computed(() => {
  if (!currentQuestion.value) return []

  return [
    {
      key: 'response1',
      label: 'A',
      text: currentQuestion.value.response1,
    },
    {
      key: 'response2',
      label: 'B',
      text: currentQuestion.value.response2,
    },
    {
      key: 'response3',
      label: 'C',
      text: currentQuestion.value.response3,
    },
  ].filter(option => Boolean(option.text))
})

const selectQuestion = index => {
  currentQuestionIndex.value = index
}

const nextQuestion = () => {
  if (currentQuestionIndex.value < totalQuestions.value - 1) {
    currentQuestionIndex.value++
  }
}

const previousQuestion = () => {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--
  }
}

const getCsrfToken = () => {
  return document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content') || ''
}

const saveAnswerToServer = async (questionId, answer) => {
  if (isSubmitted.value || timerIsExpired.value) {
    return
  }

  answerSaveError.value = ''

  try {
    const response = await fetch(
      route('learner-space.exam.answer', props.exam.hashid),
      {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          question_id: questionId,
          answer: answer || null,
        }),
      }
    )

    const payload = await response.json().catch(() => ({}))

    if (response.status === 409 || payload.expired) {
      remainingSeconds.value = 0
      await autoSubmitExam()
      return
    }

    if (!response.ok) {
      throw new Error(
        payload.message || t('t-answer-save-failed')
      )
    }

    if (
      payload.remaining_seconds !== undefined &&
      payload.remaining_seconds !== null
    ) {
      remainingSeconds.value = Math.min(
        remainingSeconds.value,
        Number(payload.remaining_seconds)
      )
    }
  } catch (error) {
    answerSaveError.value =
      t('t-answer-saved-locally')
  }
}

const selectAnswer = answer => {
  if (isSubmitted.value || timerIsExpired.value) return
  if (!currentQuestion.value) return

  form.answers[currentQuestion.value.id] = answer

  saveAnswerToServer(
    currentQuestion.value.id,
    answer
  )
}

const clearCurrentAnswer = () => {
  if (isSubmitted.value || timerIsExpired.value) return
  if (!currentQuestion.value) return

  form.answers[currentQuestion.value.id] = ''

  saveAnswerToServer(
    currentQuestion.value.id,
    null
  )
}

const goToFirstUnanswered = () => {
  const index = questions.value.findIndex(question => {
    return !form.answers[question.id]
  })

  if (index !== -1) {
    currentQuestionIndex.value = index
  }
}

const submitExam = () => {
  if (!canSubmit.value) return
  if (timerIsExpired.value) return

  form.auto_submit = false

  form.post(
    route('learner-space.exam.submit', props.exam.hashid),
    {
      preserveScroll: true,
    }
  )
}

const autoSubmitExam = async () => {
  if (isSubmitted.value) return
  if (autoSubmitTriggered) return

  autoSubmitTriggered = true
  isAutoSubmitting.value = true
  form.auto_submit = true

  if (timerInterval) {
    window.clearInterval(timerInterval)
    timerInterval = null
  }

  form.post(
    route('learner-space.exam.submit', props.exam.hashid),
    {
      preserveScroll: true,
      onFinish: () => {
        isAutoSubmitting.value = false
      },
    }
  )
}

const calculateRemainingFromDeadline = () => {
  if (!props.exam.expires_at) {
    return Number(props.exam.remaining_seconds || 0)
  }

  const serverNowMs = Date.now() + serverClockOffsetMs.value
  const expiresAtMs = new Date(props.exam.expires_at).getTime()

  return Math.max(
    0,
    Math.ceil((expiresAtMs - serverNowMs) / 1000)
  )
}

const updateTimer = () => {
  remainingSeconds.value = calculateRemainingFromDeadline()

  if (remainingSeconds.value <= 0) {
    autoSubmitExam()
  }
}

const blockExamShortcuts = event => {
  const key = String(event.key || '').toLowerCase()
  const blockedWithModifier = (
    event.ctrlKey || event.metaKey
  ) && ['c', 'x', 'v', 'a'].includes(key)

  if (blockedWithModifier) {
    event.preventDefault()
  }
}

const preventExamAction = event => {
  event.preventDefault()
}

onMounted(() => {
  if (props.exam.server_now) {
    serverClockOffsetMs.value =
      new Date(props.exam.server_now).getTime() -
      Date.now()
  }

  document.addEventListener('keydown', blockExamShortcuts)
  document.addEventListener('copy', preventExamAction)
  document.addEventListener('cut', preventExamAction)
  document.addEventListener('paste', preventExamAction)
  document.addEventListener('contextmenu', preventExamAction)
  document.addEventListener('dragstart', preventExamAction)

  if (!isSubmitted.value) {
    updateTimer()

    timerInterval = window.setInterval(
      updateTimer,
      1000
    )
  }
})

onBeforeUnmount(() => {
  if (timerInterval) {
    window.clearInterval(timerInterval)
  }

  document.removeEventListener('keydown', blockExamShortcuts)
  document.removeEventListener('copy', preventExamAction)
  document.removeEventListener('cut', preventExamAction)
  document.removeEventListener('paste', preventExamAction)
  document.removeEventListener('contextmenu', preventExamAction)
  document.removeEventListener('dragstart', preventExamAction)
})

const backToFinish = () => {
  router.visit(route('learner-space.course-finish', props.exam.enrollment.hashid))
}

const goToCourseViewer = () => {
  router.visit(
    route('learner-space.course-viewer', props.exam.enrollment.hashid)
  )
}

const retryExam = () => {
  if (!canRetryExam.value) return
  if (!props.exam.enrollment?.hashid) return

  router.visit(
    route('learner-space.exam.start', props.exam.enrollment.hashid)
  )
}

const goToCourses = () => {
  router.visit(route('learner-space.my-courses'))
}

const getQuestionStatus = question => {
  return form.answers[question.id] ? 'answered' : 'pending'
}

const isQuestionActive = index => {
  return Number(currentQuestionIndex.value) === Number(index)
}

const getQuestionMapClass = (question, index) => {
  return {
    active: isQuestionActive(index),
    pending: getQuestionStatus(question) === 'pending',
    answered: getQuestionStatus(question) === 'answered',
  }
}

const getOptionClass = option => {
  if (!currentQuestion.value || isSubmitted.value) {
    return {}
  }

  return {
    selected: currentAnswer.value === option.key,
  }
}
</script>

<template>
  <Head :title="exam.title || $t('ui-exam')" />

  <section
    class="exam-page"
    @copy.prevent
    @cut.prevent
    @paste.prevent
    @contextmenu.prevent
    @dragstart.prevent
  >
    <div class="exam-shell">
      <!-- LEFT PANEL -->
      <aside class="exam-sidebar">
        <div class="sidebar-top">
          <button
            type="button"
            class="back-btn"
            :aria-label="$t('t-back')"
            @click="backToFinish"
          >
            <i class="ri-arrow-left-line"></i>
          </button>

          <div class="sidebar-title min-width-0">
          <span>{{ $t('t-final-assessment') }}</span>
          <h6>{{ exam.course?.title || $t('t-course-exam') }}</h6>
          </div>
        </div>

        <div
          v-if="isSubmitted"
          class="sidebar-result"
          :class="exam.passed ? 'passed' : 'failed'"
        >
          <div class="result-icon">
            <i :class="exam.passed ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'"></i>
          </div>

          <span>{{ exam.passed ? $t('t-passed') : $t('t-failed') }}</span>

          <strong>{{ resultPercent }}%</strong>

          <p>
            {{ $t('t-passing-score') }}: {{ exam.pass_score }}%
          </p>

          <small
            v-if="!exam.passed && requiresCourseRetake"
            class="result-retake-note"
          >
            {{ $t('t-retake-course-before-exam') }}
          </small>
        </div>

        <div
          v-else
          class="sidebar-progress"
        >
          <div class="progress-main">
            <div>
              <span>{{ $t('t-progress') }}</span>
              <strong>{{ answeredQuestions }} / {{ totalQuestions }}</strong>
            </div>

            <div class="progress-percent">
              {{ progressPercent }}%
            </div>
          </div>

          <div class="progress-track">
            <div :style="{ width: `${progressPercent}%` }"></div>
          </div>
        </div>

        <div
          v-if="!isSubmitted"
          class="sidebar-timer"
          :class="{
            warning: timerIsWarning,
            danger: timerIsDanger,
          }"
        >
          <div class="sidebar-timer-icon">
            <i class="ri-timer-line"></i>
          </div>

          <div class="sidebar-timer-content">
            <span>{{ $t('t-time-remaining') }}</span>
            <strong>{{ formattedRemainingTime }}</strong>
          </div>

          <small>
            {{ exam.time_limit_minutes }} {{ $t('ui-min') }}
          </small>
        </div>

        <div v-if="!isSubmitted" class="question-map-card">
          <div class="question-map-header">
            <div>
              <h6>{{ $t('t-question-map') }}</h6>
              <p>{{ $t('t-question-map-description') }}</p>
            </div>
          </div>

          <div class="question-map-legend">
            <span>
              <i class="legend-dot pending"></i>
              {{ $t('t-to-answer') }}
            </span>

            <span>
              <i class="legend-dot answered"></i>
              {{ $t('t-answered') }}
            </span>
          </div>

          <div class="question-map-grid">
            <button
              v-for="(question, index) in questions"
              :key="question.id"
              type="button"
              class="question-map-dot"
              :class="getQuestionMapClass(question, index)"
              @click="selectQuestion(index)"
            >
              {{ index + 1 }}
            </button>
          </div>
        </div>

      </aside>

      <!-- MAIN PANEL -->
      <main class="exam-main">
        <header class="exam-header">
          <div class="exam-header-content min-width-0">
            <div class="exam-badge">
              <i class="ri-shield-check-line"></i>
              {{ $t('t-attempt-number', { number: exam.attempt_number }) }}
            </div>

            <h1>{{ exam.title || $t('t-final-exam') }}</h1>

            <p>
              {{ exam.description || $t('t-exam-instructions') }}
            </p>
          </div>

          <div class="exam-header-side">
            <div
              v-if="!isSubmitted"
              class="header-timer"
              :class="{
                warning: timerIsWarning,
                danger: timerIsDanger,
              }"
            >
              <i class="ri-time-line"></i>

              <div>
                <span>{{ $t('t-remaining') }}</span>
                <strong>{{ formattedRemainingTime }}</strong>
              </div>
            </div>

            <div
              class="exam-status"
              :class="isSubmitted ? (exam.passed ? 'passed' : 'failed') : 'progress'"
            >
              <i
                :class="isSubmitted
                  ? exam.passed
                    ? 'ri-checkbox-circle-line'
                    : 'ri-close-circle-line'
                  : 'ri-time-line'"
              ></i>

              <span>
                {{
                  isSubmitted
                    ? exam.passed
                      ? $t('t-passed')
                      : $t('t-failed')
                    : $t('t-in-progress')
                }}
              </span>
            </div>
          </div>
        </header>

        <div
          v-if="!isSubmitted"
          class="exam-summary-strip in-progress-summary"
        >
          <div class="summary-item">
            <span>{{ $t('t-total-questions') }}</span>
            <strong>{{ totalQuestions }}</strong>
          </div>

          <div class="summary-item success">
            <span>{{ $t('t-answered') }}</span>
            <strong>{{ answeredQuestions }}</strong>
          </div>

          <div class="summary-item warning">
            <span>{{ $t('t-to-answer') }}</span>
            <strong>{{ unansweredQuestions }}</strong>
          </div>
        </div>

        <div
          v-else
          class="exam-result-overview"
          :class="exam.passed ? 'passed' : 'failed'"
        >
          <div class="exam-result-overview-icon">
            <i :class="exam.passed ? 'ri-award-line' : 'ri-file-list-3-line'"></i>
          </div>

          <div class="exam-result-overview-copy">
            <span>{{ $t('t-final-score') }}</span>
            <strong>{{ resultPercent }}%</strong>
            <p>
              {{ $t('t-passing-score') }}: {{ exam.pass_score }}%
            </p>
          </div>

          <div class="exam-result-overview-status">
            <i :class="exam.passed ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"></i>
            {{ exam.passed ? $t('t-passed') : $t('t-failed') }}
          </div>
        </div>

        <div
          v-if="isSubmitted && !exam.passed && requiresCourseRetake"
          class="course-retake-alert"
        >
          <div class="course-retake-alert-icon">
            <i class="ri-book-open-line"></i>
          </div>

          <div class="course-retake-alert-content">
            <h3>{{ $t('t-retake-course-before-exam') }}</h3>

            <p>
              {{ $t('t-retake-course-description', { count: exam.enrollment.attempts_rest }) }}
            </p>
          </div>

          <button
            type="button"
            @click="goToCourseViewer"
          >
            {{ $t('t-return-to-course') }}
            <i class="ri-arrow-right-line"></i>
          </button>
        </div>

        <div
          v-if="isSubmitted && exam.passed"
          class="exam-passed-final-alert"
        >
          <div class="exam-passed-final-alert-icon">
            <i class="ri-award-line"></i>
          </div>

          <div class="exam-passed-final-alert-content">
            <h3>{{ $t('t-final-exam-passed') }}</h3>

            <p>
              {{ $t('t-enrollment-completed-exam-closed') }}
            </p>
          </div>

          <button
            type="button"
            @click="goToCourses"
          >
            {{ $t('t-my-courses') }}
            <i class="ri-arrow-right-line"></i>
          </button>
        </div>

        <div
          v-if="isSubmitted && !exam.passed && canRetryExam"
          class="exam-retry-alert"
        >
          <div class="exam-retry-alert-icon">
            <i class="ri-repeat-line"></i>
          </div>

          <div class="exam-retry-alert-content">
            <h3>{{ $t('t-exam-retry-available') }}</h3>

            <p>
              {{ $t('t-exam-retry-description', { count: exam.enrollment.attempts_rest }) }}
            </p>
          </div>

          <div class="exam-retry-alert-actions">
            <button
              type="button"
              class="review-course-btn"
              @click="goToCourseViewer"
            >
              <i class="ri-book-open-line"></i>
              {{ $t('t-review-course') }}
            </button>

            <button
              type="button"
              class="retry-exam-btn"
              @click="retryExam"
            >
              {{ $t('t-retry-exam') }}
              <i class="ri-arrow-right-line"></i>
            </button>
          </div>
        </div>

        <div v-if="!isSubmitted" class="exam-body">
          <div
            v-if="currentQuestion"
            class="question-card"
          >
            <div class="question-card-header">
              <div>
                <span class="question-step">
                {{ $t('t-question-count', { current: currentQuestionIndex + 1, total: totalQuestions }) }}
                </span>

                <h2>{{ currentQuestion.name_chapter || $t('t-exam-section') }}</h2>
              </div>

              <div class="question-actions">
                <div
                  v-if="currentAnswer"
                  class="answered-pill"
                >
                  <i class="ri-checkbox-circle-line"></i>
                  {{ $t('t-answered') }}
                </div>

                <button
                  v-if="currentAnswer && !isSubmitted"
                  type="button"
                  class="clear-answer-btn"
                  @click="clearCurrentAnswer"
                >
                  <i class="ri-refresh-line"></i>
                  {{ $t('t-clear-answer') }}
                </button>
              </div>
            </div>

            <div
              v-if="answerSaveError"
              class="answer-save-warning"
            >
              <i class="ri-wifi-off-line"></i>
              {{ answerSaveError }}
            </div>

            <div class="question-title-panel">
              <div class="question-index-big">
                {{ currentQuestionIndex + 1 }}
              </div>

              <h3>{{ currentQuestion.question }}</h3>
            </div>

            <div class="answers-list">
              <button
                v-for="option in answerOptions"
                :key="option.key"
                type="button"
                class="answer-option"
                :class="getOptionClass(option)"
                :disabled="isSubmitted || timerIsExpired"
                @click="selectAnswer(option.key)"
              >
                <span class="answer-letter">
                  {{ option.label }}
                </span>

                <span class="answer-text">
                  {{ option.text }}
                </span>

                <i
                  v-if="!isSubmitted && currentAnswer === option.key"
                  class="ri-radio-button-line selected-icon"
                ></i>
              </button>
            </div>
          </div>

          <div
            v-else
            class="empty-state"
          >
            <div class="empty-icon">
              <i class="ri-question-line"></i>
            </div>

            <h5>{{ $t('t-no-questions-available') }}</h5>

            <p>{{ $t('t-exam-empty-description') }}</p>
          </div>
        </div>

        <footer class="exam-footer">
          <div class="footer-left">
            <button
              v-if="!isSubmitted"
              type="button"
              class="footer-btn secondary"
              :disabled="currentQuestionIndex === 0"
              @click="previousQuestion"
            >
              <i class="ri-arrow-left-line"></i>
              {{ $t('t-previous') }}
            </button>
          </div>

          <div class="footer-info">
            <span v-if="!isSubmitted">
              {{ $t('t-answered-question-count', { answered: answeredQuestions, total: totalQuestions }) }}
            </span>

            <span v-else>
              {{ $t('t-score') }}: {{ resultPercent }}%
            </span>
          </div>

          <div class="footer-right">
            <button
              v-if="!isSubmitted && !canSubmit"
              type="button"
              class="footer-btn outline"
              :disabled="unansweredQuestions === 0"
              @click="goToFirstUnanswered"
            >
              <i class="ri-search-eye-line"></i>
              {{ $t('t-review-unanswered') }}
            </button>

            <button
              v-if="currentQuestionIndex < totalQuestions - 1"
              type="button"
              class="footer-btn primary"
              @click="nextQuestion"
            >
              {{ $t('t-next') }}
              <i class="ri-arrow-right-line"></i>
            </button>

            <button
              v-else-if="!isSubmitted"
              type="button"
              class="footer-btn success"
              :disabled="!canSubmit || form.processing"
              @click="submitExam"
            >
              <span
                v-if="form.processing"
                class="spinner-border spinner-border-sm"
              ></span>

              <i
                v-else
                class="ri-send-plane-line"
              ></i>

              {{ $t('t-submit-exam') }}
            </button>

            <button
              v-else-if="requiresCourseRetake"
              type="button"
              class="footer-btn success"
              @click="goToCourseViewer"
            >
              {{ $t('t-complete-course-again') }}
              <i class="ri-book-open-line"></i>
            </button>

            <template v-else-if="canRetryExam">
              <button
                type="button"
                class="footer-btn outline"
                @click="goToCourseViewer"
              >
                <i class="ri-book-open-line"></i>
                {{ $t('t-review-course') }}
              </button>

              <button
                type="button"
                class="footer-btn success"
                @click="retryExam"
              >
                {{ $t('t-retry-exam') }}
                <i class="ri-repeat-line"></i>
              </button>
            </template>

            <button
              v-else
              type="button"
              class="footer-btn primary"
              @click="goToCourses"
            >
              {{ $t('t-my-courses') }}
              <i class="ri-book-open-line"></i>
            </button>
          </div>
        </footer>
      </main>
    </div>

    <div
      v-if="isAutoSubmitting"
      class="exam-timeout-overlay"
    >
      <div class="exam-timeout-card">
        <div class="exam-timeout-icon">
          <i class="ri-timer-flash-line"></i>
        </div>

        <h3>{{ $t('t-time-is-over') }}</h3>

        <p>
          {{ $t('t-auto-submitting-answers') }}
        </p>

        <span class="spinner-border"></span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.exam-page {
  --app-header-height: 104px;
  --app-footer-height: 58px;

  min-height: calc(100dvh - var(--app-header-height));
  background:
    radial-gradient(circle at top left, rgba(13, 143, 233, 0.18), transparent 34%),
    radial-gradient(circle at bottom right, rgba(64, 81, 137, 0.12), transparent 36%),
    linear-gradient(180deg, #f7fbff 0%, #ffffff 52%, #f8fafc 100%);
  padding: 18px 42px calc(var(--app-footer-height) + 18px);
  overflow-y: auto;
}

.exam-shell {
  width: min(1540px, 100%);
  min-height: calc(100dvh - var(--app-header-height) - var(--app-footer-height) - 36px);
  height: auto;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 350px minmax(0, 1fr);
  background: #ffffff;
  border: 1px solid #e8edf3;
  border-radius: 30px;
  overflow: visible;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
}

.exam-sidebar {
  min-height: 100%;
  border-radius: 30px 0 0 30px;
  background:
    radial-gradient(circle at top left, rgba(13, 143, 233, 0.28), transparent 44%),
    linear-gradient(180deg, #071126 0%, #0f172a 100%);
  color: #ffffff;
  display: flex;
  flex-direction: column;
}

.sidebar-top {
  min-height: 82px;
  padding: 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  gap: 12px;
}

.back-btn {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border: none;
  border-radius: 15px;
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.sidebar-title span {
  display: block;
  color: rgba(255, 255, 255, 0.62);
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 4px;
}

.sidebar-title h6 {
  color: #ffffff;
  font-size: 15px;
  font-weight: 900;
  margin-bottom: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-progress,
.sidebar-result,
.question-map-card {
  margin: 18px;
  padding: 18px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.progress-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}

.progress-main span {
  display: block;
  color: rgba(255, 255, 255, 0.62);
  font-size: 12px;
  font-weight: 800;
  margin-bottom: 4px;
}

.progress-main strong {
  color: #ffffff;
  font-size: 24px;
  font-weight: 900;
}

.progress-percent {
  width: 58px;
  height: 58px;
  min-width: 58px;
  border-radius: 50%;
  background: linear-gradient(135deg, #9bdceb 0%, #0d8fe9 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 900;
  box-shadow: 0 12px 26px rgba(13, 143, 233, 0.28);
}

.progress-track {
  height: 8px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  overflow: hidden;
}

.progress-track div {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #9bdceb 0%, #0d8fe9 100%);
}

.sidebar-result {
  text-align: center;
}

.sidebar-result.passed {
  background: rgba(10, 179, 156, 0.14);
  border-color: rgba(10, 179, 156, 0.28);
}

.sidebar-result.failed {
  background: rgba(240, 101, 72, 0.14);
  border-color: rgba(240, 101, 72, 0.28);
}

.result-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.12);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 34px;
}

.sidebar-result.passed .result-icon {
  color: #0ab39c;
}

.sidebar-result.failed .result-icon {
  color: #f06548;
}

.sidebar-result span {
  display: block;
  color: rgba(255, 255, 255, 0.72);
  font-size: 13px;
  font-weight: 900;
  margin-bottom: 6px;
}

.sidebar-result strong {
  display: block;
  color: #ffffff;
  font-size: 46px;
  line-height: 1;
  font-weight: 900;
  margin-bottom: 10px;
}

.sidebar-result p {
  color: rgba(255, 255, 255, 0.72);
  margin-bottom: 0;
  font-size: 13px;
  font-weight: 700;
}

.question-map-header h6 {
  color: #ffffff;
  font-size: 15px;
  font-weight: 900;
  margin-bottom: 4px;
}

.question-map-header p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 14px;
}

.question-map-legend {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 14px;
}

.question-map-legend span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 11px;
  font-weight: 800;
}

.legend-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  display: inline-flex;
}

.legend-dot.pending {
  background: rgba(255, 255, 255, 0.28);
}

.legend-dot.answered {
  background: #9bdceb;
}

.legend-dot.correct {
  background: #0ab39c;
}

.legend-dot.wrong {
  background: #f06548;
}

.question-map-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 8px;
}

.question-map-dot {
  height: 38px;
  border: none;
  border-radius: 12px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.11);
  font-size: 13px;
  font-weight: 900;
  transition: all 0.2s ease;
}

.question-map-dot:hover {
  transform: translateY(-1px);
}

.question-map-dot.active {
  background: linear-gradient(135deg, #0d8fe9 0%, #2563eb 100%);
  box-shadow: 0 10px 20px rgba(13, 143, 233, 0.25);
}

.question-map-dot.answered {
  background: rgba(155, 220, 235, 0.28);
  color: #9bdceb;
}

.question-map-dot.correct {
  background: rgba(10, 179, 156, 0.2);
  color: #0ab39c;
}

.question-map-dot.wrong {
  background: rgba(240, 101, 72, 0.2);
  color: #f06548;
}

.question-map-dot.active.answered,
.question-map-dot.active.correct,
.question-map-dot.active.wrong {
  color: #ffffff;
}

.exam-main {
  min-width: 0;
  min-height: 0;
  align-self: start;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  border-radius: 0 30px 30px 0;
}

.exam-header {
  padding: 24px 30px 20px;
  background: #ffffff;
  border-bottom: 1px solid #e8edf3;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 22px;
  flex-shrink: 0;
}

.exam-badge {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #0d8fe9;
  background: rgba(13, 143, 233, 0.1);
  border-radius: 999px;
  padding: 8px 13px;
  font-size: 12px;
  font-weight: 900;
  margin-bottom: 10px;
}

.exam-header h1 {
  color: #071126;
  font-size: 28px;
  line-height: 1.22;
  font-weight: 900;
  margin-bottom: 6px;
  max-width: 980px;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  word-break: break-word;
}

.exam-header p {
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 0;
}

.exam-status {
  height: 42px;
  padding: 0 15px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 900;
  white-space: nowrap;
}

.exam-status.progress {
  background: #e8f4ff;
  color: #0d8fe9;
}

.exam-status.passed {
  background: #e8f7f4;
  color: #0ab39c;
}

.exam-status.failed {
  background: #fdecea;
  color: #f06548;
}

.exam-summary-strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  padding: 16px 30px;
  background: #ffffff;
  border-bottom: 1px solid #e8edf3;
}

.summary-item {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  padding: 12px 14px;
  background: #f8fafc;
}

.summary-item span {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  margin-bottom: 4px;
}

.summary-item strong {
  color: #071126;
  font-size: 22px;
  font-weight: 900;
  line-height: 1;
}

.summary-item.success strong {
  color: #0ab39c;
}

.summary-item.warning strong {
  color: #f7b84b;
}

.summary-item.result.passed strong {
  color: #0ab39c;
}

.summary-item.result.failed strong {
  color: #f06548;
}

.exam-summary-strip.in-progress-summary {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.exam-result-overview {
  margin: 18px 30px 0;
  padding: 22px 24px;
  border: 1px solid rgba(13, 143, 233, 0.2);
  border-radius: 22px;
  background: linear-gradient(135deg, rgba(36, 87, 117, 0.08), rgba(13, 143, 233, 0.08));
  display: flex;
  align-items: center;
  gap: 18px;
}

.exam-result-overview.passed {
  border-color: rgba(10, 179, 156, 0.28);
  background: rgba(10, 179, 156, 0.08);
}

.exam-result-overview.failed {
  border-color: rgba(240, 101, 72, 0.25);
  background: rgba(240, 101, 72, 0.07);
}

.exam-result-overview-icon {
  width: 58px;
  height: 58px;
  min-width: 58px;
  border-radius: 18px;
  color: #ffffff;
  background: linear-gradient(135deg, #245775 0%, #0d8fe9 100%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
}

.exam-result-overview.passed .exam-result-overview-icon {
  background: linear-gradient(135deg, #0ab39c 0%, #087f6f 100%);
}

.exam-result-overview.failed .exam-result-overview-icon {
  background: linear-gradient(135deg, #f06548 0%, #b42318 100%);
}

.exam-result-overview-copy {
  flex: 1;
  min-width: 0;
}

.exam-result-overview-copy span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 3px;
}

.exam-result-overview-copy strong {
  display: block;
  color: #071126;
  font-size: 42px;
  line-height: 1;
  font-weight: 900;
  margin-bottom: 7px;
}

.exam-result-overview-copy p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
}

.exam-result-overview-status {
  padding: 9px 14px;
  border-radius: 999px;
  color: #0d8fe9;
  background: rgba(13, 143, 233, 0.1);
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  white-space: nowrap;
}

.exam-result-overview.passed .exam-result-overview-status {
  color: #087f6f;
  background: rgba(10, 179, 156, 0.12);
}

.exam-result-overview.failed .exam-result-overview-status {
  color: #b42318;
  background: rgba(240, 101, 72, 0.12);
}

.exam-passed-final-alert {
  margin: 16px 30px 0;
  border: 1px solid rgba(10, 179, 156, 0.28);
  border-radius: 18px;
  background: rgba(10, 179, 156, 0.08);
  color: #087f6f;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.exam-passed-final-alert-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: #0ab39c;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.exam-passed-final-alert-content {
  flex: 1;
  min-width: 0;
}

.exam-passed-final-alert h3 {
  color: #087f6f;
  font-size: 15px;
  font-weight: 900;
  margin: 0 0 4px;
}

.exam-passed-final-alert p {
  color: #168f7f;
  font-size: 12px;
  line-height: 1.5;
  margin: 0;
}

.exam-passed-final-alert button {
  height: 39px;
  padding-inline: 13px;
  border: none;
  border-radius: 11px;
  background: #071126;
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.exam-retry-alert {
  margin: 16px 30px 0;
  border: 1px solid rgba(10, 179, 156, 0.28);
  border-radius: 18px;
  background: rgba(10, 179, 156, 0.08);
  color: #087f6f;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.exam-retry-alert-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: #0ab39c;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.exam-retry-alert-content {
  flex: 1;
  min-width: 0;
}

.exam-retry-alert h3 {
  color: #087f6f;
  font-size: 15px;
  font-weight: 900;
  margin: 0 0 4px;
}

.exam-retry-alert p {
  color: #168f7f;
  font-size: 12px;
  line-height: 1.5;
  margin: 0;
}

.exam-retry-alert-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex-shrink: 0;
}

.exam-retry-alert-actions button {
  height: 39px;
  padding-inline: 13px;
  border-radius: 11px;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.exam-retry-alert-actions button:hover {
  transform: translateY(-1px);
}

.exam-retry-alert-actions .review-course-btn {
  border: 1px solid rgba(8, 127, 111, 0.25);
  background: #ffffff;
  color: #087f6f;
}

.exam-retry-alert-actions .retry-exam-btn {
  border: none;
  background: #071126;
  color: #ffffff;
}

.course-retake-alert {
  margin: 16px 30px 0;
  border: 1px solid rgba(247, 184, 75, 0.35);
  border-radius: 18px;
  background: #fff9ed;
  color: #7c5208;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.course-retake-alert-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: #f7b84b;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.course-retake-alert-content {
  flex: 1;
  min-width: 0;
}

.course-retake-alert h3 {
  color: #6b4708;
  font-size: 15px;
  font-weight: 900;
  margin: 0 0 4px;
}

.course-retake-alert p {
  color: #8a641e;
  font-size: 12px;
  line-height: 1.5;
  margin: 0;
}

.course-retake-alert button {
  height: 39px;
  padding-inline: 13px;
  border: none;
  border-radius: 11px;
  background: #071126;
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.result-retake-note {
  display: block;
  margin-top: 10px;
  color: #f7b84b;
  font-size: 11px;
  font-weight: 800;
  line-height: 1.45;
}

.exam-body {
  padding: 24px 30px 16px;
  overflow: visible;
  flex: 0 0 auto;
}

.question-card {
  width: 100%;
  height: auto;
  min-height: auto;
  overflow: visible;
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 28px;
  padding: 26px;
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
}

.question-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
}

.question-step {
  display: block;
  color: #0d8fe9;
  font-size: 13px;
  font-weight: 900;
  margin-bottom: 5px;
}

.question-card-header h2 {
  color: #64748b;
  font-size: 14px;
  font-weight: 800;
  margin: 0;
}

.question-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.answered-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0ab39c;
  background: #e8f7f4;
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 900;
}

.clear-answer-btn {
  height: 34px;
  border: none;
  border-radius: 999px;
  padding: 0 12px;
  background: #f1f5f9;
  color: #071126;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.question-title-panel {
  display: flex;
  gap: 18px;
  align-items: flex-start;
  padding: 18px;
  background:
    radial-gradient(circle at top left, rgba(13, 143, 233, 0.12), transparent 35%),
    #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 24px;
  margin-bottom: 20px;
}

.question-index-big {
  width: 54px;
  height: 54px;
  min-width: 54px;
  border-radius: 18px;
  background: linear-gradient(135deg, #0d8fe9 0%, #071126 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 23px;
  font-weight: 900;
  box-shadow: 0 14px 26px rgba(13, 143, 233, 0.22);
  flex-shrink: 0;
}

.question-title-panel h3 {
  color: #071126;
  font-size: 24px;
  line-height: 1.5;
  font-weight: 900;
  margin-bottom: 0;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  word-break: break-word;
}

.answers-list {
  display: grid;
  gap: 12px;
}

.answer-option {
  width: 100%;
  min-height: auto;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  border-radius: 18px;
  padding: 14px 16px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  text-align: left;
  transition: all 0.2s ease;
}

.answer-option:hover:not(:disabled) {
  border-color: #0d8fe9;
  background: #eef8ff;
  transform: translateY(-1px);
}

.answer-option.selected {
  border-color: #0d8fe9;
  background: #eef8ff;
  box-shadow: 0 12px 24px rgba(13, 143, 233, 0.12);
}

.answer-option.correct {
  border-color: rgba(10, 179, 156, 0.45);
  background: rgba(10, 179, 156, 0.08);
}

.answer-option.wrong {
  border-color: rgba(240, 101, 72, 0.45);
  background: rgba(240, 101, 72, 0.08);
}

.answer-letter {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 13px;
  background: #edf2f7;
  color: #071126;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  flex-shrink: 0;
}

.answer-option.selected .answer-letter {
  background: #0d8fe9;
  color: #ffffff;
}

.answer-option.correct .answer-letter {
  background: #0ab39c;
  color: #ffffff;
}

.answer-option.wrong .answer-letter {
  background: #f06548;
  color: #ffffff;
}

.answer-text {
  flex: 1;
  color: #334155;
  font-size: 15px;
  font-weight: 800;
  line-height: 1.55;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  word-break: break-word;
}

.selected-icon {
  color: #0d8fe9;
  font-size: 22px;
  flex-shrink: 0;
  margin-top: 8px;
}

.answer-option:disabled {
  cursor: not-allowed;
}

.answer-result {
  margin-top: 20px;
  border-radius: 20px;
  padding: 16px;
  display: flex;
  gap: 12px;
  align-items: center;
}

.answer-result.correct {
  background: rgba(10, 179, 156, 0.1);
  color: #087f6f;
}

.answer-result.wrong {
  background: rgba(240, 101, 72, 0.1);
  color: #b42318;
}

.answer-result-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 15px;
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 25px;
}

.answer-result strong {
  display: block;
  font-weight: 900;
  margin-bottom: 4px;
}

.answer-result p {
  margin-bottom: 0;
}

.empty-state {
  min-height: 360px;
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 28px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
}

.empty-icon {
  width: 76px;
  height: 76px;
  border-radius: 24px;
  background: rgba(13, 143, 233, 0.1);
  color: #0d8fe9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
}

.empty-state h5 {
  color: #071126;
  font-weight: 900;
  margin-bottom: 0;
}

.empty-state p {
  margin-bottom: 0;
}

.exam-footer {
  margin: 0 30px 24px;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  display: grid;
  grid-template-columns: minmax(150px, 1fr) auto minmax(320px, 1fr);
  align-items: center;
  gap: 14px;
  position: static;
  z-index: 5;
  flex-shrink: 0;
}

.footer-left {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.footer-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.footer-btn {
  height: 48px;
  padding-inline: 20px;
  border: none;
  border-radius: 15px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.footer-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.footer-btn.secondary {
  background: #f1f5f9;
  color: #071126;
}

.footer-btn.outline {
  background: #ffffff;
  color: #071126;
  border: 1px solid #e2e8f0;
}

.footer-btn.primary {
  background: #071126;
  color: #ffffff;
}

.footer-btn.success {
  background: linear-gradient(135deg, #0ab39c 0%, #087f6f 100%);
  color: #ffffff;
}

.footer-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.footer-info {
  flex: 1;
  text-align: center;
  color: #64748b;
  font-size: 13px;
  font-weight: 900;
}

.min-width-0 {
  min-width: 0;
}


/* =========================================================
   EXAM TIMER AND CONTENT PROTECTION
   ========================================================= */

.exam-page,
.exam-page * {
  -webkit-user-select: none;
  user-select: none;
}

.exam-page img {
  -webkit-user-drag: none;
  user-drag: none;
}

.sidebar-timer {
  min-height: 72px;
  margin: 0 18px 18px;
  padding: 12px 14px;
  border: 1px solid rgba(155, 220, 235, 0.24);
  border-radius: 18px;
  background: rgba(13, 143, 233, 0.13);
  display: flex;
  align-items: center;
  gap: 11px;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease;
}

.sidebar-timer.warning {
  border-color: rgba(247, 184, 75, 0.34);
  background: rgba(247, 184, 75, 0.14);
}

.sidebar-timer.danger {
  border-color: rgba(240, 101, 72, 0.45);
  background: rgba(240, 101, 72, 0.17);
  animation: timerPulse 1s ease-in-out infinite;
}

.sidebar-timer-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 13px;
  color: #ffffff;
  background: #0d8fe9;
  font-size: 21px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.sidebar-timer.warning .sidebar-timer-icon {
  background: #f7b84b;
}

.sidebar-timer.danger .sidebar-timer-icon {
  background: #f06548;
}

.sidebar-timer-content {
  flex: 1;
  min-width: 0;
}

.sidebar-timer-content span {
  display: block;
  margin-bottom: 3px;
  color: rgba(255, 255, 255, 0.62);
  font-size: 10px;
  font-weight: 800;
}

.sidebar-timer-content strong {
  color: #ffffff;
  font-size: 20px;
  line-height: 1;
  font-weight: 900;
  font-variant-numeric: tabular-nums;
}

.sidebar-timer small {
  color: rgba(255, 255, 255, 0.58);
  font-size: 10px;
  font-weight: 800;
}

.exam-header-side {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
}

.header-timer {
  min-height: 42px;
  padding: 6px 13px;
  border: 1px solid rgba(13, 143, 233, 0.18);
  border-radius: 13px;
  color: #0d8fe9;
  background: #eef8ff;
  display: inline-flex;
  align-items: center;
  gap: 9px;
}

.header-timer.warning {
  border-color: rgba(247, 184, 75, 0.28);
  color: #b97805;
  background: #fff8e8;
}

.header-timer.danger {
  border-color: rgba(240, 101, 72, 0.3);
  color: #d94b31;
  background: #fff0ed;
  animation: timerPulse 1s ease-in-out infinite;
}

.header-timer > i {
  font-size: 20px;
}

.header-timer span {
  display: block;
  margin-bottom: 1px;
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
}

.header-timer strong {
  display: block;
  color: inherit;
  font-size: 14px;
  line-height: 1;
  font-weight: 900;
  font-variant-numeric: tabular-nums;
}

.answer-save-warning {
  margin-bottom: 13px;
  padding: 10px 12px;
  border: 1px solid rgba(247, 184, 75, 0.28);
  border-radius: 12px;
  color: #8a641e;
  background: #fff9ed;
  font-size: 11px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 7px;
}

.exam-timeout-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  padding: 20px;
  background: rgba(7, 17, 38, 0.74);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.exam-timeout-card {
  width: min(390px, 100%);
  padding: 32px;
  border-radius: 26px;
  background: #ffffff;
  box-shadow: 0 30px 80px rgba(7, 17, 38, 0.3);
  text-align: center;
}

.exam-timeout-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  border-radius: 22px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    #f7b84b 0%,
    #f06548 100%
  );
  font-size: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.exam-timeout-card h3 {
  margin-bottom: 8px;
  color: #071126;
  font-size: 23px;
  font-weight: 900;
}

.exam-timeout-card p {
  margin-bottom: 18px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.55;
}

.exam-timeout-card .spinner-border {
  color: #0d8fe9;
}

@keyframes timerPulse {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.015);
  }
}

@media (max-width: 1199.98px) {
  .exam-page {
    padding: 16px 18px calc(var(--app-footer-height) + 18px);
  }

  .exam-shell {
    grid-template-columns: 310px minmax(0, 1fr);
  }

  .question-map-grid {
    grid-template-columns: repeat(5, 1fr);
  }

  .exam-header h1 {
    font-size: 26px;
  }

  .question-title-panel h3 {
    font-size: 22px;
  }

  .exam-summary-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .exam-shell {
    grid-template-columns: 1fr;
  }

  .exam-main {
    align-self: stretch;
  }

  .exam-footer {
    grid-template-columns: minmax(130px, 1fr) auto minmax(280px, 1fr);
    margin-inline: 18px;
  }

  .exam-sidebar {
    min-height: auto;
    border-radius: 30px 30px 0 0;
  }

  .exam-main {
    border-radius: 0 0 30px 30px;
  }

  .exam-header {
    padding: 22px;
  }

  .exam-summary-strip {
    padding: 14px 22px;
  }

  .exam-body {
    padding: 18px;
    overflow: visible;
  }

  .question-card {
    padding: 22px;
  }
}

@media (max-width: 575.98px) {
  .exam-header-side {
    width: 100%;
    justify-content: space-between;
  }

  .header-timer,
  .exam-status {
    flex: 1;
    justify-content: center;
  }

  .sidebar-timer {
    margin-inline: 14px;
  }

  .exam-page {
    padding: 8px 8px calc(var(--app-footer-height) + 14px);
  }

  .exam-shell {
    border-radius: 20px;
  }

  .exam-sidebar {
    border-radius: 20px 20px 0 0;
  }

  .exam-main {
    border-radius: 0 0 20px 20px;
  }

  .sidebar-top {
    padding: 14px;
  }

  .question-map-grid {
    grid-template-columns: repeat(4, 1fr);
  }

  .exam-header {
    flex-direction: column;
    padding: 18px;
  }

  .exam-header h1 {
    font-size: 22px;
  }

  .exam-summary-strip {
    grid-template-columns: 1fr 1fr;
    padding: 12px;
  }

  .exam-result-overview {
    margin: 12px;
    padding: 18px;
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .exam-result-overview-copy strong {
    font-size: 36px;
  }

  .exam-result-overview-status {
    width: 100%;
    justify-content: center;
  }

  .exam-passed-final-alert {
    margin: 12px;
    align-items: flex-start;
    flex-direction: column;
  }

  .exam-passed-final-alert button {
    width: 100%;
    justify-content: center;
  }

  .exam-retry-alert {
    margin: 12px;
    align-items: flex-start;
    flex-direction: column;
  }

  .exam-retry-alert-actions {
    width: 100%;
    flex-direction: column;
  }

  .exam-retry-alert-actions button {
    width: 100%;
    justify-content: center;
  }

  .course-retake-alert {
    margin: 12px;
    align-items: flex-start;
    flex-direction: column;
  }

  .course-retake-alert button {
    width: 100%;
    justify-content: center;
  }

  .exam-body {
    padding: 12px;
  }

  .question-card {
    padding: 16px;
    border-radius: 20px;
  }

  .question-card-header,
  .question-actions {
    flex-direction: column;
    align-items: flex-start;
  }

  .question-title-panel {
    flex-direction: column;
    padding: 16px;
  }

  .question-title-panel h3 {
    font-size: 18px;
  }

  .answer-option {
    padding: 12px;
  }

  .exam-footer {
    margin: 0 12px 16px;
    padding: 12px;
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .footer-info {
    grid-row: 1;
    width: 100%;
    text-align: center;
  }

  .footer-left {
    grid-row: 2;
    width: 100%;
  }

  .footer-right {
    grid-row: 3;
    width: 100%;
    display: grid;
    grid-template-columns: repeat(
      auto-fit,
      minmax(135px, 1fr)
    );
    gap: 8px;
  }

  .footer-btn {
    width: 100%;
    min-width: 0;
    justify-content: center;
    padding-inline: 12px;
  }
}
</style>
