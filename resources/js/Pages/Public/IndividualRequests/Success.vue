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
  <Head title="Training request submitted" />

  <main class="success-page">
    <article class="success-card">
      <span class="success-icon">
        <i class="ri-check-line"></i>
      </span>

      <span class="success-badge">Request received</span>

      <h1>Your training request was submitted</h1>

      <p>
        The request is linked to your learner account and contains
        {{ coursesCount }} selected course(s). The Super Admin will review each
        course separately.
      </p>

      <div class="reference-box">
        <span>Request reference</span>
        <strong>{{ requestReference }}</strong>
      </div>

      <div class="status-line">
        <i class="ri-time-line"></i>
        Current status: <strong>{{ status }}</strong>
      </div>

      <div class="actions">
        <a :href="dashboardUrl" class="primary-action">
          <i class="ri-dashboard-line"></i>
          Learner dashboard
        </a>

        <a :href="coursesUrl" class="secondary-action">
          <i class="ri-book-open-line"></i>
          Browse courses
        </a>
      </div>
    </article>
  </main>
</template>

<style scoped>
.success-page {
  min-height: calc(100vh - 70px);
  padding: 70px 20px;
  background: linear-gradient(180deg, #eaf7ff 0%, #ffffff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-card {
  width: min(650px, 100%);
  padding: 44px;
  border: 1px solid #e4edf4;
  border-radius: 26px;
  background: #ffffff;
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.11);
  text-align: center;
}

.success-icon {
  width: 78px;
  height: 78px;
  margin: 0 auto 16px;
  border-radius: 22px;
  color: #ffffff;
  background: #0ab39c;
  font-size: 39px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-badge {
  padding: 7px 11px;
  border-radius: 999px;
  color: #087bc9;
  background: #eaf7ff;
  font-size: 11px;
  font-weight: 900;
}

.success-card h1 {
  margin: 18px 0 12px;
  color: #071126;
  font-size: 31px;
  font-weight: 900;
}

.success-card > p {
  color: #64748b;
  line-height: 1.7;
}

.reference-box {
  margin: 24px 0 15px;
  padding: 17px;
  border: 1px solid rgba(13, 143, 233, 0.22);
  border-radius: 14px;
  background: #eaf7ff;
}

.reference-box span {
  color: #64748b;
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
  display: block;
}

.reference-box strong {
  margin-top: 5px;
  color: #087bc9;
  font-size: 21px;
  display: block;
}

.status-line {
  color: #64748b;
  font-size: 12px;
}

.status-line strong {
  color: #071126;
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
  color: #ffffff;
  background: #071126;
}

.secondary-action {
  border: 1px solid #dce5ef;
  color: #071126;
  background: #ffffff;
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
