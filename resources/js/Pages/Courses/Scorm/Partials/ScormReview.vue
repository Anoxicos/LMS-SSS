<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  selectedCategory: {
    type: Object,
    default: null,
  },
})

const countDetails = (details = []) => {
  return details.reduce((total, detail) => {
    return total + 1 + countDetails(detail.children || [])
  }, 0)
}

const planDetailsCount = computed(() => {
  return countDetails(props.form.plan?.details || [])
})

const globalQuestions = computed(() => {
  return (props.form.plan?.details || []).reduce((sum, detail) => {
    return sum + Number(detail.total_questions || 0)
  }, 0)
})

const packageLabel = computed(() => {
  return props.form.scorm_file?.name
    || props.form.scorm_title
    || props.form.scorm_entry_file
    || 'No SCORM package'
})
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>Review SCORM Course</h5>
      <p>Check the course information, SCORM package and assessment plan before finishing.</p>
    </div>

    <div class="review-grid">
      <div class="review-card">
        <div class="review-icon">
          <i class="ri-book-open-line"></i>
        </div>

        <div>
          <span>Course</span>
          <h6>{{ form.title || 'Untitled SCORM course' }}</h6>
          <p>{{ selectedCategory?.name || 'No category selected' }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-file-zip-line"></i>
        </div>

        <div>
          <span>SCORM Package</span>
          <h6>{{ packageLabel }}</h6>
          <p>{{ form.scorm_version || 'Version not detected' }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-stack-line"></i>
        </div>

        <div>
          <span>Plan</span>
          <h6>{{ form.plan?.name || 'No plan name' }}</h6>
          <p>{{ planDetailsCount }} saved detail{{ planDetailsCount === 1 ? '' : 's' }}</p>
        </div>
      </div>

      <div class="review-card">
        <div class="review-icon">
          <i class="ri-questionnaire-line"></i>
        </div>

        <div>
          <span>Questions</span>
          <h6>{{ globalQuestions }}</h6>
          <p>Total questions configured in the plan</p>
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

    <div class="mt-4 p-3 border rounded-3 bg-light" v-if="form.scorm_entry_file">
      <div class="d-flex align-items-start gap-3">
        <div class="review-icon flex-shrink-0">
          <i class="ri-play-circle-line"></i>
        </div>

        <div class="min-width-0">
          <span class="text-muted d-block mb-1">Launch file</span>
          <h6 class="mb-1 text-break">{{ form.scorm_entry_file }}</h6>
          <p class="text-muted mb-0 text-break" v-if="form.scorm_identifier">
            Identifier: {{ form.scorm_identifier }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
