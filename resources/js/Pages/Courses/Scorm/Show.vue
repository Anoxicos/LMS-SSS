<script setup>
import { formatCourseLabel } from '@/composables/course-labels'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
})

const scormFrame = ref(null)
const scormData = ref({})

const courseImage = computed(() => {
  if (props.course.image_url) {
    return props.course.image_url
  }

  if (props.course.image) {
    return `/storage/${props.course.image}`
  }

  return null
})

const courseInitials = computed(() => {
  return String(props.course.title || 'SC')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
})

const formattedCreatedAt = computed(() => {
  if (!props.course.created_at) {
    return '-'
  }

  return new Date(props.course.created_at).toLocaleDateString(i18n.global.locale?.value === 'fr' ? 'fr-FR' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
})

const formattedPrice = computed(() => {
  const price = Number(props.course.price || 0)

  if (props.course.type !== 'paid' || price === 0) {
    return t('t-free')
  }

  return `${price.toFixed(2)} ${props.course.currency || ''}`.trim()
})

const courseStatusLabel = computed(() => {
  return props.course.is_published ? t('ui-published') : t('ui-draft')
})

const scormVersion = computed(() => {
  return props.course.scorm_version || 'SCORM Package'
})

const createScormApi = () => {
  return {
    LMSInitialize: () => 'true',
    LMSFinish: () => 'true',

    LMSGetValue: (key) => {
      return scormData.value[key] || ''
    },

    LMSSetValue: (key, value) => {
      scormData.value[key] = value
      return 'true'
    },

    LMSCommit: () => 'true',
    LMSGetLastError: () => '0',
    LMSGetErrorString: () => '',
    LMSGetDiagnostic: () => '',
  }
}

onMounted(() => {
  window.API = createScormApi()
})

onBeforeUnmount(() => {
  delete window.API
})
</script>

<template>
  <Layout>
    <Head :title="course.title" />

    <PageHeader
      :title="$t('ui-scorm-course')"
      :page-title="$t('ui-courses')"
    />

    <div class="scorm-admin-page">
      <!-- =====================================================
           HERO SCORM
      ====================================================== -->
      <section class="scorm-admin-hero">
        <div class="scorm-admin-hero__body">
          <div class="scorm-admin-hero__cover">
            <img
              v-if="courseImage"
              :src="courseImage"
              :alt="course.title"
              draggable="false"
              @contextmenu.prevent
            />

            <div
              v-else
              class="scorm-admin-hero__fallback"
            >
              {{ courseInitials }}
            </div>
          </div>

          <div class="scorm-admin-hero__content">
            <div class="scorm-admin-badges">
              <span
                v-if="course.ref"
                class="scorm-admin-badge"
              >
                <i class="ri-hashtag"></i>
                {{ course.ref }}
              </span>

              <span
                class="scorm-admin-badge"
                :class="
                  course.is_published
                    ? 'scorm-admin-badge--success'
                    : 'scorm-admin-badge--warning'
                "
              >
                <i
                  :class="
                    course.is_published
                      ? 'ri-checkbox-circle-line'
                      : 'ri-draft-line'
                  "
                ></i>

                {{ courseStatusLabel }}
              </span>

              <span class="scorm-admin-badge">
                <i class="ri-calendar-line"></i>
                {{ formattedCreatedAt }}
              </span>
            </div>

            <h1 class="scorm-admin-hero__title">
              {{ course.title }}
            </h1>

            <p class="scorm-admin-hero__description">
              {{
                course.description ||
                  'No description available for this SCORM course.'
              }}
            </p>

            <div class="scorm-admin-stats">
              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-graduation-cap-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-type') }}</span>
                  <strong>{{ course.type === 'paid' ? $t('ui-paid') : course.type ? $t('t-free') : '-' }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-computer-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-training') }}</span>
                  <strong>{{ formatCourseLabel(course.training_type) }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-bar-chart-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-course-type') }}</span>
                  <strong>{{ formatCourseLabel(course.skill_level) }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-translate-2"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-language') }}</span>
                  <strong>{{ formatCourseLabel(course.language) }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-money-dollar-circle-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-price') }}</span>
                  <strong>{{ formattedPrice }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-code-box-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-content') }}</span>
                  <strong>{{ $t('ui-scorm') }}</strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-file-zip-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-package') }}</span>

                  <strong>
                    {{
                      course.scorm_zip_path
                        ? 'Uploaded'
                        : 'Missing'
                    }}
                  </strong>
                </div>
              </div>

              <div class="scorm-admin-stat">
                <span class="scorm-admin-stat__icon">
                  <i class="ri-stack-line"></i>
                </span>

                <div class="scorm-admin-stat__content">
                  <span>{{ $t('ui-version') }}</span>
                  <strong>{{ scormVersion }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="scorm-admin-hero__actions">
          <Link
            :href="route('courses.scorm.index')"
            class="scorm-admin-action scorm-admin-action--light"
          >
            <i class="ri-arrow-left-line"></i>
            <span>{{ $t('ui-back') }}</span>
          </Link>

          <a
            v-if="course.syllabus_url"
            :href="course.syllabus_url"
            target="_blank"
            rel="noopener noreferrer"
            class="scorm-admin-action scorm-admin-action--outline"
          >
            <i class="ri-file-pdf-2-line"></i>
            <span>{{ $t('t-view-syllabus') }}</span>
          </a>

          <Link
            v-if="course.hashid"
            :href="route('courses.scorm.edit', course.hashid)"
            class="scorm-admin-action scorm-admin-action--outline"
          >
            <i class="ri-edit-2-line"></i>
            <span>{{ $t('ui-edit') }}</span>
          </Link>
        </div>
      </section>

      <!-- =====================================================
           APERÇU
      ====================================================== -->
      <section class="scorm-admin-overview">
        <article class="scorm-admin-overview__card">
          <span class="scorm-admin-overview__icon">
            <i class="ri-code-box-line"></i>
          </span>

          <div>
            <span>{{ $t('ui-course-format') }}</span>
            <strong>{{ $t('ui-scorm') }}</strong>
          </div>
        </article>

        <article class="scorm-admin-overview__card">
          <span class="scorm-admin-overview__icon">
            <i class="ri-bookmark-3-line"></i>
          </span>

          <div>
            <span>{{ $t('ui-category') }}</span>
            <strong>{{ course.category?.name || '-' }}</strong>
          </div>
        </article>

        <article class="scorm-admin-overview__card">
          <span class="scorm-admin-overview__icon">
            <i class="ri-focus-3-line"></i>
          </span>

          <div>
            <span>{{ $t('ui-course-focus') }}</span>
            <strong>{{ course.course_focus || 'Not specified' }}</strong>
          </div>
        </article>
      </section>

      <!-- =====================================================
           LECTEUR SCORM
      ====================================================== -->
      <section class="scorm-admin-viewer">
        <header class="scorm-admin-viewer__header">
          <span>{{ course.title }}</span>
        </header>

        <div class="scorm-admin-viewer__body">
          <div
            v-if="course.scorm_launch_url"
            class="scorm-admin-frame-wrapper"
          >
            <iframe
              ref="scormFrame"
              :src="course.scorm_launch_url"
              class="scorm-admin-frame"
              allow="fullscreen"
              allowfullscreen
            ></iframe>
          </div>

          <div
            v-else
            class="scorm-admin-empty"
          >
            <i class="ri-code-box-line"></i>
            <h5>{{ $t('ui-no-scorm-package-available') }}</h5>
            <p>{{ $t('ui-the-course-does-not-contain-a-launchable-scorm-package') }}</p>
          </div>
        </div>
      </section>
    </div>
  </Layout>
</template>

<style scoped>
/* =========================================================
   PAGE
========================================================= */

.scorm-admin-page,
.scorm-admin-page * {
  box-sizing: border-box;
}

.scorm-admin-page {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

/* =========================================================
   HERO
   Le container query dépend de la largeur réelle du hero,
   et non uniquement de la largeur de la fenêtre.
========================================================= */

.scorm-admin-hero {
  container-name: scormHero;
  container-type: inline-size;

  position: relative;
  isolation: isolate;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  padding: 28px;
  overflow: hidden;

  border-radius: 24px;
  background: linear-gradient(
    115deg,
    #405189 0%,
    #5e70ef 54%,
    #16b5a8 100%
  );
  box-shadow: 0 18px 40px rgba(48, 61, 121, 0.15);
}

.scorm-admin-hero__body {
  display: grid;
  grid-template-columns: 205px minmax(0, 1fr);
  align-items: start;
  gap: 24px;

  width: 100%;
  min-width: 0;
  padding-right: 310px;
}

.scorm-admin-hero__cover {
  width: 205px;
  height: 162px;
  overflow: hidden;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.12);
  box-shadow: 0 14px 34px rgba(20, 31, 83, 0.18);
}

.scorm-admin-hero__cover img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scorm-admin-hero__fallback {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  height: 100%;

  color: #ffffff;
  font-size: 42px;
  font-weight: 800;
}

.scorm-admin-hero__content {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

.scorm-admin-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;

  width: 100%;
  margin-bottom: 16px;
}

.scorm-admin-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  width: auto;
  max-width: 100%;
  min-height: 32px;
  padding: 7px 12px;

  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 999px;

  color: #ffffff;
  background: rgba(255, 255, 255, 0.11);

  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.scorm-admin-badge--success {
  background: rgba(10, 179, 156, 0.24);
}

.scorm-admin-badge--warning {
  background: rgba(247, 184, 75, 0.24);
}

.scorm-admin-hero__title {
  display: block;

  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin: 0 0 10px;

  color: #ffffff;
  font-size: clamp(27px, 2.1vw, 38px);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: normal;
  text-align: left;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
  writing-mode: horizontal-tb;
}

.scorm-admin-hero__description {
  width: 100%;
  max-width: 760px;
  margin: 0 0 18px;

  color: rgba(255, 255, 255, 0.86);
  font-size: 14px;
  line-height: 1.65;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   STATISTIQUES DU HERO
========================================================= */

.scorm-admin-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(145px, 1fr));
  gap: 10px;

  width: 100%;
  max-width: 930px;
  min-width: 0;
}

.scorm-admin-stat {
  display: flex;
  align-items: center;
  gap: 11px;

  width: 100%;
  min-width: 0;
  min-height: 66px;
  padding: 11px 13px;
  overflow: hidden;

  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.11);
}

.scorm-admin-stat__icon {
  display: inline-flex;
  flex: 0 0 36px;
  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;
  border-radius: 11px;

  color: #ffffff;
  background: rgba(255, 255, 255, 0.15);
  font-size: 18px;
}

.scorm-admin-stat__content {
  width: calc(100% - 47px);
  min-width: 0;
}

.scorm-admin-stat__content > span {
  display: block;
  margin-bottom: 3px;

  color: rgba(255, 255, 255, 0.72);
  font-size: 10px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.scorm-admin-stat__content strong {
  display: block;
  width: 100%;

  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.3;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   ACTIONS
========================================================= */

.scorm-admin-hero__actions {
  position: absolute;
  top: 26px;
  right: 26px;

  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;

  width: auto;
  max-width: 290px;
}

.scorm-admin-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;

  min-height: 42px;
  padding: 10px 17px;

  border: 1px solid transparent;
  border-radius: 13px;

  font-size: 13px;
  font-weight: 700;
  line-height: 1;
  text-decoration: none;
  white-space: nowrap;

  transition:
    transform 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.scorm-admin-action:hover {
  transform: translateY(-1px);
}

.scorm-admin-action--light {
  color: #17233d;
  background: #ffffff;
  border-color: #ffffff;
}

.scorm-admin-action--outline {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.55);
}

.scorm-admin-action--outline:hover {
  color: #405189;
  background: #ffffff;
  border-color: #ffffff;
}

/* =========================================================
   APERÇU
========================================================= */

.scorm-admin-overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;

  width: 100%;
  min-width: 0;
  margin: 20px 0 24px;
}

.scorm-admin-overview__card {
  display: flex;
  align-items: center;
  gap: 13px;

  width: 100%;
  min-width: 0;
  min-height: 78px;
  padding: 16px;

  border: 1px solid #e9edf5;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(31, 45, 86, 0.05);
}

.scorm-admin-overview__icon {
  display: inline-flex;
  flex: 0 0 44px;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;
  border-radius: 14px;

  color: #405189;
  background: #eef2ff;
  font-size: 21px;
}

.scorm-admin-overview__card > div {
  min-width: 0;
}

.scorm-admin-overview__card span {
  display: block;
  margin-bottom: 4px;

  color: #68728a;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.scorm-admin-overview__card strong {
  display: block;
  width: 100%;

  color: #17233d;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.35;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

/* =========================================================
   LECTEUR
========================================================= */

.scorm-admin-viewer {
  width: 100%;
  min-width: 0;
  overflow: hidden;

  border: 1px solid #e8edf5;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 12px 35px rgba(31, 45, 86, 0.06);
}

.scorm-admin-viewer__header {
  display: flex;
  align-items: center;
  justify-content: flex-end;

  width: 100%;
  min-height: 62px;
  padding: 14px 22px;

  border-bottom: 1px solid #edf0f5;
}

.scorm-admin-viewer__header span {
  color: #536078;
  font-size: 13px;
  font-weight: 700;
  text-align: right;

  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

.scorm-admin-viewer__body {
  width: 100%;
  min-width: 0;
  padding: 20px;
}

.scorm-admin-frame-wrapper {
  width: 100%;
  min-width: 0;
  overflow: hidden;

  border-radius: 18px;
  background: #0f172a;
}

.scorm-admin-frame {
  display: block;

  width: 100%;
  height: clamp(520px, 68vh, 920px);
  min-height: 520px;

  border: 0;
  background: #ffffff;
}

.scorm-admin-empty {
  display: flex;
  min-height: 360px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;

  padding: 30px;
  color: #718096;
  text-align: center;
}

.scorm-admin-empty i {
  font-size: 44px;
}

.scorm-admin-empty h5,
.scorm-admin-empty p {
  margin: 0;
}

/* =========================================================
   CONTAINER QUERIES
   Correction réelle du problème avec la sidebar.
========================================================= */

@container scormHero (max-width: 980px) {
  .scorm-admin-hero__body {
    padding-right: 0;
  }

  .scorm-admin-hero__actions {
    position: static;
    justify-content: flex-start;

    width: 100%;
    max-width: none;
    margin-top: 20px;
  }

  .scorm-admin-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: none;
  }
}

@container scormHero (max-width: 820px) {
  .scorm-admin-hero {
    padding: 22px;
  }

  .scorm-admin-hero__body {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .scorm-admin-hero__cover {
    width: 180px;
    height: 140px;
  }

  .scorm-admin-hero__title {
    font-size: 29px;
  }
}

@container scormHero (max-width: 560px) {
  .scorm-admin-hero {
    padding: 18px;
    border-radius: 19px;
  }

  .scorm-admin-hero__cover {
    width: 145px;
    height: 114px;
    border-radius: 18px;
  }

  .scorm-admin-hero__title {
    font-size: 24px;
  }

  .scorm-admin-stats {
    grid-template-columns: 1fr;
  }

  .scorm-admin-hero__actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .scorm-admin-action {
    width: 100%;
  }
}

/* =========================================================
   VIEWPORT FALLBACK
========================================================= */

@media (max-width: 1199.98px) {
  .scorm-admin-hero__body {
    padding-right: 0;
  }

  .scorm-admin-hero__actions {
    position: static;
    justify-content: flex-start;

    width: 100%;
    max-width: none;
    margin-top: 20px;
  }
}

@media (max-width: 991.98px) {
  .scorm-admin-hero {
    padding: 22px;
  }

  .scorm-admin-hero__body {
    grid-template-columns: 1fr;
  }

  .scorm-admin-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: none;
  }

  .scorm-admin-overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .scorm-admin-overview__card:last-child {
    grid-column: 1 / -1;
  }
}

@media (max-width: 767.98px) {
  .scorm-admin-overview {
    grid-template-columns: 1fr;
    gap: 11px;
    margin: 16px 0 20px;
  }

  .scorm-admin-overview__card:last-child {
    grid-column: auto;
  }

  .scorm-admin-viewer__header {
    justify-content: flex-start;
    min-height: 56px;
    padding: 12px 16px;
  }

  .scorm-admin-viewer__header span {
    text-align: left;
  }

  .scorm-admin-viewer__body {
    padding: 12px;
  }

  .scorm-admin-frame {
    height: 66vh;
    min-height: 460px;
  }
}

@media (max-width: 575.98px) {
  .scorm-admin-hero {
    padding: 16px;
    border-radius: 17px;
  }

  .scorm-admin-hero__cover {
    width: 132px;
    height: 106px;
  }

  .scorm-admin-hero__title {
    font-size: 23px;
  }

  .scorm-admin-stats {
    grid-template-columns: 1fr;
  }

  .scorm-admin-hero__actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .scorm-admin-action {
    width: 100%;
  }

  .scorm-admin-viewer__body {
    padding: 9px;
  }

  .scorm-admin-frame {
    height: 64vh;
    min-height: 420px;
  }
}

@media (max-width: 399.98px) {
  .scorm-admin-hero {
    padding: 14px;
  }

  .scorm-admin-hero__cover {
    width: 116px;
    height: 94px;
  }

  .scorm-admin-hero__title {
    font-size: 21px;
  }

  .scorm-admin-frame {
    height: 62vh;
    min-height: 380px;
  }
}

@media (max-height: 560px) and (orientation: landscape) {
  .scorm-admin-frame {
    height: 82vh;
    min-height: 320px;
  }
}
</style>
