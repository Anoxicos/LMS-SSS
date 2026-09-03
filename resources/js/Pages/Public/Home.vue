<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import LayoutAnonyme from '@/Layouts/main-anonyme.vue'
import airplaneHero from '@assets/images/airplane-hero.png'
import maintenanceTraining from '@assets/images/home/maintenance-training.png'
import flightOperations from '@assets/images/home/flight-operations.png'
import crewTraining from '@assets/images/home/crew-training.png'
import learnerExperience from '@assets/images/home/learner-experience.png'

defineOptions({
  layout: LayoutAnonyme,
})

const pageRoot = ref(null)
let revealObserver = null

const handleImageError = event => {
  const image = event?.currentTarget

  if (!image || image.dataset.fallbackApplied === 'true') {
    return
  }

  image.dataset.fallbackApplied = 'true'
  image.src = airplaneHero
}

const trustItems = [
  {
    icon: 'ri-shield-check-line',
    title: 't-home-trust-compliance-title',
    description: 't-home-trust-compliance-description',
  },
  {
    icon: 'ri-time-line',
    title: 't-home-trust-flexibility-title',
    description: 't-home-trust-flexibility-description',
  },
  {
    icon: 'ri-device-line',
    title: 't-home-trust-device-title',
    description: 't-home-trust-device-description',
  },
  {
    icon: 'ri-award-line',
    title: 't-home-trust-certificate-title',
    description: 't-home-trust-certificate-description',
  },
]

const learningPaths = [
  {
    number: '01',
    icon: 'ri-tools-line',
    image: maintenanceTraining,
    label: 't-home-path-maintenance-label',
    title: 't-home-path-maintenance-title',
    description: 't-home-path-maintenance-description',
    features: [
      't-home-feature-ewis',
      't-home-feature-fts',
      't-home-feature-human-factors',
    ],
  },
  {
    number: '02',
    icon: 'ri-plane-line',
    image: flightOperations,
    label: 't-home-path-operations-label',
    title: 't-home-path-operations-title',
    description: 't-home-path-operations-description',
    features: [
      't-home-feature-flight-operations',
      't-home-feature-safety',
      't-home-feature-regulations',
    ],
  },
  {
    number: '03',
    icon: 'ri-team-line',
    image: crewTraining,
    label: 't-home-path-crew-label',
    title: 't-home-path-crew-title',
    description: 't-home-path-crew-description',
    features: [
      't-home-feature-cabin-crew',
      't-home-feature-ground-handling',
      't-home-feature-dangerous-goods',
    ],
  },
]

const categories = [
  {
    icon: 'ri-flight-takeoff-line',
    title: 't-home-category-flight-operations',
    description: 't-home-category-flight-operations-description',
  },
  {
    icon: 'ri-tools-line',
    title: 't-home-category-maintenance',
    description: 't-home-category-maintenance-description',
  },
  {
    icon: 'ri-user-star-line',
    title: 't-home-category-human-factors',
    description: 't-home-category-human-factors-description',
  },
  {
    icon: 'ri-shield-check-line',
    title: 't-home-category-safety',
    description: 't-home-category-safety-description',
  },
  {
    icon: 'ri-luggage-cart-line',
    title: 't-home-category-ground',
    description: 't-home-category-ground-description',
  },
  {
    icon: 'ri-government-line',
    title: 't-home-category-regulatory',
    description: 't-home-category-regulatory-description',
  },
]

const learnerBenefits = [
  {
    icon: 'ri-play-circle-line',
    title: 't-home-benefit-interactive-title',
    description: 't-home-benefit-interactive-description',
  },
  {
    icon: 'ri-bar-chart-box-line',
    title: 't-home-benefit-progress-title',
    description: 't-home-benefit-progress-description',
  },
  {
    icon: 'ri-verified-badge-line',
    title: 't-home-benefit-certificate-title',
    description: 't-home-benefit-certificate-description',
  },
  {
    icon: 'ri-refresh-line',
    title: 't-home-benefit-current-title',
    description: 't-home-benefit-current-description',
  },
]

const journeySteps = [
  {
    number: '01',
    icon: 'ri-compass-3-line',
    title: 't-home-step-explore-title',
    description: 't-home-step-explore-description',
  },
  {
    number: '02',
    icon: 'ri-book-open-line',
    title: 't-home-step-learn-title',
    description: 't-home-step-learn-description',
  },
  {
    number: '03',
    icon: 'ri-award-line',
    title: 't-home-step-certify-title',
    description: 't-home-step-certify-description',
  },
]

onMounted(() => {
  const root = pageRoot.value

  if (!root) {
    return
  }

  const revealItems = Array.from(root.querySelectorAll('[data-reveal]'))
  const prefersReducedMotion = window.matchMedia?.(
    '(prefers-reduced-motion: reduce)',
  )?.matches

  if (
    prefersReducedMotion ||
    !('IntersectionObserver' in window)
  ) {
    revealItems.forEach(item => item.classList.add('is-visible'))
    return
  }

  root.classList.add('motion-enabled')

  revealObserver = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) {
          return
        }

        entry.target.classList.add('is-visible')
        revealObserver?.unobserve(entry.target)
      })
    },
    {
      threshold: 0.14,
      rootMargin: '0px 0px -70px 0px',
    },
  )

  revealItems.forEach(item => revealObserver.observe(item))
})

onBeforeUnmount(() => {
  revealObserver?.disconnect()
  revealObserver = null
})
</script>

<template>
  <main ref="pageRoot" class="aviation-home">
    <!-- =====================================================
         HERO
         ===================================================== -->
    <section class="hero-section">
      <div class="hero-line-field" aria-hidden="true"></div>

      <div class="page-container hero-container">
        <div class="hero-content">
          <span class="hero-eyebrow hero-load hero-load-1">
            <i class="ri-graduation-cap-line"></i>
            {{ $t('t-aviation-learning-platform') }}
          </span>

          <h1 class="hero-title hero-load hero-load-2">
            <span>{{ $t('t-take-your-aviation') }}</span>
            <span>{{ $t('t-career-to-new') }}</span>
            <span class="hero-title-accent">{{ $t('t-heights') }}</span>
          </h1>

          <p class="hero-description hero-load hero-load-3">
            {{ $t('t-hero-description') }}
          </p>

          <div class="hero-actions hero-load hero-load-4">
            <Link href="/courses" class="home-btn home-btn-primary">
              <span>{{ $t('t-see-all-courses') }}</span>
              <i class="ri-arrow-right-line"></i>
            </Link>

            <a href="#learning-paths" class="home-btn home-btn-secondary">
              <i class="ri-compass-3-line"></i>
              <span>{{ $t('t-home-explore-paths') }}</span>
            </a>
          </div>

          <div class="hero-assurances hero-load hero-load-5">
            <span>
              <i class="ri-checkbox-circle-line"></i>
              {{ $t('t-home-assurance-self-paced') }}
            </span>
            <span>
              <i class="ri-checkbox-circle-line"></i>
              {{ $t('t-home-assurance-career-focused') }}
            </span>
            <span>
              <i class="ri-checkbox-circle-line"></i>
              {{ $t('t-home-assurance-mobile-ready') }}
            </span>
          </div>
        </div>

        <div class="hero-visual hero-visual-load">
          <div class="hero-dashboard">
            <div class="dashboard-toolbar">
              <div class="dashboard-toolbar-copy">
                <span>{{ $t('t-home-learning-cockpit') }}</span>
                <strong>{{ $t('t-home-personal-dashboard') }}</strong>
              </div>

              <span class="dashboard-status">
                <i class="ri-checkbox-circle-fill"></i>
                {{ $t('t-home-status-ready') }}
              </span>
            </div>

            <div class="dashboard-aircraft-stage">
              <div class="dashboard-destination">
                <span>{{ $t('t-home-next-destination') }}</span>
                <strong>{{ $t('t-home-professional-growth') }}</strong>
              </div>

              <div class="airplane-entry">
                <img
                  :src="airplaneHero"
                  alt="Airplane"
                  class="airplane-image"
                  draggable="false"
                />
              </div>

              <div class="aircraft-guide-line" aria-hidden="true"></div>
            </div>

            <div class="dashboard-progress-card">
              <div class="progress-course-row">
                <div class="progress-course-icon">
                  <i class="ri-flashlight-line"></i>
                </div>

                <div class="progress-course-copy">
                  <span>{{ $t('t-home-course-in-progress') }}</span>
                  <strong>{{ $t('t-home-demo-course') }}</strong>

                  <div class="progress-track" aria-label="72%">
                    <span></span>
                  </div>
                </div>

                <b>72%</b>
              </div>

              <div class="dashboard-feature-strip">
                <span>
                  <i class="ri-book-open-line"></i>
                  {{ $t('t-home-interactive-modules') }}
                </span>
                <span>
                  <i class="ri-time-line"></i>
                  {{ $t('t-home-assurance-self-paced') }}
                </span>
                <span>
                  <i class="ri-award-line"></i>
                  {{ $t('t-home-certificate-ready') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- =====================================================
         TRUST STRIP
         ===================================================== -->
    <section class="trust-section" aria-label="Platform benefits">
      <div class="page-container trust-layout">
        <div class="trust-heading" data-reveal>
          <span>{{ $t('t-home-built-for') }}</span>
          <strong>{{ $t('t-home-aviation-professionals') }}</strong>
        </div>

        <div class="trust-grid">
          <article
            v-for="(item, index) in trustItems"
            :key="item.title"
            class="trust-item"
            data-reveal
            :style="{ '--reveal-delay': `${index * 70}ms` }"
          >
            <div class="trust-icon">
              <i :class="item.icon"></i>
            </div>

            <div class="trust-copy">
              <h3>{{ $t(item.title) }}</h3>
              <p>{{ $t(item.description) }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- =====================================================
         LEARNING PATHS
         ===================================================== -->
    <section id="learning-paths" class="home-section paths-section">
      <div class="page-container">
        <div class="section-heading heading-with-action" data-reveal>
          <div class="section-heading-copy">
            <span class="section-eyebrow">
              <i class="ri-route-line"></i>
              {{ $t('t-home-learning-paths-eyebrow') }}
            </span>

            <h2>{{ $t('t-home-learning-paths-title') }}</h2>
            <p>{{ $t('t-home-learning-paths-description') }}</p>
          </div>

          <Link href="/courses" class="section-text-link">
            {{ $t('t-home-view-catalogue') }}
            <i class="ri-arrow-right-line"></i>
          </Link>
        </div>

        <div class="paths-grid">
          <article
            v-for="(path, index) in learningPaths"
            :key="path.title"
            class="path-card"
            data-reveal
            :style="{ '--reveal-delay': `${index * 90}ms` }"
          >
            <div class="path-media">
              <img
                :src="path.image"
                :alt="$t(path.label)"
                loading="lazy"
                @error="handleImageError"
              />

              <div class="path-media-shade" aria-hidden="true"></div>

              <span class="path-number">{{ path.number }}</span>

              <div class="path-icon">
                <i :class="path.icon"></i>
              </div>
            </div>

            <div class="path-card-body">
              <span class="path-label">{{ $t(path.label) }}</span>
              <h3>{{ $t(path.title) }}</h3>
              <p>{{ $t(path.description) }}</p>

              <div class="path-features">
                <span
                  v-for="feature in path.features"
                  :key="feature"
                >
                  <i class="ri-check-line"></i>
                  {{ $t(feature) }}
                </span>
              </div>

              <Link href="/courses" class="path-link">
                <span>{{ $t('t-home-discover-courses') }}</span>
                <i class="ri-arrow-right-up-line"></i>
              </Link>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- =====================================================
         CATEGORIES
         ===================================================== -->
    <section class="home-section categories-section">
      <div class="page-container categories-layout">
        <div class="categories-intro" data-reveal>
          <span class="section-eyebrow">
            <i class="ri-apps-2-line"></i>
            {{ $t('t-home-categories-eyebrow') }}
          </span>

          <h2>{{ $t('t-home-categories-title') }}</h2>
          <p>{{ $t('t-home-categories-description') }}</p>

          <Link href="/courses" class="home-btn home-btn-dark">
            <span>{{ $t('t-see-all-courses') }}</span>
            <i class="ri-arrow-right-line"></i>
          </Link>
        </div>

        <div class="categories-grid">
          <Link
            v-for="(category, index) in categories"
            :key="category.title"
            href="/courses"
            class="category-card"
            data-reveal
            :style="{ '--reveal-delay': `${(index % 2) * 70}ms` }"
          >
            <div class="category-icon">
              <i :class="category.icon"></i>
            </div>

            <div class="category-copy">
              <h3>{{ $t(category.title) }}</h3>
              <p>{{ $t(category.description) }}</p>
            </div>

            <i class="ri-arrow-right-line category-arrow"></i>
          </Link>
        </div>
      </div>
    </section>

    <!-- =====================================================
         LEARNER EXPERIENCE
         ===================================================== -->
    <section class="home-section experience-section">
      <div class="experience-line-field" aria-hidden="true"></div>

      <div class="page-container experience-layout">
        <div class="experience-main" data-reveal>
          <span class="section-eyebrow section-eyebrow-light">
            <i class="ri-dashboard-line"></i>
            {{ $t('t-home-experience-eyebrow') }}
          </span>

          <h2>{{ $t('t-home-experience-title') }}</h2>
          <p>{{ $t('t-home-experience-description') }}</p>

          <div class="experience-note">
            <i class="ri-information-line"></i>
            <span>{{ $t('t-home-experience-note') }}</span>
          </div>

          <div class="experience-image">
            <img
              :src="learnerExperience"
              :alt="$t('t-home-experience-title')"
              loading="lazy"
              @error="handleImageError"
            />

            <div class="experience-image-shade" aria-hidden="true"></div>

            <div class="experience-image-caption">
              <i class="ri-play-circle-line"></i>
              <span>{{ $t('t-home-benefit-interactive-title') }}</span>
            </div>
          </div>
        </div>

        <div class="benefits-grid">
          <article
            v-for="(benefit, index) in learnerBenefits"
            :key="benefit.title"
            class="benefit-card"
            data-reveal
            :style="{ '--reveal-delay': `${index * 70}ms` }"
          >
            <div class="benefit-icon">
              <i :class="benefit.icon"></i>
            </div>

            <div class="benefit-copy">
              <h3>{{ $t(benefit.title) }}</h3>
              <p>{{ $t(benefit.description) }}</p>
            </div>

            <span class="benefit-index">0{{ index + 1 }}</span>
          </article>
        </div>
      </div>
    </section>

    <!-- =====================================================
         JOURNEY
         ===================================================== -->
    <section class="home-section journey-section">
      <div class="page-container">
        <div class="section-heading centered-heading" data-reveal>
          <span class="section-eyebrow">
            <i class="ri-navigation-line"></i>
            {{ $t('t-home-journey-eyebrow') }}
          </span>

          <h2>{{ $t('t-home-journey-title') }}</h2>
          <p>{{ $t('t-home-journey-description') }}</p>
        </div>

        <div class="journey-grid">
          <article
            v-for="(step, index) in journeySteps"
            :key="step.number"
            class="journey-card"
            data-reveal
            :style="{ '--reveal-delay': `${index * 90}ms` }"
          >
            <div class="journey-card-top">
              <span class="journey-number">{{ step.number }}</span>

              <div class="journey-icon">
                <i :class="step.icon"></i>
              </div>
            </div>

            <h3>{{ $t(step.title) }}</h3>
            <p>{{ $t(step.description) }}</p>

            
          </article>
        </div>
      </div>
    </section>

    <!-- =====================================================
         FINAL CTA
         ===================================================== -->
    <section class="final-cta-section">
      <div class="page-container">
        <div
          class="final-cta-card"
          data-reveal
          :style="{ '--cta-background-image': `url(${flightOperations})` }"
        >
          <div class="final-cta-media" aria-hidden="true"></div>
          <div class="final-cta-overlay" aria-hidden="true"></div>
          <div class="final-cta-lines" aria-hidden="true"></div>

          <div class="final-cta-copy">
            <span class="final-cta-label">
              <i class="ri-flight-takeoff-line"></i>
              {{ $t('t-home-final-cta-eyebrow') }}
            </span>

            <h2>{{ $t('t-home-final-cta-title') }}</h2>
            <p>{{ $t('t-home-final-cta-description') }}</p>
          </div>

          <Link href="/courses" class="home-btn home-btn-white">
            <span>{{ $t('t-home-browse-training') }}</span>
            <i class="ri-arrow-right-line"></i>
          </Link>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
.aviation-home {
  --home-cyan: #37cced;
  --home-blue: #0d8fe9;
  --home-blue-dark: #087bc9;
  --home-navy: #071126;
  --home-navy-hover: #132b4a;
  --home-soft: #eaf7ff;
  --home-page-soft: #f4fbff;
  --home-border: #e4edf4;
  --home-muted: #64748b;
  --home-text: #334155;
  --home-success: #0ab39c;
  --shadow-card: 0 16px 38px rgba(15, 23, 42, 0.075);
  --shadow-card-hover: 0 24px 52px rgba(15, 23, 42, 0.12);
  --shadow-blue: 0 18px 38px rgba(13, 143, 233, 0.2);

  overflow: hidden;
  color: var(--home-navy);
  background: linear-gradient(
    180deg,
    #f4fbff 0%,
    #ffffff 44%,
    #f8fafc 100%
  );
}

.aviation-home,
.aviation-home * {
  box-sizing: border-box;
}

.aviation-home a:focus-visible {
  outline: 3px solid rgba(55, 204, 237, 0.46);
  outline-offset: 4px;
}

.page-container {
  width: min(1380px, calc(100% - 48px));
  margin: 0 auto;
}

.home-section {
  padding: 108px 0;
}

/* =========================================================
   SHARED UI
   ========================================================= */
.section-eyebrow,
.hero-eyebrow {
  width: fit-content;
  min-height: 36px;
  padding: 0 13px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 10px;
  color: var(--home-blue-dark);
  background: var(--home-soft);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.075em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.section-eyebrow i,
.hero-eyebrow i {
  font-size: 16px;
}

.section-eyebrow-light {
  border-color: rgba(255, 255, 255, 0.34);
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
}

.section-heading {
  margin-bottom: 46px;
}

.section-heading-copy {
  max-width: 820px;
}

.section-heading h2,
.categories-intro h2,
.experience-main h2,
.final-cta-copy h2 {
  margin: 20px 0 16px;
  letter-spacing: -0.045em;
  font-weight: 900;
}

.section-heading h2,
.categories-intro h2 {
  color: var(--home-navy);
  font-size: clamp(38px, 4.3vw, 60px);
  line-height: 1.05;
}

.section-heading p,
.categories-intro > p {
  max-width: 730px;
  margin: 0;
  color: #526174;
  font-size: 16px;
  line-height: 1.78;
}

.heading-with-action {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 36px;
}

.centered-heading {
  max-width: 820px;
  margin-right: auto;
  margin-bottom: 52px;
  margin-left: auto;
  text-align: center;
}

.centered-heading .section-eyebrow {
  margin: 0 auto;
}

.centered-heading p {
  margin: 0 auto;
}

.section-text-link {
  flex: 0 0 auto;
  min-height: 42px;
  padding: 0 2px;
  border-bottom: 2px solid rgba(13, 143, 233, 0.2);
  color: var(--home-navy);
  font-size: 13px;
  font-weight: 900;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  transition:
    color 0.2s ease,
    border-color 0.2s ease;
}

.section-text-link i {
  transition: transform 0.2s ease;
}

.section-text-link:hover {
  color: var(--home-blue-dark);
  border-color: var(--home-blue);
}

.section-text-link:hover i {
  transform: translateX(4px);
}

.home-btn {
  min-height: 54px;
  padding: 0 24px;
  border: 1px solid transparent;
  border-radius: 13px;
  font-size: 14px;
  font-weight: 900;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.home-btn i {
  font-size: 18px;
  transition: transform 0.2s ease;
}

.home-btn:hover {
  transform: translateY(-2px);
}

.home-btn:hover i:last-child {
  transform: translateX(3px);
}

.home-btn-primary,
.home-btn-dark {
  color: #ffffff;
  background: var(--home-navy);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
}

.home-btn-primary:hover,
.home-btn-dark:hover {
  color: #ffffff;
  background: var(--home-navy-hover);
  box-shadow: 0 20px 40px rgba(7, 17, 38, 0.26);
}

.home-btn-secondary {
  border-color: rgba(255, 255, 255, 0.58);
  color: #ffffff;
  background: rgba(7, 17, 38, 0.2);
  box-shadow: 0 12px 28px rgba(7, 17, 38, 0.12);
}

.home-btn-secondary:hover {
  border-color: rgba(255, 255, 255, 0.82);
  color: #ffffff;
  background: rgba(7, 17, 38, 0.34);
}

.home-btn-dark {
  margin-top: 30px;
}

.home-btn-white {
  flex: 0 0 auto;
  min-width: 220px;
  border-color: rgba(255, 255, 255, 0.82);
  color: var(--home-navy);
  background: #ffffff;
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.2);
}

.home-btn-white:hover {
  color: var(--home-navy);
  background: var(--home-soft);
}

/* =========================================================
   HERO
   ========================================================= */
.hero-section {
  position: relative;
  min-height: 760px;
  padding: 72px 0 92px;
  overflow: hidden;
  color: #ffffff;
  background:
    linear-gradient(
      104deg,
      rgba(7, 17, 38, 0.28) 0%,
      rgba(7, 17, 38, 0.14) 42%,
      rgba(7, 17, 38, 0.03) 74%,
      transparent 100%
    ),
    linear-gradient(
      90deg,
      var(--home-cyan) 0%,
      var(--home-blue) 100%
    );
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
}

.hero-line-field {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.16;
  background-image:
    linear-gradient(
      90deg,
      transparent 0%,
      transparent 17%,
      rgba(255, 255, 255, 0.22) 17.1%,
      transparent 17.2%,
      transparent 82%,
      rgba(255, 255, 255, 0.16) 82.1%,
      transparent 82.2%
    ),
    linear-gradient(
      180deg,
      transparent 0%,
      transparent 72%,
      rgba(255, 255, 255, 0.18) 72.1%,
      transparent 72.2%
    );
}

.hero-line-field::after {
  content: '';
  position: absolute;
  right: -12%;
  bottom: 18%;
  width: 58%;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(-10deg);
  transform-origin: right center;
}

.hero-container {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: minmax(430px, 0.92fr) minmax(500px, 1.08fr);
  align-items: center;
  gap: clamp(58px, 7vw, 104px);
}

.hero-content {
  max-width: 710px;
}

.hero-eyebrow {
  margin-bottom: 24px;
  border-color: rgba(255, 255, 255, 0.66);
  color: var(--home-navy);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.1);
}

.hero-eyebrow i {
  color: var(--home-blue);
}

.hero-title {
  margin: 0;
  color: #ffffff;
  font-size: clamp(52px, 5.65vw, 82px);
  line-height: 0.99;
  letter-spacing: -0.058em;
  font-weight: 950;
  text-shadow: 0 10px 26px rgba(7, 17, 38, 0.1);
}

.hero-title > span {
  display: block;
}

.hero-title-accent {
  position: relative;
  width: fit-content;
  margin-top: 5px;
  padding-bottom: 10px;
}

.hero-title-accent::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 4px;
  border-radius: 2px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 6px 18px rgba(7, 17, 38, 0.14);
  transform-origin: left center;
  animation: hero-line-draw 0.85s 1s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.hero-description {
  max-width: 650px;
  margin: 30px 0 0;
  color: rgba(255, 255, 255, 0.96);
  font-size: 17px;
  line-height: 1.75;
  font-weight: 550;
}

.hero-actions {
  margin-top: 32px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}

.hero-assurances {
  margin-top: 28px;
  color: rgba(255, 255, 255, 0.94);
  font-size: 12px;
  font-weight: 750;
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
}

.hero-assurances span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.hero-assurances i {
  color: #ffffff;
  font-size: 16px;
}

.hero-visual {
  min-width: 0;
}

.hero-dashboard {
  position: relative;
  min-height: 570px;
  padding: 22px;
  border: 1px solid rgba(255, 255, 255, 0.76);
  border-radius: 26px;
  color: var(--home-navy);
  background: rgba(255, 255, 255, 0.97);
  box-shadow: 0 28px 62px rgba(7, 17, 38, 0.2);
}

.hero-dashboard::before,
.hero-dashboard::after {
  content: '';
  position: absolute;
  pointer-events: none;
  background: rgba(255, 255, 255, 0.32);
}

.hero-dashboard::before {
  top: 68px;
  right: -56px;
  width: 130px;
  height: 1px;
}

.hero-dashboard::after {
  right: 84px;
  bottom: -42px;
  width: 1px;
  height: 98px;
}

.dashboard-toolbar {
  min-height: 58px;
  padding: 0 2px 18px;
  border-bottom: 1px solid var(--home-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.dashboard-toolbar-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dashboard-toolbar-copy span,
.dashboard-destination span,
.progress-course-copy > span {
  color: #526174;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.dashboard-toolbar-copy strong,
.dashboard-destination strong {
  color: var(--home-navy);
  font-size: 15px;
  font-weight: 900;
}

.dashboard-status {
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 9px;
  color: var(--home-blue-dark);
  background: var(--home-soft);
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 0.035em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.dashboard-status i {
  color: var(--home-success);
}

.dashboard-aircraft-stage {
  position: relative;
  min-height: 360px;
  margin-top: 16px;
  overflow: hidden;
  border: 1px solid rgba(13, 143, 233, 0.1);
  border-radius: 18px;
  background: linear-gradient(
    145deg,
    #f4fbff 0%,
    #eaf7ff 58%,
    #ffffff 100%
  );
  display: flex;
  align-items: center;
  justify-content: center;
}

.dashboard-destination {
  position: absolute;
  top: 25px;
  left: 24px;
  z-index: 3;
  max-width: 220px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.dashboard-destination strong {
  font-size: 18px;
  line-height: 1.25;
}

.airplane-entry {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: plane-entry 1.05s 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.airplane-image {
  position: relative;
  z-index: 2;
  width: min(118%, 760px);
  max-height: 360px;
  margin-top: 34px;
  object-fit: contain;
  user-select: none;
  pointer-events: none;
  filter:
    drop-shadow(0 28px 23px rgba(7, 17, 38, 0.25))
    contrast(1.03)
    saturate(1.04);
  animation: plane-float 6s 1.4s ease-in-out infinite;
}

.aircraft-guide-line {
  position: absolute;
  right: 22px;
  bottom: 68px;
  left: 22px;
  height: 1px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(13, 143, 233, 0.3) 50%,
    transparent 100%
  );
}

.dashboard-progress-card {
  position: absolute;
  right: 22px;
  bottom: 22px;
  left: 22px;
  z-index: 4;
  padding: 16px;
  border: 1px solid var(--home-border);
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 18px 44px rgba(7, 17, 38, 0.15);
}

.progress-course-row {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
}

.progress-course-icon {
  width: 44px;
  height: 44px;
  border-radius: 11px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-course-icon i {
  font-size: 20px;
}

.progress-course-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.progress-course-copy strong {
  overflow: hidden;
  color: var(--home-navy);
  font-size: 14px;
  font-weight: 900;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.progress-course-row > b {
  color: var(--home-blue-dark);
  font-size: 15px;
  font-weight: 950;
}

.progress-track {
  height: 6px;
  margin-top: 5px;
  overflow: hidden;
  border-radius: 3px;
  background: #dfeaf2;
}

.progress-track span {
  display: block;
  width: 72%;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(
    90deg,
    var(--home-cyan),
    var(--home-blue)
  );
  transform-origin: left center;
  animation: progress-fill 1.1s 1.15s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.dashboard-feature-strip {
  margin-top: 14px;
  padding-top: 13px;
  border-top: 1px solid #e3ebf2;
  color: #526174;
  font-size: 10.5px;
  font-weight: 800;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.dashboard-feature-strip span {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.dashboard-feature-strip i {
  flex: 0 0 auto;
  color: var(--home-blue);
  font-size: 15px;
}

/* =========================================================
   TRUST
   ========================================================= */
.trust-section {
  position: relative;
  z-index: 3;
  padding: 36px 0;
  border-top: 1px solid rgba(13, 143, 233, 0.08);
  border-bottom: 1px solid var(--home-border);
  background: #ffffff;
}

.trust-layout {
  display: grid;
  grid-template-columns: 235px minmax(0, 1fr);
  align-items: center;
  gap: 38px;
}

.trust-heading {
  padding-right: 28px;
  border-right: 1px solid var(--home-border);
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.trust-heading span {
  color: var(--home-blue-dark);
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.trust-heading strong {
  color: var(--home-navy);
  font-size: 18px;
  line-height: 1.35;
  font-weight: 900;
}

.trust-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 24px;
}

.trust-item {
  min-width: 0;
  display: grid;
  grid-template-columns: 46px minmax(0, 1fr);
  align-items: start;
  gap: 13px;
}

.trust-icon {
  width: 46px;
  height: 46px;
  border: 1px solid rgba(13, 143, 233, 0.18);
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 9px 20px rgba(13, 143, 233, 0.16);
  display: flex;
  align-items: center;
  justify-content: center;
}

.trust-icon i {
  font-size: 20px;
}

.trust-copy h3 {
  margin: 0 0 5px;
  color: var(--home-navy);
  font-size: 14px;
  line-height: 1.35;
  font-weight: 900;
}

.trust-copy p {
  margin: 0;
  color: #526174;
  font-size: 12px;
  line-height: 1.58;
}

/* =========================================================
   LEARNING PATHS
   ========================================================= */
.paths-section {
  border-bottom: 1px solid rgba(13, 143, 233, 0.08);
  background: var(--home-page-soft);
}

.paths-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.path-card {
  position: relative;
  min-height: 640px;
  overflow: hidden;
  border: 1px solid #dceaf3;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: var(--shadow-card);
  display: flex;
  flex-direction: column;
  transition:
    transform 0.25s ease,
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.path-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  z-index: 3;
  height: 4px;
  background: linear-gradient(
    90deg,
    var(--home-cyan),
    var(--home-blue)
  );
  transform: scaleX(0.3);
  transform-origin: left center;
  transition: transform 0.25s ease;
}

.path-card:hover {
  border-color: rgba(13, 143, 233, 0.32);
  box-shadow: var(--shadow-card-hover);
  transform: translateY(-7px);
}

.path-card:hover::before {
  transform: scaleX(1);
}

.path-media {
  position: relative;
  height: 218px;
  margin: 18px 18px 0;
  overflow: hidden;
  border: 1px solid rgba(7, 17, 38, 0.07);
  border-radius: 15px;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
}

.path-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.55s cubic-bezier(0.16, 1, 0.3, 1);
}

.path-card:hover .path-media img {
  transform: scale(1.055);
}

.path-media-shade {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    rgba(7, 17, 38, 0.02) 34%,
    rgba(7, 17, 38, 0.54) 100%
  );
}

.path-number {
  position: absolute;
  bottom: 16px;
  left: 16px;
  z-index: 2;
  color: #ffffff;
  font-size: 12px;
  font-weight: 950;
  letter-spacing: 0.12em;
}

.path-icon {
  position: absolute;
  right: 14px;
  bottom: 14px;
  z-index: 2;
  width: 46px;
  height: 46px;
  border: 1px solid rgba(255, 255, 255, 0.74);
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 10px 24px rgba(7, 17, 38, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.path-icon i {
  font-size: 21px;
}

.path-card-body {
  flex: 1;
  padding: 24px 22px 22px;
  display: flex;
  flex-direction: column;
}

.path-label {
  color: var(--home-blue-dark);
  font-size: 10px;
  font-weight: 950;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.path-card h3 {
  margin: 11px 0 13px;
  color: var(--home-navy);
  font-size: 25px;
  line-height: 1.18;
  letter-spacing: -0.028em;
  font-weight: 900;
}

.path-card-body > p {
  margin: 0;
  color: #526174;
  font-size: 14px;
  line-height: 1.72;
}

.path-features {
  margin: 24px 0 28px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.path-features span {
  color: #334155;
  font-size: 12.5px;
  line-height: 1.45;
  font-weight: 750;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.path-features i {
  flex: 0 0 auto;
  margin-top: 1px;
  color: var(--home-blue);
  font-size: 16px;
}

.path-link {
  margin-top: auto;
  padding-top: 18px;
  border-top: 1px solid #e3ebf2;
  color: var(--home-navy);
  font-size: 13px;
  font-weight: 900;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.path-link i {
  font-size: 19px;
  transition: transform 0.2s ease;
}

.path-link:hover {
  color: var(--home-blue-dark);
}

.path-link:hover i {
  transform: translate(3px, -3px);
}

/* =========================================================
   CATEGORIES
   ========================================================= */
.categories-section {
  border-bottom: 1px solid rgba(13, 143, 233, 0.08);
  background: #ffffff;
}

.categories-layout {
  display: grid;
  grid-template-columns: minmax(300px, 0.72fr) minmax(580px, 1.28fr);
  align-items: start;
  gap: clamp(60px, 7vw, 112px);
}

.categories-intro {
  position: sticky;
  top: 110px;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.category-card {
  position: relative;
  min-height: 166px;
  padding: 22px;
  overflow: hidden;
  border: 1px solid #dfeaf2;
  border-radius: 17px;
  color: inherit;
  background: #ffffff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.065);
  text-decoration: none;
  display: grid;
  grid-template-columns: 50px minmax(0, 1fr) auto;
  align-items: start;
  gap: 14px;
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease,
    background-color 0.22s ease;
}

.category-card::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(
    90deg,
    var(--home-cyan),
    var(--home-blue)
  );
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 0.22s ease;
}

.category-card:hover {
  border-color: rgba(13, 143, 233, 0.36);
  color: inherit;
  background: #f8fcff;
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.1);
  transform: translateY(-4px);
}

.category-card:hover::after {
  transform: scaleX(1);
}

.category-icon {
  width: 50px;
  height: 50px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 13px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 9px 20px rgba(13, 143, 233, 0.16);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.22s ease;
}

.category-card:hover .category-icon {
  transform: translateY(-2px);
}

.category-icon i {
  font-size: 22px;
}

.category-copy h3 {
  margin: 3px 0 8px;
  color: var(--home-navy);
  font-size: 16px;
  line-height: 1.35;
  font-weight: 900;
}

.category-copy p {
  margin: 0;
  color: #526174;
  font-size: 12.5px;
  line-height: 1.62;
}

.category-arrow {
  margin-top: 5px;
  color: var(--home-blue-dark);
  font-size: 18px;
  transition: transform 0.2s ease;
}

.category-card:hover .category-arrow {
  transform: translateX(4px);
}

/* =========================================================
   EXPERIENCE
   ========================================================= */
.experience-section {
  position: relative;
  overflow: hidden;
  color: #ffffff;
  background:
    linear-gradient(
      112deg,
      rgba(255, 255, 255, 0.055) 0%,
      rgba(255, 255, 255, 0) 42%
    ),
    linear-gradient(
      108deg,
      var(--home-navy) 0%,
      var(--home-navy-hover) 48%,
      var(--home-blue) 100%
    );
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.12),
    inset 0 -1px 0 rgba(255, 255, 255, 0.12);
}

.experience-line-field {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.08;
  background-image:
    linear-gradient(
      90deg,
      transparent 0%,
      transparent 49.8%,
      rgba(255, 255, 255, 0.28) 50%,
      transparent 50.2%
    ),
    linear-gradient(
      rgba(255, 255, 255, 0.14) 1px,
      transparent 1px
    );
  background-size: 100% 100%, 100% 70px;
}

.experience-layout {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: minmax(340px, 0.82fr) minmax(600px, 1.18fr);
  align-items: center;
  gap: clamp(56px, 8vw, 118px);
}

.experience-main h2 {
  color: #ffffff;
  font-size: clamp(38px, 4.4vw, 58px);
  line-height: 1.05;
}

.experience-main > p {
  max-width: 650px;
  margin: 0;
  color: rgba(255, 255, 255, 0.91);
  font-size: 16px;
  line-height: 1.78;
}

.experience-note {
  margin-top: 28px;
  padding: 16px 17px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-left: 4px solid var(--home-cyan);
  border-radius: 10px;
  color: rgba(255, 255, 255, 0.94);
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 14px 30px rgba(7, 17, 38, 0.14);
  font-size: 13px;
  line-height: 1.65;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.experience-note i {
  flex: 0 0 auto;
  margin-top: 1px;
  color: var(--home-cyan);
  font-size: 18px;
}

.experience-image {
  position: relative;
  height: 258px;
  margin-top: 28px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 24px 52px rgba(7, 17, 38, 0.34);
}

.experience-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.experience-image:hover img {
  transform: scale(1.035);
}

.experience-image-shade {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    transparent 38%,
    rgba(7, 17, 38, 0.78) 100%
  );
}

.experience-image-caption {
  position: absolute;
  right: 18px;
  bottom: 16px;
  left: 18px;
  z-index: 2;
  color: #ffffff;
  font-size: 13px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 9px;
}

.experience-image-caption i {
  color: var(--home-cyan);
  font-size: 22px;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.benefit-card {
  position: relative;
  min-height: 228px;
  padding: 24px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 16px;
  color: var(--home-navy);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.16);
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.benefit-card:hover {
  border-color: rgba(255, 255, 255, 0.78);
  box-shadow: 0 22px 44px rgba(7, 17, 38, 0.22);
  transform: translateY(-5px);
}

.benefit-card::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(
    90deg,
    var(--home-cyan),
    var(--home-blue)
  );
}

.benefit-icon {
  width: 48px;
  height: 48px;
  margin-bottom: 26px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
}

.benefit-icon i {
  font-size: 22px;
}

.benefit-copy h3 {
  margin: 0 0 10px;
  color: var(--home-navy);
  font-size: 18px;
  line-height: 1.3;
  font-weight: 900;
}

.benefit-copy p {
  margin: 0;
  color: #526174;
  font-size: 13px;
  line-height: 1.7;
}

.benefit-index {
  position: absolute;
  top: 22px;
  right: 22px;
  color: #b8c8d5;
  font-size: 11px;
  font-weight: 950;
  letter-spacing: 0.1em;
}

/* =========================================================
   JOURNEY
   ========================================================= */
.journey-section {
  border-bottom: 1px solid rgba(13, 143, 233, 0.08);
  background: var(--home-page-soft);
}

.journey-grid {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.journey-card {
  position: relative;
  min-height: 260px;
  padding: 28px;
  border: 1px solid #dce8f1;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.085);
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.journey-card:hover {
  border-color: rgba(13, 143, 233, 0.32);
  box-shadow: 0 22px 44px rgba(15, 23, 42, 0.11);
  transform: translateY(-4px);
}

.journey-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.journey-number {
  min-width: 44px;
  padding-bottom: 9px;
  border-bottom: 3px solid var(--home-cyan);
  color: var(--home-blue-dark);
  font-size: 12px;
  font-weight: 950;
  letter-spacing: 0.12em;
}

.journey-icon {
  width: 46px;
  height: 46px;
  border: 1px solid rgba(13, 143, 233, 0.16);
  border-radius: 12px;
  color: #ffffff;
  background: linear-gradient(
    135deg,
    var(--home-cyan),
    var(--home-blue)
  );
  box-shadow: 0 10px 22px rgba(13, 143, 233, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
}

.journey-icon i {
  font-size: 20px;
}

.journey-card h3 {
  margin: 34px 0 11px;
  color: var(--home-navy);
  font-size: 22px;
  line-height: 1.25;
  font-weight: 900;
}

.journey-card p {
  margin: 0;
  color: #526174;
  font-size: 13.5px;
  line-height: 1.72;
}



/* =========================================================
   FINAL CTA
   ========================================================= */
.final-cta-section {
  padding: 0 0 100px;
  background: var(--home-page-soft);
}

.final-cta-card {
  position: relative;
  isolation: isolate;
  min-height: 360px;
  padding: clamp(42px, 6vw, 76px);
  overflow: hidden;
  border: 1px solid rgba(13, 143, 233, 0.24);
  border-radius: 22px;
  color: #ffffff;
  background: var(--home-navy);
  box-shadow: 0 26px 58px rgba(7, 17, 38, 0.22);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 46px;
}

.final-cta-media,
.final-cta-overlay,
.final-cta-lines {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.final-cta-media {
  z-index: -3;
  background-image: var(--cta-background-image);
  background-repeat: no-repeat;
  background-position: center 48%;
  background-size: cover;
  filter:
    brightness(1.12)
    saturate(1.08)
    contrast(1.02);
  transform: scale(1.035);
  transition:
    transform 1.1s cubic-bezier(0.16, 1, 0.3, 1),
    filter 0.45s ease;
}

.final-cta-card:hover .final-cta-media {
  filter:
    brightness(1.16)
    saturate(1.12)
    contrast(1.03);
  transform: scale(1.075);
}

/*
|--------------------------------------------------------------------------
| Voile allégé : l'image reste visible, le texte reste lisible
|--------------------------------------------------------------------------
*/
.final-cta-overlay {
  z-index: -2;
  background:
    linear-gradient(
      90deg,
      rgba(7, 17, 38, 0.86) 0%,
      rgba(7, 17, 38, 0.76) 30%,
      rgba(7, 17, 38, 0.54) 52%,
      rgba(7, 17, 38, 0.24) 74%,
      rgba(13, 143, 233, 0.10) 100%
    ),
    linear-gradient(
      180deg,
      rgba(7, 17, 38, 0.01) 0%,
      rgba(7, 17, 38, 0.16) 100%
    );
}

.final-cta-lines {
  z-index: -1;
  opacity: 0.62;
}

.final-cta-lines::before,
.final-cta-lines::after {
  content: '';
  position: absolute;
  background: rgba(255, 255, 255, 0.22);
}

.final-cta-lines::before {
  right: -80px;
  bottom: 54px;
  width: 460px;
  height: 1px;
  transform: rotate(-13deg);
}

.final-cta-lines::after {
  top: 0;
  right: 26%;
  width: 1px;
  height: 100%;
  background: linear-gradient(
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
}

.final-cta-card > *:not(.final-cta-media):not(.final-cta-overlay):not(.final-cta-lines) {
  position: relative;
  z-index: 2;
}

.final-cta-copy {
  max-width: 690px;
  padding-left: 22px;
  border-left: 3px solid var(--home-cyan);
}

.final-cta-label {
  color: #8be4f6;
  font-size: 10px;
  font-weight: 950;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.final-cta-label i {
  font-size: 18px;
}

.final-cta-copy h2 {
  color: #ffffff;
  font-size: clamp(38px, 4.8vw, 64px);
  line-height: 1.04;
  text-shadow: 0 12px 30px rgba(7, 17, 38, 0.42);
}

.final-cta-copy p {
  max-width: 690px;
  margin: 0;
  color: rgba(255, 255, 255, 0.96);
  font-size: 15px;
  line-height: 1.75;
  text-shadow: 0 4px 16px rgba(7, 17, 38, 0.46);
}

/* =========================================================
   MOTION
   ========================================================= */
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

.hero-load {
  opacity: 0;
  animation: hero-rise 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.hero-load-1 {
  animation-delay: 0.08s;
}

.hero-load-2 {
  animation-delay: 0.18s;
}

.hero-load-3 {
  animation-delay: 0.32s;
}

.hero-load-4 {
  animation-delay: 0.44s;
}

.hero-load-5 {
  animation-delay: 0.56s;
}

.hero-visual-load {
  opacity: 0;
  animation: hero-visual-in 1s 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes hero-rise {
  from {
    opacity: 0;
    transform: translateY(24px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes hero-visual-in {
  from {
    opacity: 0;
    transform: translateX(58px) translateY(16px) scale(0.97);
  }

  to {
    opacity: 1;
    transform: translateX(0) translateY(0) scale(1);
  }
}

@keyframes hero-line-draw {
  from {
    opacity: 0;
    transform: scaleX(0);
  }

  to {
    opacity: 1;
    transform: scaleX(1);
  }
}

@keyframes plane-entry {
  from {
    opacity: 0;
    transform: translateX(75px) translateY(18px) scale(0.9);
  }

  to {
    opacity: 1;
    transform: translateX(0) translateY(0) scale(1);
  }
}

@keyframes plane-float {
  0%,
  100% {
    transform: translateY(0) rotate(-0.6deg);
  }

  50% {
    transform: translateY(-9px) rotate(0deg);
  }
}

@keyframes progress-fill {
  from {
    transform: scaleX(0);
  }

  to {
    transform: scaleX(1);
  }
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 1199.98px) {
  .hero-container {
    grid-template-columns: minmax(390px, 0.92fr) minmax(430px, 1.08fr);
    gap: 46px;
  }

  .hero-dashboard {
    min-height: 545px;
  }

  .trust-layout {
    grid-template-columns: 210px minmax(0, 1fr);
    gap: 28px;
  }

  .trust-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .categories-layout,
  .experience-layout {
    gap: 54px;
  }
}

@media (max-width: 991.98px) {
  .page-container {
    width: min(100% - 36px, 900px);
  }

  .home-section {
    padding: 84px 0;
  }

  .hero-section {
    min-height: auto;
    padding: 62px 0 78px;
  }

  .hero-container,
  .categories-layout,
  .experience-layout {
    grid-template-columns: 1fr;
  }

  .hero-content {
    max-width: 780px;
  }

  .hero-visual {
    max-width: 780px;
  }

  .trust-layout {
    grid-template-columns: 1fr;
  }

  .trust-heading {
    padding: 0 0 20px;
    border-right: 0;
    border-bottom: 1px solid var(--home-border);
  }

  .paths-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .path-card:last-child {
    grid-column: 1 / -1;
    min-height: auto;
  }

  .categories-intro {
    position: static;
    max-width: 740px;
  }

  .experience-main {
    max-width: 760px;
  }

  .experience-section {
    background: linear-gradient(
      145deg,
      var(--home-navy) 0%,
      var(--home-navy-hover) 58%,
      var(--home-blue) 100%
    );
  }

}

@media (max-width: 767.98px) {
  .page-container {
    width: min(100% - 28px, 680px);
  }

  .home-section {
    padding: 70px 0;
  }

  .hero-section {
    padding: 48px 0 64px;
  }

  .hero-title {
    font-size: clamp(43px, 12vw, 62px);
  }

  .hero-description {
    font-size: 15.5px;
  }

  .hero-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .hero-actions .home-btn {
    width: 100%;
  }

  .hero-assurances {
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
  }

  .hero-dashboard {
    min-height: 510px;
    padding: 16px;
    border-radius: 20px;
  }

  .dashboard-toolbar {
    align-items: flex-start;
  }

  .dashboard-aircraft-stage {
    min-height: 315px;
  }

  .dashboard-destination {
    top: 22px;
    left: 18px;
  }

  .airplane-image {
    width: 126%;
    max-width: none;
  }

  .dashboard-progress-card {
    right: 16px;
    bottom: 16px;
    left: 16px;
  }

  .dashboard-feature-strip {
    grid-template-columns: 1fr;
    gap: 7px;
  }

  .trust-grid,
  .paths-grid,
  .categories-grid,
  .benefits-grid,
  .journey-grid {
    grid-template-columns: 1fr;
  }

  .path-card,
  .path-card:last-child {
    grid-column: auto;
    min-height: auto;
  }

  .heading-with-action {
    align-items: flex-start;
    flex-direction: column;
  }

  .category-card {
    min-height: auto;
  }

  .journey-card {
    min-height: auto;
  }

  .final-cta-section {
    padding-bottom: 70px;
  }

  .final-cta-card {
    min-height: 430px;
    align-items: stretch;
    justify-content: flex-end;
    flex-direction: column;
  }

  .final-cta-media {
    background-position: 66% center;
  }

  .final-cta-overlay {
    background:
      linear-gradient(
        180deg,
        rgba(7, 17, 38, 0.16) 0%,
        rgba(7, 17, 38, 0.42) 38%,
        rgba(7, 17, 38, 0.74) 66%,
        rgba(7, 17, 38, 0.92) 100%
      );
  }

  .final-cta-copy {
    max-width: none;
  }

  .home-btn-white {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .page-container {
    width: calc(100% - 24px);
  }

  .section-eyebrow,
  .hero-eyebrow {
    min-height: 34px;
    padding-inline: 11px;
    font-size: 9.5px;
  }

  .hero-title {
    font-size: 41px;
  }

  .hero-title-accent {
    padding-bottom: 8px;
  }

  .hero-dashboard {
    min-height: 490px;
  }

  .dashboard-status {
    padding-inline: 8px;
    font-size: 8px;
  }

  .dashboard-aircraft-stage {
    min-height: 295px;
  }

  .dashboard-destination {
    max-width: 170px;
  }

  .dashboard-destination strong {
    font-size: 15px;
  }

  .progress-course-row {
    grid-template-columns: 40px minmax(0, 1fr) auto;
  }

  .progress-course-icon {
    width: 40px;
    height: 40px;
  }

  .section-heading h2,
  .categories-intro h2,
  .experience-main h2,
  .final-cta-copy h2 {
    font-size: 35px;
  }

  .path-media {
    height: 200px;
  }

  .path-card h3 {
    font-size: 23px;
  }

  .category-card {
    grid-template-columns: 46px minmax(0, 1fr);
    padding: 18px;
  }

  .category-icon {
    width: 46px;
    height: 46px;
  }

  .category-arrow {
    display: none;
  }

  .experience-image {
    height: 230px;
  }

  .benefit-card,
  .journey-card {
    padding: 22px;
  }

  .final-cta-card {
    min-height: 440px;
    padding: 34px 24px;
    border-radius: 20px;
  }

  .final-cta-copy {
    padding-left: 16px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-load,
  .hero-visual-load,
  .airplane-entry,
  .airplane-image,
  .progress-track span,
  .hero-title-accent::after {
    opacity: 1 !important;
    animation: none !important;
    transform: none !important;
  }

  .home-btn,
  .section-text-link,
  .path-card,
  .path-media img,
  .category-card,
  .category-icon,
  .benefit-card,
  .experience-image img,
  .journey-card,
  .final-cta-media {
    transition: none !important;
  }

  .home-btn:hover,
  .path-card:hover,
  .category-card:hover,
  .benefit-card:hover,
  .journey-card:hover {
    transform: none !important;
  }
}
</style>
