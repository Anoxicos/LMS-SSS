<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

import '../course-builder.css'
import ScormBasicInfo from './Partials/ScormBasicInfo.vue'

defineProps({
  categories: {
    type: Array,
    required: true,
  },
  tags: {
    type: Array,
    default: () => [],
  },
})

const form = useForm({
  ref: '',
  title: '',
  description: '',
  category_id: '',
  type: '',
  training_type: '',
  method: '',
  duration_hours: '',
  passing_score: 80,
  skill_level: '',
  course_focus: '',
  language: 'English',
  price: 0,
  currency: 'MAD',
  is_published: false,
  tags: [],

  image: null,
  image_url: null,
  remove_image: false,

  syllabus: null,
  syllabus_url: null,
  syllabus_name: null,
  remove_syllabus: false,

  scorm_file: null,
  scorm_zip_path: null,
  scorm_extract_path: null,
  scorm_entry_file: null,
  scorm_version: null,
  scorm_identifier: null,
  scorm_title: null,
  scorm_launch_url: null,
})

const validateForm = () => {
  const durationMissing =
    form.duration_hours === '' ||
    form.duration_hours === null ||
    form.duration_hours === undefined

  const passingScoreMissing =
    form.passing_score === '' ||
    form.passing_score === null ||
    form.passing_score === undefined

  if (
    !form.ref ||
    !form.title ||
    !form.category_id ||
    !form.type ||
    !form.training_type ||
    !form.method ||
    durationMissing ||
    passingScoreMissing
  ) {
    Swal.fire({
      icon: 'warning',
      title: 'Required fields',
      text: 'Please fill in all required SCORM course information.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  const durationHours = Number(form.duration_hours)
  const passingScore = Number(form.passing_score)

  if (Number.isNaN(durationHours) || durationHours <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid duration',
      text: 'Duration must be greater than 0 hour.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (Number.isNaN(passingScore) || passingScore < 0 || passingScore > 100) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid passing score',
      text: 'Passing score must be between 0 and 100.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (!form.scorm_file) {
    Swal.fire({
      icon: 'warning',
      title: 'SCORM package required',
      text: 'Please select a valid SCORM ZIP package.',
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  return true
}

const submit = () => {
  if (!validateForm()) return

  form.post(route('courses.scorm.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Created!',
        text: 'SCORM course has been created. You can now configure its plan.',
        timer: 1800,
        showConfirmButton: false,
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: form.errors.error || 'Please check the SCORM course information.',
        timer: 2800,
        showConfirmButton: false,
      })
    },
  })
}
</script>

<template>
  <Layout>
    <Head title="Create SCORM Course" />
    <PageHeader title="Create SCORM Course" pageTitle="Courses" />

    <div class="course-builder-page">
      <BCard class="course-builder-card border-0 shadow-sm">
        <BCardHeader class="course-builder-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="course-title-box">
              <div class="course-main-icon">
                <i class="ri-code-box-line"></i>
              </div>

              <div>
                <h4 class="mb-1 fw-bold">Create SCORM Course</h4>
                <p class="text-muted mb-0">
                  Start with the course information and SCORM package. The plan is configured next.
                </p>
              </div>
            </div>

            <Link :href="route('courses.scorm.index')" class="btn btn-light back-btn">
              <i class="ri-arrow-left-line me-1"></i>
              Back to SCORM Courses
            </Link>
          </div>
        </BCardHeader>

        <BCardBody>
          <ScormBasicInfo
            :form="form"
            :categories="categories"
            :tags="tags"
            :is-editing="false"
          />

          <div class="builder-footer">
            <Link :href="route('courses.scorm.index')" class="btn btn-light footer-btn">
              <i class="ri-arrow-left-line me-1"></i>
              Cancel
            </Link>

            <BButton
              type="button"
              variant="success"
              class="footer-btn"
              :disabled="form.processing"
              @click="submit"
            >
              <span
                v-if="form.processing"
                class="spinner-border spinner-border-sm me-1"
              ></span>
              <i v-else class="ri-save-line me-1"></i>
              Save & Continue
            </BButton>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>
