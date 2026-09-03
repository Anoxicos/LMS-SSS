<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import haliAirLogoLight from '@assets/images/logo-blanche-.png'
import haliAirLogoDark from '@assets/images/logo_safesky blue.webp'

import Checkbox from '@/Components/Checkbox.vue'
import InputError from '@/Components/InputError.vue'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const togglePassword = ref(false)

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
  <Head title="Login" />

  <div class="lms-auth-page">
    <BContainer fluid class="auth-container">
      <BRow class="auth-row align-items-center justify-content-center">
        <BCol xxl="10" xl="11" lg="12" class="auth-column">
          <BCard no-body class="auth-card border-0">
            <BRow class="g-0">
              <!-- Left Side -->
              <BCol lg="6" class="auth-left">
                <div class="auth-left-pattern"></div>

                <div class="auth-left-content">
                  <Link
                    href="/"
                    class="brand-box"
                    aria-label="SAFESKY SOLUTION."
                  >
                    <span class="brand-logo-shell">
                      <img
                        :src="haliAirLogoLight"
                        alt="Hali Air"
                        class="brand-logo"
                      />
                    </span>
                  </Link>

                  <div class="left-hero">
                    <span class="secure-pill">
                      <i class="ri-shield-check-line"></i>
                      Secure aviation learning platform
                    </span>

                    <h1>
                      Advance aviation knowledge with structured online training.
                    </h1>

                    <p>
                      Access professional aviation courses, complete assessments, follow your progress and receive verified training certificates.
                    </p>
                  </div>

                  <div class="feature-list">
                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-book-open-line"></i>
                      </div>

                      <div>
                        <strong>Aviation Courses</strong>
                        <span>Structured learning content</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-mic-line"></i>
                      </div>

                      <div>
                        <strong>Voice Guidance</strong>
                        <span>Assisted course narration</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-award-line"></i>
                      </div>

                      <div>
                        <strong>Certificates</strong>
                        <span>Verified completion records</span>
                      </div>
                    </div>

                    <div class="feature-item">
                      <div class="feature-icon">
                        <i class="ri-building-4-line"></i>
                      </div>

                      <div>
                        <strong>Company Training</strong>
                        <span>Managed corporate learning</span>
                      </div>
                    </div>
                  </div>

                  <div class="left-footer-text">
                    <i class="ri-user-star-line"></i>
                    <span>Trusted learning space for aviation professionals.</span>
                  </div>
                </div>
              </BCol>

              <!-- Right Side -->
              <BCol lg="6" class="auth-right">
                <div class="auth-form-wrapper">
                  <Link
                    href="/"
                    class="mobile-brand"
                    aria-label="Hali Air home"
                  >
                    <span class="mobile-brand-logo-shell">
                      <img
                        :src="haliAirLogoDark"
                        alt="Hali Air"
                        class="mobile-brand-logo"
                      />
                    </span>
                  </Link>

                  <div class="login-head">
                    <span class="login-pill">
                      <i class="ri-login-circle-line"></i>
                     login
                    </span>

                    <h2>Welcome back</h2>

                    <p>
                      Sign in to continue your aviation learning journey.
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
                        Email address
                      </label>

                      <div class="input-shell">
                        <i class="ri-mail-line input-icon"></i>

                        <input
                          id="email"
                          v-model="form.email"
                          type="email"
                          class="form-control"
                          placeholder="Enter your email"
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
                          Password
                        </label>

                        <Link
                          v-if="canResetPassword"
                          :href="route('password.request')"
                          class="forgot-link"
                        >
                          Forgot password?
                        </Link>
                      </div>

                      <div class="input-shell mt-2">
                        <i class="ri-lock-2-line input-icon"></i>

                        <input
                          id="password-input"
                          v-model="form.password"
                          :type="togglePassword ? 'text' : 'password'"
                          class="form-control"
                          placeholder="Enter your password"
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
                          Remember me
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
                      Sign In
                    </BButton>
                  </form>
                </div>
              </BCol>
            </BRow>
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
  --auth-cyan: #37cced;
  --auth-blue: #0d8fe9;
  --auth-blue-dark: #087bc9;
  --auth-night: #071126;
  --auth-night-hover: #10233f;
  --auth-text: #172033;
  --auth-muted: #64748b;
  --auth-border: #dfe8f1;
  --auth-soft: #f5faff;
  --auth-success: #0ab39c;

  width: 100%;
  min-height: 100dvh;
  position: relative;
  overflow-x: hidden;
  /* Structured background: no decorative circles or floating bubbles. */
  background:
    linear-gradient(135deg, rgba(55, 204, 237, 0.08) 0%, transparent 34%),
    linear-gradient(315deg, rgba(13, 143, 233, 0.06) 0%, transparent 32%),
    linear-gradient(180deg, #f8fcff 0%, #ffffff 52%, #f3f8fc 100%);
}

.auth-container {
  position: relative;
  z-index: 1;
}

.auth-card {
  overflow: hidden;
  border: 1px solid rgba(223, 232, 241, 0.96) !important;
  border-radius: 30px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow:
    0 30px 80px rgba(7, 17, 38, 0.12),
    0 2px 8px rgba(7, 17, 38, 0.035);
}

/* =========================================================
   LEFT PANEL
   ========================================================= */
.auth-left {
  position: relative;
  overflow: hidden;
  color: #ffffff;
  background:
    linear-gradient(150deg, rgba(255, 255, 255, 0.09), transparent 34%),
    linear-gradient(145deg, var(--auth-cyan) 0%, var(--auth-blue) 62%, var(--auth-blue-dark) 100%);
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
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0));
}

.auth-left-pattern {
  display: none;
}

.auth-left-content {
  position: relative;
  z-index: 2;
  height: 100%;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto auto;
  align-items: start;
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

/* White wordmark used only on the blue desktop panel. */
.brand-logo-shell {
  width: 180px;
  height: 66px;
  border-radius: 14px;
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

/* Dark logo variant used on the white tablet/mobile card. */
.mobile-brand-logo-shell {
  width: 176px;
  height: 66px;
  padding: 8px 16px;
  border: 1px solid var(--auth-border);
  border-radius: 17px;
  background: #ffffff;
  box-shadow: 0 12px 26px rgba(7, 17, 38, 0.09);
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
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 999px;
  color: rgba(255, 255, 255, 0.96);
  background: rgba(255, 255, 255, 0.13);
  backdrop-filter: blur(10px);
  font-size: 10.5px;
  font-weight: 850;
  letter-spacing: 0.01em;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.secure-pill i {
  font-size: 14px;
}

.left-hero h1 {
  max-width: 620px;
  margin: 0 0 16px;
  color: #ffffff;
  font-size: clamp(35px, 3.25vw, 52px);
  line-height: 1.06;
  font-weight: 950;
  letter-spacing: -0.045em;
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
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 15px;
  background: rgba(255, 255, 255, 0.105);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  gap: 11px;
  transition: border-color 0.18s ease, background 0.18s ease;
}

.feature-item:hover {
  border-color: rgba(255, 255, 255, 0.36);
  background: rgba(255, 255, 255, 0.15);
}

.feature-icon {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 11px;
  color: var(--auth-blue-dark);
  background: rgba(255, 255, 255, 0.96);
  font-size: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.feature-item strong {
  display: block;
  margin-bottom: 3px;
  color: #ffffff;
  font-size: 11.5px;
  line-height: 1.25;
  font-weight: 900;
}

.feature-item span {
  display: block;
  color: rgba(255, 255, 255, 0.76);
  font-size: 9.5px;
  line-height: 1.35;
  font-weight: 700;
}

.left-footer-text {
  margin-top: 16px;
  color: rgba(255, 255, 255, 0.86);
  font-size: 10.5px;
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
  color: var(--auth-blue-dark);
  background: rgba(255, 255, 255, 0.96);
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
  background:
    linear-gradient(145deg, #ffffff 0%, #ffffff 70%, rgba(13, 143, 233, 0.025) 100%);
}

.auth-right::before {
  content: '';
  position: absolute;
  top: 8%;
  bottom: 8%;
  left: 0;
  width: 1px;
  background: linear-gradient(180deg, transparent, #e6edf4 18%, #e6edf4 82%, transparent);
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

.mobile-brand {
  display: none;
  margin-bottom: 25px;
}


.login-head {
  margin-bottom: 27px;
}

.login-pill {
  width: fit-content;
  min-height: 31px;
  margin-bottom: 13px;
  padding: 6px 11px;
  border: 1px solid rgba(13, 143, 233, 0.1);
  border-radius: 999px;
  color: var(--auth-blue-dark);
  background: #edf8ff;
  font-size: 10.5px;
  font-weight: 900;
  text-transform: capitalize;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.login-pill i {
  color: var(--auth-blue);
  font-size: 14px;
}

.login-head h2 {
  margin: 0 0 8px;
  color: var(--auth-night);
  font-size: clamp(32px, 3vw, 39px);
  line-height: 1.05;
  font-weight: 950;
  letter-spacing: -0.04em;
}

.login-head p {
  margin: 0;
  color: var(--auth-muted);
  font-size: 13.5px;
  line-height: 1.65;
}

.auth-status-alert {
  padding: 11px 13px;
  border: 1px solid rgba(10, 179, 156, 0.18);
  border-radius: 13px;
  color: #087f6f;
  background: rgba(10, 179, 156, 0.07);
  font-size: 11.5px;
  font-weight: 850;
  display: flex;
  align-items: center;
  gap: 8px;
}

.auth-form .form-label {
  margin-bottom: 7px;
  color: #263348;
  font-size: 12px;
  font-weight: 900;
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
  color: #7c8ba0;
  font-size: 17px;
  pointer-events: none;
}

.input-shell .form-control {
  min-height: 52px;
  padding: 0 46px;
  border: 1px solid var(--auth-border);
  border-radius: 13px;
  color: var(--auth-text);
  background: #f9fbfd;
  font-size: 12.5px;
  font-weight: 750;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.input-shell .form-control::placeholder {
  color: #9aa8b8;
}

.input-shell .form-control:hover {
  border-color: rgba(13, 143, 233, 0.3);
  background: #ffffff;
}

.input-shell .form-control:focus {
  border-color: var(--auth-blue);
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.11);
}

.input-shell .form-control.is-invalid {
  border-color: #f06548;
}

.password-toggle {
  position: absolute !important;
  top: 50%;
  right: 6px;
  z-index: 3;
  transform: translateY(-50%);
  padding: 8px 10px !important;
  color: #64748b !important;
  text-decoration: none !important;
}

.password-toggle:hover {
  color: var(--auth-blue-dark) !important;
}

.forgot-link {
  color: var(--auth-blue-dark);
  font-size: 11.5px;
  font-weight: 900;
  text-decoration: none;
}

.forgot-link:hover {
  color: var(--auth-night);
}

.auth-options {
  margin: 16px 0 20px;
}

.form-check-label {
  color: #475569;
  font-size: 12px;
  font-weight: 800;
}

.form-check-input {
  cursor: pointer;
}

.login-btn {
  min-height: 52px;
  border: 0;
  border-radius: 13px;
  color: #ffffff;
  background: var(--auth-night);
  box-shadow: 0 14px 30px rgba(7, 17, 38, 0.2);
  font-size: 13.5px;
  font-weight: 900;
  letter-spacing: 0.005em;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.login-btn:hover {
  color: #ffffff;
  transform: translateY(-1px);
  background: var(--auth-night-hover);
  box-shadow: 0 18px 36px rgba(7, 17, 38, 0.24);
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
  color: #8291a3;
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
    height: min(744px, calc(100dvh - 58px));
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
    border-radius: 13px;
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

  .login-pill {
    min-height: 28px;
    margin-bottom: 10px;
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

  .auth-card {
    width: min(560px, 100%);
    margin: 0 auto;
    border-radius: 24px;
  }

  .auth-right::before {
    display: none;
  }

  .auth-form-wrapper {
    min-height: auto;
    padding: 42px 34px;
  }

  .mobile-brand {
    display: inline-flex;
  }
}

@media (max-width: 575.98px) {
  .auth-container {
    padding: 16px 11px;
  }

  .auth-card {
    border-radius: 19px;
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
    border-radius: 15px;
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
</style>
