<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  learner: {
    type: Object,
    required: true,
  },
  checkoutUrl: {
    type: String,
    required: true,
  },
})

const form = useForm({
  first_name: props.learner.first_name || '',
  middle_name: props.learner.middle_name || '',
  last_name: props.learner.last_name || '',
  country: props.learner.country || '',
  date_of_birth: props.learner.date_of_birth || '',
  place_of_birth: props.learner.place_of_birth || '',
  passport_number: props.learner.passport_number || '',
  passport_file: null,
})

const passportDragging = ref(false)
const passportName = ref('')
const passportUploadError = ref('')

const handlePassport = event => {
  const file = event.target.files?.[0] || null
  if (event.target) event.target.value = ''
  if (!file) return

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['pdf', 'jpg', 'jpeg', 'png'].includes(ext) || file.size > 5 * 1024 * 1024) {
    form.passport_file = null
    passportName.value = ''
    passportUploadError.value = 'Choose a PDF, JPG, or PNG file up to 5 MB.'
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

const submit = () => {
  form.post(route('learner-account.update'), {
    forceFormData: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <Head :title="$t('t-complete-learner-profile')" />

  <main class="profile-page">
    <section class="profile-hero">
      <div>
        <span class="profile-badge">
          <i class="ri-user-settings-line"></i>
          {{ $t('t-learner-account') }}
        </span>
        <h1>{{ $t('t-complete-your-learner-profile') }}</h1>
        <p>
          {{ $t('t-learner-profile-requirement') }}
        </p>
      </div>
    </section>

    <section class="profile-shell">
      <form class="profile-card" @submit.prevent="submit">
        <header>
          <span><i class="ri-id-card-line"></i></span>
          <div>
            <h2>{{ $t('t-account-information') }}</h2>
            <p>{{ learner.email }}</p>
          </div>
        </header>

        <div v-if="form.errors.profile" class="global-error">
          {{ form.errors.profile }}
        </div>

        <div class="form-grid three-columns">
          <label>
            <span>{{ $t('t-first-name') }} *</span>
            <input v-model="form.first_name" type="text" required>
            <small v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
          </label>

          <label>
            <span>{{ $t('t-middle-name') }}</span>
            <input v-model="form.middle_name" type="text">
            <small v-if="form.errors.middle_name">{{ form.errors.middle_name }}</small>
          </label>

          <label>
            <span>{{ $t('t-last-name') }} *</span>
            <input v-model="form.last_name" type="text" required>
            <small v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
          </label>
        </div>

        <div class="form-grid">
          <label>
            <span>{{ $t('t-country') }} *</span>
            <input v-model="form.country" type="text" required>
            <small v-if="form.errors.country">{{ form.errors.country }}</small>
          </label>

          <label>
            <span>{{ $t('t-date-of-birth') }} *</span>
            <input v-model="form.date_of_birth" type="date" required>
            <small v-if="form.errors.date_of_birth">{{ form.errors.date_of_birth }}</small>
          </label>

          <label>
            <span>{{ $t('t-place-of-birth') }} *</span>
            <input v-model="form.place_of_birth" type="text" required>
            <small v-if="form.errors.place_of_birth">{{ form.errors.place_of_birth }}</small>
          </label>

          <label>
            <span>{{ $t('t-passport-number') }} *</span>
            <input v-model="form.passport_number" type="text" required>
            <small v-if="form.errors.passport_number">{{ form.errors.passport_number }}</small>
          </label>

          <label class="full-field">
            <span>
              {{ $t('t-passport-document') }}
              <template v-if="!learner.has_passport_file">*</template>
            </span>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              :required="!learner.has_passport_file"
              class="media-drop-target"
              :class="{ 'is-dragging': passportDragging }"
              @dragenter.prevent="passportDragging = true"
              @dragover.prevent="passportDragging = true"
              @dragleave.prevent="passportDragging = false"
              @drop.prevent="handlePassportDrop"
              @change="handlePassport"
            >
            <small v-if="passportName" class="selected-upload" role="status"><i class="ri-check-line" aria-hidden="true"></i> {{ passportName }}</small>
            <small v-if="passportUploadError" class="upload-error" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ passportUploadError }}</small>
            <small v-if="form.errors.passport_file">{{ form.errors.passport_file }}</small>
            <em v-else-if="learner.has_passport_file">
              {{ $t('t-passport-replacement-hint') }}
            </em>
          </label>
        </div>

        <div v-if="form.processing && form.progress" class="upload-progress" role="status" aria-live="polite">
          <div class="d-flex justify-content-between small"><span>{{ passportName }}</span><strong>{{ form.progress.percentage }}%</strong></div>
          <div class="progress" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" :style="{ width: `${form.progress.percentage}%` }"></div></div>
        </div>

        <button type="submit" :disabled="form.processing">
          <i :class="form.processing ? 'ri-loader-4-line spinner' : 'ri-save-line'"></i>
          {{ form.processing ? $t('t-saving') : $t('t-save-and-continue-to-cart') }}
        </button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.media-drop-target { transition: border-color 140ms ease, background-color 140ms ease; }
.media-drop-target.is-dragging { outline: 2px dashed var(--signal); outline-offset: 2px; background: rgba(31, 92, 255, 0.08); }
.selected-upload { display: block; color: var(--success-deep); }
.upload-error { display: block; color: var(--danger-deep); }
.upload-progress .progress { height: 7px; }

.profile-page {
  min-height: 100vh;
  padding: 112px 0 70px;
  color: var(--ink);
  background: linear-gradient(180deg, var(--paper) 0%, var(--chalk) 55%, var(--paper) 100%);
}

.profile-hero {
  padding: 44px 20px 112px;
  color: var(--chalk);
  text-align: center;
  background: radial-gradient(ellipse at 74% 35%, rgba(56, 225, 255, 0.14), transparent 34%), var(--ink);
}

.profile-hero > div {
  width: min(820px, 100%);
  margin: 0 auto;
}

.profile-badge {
  padding: 9px 14px;
  border-radius: 10px;
  color: var(--ink);
  background: var(--chalk);
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.profile-hero h1 {
  margin: 18px 0 12px;
  color: var(--chalk);
  font-size: clamp(34px, 5vw, 52px);
  font-weight: 900;
}

.profile-hero p {
  margin: 0;
  color: rgba(255, 255, 255, 0.92);
  line-height: 1.7;
}

.profile-shell {
  width: min(1040px, calc(100% - 32px));
  margin: -58px auto 0;
}

.profile-card {
  padding: clamp(20px, 4vw, 36px);
  border: 1px solid var(--sand);
  border-radius: 20px;
  background: var(--chalk);
  box-shadow: 0 22px 55px rgba(15, 23, 42, 0.11);
}

.profile-card header {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--sand);
  display: flex;
  align-items: center;
  gap: 13px;
}

.profile-card header > span {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  color: var(--signal);
  background: var(--paper);
  font-size: 23px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.profile-card h2 {
  margin: 0 0 3px;
  font-size: 22px;
  font-weight: 900;
}

.profile-card header p {
  margin: 0;
  color: var(--slate);
  font-size: 12px;
}

.global-error {
  margin-bottom: 16px;
  padding: 12px;
  border-radius: 11px;
  color: var(--danger-deep);
  background: rgba(255, 77, 77, 0.08);
}

.form-grid {
  margin-bottom: 18px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.form-grid.three-columns {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.full-field {
  grid-column: 1 / -1;
}

label > span {
  margin-bottom: 7px;
  color: var(--slate);
  font-size: 12px;
  font-weight: 850;
  display: block;
}

input {
  width: 100%;
  min-height: 47px;
  padding: 0 13px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  color: var(--ink);
  background: #fff;
}

input[type='file'] {
  padding: 10px 13px;
}

input:focus {
  border-color: var(--signal);
  box-shadow: 0 0 0 3px rgba(31, 92, 255, 0.14);
}

input:focus-visible,
.profile-card > button:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 2px;
}

small {
  margin-top: 6px;
  color: var(--danger);
  font-size: 11px;
  display: block;
}

em {
  margin-top: 7px;
  color: var(--slate);
  font-size: 11px;
  font-style: normal;
  display: block;
}

.profile-card > button {
  width: 100%;
  min-height: 52px;
  margin-top: 7px;
  border: 0;
  border-radius: 14px;
  color: var(--chalk);
  background: var(--ink);
  font-weight: 900;
  cursor: pointer;
}

.profile-card > button:hover:not(:disabled) {
  background: var(--midnight);
}

.spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 767.98px) {
  .profile-page {
    padding-top: 96px;
  }

  .profile-hero {
    padding: 34px 20px 84px;
  }

  .profile-shell {
    width: calc(100% - 20px);
    margin-top: -45px;
  }

  .profile-card {
    padding: 18px;
    border-radius: 19px;
  }

  .form-grid,
  .form-grid.three-columns {
    grid-template-columns: 1fr;
  }

  .full-field {
    grid-column: auto;
  }
}

@media (min-width: 600px) and (max-width: 900px) {
  .form-grid.three-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
