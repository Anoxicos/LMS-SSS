<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import defaultCourseImage from '@assets/images/sss/default-course_1.jpeg'
import { useIndividualCourseCart } from '@/Composables/useIndividualCourseCart'

defineOptions({
  layout: LayoutAnonyme,
})

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },

  relatedCourses: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()

const authUser = computed(() => {
  return page.props.auth?.user || null
})

const {
  isCourseInCart,
  addCourse,
} = useIndividualCourseCart()

const goToCart = () => {
  if (authUser.value) {
    router.visit(route('public.individual-requests.create'))
    return
  }

  router.visit(route('learner.register'))
}

const handleCourseCart = () => {
  if (isCourseInCart(props.course.id)) {
    goToCart()
    return
  }

  addCourse(props.course)

  if (!authUser.value) {
    router.visit(route('learner.register'))
  }
}

const pageRoot = ref(null)
let revealObserver = null

const activeTab = ref('overview')

const openChapters = ref(
  props.course.chapters?.length
    ? [props.course.chapters[0].id]
    : []
)

const tabs = [
  {
    key: 'overview',
    labelKey: 't-overview',
    icon: 'ri-file-text-line',
  },
  // {
  //   key: 'learning',
  //   labelKey: 't-what-you-will-learn',
  //   icon: 'ri-lightbulb-flash-line',
  // },
  // {
  //   key: 'prerequisites',
  //   labelKey: 't-prerequisites',
  //   icon: 'ri-shield-check-line',
  // },
  // {
  //   key: 'curriculum',
  //   labelKey: 't-curriculum',
  //   icon: 'ri-list-check-3',
  // },
]

const courseImage = computed(() => {
  return props.course.image_url || defaultCourseImage
})

const isScormCourse = computed(() => {
  return String(props.course.content_type || 'normal').toLowerCase() === 'scorm'
})

const durationCount = computed(() => {
  return Number(props.course.duration_hours || 0)
})

const formattedDurationValue = computed(() => {
  const duration = durationCount.value

  if (!duration) return '-'

  return Number.isInteger(duration)
    ? String(duration)
    : duration.toFixed(2).replace(/\.?0+$/, '')
})

const durationTranslationKey = computed(() => {
  return durationCount.value === 1
    ? 't-hour-singular'
    : 't-hour-plural'
})

const formattedPassingScore = computed(() => {
  const score = Number(props.course.passing_score)

  if (Number.isNaN(score)) return '-'

  return `${score.toFixed(2).replace(/\.?0+$/, '')}%`
})

const hasSyllabus = computed(() => {
  return Boolean(props.course.syllabus_url)
})

const totalChapters = computed(() => {
  return Number(
    props.course.chapters_count ||
    props.course.chapters?.length ||
    0
  )
})

const totalLessons = computed(() => {
  return Number(
    props.course.lessons_count ||
    props.course.chapters?.reduce(
      (total, chapter) => {
        return total + Number(
          chapter.slides_count ||
          chapter.slides?.length ||
          0
        )
      },
      0
    ) ||
    0
  )
})

const chapterTranslationKey = computed(() => {
  return totalChapters.value === 1
    ? 't-chapter-singular'
    : 't-chapter-plural'
})

const lessonTranslationKey = computed(() => {
  return totalLessons.value === 1
    ? 't-lesson-singular'
    : 't-lesson-plural'
})

const learningTopics = computed(() => {
  return (props.course.chapters || [])
    .map(chapter => chapter.title)
    .filter(Boolean)
    .slice(0, 8)
})

const countTranslationKey = (count, singularKey, pluralKey) => {
  return Number(count) === 1 ? singularKey : pluralKey
}

const formatLabel = value => {
  if (!value) return '-'

  return String(value)
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\b\w/g, character => character.toUpperCase())
}

const isFreeCourse = course => {
  return !course?.price || Number(course.price) <= 0
}

const formatPrice = course => {
  const amount = Number(course?.price || 0).toFixed(2)

  if (course?.currency === 'EUR') {
    return `€ ${amount.replace('.', ',')}`
  }

  return `${amount} ${course?.currency || 'USD'}`
}

const handleImageError = event => {
  const image = event?.currentTarget

  if (!image || image.dataset.fallbackApplied === 'true') {
    return
  }

  image.dataset.fallbackApplied = 'true'
  image.src = defaultCourseImage
}

const toggleChapter = chapterId => {
  if (openChapters.value.includes(chapterId)) {
    openChapters.value = openChapters.value.filter(id => id !== chapterId)
    return
  }

  openChapters.value.push(chapterId)
}

const isChapterOpen = chapterId => {
  return openChapters.value.includes(chapterId)
}

const lessonIcon = lessonType => {
  const icons = {
    content: 'ri-file-text-line',
    video: 'ri-video-line',
    file: 'ri-attachment-2',
    quiz: 'ri-question-answer-line',
  }

  return icons[lessonType] || 'ri-file-list-3-line'
}

const relatedCourseUrl = course => {
  return `/courses/${course.hashid}`
}

const shortText = (text, maximumLength = 105) => {
  if (!text) return ''

  return text.length > maximumLength
    ? `${text.substring(0, maximumLength)}...`
    : text
}

onMounted(() => {
  const root = pageRoot.value

  if (!root) return

  const items = Array.from(root.querySelectorAll('[data-reveal]'))
  const reduceMotion = window.matchMedia?.(
    '(prefers-reduced-motion: reduce)',
  )?.matches

  if (reduceMotion || !('IntersectionObserver' in window)) {
    items.forEach(item => item.classList.add('is-visible'))
    return
  }

  root.classList.add('motion-enabled')

  revealObserver = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return

        entry.target.classList.add('is-visible')
        revealObserver?.unobserve(entry.target)
      })
    },
    {
      threshold: 0.12,
      rootMargin: '0px 0px -55px 0px',
    },
  )

  items.forEach(item => revealObserver.observe(item))
})

onBeforeUnmount(() => {
  revealObserver?.disconnect()
  revealObserver = null
})
</script>

<template>
  <Head :title="course.title" />

  <div ref="pageRoot" class="course-details-page">
    <section class="course-details-hero">
      <div class="hero-container">
        <div class="hero-content hero-content-load">
          <div class="course-breadcrumb">
            <Link href="/">{{ $t('t-home') }}</Link>
            <i class="ri-arrow-right-s-line"></i>
            <Link href="/courses">{{ $t('t-courses') }}</Link>
            <i class="ri-arrow-right-s-line"></i>
            <strong>{{ course.category?.name || $t('t-course') }}</strong>
          </div>

          <span class="hero-category">
            <i :class="isScormCourse ? 'ri-code-box-line' : 'ri-flight-takeoff-line'"></i>
            {{ course.category?.name || $t('t-professional-training') }}

            <template v-if="course.ref">
              <span class="hero-category-separator">·</span>
              <span>#{{ course.ref }}</span>
            </template>
          </span>

          <h1>{{ course.title }}</h1>

          <p class="hero-description">
            {{ course.description || $t('t-course-default-description') }}
          </p>

          <div class="hero-meta">
            <span>
              <i :class="isScormCourse ? 'ri-code-box-line' : 'ri-book-open-line'"></i>

              <template v-if="isScormCourse">
                {{ $t('t-scorm-course') }}
              </template>

              <template v-else>
                {{ $t(chapterTranslationKey, { count: totalChapters }) }}
              </template>
            </span>

            <span v-if="course.method">
              <i class="ri-route-line"></i>
              {{ course.method }}
            </span>

            <span v-if="course.duration_hours">
              <i class="ri-time-line"></i>
              {{ $t(durationTranslationKey, { count: formattedDurationValue }) }}
            </span>

            <span v-if="course.skill_level">
              <i class="ri-bar-chart-line"></i>
              {{ formatLabel(course.skill_level) }}
            </span>
          </div>

          <div
            v-if="course.tags?.length"
            class="hero-tags"
          >
            <span
              v-for="tag in course.tags"
              :key="tag.id"
            >
              {{ tag.name }}
            </span>
          </div>
        </div>

        <aside class="hero-purchase-card hero-purchase-load">
          <div class="purchase-image">
            <img
              :src="courseImage"
              :alt="course.title"
              @error="handleImageError"
            >

            <span
              v-if="isScormCourse"
              class="purchase-voice-badge"
            >
              <i class="ri-code-box-line"></i>
              SCORM
            </span>

            <span
              v-else-if="course.support_voice_over"
              class="purchase-voice-badge"
            >
              <i class="ri-volume-up-line"></i>
              {{ $t('t-voice-over') }}
            </span>
          </div>

          <div class="purchase-content">
            <strong class="purchase-price">
              <template v-if="isFreeCourse(course)">
                {{ $t('t-free') }}
              </template>

              <template v-else>
                {{ formatPrice(course) }}
              </template>
            </strong>

            <p>{{ $t('t-course-access-description') }}</p>

            <a
              v-if="hasSyllabus"
              :href="course.syllabus_url"
              target="_blank"
              rel="noopener noreferrer"
              class="purchase-syllabus-button"
            >
              <i class="ri-file-pdf-2-line"></i>
              {{ $t('t-view-syllabus') }}
            </a>

            <button
              type="button"
              class="purchase-button"
              :class="{ 'is-added': isCourseInCart(course.id) }"
              @click="handleCourseCart"
            >
              <i
                :class="
                  isCourseInCart(course.id)
                    ? 'ri-shopping-cart-check-line'
                    : 'ri-shopping-cart-line'
                "
              ></i>
              {{
                isCourseInCart(course.id)
                  ? $t('t-view-cart')
                  : $t('t-add-to-cart')
              }}
            </button>

            <div class="purchase-guarantee">
              <i class="ri-shield-check-line"></i>
              <span>{{ $t('t-secure-professional-training-access') }}</span>
            </div>
          </div>
        </aside>
      </div>
    </section>

    <section class="course-main-section">
      <div class="course-main-column" data-reveal>
        <div class="course-tabs-card">
          <div class="course-tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              :class="{ active: activeTab === tab.key }"
              @click="activeTab = tab.key"
            >
              <i :class="tab.icon"></i>
              <span>{{ $t(tab.labelKey) }}</span>
            </button>
          </div>

          <div
            v-if="activeTab === 'overview'"
            class="tab-panel"
          >
            <div class="section-title">
              <span class="section-title-icon">
                <i class="ri-file-search-line"></i>
              </span>

              <div>
                <h2>{{ $t('t-course-overview') }}</h2>
                <p>{{ $t('t-course-overview-description') }}</p>
              </div>
            </div>

            <div class="description-content">
              {{ course.description || $t('t-course-no-detailed-description') }}
            </div>

            <div class="overview-grid">
              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-route-line"></i>
                </span>

                <div>
                  <small>{{ $t('t-method') }}</small>
                  <strong>{{ course.method || '-' }}</strong>
                </div>
              </div>

              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-computer-line"></i>
                </span>

                <div>
                  <small>{{ $t('t-training-type') }}</small>
                  <strong>{{ formatLabel(course.training_type) }}</strong>
                </div>
              </div>

              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-time-line"></i>
                </span>

                <div>
                  <small>{{ $t('t-duration') }}</small>
                  <strong>
                    <template v-if="formattedDurationValue === '-'">-</template>
                    <template v-else>
                      {{ $t(durationTranslationKey, { count: formattedDurationValue }) }}
                    </template>
                  </strong>
                </div>
              </div>

              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-percent-line"></i>
                </span>

                <div>
                  <small>{{ $t('t-passing-score') }}</small>
                  <strong>{{ formattedPassingScore }}</strong>
                </div>
              </div>

              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-focus-3-line"></i>
                </span>

                <div>
                  <small>{{ $t('t-course-focus') }}</small>
                  <strong>{{ formatLabel(course.course_focus) }}</strong>
                </div>
              </div>

              <div class="overview-item">
                <span class="overview-icon">
                  <i class="ri-translate-2"></i>
                </span>

                <div>
                  <small>{{ $t('t-language') }}</small>
                  <strong>{{ formatLabel(course.language) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div
            v-else-if="activeTab === 'learning'"
            class="tab-panel"
          >
            <div class="section-title">
              <span class="section-title-icon success">
                <i class="ri-lightbulb-flash-line"></i>
              </span>

              <div>
                <h2>{{ $t('t-what-you-will-learn') }}</h2>
                <p>{{ $t('t-learning-subjects-description') }}</p>
              </div>
            </div>

            <div
              v-if="learningTopics.length"
              class="learning-grid"
            >
              <div
                v-for="(topic, index) in learningTopics"
                :key="`${topic}-${index}`"
                class="learning-item"
              >
                <i class="ri-checkbox-circle-fill"></i>
                <span>{{ topic }}</span>
              </div>
            </div>

            <div
              v-else-if="isScormCourse"
              class="learning-grid"
            >
              <div class="learning-item">
                <i class="ri-checkbox-circle-fill"></i>
                <span>
                  {{ course.scorm_title || $t('t-interactive-scorm-training-package') }}
                </span>
              </div>

              <div class="learning-item">
                <i class="ri-checkbox-circle-fill"></i>
                <span>{{ course.method || $t('t-online-learning-content') }}</span>
              </div>

              <div class="learning-item">
                <i class="ri-checkbox-circle-fill"></i>
                <span>
                  {{ $t('t-passing-score-value', { score: formattedPassingScore }) }}
                </span>
              </div>
            </div>

            <div
              v-else
              class="content-empty-state"
            >
              <i class="ri-lightbulb-line"></i>
              <p>{{ $t('t-learning-objectives-soon') }}</p>
            </div>
          </div>

          <div
            v-else-if="activeTab === 'prerequisites'"
            class="tab-panel"
          >
            <div class="section-title">
              <span class="section-title-icon warning">
                <i class="ri-shield-check-line"></i>
              </span>

              <div>
                <h2>{{ $t('t-prerequisites') }}</h2>
                <p>{{ $t('t-prerequisites-description') }}</p>
              </div>
            </div>

            <div class="prerequisites-list">
              <div class="prerequisite-item">
                <i class="ri-checkbox-circle-line"></i>

                <div>
                  <strong>{{ $t('t-recommended-level') }}</strong>

                  <p>
                    {{
                      $t('t-course-designed-for-level', {
                        level: course.skill_level
                          ? formatLabel(course.skill_level)
                          : $t('t-all-levels'),
                      })
                    }}
                  </p>
                </div>
              </div>

              <div class="prerequisite-item">
                <i class="ri-checkbox-circle-line"></i>

                <div>
                  <strong>{{ $t('t-training-access') }}</strong>
                  <p>{{ $t('t-training-access-description') }}</p>
                </div>
              </div>

              <div class="prerequisite-item">
                <i class="ri-checkbox-circle-line"></i>

                <div>
                  <strong>{{ $t('t-course-completion') }}</strong>
                  <p>{{ $t('t-course-completion-description') }}</p>
                </div>
              </div>
            </div>
          </div>

          <div
            v-else
            class="tab-panel"
          >
            <div class="section-title">
              <span class="section-title-icon">
                <i class="ri-list-check-3"></i>
              </span>

              <div>
                <h2>{{ $t('t-course-curriculum') }}</h2>

                <p v-if="isScormCourse">
                  {{
                    $t('t-interactive-package-version', {
                      version: course.scorm_version || 'SCORM',
                    })
                  }}
                </p>

                <p v-else>
                  {{ $t(chapterTranslationKey, { count: totalChapters }) }}
                  {{ $t('t-and') }}
                  {{ $t(lessonTranslationKey, { count: totalLessons }) }}.
                </p>
              </div>
            </div>

            <div
              v-if="isScormCourse"
              class="scorm-curriculum-card"
            >
              <span class="scorm-curriculum-icon">
                <i class="ri-code-box-line"></i>
              </span>

              <div>
                <small>{{ $t('t-interactive-package') }}</small>
                <h3>{{ course.scorm_title || course.title }}</h3>

                <p>
                  {{ $t('t-scorm-delivery-description') }}
                  <template v-if="course.scorm_version">
                    ({{ course.scorm_version }})
                  </template>.
                </p>
              </div>
            </div>

            <div
              v-else-if="course.chapters?.length"
              class="curriculum-list"
            >
              <article
                v-for="(chapter, chapterIndex) in course.chapters"
                :key="chapter.id"
                class="chapter-card"
                :class="{ open: isChapterOpen(chapter.id) }"
              >
                <button
                  type="button"
                  class="chapter-header"
                  @click="toggleChapter(chapter.id)"
                >
                  <span class="chapter-number">
                    {{ chapterIndex + 1 }}
                  </span>

                  <span class="chapter-heading">
                    <strong>{{ chapter.title }}</strong>

                    <small>
                      {{
                        $t(
                          countTranslationKey(
                            chapter.slides_count || chapter.slides?.length || 0,
                            't-lesson-singular',
                            't-lesson-plural',
                          ),
                          {
                            count: chapter.slides_count || chapter.slides?.length || 0,
                          },
                        )
                      }}
                    </small>
                  </span>

                  <i
                    :class="
                      isChapterOpen(chapter.id)
                        ? 'ri-subtract-line'
                        : 'ri-add-line'
                    "
                  ></i>
                </button>

                <div
                  v-if="isChapterOpen(chapter.id)"
                  class="chapter-content"
                >
                  <p
                    v-if="chapter.description"
                    class="chapter-description"
                  >
                    {{ chapter.description }}
                  </p>

                  <div class="lessons-list">
                    <div
                      v-for="(slide, slideIndex) in chapter.slides"
                      :key="slide.id"
                      class="lesson-row"
                    >
                      <span class="lesson-icon">
                        <i :class="lessonIcon(slide.lesson_type)"></i>
                      </span>

                      <div class="lesson-info">
                        <strong>
                          {{ slideIndex + 1 }}. {{ slide.title }}
                        </strong>

                        <small>
                          {{ formatLabel(slide.lesson_type || $t('t-lesson')) }}
                        </small>
                      </div>

                      <span
                        v-if="slide.is_preview"
                        class="preview-badge"
                      >
                        {{ $t('t-preview') }}
                      </span>

                      <i
                        v-else
                        class="ri-lock-line lesson-lock"
                      ></i>
                    </div>
                  </div>
                </div>
              </article>
            </div>

            <div
              v-else
              class="content-empty-state"
            >
              <i class="ri-file-list-3-line"></i>
              <p>{{ $t('t-curriculum-soon') }}</p>
            </div>
          </div>
        </div>
      </div>

      <aside class="course-information-card" data-reveal style="--reveal-delay: 90ms">
        <div class="information-heading">
          <span>
            <i class="ri-information-line"></i>
          </span>

          <div>
            <h2>{{ $t('t-course-information') }}</h2>
            <p>{{ $t('t-course-information-description') }}</p>
          </div>
        </div>

        <div class="information-list">
          <div class="information-row">
            <span>
              <i class="ri-route-line"></i>
              {{ $t('t-method') }}
            </span>

            <strong>{{ course.method || '-' }}</strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-time-line"></i>
              {{ $t('t-duration') }}
            </span>

            <strong>
              <template v-if="formattedDurationValue === '-'">-</template>
              <template v-else>
                {{ $t(durationTranslationKey, { count: formattedDurationValue }) }}
              </template>
            </strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-percent-line"></i>
              {{ $t('t-passing-score') }}
            </span>

            <strong>{{ formattedPassingScore }}</strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-folder-line"></i>
              {{ $t('t-category') }}
            </span>

            <strong>{{ course.category?.name || '-' }}</strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-bar-chart-line"></i>
              {{ $t('t-course-type') }}
            </span>

            <strong>{{ formatLabel(course.skill_level) }}</strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-translate-2"></i>
              {{ $t('t-language') }}
            </span>

            <strong>{{ formatLabel(course.language) }}</strong>
          </div>

          <div class="information-row">
            <span>
              <i class="ri-computer-line"></i>
              {{ $t('t-training') }}
            </span>

            <strong>{{ formatLabel(course.training_type) }}</strong>
          </div>

          <template v-if="isScormCourse"></template>

          <template v-else>
            <div class="information-row">
              <span>
                <i class="ri-book-open-line"></i>
                {{ $t('t-chapters') }}
              </span>

              <strong>{{ totalChapters }}</strong>
            </div>

            <div class="information-row">
              <span>
                <i class="ri-file-list-3-line"></i>
                {{ $t('t-lessons') }}
              </span>

              <strong>{{ totalLessons }}</strong>
            </div>

            <div class="information-row">
              <span>
                <i class="ri-volume-up-line"></i>
                {{ $t('t-voice-over') }}
              </span>

              <strong>
                {{
                  course.support_voice_over
                    ? $t('t-included')
                    : $t('t-not-included')
                }}
              </strong>
            </div>
          </template>

          <div
            v-if="hasSyllabus"
            class="information-row"
          >
            <span>
              <i class="ri-file-pdf-2-line"></i>
              {{ $t('t-syllabus') }}
            </span>

            <a
              :href="course.syllabus_url"
              target="_blank"
              rel="noopener noreferrer"
              class="information-file-link"
            >
              {{ $t('t-view-pdf') }}
              <i class="ri-external-link-line"></i>
            </a>
          </div>
        </div>

        <button
          type="button"
          class="sidebar-enroll-button"
          :class="{ 'is-added': isCourseInCart(course.id) }"
          @click="handleCourseCart"
        >
          <i
            :class="
              isCourseInCart(course.id)
                ? 'ri-shopping-cart-check-line'
                : 'ri-shopping-cart-line'
            "
          ></i>
          {{
            isCourseInCart(course.id)
              ? $t('t-view-cart')
              : $t('t-add-to-cart')
          }}
        </button>
      </aside>
    </section>

    <section
      v-if="relatedCourses.length"
      class="related-section"
      data-reveal
    >
      <div class="related-heading">
        <div>
          <span>{{ $t('t-continue-learning') }}</span>
          <h2>{{ $t('t-also-available-category') }}</h2>
        </div>

        <Link
          href="/courses"
          class="all-courses-link"
        >
          {{ $t('t-view-all-courses') }}
          <i class="ri-arrow-right-line"></i>
        </Link>
      </div>

      <div class="related-grid">
        <article
          v-for="(relatedCourse, index) in relatedCourses"
          :key="relatedCourse.id"
          class="related-card"
          data-reveal
          :style="{ '--reveal-delay': `${index * 70}ms` }"
        >
          <Link
            :href="relatedCourseUrl(relatedCourse)"
            class="related-cover"
          >
            <img
              :src="relatedCourse.image_url || defaultCourseImage"
              :alt="relatedCourse.title"
              @error="handleImageError"
            >

            <span>
              {{ relatedCourse.category?.name || $t('t-course') }}
            </span>
          </Link>

          <div class="related-body">
            <Link
              :href="relatedCourseUrl(relatedCourse)"
              class="related-title"
            >
              {{ relatedCourse.title }}
            </Link>

            <p>
              {{
                shortText(relatedCourse.description) ||
                $t('t-no-description-available')
              }}
            </p>

            <div class="related-meta">
              <span v-if="relatedCourse.training_type">
                <i class="ri-computer-line"></i>
                {{ formatLabel(relatedCourse.training_type) }}
              </span>

              <span v-if="relatedCourse.skill_level">
                <i class="ri-bar-chart-line"></i>
                {{ formatLabel(relatedCourse.skill_level) }}
              </span>

              <span v-if="relatedCourse.method">
                <i class="ri-route-line"></i>
                {{ relatedCourse.method }}
              </span>

              <span v-if="relatedCourse.duration_hours">
                <i class="ri-time-line"></i>
                {{ Number(relatedCourse.duration_hours) }}h
              </span>
            </div>
          </div>

          <div class="related-footer">
            <strong>
              <template v-if="isFreeCourse(relatedCourse)">
                {{ $t('t-free') }}
              </template>

              <template v-else>
                {{ formatPrice(relatedCourse) }}
              </template>
            </strong>

            <Link :href="relatedCourseUrl(relatedCourse)">
              {{ $t('t-view-course') }}
              <i class="ri-arrow-right-line"></i>
            </Link>
          </div>
        </article>
      </div>
    </section>

    <div class="mobile-course-action">
      <div>
        <small>{{ $t('t-course-price') }}</small>

        <strong>
          <template v-if="isFreeCourse(course)">
            {{ $t('t-free') }}
          </template>

          <template v-else>
            {{ formatPrice(course) }}
          </template>
        </strong>
      </div>

      <button
        type="button"
        :class="{ 'is-added': isCourseInCart(course.id) }"
        @click="handleCourseCart"
      >
        <i
          :class="
            isCourseInCart(course.id)
              ? 'ri-shopping-cart-check-line'
              : 'ri-shopping-cart-line'
          "
        ></i>
        {{
          isCourseInCart(course.id)
            ? $t('t-view-cart')
            : $t('t-add-to-cart')
        }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.course-details-page {
  --course-cyan: #37cced;
  --course-blue: #0d8fe9;
  --course-blue-dark: #087bc9;
  --course-navy: #071126;
  --course-navy-hover: #132b4a;
  --course-soft: #eaf7ff;
  --course-page-soft: #f4fbff;
  --course-border: #e4edf4;
  --course-text: #334155;
  --course-muted: #64748b;
  --course-muted-light: #94a3b8;
  --course-success: #0ab39c;
  --course-warning: #f7b84b;
  --course-danger: #f06548;
  --layout-footer-height: 62px;
  --main-gradient: linear-gradient(
    90deg,
    var(--course-cyan) 0%,
    var(--course-blue) 100%
  );
  --card-shadow: 0 16px 38px rgba(15, 23, 42, 0.075);
  --card-shadow-hover: 0 24px 52px rgba(15, 23, 42, 0.12);

  min-height: 100vh;
  padding-bottom: calc(var(--layout-footer-height) + 58px);
  overflow: hidden;
  color: var(--course-navy);
  background: linear-gradient(
    180deg,
    #f4fbff 0%,
    #ffffff 46%,
    #f8fafc 100%
  );
}

.course-details-page,
.course-details-page * {
  box-sizing: border-box;
}

.course-details-page a:focus-visible,
.course-details-page button:focus-visible {
  outline: 3px solid rgba(55, 204, 237, 0.42);
  outline-offset: 3px;
}

/* =========================================================
   HERO
   ========================================================= */
.course-details-hero {
  position: relative;
  overflow: hidden;
  padding: 62px 24px 78px;
  color: #ffffff;
  background:
    linear-gradient(
      105deg,
      rgba(7, 17, 38, 0.28) 0%,
      rgba(7, 17, 38, 0.13) 43%,
      rgba(7, 17, 38, 0.02) 75%,
      transparent 100%
    ),
    var(--main-gradient);
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.2);
}

.course-details-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.15;
  background-image:
    linear-gradient(
      90deg,
      transparent 0%,
      transparent 18%,
      rgba(255, 255, 255, 0.22) 18.1%,
      transparent 18.2%,
      transparent 83%,
      rgba(255, 255, 255, 0.18) 83.1%,
      transparent 83.2%
    ),
    linear-gradient(
      180deg,
      transparent 0%,
      transparent 73%,
      rgba(255, 255, 255, 0.18) 73.1%,
      transparent 73.2%
    );
}

.course-details-hero::after {
  content: '';
  position: absolute;
  right: -10%;
  bottom: 13%;
  width: 54%;
  height: 1px;
  pointer-events: none;
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(-10deg);
  transform-origin: right center;
}

.hero-container {
  position: relative;
  z-index: 2;
  width: min(1320px, 100%);
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 390px;
  align-items: center;
  gap: clamp(44px, 6vw, 82px);
}

.hero-content {
  min-width: 0;
  max-width: 850px;
}

.course-breadcrumb {
  width: fit-content;
  max-width: 100%;
  min-height: 34px;
  margin-bottom: 20px;
  padding: 7px 12px;
  border: 1px solid rgba(255, 255, 255, 0.56);
  border-radius: 999px;
  color: var(--course-navy);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.1);
  font-size: 10.5px;
  font-weight: 850;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
}

.course-breadcrumb a {
  color: var(--course-navy);
  text-decoration: none;
  transition: color 0.2s ease;
}

.course-breadcrumb a:hover,
.course-breadcrumb strong {
  color: var(--course-blue-dark);
}

.course-breadcrumb strong {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hero-category {
  width: fit-content;
  max-width: 100%;
  min-height: 36px;
  margin-bottom: 18px;
  padding: 0 13px;
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 10px;
  color: var(--course-navy);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.1);
  font-size: 10.5px;
  font-weight: 900;
  letter-spacing: 0.035em;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-category > i {
  color: var(--course-blue);
  font-size: 16px;
}

.hero-category-separator {
  opacity: 0.45;
}

.hero-content h1 {
  max-width: 860px;
  margin: 0 0 19px;
  color: #ffffff;
  font-size: clamp(42px, 4.7vw, 66px);
  line-height: 1.035;
  letter-spacing: -0.052em;
  font-weight: 950;
  text-wrap: balance;
  text-shadow: 0 10px 28px rgba(7, 17, 38, 0.12);
}

.hero-description {
  max-width: 780px;
  margin: 0 0 25px;
  color: rgba(255, 255, 255, 0.95);
  font-size: 15.5px;
  line-height: 1.78;
  font-weight: 550;
}

.hero-meta {
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.hero-meta span {
  min-height: 35px;
  padding: 0 11px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
  color: #ffffff;
  background: rgba(7, 17, 38, 0.2);
  box-shadow: 0 8px 20px rgba(7, 17, 38, 0.07);
  backdrop-filter: blur(8px);
  font-size: 10.5px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.hero-meta i {
  font-size: 15px;
}

.hero-tags {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
}

.hero-tags span {
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid rgba(255, 255, 255, 0.34);
  border-radius: 9px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.13);
  font-size: 9.5px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
}

/* =========================================================
   PURCHASE CARD
   ========================================================= */
.hero-purchase-card {
  position: relative;
  width: 100%;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 28px 64px rgba(7, 17, 38, 0.22);
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
}

.hero-purchase-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 20px;
  left: 20px;
  z-index: 4;
  height: 4px;
  border-radius: 0 0 999px 999px;
  background: var(--main-gradient);
}

.hero-purchase-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 34px 72px rgba(7, 17, 38, 0.27);
}

.purchase-image {
  position: relative;
  height: 222px;
  overflow: hidden;
  background: #eaf4fa;
}

.purchase-image::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    rgba(7, 17, 38, 0.02) 40%,
    rgba(7, 17, 38, 0.38) 100%
  );
}

.purchase-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.65s cubic-bezier(0.16, 1, 0.3, 1);
}

.hero-purchase-card:hover .purchase-image img {
  transform: scale(1.055);
}

.purchase-voice-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 2;
  min-height: 34px;
  padding: 0 11px;
  border: 1px solid rgba(255, 255, 255, 0.82);
  border-radius: 10px;
  color: var(--course-blue-dark);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 8px 18px rgba(7, 17, 38, 0.14);
  font-size: 9.5px;
  font-weight: 900;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.purchase-content {
  padding: 23px;
}

.purchase-price {
  display: block;
  margin-bottom: 9px;
  color: var(--course-navy);
  font-size: 30px;
  line-height: 1;
  letter-spacing: -0.035em;
  font-weight: 950;
}

.purchase-content p {
  margin: 0 0 18px;
  color: #526174;
  font-size: 11.5px;
  line-height: 1.65;
}

.purchase-syllabus-button,
.purchase-button {
  border: none;
  cursor: pointer;
  width: 100%;
  min-height: 48px;
  border-radius: 13px;
  text-decoration: none;
  font-size: 11.5px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition:
    transform 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.2s ease;
}

.purchase-syllabus-button {
  margin-bottom: 10px;
  border: 1px solid rgba(13, 143, 233, 0.22);
  color: var(--course-blue-dark);
  background: var(--course-soft);
}

.purchase-syllabus-button:hover {
  color: var(--course-blue-dark);
  border-color: rgba(13, 143, 233, 0.4);
  background: #dff4ff;
  transform: translateY(-1px);
}

.purchase-button {
  border: 1px solid var(--course-navy);
  color: #ffffff;
  background: var(--course-navy);
  box-shadow: 0 13px 28px rgba(7, 17, 38, 0.2);
}

.purchase-button:hover {
  color: #ffffff;
  border-color: var(--course-navy-hover);
  background: var(--course-navy-hover);
  box-shadow: 0 17px 34px rgba(7, 17, 38, 0.27);
  transform: translateY(-2px);
}

.purchase-guarantee {
  margin-top: 15px;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 750;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.purchase-guarantee i {
  color: var(--course-success);
  font-size: 15px;
}

/* =========================================================
   MAIN LAYOUT
   ========================================================= */
.course-main-section {
  width: min(1320px, calc(100% - 40px));
  margin: 30px auto 0;
  padding-bottom: 54px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  align-items: start;
  gap: 24px;
}

.course-main-column {
  min-width: 0;
}

.course-tabs-card,
.course-information-card {
  position: relative;
  border: 1px solid var(--course-border);
  border-radius: 22px;
  background: #ffffff;
  box-shadow: var(--card-shadow);
}

.course-tabs-card::before,
.course-information-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 20px;
  left: 20px;
  z-index: 3;
  height: 3px;
  border-radius: 0 0 999px 999px;
  background: var(--main-gradient);
  pointer-events: none;
}

.course-tabs-card {
  overflow: hidden;
}

.course-tabs {
  min-height: 72px;
  padding: 14px 20px 13px;
  border-bottom: 1px solid var(--course-border);
  background: linear-gradient(180deg, #ffffff 0%, #f7fcff 100%);
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 9px;
}

.course-tabs button {
  width: auto;
  min-width: 152px;
  min-height: 43px;
  flex: 0 0 auto;
  padding: 0 17px;
  border: 1px solid transparent;
  border-radius: 12px;
  color: var(--course-muted);
  background: transparent;
  font-size: 10.5px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.course-tabs button:hover:not(.active) {
  color: var(--course-blue-dark);
  border-color: rgba(13, 143, 233, 0.18);
  background: var(--course-soft);
}

.course-tabs button.active {
  color: #ffffff;
  border-color: transparent;
  background: var(--main-gradient);
  box-shadow: 0 9px 22px rgba(13, 143, 233, 0.22);
}

.course-tabs button i {
  font-size: 15px;
}

.tab-panel {
  padding: 31px 32px 34px;
}

.section-title {
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.section-title-icon {
  width: 50px;
  height: 50px;
  flex: 0 0 50px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 14px;
  color: #ffffff;
  background: var(--main-gradient);
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.18);
  font-size: 21px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.section-title-icon.success {
  color: #ffffff;
  border-color: rgba(10, 179, 156, 0.3);
  background: linear-gradient(135deg, #15c7ad, #0a8f7e);
}

.section-title-icon.warning {
  color: #ffffff;
  border-color: rgba(247, 184, 75, 0.35);
  background: linear-gradient(135deg, #f7c660, #d99a20);
}

.section-title h2 {
  margin: 0 0 4px;
  color: var(--course-navy);
  font-size: 22px;
  line-height: 1.25;
  letter-spacing: -0.02em;
  font-weight: 950;
}

.section-title p {
  margin: 0;
  color: var(--course-muted-light);
  font-size: 10.5px;
  font-weight: 750;
}

.description-content {
  padding: 2px 1px;
  color: var(--course-text);
  font-size: 13.5px;
  line-height: 1.86;
  white-space: pre-line;
}

/* =========================================================
   OVERVIEW
   ========================================================= */
.overview-grid {
  margin-top: 27px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.overview-item {
  min-width: 0;
  min-height: 88px;
  padding: 16px;
  border: 1px solid #e2edf5;
  border-radius: 15px;
  background: linear-gradient(180deg, #ffffff 0%, #f9fcfe 100%);
  box-shadow: 0 7px 20px rgba(15, 23, 42, 0.035);
  display: flex;
  align-items: center;
  gap: 12px;
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.overview-item:hover {
  border-color: rgba(13, 143, 233, 0.3);
  box-shadow: 0 14px 28px rgba(13, 143, 233, 0.1);
  transform: translateY(-3px);
}

.overview-icon {
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  border: 1px solid rgba(13, 143, 233, 0.15);
  border-radius: 12px;
  color: var(--course-blue-dark);
  background: var(--course-soft);
  font-size: 19px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.overview-item > div {
  min-width: 0;
}

.overview-item small {
  display: block;
  margin-bottom: 4px;
  color: var(--course-muted-light);
  font-size: 9.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.045em;
}

.overview-item strong {
  display: block;
  overflow: hidden;
  color: var(--course-navy);
  font-size: 12px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* =========================================================
   LEARNING / PREREQUISITES
   ========================================================= */
.learning-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.learning-item {
  min-height: 64px;
  padding: 14px 15px;
  border: 1px solid rgba(10, 179, 156, 0.18);
  border-radius: 14px;
  color: #1e293b;
  background: rgba(10, 179, 156, 0.055);
  font-size: 11.5px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 10px;
}

.learning-item i {
  flex: 0 0 auto;
  color: var(--course-success);
  font-size: 17px;
}

.prerequisites-list {
  display: grid;
  gap: 11px;
}

.prerequisite-item {
  padding: 16px;
  border: 1px solid var(--course-border);
  border-radius: 15px;
  background: #fbfdff;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.prerequisite-item > i {
  flex: 0 0 auto;
  color: var(--course-success);
  font-size: 20px;
}

.prerequisite-item strong {
  display: block;
  margin-bottom: 4px;
  color: var(--course-navy);
  font-size: 12.5px;
  font-weight: 900;
}

.prerequisite-item p {
  margin: 0;
  color: var(--course-muted);
  font-size: 11.5px;
  line-height: 1.58;
}

.content-empty-state {
  padding: 52px 20px;
  color: var(--course-muted-light);
  text-align: center;
}

.content-empty-state i {
  display: block;
  margin-bottom: 11px;
  color: var(--course-blue-dark);
  font-size: 38px;
}

.content-empty-state p {
  margin: 0;
  font-size: 12px;
}

/* =========================================================
   CURRICULUM
   ========================================================= */
.scorm-curriculum-card {
  min-height: 150px;
  padding: 22px;
  border: 1px solid rgba(13, 143, 233, 0.2);
  border-radius: 18px;
  background: linear-gradient(135deg, var(--course-soft), #ffffff);
  display: flex;
  align-items: center;
  gap: 18px;
}

.scorm-curriculum-icon {
  width: 62px;
  height: 62px;
  flex: 0 0 62px;
  border-radius: 17px;
  color: #ffffff;
  background: var(--main-gradient);
  box-shadow: 0 11px 24px rgba(13, 143, 233, 0.22);
  font-size: 27px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.scorm-curriculum-card small {
  color: var(--course-blue-dark);
  font-size: 9.5px;
  font-weight: 900;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.scorm-curriculum-card h3 {
  margin: 5px 0 7px;
  color: var(--course-navy);
  font-size: 17px;
  font-weight: 900;
}

.scorm-curriculum-card p {
  margin: 0;
  color: var(--course-muted);
  font-size: 11.5px;
  line-height: 1.6;
}

.curriculum-list {
  display: grid;
  gap: 11px;
}

.chapter-card {
  overflow: hidden;
  border: 1px solid var(--course-border);
  border-radius: 16px;
  background: #ffffff;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.chapter-card.open {
  border-color: rgba(13, 143, 233, 0.32);
  box-shadow: 0 13px 28px rgba(13, 143, 233, 0.09);
}

.chapter-header {
  width: 100%;
  min-height: 74px;
  padding: 14px 16px;
  border: 0;
  color: var(--course-navy);
  background: #ffffff;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.chapter-header:hover {
  background: #f7fcff;
}

.chapter-number {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  border-radius: 12px;
  color: #ffffff;
  background: var(--main-gradient);
  box-shadow: 0 8px 18px rgba(13, 143, 233, 0.2);
  font-size: 13px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.chapter-heading {
  flex: 1;
  min-width: 0;
}

.chapter-heading strong {
  display: block;
  margin-bottom: 3px;
  overflow: hidden;
  color: var(--course-navy);
  font-size: 13.5px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chapter-heading small {
  color: var(--course-muted-light);
  font-size: 9.5px;
  font-weight: 750;
}

.chapter-header > i {
  flex: 0 0 auto;
  color: var(--course-blue-dark);
  font-size: 20px;
}

.chapter-content {
  padding: 0 16px 16px;
  border-top: 1px solid var(--course-border);
}

.chapter-description {
  margin: 14px 0;
  color: var(--course-muted);
  font-size: 11.5px;
  line-height: 1.62;
}

.lessons-list {
  margin-top: 12px;
  display: grid;
  gap: 8px;
}

.lesson-row {
  min-height: 58px;
  padding: 10px 12px;
  border: 1px solid transparent;
  border-radius: 12px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  gap: 11px;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease;
}

.lesson-row:hover {
  border-color: rgba(13, 143, 233, 0.18);
  background: #f3faff;
}

.lesson-icon {
  width: 35px;
  height: 35px;
  flex: 0 0 35px;
  border: 1px solid rgba(13, 143, 233, 0.12);
  border-radius: 10px;
  color: var(--course-blue-dark);
  background: var(--course-soft);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.lesson-info {
  flex: 1;
  min-width: 0;
}

.lesson-info strong {
  display: block;
  margin-bottom: 2px;
  overflow: hidden;
  color: var(--course-navy);
  font-size: 11.5px;
  font-weight: 850;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lesson-info small {
  color: var(--course-muted-light);
  font-size: 8.5px;
  font-weight: 750;
}

.preview-badge {
  padding: 6px 9px;
  border-radius: 999px;
  color: #087f6f;
  background: rgba(10, 179, 156, 0.11);
  font-size: 8.5px;
  font-weight: 900;
}

.lesson-lock {
  color: var(--course-muted-light);
}

/* =========================================================
   INFORMATION SIDEBAR
   ========================================================= */
.course-information-card {
  position: sticky;
  top: 94px;
  align-self: start;
  overflow: hidden;
}

.information-heading {
  min-height: 88px;
  padding: 22px 22px 18px;
  border-bottom: 1px solid var(--course-border);
  background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
  display: flex;
  align-items: center;
  gap: 12px;
}

.information-heading > span {
  width: 47px;
  height: 47px;
  flex: 0 0 47px;
  border-radius: 13px;
  color: #ffffff;
  background: var(--main-gradient);
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.2);
  font-size: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.information-heading h2 {
  margin: 0 0 3px;
  color: var(--course-navy);
  font-size: 16px;
  font-weight: 900;
}

.information-heading p {
  margin: 0;
  color: var(--course-muted-light);
  font-size: 9.5px;
  font-weight: 700;
}

.information-list {
  padding: 0 22px;
  display: grid;
}

.information-row {
  min-height: 58px;
  border-bottom: 1px solid var(--course-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.information-row span {
  min-width: 0;
  color: var(--course-muted);
  font-size: 10.5px;
  font-weight: 750;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.information-row span i {
  flex: 0 0 auto;
  color: var(--course-blue-dark);
  font-size: 15px;
}

.information-row strong {
  max-width: 52%;
  overflow-wrap: anywhere;
  color: var(--course-navy);
  font-size: 10.5px;
  font-weight: 900;
  text-align: right;
}

.information-file-link {
  color: var(--course-blue-dark);
  font-size: 10.5px;
  font-weight: 900;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.information-file-link:hover {
  color: var(--course-blue);
}

.sidebar-enroll-button {
  border: none;
  cursor: pointer;
  width: calc(100% - 44px);
  min-height: 48px;
  margin: 20px 22px 22px;
  border-radius: 13px;
  color: #ffffff;
  background: var(--course-navy);
  box-shadow: 0 12px 26px rgba(7, 17, 38, 0.2);
  text-decoration: none;
  font-size: 11.5px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  transition:
    transform 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.2s ease;
}

.sidebar-enroll-button:hover {
  color: #ffffff;
  background: var(--course-navy-hover);
  box-shadow: 0 16px 32px rgba(7, 17, 38, 0.27);
  transform: translateY(-2px);
}

/* =========================================================
   RELATED COURSES
   ========================================================= */
.related-section {
  width: min(1320px, calc(100% - 40px));
  margin: 2px auto 0;
  padding-bottom: 48px;
}

.related-heading {
  margin-bottom: 22px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 18px;
}

.related-heading span {
  display: block;
  margin-bottom: 5px;
  color: var(--course-blue-dark);
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.related-heading h2 {
  margin: 0;
  color: var(--course-navy);
  font-size: 28px;
  letter-spacing: -0.025em;
  font-weight: 950;
}

.all-courses-link {
  min-height: 41px;
  padding: 0 14px;
  border: 1px solid var(--course-border);
  border-radius: 12px;
  color: var(--course-navy);
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.045);
  font-size: 10.5px;
  font-weight: 900;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  transition:
    transform 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.all-courses-link:hover {
  color: var(--course-blue-dark);
  border-color: rgba(13, 143, 233, 0.28);
  background: var(--course-soft);
  transform: translateY(-2px);
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.related-card {
  position: relative;
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--course-border);
  border-radius: 21px;
  background: #ffffff;
  box-shadow: var(--card-shadow);
  display: flex;
  flex-direction: column;
  transition:
    transform 0.25s ease,
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.related-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  z-index: 3;
  height: 4px;
  background: var(--main-gradient);
  transform: scaleX(0.3);
  transform-origin: left center;
  transition: transform 0.25s ease;
}

.related-card:hover {
  border-color: rgba(13, 143, 233, 0.32);
  box-shadow: var(--card-shadow-hover);
  transform: translateY(-6px);
}

.related-card:hover::before {
  transform: scaleX(1);
}

.related-cover {
  position: relative;
  height: 190px;
  overflow: hidden;
  background: #eef5fa;
  text-decoration: none;
  display: block;
}

.related-cover::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    transparent 45%,
    rgba(7, 17, 38, 0.38) 100%
  );
}

.related-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.55s cubic-bezier(0.16, 1, 0.3, 1);
}

.related-card:hover .related-cover img {
  transform: scale(1.055);
}

.related-cover span {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 2;
  max-width: calc(100% - 28px);
  min-height: 31px;
  padding: 0 10px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.75);
  border-radius: 10px;
  color: var(--course-navy);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 7px 16px rgba(7, 17, 38, 0.1);
  font-size: 9.5px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
}

.related-body {
  flex: 1;
  padding: 19px 19px 14px;
}

.related-title {
  display: block;
  margin-bottom: 8px;
  color: var(--course-navy);
  font-size: 17px;
  line-height: 1.35;
  font-weight: 900;
  text-decoration: none;
  transition: color 0.2s ease;
}

.related-title:hover {
  color: var(--course-blue-dark);
}

.related-body p {
  min-height: 40px;
  margin: 0 0 14px;
  color: #526174;
  font-size: 10.5px;
  line-height: 1.58;
}

.related-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.related-meta span {
  min-height: 28px;
  padding: 0 8px;
  border: 1px solid var(--course-border);
  border-radius: 999px;
  color: #475569;
  background: #f8fafc;
  font-size: 8.5px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.related-meta i {
  color: var(--course-blue-dark);
}

.related-footer {
  padding: 0 19px 19px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.related-footer strong {
  color: var(--course-navy);
  font-size: 18px;
  font-weight: 950;
}

.related-footer a {
  min-height: 40px;
  padding: 0 13px;
  border-radius: 11px;
  color: #ffffff;
  background: var(--course-navy);
  box-shadow: 0 9px 20px rgba(7, 17, 38, 0.16);
  font-size: 9.5px;
  font-weight: 900;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition:
    transform 0.2s ease,
    background-color 0.2s ease;
}

.related-footer a:hover {
  color: #ffffff;
  background: var(--course-navy-hover);
  transform: translateY(-2px);
}

/* =========================================================
   MOBILE ACTION
   ========================================================= */
.mobile-course-action {
  display: none;
}

/* =========================================================
   MOTION
   ========================================================= */
.hero-content-load {
  opacity: 0;
  animation: hero-copy-in 0.85s 0.08s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.hero-purchase-load {
  opacity: 0;
  animation: hero-card-in 0.95s 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.motion-enabled [data-reveal] {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
    transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: var(--reveal-delay, 0ms);
}

.motion-enabled [data-reveal].is-visible {
  opacity: 1;
  transform: translateY(0);
}

@keyframes hero-copy-in {
  from {
    opacity: 0;
    transform: translateY(25px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes hero-card-in {
  from {
    opacity: 0;
    transform: translateX(48px) translateY(12px) scale(0.97);
  }

  to {
    opacity: 1;
    transform: translateX(0) translateY(0) scale(1);
  }
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 1180px) {
  .hero-container {
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 38px;
  }

  .course-main-section {
    grid-template-columns: minmax(0, 1fr) 330px;
  }
}

@media (max-width: 991.98px) {
  .course-details-hero {
    padding: 48px 20px 60px;
  }

  .hero-container {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .hero-content {
    max-width: 820px;
  }

  .hero-content h1 {
    max-width: 820px;
    font-size: clamp(40px, 6.4vw, 54px);
  }

  .hero-purchase-card {
    display: grid;
    grid-template-columns: minmax(250px, 38%) minmax(0, 1fr);
  }

  .purchase-image {
    height: 100%;
    min-height: 250px;
  }

  .purchase-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 25px;
  }

  .course-main-section {
    width: calc(100% - 28px);
    margin-top: 23px;
    padding-bottom: 44px;
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .course-information-card {
    position: static;
  }

  .information-list {
    padding: 16px 22px 4px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 16px;
  }

  .information-row {
    min-height: 58px;
    padding: 0 12px;
    border: 1px solid var(--course-border);
    border-radius: 12px;
    background: #fbfdff;
  }

  .sidebar-enroll-button {
    max-width: 320px;
    margin-right: auto;
    margin-left: auto;
  }

  .related-section {
    width: calc(100% - 28px);
  }

  .related-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767.98px) {
  .course-details-page {
    padding-bottom: calc(
      var(--layout-footer-height) +
      28px +
      env(safe-area-inset-bottom)
    );
  }

  .course-details-hero {
    padding: 35px 15px 40px;
  }

  .course-breadcrumb {
    margin-bottom: 17px;
    font-size: 9.5px;
  }

  .hero-category {
    margin-bottom: 14px;
  }

  .hero-content h1 {
    margin-bottom: 15px;
    font-size: clamp(31px, 9vw, 40px);
    letter-spacing: -0.035em;
  }

  .hero-description {
    margin-bottom: 20px;
    font-size: 13px;
    line-height: 1.68;
  }

  .hero-meta {
    gap: 6px;
  }

  .hero-meta span {
    min-height: 32px;
    padding: 0 9px;
    font-size: 9.5px;
  }

  .hero-purchase-card {
    display: block;
    border-radius: 20px;
  }

  .purchase-image {
    height: 205px;
    min-height: 0;
  }

  .purchase-content {
    padding: 19px;
  }

  .purchase-price {
    font-size: 26px;
  }

  .course-main-section {
    width: calc(100% - 20px);
    margin-top: 18px;
    padding-bottom: 34px;
  }

  .course-tabs-card,
  .course-information-card {
    border-radius: 19px;
  }

  .course-tabs {
    min-height: auto;
    padding: 11px;
    overflow-x: auto;
    scrollbar-width: none;
  }

  .course-tabs::-webkit-scrollbar {
    display: none;
  }

  .course-tabs button {
    min-width: 145px;
    min-height: 42px;
  }

  .tab-panel {
    padding: 21px 18px 23px;
  }

  .section-title {
    margin-bottom: 20px;
    align-items: flex-start;
  }

  .section-title-icon {
    width: 44px;
    height: 44px;
    flex-basis: 44px;
  }

  .section-title h2 {
    font-size: 18.5px;
  }

  .description-content {
    font-size: 12.5px;
    line-height: 1.76;
  }

  .overview-grid,
  .learning-grid {
    grid-template-columns: 1fr;
  }

  .overview-item {
    min-height: 77px;
  }

  .overview-item strong {
    white-space: normal;
  }

  .scorm-curriculum-card {
    align-items: flex-start;
    flex-direction: column;
  }

  .chapter-heading strong,
  .lesson-info strong {
    white-space: normal;
  }

  .information-heading {
    padding: 19px 18px 16px;
  }

  .information-list {
    padding: 14px 18px 4px;
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .sidebar-enroll-button {
    display: none;
  }

  .related-section {
    width: calc(100% - 20px);
    padding-bottom: 26px;
  }

  .related-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 13px;
  }

  .related-heading h2 {
    font-size: 23px;
  }

  .related-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .mobile-course-action {
    position: static;
    inset: auto;
    z-index: auto;
    width: calc(100% - 20px);
    min-height: 82px;
    margin: 0 auto 28px;
    padding: 14px;
    border: 1px solid var(--course-border);
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    backdrop-filter: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
  }

  .mobile-course-action > div {
    min-width: 0;
    flex: 1 1 auto;
  }

  .mobile-course-action small {
    display: block;
    margin-bottom: 4px;
    color: var(--course-muted-light);
    font-size: 9px;
    font-weight: 850;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .mobile-course-action strong {
    display: block;
    overflow: hidden;
    color: var(--course-navy);
    font-size: 18px;
    font-weight: 950;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mobile-course-action button {
    flex: 0 0 auto;
    min-height: 46px;
    padding: 0 17px;
    border-radius: 13px;
    color: #ffffff;
    background: var(--course-navy);
    box-shadow: 0 10px 22px rgba(7, 17, 38, 0.2);
    text-decoration: none;
    font-size: 11px;
    font-weight: 900;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition:
      transform 0.2s ease,
      background-color 0.2s ease,
      box-shadow 0.2s ease;
  }

  .mobile-course-action button:hover {
    color: #ffffff;
    background: var(--course-navy-hover);
    box-shadow: 0 14px 28px rgba(7, 17, 38, 0.25);
    transform: translateY(-1px);
  }
}

@media (max-width: 480px) {
  .course-details-page {
    --layout-footer-height: 58px;
  }

  .course-breadcrumb {
    display: none;
  }

  .course-details-hero {
    padding-top: 29px;
  }

  .hero-content h1 {
    font-size: 30px;
  }

  .purchase-image {
    height: 187px;
  }

  .purchase-content {
    padding: 17px;
  }

  .tab-panel {
    padding: 18px 16px 20px;
  }

  .section-title {
    gap: 10px;
  }

  .section-title p {
    font-size: 9px;
  }

  .lesson-row {
    align-items: flex-start;
  }

  .preview-badge,
  .lesson-lock {
    margin-top: 7px;
  }

  .related-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .related-footer a {
    width: 100%;
    justify-content: center;
  }

  .mobile-course-action button {
    padding: 0 13px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-content-load,
  .hero-purchase-load {
    opacity: 1 !important;
    animation: none !important;
    transform: none !important;
  }

  .hero-purchase-card,
  .purchase-image img,
  .purchase-syllabus-button,
  .purchase-button,
  .course-tabs button,
  .overview-item,
  .chapter-card,
  .chapter-header,
  .lesson-row,
  .sidebar-enroll-button,
  .all-courses-link,
  .related-card,
  .related-cover img,
  .related-footer a {
    transition: none !important;
  }

  .hero-purchase-card:hover,
  .overview-item:hover,
  .related-card:hover,
  .purchase-button:hover,
  .sidebar-enroll-button:hover,
  .all-courses-link:hover,
  .related-footer a:hover {
    transform: none !important;
  }
}

.purchase-button.is-added,
.sidebar-enroll-button.is-added,
.mobile-course-action button.is-added {
  background: #0ab39c;
}

.purchase-button.is-added:hover,
.sidebar-enroll-button.is-added:hover,
.mobile-course-action button.is-added:hover {
  background: #079b87;
}

.mobile-course-action button {
  border: none;
  cursor: pointer;
}

</style>
