<script setup>
import { formatCourseLabel } from '@/composables/course-labels'
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import defaultCourseImage from '@assets/images/sss/default-course_1.jpeg'
import { useIndividualCourseCart } from '@/Composables/useIndividualCourseCart'

defineOptions({
  layout: LayoutAnonyme,
})


const PUBLIC_ACTIVE_MENU_KEY = 'activePublicMenuKey'

const activatePublicMenu = key => {
  if (!key || typeof window === 'undefined') return

  window.localStorage.setItem(PUBLIC_ACTIVE_MENU_KEY, key)

  window.dispatchEvent(
    new CustomEvent('public-menu-active', {
      detail: {
        key,
      },
    })
  )
}

const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  filterOptions: {
    type: Object,
    default: () => ({
      training_types: [],
      skill_levels: [],
      course_focuses: [],
      types: [],
      languages: [],
    }),
  },
})

const page = usePage()

const authUser = computed(() => {
  return page.props.auth?.user || null
})

const {
  cartCount,
  isCourseInCart,
  toggleCourse,
} = useIndividualCourseCart()

const openCart = () => {
  if (!cartCount.value) return

  if (authUser.value) {
    router.visit(route('public.individual-requests.create'))
    return
  }

  router.visit(route('login', { mode: 'register' }))
}

const handleCourseCart = course => {
  const action = toggleCourse(course)

  if (action === 'added' && !authUser.value) {
    router.visit(route('login', { mode: 'register' }))
  }
}

const searchQuery = ref('')
const selectedCategories = ref([])
const selectedTrainingType = ref('')
const selectedSkillLevel = ref('')
const selectedCourseFocus = ref('')
const selectedType = ref('')
const selectedLanguage = ref('')

const viewMode = ref('grid')
const sortBy = ref('newest')

const currentPage = ref(1)
const perPage = ref(9)

const showMobileFilters = ref(false)

const courseImage = course => {
  if (!course) return defaultCourseImage
  if (course.image_url) return course.image_url
  if (course.image) return `/storage/${course.image}`

  return defaultCourseImage
}

const handleImageError = event => {
  event.target.src = defaultCourseImage
}

const openMobileFilters = () => {
  showMobileFilters.value = true
}

const closeMobileFilters = () => {
  showMobileFilters.value = false
}

const formatLabel = value => formatCourseLabel(value)

const activeFiltersCount = computed(() => {
  let count = 0

  if (searchQuery.value) count++
  if (selectedCategories.value.length) count += selectedCategories.value.length
  if (selectedTrainingType.value) count++
  if (selectedSkillLevel.value) count++
  if (selectedCourseFocus.value) count++
  if (selectedType.value) count++
  if (selectedLanguage.value) count++

  return count
})

const isAnyFilterActive = computed(() => {
  return activeFiltersCount.value > 0
})

const filteredCourses = computed(() => {
  let data = [...props.courses]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()

    data = data.filter(course => {
      return (
        course.title?.toLowerCase().includes(query) ||
        course.description?.toLowerCase().includes(query) ||
        course.category?.name?.toLowerCase().includes(query) ||
        course.training_type?.toLowerCase().includes(query) ||
        course.skill_level?.toLowerCase().includes(query) ||
        course.course_focus?.toLowerCase().includes(query) ||
        course.type?.toLowerCase().includes(query) ||
        course.language?.toLowerCase().includes(query)
      )
    })
  }

  if (selectedCategories.value.length > 0) {
    data = data.filter(course => {
      return selectedCategories.value.includes(course.category?.id)
    })
  }

  if (selectedTrainingType.value) {
    data = data.filter(course => course.training_type === selectedTrainingType.value)
  }

  if (selectedSkillLevel.value) {
    data = data.filter(course => course.skill_level === selectedSkillLevel.value)
  }

  if (selectedCourseFocus.value) {
    data = data.filter(course => course.course_focus === selectedCourseFocus.value)
  }

  if (selectedType.value) {
    data = data.filter(course => course.type === selectedType.value)
  }

  if (selectedLanguage.value) {
    data = data.filter(course => course.language === selectedLanguage.value)
  }

  if (sortBy.value === 'newest') {
    data.sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
  }

  if (sortBy.value === 'price_low') {
    data.sort((a, b) => Number(a.price || 0) - Number(b.price || 0))
  }

  if (sortBy.value === 'price_high') {
    data.sort((a, b) => Number(b.price || 0) - Number(a.price || 0))
  }

  if (sortBy.value === 'title') {
    data.sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')))
  }

  return data
})

const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredCourses.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredCourses.value.length / perPage.value) || 1
})

const displayedFrom = computed(() => {
  if (!filteredCourses.value.length) return 0

  return (currentPage.value - 1) * perPage.value + 1
})

const displayedTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, filteredCourses.value.length)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 5) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const pages = [1]

  let start = Math.max(2, current - 1)
  let end = Math.min(total - 1, current + 1)

  if (current <= 3) {
    start = 2
    end = 4
  }

  if (current >= total - 2) {
    start = total - 3
    end = total - 1
  }

  if (start > 2) pages.push('...')

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  if (end < total - 1) pages.push('...')

  pages.push(total)

  return pages
})

const toggleCategory = categoryId => {
  if (selectedCategories.value.includes(categoryId)) {
    selectedCategories.value = selectedCategories.value.filter(id => id !== categoryId)
  } else {
    selectedCategories.value.push(categoryId)
  }

  currentPage.value = 1
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategories.value = []
  selectedTrainingType.value = ''
  selectedSkillLevel.value = ''
  selectedCourseFocus.value = ''
  selectedType.value = ''
  selectedLanguage.value = ''
  currentPage.value = 1
}

const resetFiltersAndClose = () => {
  resetFilters()
  closeMobileFilters()
}

const applyFiltersAndClose = () => {
  currentPage.value = 1
  closeMobileFilters()
}

const goToPage = page => {
  if (page === '...') return

  currentPage.value = page
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const isFreeCourse = course => {
  return !course?.price || Number(course.price) <= 0
}

const formatPrice = course => {
  if (course?.currency === 'EUR') {
    return `€ ${Number(course.price || 0).toFixed(2).replace('.', ',')}`
  }

  return `${Number(course?.price || 0).toFixed(2)} ${course?.currency || 'USD'}`
}

const shortText = (text, max = 90) => {
  if (!text) return ''

  return text.length > max ? text.substring(0, max) + '...' : text
}

const courseUrl = course => {
  if (!course?.hashid) {
    return '#'
  }

  return route('public.courses.show', {
    courseHashid: course.hashid,
  })
}

watch(
  [
    searchQuery,
    selectedTrainingType,
    selectedSkillLevel,
    selectedCourseFocus,
    selectedType,
    selectedLanguage,
    sortBy,
  ],
  () => {
    currentPage.value = 1
  }
)

watch(showMobileFilters, isOpen => {
  if (typeof document !== 'undefined') {
    document.body.classList.toggle('courses-filters-open', isOpen)
  }
})

onMounted(() => {
  activatePublicMenu('public-courses')
})

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.body.classList.remove('courses-filters-open')
  }
})
</script>

<template>
  <div class="courses-page">
    <section class="courses-hero">
      <div class="courses-hero-inner">
        <div class="courses-hero-copy">
          <div class="breadcrumb">
            <Link
              href="/"
              data-key="home"
              @click="activatePublicMenu('home')"
            >
              {{ $t('t-home') }}
            </Link>

            <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
            <strong>{{ $t('t-courses') }}</strong>
          </div>

          <h1>{{ $t('t-explore-aviation-courses') }}</h1>

          <p>{{ $t('t-courses-hero-description') }}</p>

          <label class="search-wrapper">
            <i class="ri-search-line" aria-hidden="true"></i>

            <input
              v-model="searchQuery"
              type="search"
              :placeholder="$t('t-search-course-placeholder')"
              :aria-label="$t('t-search-course-placeholder')"
            >

          </label>
        </div>

        <aside class="course-signal-panel" :aria-label="$t('t-courses')">
          <div class="course-signal-heading">
            <span>{{ $t('t-available-courses') }}</span>
            <i class="ri-flight-takeoff-line" aria-hidden="true"></i>
          </div>

          <div class="course-signal-orbit" aria-hidden="true">
            <span><i class="ri-plane-line"></i></span>
            <i class="ri-add-line orbit-cross orbit-cross-top"></i>
            <i class="ri-add-line orbit-cross orbit-cross-bottom"></i>
          </div>

          <div class="course-signal-stats">
            <div>
              <strong>{{ filteredCourses.length }}</strong>
              <span>{{ $t('t-results-found') }}</span>
            </div>

            <div>
              <strong>{{ categories.length }}</strong>
              <span>{{ $t('t-categories') }}</span>
            </div>
          </div>
        </aside>
      </div>
    </section>

    <button
      type="button"
      class="filters-overlay"
      :class="{ show: showMobileFilters }"
      @click="closeMobileFilters"
    ></button>

    <section class="courses-section">
      <div class="mobile-controls" :class="{ 'has-reset': isAnyFilterActive }">
        <button
          type="button"
          class="filter-toggle-btn"
          @click="openMobileFilters"
        >
          <i class="ri-sound-module-line"></i>
          {{ $t('t-filters') }}

          <span v-if="activeFiltersCount">
            {{ activeFiltersCount }}
          </span>
        </button>

        <button
          v-if="isAnyFilterActive"
          type="button"
          class="mobile-reset-inline-btn"
          :title="$t('t-reset-filters')"
          @click="resetFilters"
        >
          <i class="ri-refresh-line"></i>
          <span>{{ $t('t-reset-filters') }}</span>
        </button>

        <div class="mobile-sort-control">
          <span class="mobile-sort-icon" aria-hidden="true">
            <i class="ri-arrow-up-down-line"></i>
          </span>

          <div class="mobile-sort-content">
            <label for="mobile-course-sort" class="mobile-sort-label">
              {{ $t('t-sort-courses') }}
            </label>

            <select
              id="mobile-course-sort"
              v-model="sortBy"
              class="mobile-sort"
              :aria-label="$t('t-sort-courses')"
            >
              <option value="newest">{{ $t('t-newest-first') }}</option>
              <option value="price_low">{{ $t('t-price-low-high') }}</option>
              <option value="price_high">{{ $t('t-price-high-low') }}</option>
              <option value="title">{{ $t('t-title-a-z') }}</option>
            </select>
          </div>

          <i
            class="ri-arrow-down-s-line mobile-sort-arrow"
            aria-hidden="true"
          ></i>
        </div>

        <div class="mobile-results-line">
          <span>{{ filteredCourses.length }} {{ $t('t-results-found') }}</span>

          <small v-if="isAnyFilterActive">
            {{ activeFiltersCount }} {{ $t('t-active-filters') }}
          </small>
        </div>
      </div>

      <aside
        class="filters-card"
        :class="{ 'is-open': showMobileFilters }"
      >
        <div class="filters-mobile-head">
          <div class="filters-mobile-head-main">
            <span class="filters-mobile-icon">
              <i class="ri-sound-module-line"></i>
            </span>

            <div class="filters-mobile-copy">
              <div class="filters-mobile-title-row">
                <strong>{{ $t('t-filters') }}</strong>

                <span
                  v-if="activeFiltersCount"
                  class="filters-mobile-active-count"
                >
                  {{ activeFiltersCount }}
                </span>
              </div>

              <p>{{ filteredCourses.length }} {{ $t('t-results-available') }}</p>
            </div>
          </div>

          <button
            type="button"
            class="filters-mobile-close"
            :aria-label="$t('t-close-filters')"
            @click="closeMobileFilters"
          >
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="filters-header">
          <div class="filters-header-main">
            <span class="filters-header-icon">
              <i class="ri-sound-module-line"></i>
            </span>

            <div class="filters-header-content">
              <div class="filters-header-title">
                <h2>{{ $t('t-filters') }}</h2>

                <span
                  v-if="activeFiltersCount"
                  class="filters-active-count"
                >
                  {{ activeFiltersCount }}
                </span>
              </div>

              <p>{{ $t('t-refine-course-search') }}</p>
            </div>
          </div>

          <button
            type="button"
            class="filters-reset-btn"
            :disabled="!isAnyFilterActive"
            @click="resetFilters"
          >
            <i class="ri-refresh-line"></i>
            <span>{{ $t('t-reset') }}</span>
          </button>
        </div>

        <div class="filters-body">
          <div
            v-if="categories.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-categories') }}</span>
              <i class="ri-folder-line"></i>
            </div>

            <label
              v-for="category in categories"
              :key="category.id"
              class="check-row"
            >
              <input
                type="checkbox"
                :checked="selectedCategories.includes(category.id)"
                @change="toggleCategory(category.id)"
              >

              <span class="fake-checkbox"></span>

              <span class="check-label">
                {{ category.name }}
              </span>

              <span class="count">
                {{ category.courses_count || 0 }}
              </span>
            </label>
          </div>

          <div
            v-if="filterOptions.types && filterOptions.types.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-access-type') }}</span>
              <i class="ri-price-tag-3-line"></i>
            </div>

            <label
              v-for="type in filterOptions.types"
              :key="type"
              class="radio-row"
            >
              <input
                v-model="selectedType"
                type="radio"
                :value="type"
              >

              <span></span>
              {{ formatLabel(type) }}
            </label>
          </div>

          <div
            v-if="filterOptions.training_types && filterOptions.training_types.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-training-mode') }}</span>
              <i class="ri-computer-line"></i>
            </div>

            <label
              v-for="trainingType in filterOptions.training_types"
              :key="trainingType"
              class="radio-row"
            >
              <input
                v-model="selectedTrainingType"
                type="radio"
                :value="trainingType"
              >

              <span></span>
              {{ formatLabel(trainingType) }}
            </label>
          </div>

          <div
            v-if="filterOptions.skill_levels && filterOptions.skill_levels.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-training-type') }}</span>
              <i class="ri-bar-chart-line"></i>
            </div>

            <label
              v-for="skillLevel in filterOptions.skill_levels"
              :key="skillLevel"
              class="radio-row"
            >
              <input
                v-model="selectedSkillLevel"
                type="radio"
                :value="skillLevel"
              >

              <span></span>
              {{ formatLabel(skillLevel) }}
            </label>
          </div>

          <div
            v-if="filterOptions.course_focuses && filterOptions.course_focuses.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-course-focus') }}</span>
              <i class="ri-focus-3-line"></i>
            </div>

            <label
              v-for="courseFocus in filterOptions.course_focuses"
              :key="courseFocus"
              class="radio-row"
            >
              <input
                v-model="selectedCourseFocus"
                type="radio"
                :value="courseFocus"
              >

              <span></span>
              {{ formatLabel(courseFocus) }}
            </label>
          </div>

          <div
            v-if="filterOptions.languages && filterOptions.languages.length"
            class="filter-group"
          >
            <div class="filter-title">
              <span>{{ $t('t-language') }}</span>
              <i class="ri-translate-2"></i>
            </div>

            <label
              v-for="language in filterOptions.languages"
              :key="language"
              class="radio-row"
            >
              <input
                v-model="selectedLanguage"
                type="radio"
                :value="language"
              >

              <span></span>
              {{ formatLabel(language) }}
            </label>
          </div>
        </div>

        <div class="filters-mobile-actions">
          <button
            type="button"
            class="reset-mobile-btn"
            :disabled="!isAnyFilterActive"
            @click="resetFiltersAndClose"
          >
            <i class="ri-refresh-line"></i>
            <span>{{ $t('t-reset') }}</span>
          </button>

          <button
            type="button"
            class="apply-mobile-btn"
            @click="applyFiltersAndClose"
          >
            <span>{{ $t('t-view-results', { count: filteredCourses.length }) }}</span>
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </aside>

      <main class="courses-content">
        <div class="courses-toolbar">
          <div>
            <h2>{{ $t('t-available-courses') }}</h2>

            <p>
              {{ $t('t-showing-results', { from: displayedFrom, to: displayedTo, count: filteredCourses.length }) }}
            </p>
          </div>

          <div class="toolbar-actions">
            <button
              type="button"
              class="public-cart-button"
              :disabled="!cartCount"
              @click="openCart"
            >
              <i class="ri-shopping-cart-2-line"></i>
              <span>{{ $t('t-my-cart') }}</span>
              <strong v-if="cartCount">{{ cartCount }}</strong>
            </button>

            <div class="view-switch">
              <button
                type="button"
                :class="{ active: viewMode === 'grid' }"
                @click="viewMode = 'grid'"
              >
                <i class="ri-grid-line"></i>
              </button>

              <button
                type="button"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                <i class="ri-list-check"></i>
              </button>
            </div>

            <select v-model="sortBy" class="desktop-sort">
              <option value="newest">{{ $t('t-newest') }}</option>
              <option value="price_low">{{ $t('t-price-low-high') }}</option>
              <option value="price_high">{{ $t('t-price-high-low') }}</option>
              <option value="title">{{ $t('t-title') }}</option>
            </select>
          </div>
        </div>

        <div
          v-if="paginatedCourses.length > 0"
          class="courses-grid"
          :class="{ 'list-mode': viewMode === 'list' }"
        >
          <article
            v-for="course in paginatedCourses"
            :key="course.id"
            class="course-card"
          >
            <Link :href="courseUrl(course)" class="course-cover">
              <img
                :src="courseImage(course)"
                :alt="course.title || $t('t-course-image')"
                @error="handleImageError"
              >

              <div class="course-cover-overlay"></div>

              <span class="course-category">
                {{ course.category?.name || $t('t-course') }}
              </span>

              <span
                class="voice-badge"
                :class="{ active: course.support_voice_over }"
              >
                <i :class="course.support_voice_over ? 'ri-volume-up-line' : 'ri-volume-mute-line'"></i>
              </span>
            </Link>

            <div class="course-body">
              <Link :href="courseUrl(course)" class="course-title">
                {{ course.title || '-' }}
              </Link>

              <p class="course-description">
                {{ shortText(course.description || $t('t-no-description'), 110) }}
              </p>

              <div class="course-meta">
                <span v-if="course.type">
                  <i class="ri-price-tag-3-line"></i>
                  {{ formatLabel(course.type) }}
                </span>

                <span v-if="course.training_type">
                  <i class="ri-computer-line"></i>
                  {{ formatLabel(course.training_type) }}
                </span>

                <span v-if="course.skill_level">
                  <i class="ri-bar-chart-line"></i>
                  {{ formatLabel(course.skill_level) }}
                </span>

                <span v-if="course.language">
                  <i class="ri-translate-2"></i>
                  {{ formatLabel(course.language) }}
                </span>
              </div>
            </div>

            <div class="course-footer">
              <strong class="price">
                <template v-if="isFreeCourse(course)">
                  {{ $t('t-free') }}
                </template>

                <template v-else>
                  {{ formatPrice(course) }}
                </template>
              </strong>

              <button
                type="button"
                class="enroll-btn"
                :class="{ 'is-added': isCourseInCart(course.id) }"
                @click="handleCourseCart(course)"
              >
                <i
                  :class="
                    isCourseInCart(course.id)
                      ? 'ri-check-line'
                      : 'ri-shopping-cart-line'
                  "
                ></i>

                {{
                  isCourseInCart(course.id)
                    ? $t('t-added-to-cart')
                    : $t('t-add-to-cart')
                }}
              </button>
            </div>
          </article>
        </div>

        <div v-else class="empty-state">
          <div class="empty-icon">
            <i class="ri-search-line"></i>
          </div>

          <h3>{{ $t('t-no-courses-found') }}</h3>

          <p>{{ $t('t-try-changing-filters') }}</p>

          <button type="button" @click="resetFilters">
            {{ $t('t-reset-filters') }}
          </button>
        </div>

        <div v-if="totalPages > 1" class="pagination">
          <button
            type="button"
            :disabled="currentPage === 1"
            @click="prevPage"
          >
            <i class="ri-arrow-left-s-line"></i>
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="page-number"
            :class="{ active: page === currentPage, dots: page === '...' }"
            :disabled="page === '...'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            type="button"
            :disabled="currentPage === totalPages"
            @click="nextPage"
          >
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </main>
    </section>
  </div>
</template>

<style scoped>
.courses-page {
  --mobile-menu-height: 82px;

  min-height: 100vh;
  color: var(--ink);
  background: linear-gradient(180deg, var(--paper) 0%, var(--chalk) 52%, var(--paper) 100%);
}

.courses-hero {
  position: relative;
  overflow: hidden;
  min-height: 350px;
  padding: 72px 24px 110px;
  color: var(--chalk);
  background: radial-gradient(ellipse at 72% 24%, rgba(56, 225, 255, 0.16), transparent 42%), var(--ink);
}

.courses-hero::before,
.courses-hero::after {
  content: none;
  display: none;
}

.courses-hero-inner {
  position: relative;
  z-index: 2;
  width: min(980px, 100%);
  margin: 0 auto;
  text-align: center;
}

.breadcrumb {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-bottom: 22px;
  padding: 9px 14px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 999px;
  color: var(--ink);
  background-color: rgba(255, 255, 255, 0.94);
  font-size: 13px;
  font-weight: 800;
}

.breadcrumb a {
  color: var(--ink);
  text-decoration: none;
}

.breadcrumb strong {
  color: var(--signal-deep);
}

.courses-hero h1 {
  margin: 0 0 18px;
  color: var(--chalk);
  font-size: clamp(38px, 5vw, 62px);
  line-height: 1.05;
  font-weight: 900;
  letter-spacing: -1.8px;
}

.courses-hero p {
  width: min(720px, 100%);
  margin: 0 auto 30px;
  color: rgba(255, 255, 255, 0.92);
  font-size: 17px;
  font-weight: 650;
  line-height: 1.65;
}

.search-wrapper {
  width: min(820px, 100%);
  height: 60px;
  margin: 0 auto;
  padding: 0 22px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 18px;
  background-color: var(--chalk);
  box-shadow: 0 18px 48px rgba(7, 17, 38, 0.2);
  display: flex;
  align-items: center;
}

.search-wrapper i {
  margin-right: 13px;
  color: var(--signal-deep);
  font-size: 23px;
}

.search-wrapper input {
  width: 100%;
  border: none;
  outline: none;
  color: var(--ink);
  background-color: transparent;
  font-size: 15px;
  font-weight: 650;
}

.search-wrapper input::placeholder {
  color: var(--stone);
}

.courses-section {
  position: relative;
  z-index: 5;
  width: min(1480px, calc(100% - 42px));
  margin: -58px auto 78px;
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.mobile-controls {
  display: none;
}

.filters-card {
  position: sticky;
  top: 94px;
  height: fit-content;
  max-height: calc(100vh - 118px);
  padding: 0;
  overflow-y: auto;
  border: 1px solid var(--sand);
  border-radius: 24px;
  background-color: var(--chalk);
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
  scrollbar-width: thin;
  scrollbar-color: var(--sand) transparent;
}

.filters-card::-webkit-scrollbar {
  width: 6px;
}

.filters-card::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background-color: var(--sand);
}

.filters-mobile-head,
.filters-mobile-actions {
  display: none;
}

.filters-header {
  position: sticky;
  top: 0;
  z-index: 4;
  min-height: 94px;
  padding: 18px;
  border-bottom: 1px solid var(--sand);
  border-radius: 23px 23px 0 0;
  background: var(--chalk);
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.filters-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 18px;
  right: 18px;
  height: 3px;
  border-radius: 0 0 999px 999px;
  background: linear-gradient(90deg, var(--signal) 0%, var(--signal) 100%);
}

.filters-header-main {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.filters-header-icon,
.filters-mobile-icon,
.mobile-sort-icon {
  color: var(--signal-deep);
  background-color: var(--paper);
  border-color: rgba(13, 143, 233, 0.18);
}

.filters-header-icon {
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  border: 1px solid rgba(13, 143, 233, 0.18);
  border-radius: 13px;
  font-size: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.filters-header-content {
  min-width: 0;
}

.filters-header-title {
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filters-header h2 {
  margin: 0;
  color: var(--ink);
  font-size: 20px;
  font-weight: 850;
}

.filters-header p {
  margin: 0;
  color: var(--slate);
  font-size: 11px;
  font-weight: 650;
}

.filters-active-count,
.filters-mobile-active-count {
  min-width: 21px;
  height: 21px;
  padding: 0 6px;
  border: 1px solid rgba(13, 143, 233, 0.18);
  border-radius: 999px;
  color: var(--signal-deep);
  background-color: var(--paper);
  font-size: 10px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.filters-reset-btn {
  min-width: 76px;
  height: 38px;
  padding: 0 12px;
  border: 1px solid var(--sand);
  border-radius: 11px;
  color: var(--slate);
  background-color: var(--chalk);
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filters-reset-btn:hover:not(:disabled) {
  color: var(--signal-deep);
  border-color: rgba(13, 143, 233, 0.3);
  background-color: var(--paper);
  transform: translateY(-1px);
}

.filters-reset-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.filter-group {
  margin: 0 20px;
  padding: 19px 0;
  border-bottom: 1px solid var(--sand);
}

.filter-group:last-of-type {
  border-bottom: none;
}

.filter-title {
  margin-bottom: 12px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.filter-title i {
  color: var(--signal-deep);
  font-size: 17px;
}

.check-row,
.radio-row {
  min-height: 39px;
  color: var(--ink);
  font-size: 13px;
  font-weight: 750;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
}

.check-row:hover,
.radio-row:hover {
  color: var(--signal-deep);
}

.check-row input,
.radio-row input {
  display: none;
}

.fake-checkbox {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  border: 1px solid var(--sand);
  border-radius: 5px;
  background-color: var(--chalk);
}

.check-row input:checked + .fake-checkbox {
  border-color: var(--signal-deep);
  background-color: var(--signal-deep);
  box-shadow: inset 0 0 0 4px var(--chalk);
}

.radio-row > span {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  border: 2px solid var(--sand);
  border-radius: 50%;
}

.radio-row input:checked + span {
  border: 5px solid var(--signal-deep);
}

.check-label {
  flex: 1;
  min-width: 0;
}

.count {
  min-width: 26px;
  height: 24px;
  padding: 0 7px;
  border-radius: 999px;
  color: var(--slate);
  background-color: var(--paper);
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.courses-content {
  min-width: 0;
  padding: 24px;
  border: 1px solid var(--sand);
  border-radius: 24px;
  background-color: var(--chalk);
  box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
}

.courses-toolbar {
  padding-bottom: 20px;
  margin-bottom: 22px;
  border-bottom: 1px solid var(--sand);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.courses-toolbar h2 {
  margin: 0 0 4px;
  color: var(--ink);
  font-size: 24px;
  font-weight: 900;
}

.courses-toolbar p {
  margin: 0;
  color: var(--stone);
  font-size: 12px;
  font-weight: 750;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}


.public-cart-button {
  position: relative;
  min-height: 45px;
  padding: 0 15px;
  border: 1px solid var(--sand);
  border-radius: 13px;
  color: var(--ink);
  background: var(--chalk);
  font-size: 12px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.public-cart-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.public-cart-button:hover:not(:disabled) {
  color: var(--signal-deep);
  border-color: rgba(13, 143, 233, 0.32);
  background: var(--paper);
}

.public-cart-button strong {
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  color: var(--chalk);
  background: var(--signal);
  font-size: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.view-switch {
  padding: 4px;
  border-radius: 13px;
  background-color: var(--paper);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.view-switch button {
  width: 39px;
  height: 37px;
  border: none;
  border-radius: 10px;
  color: var(--slate);
  background-color: transparent;
  font-size: 16px;
  cursor: pointer;
}

.view-switch button.active {
  color: var(--signal-deep);
  background-color: var(--chalk);
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.09);
}

.desktop-sort {
  height: 45px;
  min-width: 176px;
  padding: 0 14px;
  border: 1px solid var(--sand);
  border-radius: 13px;
  color: var(--ink);
  background-color: var(--chalk);
  font-size: 13px;
  font-weight: 800;
  outline: none;
}

.desktop-sort:focus {
  border-color: var(--signal-deep);
  box-shadow: 0 0 0 0.18rem rgba(13, 143, 233, 0.14);
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.course-card {
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--sand);
  border-radius: 22px;
  background-color: var(--chalk);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
  display: flex;
  flex-direction: column;
  transition: all 0.22s ease;
}

.course-card:hover {
  transform: translateY(-4px);
  border-color: rgba(13, 143, 233, 0.3);
  box-shadow: 0 20px 44px rgba(15, 23, 42, 0.12);
}

.course-cover {
  position: relative;
  height: 205px;
  overflow: hidden;
  background-color: var(--paper);
  text-decoration: none;
  display: block;
}

.course-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}

.course-card:hover .course-cover img {
  transform: scale(1.04);
}

.course-cover-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(7, 17, 38, 0.02), rgba(7, 17, 38, 0.48));
}

.course-category {
  position: absolute;
  top: 14px;
  left: 14px;
  max-width: calc(100% - 76px);
  padding: 7px 11px;
  border-radius: 999px;
  color: var(--ink);
  background-color: rgba(255, 255, 255, 0.94);
  font-size: 11px;
  font-weight: 900;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.voice-badge {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  color: var(--stone);
  background-color: rgba(255, 255, 255, 0.94);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.voice-badge.active {
  color: var(--signal-deep);
}

.course-body {
  flex: 1;
  padding: 20px 20px 15px;
}

.course-title {
  display: block;
  margin-bottom: 9px;
  color: var(--ink);
  font-size: 19px;
  line-height: 1.35;
  font-weight: 900;
  text-decoration: none;
}

.course-title:hover {
  color: var(--signal-deep);
}

.course-description {
  min-height: 44px;
  margin-bottom: 15px;
  color: var(--slate);
  font-size: 13px;
  line-height: 1.55;
}

.course-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.course-meta span {
  padding: 6px 9px;
  border: 1px solid var(--sand);
  border-radius: 999px;
  color: var(--slate);
  background-color: var(--paper);
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.course-meta i {
  color: var(--signal-deep);
}

.course-footer {
  padding: 0 20px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.price {
  color: var(--ink);
  font-size: 20px;
  line-height: 1;
  font-weight: 900;
  white-space: nowrap;
}

.enroll-btn {
  height: 42px;
  border: none;
  cursor: pointer;
  padding: 0 16px;
  border-radius: 12px;
  color: var(--chalk);
  background-color: var(--ink);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
  text-decoration: none;
  font-size: 12px;
  font-weight: 900;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  transition: all 0.2s ease;
}

.enroll-btn.is-added {
  background-color: var(--success);
  box-shadow: 0 16px 34px rgba(10, 179, 156, 0.22);
}

.enroll-btn.is-added:hover {
  background-color: var(--success-deep);
}

.enroll-btn:hover {
  color: var(--chalk);
  background-color: var(--midnight);
  box-shadow: 0 20px 40px rgba(7, 17, 38, 0.28);
  transform: translateY(-2px);
}

.courses-grid.list-mode {
  grid-template-columns: 1fr;
  gap: 14px;
}

.courses-grid.list-mode .course-card {
  display: grid;
  grid-template-columns: 230px minmax(0, 1fr) 180px;
  align-items: center;
}

.courses-grid.list-mode .course-cover {
  height: 164px;
}

.courses-grid.list-mode .course-body {
  padding: 19px;
}

.courses-grid.list-mode .course-description {
  min-height: auto;
}

.courses-grid.list-mode .course-footer {
  height: 100%;
  padding: 20px;
  border-left: 1px solid var(--sand);
  flex-direction: column;
  align-items: stretch;
  justify-content: center;
}

.courses-grid.list-mode .enroll-btn {
  width: 100%;
}

.empty-state {
  padding: 70px 20px;
  text-align: center;
  color: var(--slate);
}

.empty-icon {
  width: 86px;
  height: 86px;
  margin: 0 auto 18px;
  border-radius: 28px;
  color: var(--signal-deep);
  background: var(--paper);
  font-size: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.empty-state h3 {
  margin-bottom: 8px;
  color: var(--ink);
  font-size: 24px;
  font-weight: 900;
}

.empty-state p {
  margin-bottom: 0;
}

.empty-state button {
  margin-top: 16px;
  padding: 13px 20px;
  border: none;
  border-radius: 12px;
  color: var(--chalk);
  background-color: var(--ink);
  box-shadow: 0 12px 26px rgba(7, 17, 38, 0.18);
  font-weight: 900;
  cursor: pointer;
}

.pagination {
  margin-top: 30px;
  padding-top: 24px;
  border-top: 1px solid var(--sand);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.pagination button {
  min-width: 40px;
  height: 40px;
  border: none;
  border-radius: 11px;
  color: var(--slate);
  background-color: transparent;
  font-weight: 900;
  cursor: pointer;
}

.pagination button:hover:not(:disabled) {
  color: var(--signal-deep);
  background-color: var(--paper);
}

.pagination button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.pagination .page-number.active {
  color: var(--chalk);
  background-color: var(--ink);
  box-shadow: 0 8px 18px rgba(7, 17, 38, 0.16);
}

.filters-overlay {
  display: none;
  border: none;
  padding: 0;
  background-color: rgba(7, 17, 38, 0.56);
}

@media (max-width: 1320px) {
  .courses-section {
    grid-template-columns: 280px minmax(0, 1fr);
  }

  .courses-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .courses-grid.list-mode .course-card {
    grid-template-columns: 210px minmax(0, 1fr);
  }

  .courses-grid.list-mode .course-footer {
    grid-column: 1 / -1;
    height: auto;
    padding-top: 0;
    border-left: none;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .courses-grid.list-mode .enroll-btn {
    width: auto;
  }
}

@media (max-width: 991.98px) {
  .courses-hero {
    min-height: auto;
    padding: 48px 20px 88px;
  }

  .courses-hero h1 {
    font-size: 43px;
  }

  .courses-section {
    width: calc(100% - 28px);
    margin-top: -46px;
    display: block;
    z-index: auto;
  }

  .mobile-controls {
    margin-bottom: 14px;
    padding: 12px;
    border: 1px solid var(--sand);
    border-radius: 18px;
    background-color: var(--chalk);
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 9px;
  }

  .filter-toggle-btn {
    width: 100%;
    min-width: 0;
    height: 47px;
    padding: 0 14px;
    border: none;
    border-radius: 12px;
    color: var(--chalk);
    background: linear-gradient(90deg, var(--signal) 0%, var(--signal) 100%);
    box-shadow: 0 8px 18px rgba(7, 17, 38, 0.2);
    font-size: 13px;
    font-weight: 850;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
  }

  .filter-toggle-btn > span {
    min-width: 23px;
    height: 23px;
    margin-left: auto;
    padding: 0 6px;
    border-radius: 999px;
    color: var(--signal-deep);
    background-color: var(--chalk);
    font-size: 10px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .mobile-reset-inline-btn {
    min-width: 126px;
    height: 47px;
    padding: 0 13px;
    border: 1px solid var(--sand);
    border-radius: 12px;
    color: var(--slate);
    background-color: var(--chalk);
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  .mobile-sort-control {
    position: relative;
    grid-column: 1 / -1;
    width: 100%;
    min-height: 66px;
    padding: 9px 44px 9px 54px;
    border: 1px solid var(--sand);
    border-radius: 14px;
    background: var(--chalk);
    display: flex;
    align-items: center;
  }

  .mobile-sort-icon {
    position: absolute;
    top: 50%;
    left: 12px;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(13, 143, 233, 0.16);
    border-radius: 10px;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .mobile-sort-content {
    width: 100%;
    min-width: 0;
  }

  .mobile-sort-label {
    display: block;
    margin: 0 0 2px;
    color: var(--slate);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .mobile-sort {
    width: 100%;
    height: 27px;
    border: none;
    outline: none;
    color: var(--ink);
    background: transparent;
    font-size: 13px;
    font-weight: 850;
    appearance: none;
  }

  .mobile-sort-arrow {
    position: absolute;
    top: 50%;
    right: 14px;
    color: var(--slate);
    font-size: 20px;
    transform: translateY(-50%);
  }

  .mobile-results-line {
    grid-column: 1 / -1;
    color: var(--slate);
    font-size: 11px;
    font-weight: 750;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .mobile-results-line small {
    color: var(--signal-deep);
    font-weight: 900;
  }

  .filters-overlay {
    position: fixed;
    top: var(--mobile-menu-height);
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1990;
    display: block;
    opacity: 0;
    pointer-events: none;
    background-color: rgba(7, 17, 38, 0.52);
    transition: opacity 0.24s ease;
  }

  .filters-overlay.show {
    opacity: 1;
    pointer-events: auto;
  }

  .filters-card {
    position: fixed;
    top: var(--mobile-menu-height);
    right: 0;
    bottom: 0;
    z-index: 2005;
    width: min(420px, calc(100vw - 24px));
    height: calc(100dvh - var(--mobile-menu-height));
    max-height: none;
    overflow: hidden;
    border: none;
    border-radius: 24px 0 0 24px;
    background-color: var(--paper);
    transform: translateX(105%);
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: -24px 0 64px rgba(15, 23, 42, 0.22);
    display: flex;
    flex-direction: column;
  }

  .filters-card.is-open {
    transform: translateX(0);
  }

  .filters-mobile-head {
    position: relative;
    flex: 0 0 auto;
    min-height: 86px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--sand);
    background: var(--chalk);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
  }

  .filters-mobile-head::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--signal) 0%, var(--signal) 100%);
  }

  .filters-mobile-head-main {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .filters-mobile-icon {
    width: 45px;
    height: 45px;
    flex: 0 0 45px;
    border: 1px solid rgba(13, 143, 233, 0.16);
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .filters-mobile-title-row {
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .filters-mobile-title-row strong {
    color: var(--ink);
    font-size: 20px;
    font-weight: 900;
  }

  .filters-mobile-copy p {
    margin: 0;
    color: var(--slate);
    font-size: 11px;
    font-weight: 700;
  }

  .filters-mobile-close {
    width: 42px;
    height: 42px;
    border: 1px solid var(--sand);
    border-radius: 12px;
    color: var(--slate);
    background-color: var(--chalk);
    font-size: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .filters-header {
    display: none;
  }

  .filters-body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 12px 14px 18px;
    overflow-y: auto;
  }

  .filter-group {
    margin: 0 0 12px;
    padding: 15px;
    border: 1px solid var(--sand);
    border-radius: 17px;
    background-color: var(--chalk);
  }

  .check-row,
  .radio-row {
    position: relative;
    width: 100%;
    min-height: 52px;
    margin: 0 0 9px;
    padding: 10px 12px;
    border: 1px solid var(--sand);
    border-radius: 13px;
    color: var(--slate);
    background-color: var(--chalk);
    display: flex;
    align-items: center;
    gap: 11px;
  }

  .check-row:has(input:checked),
  .radio-row:has(input:checked) {
    color: var(--signal-deep);
    border-color: rgba(13, 143, 233, 0.42);
    background: var(--paper);
    box-shadow: inset 3px 0 0 var(--signal-deep);
  }

  .check-row input,
  .radio-row input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
  }

  .fake-checkbox,
  .radio-row > span {
    width: 21px;
    height: 21px;
    flex: 0 0 21px;
  }

  .check-row input:checked + .fake-checkbox {
    border-color: var(--signal-deep);
    background-color: var(--signal-deep);
    box-shadow: none;
  }

  .check-row input:checked + .fake-checkbox::after {
    content: '';
    width: 5px;
    height: 9px;
    margin: 3px auto 0;
    border-right: 2px solid var(--chalk);
    border-bottom: 2px solid var(--chalk);
    transform: rotate(45deg);
    display: block;
  }

  .radio-row input:checked + span {
    border: 2px solid var(--signal-deep);
    background-color: var(--chalk);
    box-shadow: 0 0 0 4px rgba(13, 143, 233, 0.1);
  }

  .radio-row input:checked + span::after {
    content: '';
    width: 9px;
    height: 9px;
    margin: 4px;
    border-radius: 50%;
    background-color: var(--signal-deep);
    display: block;
  }

  .filters-mobile-actions {
    flex: 0 0 auto;
    width: 100%;
    padding: 13px 14px calc(13px + env(safe-area-inset-bottom));
    border-top: 1px solid var(--sand);
    background-color: var(--chalk);
    display: grid;
    grid-template-columns: 0.82fr 1.45fr;
    gap: 9px;
  }

  .reset-mobile-btn,
  .apply-mobile-btn {
    min-height: 50px;
    padding: 0 14px;
    border-radius: 13px;
    font-size: 12px;
    font-weight: 850;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
  }

  .reset-mobile-btn {
    border: 1px solid var(--sand);
    color: var(--slate);
    background-color: var(--chalk);
  }

  .apply-mobile-btn {
    border: none;
    color: var(--chalk);
    background: linear-gradient(90deg, var(--signal) 0%, var(--signal) 100%);
    box-shadow: 0 9px 20px rgba(7, 17, 38, 0.22);
  }

  .courses-content {
    padding: 20px;
  }

  .desktop-sort {
    display: none;
  }

  .courses-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .courses-grid.list-mode .course-card {
    display: flex;
  }

  .courses-grid.list-mode .course-cover {
    height: 205px;
  }

  .courses-grid.list-mode .course-footer {
    padding: 0 20px 20px;
    flex-direction: row;
  }
}

@media (max-width: 767.98px) {
  .courses-hero {
    padding: 38px 16px 78px;
  }

  .courses-hero h1 {
    font-size: 36px;
    letter-spacing: -0.8px;
  }

  .courses-hero p {
    margin-bottom: 22px;
    font-size: 14px;
  }

  .search-wrapper {
    height: 54px;
    padding: 0 16px;
    border-radius: 15px;
  }

  .courses-section {
    width: calc(100% - 20px);
    margin-top: -40px;
  }

  .courses-content {
    padding: 15px;
    border-radius: 19px;
  }

  .courses-toolbar {
    margin-bottom: 17px;
    padding-bottom: 16px;
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-actions,
  .view-switch {
    width: 100%;
  }

  .view-switch button {
    flex: 1;
  }

  .courses-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .course-cover,
  .courses-grid.list-mode .course-cover {
    height: 195px;
  }

  .course-body {
    padding: 17px 17px 13px;
  }

  .course-footer,
  .courses-grid.list-mode .course-footer {
    padding: 0 17px 17px;
  }
}

@media (max-width: 575.98px) {
  .mobile-controls {
    grid-template-columns: minmax(0, 1fr) 48px;
    padding: 10px;
  }

  .mobile-reset-inline-btn {
    width: 48px;
    min-width: 48px;
    padding: 0;
  }

  .mobile-reset-inline-btn span {
    display: none;
  }

  .filters-card {
    width: 100vw;
    border-radius: 0;
  }

  .filters-mobile-head {
    border-radius: 0;
  }

  .course-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .enroll-btn {
    width: 100%;
  }
}

:global(body.courses-filters-open) {
  overflow: hidden;
  touch-action: none;
}

/* =========================================================
   FINAL LAYOUT CORRECTION
   - no oversized empty white panel
   - stable grid cards
   - real horizontal list mode
   - footer-safe desktop layout
   ========================================================= */

.courses-page {
  --layout-footer-height: 62px;
  padding-bottom: calc(var(--layout-footer-height) + 28px);
}

.courses-section {
  margin-bottom: 0;
  padding-bottom: 34px;
}

/* The content area is no longer rendered as one oversized card. */
.courses-content {
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
}

.courses-toolbar {
  min-height: 90px;
  margin: 0 0 18px;
  padding: 18px 20px;
  border: 1px solid var(--sand);
  border-radius: 20px;
  background: var(--chalk);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.075);
}

.courses-toolbar h2 {
  font-size: 22px;
}

/* Grid cards keep a professional width when there are only a few courses. */
.courses-grid:not(.list-mode) {
  grid-template-columns: repeat(auto-fill, minmax(290px, 360px));
  justify-content: start;
  align-items: stretch;
  gap: 18px;
}

.courses-grid:not(.list-mode) .course-card {
  width: 100%;
  min-height: 410px;
}

.courses-grid:not(.list-mode) .course-cover {
  height: 190px;
}

.courses-grid:not(.list-mode) .course-body {
  padding: 18px 18px 13px;
}

.courses-grid:not(.list-mode) .course-footer {
  padding: 0 18px 18px;
}

/* Desktop list mode always uses the full available width. */
@media (min-width: 992px) {
  .courses-grid.list-mode {
    width: 100%;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 14px;
  }

  .courses-grid.list-mode .course-card {
    width: 100%;
    min-height: 190px;
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr) 190px;
    grid-template-rows: 1fr;
    align-items: stretch;
    overflow: hidden;
  }

  .courses-grid.list-mode .course-cover {
    grid-column: 1;
    grid-row: 1;
    width: 100%;
    height: 100%;
    min-height: 190px;
    border-radius: 0;
  }

  .courses-grid.list-mode .course-body {
    grid-column: 2;
    grid-row: 1;
    min-width: 0;
    padding: 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .courses-grid.list-mode .course-title {
    margin-bottom: 8px;
    font-size: 21px;
  }

  .courses-grid.list-mode .course-description {
    min-height: 0;
    margin-bottom: 16px;
  }

  .courses-grid.list-mode .course-footer {
    grid-column: 3;
    grid-row: 1;
    width: 100%;
    height: auto;
    padding: 22px;
    border-top: 0;
    border-left: 1px solid var(--sand);
    background: linear-gradient(180deg, var(--chalk) 0%, var(--paper) 100%);
    flex-direction: column;
    align-items: stretch;
    justify-content: center;
    gap: 16px;
  }

  .courses-grid.list-mode .price {
    font-size: 21px;
    text-align: center;
  }

  .courses-grid.list-mode .enroll-btn {
    width: 100%;
  }
}

/* Sidebar follows its own content and never creates an internal desktop scroll. */
@media (min-width: 992px) {
  .filters-card {
    position: sticky;
    top: 94px;
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .filters-header {
    position: relative;
    top: auto;
  }
}

/* Empty results remain presented as a real card. */
.empty-state {
  min-height: 330px;
  padding: 58px 20px;
  border: 1px solid var(--sand);
  border-radius: 22px;
  background: var(--chalk);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.065);
}

.pagination {
  margin-top: 18px;
  padding: 16px;
  border: 1px solid var(--sand);
  border-radius: 18px;
  background: var(--chalk);
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.055);
}

@media (min-width: 992px) and (max-width: 1240px) {
  .courses-grid:not(.list-mode) {
    grid-template-columns: repeat(2, minmax(280px, 1fr));
  }

  .courses-grid.list-mode .course-card {
    grid-template-columns: 220px minmax(0, 1fr) 170px;
  }
}

@media (max-width: 991.98px) {
  .courses-page {
    padding-bottom: calc(var(--layout-footer-height) + 20px);
  }

  .courses-section {
    padding-bottom: 24px;
  }

  .courses-content {
    padding: 0;
  }

  .courses-toolbar {
    min-height: 0;
    padding: 16px;
    border-radius: 18px;
  }

  .courses-grid:not(.list-mode) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .courses-grid:not(.list-mode) .course-card {
    min-height: 0;
  }
}

@media (max-width: 767.98px) {
  .mobile-controls:not(.has-reset) {
    grid-template-columns: minmax(0, 1fr);
  }

  .toolbar-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .view-switch {
    display: none;
  }

  .public-cart-button {
    min-width: 132px;
  }

  .mobile-results-line {
    gap: 8px;
  }

  .mobile-results-line small {
    text-align: right;
  }

  .courses-grid:not(.list-mode),
  .courses-grid.list-mode {
    grid-template-columns: 1fr;
  }

  .courses-grid.list-mode .course-card {
    width: 100%;
    display: flex;
    flex-direction: column;
  }

  .courses-grid.list-mode .course-cover,
  .courses-grid:not(.list-mode) .course-cover {
    width: 100%;
    height: 195px;
  }

  .courses-toolbar {
    gap: 14px;
  }
}

.courses-hero {
  padding-top: clamp(108px, 10vh, 124px);
}

@media (max-width: 991.98px) {
  .courses-hero {
    padding: 104px 20px 82px;
  }
}

@media (max-width: 767.98px) {
  .courses-hero {
    padding: 100px 16px 70px;
  }
}

.courses-hero {
  min-height: 0;
  padding: clamp(108px, 10vh, 124px) 24px 96px;
  background:
    radial-gradient(ellipse at 72% 28%, rgba(56, 225, 255, 0.15), transparent 38%),
    linear-gradient(112deg, var(--ink) 0%, var(--night) 66%, #101a2b 100%);
}

.courses-hero::before {
  content: '';
  display: block;
  position: absolute;
  right: -120px;
  bottom: -250px;
  width: 620px;
  aspect-ratio: 1;
  border: 1px solid rgba(56, 225, 255, 0.1);
  border-radius: 50%;
  box-shadow: 0 0 0 42px rgba(56, 225, 255, 0.025), 0 0 0 90px rgba(56, 225, 255, 0.018);
  pointer-events: none;
}

.courses-hero::after {
  content: '';
  display: block;
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(56, 225, 255, 0.42), transparent);
  pointer-events: none;
}

.courses-hero-inner {
  width: min(1240px, 100%);
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.65fr);
  align-items: center;
  gap: clamp(32px, 6vw, 92px);
  text-align: left;
}

.courses-hero-copy { min-width: 0; }

.courses-hero .breadcrumb {
  margin-bottom: 25px;
  padding: 8px 12px;
  border-radius: 9px;
  color: var(--mist);
  background: rgba(255, 255, 255, 0.07);
  border-color: rgba(255, 255, 255, 0.14);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.courses-hero .breadcrumb a { color: var(--mist); }
.courses-hero .breadcrumb strong { color: var(--jet); }

.courses-hero h1 {
  max-width: 780px;
  margin-bottom: 20px;
  font-size: clamp(48px, 6.3vw, 78px);
  line-height: 0.98;
  letter-spacing: -0.065em;
}

.courses-hero p {
  max-width: 650px;
  margin: 0 0 28px;
  color: rgba(255, 255, 255, 0.72);
  font-size: 15px;
  font-weight: 500;
  line-height: 1.7;
}

.search-wrapper {
  width: min(680px, 100%);
  height: 62px;
  margin: 0;
  padding: 0 18px;
  border: 1px solid rgba(255, 255, 255, 0.78);
  border-radius: 13px;
  box-shadow: 0 18px 46px rgba(0, 0, 0, 0.24), 0 0 0 4px rgba(56, 225, 255, 0.06);
}

.course-signal-panel {
  position: relative;
  min-width: 0;
  min-height: 340px;
  padding: 20px 22px 18px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 18px;
  color: var(--chalk);
  background: linear-gradient(155deg, rgba(22, 35, 61, 0.88), rgba(14, 22, 38, 0.9));
  box-shadow: inset 0 1px rgba(255, 255, 255, 0.06), 0 24px 60px rgba(0, 0, 0, 0.18);
  display: grid;
  grid-template-rows: auto 1fr auto;
}

.course-signal-heading {
  position: relative;
  z-index: 1;
  color: var(--mist);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.13em;
  text-transform: uppercase;
}

.course-signal-heading i { color: var(--jet); font-size: 18px; }

.course-signal-orbit {
  --orbit-radius: 82px;
  position: relative;
  width: 178px;
  aspect-ratio: 1;
  margin: 8px auto;
  border: 1px solid rgba(56, 225, 255, 0.22);
  border-radius: 50%;
  display: grid;
  place-items: center;
  box-shadow: 0 0 0 20px rgba(56, 225, 255, 0.025), inset 0 0 34px rgba(56, 225, 255, 0.04);
}

.course-signal-orbit::before {
  content: '';
  position: absolute;
  inset: 15px;
  border: 1px dashed rgba(56, 225, 255, 0.18);
  border-radius: 50%;
}

.course-signal-orbit::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--jet);
  box-shadow: 0 0 12px rgba(56, 225, 255, 0.8);
  transform: rotate(0) translateX(var(--orbit-radius));
  transform-origin: 0 50%;
  animation: course-orbit 14s linear infinite;
}

.course-signal-orbit > span {
  width: 52px;
  height: 52px;
  border: 1px solid rgba(56, 225, 255, 0.3);
  border-radius: 15px;
  color: var(--ink);
  background: var(--jet);
  display: grid;
  place-items: center;
  box-shadow: 0 0 34px rgba(56, 225, 255, 0.16);
}

.course-signal-orbit > span i { font-size: 24px; }

.orbit-cross {
  position: absolute;
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
}

.orbit-cross-top { top: 12px; right: 28px; }
.orbit-cross-bottom { bottom: 25px; left: 5px; }

.course-signal-stats {
  position: relative;
  z-index: 1;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.course-signal-stats > div { min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.course-signal-stats > div + div { padding-left: 14px; border-left: 1px solid rgba(255, 255, 255, 0.12); }
.course-signal-stats strong { color: var(--jet); font-size: 25px; line-height: 1; font-weight: 900; }
.course-signal-stats span { color: var(--mist); font-size: 10px; line-height: 1.3; }

.course-card { border-radius: 17px; box-shadow: 0 10px 28px rgba(7, 11, 20, 0.055); }
.courses-grid:not(.list-mode) .course-cover { height: 200px; }
.course-body { display: flex; flex-direction: column; }
.course-title { display: -webkit-box; min-height: 2.55em; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
.course-description { display: -webkit-box; min-height: 60px; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 3; }
.courses-grid:not(.list-mode) .course-footer { padding: 14px 18px 18px; border-top: 1px solid var(--sand); }

@keyframes course-orbit { to { transform: rotate(1turn) translateX(var(--orbit-radius)); } }

@media (max-width: 991.98px) {
  .courses-hero { padding: 104px 20px 78px; }
  .courses-hero-inner { grid-template-columns: 1fr; gap: 26px; }
  .course-signal-panel { min-height: 0; padding: 17px 20px; grid-template-columns: 140px minmax(0, 1fr); grid-template-rows: auto 1fr; align-items: center; gap: 0 20px; }
  .course-signal-heading { grid-column: 1 / -1; }
  .course-signal-orbit { --orbit-radius: 52px; width: 118px; grid-row: 2; margin: 16px auto 4px; }
  .course-signal-orbit::before { inset: 11px; }
  .course-signal-stats { grid-column: 2; grid-row: 2; width: 100%; }
}

@media (max-width: 767.98px) {
  .courses-hero { padding: 100px 16px 68px; }
  .courses-hero-inner { gap: 24px; }
  .courses-hero h1 { max-width: 580px; font-size: clamp(42px, 10vw, 62px); }
  .courses-hero p { font-size: 14px; }
  .search-wrapper { height: 56px; }
  .course-signal-panel { grid-template-columns: 1fr; padding: 15px 16px; }
  .course-signal-heading { grid-column: auto; }
  .course-signal-orbit { display: none; }
  .course-signal-stats { grid-column: auto; grid-row: auto; margin-top: 12px; }
}

@media (max-width: 575.98px) {
  .courses-hero { padding: 96px 14px 60px; }
  .courses-hero .breadcrumb { margin-bottom: 18px; }
  .courses-hero h1 { font-size: clamp(40px, 11vw, 52px); }
  .courses-hero p { margin-bottom: 20px; }
  .courses-section { width: calc(100% - 24px); margin-top: -32px; }
  .course-signal-stats strong { font-size: 22px; }
  .course-signal-stats span { font-size: 9px; }
  .courses-toolbar { border-radius: 15px; }
  .courses-grid:not(.list-mode) .course-card { min-height: 0; }
  .courses-grid:not(.list-mode) .course-cover { height: 185px; }
  .course-title { min-height: 0; }
  .course-description { min-height: 0; }
  .courses-grid:not(.list-mode) .course-footer { margin-top: auto; }
}

@media (prefers-reduced-motion: reduce) {
  .course-signal-orbit::after { animation: none; }
  .course-card,
  .course-cover img { transition: none; }
}

</style>
