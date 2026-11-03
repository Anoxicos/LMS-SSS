<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { onUnmounted, ref } from 'vue'
import authLogo from '@assets/images/brand/safesky-auth.svg'

import AuthParticles from '@/Components/AuthParticles.vue'
import Checkbox from '@/Components/Checkbox.vue'
import InputError from '@/Components/InputError.vue'
import LearnerRegister from '@/Pages/Public/Auth/LearnerRegister.vue'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const togglePassword = ref(false)
const startsInRegister = typeof window !== 'undefined'
  && new URLSearchParams(window.location.search).get('mode') === 'register'
const showRegister = ref(startsInRegister)
const activeMode = ref(startsInRegister ? 'register' : 'login')
const curtainStage = ref('')
const authPanelRef = ref(null)
const curtainTimers = []

const scheduleCurtain = (callback, delay) => {
  curtainTimers.push(window.setTimeout(callback, delay))
}

const switchMode = mode => {
  const nextMode = mode === 'register'
  if (curtainStage.value || showRegister.value === nextMode) return

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  activeMode.value = mode
  if (reduceMotion) {
    showRegister.value = nextMode
  } else {
    curtainStage.value = 'closing'
    scheduleCurtain(() => {
      showRegister.value = nextMode
      scheduleCurtain(() => {
        curtainStage.value = 'opening'
        scheduleCurtain(() => { curtainStage.value = '' }, 360)
      }, 340)
    }, 360)
  }

  window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' })
  if (authPanelRef.value) authPanelRef.value.scrollTop = 0
}

onUnmounted(() => curtainTimers.forEach(window.clearTimeout))

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    })
}
</script>

<template>
  <div class="lms-auth-page" :class="{ 'is-register': showRegister }">
    <Head :title="showRegister ? $t('t-create-learner-account') : $t('t-login')" />
    <AuthParticles layer="backdrop" color="31, 92, 255" :quantity="110" :opacity="0.32" :size="2.4" />
    <BContainer fluid class="auth-container">
      <BRow class="auth-row align-items-center justify-content-center">
        <BCol xxl="10" xl="11" lg="12" class="auth-column">
          <BCard no-body class="auth-card border-0">
            <BRow class="g-0">
              <!-- Left Side -->
              <BCol lg="6" class="auth-left">
                <div class="auth-left-pattern"></div>
                <AuthParticles />

                <div class="auth-left-content">
                  <Link
                    href="/"
                    class="brand-box"
                    :aria-label="$t('t-safesky-home')"
                  >
                    <span class="brand-logo-shell">
                      <img
                        :src="authLogo"
                        :alt="$t('t-safesky-home')"
                        class="brand-logo"
                      />
                    </span>
                  </Link>

                  <div class="left-hero">
                    <span class="secure-pill">
                      <i class="ri-shield-check-line"></i>
                      {{ $t('t-secure-aviation-platform') }}
                    </span>

                    <h1>
                      {{ $t('t-auth-advance') }} <span class="auth-title-accent">{{ $t('t-aviation') }}</span> {{ $t('t-auth-knowledge') }}
                    </h1>

                    <p>
                      {{ $t('t-auth-platform-description') }}
                    </p>
                  </div>

                  <div class="feature-list">
                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-book-open-line"></i>
                      </div>

                      <div>
                        <strong>{{ $t('t-aviation-courses') }}</strong>
                        <span>{{ $t('t-structured-learning-content') }}</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-mic-line"></i>
                      </div>

                      <div>
                        <strong>{{ $t('t-voice-guidance') }}</strong>
                        <span>{{ $t('t-assisted-course-narration') }}</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-award-line"></i>
                      </div>

                      <div>
                        <strong>{{ $t('t-certificates') }}</strong>
                        <span>{{ $t('t-verified-completion-records') }}</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-building-4-line"></i>
                      </div>

                      <div>
                        <strong>{{ $t('t-company-training') }}</strong>
                        <span>{{ $t('t-managed-corporate-learning') }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="left-footer-text">
                    <i class="ri-user-star-line"></i>
                    <span>{{ $t('t-trusted-aviation-learning-space') }}</span>
                  </div>
                </div>
              </BCol>

              <!-- Right Side -->
              <BCol lg="6" class="auth-right">
                <div ref="authPanelRef" class="auth-form-wrapper">
                  <Transition :name="showRegister ? 'auth-mode-forward' : 'auth-mode-back'" mode="out-in">
                  <div v-if="!showRegister" key="login" class="auth-login-view">
                  <Link
                    href="/"
                    class="mobile-brand"
                    :aria-label="$t('t-safesky-home')"
                  >
                    <span class="mobile-brand-logo-shell">
                      <img
                        :src="authLogo"
                        :alt="$t('t-safesky-home')"
                        class="mobile-brand-logo"
                      />
                    </span>
                  </Link>

                  <div class="login-head">
                    <div class="auth-mode-toggle" role="group" :aria-label="$t('t-choose-signin-mode')">
                      <button type="button" :aria-pressed="activeMode === 'login'" :class="{ 'is-active': activeMode === 'login' }" @click="switchMode('login')">{{ $t('t-sign-in') }}</button>
                      <button type="button" :aria-pressed="activeMode === 'register'" :class="{ 'is-active': activeMode === 'register' }" @click="switchMode('register')">{{ $t('t-sign-up') }}</button>
                    </div>

                    <h2>{{ $t('t-welcome-back') }}</h2>

                    <p>
                      {{ $t('t-signin-to-continue-learning') }}
                    </p>
                  </div>

                  <div
                    v-if="status"
                    class="alert auth-status-alert"
                  >
                    <i class="ri-checkbox-circle-line"></i>
                    {{ status }}
                  </div>

                  <form class="auth-form" @submit.prevent="submit">
                    <div class="mb-3">
                      <label for="email" class="form-label">
                        {{ $t('t-email-address') }}
                      </label>

                      <div class="input-shell">
                        <i class="ri-mail-line input-icon"></i>

                        <input
                          id="email"
                          v-model="form.email"
                          type="email"
                          class="form-control"
                          :placeholder="$t('t-enter-your-email')"
                          autocomplete="email"
                          required
                          autofocus
                          :class="{ 'is-invalid': form.errors.email }"
                        />
                      </div>

                      <InputError :message="form.errors.email" />
                    </div>

                    <div class="mb-3">
                      <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="password-input">
                          {{ $t('t-password') }}
                        </label>

                        <Link
                          v-if="canResetPassword"
                          :href="route('password.request')"
                          class="forgot-link"
                        >
                          {{ $t('t-forgot-password-question') }}
                        </Link>
                      </div>

                      <div class="input-shell mt-2">
                        <i class="ri-lock-2-line input-icon"></i>

                        <input
                          id="password-input"
                          v-model="form.password"
                          :type="togglePassword ? 'text' : 'password'"
                          class="form-control"
                          :placeholder="$t('t-enter-your-password')"
                          autocomplete="current-password"
                          required
                          :class="{ 'is-invalid': form.errors.password }"
                        />

                        <BButton
                          variant="link"
                          class="password-toggle"
                          type="button"
                          @click="togglePassword = !togglePassword"
                        >
                          <i
                            :class="togglePassword ? 'ri-eye-off-line' : 'ri-eye-line'"
                          ></i>
                        </BButton>
                      </div>

                      <InputError :message="form.errors.password" />
                    </div>

                    <div class="auth-options">
                      <div class="form-check">
                        <Checkbox
                          id="auth-remember-check"
                          v-model:checked="form.remember"
                          name="remember"
                          class="form-check-input"
                        />

                        <label
                          class="form-check-label"
                          for="auth-remember-check"
                        >
                          {{ $t('t-remember-me') }}
                        </label>
                      </div>
                    </div>

                    <BButton
                      variant="primary"
                      class="login-btn w-100"
                      type="submit"
                      :disabled="form.processing"
                    >
                      <span
                        v-if="form.processing"
                        class="spinner-border spinner-border-sm me-2"
                      ></span>

                      <i v-else class="ri-login-box-line me-1"></i>
                      {{ $t('t-sign-in') }}
                    </BButton>
                  </form>
                  <Link href="/courses?arrival=1" class="continue-guest-link">
                    {{ $t('t-continue-as-guest') }} <i class="ri-arrow-right-line" aria-hidden="true"></i>
                  </Link>
                  </div>
                  <LearnerRegister
                    v-else
                    key="register"
                    :login-url="route('login')"
                    :courses-url="route('courses')"
                    :embedded="true"
                    :active-mode="activeMode"
                    @switch="switchMode"
                  />
                  </Transition>
                </div>
              </BCol>
            </BRow>
            <div
              class="auth-curtain"
              :class="curtainStage && `is-${curtainStage}`"
              aria-hidden="true"
            >
              <span class="auth-curtain-panel auth-curtain-panel-left"></span>
              <span class="auth-curtain-panel auth-curtain-panel-right"></span>
            </div>
          </BCard>

          <div class="auth-copyright">
            <p>
              &copy; {{ new Date().getFullYear() }} SAFESKY SOLUTION.
            </p>
          </div>
        </BCol>
      </BRow>
    </BContainer>
  </div>
</template>


<style scoped>
.lms-auth-page {
  width: 100%;
  min-height: 100dvh;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  background:
    radial-gradient(ellipse at 52% 42%, rgba(56, 225, 255, 0.1), transparent 54%),
    var(--paper);
}

.auth-container {
  position: relative;
  z-index: 1;
}

.auth-card {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--sand) !important;
  border-radius: 4px;
  background: var(--chalk);
  box-shadow: 0 30px 80px rgba(7, 11, 20, 0.35);
}

/* =========================================================
   LEFT PANEL
   ========================================================= */
.auth-left {
  position: relative;
  overflow: hidden;
  color: var(--chalk);
  background:
    radial-gradient(ellipse at 78% 20%, rgba(56, 225, 255, 0.18), transparent 42%),
    var(--ink);
}

.auth-left::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(90deg, rgba(255, 255, 255, 0.055) 1px, transparent 1px),
    linear-gradient(rgba(255, 255, 255, 0.055) 1px, transparent 1px);
  background-size: 52px 52px;
  opacity: 0.34;
  mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.42), transparent 78%);
}

.auth-left::after {
  /* No decorative circle: use a restrained linear accent instead. */
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 4px;
  pointer-events: none;
  background: linear-gradient(90deg, var(--jet), transparent);
}

.auth-left-pattern {
  display: none;
}

.auth-left-content {
  position: relative;
  z-index: 2;
  height: 100%;
  display: grid;
  grid-template-rows: auto minmax(min-content, 1fr) auto auto;
  align-items: start;
  gap: clamp(12px, 1.8vh, 20px);
}

.brand-box,
.mobile-brand {
  width: fit-content;
  text-decoration: none;
}

.brand-logo-shell,
.mobile-brand-logo-shell {
  position: relative;
  overflow: hidden;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* White wordmark on the Ink panel. */
.brand-logo-shell {
  width: 180px;
  height: 66px;
  border-radius: 4px;
  background: transparent;
}

.brand-logo {
  display: block;
  width: 100%;
  height: 100%;
  max-width: none;
  max-height: none;
  object-fit: contain;
  object-position: center;
  transform: scale(1.16);
  transform-origin: center;
  filter: drop-shadow(0 4px 10px rgba(7, 17, 38, 0.12));
}

/* Dark logo variant used on Paper. */
.mobile-brand-logo-shell {
  width: 176px;
  height: 66px;
  padding: 8px 16px;
  border: 1px solid var(--sand);
  border-radius: 4px;
  background: var(--chalk);
}

.mobile-brand-logo {
  display: block;
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  object-position: center;
  transform: none;
}

.left-hero {
  align-self: center;
  margin: 0;
}

.secure-pill {
  width: fit-content;
  min-height: 32px;
  margin-bottom: 17px;
  padding: 6px 12px;
  border: 1px solid var(--midnight);
  border-radius: 999px;
  color: var(--mist);
  background: var(--night);
  font-size: 11.5px;
  font-weight: 850;
  letter-spacing: 0.01em;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.secure-pill i {
  color: var(--jet);
  font-size: 14px;
}

.left-hero h1 {
  max-width: 620px;
  margin: 0 0 16px;
  color: var(--chalk);
  font-size: clamp(34px, 3.6vw, 50px);
  line-height: 1.02;
  font-weight: 950;
  letter-spacing: -0.045em;
}

.auth-title-accent {
  color: var(--jet);
}

.left-hero p {
  max-width: 590px;
  margin: 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 14px;
  line-height: 1.72;
  font-weight: 560;
}

.feature-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.feature-item {
  min-width: 0;
  min-height: 64px;
  padding: 11px 12px;
  border: 1px solid var(--midnight);
  border-radius: 4px;
  background: var(--night);
  display: flex;
  align-items: center;
  gap: 11px;
  transition: border-color 0.18s ease, background 0.18s ease;
}

.feature-item:hover {
  border-color: var(--jet);
  background: var(--midnight);
}

.feature-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 11px;
  color: var(--ink);
  background: var(--jet);
  font-size: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.feature-item strong {
  display: block;
  margin-bottom: 3px;
  color: var(--chalk);
  font-size: 12.5px;
  line-height: 1.25;
  font-weight: 900;
}

.feature-item span {
  display: block;
  color: rgba(255, 255, 255, 0.76);
  font-size: 10.5px;
  line-height: 1.35;
  font-weight: 700;
}

.left-footer-text {
  margin-top: 16px;
  color: rgba(255, 255, 255, 0.86);
  font-size: 11.5px;
  line-height: 1.4;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 9px;
}

.left-footer-text i {
  width: 30px;
  height: 30px;
  min-width: 30px;
  border-radius: 9px;
  color: var(--ink);
  background: var(--jet);
  font-size: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* =========================================================
   RIGHT PANEL
   ========================================================= */
.auth-right {
  position: relative;
  background: var(--paper);
}

.lms-auth-page.is-register .auth-left { order: 2; }
.lms-auth-page.is-register .auth-right { order: 1; }
.lms-auth-page.is-register .auth-right::before { right: 0; left: auto; }

.auth-login-view { width: 100%; }

.lms-auth-page.is-register .auth-form-wrapper {
  max-width: none;
  height: 100%;
  padding: 0;
  display: block;
  overflow: hidden;
}

.auth-curtain {
  position: absolute;
  z-index: 5;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.auth-curtain.is-closing,
.auth-curtain.is-opening { pointer-events: auto; }

.auth-curtain-panel {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 51%;
  background: var(--ink);
  transition: transform 360ms cubic-bezier(0.7, 0, 0.3, 1);
}

.auth-curtain-panel-left {
  left: 0;
  border-right: 1px solid rgba(56, 225, 255, 0.48);
  transform: translateX(-100%);
}

.auth-curtain-panel-right {
  right: 0;
  border-left: 1px solid rgba(56, 225, 255, 0.48);
  transform: translateX(100%);
}

.auth-curtain.is-closing .auth-curtain-panel { transform: translateX(0); }

.auth-right::before {
  content: '';
  position: absolute;
  top: 8%;
  bottom: 8%;
  left: 0;
  width: 1px;
  background: linear-gradient(180deg, transparent, var(--sand) 18%, var(--sand) 82%, transparent);
}

.auth-form-wrapper {
  width: 100%;
  max-width: 500px;
  height: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.continue-guest-link {
  width: fit-content;
  margin: 18px auto 0;
  color: var(--slate);
  font-size: 13px;
  font-weight: 750;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: color 0.2s ease;
}

.continue-guest-link:hover {
  color: var(--signal);
}

.continue-guest-link:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 4px;
}

.mobile-brand {
  display: none;
  margin-bottom: 25px;
}


.login-head {
  margin-bottom: 27px;
}

.login-head h2 {
  margin: 0 0 8px;
  color: var(--ink);
  font-size: clamp(32px, 3vw, 39px);
  line-height: 1.05;
  font-weight: 950;
  letter-spacing: -0.04em;
}

.login-head p {
  margin: 0;
  color: var(--slate);
  font-size: 15px;
  line-height: 1.65;
}

.auth-status-alert {
  padding: 11px 13px;
  border: 1px solid rgba(25, 195, 154, 0.3);
  border-radius: 4px;
  color: var(--success-deep);
  background: rgba(25, 195, 154, 0.08);
  font-size: 12px;
  font-weight: 850;
  display: flex;
  align-items: center;
  gap: 8px;
}

.auth-form .form-label {
  margin-bottom: 7px;
  color: var(--slate);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.input-shell {
  position: relative;
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 15px;
  z-index: 2;
  transform: translateY(-50%);
  color: var(--slate);
  font-size: 17px;
  pointer-events: none;
}

.input-shell .form-control {
  min-height: 52px;
  padding: 0 46px;
  border: 0;
  border-bottom: 1px solid var(--sand);
  border-radius: 0;
  color: var(--ink);
  background: transparent;
  font-size: 14px;
  font-weight: 750;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.input-shell .form-control::placeholder {
  color: var(--slate);
}

.input-shell .form-control:hover {
  border-color: var(--signal);
  background: var(--chalk);
}

.input-shell .form-control:focus {
  border-color: var(--signal);
  background: var(--chalk);
  box-shadow: 0 2px 0 var(--signal);
}

.input-shell .form-control.is-invalid {
  border-color: var(--danger);
}

.password-toggle {
  position: absolute !important;
  top: 50%;
  right: 6px;
  z-index: 3;
  transform: translateY(-50%);
  padding: 8px 10px !important;
  color: var(--slate) !important;
  text-decoration: none !important;
}

.password-toggle:hover {
  color: var(--signal-deep) !important;
}

.forgot-link {
  color: var(--signal-deep);
  font-size: 12px;
  font-weight: 900;
  text-decoration: none;
}

.forgot-link:hover {
  color: var(--ink);
}

.auth-options {
  margin: 16px 0 20px;
}

.form-check-label {
  color: var(--slate);
  font-size: 13px;
  font-weight: 800;
}

.form-check-input {
  cursor: pointer;
}

.login-btn {
  min-height: 52px;
  border: 0;
  border-radius: 999px;
  color: var(--chalk);
  background: var(--signal);
  box-shadow: none;
  font-size: 13.5px;
  font-weight: 900;
  letter-spacing: 0.005em;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.login-btn:hover {
  color: var(--chalk);
  transform: translateY(-1px);
  background: var(--signal-deep);
  box-shadow: none;
}

.login-btn:disabled {
  opacity: 0.68;
  transform: none;
}

.auth-copyright {
  flex: 0 0 auto;
  margin-top: 7px;
  text-align: center;
}

.auth-copyright p {
  margin: 0;
  color: var(--slate);
  font-size: 10.5px;
  line-height: 16px;
  font-weight: 750;
}

/* =========================================================
   STRICT DESKTOP 100VH
   ========================================================= */
@media (min-width: 992px) {
  .lms-auth-page {
    height: 100dvh;
    min-height: 100dvh;
    max-height: 100dvh;
    overflow: hidden;
  }

  .auth-container {
    width: 100%;
    height: 100dvh;
    max-height: 100dvh;
    padding: 14px 24px 9px;
    display: flex;
    align-items: center;
  }

  .auth-row {
    width: 100%;
    height: 100%;
    min-height: 0;
    margin: 0;
  }

  .auth-column {
    height: 100%;
    min-height: 0;
    padding-right: 0;
    padding-left: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .auth-card {
    width: min(1240px, 100%);
    height: min(820px, calc(100dvh - 58px));
    min-height: 0;
    margin-inline: auto;
    flex: 0 1 auto;
  }

  .auth-card > .row {
    height: 100%;
    min-height: 0;
  }

  .auth-left,
  .auth-right {
    height: 100%;
    min-height: 0;
  }

  .lms-auth-page.is-register .auth-form-wrapper { padding: 0; }

  .auth-left {
    flex: 0 0 47%;
    max-width: 47%;
  }

  .auth-right {
    flex: 0 0 53%;
    max-width: 53%;
  }

  .auth-left-content {
    padding: clamp(28px, 3vw, 42px);
    gap: clamp(14px, 2.2vh, 24px);
  }

  .auth-form-wrapper {
    padding: clamp(34px, 4vw, 58px);
  }

  .auth-form .mb-3 {
    margin-bottom: 14px !important;
  }
}

/* Short desktop screens and browser zoom. */
@media (min-width: 992px) and (max-height: 780px) {
  .auth-card {
    height: calc(100dvh - 54px);
  }

  .auth-left-content {
    padding: 24px 30px 22px;
    gap: 13px;
  }

  .brand-logo-shell {
    width: 158px;
    height: 56px;
  }

  .brand-logo {
    transform: scale(1.14);
  }

  .secure-pill {
    min-height: 28px;
    margin-bottom: 11px;
    padding: 5px 10px;
    font-size: 9.5px;
  }

  .left-hero h1 {
    margin-bottom: 10px;
    font-size: clamp(29px, 2.75vw, 39px);
  }

  .left-hero p {
    font-size: 12px;
    line-height: 1.55;
  }

  .feature-list {
    gap: 8px;
  }

  .feature-item {
    min-height: 54px;
    padding: 9px 10px;
    border-radius: 4px;
  }

  .feature-icon {
    width: 33px;
    height: 33px;
    min-width: 33px;
    border-radius: 10px;
    font-size: 16px;
  }

  .feature-item strong {
    font-size: 10.5px;
  }

  .feature-item span {
    font-size: 8.7px;
  }

  .left-footer-text {
    margin-top: 10px;
    font-size: 9.5px;
  }

  .left-footer-text i {
    width: 27px;
    height: 27px;
    min-width: 27px;
    font-size: 14px;
  }

  .auth-form-wrapper {
    max-width: 470px;
    padding: 28px 38px;
  }

  .login-head {
    margin-bottom: 21px;
  }

  .login-head h2 {
    font-size: 31px;
  }

  .login-head p {
    font-size: 12.5px;
  }

  .input-shell .form-control,
  .login-btn {
    min-height: 48px;
  }

  .auth-options {
    margin: 12px 0 16px;
  }
}

/* Very short desktop screens. */
@media (min-width: 992px) and (max-height: 660px) {
  .feature-list {
    display: none;
  }

  .auth-left-content {
    grid-template-rows: auto 1fr auto;
  }

  .left-footer-text {
    margin-top: 0;
  }

  .auth-card {
    height: calc(100dvh - 48px);
  }
}

/* =========================================================
   TABLET / MOBILE
   ========================================================= */
@media (max-width: 991.98px) {
  .lms-auth-page {
    min-height: 100dvh;
    overflow: auto;
  }

  .auth-container {
    padding: 26px 18px;
  }

  .auth-row {
    min-height: calc(100dvh - 52px);
  }

  .auth-left {
    display: none;
  }

  .lms-auth-page.is-register .auth-right { order: 1; }

  .auth-card {
    width: min(560px, 100%);
    margin: 0 auto;
    border-radius: 4px;
  }

  .auth-right::before {
    display: none;
  }

  .auth-form-wrapper {
    min-height: auto;
    padding: 42px 34px;
  }

  .lms-auth-page.is-register .auth-form-wrapper {
    height: 100%;
    min-height: 0;
    padding: 8px 18px;
    overflow: hidden;
  }

  .lms-auth-page.is-register {
    height: 100dvh;
    min-height: 100dvh;
    max-height: 100dvh;
    overflow: hidden;
  }

  .lms-auth-page.is-register .auth-container {
    height: 100%;
    min-height: 0;
    padding: 12px;
    display: flex;
    align-items: stretch;
  }

  .lms-auth-page.is-register .auth-row,
  .lms-auth-page.is-register .auth-column,
  .lms-auth-page.is-register .auth-card,
  .lms-auth-page.is-register .auth-card > .row,
  .lms-auth-page.is-register .auth-right {
    height: 100%;
    min-height: 0;
  }

  .lms-auth-page.is-register .auth-row { width: 100%; }

  .lms-auth-page.is-register .auth-column {
    padding: 0;
    display: flex;
    flex-direction: column;
  }

  .lms-auth-page.is-register .auth-card {
    width: 100%;
    max-height: 100%;
    margin: 0;
  }

  .lms-auth-page.is-register .auth-copyright { display: none; }

  .mobile-brand {
    display: inline-flex;
  }
}

@media (max-width: 575.98px) {
  .auth-container {
    padding: 16px 11px;
  }

  .auth-card {
    border-radius: 4px;
  }

  .auth-form-wrapper {
    padding: 28px 20px;
  }

  .mobile-brand {
    margin-bottom: 21px;
  }

  .mobile-brand-logo-shell {
    width: 158px;
    height: 60px;
    padding: 7px 14px;
    border-radius: 4px;
  }

  .mobile-brand-logo {
    width: 100%;
    height: 100%;
    transform: none;
  }

  .login-head {
    margin-bottom: 23px;
  }

  .login-head h2 {
    font-size: 29px;
  }

  .login-head p {
    font-size: 12.5px;
  }

  .input-shell .form-control,
  .login-btn {
    min-height: 48px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .feature-item,
  .login-btn,
  .input-shell .form-control {
    transition: none !important;
  }

  .login-btn:hover {
    transform: none !important;
  }
}

:global(.auth-mode-toggle) {
  display: inline-flex;
  gap: 4px;
  margin: 0 0 22px;
  padding: 4px;
  border: 1px solid var(--sand);
  border-radius: 999px;
  background: var(--chalk);
}

:global(.auth-mode-toggle button),
:global(.auth-mode-toggle a) {
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
  transition: color 180ms ease, background 180ms ease, transform 180ms ease;
}

:global(.auth-mode-toggle button:hover),
:global(.auth-mode-toggle a:hover) {
  color: var(--ink);
}

:global(.auth-mode-toggle button:active),
:global(.auth-mode-toggle a:active) {
  transform: scale(0.98);
}

:global(.auth-mode-toggle button.is-active),
:global(.auth-mode-toggle button[aria-pressed="true"]) {
  color: var(--chalk);
  background: var(--ink);
  animation: auth-mode-selected 280ms cubic-bezier(.2,.75,.25,1) both;
}

@keyframes auth-mode-selected {
  0% { transform: scale(.94); }
  72% { transform: scale(1.035); }
  100% { transform: scale(1); }
}

:global(.auth-mode-toggle button:focus-visible),
:global(.auth-mode-toggle a:focus-visible) {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

.auth-left .brand-box:focus-visible {
  outline: 2px solid var(--jet);
  outline-offset: 3px;
}

.auth-right a:focus-visible,
.auth-right button:focus-visible,
.auth-right input:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

:global(.auth-mode-forward-enter-active),
:global(.auth-mode-forward-leave-active),
:global(.auth-mode-back-enter-active),
:global(.auth-mode-back-leave-active) {
  transition: opacity 220ms ease, transform 320ms var(--ease-out);
}

:global(.auth-mode-forward-enter-from) {
  opacity: 0;
  transform: translateX(32px);
}

:global(.auth-mode-forward-leave-to) {
  opacity: 0;
  transform: translateX(-32px);
}

:global(.auth-mode-back-enter-from) {
  opacity: 0;
  transform: translateX(-32px);
}

:global(.auth-mode-back-leave-to) {
  opacity: 0;
  transform: translateX(32px);
}

@media (prefers-reduced-motion: reduce) {
  :global(.auth-mode-forward-enter-active),
  :global(.auth-mode-forward-leave-active),
  :global(.auth-mode-back-enter-active),
  :global(.auth-mode-back-leave-active) {
    transition-duration: 1ms;
  }

  :global(.auth-mode-toggle button),
  :global(.auth-mode-toggle a) {
    transition-duration: 1ms;
  }

  :global(.auth-mode-toggle button.is-active),
  :global(.auth-mode-toggle button[aria-pressed="true"]) {
    animation-duration: 1ms;
  }

  .auth-curtain { display: none; }
  .auth-curtain-panel { transition-duration: 1ms; }
}
</style>
