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
  <Head title="Submit individual training request" />

  <main class="checkout-page">
    <section class="checkout-hero">
      <div class="hero-inner">
        <span class="hero-badge">
          <i class="ri-shopping-cart-check-line"></i>
          Authenticated learner request
        </span>

        <h1>Review and submit your courses</h1>

        <p>
          The request will be linked to your learner account. The Super Admin
          can approve all courses, approve some courses, or reject them.
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
              <h2>Learner account</h2>
              <p>The request will use the information saved in this account.</p>
            </div>

            <a :href="profileUrl" class="edit-profile-link">
              <i class="ri-edit-line"></i>
              Edit profile
            </a>
          </header>

          <div class="account-grid">
            <div>
              <span>Full name</span>
              <strong>{{ learner.full_name }}</strong>
            </div>

            <div>
              <span>Email</span>
              <strong>{{ learner.email }}</strong>
            </div>

            <div>
              <span>Country</span>
              <strong>{{ learner.country }}</strong>
            </div>

            <div>
              <span>Date of birth</span>
              <strong>{{ learner.date_of_birth }}</strong>
            </div>

            <div>
              <span>Place of birth</span>
              <strong>{{ learner.place_of_birth }}</strong>
            </div>

            <div>
              <span>Passport number</span>
              <strong>{{ learner.passport_number }}</strong>
            </div>
          </div>

          <div v-if="!learner.email_verified" class="verification-notice">
            <i class="ri-mail-warning-line"></i>
            <span>
              Your email is not verified yet. You may submit the request, but
              email verification will still be required to access learner courses.
            </span>
          </div>
        </article>

        <form class="request-card" @submit.prevent="submitRequest">
          <header class="card-header">
            <span class="header-icon">
              <i class="ri-file-send-line"></i>
            </span>

            <div>
              <h2>Request information</h2>
              <p>Add an optional note for the Super Admin.</p>
            </div>
          </header>

          <label class="note-field">
            <span>Additional note</span>
            <textarea
              v-model="form.learner_note"
              rows="6"
              placeholder="Add any useful information about your training request..."
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
            <h2>Selected courses</h2>
            <p>{{ cartCount }} course(s) in your cart</p>
          </div>

          <Link :href="coursesUrl" class="add-more-link">
            <i class="ri-add-line"></i>
            Add courses
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
          <span>Estimated total</span>
          <strong>{{ formattedTotal }}</strong>
        </div>

        <p class="cart-note">
          Prices and course availability are verified again by the server when
          the request is submitted.
        </p>
      </aside>
    </section>
  </main>
</template>

<style scoped>
.checkout-page {
  min-height: 100vh;
  padding-bottom: 72px;
  color: #071126;
  background: linear-gradient(180deg, #f4fbff 0%, #ffffff 52%, #f8fafc 100%);
}

.checkout-hero {
  padding: 68px 20px 115px;
  color: #ffffff;
  background: linear-gradient(90deg, #37cced 0%, #0d8fe9 100%);
}

.hero-inner {
  width: min(900px, 100%);
  margin: 0 auto;
  text-align: center;
}

.hero-badge {
  padding: 9px 14px;
  border-radius: 999px;
  color: #071126;
  background: #ffffff;
  font-size: 12px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-inner h1 {
  margin: 18px 0 13px;
  color: #ffffff;
  font-size: clamp(36px, 5vw, 56px);
  font-weight: 900;
}

.hero-inner p {
  max-width: 720px;
  margin: 0 auto;
  color: rgba(255, 255, 255, 0.92);
  line-height: 1.7;
}

.checkout-shell {
  position: relative;
  width: min(1360px, calc(100% - 32px));
  margin: -58px auto 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 400px;
  gap: 24px;
  align-items: start;
}

.checkout-main {
  display: grid;
  gap: 20px;
}

.account-card,
.request-card,
.cart-card {
  border: 1px solid #e4edf4;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.09);
}

.account-card,
.request-card {
  padding: 26px;
}

.cart-card {
  position: sticky;
  top: 92px;
  padding: 22px;
}

.card-header,
.cart-header {
  margin-bottom: 21px;
  padding-bottom: 18px;
  border-bottom: 1px solid #e4edf4;
  display: flex;
  align-items: center;
  gap: 13px;
}

.cart-header {
  justify-content: space-between;
}

.header-icon {
  width: 48px;
  height: 48px;
  flex: 0 0 48px;
  border-radius: 14px;
  color: #0d8fe9;
  background: #eaf7ff;
  font-size: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.card-header h2,
.cart-header h2 {
  margin: 0 0 4px;
  font-size: 21px;
  font-weight: 900;
}

.card-header p,
.cart-header p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.edit-profile-link,
.add-more-link {
  margin-left: auto;
  color: #0d8fe9;
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
  gap: 13px;
}

.account-grid > div {
  padding: 13px;
  border: 1px solid #e4edf4;
  border-radius: 13px;
  background: #f8fafc;
}

.account-grid span {
  margin-bottom: 5px;
  color: #64748b;
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
  display: block;
}

.account-grid strong {
  font-size: 13px;
}

.verification-notice {
  margin-top: 16px;
  padding: 13px;
  border: 1px solid rgba(247, 184, 75, 0.32);
  border-radius: 12px;
  color: #7a4b00;
  background: #fff8e8;
  font-size: 12px;
  line-height: 1.55;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.note-field > span {
  margin-bottom: 8px;
  color: #475569;
  font-size: 12px;
  font-weight: 850;
  display: block;
}

.note-field textarea {
  width: 100%;
  padding: 13px;
  border: 1px solid #dce5ef;
  border-radius: 13px;
  outline: none;
  resize: vertical;
}

.note-field textarea:focus {
  border-color: #0d8fe9;
  box-shadow: 0 0 0 3px rgba(13, 143, 233, 0.12);
}

.note-field small {
  margin-top: 6px;
  color: #f06548;
  font-size: 11px;
  display: block;
}

.request-error {
  margin-top: 14px;
  padding: 12px;
  border-radius: 12px;
  color: #b42318;
  background: #fff1ef;
  font-size: 12px;
  font-weight: 750;
}

.submit-request-button {
  width: 100%;
  min-height: 52px;
  margin-top: 18px;
  border: 0;
  border-radius: 14px;
  color: #ffffff;
  background: #071126;
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
  font-weight: 900;
  cursor: pointer;
}

.submit-request-button:hover:not(:disabled) {
  background: #132b4a;
}

.submit-request-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.cart-list {
  max-height: 520px;
  overflow-y: auto;
  display: grid;
  gap: 11px;
}

.cart-item {
  padding: 9px;
  border: 1px solid #e4edf4;
  border-radius: 14px;
  display: grid;
  grid-template-columns: 66px minmax(0, 1fr) 34px;
  gap: 10px;
  align-items: center;
}

.cart-item img {
  width: 66px;
  height: 59px;
  border-radius: 10px;
  object-fit: cover;
}

.cart-item-copy {
  min-width: 0;
}

.cart-item-copy strong {
  margin-bottom: 5px;
  font-size: 12px;
  line-height: 1.35;
  display: block;
}

.cart-item-copy span {
  color: #087bc9;
  font-size: 11px;
  font-weight: 900;
}

.remove-button {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 10px;
  color: #f06548;
  background: #fff1ef;
  cursor: pointer;
}

.cart-total {
  margin-top: 18px;
  padding-top: 17px;
  border-top: 1px solid #e4edf4;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cart-total span {
  color: #64748b;
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
  border-radius: 11px;
  color: #475569;
  background: #eaf7ff;
  font-size: 11px;
  line-height: 1.55;
}

.spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1050px) {
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
    padding: 45px 16px 90px;
  }

  .checkout-shell {
    width: calc(100% - 20px);
    margin-top: -44px;
  }

  .account-card,
  .request-card,
  .cart-card {
    padding: 17px;
    border-radius: 19px;
  }

  .card-header {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .edit-profile-link {
    width: 100%;
    margin-left: 61px;
  }

  .account-grid {
    grid-template-columns: 1fr;
  }
}
</style>
