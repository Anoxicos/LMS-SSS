import i18n from '@/i18n'

const keys = {
  paid: 'ui-paid',
  free: 'ui-free',
  private: 'ui-private',
  payant: 'ui-paid',
  gratuit: 'ui-free',
  'privé': 'ui-private',
  online: 'ui-online',
  onsite: 'ui-onsite',
  hybrid: 'ui-hybrid',
  'en ligne': 'ui-online',
  'sur site': 'ui-onsite',
  présentiel: 'ui-onsite',
  hybride: 'ui-hybrid',
  initial: 'ui-initial',
  recurrent: 'ui-recurrent',
  récurrent: 'ui-recurrent',
  english: 'ui-english',
  french: 'ui-french',
  arabic: 'ui-arabic',
  en: 'ui-english',
  fr: 'ui-french',
  ar: 'ui-arabic',
  'online e-learning (lms)': 'ui-online-e-learning-lms',
  'virtual classroom': 'ui-virtual-classroom',
  'on-site classroom training': 'ui-on-site-classroom-training',
  'blended learning': 'ui-blended-learning',
  'self-study': 'ui-self-study',
}

export const formatCourseLabel = value => {
  if (value == null || value === '') return '-'

  const label = String(value).trim()
  const key = keys[label.toLowerCase()]

  if (key) return i18n.global.t(key)

  return label.replaceAll('_', ' ').replaceAll('-', ' ').replace(/\b\w/g, char => char.toUpperCase())
}

export const formatVoiceLanguageLabel = value => {
  const language = String(value || '').toLowerCase()
  const key = {
    'en-us': 'ui-english-us',
    'en-gb': 'ui-english-uk',
    'fr-fr': 'ui-french',
    'ar-sa': 'ui-arabic',
    'es-es': 'ui-spanish',
  }[language]

  return key ? i18n.global.t(key) : formatCourseLabel(value)
}

export const normalizeCourseChoice = (field, value) => {
  if (value == null || value === '') return value

  const normalized = String(value).trim().toLowerCase()

  if (field === 'language') {
    if (['english', 'anglais', 'en'].includes(normalized)) return 'English'
    if (['french', 'français', 'francais', 'fr'].includes(normalized)) return 'French'
    if (['arabic', 'arabe', 'ar'].includes(normalized)) return 'Arabic'
    return value
  }

  if (field === 'type') {
    if (['paid', 'payant'].includes(normalized)) return 'paid'
    if (['free', 'gratuit'].includes(normalized)) return 'free'
    if (['private', 'privé', 'prive'].includes(normalized)) return 'private'
  }

  if (field === 'training_type') {
    if (['online', 'en ligne'].includes(normalized)) return 'online'
    if (['onsite', 'on-site', 'on site', 'sur site', 'présentiel', 'presentiel'].includes(normalized)) return 'onsite'
    if (['hybrid', 'hybride'].includes(normalized)) return 'hybrid'
  }

  return normalized
}
