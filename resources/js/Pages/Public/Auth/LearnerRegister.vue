<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'

const props = defineProps({
  loginUrl: {
    type: String,
    required: true,
  },

  coursesUrl: {
    type: String,
    required: true,
  },
})

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

const handlePassport = event => {
  form.passport_file = event.target.files?.[0] || null
}

const submit = () => {
  form.post(route('learner.register.store'), {
    forceFormData: true,
    preserveScroll: true,

    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

<template>
  <Head title="Create learner account" />

  <main class="learner-register-page">
    <!-- =====================================================
         HERO
    ====================================================== -->
    <section class="register-hero">
      <div class="hero-copy">
        

        <h1>Create your learner account</h1>

        <p>
          Your selected course remains in your cart. Complete your account,
          then submit the training request from your secure learner space.
        </p>

        <div class="hero-points">
          <span>
            <i class="ri-shield-check-line"></i>
            Secure personal account
          </span>

          <span>
            <i class="ri-file-lock-line"></i>
            Private passport storage
          </span>

          <span>
            <i class="ri-shopping-cart-2-line"></i>
            Cart preserved automatically
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
        <!-- Header -->
        <header class="card-header">
          <span class="header-icon">
            <i class="ri-graduation-cap-line"></i>
          </span>

          <div>
            <h2>Learner information</h2>

            <p>
              All required information is stored in your learner account.
            </p>
          </div>
        </header>

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
        <div class="form-section">
          <div class="section-title">
            <i class="ri-user-line"></i>
            Identity
          </div>

          <div class="form-grid three-columns">
            <label class="form-field">
              <span>First name *</span>

              <input
                v-model="form.first_name"
                type="text"
                autocomplete="given-name"
                required
              >

              <small v-if="form.errors.first_name">
                {{ form.errors.first_name }}
              </small>
            </label>

            <label class="form-field">
              <span>Middle name</span>

              <input
                v-model="form.middle_name"
                type="text"
                autocomplete="additional-name"
              >

              <small v-if="form.errors.middle_name">
                {{ form.errors.middle_name }}
              </small>
            </label>

            <label class="form-field">
              <span>Last name *</span>

              <input
                v-model="form.last_name"
                type="text"
                autocomplete="family-name"
                required
              >

              <small v-if="form.errors.last_name">
                {{ form.errors.last_name }}
              </small>
            </label>
          </div>
        </div>

        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->
        <div class="form-section">
          <div class="section-title">
            <i class="ri-map-pin-user-line"></i>
            Personal information
          </div>

          <div class="form-grid">
            <label class="form-field">
              <span>Country *</span>

              <input
                v-model="form.country"
                type="text"
                autocomplete="country-name"
                required
              >

              <small v-if="form.errors.country">
                {{ form.errors.country }}
              </small>
            </label>

            <label class="form-field">
              <span>Date of birth *</span>

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
              <span>Place of birth *</span>

              <input
                v-model="form.place_of_birth"
                type="text"
                required
              >

              <small v-if="form.errors.place_of_birth">
                {{ form.errors.place_of_birth }}
              </small>
            </label>
          </div>
        </div>

        <!-- =================================================
             PASSPORT
        ================================================== -->
        <div class="form-section">
          <div class="section-title">
            <i class="ri-passport-line"></i>
            Passport
          </div>

          <div class="form-grid">
            <label class="form-field">
              <span>Passport number *</span>

              <input
                v-model="form.passport_number"
                type="text"
                required
              >

              <small v-if="form.errors.passport_number">
                {{ form.errors.passport_number }}
              </small>
            </label>

            <label class="form-field">
              <span>Passport document *</span>

              <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                required
                @change="handlePassport"
              >

              <small v-if="form.errors.passport_file">
                {{ form.errors.passport_file }}
              </small>
            </label>
          </div>
        </div>

        <!-- =================================================
             LOGIN CREDENTIALS
        ================================================== -->
        <div class="form-section">
          <div class="section-title">
            <i class="ri-lock-password-line"></i>
            Login credentials
          </div>

          <div class="form-grid">
            <label class="form-field form-field-full">
              <span>Email *</span>

              <input
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
              >

              <small v-if="form.errors.email">
                {{ form.errors.email }}
              </small>
            </label>

            <label class="form-field">
              <span>Password *</span>

              <input
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                required
              >

              <small v-if="form.errors.password">
                {{ form.errors.password }}
              </small>
            </label>

            <label class="form-field">
              <span>Confirm password *</span>

              <input
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                required
              >
            </label>
          </div>
        </div>

        <!-- Terms -->
        <label class="terms-row">
          <input
            v-model="form.terms"
            type="checkbox"
          >

          <span>
            I confirm that the information provided is accurate and I accept
            the platform terms and privacy policy.
          </span>
        </label>

        <small
          v-if="form.errors.terms"
          class="terms-error"
        >
          {{ form.errors.terms }}
        </small>

        <!-- Submit -->
        <button
          type="submit"
          class="submit-button"
          :disabled="form.processing"
        >
          <i
            :class="
              form.processing
                ? 'ri-loader-4-line spinner'
                : 'ri-user-add-line'
            "
          ></i>

          {{
            form.processing
              ? 'Creating account...'
              : 'Create learner account'
          }}
        </button>

        <!-- Login -->
        <p class="login-line">
          Already have an account?

          <a :href="loginUrl">
            Sign in
          </a>
        </p>
      </form>
    </section>
  </main>
</template>

<style scoped>
/* =========================================================
   PAGE
========================================================= */
.learner-register-page {
  --register-primary: #0d8fe9;
  --register-primary-deep: #087bc9;
  --register-primary-light: #37cced;
  --register-primary-soft: #eaf7ff;
  --register-dark: #071126;
  --register-dark-hover: #132b4a;
  --register-muted: #64748b;
  --register-border: #e4edf4;
  --register-danger: #f06548;

  min-height: 100vh;
  padding-bottom: 72px;
  color: var(--register-dark);
  background:
    linear-gradient(
      180deg,
      #f4fbff 0%,
      #ffffff 52%,
      #f8fafc 100%
    );
}

/* =========================================================
   HERO
========================================================= */
.register-hero {
  min-height: 360px;
  padding: 56px 24px 120px;
  color: #ffffff;
  background:
    linear-gradient(
      90deg,
      var(--register-primary-light) 0%,
      var(--register-primary) 100%
    );
}

.hero-copy {
  width: min(960px, 100%);
  margin: 0 auto;
  text-align: center;
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
  color: var(--register-dark);
}

.breadcrumb-home:hover {
  color: var(--register-primary-deep);
}

.breadcrumb-current {
  color: var(--register-primary-deep);
}

.breadcrumb-current:hover {
  color: var(--register-primary);
}

.hero-breadcrumb i {
  color: var(--register-dark);
  font-size: 18px;
  line-height: 1;
}

/* =========================================================
   HERO CONTENT
========================================================= */
.hero-badge {
  padding: 9px 14px;
  border-radius: 999px;
  color: var(--register-dark);
  background: #ffffff;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-copy h1 {
  margin: 20px 0 14px;
  color: #ffffff;
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
  color: #ffffff;
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
  border: 1px solid var(--register-border);
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
}

/* =========================================================
   CARD HEADER
========================================================= */
.card-header {
  padding-bottom: 22px;
  border-bottom: 1px solid var(--register-border);
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  width: 50px;
  height: 50px;
  flex: 0 0 50px;
  border: 1px solid rgba(13, 143, 233, 0.14);
  border-radius: 14px;
  color: var(--register-primary);
  background: var(--register-primary-soft);
  font-size: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.card-header h2 {
  margin: 0 0 4px;
  color: var(--register-dark);
  font-size: 23px;
  font-weight: 900;
}

.card-header p {
  margin: 0;
  color: var(--register-muted);
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
  border-radius: 12px;
  color: #b42318;
  background: #fff1ef;
  font-size: 13px;
  font-weight: 750;
}

/* =========================================================
   FORM SECTIONS
========================================================= */
.form-section {
  padding: 24px 0;
  border-bottom: 1px solid #edf2f7;
}

.section-title {
  margin-bottom: 15px;
  color: #334155;
  font-size: 13px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title i {
  color: var(--register-primary);
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

.form-field > span {
  display: block;
  margin-bottom: 7px;
  color: #475569;
  font-size: 12px;
  font-weight: 850;
}

.form-field input {
  width: 100%;
  min-height: 48px;
  padding: 0 13px;
  border: 1px solid #dce5ef;
  border-radius: 12px;
  color: var(--register-dark);
  background: #ffffff;
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
  border-color: #c7d4e2;
}

.form-field input[type='file'] {
  padding: 10px 13px;
  cursor: pointer;
}

.form-field input:focus {
  border-color: var(--register-primary);
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.12);
}

.form-field small,
.terms-error {
  display: block;
  margin-top: 6px;
  color: var(--register-danger);
  font-size: 11px;
  font-weight: 750;
}

/* =========================================================
   TERMS
========================================================= */
.terms-row {
  margin-top: 22px;
  padding: 14px;
  border: 1px solid var(--register-border);
  border-radius: 13px;
  color: #475569;
  background: #f8fafc;
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
  accent-color: var(--register-primary);
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
  color: #ffffff;
  background: var(--register-dark);
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
  background: var(--register-dark-hover);
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
  color: var(--register-muted);
  text-align: center;
  font-size: 13px;
}

.login-line a {
  margin-left: 3px;
  color: var(--register-primary);
  font-weight: 900;
  text-decoration: none;
}

.login-line a:hover {
  color: var(--register-primary-deep);
  text-decoration: underline;
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
</style>