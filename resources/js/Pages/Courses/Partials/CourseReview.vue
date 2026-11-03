<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

defineProps({
  form: {
    type: Object,
    required: true,
  },
  selectedCategory: {
    type: Object,
    default: null,
  },
  totalSlides: {
    type: Number,
    default: 0,
  },
  courseId: {
    type: [Number, String, null],
    default: null,
  },
})
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>{{ $t('ui-review-course') }}</h5>
      <p>{{ $t('ui-check-all-information-before-finishing') }}</p>
    </div>

    <div class="review-grid">
      <div class="review-card">
        <div class="review-icon">
          <i class="ri-book-open-line"></i>
        </div>

        <div>
          <span>{{ $t('ui-course') }}</span>
          <h6>{{ form.title || 'Untitled course' }}</h6>
          <p>{{ selectedCategory?.name || 'No category selected' }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-list-check-2"></i>
        </div>

        <div>
          <span>{{ $t('ui-chapters') }}</span>
          <h6>{{ form.chapters.length }}</h6>
          <p>{{ $t('ui-total-chapters') }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-slideshow-line"></i>
        </div>

        <div>
          <span>{{ $t('ui-slides') }}</span>
          <h6>{{ totalSlides }}</h6>
          <p>{{ $t('ui-total-slides') }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-stack-line"></i>
        </div>

        <div>
          <span>{{ $t('ui-plan') }}</span>
          <h6>{{ form.plan.name || 'No plan name' }}</h6>
          <p>
            {{ form.plan.details.length }} {{ $t('ui-detail') }}{{ form.plan.details.length > 1 ? 's' : '' }}
          </p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i :class="form.is_published ? 'ri-checkbox-circle-line' : 'ri-draft-line'"></i>
        </div>

        <div>
          <span>{{ $t('ui-status') }}</span>
          <h6>{{ form.is_published ? $t('ui-published') : $t('ui-draft') }}</h6>
          <p>{{ $t('ui-publication-status') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>