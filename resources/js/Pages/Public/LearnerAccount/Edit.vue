<script setup>
import { Head, useForm } from '@inertiajs/vue3'
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

const handlePassport = event => {
  form.passport_file = event.target.files?.[0] || null
}

const submit = () => {
  form.post(route('learner-account.update'), {
    forceFormData: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Complete learner profile" />

  <main class="profile-page">
    <section class="profile-hero">
      <div>
        <span class="profile-badge">
          <i class="ri-user-settings-line"></i>
          Learner account
        </span>
        <h1>Complete your learner profile</h1>
        <p>
          Your identity and passport information must be complete before you
          can submit an individual training request.
        </p>
      </div>
    </section>

    <section class="profile-shell">
      <form class="profile-card" @submit.prevent="submit">
        <header>
          <span><i class="ri-id-card-line"></i></span>
          <div>
            <h2>Account information</h2>
            <p>{{ learner.email }}</p>
          </div>
        </header>

        <div v-if="form.errors.profile" class="global-error">
          {{ form.errors.profile }}
        </div>

        <div class="form-grid three-columns">
          <label>
            <span>First name *</span>
            <input v-model="form.first_name" type="text" required>
            <small v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
          </label>

          <label>
            <span>Middle name</span>
            <input v-model="form.middle_name" type="text">
            <small v-if="form.errors.middle_name">{{ form.errors.middle_name }}</small>
          </label>

          <label>
            <span>Last name *</span>
            <input v-model="form.last_name" type="text" required>
            <small v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
          </label>
        </div>

        <div class="form-grid">
          <label>
            <span>Country *</span>
            <input v-model="form.country" type="text" required>
            <small v-if="form.errors.country">{{ form.errors.country }}</small>
          </label>

          <label>
            <span>Date of birth *</span>
            <input v-model="form.date_of_birth" type="date" required>
            <small v-if="form.errors.date_of_birth">{{ form.errors.date_of_birth }}</small>
          </label>

          <label>
            <span>Place of birth *</span>
            <input v-model="form.place_of_birth" type="text" required>
            <small v-if="form.errors.place_of_birth">{{ form.errors.place_of_birth }}</small>
          </label>

          <label>
            <span>Passport number *</span>
            <input v-model="form.passport_number" type="text" required>
            <small v-if="form.errors.passport_number">{{ form.errors.passport_number }}</small>
          </label>

          <label class="full-field">
            <span>
              Passport document
              <template v-if="!learner.has_passport_file">*</template>
            </span>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              :required="!learner.has_passport_file"
              @change="handlePassport"
            >
            <small v-if="form.errors.passport_file">{{ form.errors.passport_file }}</small>
            <em v-else-if="learner.has_passport_file">
              A passport document is already stored. Select a new file only to replace it.
            </em>
          </label>
        </div>

        <button type="submit" :disabled="form.processing">
          <i :class="form.processing ? 'ri-loader-4-line spinner' : 'ri-save-line'"></i>
          {{ form.processing ? 'Saving...' : 'Save and continue to cart' }}
        </button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.profile-page {
  min-height: 100vh;
  padding-bottom: 70px;
  color: #071126;
  background: linear-gradient(180deg, #f4fbff 0%, #ffffff 55%, #f8fafc 100%);
}

.profile-hero {
  padding: 65px 20px 115px;
  color: #fff;
  text-align: center;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
}

.profile-hero > div {
  width: min(820px, 100%);
  margin: 0 auto;
}

.profile-badge {
  padding: 9px 14px;
  border-radius: 999px;
  color: #071126;
  background: #fff;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.profile-hero h1 {
  margin: 18px 0 12px;
  color: #fff;
  font-size: clamp(34px, 5vw, 52px);
  font-weight: 900;
}

.profile-hero p {
  margin: 0;
  color: rgba(255, 255, 255, 0.92);
  line-height: 1.7;
}

.profile-shell {
  width: min(950px, calc(100% - 28px));
  margin: -58px auto 0;
}

.profile-card {
  padding: 28px;
  border: 1px solid #e4edf4;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 22px 55px rgba(15, 23, 42, 0.11);
}

.profile-card header {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e4edf4;
  display: flex;
  align-items: center;
  gap: 13px;
}

.profile-card header > span {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  color: #0d8fe9;
  background: #eaf7ff;
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
  color: #64748b;
  font-size: 12px;
}

.global-error {
  margin-bottom: 16px;
  padding: 12px;
  border-radius: 11px;
  color: #b42318;
  background: #fff1ef;
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
  color: #475569;
  font-size: 12px;
  font-weight: 850;
  display: block;
}

input {
  width: 100%;
  min-height: 47px;
  padding: 0 13px;
  border: 1px solid #dce5ef;
  border-radius: 12px;
  outline: none;
}

input[type='file'] {
  padding: 10px 13px;
}

input:focus {
  border-color: #0d8fe9;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.12);
}

small {
  margin-top: 6px;
  color: #f06548;
  font-size: 11px;
  display: block;
}

em {
  margin-top: 7px;
  color: #64748b;
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
  color: #fff;
  background: #071126;
  font-weight: 900;
  cursor: pointer;
}

.profile-card > button:hover:not(:disabled) {
  background: #132b4a;
}

.spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 767.98px) {
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
</style>
