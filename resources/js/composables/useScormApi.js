import { ref } from 'vue'

export function useScormApi({
  version = '1.2',
  initialValues = {},
  commitUrl,
  storageKey,
  onCommitSuccess = null,
}) {
  const apiReady = ref(false)

  const configuredVersion = String(version || '')
    .toLowerCase()
    .includes('2004')
    ? '2004'
    : '1.2'

  let activeApiVersion = configuredVersion

  const localStorageKey = storageKey || 'lms:scorm:default'

  const nonPersistentKeys = new Set([
    'cmi.core.student_id',
    'cmi.core.student_name',
    'cmi.core.entry',
    'cmi.core.lesson_mode',
    'cmi.core.credit',
    'cmi.learner_id',
    'cmi.learner_name',
    'cmi.entry',
    'cmi.mode',
    'cmi.credit',
  ])

  const getCsrfToken = () => {
    return document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content') || ''
  }

  const getXsrfCookie = () => {
    const cookie = document.cookie
      .split('; ')
      .find(item => item.startsWith('XSRF-TOKEN='))

    if (!cookie) {
      return ''
    }

    return decodeURIComponent(
      cookie.substring('XSRF-TOKEN='.length)
    )
  }

  const readLocalValues = () => {
    try {
      const raw = window.localStorage.getItem(localStorageKey)

      if (!raw) {
        return {}
      }

      const parsed = JSON.parse(raw)

      return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
        ? parsed
        : {}
    } catch (error) {
      console.warn('Unable to read local SCORM state:', error)
      return {}
    }
  }

  const localValues = readLocalValues()

  const values = {
    ...initialValues,
    ...localValues,
  }

  const hasMeaningfulResumeState = () => {
    const resumeKeys = [
      'cmi.core.lesson_location',
      'cmi.location',
      'cmi.suspend_data',
      'cmi.core.exit',
      'cmi.exit',
    ]

    if (
      resumeKeys.some(key => String(values[key] || '').trim() !== '')
    ) {
      return true
    }

    const lessonStatus = String(
      values['cmi.core.lesson_status'] || ''
    ).toLowerCase()

    const completionStatus = String(
      values['cmi.completion_status'] || ''
    ).toLowerCase()

    if (
      lessonStatus &&
      !['not attempted', 'unknown'].includes(lessonStatus)
    ) {
      return true
    }

    if (
      completionStatus &&
      !['not attempted', 'unknown'].includes(completionStatus)
    ) {
      return true
    }

    return Object.keys(localValues).length > 0
  }

  const serverRequestedResume = [
    initialValues['cmi.core.entry'],
    initialValues['cmi.entry'],
  ].some(value => String(value || '').toLowerCase() === 'resume')

  const resumeEntry = (
    serverRequestedResume || hasMeaningfulResumeState()
  )
    ? 'resume'
    : 'ab-initio'

  // LMS read-only launch values.
  values['cmi.core.student_id'] = String(
    initialValues['cmi.core.student_id'] || ''
  )
  values['cmi.core.student_name'] = String(
    initialValues['cmi.core.student_name'] || 'Learner'
  )
  values['cmi.core.entry'] = resumeEntry
  values['cmi.core.lesson_mode'] = 'normal'
  values['cmi.core.credit'] = 'credit'

  values['cmi.learner_id'] = String(
    initialValues['cmi.learner_id'] || ''
  )
  values['cmi.learner_name'] = String(
    initialValues['cmi.learner_name'] || 'Learner'
  )
  values['cmi.entry'] = resumeEntry
  values['cmi.mode'] = 'normal'
  values['cmi.credit'] = 'credit'

  let initialized = false
  let terminated = false
  let lastError = '0'
  let saveTimer = null
  let listenersInstalled = false
  let saveQueue = Promise.resolve()

  const setActiveApiVersion = apiVersion => {
    activeApiVersion = apiVersion === '2004' ? '2004' : '1.2'
  }

  const setError = code => {
    lastError = String(code)
  }

  const getErrorString = code => {
    const errors = {
      0: 'No error',
      101: 'General exception',
      103: 'Already initialized',
      201: 'Invalid argument error',
      301: 'Not initialized',
      401: 'Not implemented error',
      403: 'Element is read only',
      404: 'Element is write only',
      405: 'Incorrect data type',
    }

    return errors[String(code)] || 'Unknown error'
  }

  const getPersistableValues = () => {
    return Object.fromEntries(
      Object.entries(values).filter(([key]) => {
        return key.startsWith('cmi.') && !nonPersistentKeys.has(key)
      })
    )
  }

  const persistLocally = () => {
    try {
      window.localStorage.setItem(
        localStorageKey,
        JSON.stringify(getPersistableValues())
      )
    } catch (error) {
      console.warn('Unable to save local SCORM state:', error)
    }
  }

  const createPayload = () => {
    return {
      _token: getCsrfToken(),
      version: activeApiVersion,
      values: getPersistableValues(),
    }
  }

  const sendFetchRequest = async payload => {
    const csrfToken = getCsrfToken()
    const xsrfCookie = getXsrfCookie()

    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }

    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken
    } else if (xsrfCookie) {
      headers['X-XSRF-TOKEN'] = xsrfCookie
    }

    const response = await fetch(commitUrl, {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers,
      body: JSON.stringify(payload),
    })

    if (!response.ok) {
      const responseText = await response.text()

      console.error(
        'SCORM commit response:',
        response.status,
        responseText
      )

      throw new Error(
        `SCORM commit failed with status ${response.status}`
      )
    }

    const result = await response.json()

    if (typeof onCommitSuccess === 'function') {
      onCommitSuccess(result)
    }

    return result
  }

  const saveToServer = (useBeacon = false) => {
    persistLocally()

    if (!commitUrl) {
      return Promise.resolve(null)
    }

    const payload = createPayload()

    if (
      useBeacon &&
      typeof navigator.sendBeacon === 'function'
    ) {
      const blob = new Blob(
        [JSON.stringify(payload)],
        { type: 'application/json' }
      )

      navigator.sendBeacon(commitUrl, blob)
      return Promise.resolve(null)
    }

    saveQueue = saveQueue
      .catch(() => null)
      .then(() => sendFetchRequest(payload))
      .catch(error => {
        console.error('SCORM commit error:', error)
        return null
      })

    return saveQueue
  }

  const scheduleSave = (delay = 250) => {
    persistLocally()

    if (saveTimer) {
      window.clearTimeout(saveTimer)
    }

    saveTimer = window.setTimeout(() => {
      saveTimer = null
      saveToServer(false)
    }, delay)
  }

  const getProgressMeasure = () => {
    const candidates = [
      values['cmi.progress_measure'],
      values['cmi.core.progress_measure'],
      values['cmi.progress'],
      values['cmi.core.progress'],
    ]

    for (const candidate of candidates) {
      const parsed = Number(candidate)

      if (!Number.isNaN(parsed)) {
        return parsed > 1 ? parsed / 100 : parsed
      }
    }

    return null
  }

  const isCompleted = () => {
    const lessonStatus = String(
      values['cmi.core.lesson_status'] || ''
    ).toLowerCase()

    const completionStatus = String(
      values['cmi.completion_status'] || ''
    ).toLowerCase()

    const progressMeasure = getProgressMeasure()

    return (
      ['completed', 'passed'].includes(lessonStatus) ||
      completionStatus === 'completed' ||
      (progressMeasure !== null && progressMeasure >= 1)
    )
  }

  const prepareResumeState = () => {
    if (isCompleted()) {
      persistLocally()
      return
    }

    if (activeApiVersion === '1.2') {
      values['cmi.core.exit'] = 'suspend'
    } else {
      values['cmi.exit'] = 'suspend'
    }

    persistLocally()
  }

  const flushBeforeLeave = () => {
    if (saveTimer) {
      window.clearTimeout(saveTimer)
      saveTimer = null
    }

    prepareResumeState()
    saveToServer(true)
  }

  const handlePageHide = () => {
    flushBeforeLeave()
  }

  const handleBeforeUnload = () => {
    flushBeforeLeave()
  }

  const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden') {
      prepareResumeState()
      saveToServer(true)
    }
  }

  const installPersistenceListeners = () => {
    if (listenersInstalled) {
      return
    }

    window.addEventListener('pagehide', handlePageHide)
    window.addEventListener('beforeunload', handleBeforeUnload)
    document.addEventListener(
      'visibilitychange',
      handleVisibilityChange
    )

    listenersInstalled = true
  }

  const removePersistenceListeners = () => {
    if (!listenersInstalled) {
      return
    }

    window.removeEventListener('pagehide', handlePageHide)
    window.removeEventListener('beforeunload', handleBeforeUnload)
    document.removeEventListener(
      'visibilitychange',
      handleVisibilityChange
    )

    listenersInstalled = false
  }

  const initialize = (argument, apiVersion) => {
    setActiveApiVersion(apiVersion)

    if (String(argument ?? '') !== '') {
      setError('201')
      return 'false'
    }

    if (initialized && !terminated) {
      setError('103')
      return 'false'
    }

    initialized = true
    terminated = false
    setError('0')
    persistLocally()

    return 'true'
  }

  const terminate = (argument, apiVersion) => {
    setActiveApiVersion(apiVersion)

    if (String(argument ?? '') !== '') {
      setError('201')
      return 'false'
    }

    if (!initialized) {
      setError('301')
      return 'false'
    }

    if (saveTimer) {
      window.clearTimeout(saveTimer)
      saveTimer = null
    }

    prepareResumeState()
    saveToServer(true)

    initialized = false
    terminated = true
    setError('0')

    return 'true'
  }

  const getDefaultValue = key => {
    if (key.endsWith('._count')) {
      return '0'
    }

    const defaults = {
      'cmi._version': '1.0',
      'cmi.core.lesson_status': 'not attempted',
      'cmi.completion_status': 'unknown',
      'cmi.success_status': 'unknown',
      'cmi.progress_measure': '',
      'cmi.core.lesson_location': '',
      'cmi.location': '',
      'cmi.suspend_data': '',
      'cmi.core.total_time': '0000:00:00.00',
      'cmi.total_time': 'PT0H0M0S',
      'cmi.core.score._children': 'raw,min,max',
      'cmi.score._children': 'scaled,raw,min,max',
      'cmi.objectives._count': '0',
      'cmi.interactions._count': '0',
      'cmi.comments_from_learner._count': '0',
      'cmi.comments_from_lms._count': '0',
    }

    return Object.prototype.hasOwnProperty.call(defaults, key)
      ? defaults[key]
      : ''
  }

  const getValue = (element, apiVersion) => {
    setActiveApiVersion(apiVersion)

    if (!initialized) {
      setError('301')
      return ''
    }

    const key = String(element || '')
    setError('0')

    if (Object.prototype.hasOwnProperty.call(values, key)) {
      return String(values[key] ?? '')
    }

    return getDefaultValue(key)
  }

  const setValue = (element, value, apiVersion) => {
    setActiveApiVersion(apiVersion)

    if (!initialized) {
      setError('301')
      return 'false'
    }

    const key = String(element || '')

    if (!key.startsWith('cmi.')) {
      setError('201')
      return 'false'
    }

    if (nonPersistentKeys.has(key)) {
      setError('403')
      return 'false'
    }

    values[key] = String(value ?? '')
    setError('0')

    persistLocally()
    scheduleSave()

    return 'true'
  }

  const commit = (argument, apiVersion) => {
    setActiveApiVersion(apiVersion)

    if (String(argument ?? '') !== '') {
      setError('201')
      return 'false'
    }

    if (!initialized) {
      setError('301')
      return 'false'
    }

    if (saveTimer) {
      window.clearTimeout(saveTimer)
      saveTimer = null
    }

    persistLocally()
    saveToServer(false)
    setError('0')

    return 'true'
  }

  const getLastError = apiVersion => {
    setActiveApiVersion(apiVersion)
    return lastError
  }

  const getDiagnostic = (code, apiVersion) => {
    setActiveApiVersion(apiVersion)
    return getErrorString(code || lastError)
  }

  const api12 = {
    LMSInitialize: argument => initialize(argument, '1.2'),
    LMSFinish: argument => terminate(argument, '1.2'),
    LMSGetValue: element => getValue(element, '1.2'),
    LMSSetValue: (element, value) => setValue(element, value, '1.2'),
    LMSCommit: argument => commit(argument, '1.2'),
    LMSGetLastError: () => getLastError('1.2'),
    LMSGetErrorString: code => getErrorString(code),
    LMSGetDiagnostic: code => getDiagnostic(code, '1.2'),
  }

  const api2004 = {
    Initialize: argument => initialize(argument, '2004'),
    Terminate: argument => terminate(argument, '2004'),
    GetValue: element => getValue(element, '2004'),
    SetValue: (element, value) => setValue(element, value, '2004'),
    Commit: argument => commit(argument, '2004'),
    GetLastError: () => getLastError('2004'),
    GetErrorString: code => getErrorString(code),
    GetDiagnostic: code => getDiagnostic(code, '2004'),
  }

  const install = () => {
    window.API = api12
    window.API_1484_11 = api2004

    installPersistenceListeners()
    persistLocally()
    apiReady.value = true
  }

  const uninstall = () => {
    removePersistenceListeners()
    flushBeforeLeave()

    if (window.API === api12) {
      delete window.API
    }

    if (window.API_1484_11 === api2004) {
      delete window.API_1484_11
    }

    apiReady.value = false
  }

  return {
    apiReady,
    values,
    install,
    uninstall,
    save: () => saveToServer(false),
    clearLocalState: () => {
      window.localStorage.removeItem(localStorageKey)
    },
  }
}
