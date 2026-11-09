<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import authLogo from '@assets/images/brand/safesky-auth.svg'
import i18n from '@/i18n'

const props = defineProps({
  loginUrl: {
    type: String,
    required: true,
  },

  embedded: {
    type: Boolean,
    default: false,
  },
  activeMode: {
    type: String,
    default: 'register',
  },
})

const emit = defineEmits(['switch'])
const t = (...args) => i18n.global.t(...args)

defineOptions({
  layout: LayoutAnonyme,
})

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  country: 'Morocco',
  date_of_birth: '',
  place_of_birth: '',
  passport_number: '',
  passport_file: null,
  password: '',
  password_confirmation: '',
  terms: false,
})

const steps = computed(() => [
  { title: t('t-identity'), description: t('t-registration-name-prompt'), icon: 'ri-user-line' },
  { title: t('t-personal-details'), description: t('t-registration-birth-prompt'), icon: 'ri-map-pin-user-line' },
  { title: t('t-passport'), description: t('t-registration-passport-prompt'), icon: 'ri-passport-line' },
  { title: t('t-login-credentials'), description: t('t-registration-credentials-prompt'), icon: 'ri-lock-password-line' },
])
const currentStep = ref(0)
const stepPanels = ref([])
const passwordConfirmationRef = ref(null)
const passportDragging = ref(false)
const passportName = ref('')
const passportUploadError = ref('')
const setStepPanel = (element, index) => {
  if (element) stepPanels.value[index] = element
}

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const passwordStrength = computed(() => {
  const password = form.password
  if (!password) return 0

  const score = [
    password.length >= 8,
    password.length >= 12,
    /[a-z]/.test(password) && /[A-Z]/.test(password),
    /\d/.test(password),
    /[^a-zA-Z0-9]/.test(password),
  ].filter(Boolean).length

  return score <= 1 ? 1 : score <= 2 ? 2 : 3
})
const passwordStrengthLabel = computed(() =>
  [t('t-enter-a-password'), t('t-weak'), t('t-fair'), t('t-strong')][passwordStrength.value]
)
const passwordMeetsLength = computed(() => form.password.length >= 8)

const handlePassport = event => {
  const file = event.target.files?.[0] || null
  if (event.target) event.target.value = ''
  if (!file) return

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(ext) || file.size > 5 * 1024 * 1024) {
    form.passport_file = null
    passportName.value = ''
    passportUploadError.value = 'Choose a PDF or image file up to 5 MB.'
    return
  }

  passportUploadError.value = ''
  passportName.value = file.name
  form.passport_file = file
}

const handlePassportDrop = event => {
  passportDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) handlePassport({ target: { files: [file], value: '' } })
}

const updatePasswordConfirmationValidity = () => {
  passwordConfirmationRef.value?.setCustomValidity(
    form.password_confirmation && form.password_confirmation !== form.password
      ? t('t-passwords-do-not-match')
      : ''
  )
}

const validateStep = index => {
  updatePasswordConfirmationValidity()
  const invalid = stepPanels.value[index]?.querySelector(':invalid')
  if (invalid) {
    invalid.reportValidity()
    return false
  }
  return true
}

const focusCurrentStep = async () => {
  await nextTick()
  stepPanels.value[currentStep.value]?.querySelector('input:not([type="file"]), button')?.focus({ preventScroll: true })
}

const nextStep = async () => {
  if (!validateStep(currentStep.value) || currentStep.value >= steps.value.length - 1) return
  currentStep.value += 1
  await focusCurrentStep()
}

const previousStep = async () => {
  if (currentStep.value === 0) return
  currentStep.value -= 1
  await focusCurrentStep()
}

const submit = () => {
  if (!validateStep(currentStep.value)) return
  form.post(route('learner.register.store'), {
    forceFormData: true,
    preserveScroll: true,

    onError: errors => {
      const errorField = Object.keys(errors)[0]
      const errorStep = {
        first_name: 0, middle_name: 0, last_name: 0,
        country: 1, date_of_birth: 1, place_of_birth: 1,
        passport_number: 2, passport_file: 2,
        email: 3, password: 3, password_confirmation: 3, terms: 3,
      }[errorField]
      if (errorStep !== undefined) currentStep.value = errorStep
    },

    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

<template>
  <main :class="props.embedded ? 'learner-register-embedded' : 'learner-register-page'">
    <Head :title="$t('t-create-learner-account')" />
    <!-- =====================================================
         HERO
    ====================================================== -->
    <section v-if="!props.embedded" class="register-hero">
      <div class="hero-copy">
        <Link href="/" class="register-brand" :aria-label="$t('t-safesky-home')">
          <img :src="authLogo" :alt="$t('t-safesky-home')">
        </Link>

        <div class="auth-mode-toggle" role="group" :aria-label="$t('t-choose-signin-mode')">
          <button
            v-if="props.embedded"
            type="button"
            aria-pressed="false"
            @click="emit('switch', 'login')"
          >{{ $t('t-sign-in') }}</button>
          <a v-else class="auth-mode-link" :href="loginUrl">{{ $t('t-sign-in') }}</a>
          <button type="button" aria-pressed="true" class="is-active" :disabled="!props.embedded" @click="emit('switch', 'register')">{{ $t('t-sign-up') }}</button>
        </div>

        <h1>{{ $t('t-create-your-learner-account') }}</h1>

        <p>
          {{ $t('t-registration-intro') }}
        </p>

        <div class="hero-points">
          <span>
            <i class="ri-shield-check-line"></i>
            {{ $t('t-secure-personal-account') }}
          </span>

          <span>
            <i class="ri-file-lock-line"></i>
            {{ $t('t-private-passport-storage') }}
          </span>

          <span>
            <i class="ri-shopping-cart-2-line"></i>
            {{ $t('t-cart-preserved') }}
          </span>
        </div>
      </div>
    </section>

    <!-- =====================================================
         REGISTRATION FORM
    ====================================================== -->
    <section class="register-shell">
      <form
        class="register-card"
        @submit.prevent="submit"
      >
        <div v-if="props.embedded" class="auth-mode-toggle" role="group" :aria-label="$t('t-choose-signin-mode')">
          <button type="button" :aria-pressed="props.activeMode === 'login'" :class="{ 'is-active': props.activeMode === 'login' }" @click="emit('switch', 'login')">{{ $t('t-sign-in') }}</button>
          <button type="button" :aria-pressed="props.activeMode === 'register'" :class="{ 'is-active': props.activeMode === 'register' }" @click="emit('switch', 'register')">{{ $t('t-sign-up') }}</button>
        </div>

        <header v-if="props.embedded" class="auth-register-heading">
          <h1>{{ $t('t-create-your-learner-account') }}</h1>
          <p>{{ $t('t-secure-course-access') }}</p>
        </header>

        <!-- Header -->
        <header v-if="!props.embedded" class="card-header">
          <span class="header-icon">
            <i :class="steps[currentStep].icon"></i>
          </span>

          <div>
            <h2>{{ steps[currentStep].title }}</h2>
            <p>{{ steps[currentStep].description }}</p>
          </div>
        </header>

        <div v-if="form.processing && form.progress" class="upload-progress" role="status" aria-live="polite">
          <div class="d-flex justify-content-between small"><span>{{ passportName }}</span><strong>{{ form.progress.percentage }}%</strong></div>
          <div class="progress" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" :style="{ width: `${form.progress.percentage}%` }"></div></div>
        </div>

        <div class="step-progress" role="progressbar" :aria-valuenow="currentStep + 1" :aria-valuemin="1" :aria-valuemax="steps.length" :aria-label="$t('t-step-of-total', { step: currentStep + 1, total: steps.length, title: steps[currentStep].title })">
          <span><strong>{{ steps[currentStep].title }}</strong> · {{ $t('t-step-count', { step: currentStep + 1, total: steps.length }) }}</span>
          <div class="step-progress-track"><i :style="{ width: `${((currentStep + 1) / steps.length) * 100}%` }"></i></div>
        </div>

        <!-- Global error -->
        <div
          v-if="form.errors.registration"
          class="global-error"
        >
          {{ form.errors.registration }}
        </div>

        <!-- =================================================
             IDENTITY
        ================================================== -->
        <fieldset :ref="el => setStepPanel(el, 0)" v-show="currentStep === 0" class="register-step" :disabled="currentStep !== 0">
          <legend class="visually-hidden">{{ $t('t-identity') }}</legend>
          <div class="form-section">

          <div class="form-grid three-columns">
            <label class="form-field">
              <span>{{ $t('t-first-name') }} *</span>

              <input
                v-model="form.first_name"
                type="text"
                :placeholder="$t('t-example-name', { name: 'Amira' })"
                autocomplete="given-name"
                required
              >

              <small v-if="form.errors.first_name">
                {{ form.errors.first_name }}
              </small>
            </label>

            <label class="form-field">
              <span>{{ $t('t-middle-name') }}</span>

              <input
                v-model="form.middle_name"
                type="text"
                :placeholder="$t('t-optional')"
                autocomplete="additional-name"
              >

              <small v-if="form.errors.middle_name">
                {{ form.errors.middle_name }}
              </small>
            </label>

            <label class="form-field">
              <span>{{ $t('t-last-name') }} *</span>

              <input
                v-model="form.last_name"
                type="text"
                :placeholder="$t('t-example-name', { name: 'Hassan' })"
                autocomplete="family-name"
                required
              >

              <small v-if="form.errors.last_name">
                {{ form.errors.last_name }}
              </small>
            </label>
          </div>
          </div>
        </fieldset>

        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->
        <fieldset :ref="el => setStepPanel(el, 1)" v-show="currentStep === 1" class="register-step" :disabled="currentStep !== 1">
          <legend class="visually-hidden">{{ $t('t-personal-details') }}</legend>
          <div class="form-section">

          <div class="form-grid">
            <label class="form-field">
              <span>{{ $t('t-country') }} *</span>

              <input
                v-model="form.country"
                type="text"
                :placeholder="$t('t-example-country', { country: 'Morocco' })"
                autocomplete="country-name"
                required
              >

              <small v-if="form.errors.country">
                {{ form.errors.country }}
              </small>
            </label>

            <label class="form-field">
              <span>{{ $t('t-date-of-birth') }} *</span>

              <input
                v-model="form.date_of_birth"
                type="date"
                required
              >

              <small v-if="form.errors.date_of_birth">
                {{ form.errors.date_of_birth }}
              </small>
            </label>

            <label class="form-field form-field-full">
              <span>{{ $t('t-place-of-birth') }} *</span>

              <input
                v-model="form.place_of_birth"
                type="text"
                :placeholder="$t('t-example-city', { city: 'Casablanca' })"
                required
              >

              <small v-if="form.errors.place_of_birth">
                {{ form.errors.place_of_birth }}
              </small>
            </label>
          </div>
          </div>
        </fieldset>

        <!-- =================================================
             PASSPORT
        ================================================== -->
        <fieldset :ref="el => setStepPanel(el, 2)" v-show="currentStep === 2" class="register-step" :disabled="currentStep !== 2">
          <legend class="visually-hidden">{{ $t('t-passport') }}</legend>
          <div class="form-section">

          <div class="form-grid">
            <label class="form-field">
              <span>{{ $t('t-passport-number') }} *</span>

              <input
                v-model="form.passport_number"
                type="text"
                :placeholder="$t('t-enter-passport-number')"
                required
              >

              <small v-if="form.errors.passport_number">
                {{ form.errors.passport_number }}
              </small>
            </label>

            <label class="form-field">
              <span>{{ $t('t-passport-document') }} *</span>

              <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.webp"
                required
                class="media-drop-target"
                :class="{ 'is-dragging': passportDragging }"
                @dragenter.prevent="passportDragging = true"
                @dragover.prevent="passportDragging = true"
                @dragleave.prevent="passportDragging = false"
                @drop.prevent="handlePassportDrop"
                @change="handlePassport"
              >

              <small v-if="passportName" class="selected-upload" role="status"><i class="ri-check-line" aria-hidden="true"></i> {{ passportName }}</small>
              <small class="file-hint">PDF or JPG, PNG, WebP · up to 5 MB</small>
              <small v-if="passportUploadError" class="upload-error" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ passportUploadError }}</small>

              <small v-if="form.errors.passport_file">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                {{ form.errors.passport_file }}
              </small>
            </label>
          </div>
          </div>
        </fieldset>

        <!-- =================================================
             LOGIN CREDENTIALS
        ================================================== -->
        <fieldset :ref="el => setStepPanel(el, 3)" v-show="currentStep === 3" class="register-step" :disabled="currentStep !== 3">
          <legend class="visually-hidden">{{ $t('t-login-credentials') }}</legend>
          <div class="form-section">

          <div class="form-grid">
            <label class="form-field form-field-full">
              <span>{{ $t('t-email-address') }} *</span>

              <input
                v-model="form.email"
                type="email"
                placeholder="name@example.com"
                autocomplete="email"
                required
              >

              <small v-if="form.errors.email">
                {{ form.errors.email }}
              </small>
            </label>

            <div class="form-field">
              <label class="field-label" for="learner-password">{{ $t('ui-password') }}</label>

              <div class="password-field">
                <input
                  id="learner-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  :placeholder="$t('t-password-minimum-length')"
                  autocomplete="new-password"
                  aria-describedby="password-guidance"
                  minlength="8"
                  required
                  @input="updatePasswordConfirmationValidity"
                >
                <button
                  type="button"
                  class="password-visibility"
                  :aria-label="showPassword ? $t('t-hide-password') : $t('t-show-password')"
                  :aria-pressed="showPassword"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
              </div>

              <small v-if="form.errors.password">{{ form.errors.password }}</small>

              <div id="password-guidance" class="password-guidance" aria-live="polite">
                <div class="password-strength">
                  <span>{{ passwordStrengthLabel }}</span>
                  <span class="strength-meter" aria-hidden="true">
                    <i v-for="step in 3" :key="step" :class="{ active: passwordStrength >= step, [`level-${passwordStrength}`]: passwordStrength >= step }"></i>
                  </span>
                </div>
                <span class="password-requirement" :class="{ met: passwordMeetsLength }">
                  <i :class="passwordMeetsLength ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"></i>
                  {{ $t('t-password-minimum-length') }}
                </span>
              </div>
            </div>

            <div class="form-field">
              <label class="field-label" for="learner-password-confirmation">{{ $t('t-confirm-password') }} *</label>

              <div class="password-field">
                <input
                  id="learner-password-confirmation"
                  ref="passwordConfirmationRef"
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirmation ? 'text' : 'password'"
                  :placeholder="$t('t-reenter-password')"
                  autocomplete="new-password"
                  required
                  @input="updatePasswordConfirmationValidity"
                >
                <button
                  type="button"
                  class="password-visibility"
                  :aria-label="showPasswordConfirmation ? $t('t-hide-confirmation') : $t('t-show-confirmation')"
                  :aria-pressed="showPasswordConfirmation"
                  @click="showPasswordConfirmation = !showPasswordConfirmation"
                >
                  <i :class="showPasswordConfirmation ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
              </div>

              <small v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</small>
            </div>
          </div>
          </div>

          <label class="terms-row">
            <input v-model="form.terms" type="checkbox" required>
            <span>{{ $t('t-registration-terms-confirmation') }}</span>
          </label>
          <small v-if="form.errors.terms" class="terms-error">{{ form.errors.terms }}</small>
        </fieldset>

        <footer class="step-actions">
          <button v-if="currentStep > 0" type="button" class="step-back" @click="previousStep">{{ $t('t-back') }}</button>
          <span class="step-actions-count">{{ currentStep + 1 }} / {{ steps.length }}</span>
          <button v-if="currentStep < steps.length - 1" type="button" class="step-next" @click="nextStep">
            {{ $t('t-continue') }} <i class="ri-arrow-right-line" aria-hidden="true"></i>
          </button>
          <button v-else type="submit" class="step-next" :disabled="form.processing">
            <i :class="form.processing ? 'ri-loader-4-line spinner' : 'ri-user-add-line'" aria-hidden="true"></i>
            {{ form.processing ? $t('t-creating-account') : $t('t-create-account') }}
          </button>
        </footer>

        <Link href="/?arrival=1" class="register-guest-link">
          {{ $t('t-continue-as-guest') }} <i class="ri-arrow-right-line" aria-hidden="true"></i>
        </Link>

        <!-- Login -->
        <p v-if="!props.embedded" class="login-line">
          {{ $t('t-already-have-account') }}

          <button v-if="props.embedded" type="button" @click="emit('switch', 'login')">
            {{ $t('t-sign-in') }}
          </button>
          <a v-else :href="loginUrl">{{ $t('t-sign-in') }}</a>
        </p>
      </form>
    </section>
  </main>
</template>

<style scoped>
.media-drop-target { transition: border-color 140ms ease, background-color 140ms ease; }
.media-drop-target.is-dragging { outline: 2px dashed var(--signal); outline-offset: 2px; background: rgba(31, 92, 255, 0.08); }
.selected-upload { display: block; color: var(--success-deep); }
.file-hint { display: block; color: var(--slate); }
.upload-error { display: block; color: var(--danger-deep); }
.upload-progress .progress { height: 7px; }
/* =========================================================
   PAGE
========================================================= */
.learner-register-page {

  min-height: 100vh;
  padding-bottom: 72px;
  color: var(--ink);
  background:
    linear-gradient(
      180deg,
      var(--paper) 0%,
      var(--chalk) 52%,
      var(--paper) 100%
    );
}

/* =========================================================
   HERO
========================================================= */
.register-hero {
  min-height: 360px;
  padding: 56px 24px 120px;
  color: var(--chalk);
  background: radial-gradient(ellipse at 72% 22%, rgba(56, 225, 255, 0.16), transparent 42%), var(--ink);
}

.hero-copy {
  width: min(960px, 100%);
  margin: 0 auto;
  text-align: center;
}

.register-brand {
  width: fit-content;
  display: block;
  margin: 0 auto 24px;
}

.register-brand img {
  display: block;
  width: min(168px, 42vw);
  max-height: 58px;
  object-fit: contain;
}

/* =========================================================
   BREADCRUMB NAVIGATION
========================================================= */
.hero-breadcrumb {
  width: fit-content;
  min-height: 48px;
  margin: 0 auto 26px;
  padding: 8px 18px;
  border: 1px solid rgba(255, 255, 255, 0.55);
  border-radius: 999px;
  background-color: rgba(255, 255, 255, 0.96);
  box-shadow: 0 12px 30px rgba(7, 17, 38, 0.12);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
}

.hero-breadcrumb a {
  font-size: 13px;
  font-weight: 900;
  line-height: 1;
  text-decoration: none;
  transition:
    color 0.2s ease,
    transform 0.2s ease;
}

.hero-breadcrumb a:hover {
  transform: translateY(-1px);
}

.breadcrumb-home {
  color: var(--ink);
}

.breadcrumb-home:hover {
  color: var(--signal-deep);
}

.breadcrumb-current {
  color: var(--signal-deep);
}

.breadcrumb-current:hover {
  color: var(--signal);
}

.hero-breadcrumb i {
  color: var(--ink);
  font-size: 18px;
  line-height: 1;
}

/* =========================================================
   HERO CONTENT
========================================================= */
.hero-badge {
  padding: 9px 14px;
  border-radius: 999px;
  color: var(--ink);
  background: var(--chalk);
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-copy h1 {
  margin: 10px 0 14px;
  color: var(--chalk);
  font-size: clamp(38px, 5vw, 58px);
  line-height: 1.08;
  font-weight: 900;
  letter-spacing: -1.3px;
}

.hero-copy > p {
  max-width: 720px;
  margin: 0 auto;
  color: rgba(255, 255, 255, 0.92);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.7;
}

.hero-points {
  margin-top: 24px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

.hero-points span {
  padding: 9px 12px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 10px;
  color: var(--chalk);
  background: rgba(7, 17, 38, 0.12);
  font-size: 12px;
  font-weight: 750;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-points i {
  font-size: 16px;
}

/* =========================================================
   REGISTER SHELL
========================================================= */
.register-shell {
  position: relative;
  width: min(980px, calc(100% - 28px));
  margin: -70px auto 0;
}

.register-card {
  padding: 30px;
  border: 1px solid var(--sand);
  border-radius: 4px;
  background: var(--chalk);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
}

/* =========================================================
   CARD HEADER
========================================================= */
.card-header {
  padding-bottom: 22px;
  border-bottom: 1px solid var(--sand);
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  width: 50px;
  height: 50px;
  flex: 0 0 50px;
  border: 1px solid rgba(13, 143, 233, 0.14);
  border-radius: 4px;
  color: var(--signal);
  background: var(--paper);
  font-size: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.card-header h2 {
  margin: 0 0 4px;
  color: var(--ink);
  font-size: 23px;
  font-weight: 900;
}

.card-header p {
  margin: 0;
  color: var(--slate);
  font-size: 13px;
  line-height: 1.5;
}

/* =========================================================
   ERRORS
========================================================= */
.global-error {
  margin-top: 18px;
  padding: 13px;
  border: 1px solid rgba(240, 101, 72, 0.22);
  border-radius: 4px;
  color: var(--danger-deep);
  background: rgba(255, 77, 77, 0.08);
  font-size: 13px;
  font-weight: 750;
}

/* =========================================================
   FORM SECTIONS
========================================================= */
.form-section {
  padding: 24px 0;
  border-bottom: 1px solid var(--sand);
}

.section-title {
  margin-bottom: 15px;
  color: var(--slate);
  font-size: 13px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title i {
  color: var(--signal);
  font-size: 18px;
}

/* =========================================================
   FORM GRID
========================================================= */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.form-grid.three-columns {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.form-field-full {
  grid-column: 1 / -1;
}

/* =========================================================
   FORM FIELDS
========================================================= */
.form-field {
  min-width: 0;
}

.form-field > span,
.field-label {
  display: block;
  margin-bottom: 7px;
  color: var(--slate);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 11px;
  font-weight: 850;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.form-field input {
  width: 100%;
  min-height: 48px;
  padding: 0 13px;
  border: 0;
  border-bottom: 1px solid var(--sand);
  border-radius: 0;
  color: var(--ink);
  background: var(--chalk);
  outline: none;
  font-size: 13px;
  font-weight: 600;
  box-sizing: border-box;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease;
}

.form-field input:hover {
  border-bottom-color: var(--signal);
}

.form-field input[type='file'] {
  padding: 10px 13px;
  border: 1px solid var(--sand);
  border-radius: 4px;
  cursor: pointer;
}

.form-field input:focus {
  border-bottom-color: var(--signal);
  background-color: var(--chalk);
  box-shadow: 0 2px 0 var(--signal);
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 48px;
}

.password-visibility {
  position: absolute;
  top: 50%;
  right: 4px;
  width: 40px;
  height: 40px;
  transform: translateY(-50%);
  border: 0;
  color: var(--slate);
  background: transparent;
  cursor: pointer;
}

.password-visibility:hover {
  color: var(--signal-deep);
}

.password-guidance {
  margin-top: 8px;
  display: grid;
  gap: 5px;
  color: var(--slate);
  font-size: 11px;
}

.password-strength,
.password-requirement {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.strength-meter {
  display: inline-flex;
  gap: 4px;
}

.strength-meter i {
  width: 24px;
  height: 3px;
  background: var(--sand);
}

.strength-meter i.level-1.active {
  background: var(--danger);
}

.strength-meter i.level-2.active {
  background: var(--warning);
}

.strength-meter i.level-3.active {
  background: var(--success-deep);
}

.password-requirement {
  justify-content: flex-start;
}

.password-requirement.met {
  color: var(--success-deep);
}

.form-field small,
.terms-error {
  display: block;
  margin-top: 6px;
  color: var(--danger);
  font-size: 11px;
  font-weight: 750;
}

/* =========================================================
   TERMS
========================================================= */
.terms-row {
  margin-top: 22px;
  padding: 14px;
  border: 1px solid var(--sand);
  border-radius: 4px;
  color: var(--slate);
  background: var(--paper);
  font-size: 12px;
  line-height: 1.6;
  cursor: pointer;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.terms-row input {
  width: 17px;
  height: 17px;
  margin-top: 2px;
  flex: 0 0 17px;
  accent-color: var(--signal);
  cursor: pointer;
}

/* =========================================================
   SUBMIT BUTTON
========================================================= */
.submit-button {
  width: 100%;
  min-height: 54px;
  margin-top: 22px;
  padding: 0 20px;
  border: 0;
  border-radius: 14px;
  color: var(--chalk);
  background: var(--ink);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
  font-size: 13px;
  font-weight: 900;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.submit-button:hover:not(:disabled) {
  background: var(--midnight);
  box-shadow: 0 20px 40px rgba(7, 17, 38, 0.28);
  transform: translateY(-2px);
}

.submit-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* =========================================================
   LOGIN
========================================================= */
.login-line {
  margin: 18px 0 0;
  color: var(--slate);
  text-align: center;
  font-size: 13px;
}

.register-guest-link {
  width: fit-content;
  margin: 12px auto 0;
  color: var(--slate);
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.register-guest-link:hover { color: var(--signal); }
.register-guest-link:focus-visible { outline: 2px solid var(--signal); outline-offset: 4px; }

.login-line a {
  margin-left: 3px;
  color: var(--signal);
  font-weight: 900;
  text-decoration: none;
}

.login-line button {
  margin-left: 3px;
  padding: 0;
  border: 0;
  color: var(--signal);
  background: transparent;
  font-weight: 900;
}

.login-line a:hover,
.login-line button:hover {
  color: var(--signal-deep);
  text-decoration: underline;
}

.password-visibility:focus-visible,
.login-line button:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
  .form-field input,
  .submit-button,
  .password-visibility {
    transition: none !important;
  }
}

/* =========================================================
   ANIMATION
========================================================= */
.spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* =========================================================
   TABLET
========================================================= */
@media (max-width: 991.98px) {
  .register-hero {
    padding-top: 50px;
  }

  .form-grid.three-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .form-grid.three-columns .form-field:last-child {
    grid-column: 1 / -1;
  }
}

/* =========================================================
   MOBILE
========================================================= */
@media (max-width: 767.98px) {
  .learner-register-page {
    padding-bottom: 46px;
  }

  .register-hero {
    min-height: auto;
    padding: 38px 16px 95px;
  }

  .hero-breadcrumb {
    min-height: 44px;
    margin-bottom: 18px;
    padding: 7px 15px;
    gap: 7px;
  }

  .hero-breadcrumb a {
    font-size: 12px;
  }

  .hero-breadcrumb i {
    font-size: 17px;
  }

  .hero-copy h1 {
    margin-top: 17px;
    font-size: 36px;
    letter-spacing: -0.8px;
  }

  .hero-copy > p {
    font-size: 14px;
    line-height: 1.65;
  }

  .hero-points {
    align-items: stretch;
    flex-direction: column;
  }

  .hero-points span {
    min-height: 43px;
    justify-content: center;
  }

  .register-shell {
    width: calc(100% - 20px);
    margin-top: -48px;
  }

  .register-card {
    padding: 18px;
    border-radius: 19px;
  }

  .card-header {
    align-items: flex-start;
  }

  .header-icon {
    width: 46px;
    height: 46px;
    flex-basis: 46px;
    font-size: 21px;
  }

  .card-header h2 {
    font-size: 20px;
  }

  .form-section {
    padding: 21px 0;
  }

  .form-grid,
  .form-grid.three-columns {
    grid-template-columns: 1fr;
  }

  .form-grid.three-columns .form-field:last-child,
  .form-field-full {
    grid-column: auto;
  }
}

/* =========================================================
   SMALL MOBILE
========================================================= */
@media (max-width: 480px) {
  .register-hero {
    padding-right: 12px;
    padding-left: 12px;
  }

  .hero-copy h1 {
    font-size: 31px;
  }

  .hero-badge {
    font-size: 11px;
  }

  .hero-breadcrumb {
    min-height: 42px;
    padding: 6px 14px;
  }

  .register-shell {
    width: calc(100% - 16px);
  }

  .register-card {
    padding: 16px;
  }

  .card-header {
    gap: 11px;
  }

  .card-header p {
    font-size: 12px;
  }

  .terms-row {
    padding: 12px;
  }
}

.learner-register-embedded {
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: 0;
  color: var(--ink);
  display: flex;
  align-items: stretch;
}

.learner-register-embedded .register-shell {
  width: min(100%, 620px);
  height: 100%;
  margin: 0 auto;
  display: flex;
  min-height: 0;
}

.learner-register-embedded .register-card {
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: clamp(14px, 2.4vh, 22px) clamp(14px, 2vw, 28px);
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.learner-register-embedded .auth-mode-toggle {
  width: fit-content;
  margin: 0 0 clamp(16px, 2.2vh, 22px);
  padding: 4px;
  border: 1px solid var(--sand);
  border-radius: 999px;
  background: var(--chalk);
  display: flex;
  gap: 8px;
}

.learner-register-embedded .auth-mode-toggle button,
.auth-mode-toggle button,
.auth-mode-toggle a {
  min-width: 104px;
  min-height: 40px;
  padding: 8px 17px;
  border: 0;
  border-radius: 999px;
  color: var(--slate);
  background: transparent;
  font: inherit;
  font-size: 13px;
  font-weight: 750;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  transition: color 180ms ease, background 180ms ease, transform 220ms cubic-bezier(.2,.75,.25,1);
}

.learner-register-embedded .auth-mode-toggle button:active:not(:disabled) { transform: scale(.96); }

.learner-register-embedded .auth-mode-toggle button[aria-pressed="true"],
.learner-register-embedded .auth-mode-toggle button.is-active,
.auth-mode-toggle button.is-active,
.auth-mode-toggle button[aria-pressed="true"] {
  color: var(--chalk);
  background: var(--ink);
  animation: auth-mode-selected 280ms cubic-bezier(.2,.75,.25,1) both;
}

@keyframes auth-mode-selected {
  0% { transform: scale(.94); }
  72% { transform: scale(1.035); }
  100% { transform: scale(1); }
}

.learner-register-embedded .auth-mode-toggle button:focus-visible,
.auth-mode-toggle button:focus-visible,
.auth-mode-toggle a:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

.learner-register-embedded .card-header {
  flex: 0 0 auto;
  padding: 0 0 10px;
  border: 0;
  background: transparent;
}

.auth-register-heading { flex: 0 0 auto; margin-bottom: 13px; }
.auth-register-heading h1 {
  margin: 0 0 5px;
  color: var(--ink);
  font-size: clamp(28px, 3vw, 39px);
  line-height: 1.08;
  font-weight: 900;
  letter-spacing: -0.04em;
}
.auth-register-heading p { margin: 0; color: var(--slate); font-size: 13px; line-height: 1.4; }

.step-progress {
  flex: 0 0 auto;
  margin: 0 0 10px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 750;
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 12px;
}

.step-progress-track {
  height: 3px;
  overflow: hidden;
  background: var(--sand);
}

.step-progress-track i {
  height: 100%;
  display: block;
  background: var(--signal);
  transition: width 220ms var(--ease-out);
}

.register-step {
  min-width: 0;
  margin: 0;
  padding: 0;
  border: 0;
}

.learner-register-embedded .register-step {
  flex: 1 1 auto;
  min-height: 0;
  min-inline-size: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  padding-top: clamp(8px, 1.5vh, 16px);
  overflow: hidden;
}

.learner-register-embedded .form-section {
  width: 100%;
  padding: 0;
  border: 0;
}

.learner-register-embedded .form-grid { gap: 12px 16px; }
.learner-register-embedded .form-field > span,
.learner-register-embedded .field-label { margin-bottom: 4px; }
.learner-register-embedded .form-field input { min-height: 42px; }
.learner-register-embedded .password-visibility { height: 36px; }
.learner-register-embedded .password-guidance { margin-top: 4px; gap: 2px; }
.learner-register-embedded .terms-row { margin-top: 8px; padding: 8px 10px; font-size: 11px; line-height: 1.4; }

.step-actions {
  flex: 0 0 auto;
  min-height: 46px;
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.step-actions-count { color: var(--stone); font-size: 11px; font-weight: 800; }
.step-next, .step-back {
  min-height: 44px;
  padding: 0 18px;
  border: 0;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 850;
  cursor: pointer;
}
.step-next {
  min-width: 150px;
  margin-left: auto;
  color: var(--chalk);
  background: var(--signal);
}
.step-next:hover:not(:disabled) { background: var(--signal-deep); }
.step-next:disabled { opacity: .65; cursor: wait; }
.step-back { color: var(--ink); background: transparent; }
.step-back:hover { background: var(--sand); }
.step-next:focus-visible, .step-back:focus-visible { outline: 2px solid var(--signal); outline-offset: 3px; }

.learner-register-embedded .form-field input:not([type='file']) {
  border: 1px solid var(--sand);
  border-radius: 4px;
  background: var(--chalk);
}
.learner-register-embedded .form-field input:focus:not([type='file']) {
  border-color: var(--signal);
  background: var(--chalk);
  box-shadow: 0 0 0 2px rgba(13, 143, 233, 0.12);
}
.learner-register-embedded .form-field input::placeholder {
  color: var(--stone);
  opacity: 1;
}

@media (max-width: 991.98px) {
  .learner-register-embedded {
    height: 100%;
    min-height: 0;
    padding: 0;
    align-items: stretch;
  }

  .learner-register-embedded .register-shell { width: 100%; height: 100%; }
  .learner-register-embedded .auth-mode-toggle { margin-bottom: 12px; }
  .learner-register-embedded .auth-register-heading { margin-bottom: 6px; }
  .learner-register-embedded .auth-register-heading h1 { font-size: 25px; }
  .learner-register-embedded .card-header { padding-bottom: 6px; }
  .learner-register-embedded .header-icon { width: 38px; height: 38px; flex-basis: 38px; font-size: 18px; }
  .learner-register-embedded .card-header h2 { font-size: 17px; }
  .learner-register-embedded .card-header p { font-size: 11px; }
  .learner-register-embedded .form-section { padding: 2px 0; }
  .learner-register-embedded .form-grid { gap: 8px 12px; }
  .learner-register-embedded .form-field input { min-height: 38px; }
  .learner-register-embedded .terms-row { margin-top: 4px; padding: 6px 8px; font-size: 10px; }
  .learner-register-embedded .step-progress { margin-bottom: 6px; }
  .step-actions { min-height: 42px; margin-top: 4px; }
  .step-next, .step-back { min-height: 40px; }
}

@media (prefers-reduced-motion: reduce) {
  .learner-register-embedded .auth-mode-toggle button,
  .auth-mode-toggle button,
  .auth-mode-toggle a { transition-duration: 1ms; }
  .auth-mode-toggle button[aria-pressed="true"],
  .auth-mode-toggle button.is-active { animation-duration: 1ms; }
}
</style>
