import {createI18n} from 'vue-i18n'


import en from './lang/en.json'
import fr from './lang/fr.json'

const messages = {
  en,
  fr,
}

const locale = typeof window !== 'undefined'
  ? window.localStorage.getItem('lms_language') || 'en'
  : 'en'

const i18n = createI18n({
  locale,
  fallbackLocale: 'en',
  messages: messages
});
export default i18n;
