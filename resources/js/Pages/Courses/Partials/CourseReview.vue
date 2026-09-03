<script setup>
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
      <h5>Review Course</h5>
      <p>Check all information before finishing.</p>
    </div>

    <div class="review-grid">
      <div class="review-card">
        <div class="review-icon">
          <i class="ri-book-open-line"></i>
        </div>

        <div>
          <span>Course</span>
          <h6>{{ form.title || 'Untitled course' }}</h6>
          <p>{{ selectedCategory?.name || 'No category selected' }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-list-check-2"></i>
        </div>

        <div>
          <span>Chapters</span>
          <h6>{{ form.chapters.length }}</h6>
          <p>Total chapters</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-slideshow-line"></i>
        </div>

        <div>
          <span>Slides</span>
          <h6>{{ totalSlides }}</h6>
          <p>Total slides</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-stack-line"></i>
        </div>

        <div>
          <span>Plan</span>
          <h6>{{ form.plan.name || 'No plan name' }}</h6>
          <p>
            {{ form.plan.details.length }} detail{{ form.plan.details.length > 1 ? 's' : '' }}
          </p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i :class="form.is_published ? 'ri-checkbox-circle-line' : 'ri-draft-line'"></i>
        </div>

        <div>
          <span>Status</span>
          <h6>{{ form.is_published ? 'Published' : 'Draft' }}</h6>
          <p>Publication status</p>
        </div>
      </div>
    </div>
  </div>
</template>