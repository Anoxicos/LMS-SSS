<script setup>
import { computed, onMounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import defaultCourseImage from '@assets/images/sss/default-course_1.jpeg'
import { useIndividualCourseCart } from '@/Composables/useIndividualCourseCart'

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  learner: {
    type: Object,
    required: true,
  },
  coursesUrl: {
    type: String,
    required: true,
  },
  profileUrl: {
    type: String,
    required: true,
  },
})

const {
  cartItems,
  cartCount,
  cartTotal,
  removeCourse,
  clearCart,
} = useIndividualCourseCart()

const form = useForm({
  course_ids: [],
  learner_note: '',
})

const currency = computed(() => {
  return cartItems.value[0]?.currency || 'MAD'
})

const formattedTotal = computed(() => {
  return `${cartTotal.value.toFixed(2)} ${currency.value}`
})

const courseImage = course => {
  if (course?.image_url) return course.image_url
  if (course?.image) return `/storage/${course.image}`
  return defaultCourseImage
}

const submitRequest = () => {
  form.course_ids = cartItems.value.map(course => Number(course.id))

  form.post(route('public.individual-requests.store'), {
    preserveScroll: true,
    onSuccess: () => {
      clearCart()
    },
  })
}

onMounted(() => {
  if (!cartCount.value) {
    router.visit(props.coursesUrl)
  }
})
</script>

<template>
  <Head :title="$t('ui-submit-individual-training-request')" />

  <main class="checkout-page">
    <section class="checkout-hero">
      <div class="hero-inner">
        <span class="hero-badge">
          <i class="ri-shopping-cart-check-line"></i>
          {{ $t('ui-authenticated-learner-request') }}
        </span>

        <h1>{{ $t('ui-review-and-submit-your-courses') }}</h1>

        <p>
          {{ $t('ui-the-request-will-be-linked-to-your-learner-account-the') }}
        </p>
      </div>
    </section>

    <section class="checkout-shell">
      <div class="checkout-main">
        <article class="account-card">
          <header class="card-header">
            <span class="header-icon">
              <i class="ri-user-star-line"></i>
            </span>

            <div>
              <h2>{{ $t('t-learner-account') }}</h2>
              <p>{{ $t('ui-the-request-will-use-the-information-saved-in-this-acc') }}</p>
            </div>

            <a :href="profileUrl" class="edit-profile-link">
              <i class="ri-edit-line"></i>
              {{ $t('t-edit-profile') }}
            </a>
          </header>

          <div class="account-grid">
            <div>
              <span>{{ $t('ui-full-name') }}</span>
              <strong>{{ learner.full_name }}</strong>
            </div>

            <div>
              <span>{{ $t('ui-email') }}</span>
              <strong>{{ learner.email }}</strong>
            </div>

            <div>
              <span>{{ $t('ui-country') }}</span>
              <strong>{{ learner.country }}</strong>
            </div>

            <div>
              <span>{{ $t('ui-date-of-birth') }}</span>
              <strong>{{ learner.date_of_birth }}</strong>
            </div>

            <div>
              <span>{{ $t('ui-place-of-birth') }}</span>
              <strong>{{ learner.place_of_birth }}</strong>
            </div>

            <div>
              <span>{{ $t('ui-passport-number') }}</span>
              <strong>{{ learner.passport_number }}</strong>
            </div>
          </div>

          <div v-if="!learner.email_verified" class="verification-notice">
            <i class="ri-mail-warning-line"></i>
            <span>
              {{ $t('ui-your-email-is-not-verified-yet-you-may-submit-the-requ') }}
            </span>
          </div>
        </article>

        <form class="request-card" @submit.prevent="submitRequest">
          <header class="card-header">
            <span class="header-icon">
              <i class="ri-file-send-line"></i>
            </span>

            <div>
              <h2>{{ $t('ui-request-information') }}</h2>
              <p>{{ $t('ui-add-an-optional-note-for-the-super-admin') }}</p>
            </div>
          </header>

          <label class="note-field">
            <span>{{ $t('ui-additional-note') }}</span>
            <textarea
              v-model="form.learner_note"
              rows="6"
              :placeholder="$t('ui-add-any-useful-information-about-your-training-request')"
            ></textarea>
            <small v-if="form.errors.learner_note">
              {{ form.errors.learner_note }}
            </small>
          </label>

          <div v-if="form.errors.course_ids" class="request-error">
            <i class="ri-error-warning-line"></i>
            {{ form.errors.course_ids }}
          </div>

          <button
            type="submit"
            class="submit-request-button"
            :disabled="form.processing || !cartCount"
          >
            <i :class="form.processing ? 'ri-loader-4-line spinner' : 'ri-send-plane-line'"></i>
            {{ form.processing ? 'Submitting request...' : 'Submit training request' }}
          </button>
        </form>
      </div>

      <aside class="cart-card">
        <header class="cart-header">
          <div>
            <h2>{{ $t('ui-selected-courses') }}</h2>
            <p class="cart-count"><strong>{{ cartCount }}</strong>{{ $t('ui-course-s-in-your-cart') }}</p>
          </div>

          <Link :href="coursesUrl" class="add-more-link">
            <i class="ri-add-line"></i>
            {{ $t('ui-add-courses') }}
          </Link>
        </header>

        <div class="cart-list">
          <article
            v-for="course in cartItems"
            :key="course.id"
            class="cart-item"
          >
            <img :src="courseImage(course)" :alt="course.title">

            <div class="cart-item-copy">
              <strong>{{ course.title }}</strong>
              <span>{{ Number(course.price || 0).toFixed(2) }} {{ course.currency }}</span>
            </div>

            <button
              type="button"
              class="remove-button"
              :aria-label="`Remove ${course.title}`"
              @click="removeCourse(course.id)"
            >
              <i class="ri-delete-bin-6-line"></i>
            </button>
          </article>
        </div>

        <div class="cart-total">
          <span>{{ $t('ui-estimated-total') }}</span>
          <strong>{{ formattedTotal }}</strong>
        </div>

        <p class="cart-note">
          {{ $t('ui-prices-and-course-availability-are-verified-again-by-t') }}
        </p>
      </aside>
    </section>
  </main>
</template>

<style scoped>
.checkout-page {
  min-height: 100dvh;
  padding-bottom: 88px;
  color: var(--ink);
  background: var(--paper);
  background-color: var(--paper, #f2efe8);
}

.checkout-hero {
  position: relative;
  overflow: hidden;
  padding: 100px 24px 100px;
  color: var(--chalk);
  background:
    radial-gradient(ellipse at 84% 12%, rgba(56, 225, 255, 0.13), transparent 30%),
    var(--ink);
}

.hero-inner {
  width: min(1180px, 100%);
  margin: 0 auto;
}

.hero-badge {
  color: var(--jet);
  font-size: 11px;
  font-weight: 850;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 9px;
}

.hero-badge i {
  font-size: 15px;
}

.hero-inner h1 {
  max-width: 780px;
  margin: 18px 0 12px;
  color: var(--chalk);
  font-size: clamp(34px, 4.4vw, 56px);
  line-height: 1.04;
  font-weight: 850;
  letter-spacing: -0.045em;
}

.hero-inner p {
  max-width: 650px;
  margin: 0;
  color: var(--mist);
  font-size: 15px;
  line-height: 1.65;
}

.checkout-shell {
  position: relative;
  width: min(1240px, calc(100% - 48px));
  margin: -72px auto 0;
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.8fr);
  gap: 22px;
  align-items: start;
}

.checkout-main {
  display: grid;
  gap: 20px;
}

.account-card,
.request-card,
.cart-card {
  border: 1px solid rgba(58, 66, 86, 0.12);
  border-radius: 8px;
  background: var(--chalk);
  box-shadow: 0 8px 28px rgba(7, 11, 20, 0.06);
}

.account-card,
.request-card {
  padding: clamp(20px, 2.4vw, 30px);
}

.cart-card {
  position: sticky;
  top: 92px;
  padding: 22px;
}

.card-header,
.cart-header {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(58, 66, 86, 0.12);
  display: flex;
  align-items: center;
  gap: 12px;
}

.cart-header {
  justify-content: space-between;
}

.header-icon {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  border-radius: 6px;
  color: var(--ink);
  background: var(--jet);
  font-size: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.card-header h2,
.cart-header h2 {
  margin: 0 0 4px;
  font-size: 19px;
  font-weight: 900;
  letter-spacing: -0.025em;
}

.card-header p,
.cart-header p {
  margin: 0;
  color: var(--slate);
  font-size: 12px;
}

.edit-profile-link,
.add-more-link {
  margin-left: auto;
  min-height: 44px;
  color: var(--signal-deep);
  text-decoration: none;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.account-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  column-gap: 22px;
  row-gap: 0;
}

.account-grid > div {
  min-width: 0;
  padding: 14px 0;
  border-bottom: 1px solid rgba(58, 66, 86, 0.1);
}

.account-grid span {
  margin-bottom: 5px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 700;
  display: block;
}

.account-grid strong {
  overflow-wrap: anywhere;
  font-size: 13px;
  line-height: 1.45;
  font-weight: 800;
}

.verification-notice {
  margin-top: 16px;
  padding: 13px;
  border: 1px solid rgba(247, 184, 75, 0.32);
  border-radius: 6px;
  color: var(--warning-deep);
  background: rgba(255, 182, 56, 0.12);
  font-size: 12px;
  line-height: 1.55;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.note-field > span {
  margin-bottom: 8px;
  color: var(--slate);
  font-size: 13px;
  font-weight: 850;
  display: block;
}

.note-field textarea {
  width: 100%;
  min-height: 132px;
  padding: 14px 15px;
  border: 1px solid var(--sand);
  border-radius: 6px;
  outline: none;
  resize: vertical;
  color: var(--ink);
  background: var(--chalk);
  line-height: 1.55;
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

.note-field textarea:focus {
  border-color: var(--signal);
  box-shadow: 0 0 0 3px rgba(31, 92, 255, 0.14);
}

.note-field textarea::placeholder {
  color: var(--slate);
}

.note-field small {
  margin-top: 6px;
  color: var(--danger);
  font-size: 11px;
  display: block;
}

.request-error {
  margin-top: 14px;
  padding: 12px;
  border-radius: 12px;
  color: var(--danger-deep);
  background: rgba(255, 77, 77, 0.08);
  font-size: 12px;
  font-weight: 750;
}

.submit-request-button {
  width: 100%;
  min-height: 50px;
  margin-top: 18px;
  border: 0;
  border-radius: 6px;
  color: var(--chalk);
  background: var(--signal);
  font-weight: 900;
  cursor: pointer;
  transition: background-color 160ms ease, transform 160ms ease;
}

.submit-request-button:hover:not(:disabled) {
  background: var(--signal-deep);
  transform: translateY(-1px);
}

.submit-request-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.cart-list {
  max-height: 520px;
  overflow-y: auto;
  display: grid;
  gap: 9px;
}

.cart-count {
  color: var(--slate);
  font-size: 12px;
  font-weight: 750;
}

.cart-count strong {
  margin-right: 4px;
  color: var(--ink);
  font-size: 14px;
}

.cart-item {
  min-width: 0;
  padding: 9px 0;
  border-bottom: 1px solid rgba(58, 66, 86, 0.1);
  display: grid;
  grid-template-columns: 58px minmax(0, 1fr) 44px;
  gap: 11px;
  align-items: center;
}

.cart-item img {
  width: 58px;
  height: 54px;
  border-radius: 4px;
  object-fit: cover;
}

.cart-item-copy {
  min-width: 0;
}

.cart-item-copy strong {
  margin-bottom: 5px;
  overflow-wrap: anywhere;
  font-size: 12px;
  line-height: 1.35;
  display: block;
}

.cart-item-copy span {
  color: var(--signal-deep);
  font-size: 11px;
  font-weight: 900;
}

.remove-button {
  width: 44px;
  height: 44px;
  border: 0;
  border-radius: 6px;
  color: var(--danger);
  background: rgba(255, 77, 77, 0.08);
  cursor: pointer;
}

.cart-total {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid var(--sand);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cart-total span {
  color: var(--slate);
  font-size: 12px;
  font-weight: 800;
}

.cart-total strong {
  font-size: 21px;
  font-weight: 900;
}

.cart-note {
  margin: 13px 0 0;
  padding: 11px;
  border-radius: 6px;
  color: var(--slate);
  background: var(--paper);
  font-size: 11px;
  line-height: 1.55;
}

.spinner {
  animation: spin 0.8s linear infinite;
}

.edit-profile-link:focus-visible,
.add-more-link:focus-visible,
.remove-button:focus-visible,
.submit-request-button:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1020px) {
  .checkout-shell {
    grid-template-columns: 1fr;
  }

  .cart-card {
    position: static;
    grid-row: 1;
  }
}

@media (max-width: 767.98px) {
  .checkout-hero {
    padding: 48px 20px 92px;
  }

  .checkout-shell {
    width: calc(100% - 28px);
    margin-top: -48px;
    gap: 14px;
  }

  .account-card,
  .request-card,
  .cart-card {
    padding: 18px;
  }

  .card-header {
    align-items: flex-start;
  }

  .header-icon {
    width: 38px;
    height: 38px;
    flex-basis: 38px;
    font-size: 18px;
  }

  .card-header h2,
  .cart-header h2 {
    font-size: 17px;
  }

  .account-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 16px;
  }
}

@media (max-width: 480px) {
  .checkout-hero {
    padding: 38px 16px 82px;
  }

  .checkout-hero h1 {
    font-size: clamp(30px, 9vw, 38px);
  }

  .checkout-hero p {
    font-size: 14px;
  }

  .checkout-shell {
    width: calc(100% - 20px);
    margin-top: -40px;
  }

  .account-card,
  .request-card,
  .cart-card {
    padding: 16px;
  }

  .card-header,
  .cart-header {
    flex-wrap: wrap;
  }

  .edit-profile-link {
    margin-left: 50px;
  }

  .cart-header .add-more-link {
    width: 100%;
    margin-left: 50px;
  }

  .account-grid {
    grid-template-columns: 1fr;
  }

  .cart-item {
    grid-template-columns: 50px minmax(0, 1fr) 44px;
    gap: 9px;
  }

  .cart-item img {
    width: 50px;
    height: 48px;
  }
}
</style>
