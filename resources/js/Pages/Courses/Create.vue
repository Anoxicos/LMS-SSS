<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'

import './course-builder.css'
import CourseBasicInfo from './Partials/CourseBasicInfo.vue'

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

  support_voice_over: false,
  voice_over_auto_play: false,
  voice_over_provider: 'voicerss',
  voice_over_lang: 'en-us',
  voice_over_voice: 'default',
})

const validateBasicInfo = () => {
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
      title: t('ui-required-fields'),
      text: t('ui-please-fill-in-all-required-course-information'),
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
      title: t('ui-invalid-duration'),
      text: t('ui-duration-must-be-greater-than-0-hour'),
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  if (Number.isNaN(passingScore) || passingScore < 0 || passingScore > 100) {
    Swal.fire({
      icon: 'warning',
      title: t('ui-invalid-passing-score'),
      text: t('ui-passing-score-must-be-between-0-and-100'),
      timer: 2500,
      showConfirmButton: false,
    })

    return false
  }

  return true
}

const submit = () => {
  if (!validateBasicInfo()) return

  form.post(route('courses.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-created-label-label'),
        text: t('ui-course-has-been-created-successfully'),
        timer: 1800,
        showConfirmButton: false,
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('ui-error-label'),
        text: t('ui-please-check-the-course-information'),
        timer: 2500,
        showConfirmButton: false,
      })
    },
  })
}
</script>

<template>
  <Layout>
    <Head :title="$t('ui-create-course')" />
    <PageHeader :title="$t('ui-create-course')" :page-title="$t('ui-courses')" />

    <div class="course-builder-page">
      <BCard class="course-builder-card border-0 shadow-sm">
        <BCardHeader class="course-builder-header border-0">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="course-title-box">
              <div class="course-main-icon">
                <i class="ri-book-open-line"></i>
              </div>

              <div>
                <h4 class="mb-1 fw-bold">{{ $t('ui-create-course') }}</h4>
                <p class="text-muted mb-0">
                  {{ $t('ui-start-by-saving-the-main-course-information') }}
                </p>
              </div>
            </div>

            <Link :href="route('courses.index')" class="btn btn-light back-btn">
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('ui-back-to-courses') }}
            </Link>
          </div>
        </BCardHeader>

        <BCardBody>
          <CourseBasicInfo
            :form="form"
            :categories="categories"
            :tags="tags"
          />

          <div class="builder-footer">
            <Link :href="route('courses.index')" class="btn btn-light footer-btn">
              <i class="ri-arrow-left-line me-1"></i>
              {{ $t('ui-cancel') }}
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
              {{ $t('ui-save-continue') }}
            </BButton>
          </div>
        </BCardBody>
      </BCard>
    </div>
  </Layout>
</template>
