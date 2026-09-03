import {createI18n} from 'vue-i18n'


import en from './lang/en.json'
import fr from './lang/fr.json'

const messages = {
  en,
  fr,
}

const locale = sessionStorage.getItem("locale");

const i18n = createI18n({
  locale:  locale || "en",
  fallbackLocale: "en",
  messages: messages
});
export default i18n;
