<script setup>
import { Head } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'

defineOptions({
  layout: LayoutAnonyme,
})

defineProps({
  requestReference: {
    type: String,
    required: true,
  },
  status: {
    type: String,
    default: 'pending',
  },
  coursesCount: {
    type: Number,
    default: 0,
  },
  dashboardUrl: {
    type: String,
    required: true,
  },
  coursesUrl: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <Head :title="$t('ui-training-request-submitted')" />

  <main class="success-page">
    <article class="success-card">
      <span class="success-icon">
        <i class="ri-check-line"></i>
      </span>

      <span class="success-badge">{{ $t('ui-request-received') }}</span>

      <h1>{{ $t('ui-your-training-request-was-submitted') }}</h1>

      <p>
        {{ $t('ui-the-request-is-linked-to-your-learner-account-and-cont') }}
        {{ coursesCount }} {{ $t('ui-selected-course-s-the-super-admin-will-review-each-cou') }}
      </p>

      <div class="reference-box">
        <span>{{ $t('ui-request-reference') }}</span>
        <strong>{{ requestReference }}</strong>
      </div>

      <div class="status-line">
        <i class="ri-time-line"></i>
        {{ $t('ui-current-status') }} <strong>{{ status }}</strong>
      </div>

      <div class="actions">
        <a :href="dashboardUrl" class="primary-action">
          <i class="ri-dashboard-line"></i>
          {{ $t('t-learner-dashboard') }}
        </a>

        <a :href="coursesUrl" class="secondary-action">
          <i class="ri-book-open-line"></i>
          {{ $t('ui-browse-courses') }}
        </a>
      </div>
    </article>
  </main>
</template>

<style scoped>
.success-page {
  min-height: calc(100vh - 70px);
  padding: 70px 20px;
  background: linear-gradient(180deg, var(--paper) 0%, var(--chalk) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-card {
  width: min(650px, 100%);
  padding: 44px;
  border: 1px solid var(--sand);
  border-radius: 26px;
  background: var(--chalk);
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.11);
  text-align: center;
}

.success-icon {
  width: 78px;
  height: 78px;
  margin: 0 auto 16px;
  border-radius: 22px;
  color: var(--chalk);
  background: var(--success);
  font-size: 39px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-badge {
  padding: 7px 11px;
  border-radius: 999px;
  color: var(--signal-deep);
  background: var(--paper);
  font-size: 11px;
  font-weight: 900;
}

.success-card h1 {
  margin: 18px 0 12px;
  color: var(--ink);
  font-size: 31px;
  font-weight: 900;
}

.success-card > p {
  color: var(--slate);
  line-height: 1.7;
}

.reference-box {
  margin: 24px 0 15px;
  padding: 17px;
  border: 1px solid rgba(13, 143, 233, 0.22);
  border-radius: 14px;
  background: var(--paper);
}

.reference-box span {
  color: var(--slate);
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
  display: block;
}

.reference-box strong {
  margin-top: 5px;
  color: var(--signal-deep);
  font-size: 21px;
  display: block;
}

.status-line {
  color: var(--slate);
  font-size: 12px;
}

.status-line strong {
  color: var(--ink);
  text-transform: capitalize;
}

.actions {
  margin-top: 25px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.actions a {
  min-height: 49px;
  border-radius: 13px;
  text-decoration: none;
  font-size: 12px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.primary-action {
  color: var(--chalk);
  background: var(--ink);
}

.secondary-action {
  border: 1px solid var(--sand);
  color: var(--ink);
  background: var(--chalk);
}

@media (max-width: 575.98px) {
  .success-card {
    padding: 26px 18px;
  }

  .actions {
    grid-template-columns: 1fr;
  }
}
</style>
