<script setup>

import { Head, router } from '@inertiajs/vue3'

import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref } from 'vue'

import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { useScormApi } from '@/composables/useScormApi'

import Swal from 'sweetalert2'

defineOptions({

  layout: LayoutAnonyme,

})

const props = defineProps({

  enrollment: {

    type: Object,

    required: true,

  },

})

const viewerShell = ref(null)

const isFullscreen = ref(false)

const iframeLoaded = ref(false)

const course = computed(() => props.enrollment.course || {})

const enrollmentHashid = computed(() => props.enrollment.hashid || null)

const launchUrl = computed(() => course.value.scorm_launch_url || '')

const packageAvailable = computed(() => Boolean(launchUrl.value))

const attemptsRemaining = computed(() => Number(props.enrollment.attempts_rest || 0))

const trainingRequest = computed(() => props.enrollment.training_request || null)

const expiration = computed(() => props.enrollment.expiration || {})

const isExpired = computed(() => Boolean(

  props.enrollment.is_expired ||

  expiration.value?.is_expired

))

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

const scormCompleted = ref(Boolean(props.enrollment.course_completed))

/*
|--------------------------------------------------------------------------
| Real-time completion synchronization
|--------------------------------------------------------------------------
|
| A local 100% must be committed to Laravel before the learner navigates to
| courseFinish. This prevents the old situation where leaving and reopening
| the course was required before the final exam became available.
|
*/
const isLaravelExamReady = enrollment => {
  if (!enrollment) return false

  return Boolean(
    enrollment.course_completed === true &&
    enrollment.can_start_exam === true &&
    Number(enrollment.attempts_rest || 0) > 0 &&
    enrollment.is_expired !== true &&
    enrollment.training_request?.can_access_course !== false &&
    enrollment.training_request?.can_start_exam !== false
  )
}

/*
|--------------------------------------------------------------------------
| Laravel is the only authority that can unlock the exam
|--------------------------------------------------------------------------
|
| 100% in the browser is only a local visual state. The completion popup and
| the Final exam button stay locked until a fresh response from Laravel says
| BOTH course_completed=true and can_start_exam=true.
|
*/
const serverCompletionConfirmed = ref(
  isLaravelExamReady(props.enrollment)
)
const completionReachedThisSession = ref(false)
const completionSyncInProgress = ref(false)
const completionVerificationInProgress = ref(false)
const completionPopupOpened = ref(false)

let completionSyncRetryTimer = null
let completionSyncAttempts = 0


const courseFormatLabel = computed(() => {

  return course.value.scorm_version || 'SCORM'

})

const persistedCmiValues = {

  ...(props.enrollment.scorm_tracking?.cmi_data || {}),

}

const clampPercent = value => {

  const percent = Number(value)

  if (!Number.isFinite(percent)) {

    return 0

  }

  return Math.min(100, Math.max(0, percent))

}

const progressMeasureToPercent = value => {

  const measure = Number(value)

  if (!Number.isFinite(measure)) {

    return 0

  }

  return clampPercent(measure <= 1 ? measure * 100 : measure)

}

const resolveInitialCourseProgress = () => {

  if (scormCompleted.value) {

    return 100

  }

  const trackingMeasure = props.enrollment.scorm_tracking?.progress_measure

  if (

    trackingMeasure !== null &&

    trackingMeasure !== undefined &&

    trackingMeasure !== ''

  ) {

    return progressMeasureToPercent(trackingMeasure)

  }

  const enrollmentProgress = props.enrollment.progress_percent

  if (

    enrollmentProgress !== null &&

    enrollmentProgress !== undefined &&

    enrollmentProgress !== ''

  ) {

    return clampPercent(enrollmentProgress)

  }

  const savedMeasure = persistedCmiValues['cmi.progress_measure']

  if (

    savedMeasure !== null &&

    savedMeasure !== undefined &&

    savedMeasure !== ''

  ) {

    return progressMeasureToPercent(savedMeasure)

  }

  return 0

}

const courseProgress = ref(resolveInitialCourseProgress())

const resolveServerSyncedProgress = enrollment => {
  if (!enrollment) return 0

  const directProgress = Number(
    enrollment.scorm_progress_percent ??
    enrollment.progress_percent
  )

  if (Number.isFinite(directProgress)) {
    return clampPercent(directProgress)
  }

  const measure = enrollment.scorm_tracking?.progress_measure

  if (measure !== null && measure !== undefined && measure !== '') {
    return progressMeasureToPercent(measure)
  }

  return 0
}

/*
|--------------------------------------------------------------------------
| Last progress explicitly confirmed by Laravel
|--------------------------------------------------------------------------
|
| courseProgress is the live value shown in the viewer. This second value is
| intentionally server-only. Navigation is delayed when the browser is ahead
| of Laravel, so My Courses never opens with an older percentage.
|
*/
const lastServerSyncedProgress = ref(
  resolveServerSyncedProgress(props.enrollment)
)

/**

*|--------------------------------------------------------------------------

*| Monotonic progress during the current SCORM session

*|--------------------------------------------------------------------------

*|

*| Rapid navigation can generate several asynchronous Commit responses.

*| An older response may arrive after a newer real-time SetValue update.

*| Keep the highest value reached during this page session so the progress

*| bar never visually moves backwards from 60% to 51%, 52%, etc.

*|

**/

const highestSessionProgress = ref(

  clampPercent(courseProgress.value)

)

const applyCourseProgress = value => {

  const nextProgress = clampPercent(value)

  highestSessionProgress.value = Math.max(

    highestSessionProgress.value,

    nextProgress

  )

  courseProgress.value = highestSessionProgress.value

}

const formattedCourseProgress = computed(() => {

  return Math.round(clampPercent(courseProgress.value))

})

const hasUnsyncedProgress = computed(() => {
  return formattedCourseProgress.value >
    Math.round(clampPercent(lastServerSyncedProgress.value))
})

const isCourseProgressCompleted = computed(() => {

  return scormCompleted.value || formattedCourseProgress.value >= 100

})

/**

*|--------------------------------------------------------------------------

*| Final exam availability

*|--------------------------------------------------------------------------

*|

*| Do not depend on the initial training_request.can_start_exam prop here.

*| That value is produced before the SCORM package reaches 100% and therefore

*| stays stale until Laravel receives a fresh SCORM commit. The viewer may show

*| live progress immediately, but exam access is never unlocked locally. Laravel

*| must explicitly confirm course_completed and can_start_exam.

*|

**/

const canGoToExam = computed(() => {
  return !isExpired.value &&
    isCourseProgressCompleted.value &&
    serverCompletionConfirmed.value &&
    attemptsRemaining.value > 0 &&
    trainingRequest.value?.can_access_course !== false
})

const finalExamActionLabel = computed(() => {
  if (
    isCourseProgressCompleted.value &&
    !serverCompletionConfirmed.value &&
    completionSyncInProgress.value
  ) {
    return t('ui-synchronizing')
  }

  return t('ui-final-exam')
})

const finalExamHelperText = computed(() => {
  if (
    isCourseProgressCompleted.value &&
    !serverCompletionConfirmed.value
  ) {
    return t('ui-saving-your-final-course-progress-before-the-exam-is-u')
  }

  if (serverCompletionConfirmed.value) {
    return t('ui-your-course-completion-is-synchronized-you-can-now-acc')
  }

  return t('ui-complete-the-entire-scorm-course-to-unlock-the-final-e')
})

const progressStatusLabel = computed(() => {

  if (isCourseProgressCompleted.value) {

    return t('ui-completed')

  }

  if (formattedCourseProgress.value > 0) {

    return t('ui-in-progress')

  }

  return t('ui-not-started')

})

const progressStatusIcon = computed(() => {

  if (isCourseProgressCompleted.value) {

    return 'ri-check-line'

  }

  if (formattedCourseProgress.value > 0) {

    return 'ri-bar-chart-line'

  }

  return 'ri-play-line'

})

/**

*|--------------------------------------------------------------------------

*| Real-time SCORM progress

*|--------------------------------------------------------------------------

*|

*| The progress bar must not wait for the SCORM package to call Commit().

*| We listen directly to LMSSetValue / SetValue, update the interface

*| immediately, then trigger a debounced commit so Laravel is synchronized.

*|

**/

const runtimeCmiValues = ref({

  ...persistedCmiValues,

})

const runtimeCompletionReported = ref(false)

let realtimeHookRetryInterval = null

let realtimeCommitTimer = null
let realtimeServerSyncInterval = null
let progressNavigationUnsubscribe = null
let navigationSyncRetryTimer = null
let pendingNavigationUrl = null
let bypassNextNavigationGuard = false
let progressCommitInFlight = false
let progressCommitWatchdogTimer = null
let progressCommitQueuedWhileInFlight = false
let navigationSyncAttempts = 0

let completionCommitQueued = false

const completedScormStatuses = new Set([

  'completed',

  'passed',

])

const normalizeScormStatus = value => {

  return String(value || '').trim().toLowerCase()

}

const parseProgressCandidate = value => {

  if (value === null || value === undefined || value === '') {

    return null

  }

  if (typeof value === 'number') {

    return Number.isFinite(value)

      ? progressMeasureToPercent(value)

      : null

  }

  const normalized = String(value).trim()

  if (!normalized) return null

  const percentMatch = normalized.match(/^(-?\d+(?:\.\d+)?)\s*%$/)

  if (percentMatch) {

    return clampPercent(percentMatch[1])

  }

  const numericValue = Number(normalized)

  if (Number.isFinite(numericValue)) {

    return progressMeasureToPercent(numericValue)

  }

  return null

}

const findProgressInsideObject = (value, depth = 0) => {

  if (!value || typeof value !== 'object' || depth > 4) {

    return null

  }

  for (const [key, candidate] of Object.entries(value)) {

    if (/(progress|percent|percentage|completion)/i.test(key)) {

      const parsed = parseProgressCandidate(candidate)

      if (parsed !== null) {

        return parsed

      }

    }

  }

  for (const candidate of Object.values(value)) {

    const nested = findProgressInsideObject(candidate, depth + 1)

    if (nested !== null) {

      return nested

    }

  }

  return null

}

const progressFromSuspendData = value => {

  if (!value || typeof value !== 'string') {

    return null

  }

  try {

    return findProgressInsideObject(JSON.parse(value))

  } catch (error) {

    const match = value.match(

      /(?:progress|percent|percentage|completion)[^0-9]{0,12}(\d+(?:\.\d+)?)\s*%?/i

    )

    return match

      ? clampPercent(match[1])

      : null

  }

}

const progressFromLocation = value => {

  if (!value) return null

  const location = String(value)

  const fraction = location.match(

    /(?:^|\D)(\d+)\s*(?:\/|of)\s*(\d+)(?:\D|$)/i

  )

  if (!fraction) return null

  const current = Number(fraction[1])

  const total = Number(fraction[2])

  if (!Number.isFinite(current) || !Number.isFinite(total) || total <= 0) {

    return null

  }

  return clampPercent((current / total) * 100)

}

const resolveRealtimeProgress = values => {

  const progressMeasure =

    values['cmi.progress_measure'] ??

    values['cmi.core.progress_measure']

  const directProgress = parseProgressCandidate(progressMeasure)

  if (directProgress !== null) {

    return directProgress

  }

  const statuses = [

    values['cmi.core.lesson_status'],

    values['cmi.completion_status'],

    values['cmi.success_status'],

  ].map(normalizeScormStatus)

  if (statuses.some(status => completedScormStatuses.has(status))) {

    runtimeCompletionReported.value = true

    return 100

  }

  const suspendProgress = progressFromSuspendData(

    values['cmi.suspend_data']

  )

  if (suspendProgress !== null) {

    return suspendProgress

  }

  const locationProgress = progressFromLocation(

    values['cmi.location'] ??

    values['cmi.core.lesson_location']

  )

  if (locationProgress !== null) {

    return locationProgress

  }

  return null

}

const applyRuntimeCmiValue = (

  element,

  value,

  { requestCommit = false } = {}

) => {

  if (!element) return

  runtimeCmiValues.value = {

    ...runtimeCmiValues.value,

    [element]: value,

  }

  const progress = resolveRealtimeProgress(

    runtimeCmiValues.value

  )

  if (progress !== null) {

    /**

*     * Do not visually move backwards during the same SCORM session.

*     * A real course restart already clears the browser storage before launch.

*     */

    applyCourseProgress(progress)

    /**

*     * A SCORM package can report 100% before the asynchronous commit response

*     * updates the Inertia props. Unlock the exam immediately in the current

*     * page and force one final commit so Laravel receives the completed state.

*     */

    if (progress >= 100 || runtimeCompletionReported.value) {
      runtimeCompletionReported.value = true
      scormCompleted.value = true
      applyCourseProgress(100)

      if (!serverCompletionConfirmed.value) {
        completionReachedThisSession.value = true
        completionSyncInProgress.value = true
        queueCompletionCommit()
      }
    }

  }

  if (requestCommit && !runtimeCompletionReported.value) {

    scheduleRealtimeCommit()

  }

}

const getScormRuntimeApi = () => {

  if (typeof window === 'undefined') {

    return null

  }

  if (window.API_1484_11) {

    return {

      object: window.API_1484_11,

      setMethod: 'SetValue',

      getMethod: 'GetValue',

      commitMethod: 'Commit',

    }

  }

  if (window.API) {

    return {

      object: window.API,

      setMethod: 'LMSSetValue',

      getMethod: 'LMSGetValue',

      commitMethod: 'LMSCommit',

    }

  }

  return null

}

const commitRuntimeValues = () => {
  const runtimeApi = getScormRuntimeApi()

  if (!runtimeApi) return false

  const commit = runtimeApi.object?.[runtimeApi.commitMethod]

  if (typeof commit !== 'function') return false

  try {
    commit.call(runtimeApi.object, '')
    return true
  } catch (error) {
    console.warn('Unable to commit real-time SCORM progress:', error)
    return false
  }
}

const clearProgressCommitWatchdog = () => {
  if (!progressCommitWatchdogTimer) return

  window.clearTimeout(progressCommitWatchdogTimer)
  progressCommitWatchdogTimer = null
}

const startTrackedProgressCommit = () => {
  if (progressCommitInFlight) {
    progressCommitQueuedWhileInFlight = true
    return false
  }

  const started = commitRuntimeValues()

  if (!started) {
    return false
  }

  progressCommitInFlight = true
  clearProgressCommitWatchdog()

  /*
   * LMSCommit/Commit returns before the HTTP request inside useScormApi has
   * necessarily finished. If the request fails silently, do not leave the
   * viewer permanently stuck in an "in flight" state.
   */
  progressCommitWatchdogTimer = window.setTimeout(() => {
    progressCommitWatchdogTimer = null
    progressCommitInFlight = false

    if (hasUnsyncedProgress.value) {
      scheduleRealtimeCommit()
    }
  }, 4000)

  return true
}

const clearNavigationSyncRetry = () => {
  if (!navigationSyncRetryTimer) return

  window.clearTimeout(navigationSyncRetryTimer)
  navigationSyncRetryTimer = null
}

const clearCompletionSyncRetry = () => {
  if (!completionSyncRetryTimer) return

  window.clearTimeout(completionSyncRetryTimer)
  completionSyncRetryTimer = null
}

const scheduleCompletionSyncRetry = () => {
  clearCompletionSyncRetry()

  if (serverCompletionConfirmed.value) return
  if (!isCourseProgressCompleted.value) return
  if (completionSyncAttempts >= 10) {
    completionSyncInProgress.value = false
    return
  }

  completionSyncRetryTimer = window.setTimeout(() => {
    completionSyncRetryTimer = null
    requestCompletionSync()
  }, 1000)
}

const applyLaravelEnrollmentState = freshEnrollment => {
  if (!freshEnrollment) return false

  const freshProgress = Number(
    freshEnrollment.scorm_progress_percent ??
    freshEnrollment.progress_percent
  )

  if (Number.isFinite(freshProgress)) {
    const normalizedFreshProgress = clampPercent(freshProgress)
    lastServerSyncedProgress.value = Math.max(
      lastServerSyncedProgress.value,
      normalizedFreshProgress
    )
    applyCourseProgress(normalizedFreshProgress)
  }

  if (freshEnrollment.course_completed === true) {
    scormCompleted.value = true
    runtimeCompletionReported.value = true
    applyCourseProgress(100)
  }

  const ready = isLaravelExamReady(freshEnrollment)
  serverCompletionConfirmed.value = ready

  if (ready) {
    completionSyncInProgress.value = false
    completionSyncAttempts = 0
    clearCompletionSyncRetry()

    window.setTimeout(() => {
      showCourseCompletedPopup()
    }, 120)
  }

  return ready
}

const verifyCompletionWithLaravel = () => {
  if (serverCompletionConfirmed.value) return
  if (!isCourseProgressCompleted.value) return
  if (completionVerificationInProgress.value) return

  completionVerificationInProgress.value = true
  completionSyncInProgress.value = true
  armNavigationGuardBypass()

  router.reload({
    only: ['enrollment'],
    preserveScroll: true,
    preserveState: true,
    onSuccess: page => {
      const freshEnrollment = page?.props?.enrollment || null
      const ready = applyLaravelEnrollmentState(freshEnrollment)

      if (!ready) {
        scheduleCompletionSyncRetry()
      }
    },
    onError: () => {
      scheduleCompletionSyncRetry()
    },
    onFinish: () => {
      completionVerificationInProgress.value = false
    },
  })
}

function requestCompletionSync() {
  if (serverCompletionConfirmed.value) {
    completionSyncInProgress.value = false
    clearCompletionSyncRetry()
    return
  }

  if (!isCourseProgressCompleted.value) return

  completionSyncInProgress.value = true
  completionSyncAttempts += 1

  const commitStarted = startTrackedProgressCommit()

  if (!commitStarted && !progressCommitInFlight) {
    verifyCompletionWithLaravel()
    scheduleCompletionSyncRetry()
    return
  }

  /*
   * The SCORM Commit starts an asynchronous HTTP request. A successful Commit
   * alone is NOT enough to unlock the learner. We always ask Laravel again for
   * the fresh enrollment state before showing the completion popup.
   */
  window.setTimeout(() => {
    verifyCompletionWithLaravel()
  }, 350)

  scheduleCompletionSyncRetry()
}

function queueCompletionCommit() {
  if (completionCommitQueued || serverCompletionConfirmed.value) return

  completionCommitQueued = true

  if (realtimeCommitTimer) {
    window.clearTimeout(realtimeCommitTimer)
  }

  realtimeCommitTimer = window.setTimeout(() => {
    completionCommitQueued = false
    realtimeCommitTimer = null
    requestCompletionSync()
  }, 0)
}

function scheduleRealtimeCommit() {
  /*
   * Do not restart the timer on every SetValue. Some SCORM packages emit CMI
   * values continuously; the old debounce could therefore postpone Commit
   * forever. The first change now guarantees a server commit within 300 ms.
   */
  if (progressCommitInFlight) {
    progressCommitQueuedWhileInFlight = true
    return
  }

  if (realtimeCommitTimer) return

  realtimeCommitTimer = window.setTimeout(() => {
    realtimeCommitTimer = null

    const started = startTrackedProgressCommit()

    if (!started && !progressCommitInFlight) {
      window.setTimeout(() => {
        scheduleRealtimeCommit()
      }, 500)
    }
  }, 300)
}

const refreshServerProgress = () => {
  if (!hasUnsyncedProgress.value) return
  if (completionVerificationInProgress.value) return
  if (pendingNavigationUrl) return

  completionVerificationInProgress.value = true
  armNavigationGuardBypass()

  router.reload({
    only: ['enrollment'],
    preserveScroll: true,
    preserveState: true,
    onSuccess: page => {
      const freshEnrollment = page?.props?.enrollment || null

      if (freshEnrollment) {
        const freshProgress = resolveServerSyncedProgress(freshEnrollment)

        lastServerSyncedProgress.value = Math.max(
          lastServerSyncedProgress.value,
          freshProgress
        )

        applyLaravelEnrollmentState(freshEnrollment)
      }
    },
    onFinish: () => {
      completionVerificationInProgress.value = false
    },
  })
}

const startRealtimeServerSync = () => {
  if (realtimeServerSyncInterval) return

  realtimeServerSyncInterval = window.setInterval(() => {
    if (!hasUnsyncedProgress.value) return

    scheduleRealtimeCommit()

    /*
     * Do a lightweight partial Inertia reload as a second confirmation path.
     * This covers commit endpoints that return only { success: true } without
     * returning the synchronized percentage in their JSON response.
     */
    window.setTimeout(() => {
      refreshServerProgress()
    }, 500)
  }, 1500)
}

const stopRealtimeServerSync = () => {
  if (!realtimeServerSyncInterval) return

  window.clearInterval(realtimeServerSyncInterval)
  realtimeServerSyncInterval = null
}

const wrapScormSetValue = runtimeApi => {

  const apiObject = runtimeApi?.object

  const methodName = runtimeApi?.setMethod

  if (!apiObject || !methodName) return false

  if (apiObject.__lmsRealtimeProgressWrapped) return true

  const originalSetValue = apiObject[methodName]

  if (typeof originalSetValue !== 'function') return false

  try {

    apiObject[methodName] = function realtimeSetValue(element, value) {

      const result = originalSetValue.call(

        apiObject,

        element,

        value

      )

      applyRuntimeCmiValue(element, value, {

        requestCommit: true,

      })

      return result

    }

    Object.defineProperty(

      apiObject,

      '__lmsRealtimeProgressWrapped',

      {

        value: true,

        configurable: true,

      }

    )

    return true

  } catch (error) {

    console.warn('Unable to install real-time SCORM listener:', error)

    return false

  }

}

const installRealtimeScormListener = () => {

  return wrapScormSetValue(

    getScormRuntimeApi()

  )

}

const startRealtimeScormTracking = () => {

  if (installRealtimeScormListener()) {

    return

  }

  realtimeHookRetryInterval = window.setInterval(() => {

    if (installRealtimeScormListener()) {

      window.clearInterval(realtimeHookRetryInterval)

      realtimeHookRetryInterval = null

    }

  }, 300)

}

const stopRealtimeScormTracking = () => {

  if (realtimeCommitTimer) {

    window.clearTimeout(realtimeCommitTimer)

    realtimeCommitTimer = null

  }

  clearCompletionSyncRetry()



  if (realtimeHookRetryInterval) {

    window.clearInterval(realtimeHookRetryInterval)

    realtimeHookRetryInterval = null

  }

}

const getCommittedProgressPercent = response => {
  const percentValue =
    response?.progress_percent ??
    response?.enrollment?.progress_percent ??
    response?.scorm_tracking?.progress_percent

  if (percentValue !== null && percentValue !== undefined) {
    return clampPercent(percentValue)
  }

  const measureValue =
    response?.progress_measure ??
    response?.scorm_tracking?.progress_measure ??
    response?.cmi_data?.['cmi.progress_measure'] ??
    response?.scorm_tracking?.cmi_data?.['cmi.progress_measure']

  if (measureValue !== null && measureValue !== undefined) {
    return progressMeasureToPercent(measureValue)
  }

  const committedCmiData = {
    ...(response?.scorm_tracking?.cmi_data || {}),
    ...(response?.cmi_data || {}),
  }

  const committedLocation =
    response?.lesson_location ??
    response?.scorm_tracking?.lesson_location

  const committedSuspendData =
    response?.suspend_data ??
    response?.scorm_tracking?.suspend_data

  if (committedLocation !== null && committedLocation !== undefined) {
    committedCmiData['cmi.location'] = committedLocation
    committedCmiData['cmi.core.lesson_location'] = committedLocation
  }

  if (committedSuspendData !== null && committedSuspendData !== undefined) {
    committedCmiData['cmi.suspend_data'] = committedSuspendData
  }

  return resolveRealtimeProgress(committedCmiData)
}

const responseConfirmsExamReady = response => {
  const completed = (
    response?.course_completed === true ||
    response?.enrollment?.course_completed === true
  )

  const canStartExam = (
    response?.can_start_exam === true ||
    response?.enrollment?.can_start_exam === true
  )

  return completed && canStartExam
}

const showCourseCompletedPopup = async () => {
  if (!completionReachedThisSession.value) return
  if (!serverCompletionConfirmed.value) return
  if (completionPopupOpened.value) return
  if (isExpired.value) return
  if (attemptsRemaining.value <= 0) return
  if (trainingRequest.value?.can_access_course === false) return

  completionPopupOpened.value = true

  const result = await Swal.fire({
    icon: 'success',
    title: t('ui-course-completed'),
    html: `
      <div style="text-align:center;color:#475569;line-height:1.65;">
        <p style="margin:0 0 8px;">
          Congratulations! You have successfully completed
          <strong style="color:#071126;">${course.value.title || 'this course'}</strong>.
        </p>
        <p style="margin:0;">
          Your progress is synchronized at <strong style="color:#087f6f;">100%</strong>.
          You can now continue to the final exam.
        </p>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: t('ui-go-to-final-exam'),
    cancelButtonText: t('ui-stay-on-course'),
    confirmButtonColor: '#071126',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
    focusConfirm: true,
  })

  if (result.isConfirmed) {
    goToFinalExam()
  }
}

const updateCourseProgressFromCommit = response => {
  progressCommitInFlight = false
  clearProgressCommitWatchdog()

  const committedProgress = getCommittedProgressPercent(response)

  if (committedProgress !== null) {
    const normalizedCommittedProgress = clampPercent(committedProgress)

    /*
     * Only values explicitly returned by Laravel may advance the server
     * watermark. Never copy the browser percentage into this value.
     */
    lastServerSyncedProgress.value = Math.max(
      lastServerSyncedProgress.value,
      normalizedCommittedProgress
    )

    applyCourseProgress(normalizedCommittedProgress)
  }

  if (progressCommitQueuedWhileInFlight) {
    progressCommitQueuedWhileInFlight = false
    scheduleRealtimeCommit()
  }

  /*
   * Never unlock from local 100% and never unlock from progress_percent=100
   * alone. The learner is ready only when Laravel explicitly confirms the
   * course completion AND the right to start the exam.
   */
  if (responseConfirmsExamReady(response)) {
    scormCompleted.value = true
    runtimeCompletionReported.value = true
    applyCourseProgress(100)

    serverCompletionConfirmed.value = true
    completionSyncInProgress.value = false
    completionSyncAttempts = 0
    clearCompletionSyncRetry()

    window.setTimeout(() => {
      showCourseCompletedPopup()
    }, 120)

    return
  }

  if (isCourseProgressCompleted.value) {
    completionReachedThisSession.value = true
    completionSyncInProgress.value = true
    verifyCompletionWithLaravel()
  }
}

const hasPreviousScormSession = Boolean(

  props.enrollment.scorm_tracking?.exists ||

  persistedCmiValues['cmi.core.lesson_location'] ||

  persistedCmiValues['cmi.location'] ||

  persistedCmiValues['cmi.suspend_data']

)

const initialCmiValues = {

  // Default statuses are used only when no saved value exists.

  'cmi.core.lesson_status': 'not attempted',

  'cmi.completion_status': 'unknown',

  'cmi.success_status': 'unknown',

  // Restore lesson_location, suspend_data, scores, interactions and statuses.

  ...persistedCmiValues,

  // These values belong to the LMS environment and must always override any

  // old value saved in cmi_data.

  'cmi.core.student_id': String(

    props.enrollment.learner?.id || props.enrollment.user_id || ''

  ),

  'cmi.core.student_name':

    props.enrollment.learner?.name || 'Learner',

  'cmi.core.entry': hasPreviousScormSession

    ? 'resume'

    : 'ab-initio',

  'cmi.core.lesson_mode': 'normal',

  'cmi.core.credit': 'credit',

  'cmi.learner_id': String(

    props.enrollment.learner?.id || props.enrollment.user_id || ''

  ),

  'cmi.learner_name':

    props.enrollment.learner?.name || 'Learner',

  'cmi.entry': hasPreviousScormSession

    ? 'resume'

    : 'ab-initio',

  'cmi.mode': 'normal',

  'cmi.credit': 'credit',

}

const scormStorageKey = computed(() => {

  return [

    'lms',

    'scorm',

    String(props.enrollment.user_id || 'user'),

    String(enrollmentHashid.value || 'enrollment'),

    String(course.value.id || 'course'),

  ].join(':')

})

/**

*|--------------------------------------------------------------------------

*| Remove the old browser progress before the learner retakes the course

*|--------------------------------------------------------------------------

*|

*| Laravel deletes scorm_trackings when only the final exam attempt remains.

*| The composable also keeps a synchronous browser copy so page refreshes can

*| resume safely. That local copy must be deleted before useScormApi reads it.

*|

**/

const clearScormRestartStorage = () => {

  if (!Boolean(props.enrollment.force_scorm_restart)) {

    return

  }

  const exactKey = scormStorageKey.value

  try {

    window.localStorage.removeItem(exactKey)

  } catch (error) {

    console.warn('Unable to clear local SCORM progress:', error)

  }

  try {

    window.sessionStorage.removeItem(exactKey)

  } catch (error) {

    console.warn('Unable to clear session SCORM progress:', error)

  }

}

clearScormRestartStorage()

const {

  apiReady,

  install: installScormApi,

  uninstall: uninstallScormApi,

} = useScormApi({

  version: course.value.scorm_version || '1.2',

  initialValues: initialCmiValues,

  commitUrl: route(

    'learner-space.scorm.commit',

    enrollmentHashid.value

  ),

  storageKey: scormStorageKey.value,

  onCommitSuccess: response => {

    updateCourseProgressFromCommit(response)

  },

})

const readFreshLaravelProgress = freshEnrollment => {
  return resolveServerSyncedProgress(freshEnrollment)
}

const armNavigationGuardBypass = () => {
  bypassNextNavigationGuard = true

  /*
   * Inertia dispatches the before event synchronously. The zero-delay fallback
   * prevents a stale bypass flag from allowing a later user navigation.
   */
  window.setTimeout(() => {
    bypassNextNavigationGuard = false
  }, 0)
}

const releasePendingNavigation = () => {
  if (!pendingNavigationUrl) return

  const destination = pendingNavigationUrl
  pendingNavigationUrl = null
  navigationSyncAttempts = 0
  clearNavigationSyncRetry()

  armNavigationGuardBypass()
  router.visit(destination)
}

const scheduleNavigationSyncRetry = () => {
  clearNavigationSyncRetry()

  if (!pendingNavigationUrl) return

  if (navigationSyncAttempts >= 8) {
    navigationSyncAttempts = 0
  }

  navigationSyncRetryTimer = window.setTimeout(() => {
    navigationSyncRetryTimer = null
    synchronizeBeforeNavigation()
  }, 450)
}

const verifyNavigationProgressWithLaravel = () => {
  if (!pendingNavigationUrl) return
  if (completionVerificationInProgress.value) {
    scheduleNavigationSyncRetry()
    return
  }

  completionVerificationInProgress.value = true
  armNavigationGuardBypass()

  router.reload({
    only: ['enrollment'],
    preserveScroll: true,
    preserveState: true,
    onSuccess: page => {
      const freshEnrollment = page?.props?.enrollment || null
      const freshProgress = readFreshLaravelProgress(freshEnrollment)

      lastServerSyncedProgress.value = Math.max(
        lastServerSyncedProgress.value,
        freshProgress
      )

      applyLaravelEnrollmentState(freshEnrollment)

      const browserPercent = formattedCourseProgress.value
      const laravelPercent = Math.round(freshProgress)

      if (laravelPercent >= browserPercent) {
        window.setTimeout(() => {
          releasePendingNavigation()
        }, 0)
        return
      }

      scheduleNavigationSyncRetry()
    },
    onError: () => {
      scheduleNavigationSyncRetry()
    },
    onFinish: () => {
      completionVerificationInProgress.value = false
    },
  })
}

function synchronizeBeforeNavigation() {
  if (!pendingNavigationUrl) return

  navigationSyncAttempts += 1

  const started = startTrackedProgressCommit()

  if (!started && !progressCommitInFlight) {
    scheduleNavigationSyncRetry()
    return
  }

  window.setTimeout(() => {
    verifyNavigationProgressWithLaravel()
  }, 300)
}

const installProgressNavigationGuard = () => {
  if (progressNavigationUnsubscribe) return

  progressNavigationUnsubscribe = router.on('before', event => {
    if (bypassNextNavigationGuard) {
      bypassNextNavigationGuard = false
      return
    }

    if (!hasUnsyncedProgress.value) return

    const visit = event?.detail?.visit
    const method = String(visit?.method || 'get').toLowerCase()

    if (method !== 'get') return

    const rawUrl = visit?.url
    if (!rawUrl) return

    if (typeof event?.preventDefault === 'function') {
      event.preventDefault()
    }

    pendingNavigationUrl = typeof rawUrl === 'string'
      ? rawUrl
      : rawUrl.toString()

    synchronizeBeforeNavigation()

    return false
  })
}

const uninstallProgressNavigationGuard = () => {
  if (typeof progressNavigationUnsubscribe === 'function') {
    progressNavigationUnsubscribe()
  }

  progressNavigationUnsubscribe = null
}

const handleVisibilityChange = () => {
  if (document.visibilityState !== 'hidden') return
  if (!hasUnsyncedProgress.value) return

  startTrackedProgressCommit()
}

const handlePageHide = () => {
  if (!hasUnsyncedProgress.value) return

  startTrackedProgressCommit()
}

const goBackToCourses = () => {

  router.visit(route('learner-space.my-courses'))

}

const goToFinalExam = () => {
  if (!enrollmentHashid.value) return

  if (!canGoToExam.value) {
    if (
      isCourseProgressCompleted.value &&
      !serverCompletionConfirmed.value
    ) {
      completionReachedThisSession.value = true
      requestCompletionSync()
    }

    return
  }

  router.visit(
    route('learner-space.course-finish', enrollmentHashid.value)
  )
}

const openPackageInNewTab = () => {

  if (!launchUrl.value) return

  window.open(launchUrl.value, '_blank', 'noopener,noreferrer')

}

const toggleFullscreen = async () => {

  if (!viewerShell.value) return

  try {

    if (!document.fullscreenElement) {

      await viewerShell.value.requestFullscreen()

      isFullscreen.value = true

      return

    }

    await document.exitFullscreen()

    isFullscreen.value = false

  } catch (error) {

    isFullscreen.value = false

  }

}

const handleFullscreenChange = () => {

  isFullscreen.value = Boolean(document.fullscreenElement)

}

const handleIframeLoad = () => {

  iframeLoaded.value = true

  installRealtimeScormListener()

}

onBeforeMount(() => {

  installScormApi()

  installRealtimeScormListener()

})

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('pagehide', handlePageHide)

  startRealtimeScormTracking()
  startRealtimeServerSync()
  installProgressNavigationGuard()

  serverCompletionConfirmed.value = isLaravelExamReady(props.enrollment)

  if (serverCompletionConfirmed.value) {
    completionSyncInProgress.value = false
  }
})

onBeforeUnmount(() => {

  /* Persist the latest values before removing the SCORM API. */

  if (hasUnsyncedProgress.value) {
    startTrackedProgressCommit()
  }

  stopRealtimeScormTracking()
  stopRealtimeServerSync()
  clearNavigationSyncRetry()
  clearProgressCommitWatchdog()
  uninstallProgressNavigationGuard()

  uninstallScormApi()

  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('pagehide', handlePageHide)

})

</script>

<template>

  <Head :title="`${course.title || $t('ui-scorm-course')} - ${$t('ui-course-viewer')}`" />

  <section class="scorm-viewer-page">

    <div class="scorm-topbar">

      <div class="topbar-left">

        <button

          type="button"

          class="icon-btn back"

          :title="$t('ui-back-to-my-courses')"

          @click="goBackToCourses"

        >

          <i class="ri-arrow-left-line"></i>

        </button>

        <div class="course-heading">

          <h1>{{ course.title || 'SCORM Course' }}</h1>

        </div>

      </div>

      <div class="topbar-actions">

        <button

          type="button"

          class="icon-btn"

          :title="$t('ui-toggle-fullscreen')"

          :disabled="!packageAvailable"

          @click="toggleFullscreen"

        >

          <i :class="isFullscreen ? 'ri-fullscreen-exit-line' : 'ri-fullscreen-line'"></i>

        </button>

        <button

          type="button"

          class="exam-btn"

          :disabled="!canGoToExam"

          @click="goToFinalExam"

        >

          <i class="ri-file-list-3-line"></i>

          <span>{{ finalExamActionLabel }}</span>

          <small>{{ attemptsRemaining }} {{ $t('ui-attempt-s') }}</small>

        </button>

      </div>

    </div>

    <div class="scorm-body">

      <aside class="course-sidebar">

        <div class="sidebar-cover">

          <img

            v-if="course.image_url"

            :src="course.image_url"

            :alt="course.title || 'Course image'"

          />

          <div v-else class="cover-fallback">

            <i class="ri-file-zip-line"></i>

          </div>

        </div>

        <div class="sidebar-content">

          <div class="category-line">

            <i class="ri-folder-line"></i>

            {{ course.category?.name || 'SCORM course' }}

          </div>

          <h2>{{ course.scorm_title || course.title || '-' }}</h2>

          <p>

            {{ course.description || 'No course description is available.' }}

          </p>

          <div class="meta-list">

            <div class="meta-row">

              <span>

                <i class="ri-global-line"></i>

                {{ $t('ui-language') }}

              </span>

              <strong>{{ course.language || '-' }}</strong>

            </div>

            <div class="meta-row">

              <span>

                <i class="ri-computer-line"></i>

                {{ $t('ui-training-type-label') }}

              </span>

              <strong>{{ course.training_type || '-' }}</strong>

            </div>

            <div class="meta-row">

              <span>

                <i class="ri-bar-chart-line"></i>

                {{ $t('ui-course-type') }}

              </span>

              <strong>{{ course.skill_level || '-' }}</strong>

            </div>

            <div v-if="trainingRequest" class="meta-row">

              <span>

                <i class="ri-calendar-check-line"></i>

                {{ $t('ui-training-period') }}

              </span>

              <strong>{{ trainingPeriodLabel }}</strong>

            </div>

            <div v-if="props.enrollment.expired_at || props.enrollment.duree_validite_formation" class="meta-row">

              <span>

                <i :class="isExpired ? 'ri-calendar-close-line' : 'ri-calendar-check-line'"></i>

                {{ $t('ui-validity') }}

              </span>

              <strong>

                {{ props.enrollment.expired_at

                  ? `${isExpired ? 'Expired' : 'Until'} ${formatTrainingDate(String(props.enrollment.expired_at).slice(0, 10))}`

                  : `${props.enrollment.duree_validite_formation} month(s)` }}

              </strong>

            </div>

          </div>

          <div

            v-if="trainingRequest?.accreditations?.length"

            class="viewer-accreditations"

          >

            <span class="viewer-accreditations-title">

              <i class="ri-shield-check-line"></i>

              {{ $t('ui-requested-approvals') }}

            </span>

            <div class="viewer-accreditation-list">

              <span

                v-for="accreditation in trainingRequest.accreditations"

                :key="accreditation.id"

              >

                {{ accreditation.name }}

              </span>

            </div>

          </div>

          <div

            class="completion-note"

            :class="{ completed: isCourseProgressCompleted }"

          >

            <div class="note-icon">

              <i

                :class="

                  isCourseProgressCompleted

                    ? 'ri-checkbox-circle-fill'

                    : 'ri-lock-2-line'

                "

              ></i>

            </div>

            <div>

              <strong>

                {{

                  isCourseProgressCompleted

                    ? 'Course completed'

                    : 'Final exam locked'

                }}

              </strong>

              <p>

                {{

                  isCourseProgressCompleted

                    ? finalExamHelperText

                    : 'Complete the entire SCORM course to unlock the final exam.'

                }}

              </p>

            </div>

          </div>

          <button

            type="button"

            class="sidebar-exam-btn"

            :disabled="!canGoToExam"

            @click="goToFinalExam"

          >

            <i class="ri-edit-circle-line"></i>

            {{ finalExamActionLabel === 'Synchronizing...' ? 'Synchronizing completion...' : 'Go to final exam' }}

          </button>

        </div>

      </aside>

      <main ref="viewerShell" class="package-shell">

        <div class="package-header">

          <div

            class="package-progress-panel compact"

            :class="{ completed: isCourseProgressCompleted }"

          >

            <div class="compact-progress-heading">

              <span>

                <i :class="progressStatusIcon"></i>

                {{ $t('ui-progress') }}

              </span>

              <strong>{{ formattedCourseProgress }}%</strong>

            </div>

            <div class="package-progress-track">

              <div

                class="package-progress-bar"

                :style="{ width: `${formattedCourseProgress}%` }"

              ></div>

            </div>

          </div>

        </div>

        <div v-if="packageAvailable && apiReady" class="iframe-wrap">

          <div v-if="!iframeLoaded" class="package-loader">

            <span class="spinner-border" role="status"></span>

            <p>{{ $t('ui-loading-scorm-package') }}</p>

          </div>

          <iframe

            :src="launchUrl"

            :title="course.title || 'SCORM package'"

            allow="fullscreen; autoplay"

            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-downloads allow-presentation"

            @load="handleIframeLoad"

          ></iframe>

        </div>

        <div v-else class="package-empty">

          <div class="empty-icon">

            <i class="ri-file-warning-line"></i>

          </div>

          <h3>{{ $t('ui-scorm-package-unavailable') }}</h3>

          <p>

            {{ $t('ui-the-launch-file-could-not-be-found-please-contact-an-administrator') }}

          </p>

          <button type="button" @click="goBackToCourses">

            <i class="ri-arrow-left-line"></i>

            {{ $t('ui-back-to-my-courses') }}

          </button>

        </div>

      </main>

    </div>

  </section>

</template>

<style scoped>

.scorm-viewer-page {

  --topbar-height: 76px;

  min-height: calc(100dvh - 82px - 58px);

  height: calc(100dvh - 82px - 58px);

  background: #f4f7fb;

  display: grid;

  grid-template-rows: var(--topbar-height) minmax(0, 1fr);

  overflow: hidden;

}

.scorm-topbar {

  min-width: 0;

  padding: 10px 18px;

  border-bottom: 1px solid #e7ecf3;

  background: #ffffff;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 16px;

}

.topbar-left,

.topbar-actions,

.course-kicker {

  display: flex;

  align-items: center;

}

.topbar-left {

  min-width: 0;

  gap: 12px;

}

.topbar-actions {

  flex-shrink: 0;

  gap: 9px;

}

.icon-btn {

  width: 42px;

  height: 42px;

  min-width: 42px;

  border: 1px solid #e5eaf2;

  border-radius: 13px;

  background: #ffffff;

  color: #405189;

  display: inline-flex;

  align-items: center;

  justify-content: center;

}

.icon-btn.back {

  border-color: transparent;

  background: #071126;

  color: #ffffff;

}

.icon-btn:disabled {

  opacity: 0.45;

}

.icon-btn i {

  font-size: 19px;

}

.course-heading {

  min-width: 0;

}

.course-kicker {

  gap: 7px;

  margin-bottom: 4px;

}

.format-badge,

.access-badge {

  min-height: 23px;

  padding: 3px 8px;

  border-radius: 999px;

  font-size: 9px;

  font-weight: 900;

  display: inline-flex;

  align-items: center;

  gap: 5px;

}

.format-badge {

  color: #405189;

  background: rgba(104, 124, 254, 0.1);

}

.access-badge {

  color: #087f6f;

  background: rgba(10, 179, 156, 0.1);

}

.course-heading h1 {

  max-width: 700px;

  margin: 0;

  color: #071126;

  font-size: 19px;

  font-weight: 900;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;

}

.exam-btn {

  height: 44px;

  padding: 0 14px;

  border: none;

  border-radius: 13px;

  color: #ffffff;

  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);

  box-shadow: 0 9px 20px rgba(104, 124, 254, 0.24);

  font-size: 11px;

  font-weight: 900;

  display: inline-flex;

  align-items: center;

  gap: 7px;

}

.exam-btn small {

  padding-left: 7px;

  border-left: 1px solid rgba(255, 255, 255, 0.28);

  font-size: 9px;

}

.exam-btn:disabled,

.sidebar-exam-btn:disabled {

  opacity: 0.55;

  cursor: not-allowed;

}

.scorm-body {

  min-height: 0;

  padding: 12px;

  display: grid;

  grid-template-columns: 300px minmax(0, 1fr);

  gap: 12px;

  overflow: hidden;

}

.course-sidebar,

.package-shell {

  min-height: 0;

  border: 1px solid #e5eaf2;

  border-radius: 20px;

  background: #ffffff;

  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);

  overflow: hidden;

}

.course-sidebar {

  overflow-y: auto;

}

.sidebar-cover {

  height: 155px;

  background: #eef2f7;

}

.sidebar-cover img {

  width: 100%;

  height: 100%;

  object-fit: cover;

}

.cover-fallback {

  width: 100%;

  height: 100%;

  color: #ffffff;

  background: linear-gradient(135deg, #9bdceb 0%, #0d8fe9 100%);

  display: flex;

  align-items: center;

  justify-content: center;

}

.cover-fallback i {

  font-size: 54px;

}

.sidebar-content {

  padding: 17px;

}

.category-line {

  margin-bottom: 7px;

  color: #0d8fe9;

  font-size: 11px;

  font-weight: 900;

  display: flex;

  align-items: center;

  gap: 5px;

}

.sidebar-content h2 {

  margin: 0 0 7px;

  color: #071126;

  font-size: 18px;

  font-weight: 900;

  line-height: 1.3;

}

.sidebar-content > p {

  margin: 0 0 14px;

  color: #64748b;

  font-size: 11px;

  line-height: 1.55;

}

.meta-list {

  display: grid;

  gap: 7px;

}

.meta-row {

  min-height: 38px;

  padding: 7px 9px;

  border: 1px solid #edf1f6;

  border-radius: 11px;

  background: #fbfcfe;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 8px;

}

.meta-row span {

  color: #64748b;

  font-size: 10px;

  font-weight: 800;

  display: inline-flex;

  align-items: center;

  gap: 5px;

}

.meta-row span i {

  color: #687cfe;

}

.meta-row strong {

  color: #071126;

  font-size: 10px;

  font-weight: 900;

  text-align: right;

}

.direct-access-note {

  margin-top: 12px;

  padding: 10px;

  border: 1px solid rgba(10, 179, 156, 0.18);

  border-radius: 12px;

  background: rgba(10, 179, 156, 0.07);

  display: flex;

  align-items: flex-start;

  gap: 8px;

}

.note-icon {

  width: 30px;

  height: 30px;

  min-width: 30px;

  border-radius: 9px;

  color: #ffffff;

  background: #0ab39c;

  display: flex;

  align-items: center;

  justify-content: center;

}

.direct-access-note strong {

  display: block;

  margin-bottom: 2px;

  color: #087f6f;

  font-size: 10px;

  font-weight: 900;

}

.direct-access-note p {

  margin: 0;

  color: #397b72;

  font-size: 9px;

  line-height: 1.45;

}

.completion-note {

  margin-top: 12px;

  padding: 11px;

  border: 1px solid rgba(240, 101, 72, 0.2);

  border-radius: 12px;

  background: rgba(240, 101, 72, 0.08);

  display: flex;

  align-items: flex-start;

  gap: 9px;

}

.completion-note .note-icon {

  width: 32px;

  height: 32px;

  min-width: 32px;

  border-radius: 9px;

  color: #ffffff;

  background: #f06548;

  display: flex;

  align-items: center;

  justify-content: center;

}

.completion-note strong {

  display: block;

  margin-bottom: 3px;

  color: #b42318;

  font-size: 11px;

  font-weight: 900;

}

.completion-note p {

  margin: 0;

  color: #8a3c32;

  font-size: 10px;

  line-height: 1.45;

}

.completion-note.completed {

  border-color: rgba(10, 179, 156, 0.2);

  background: rgba(10, 179, 156, 0.08);

}

.completion-note.completed .note-icon {

  background: #0ab39c;

}

.completion-note.completed strong {

  color: #087f6f;

}

.completion-note.completed p {

  color: #397b72;

}

.sidebar-exam-btn {

  width: 100%;

  height: 42px;

  margin-top: 12px;

  border: none;

  border-radius: 12px;

  color: #ffffff;

  background: #071126;

  font-size: 11px;

  font-weight: 900;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 7px;

}

.package-shell {

  display: grid;

  grid-template-rows: 92px minmax(0, 1fr);

}

.package-shell:fullscreen {

  width: 100vw;

  height: 100vh;

  border: none;

  border-radius: 0;

}

.package-header {

  padding: 9px 15px;

  border-bottom: 1px solid #e8edf4;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;

}

.package-header span {

  display: block;

  color: #687cfe;

  font-size: 9px;

  font-weight: 900;

  text-transform: uppercase;

  letter-spacing: 0.05em;

}

.package-header strong {

  display: block;

  max-width: 600px;

  margin-top: 2px;

  color: #071126;

  font-size: 12px;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;

}

.package-status {

  padding: 6px 9px;

  border-radius: 999px;

  font-size: 10px;

  font-weight: 900;

  display: inline-flex;

  align-items: center;

  gap: 5px;

}

.package-status.available {

  color: #087f6f;

  background: rgba(10, 179, 156, 0.1);

}

.package-status.missing {

  color: #b42318;

  background: rgba(240, 101, 72, 0.1);

}

.iframe-wrap {

  position: relative;

  min-height: 0;

  background: #f8fafc;

}

.iframe-wrap iframe {

  width: 100%;

  height: 100%;

  border: 0;

  background: #ffffff;

}

.package-loader,

.package-empty {

  position: absolute;

  inset: 0;

  background: #f8fafc;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  gap: 10px;

}

.package-loader {

  z-index: 2;

  color: #64748b;

}

.package-empty {

  position: static;

  padding: 30px;

  text-align: center;

}

.empty-icon {

  width: 68px;

  height: 68px;

  border-radius: 21px;

  color: #f06548;

  background: rgba(240, 101, 72, 0.1);

  display: flex;

  align-items: center;

  justify-content: center;

}

.empty-icon i {

  font-size: 31px;

}

.package-empty h3 {

  margin: 0;

  color: #071126;

  font-size: 20px;

  font-weight: 900;

}

.package-empty p {

  max-width: 430px;

  margin: 0;

  color: #64748b;

}

.package-empty button {

  height: 40px;

  padding: 0 14px;

  border: none;

  border-radius: 11px;

  color: #ffffff;

  background: #071126;

  font-weight: 900;

  display: flex;

  align-items: center;

  gap: 7px;

}

@media (max-width: 991.98px) {

  .scorm-viewer-page {

    height: auto;

    min-height: calc(100dvh - 82px - 58px);

    overflow: visible;

  }

  .scorm-topbar {

    align-items: flex-start;

  }

  .access-badge,

  .exam-btn small {

    display: none;

  }

  .scorm-body {

    min-height: calc(100dvh - 82px - 58px - var(--topbar-height));

    grid-template-columns: 1fr;

    overflow: visible;

  }

  .course-sidebar {

    display: none;

  }

  .package-shell {

    min-height: calc(100dvh - 82px - 58px - var(--topbar-height) - 24px);

  }

}

@media (max-width: 575.98px) {

  .scorm-viewer-page {

    --topbar-height: 68px;

  }

  .scorm-topbar {

    padding: 8px 9px;

  }

  .course-kicker {

    display: none;

  }

  .course-heading h1 {

    max-width: 155px;

    font-size: 14px;

  }

  .topbar-actions {

    gap: 5px;

  }

  .icon-btn {

    width: 36px;

    height: 36px;

    min-width: 36px;

    border-radius: 10px;

  }

  .exam-btn {

    width: 38px;

    height: 38px;

    padding: 0;

    justify-content: center;

  }

  .exam-btn span {

    display: none;

  }

  .scorm-body {

    padding: 6px;

  }

  .package-shell {

    border-radius: 14px;

  }

  .package-header {

    padding: 7px 10px;

  }

}





.package-progress-panel {

  min-width: 0;

  flex: 1;

  display: grid;

  grid-template-columns: minmax(155px, auto) minmax(220px, 1fr) auto;

  align-items: center;

  gap: 18px;

  padding: 12px 14px;

  border: 1px solid rgba(23, 111, 157, 0.16);

  border-radius: 16px;

  background: linear-gradient(

    135deg,

    rgba(36, 87, 117, 0.055) 0%,

    rgba(23, 111, 157, 0.055) 52%,

    rgba(13, 143, 233, 0.07) 100%

  );

}

.package-progress-panel.completed {

  border-color: rgba(10, 179, 156, 0.22);

  background: linear-gradient(

    135deg,

    rgba(10, 179, 156, 0.06) 0%,

    rgba(8, 127, 111, 0.08) 100%

  );

}

.package-progress-identity {

  min-width: 0;

  display: flex;

  align-items: center;

}

.package-progress-copy {

  min-width: 0;

}

.package-progress-copy span,

.package-progress-copy strong {

  display: block;

}

.package-progress-label {

  width: fit-content;

  margin-bottom: 4px;

  color: #526579;

  font-size: 9px;

  font-weight: 900;

  text-transform: uppercase;

  letter-spacing: 0.055em;

  display: inline-flex !important;

  align-items: center;

  gap: 6px;

}

.package-progress-label i {

  width: 22px;

  height: 22px;

  min-width: 22px;

  border: 1px solid rgba(23, 111, 157, 0.18);

  border-radius: 7px;

  color: #176f9d;

  background: rgba(23, 111, 157, 0.08);

  font-size: 13px;

  line-height: 1;

  display: inline-flex;

  align-items: center;

  justify-content: center;

}

.package-progress-panel.completed .package-progress-label i {

  border-color: rgba(10, 179, 156, 0.22);

  color: #087f6f;

  background: rgba(10, 179, 156, 0.1);

}

.package-progress-copy strong {

  padding-left: 28px;

  color: #071126;

  font-size: 13px;

  font-weight: 950;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;

}

.package-progress-meter {

  min-width: 0;

}

.package-progress-track {

  height: 10px;

  border-radius: 999px;

  background: #e5ecf3;

  overflow: hidden;

  box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);

}

.package-progress-bar {

  position: relative;

  height: 100%;

  min-width: 0;

  border-radius: inherit;

  background: linear-gradient(90deg, #245775 0%, #176f9d 48%, #0d8fe9 100%);

  box-shadow: 0 0 14px rgba(13, 143, 233, 0.2);

  transition: width 0.35s ease;

}

.package-progress-bar::after {

  content: '';

  position: absolute;

  inset: 0;

  background: linear-gradient(

    90deg,

    transparent 0%,

    rgba(255, 255, 255, 0.34) 50%,

    transparent 100%

  );

  transform: translateX(-100%);

  animation: progress-shine 2.6s ease-in-out infinite;

}

.package-progress-panel.completed .package-progress-bar {

  background: linear-gradient(90deg, #087f6f 0%, #0ab39c 100%);

  box-shadow: 0 0 14px rgba(10, 179, 156, 0.2);

}

.package-progress-caption {

  margin-top: 7px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;

  color: #64748b;

  font-size: 9.5px;

  font-weight: 750;

}

.package-progress-caption span {

  min-width: 0;

  display: inline-flex;

  align-items: center;

  gap: 5px;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;

}

.package-progress-caption span i {

  color: #176f9d;

  font-size: 12px;

}

.package-progress-panel.completed .package-progress-caption span i {

  color: #087f6f;

}

.package-progress-caption strong {

  flex-shrink: 0;

  color: #334155;

  font-size: 9.5px;

  font-weight: 900;

}

.package-progress-value {

  min-width: 70px;

  padding-left: 16px;

  border-left: 1px solid rgba(148, 163, 184, 0.26);

  text-align: right;

}

.package-progress-value strong,

.package-progress-value span {

  display: block;

}

.package-progress-value strong {

  color: #176f9d;

  font-size: 24px;

  line-height: 1;

  font-weight: 950;

  letter-spacing: -0.04em;

}

.package-progress-panel.completed .package-progress-value strong {

  color: #087f6f;

}

.package-progress-value span {

  margin-top: 4px;

  color: #64748b;

  font-size: 8.5px;

  font-weight: 900;

  text-transform: uppercase;

  letter-spacing: 0.05em;

}

.live-progress-label {

  color: #176f9d;

}

.package-progress-panel.completed .live-progress-label {

  color: #087f6f;

}

@keyframes progress-shine {

  0%, 55% {

    transform: translateX(-100%);

  }

  100% {

    transform: translateX(100%);

  }

}

.viewer-accreditations {

  margin-top: 14px;

  padding: 12px;

  border: 1px solid #e2e8f0;

  border-radius: 14px;

  background: #f8fafc;

}

.viewer-accreditations-title {

  display: flex;

  align-items: center;

  gap: 6px;

  color: #64748b;

  font-size: 10.5px;

  font-weight: 850;

  text-transform: uppercase;

}

.viewer-accreditations-title i {

  color: #687cfe;

  font-size: 15px;

}

.viewer-accreditation-list {

  display: flex;

  flex-wrap: wrap;

  gap: 7px;

  margin-top: 9px;

}

.viewer-accreditation-list span {

  display: inline-flex;

  align-items: center;

  padding: 6px 9px;

  border-radius: 999px;

  background: rgba(10, 179, 156, 0.12);

  color: #0a8f7e;

  font-size: 10.5px;

  font-weight: 850;

}

@media (max-width: 991.98px) {

  .package-shell {

    grid-template-rows: 88px minmax(0, 1fr);

  }

  .package-progress-panel {

    grid-template-columns: minmax(135px, auto) minmax(180px, 1fr) auto;

    gap: 12px;

    padding: 10px 12px;

  }

  .package-progress-value strong {

    font-size: 21px;

  }

}

@media (max-width: 767.98px) {

  .package-shell {

    grid-template-rows: 116px minmax(0, 1fr);

  }

  .package-header {

    align-items: stretch;

  }

  .package-progress-panel {

    grid-template-columns: minmax(0, 1fr) auto;

    gap: 10px 12px;

  }

  .package-progress-meter {

    grid-column: 1 / -1;

    grid-row: 2;

  }

  .package-status {

    align-self: flex-start;

  }

}

@media (max-width: 575.98px) {

  .package-shell {

    grid-template-rows: 112px minmax(0, 1fr);

  }

  .package-header {

    padding: 8px;

    gap: 7px;

  }

  .package-progress-panel {

    padding: 9px 10px;

    border-radius: 13px;

  }

  .package-progress-label i {

    width: 20px;

    height: 20px;

    min-width: 20px;

    border-radius: 6px;

    font-size: 12px;

  }

  .package-progress-copy span {

    font-size: 8px;

  }

  .package-progress-copy strong {

    padding-left: 26px;

    font-size: 11px;

  }

  .package-progress-value {

    min-width: 56px;

    padding-left: 10px;

  }

  .package-progress-value strong {

    font-size: 18px;

  }

  .package-progress-caption span {

    max-width: 150px;

  }

  .package-status {

    padding: 5px 7px;

    font-size: 8.5px;

  }

}



/* =========================================================

*   MINIMAL VIEWER DESIGN

*   Keep the SCORM runtime logic unchanged and give priority

*   to the course package instead of decorative UI elements.

*   ========================================================= */

.scorm-viewer-page {

  --viewer-border: #e3ebf3;

  --viewer-blue: #0d8fe9;

  --viewer-dark: #071126;

  --viewer-success: #0a9f8c;

  background: #f3f7fb;

}

.scorm-topbar {

  padding: 9px 18px;

  border-bottom-color: var(--viewer-border);

  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);

}

.icon-btn.back,

.sidebar-exam-btn,

.exam-btn {

  background: var(--viewer-dark);

  box-shadow: none;

}

.exam-btn {

  background: var(--viewer-dark);

}

.scorm-body {

  padding: 12px;

  gap: 12px;

  grid-template-columns: 300px minmax(0, 1fr);

}

.course-sidebar,

.package-shell {

  border-color: var(--viewer-border);

  border-radius: 18px;

  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.055);

}

.sidebar-cover {

  height: 150px;

}

.sidebar-content {

  padding: 16px;

}

.meta-row {

  min-height: 36px;

  border-color: #e8eef4;

  background: #fbfdff;

}

.meta-row span i,

.viewer-accreditations-title i {

  color: var(--viewer-blue);

}

.viewer-accreditations {

  padding: 11px;

  border-color: var(--viewer-border);

  background: #f8fbfe;

}

.completion-note {

  padding: 10px;

  border-radius: 11px;

}

.sidebar-exam-btn {

  height: 42px;

  border-radius: 11px;

}

.package-shell {

  grid-template-rows: 68px minmax(0, 1fr);

}

.package-header {

  min-height: 68px;

  padding: 9px 14px;

  border-bottom-color: var(--viewer-border);

  background: #ffffff;

}

.package-progress-panel.compact {

  width: 100%;

  min-width: 0;

  padding: 0;

  display: grid;

  grid-template-columns: minmax(145px, auto) minmax(220px, 1fr);

  align-items: center;

  gap: 16px;

  border: 0;

  border-radius: 0;

  background: transparent;

  box-shadow: none;

}

.compact-progress-heading {

  min-width: 0;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 14px;

}

.compact-progress-heading span {

  display: inline-flex;

  align-items: center;

  gap: 7px;

  color: #475569;

  font-size: 11px;

  font-weight: 850;

  white-space: nowrap;

}

.compact-progress-heading span i {

  width: 27px;

  height: 27px;

  min-width: 27px;

  border-radius: 8px;

  color: var(--viewer-blue);

  background: #eaf7ff;

  display: inline-flex;

  align-items: center;

  justify-content: center;

  font-size: 14px;

}

.compact-progress-heading strong {

  color: var(--viewer-dark);

  font-size: 17px;

  line-height: 1;

  font-weight: 950;

  white-space: nowrap;

}

.package-progress-panel.compact.completed .compact-progress-heading span i,

.package-progress-panel.compact.completed .compact-progress-heading strong {

  color: #087f6f;

}

.package-progress-panel.compact.completed .compact-progress-heading span i {

  background: #e8f7f4;

}

.package-progress-track {

  height: 8px;

  border-radius: 999px;

  background: #e7eef5;

  box-shadow: none;

}

.package-progress-bar {

  background: linear-gradient(90deg, #37cced 0%, var(--viewer-blue) 100%);

  box-shadow: none;

  transition: width 0.3s ease;

}

.package-progress-bar::after {

  display: none;

}

.package-progress-panel.compact.completed .package-progress-bar {

  background: linear-gradient(90deg, #0ab39c 0%, #087f6f 100%);

  box-shadow: none;

}

.iframe-wrap {

  overflow: hidden;

  background: #ffffff;

}

.iframe-wrap iframe {

  display: block;

}

@media (max-width: 1199.98px) {

  .scorm-body {

    grid-template-columns: 275px minmax(0, 1fr);

  }

}

@media (max-width: 991.98px) {

  .scorm-body {

    grid-template-columns: 1fr;

  }

  .package-shell {

    grid-template-rows: 68px minmax(0, 1fr);

  }

}

@media (max-width: 767.98px) {

  .package-shell {

    grid-template-rows: 76px minmax(0, 1fr);

  }

  .package-header {

    min-height: 76px;

    padding: 10px 12px;

  }

  .package-progress-panel.compact {

    grid-template-columns: 1fr;

    gap: 9px;

  }

  .compact-progress-heading {

    justify-content: space-between;

  }

}

@media (max-width: 575.98px) {

  .scorm-topbar {

    padding: 7px 8px;

  }

  .package-header {

    padding: 9px 10px;

  }

  .compact-progress-heading span {

    font-size: 10px;

  }

  .compact-progress-heading strong {

    font-size: 15px;

  }

  .package-progress-track {

    height: 7px;

  }

}

</style>
