<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import i18n from '@/i18n'
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
const headlinePhraseElement = ref(null)
const activeHeadlineIndex = ref(0)
let gsapContext = null
let headlineTimer = null

const headlineTerms = [
  't-home-rotate-safety',
  't-home-rotate-maintenance',
  't-home-rotate-operations',
  't-home-rotate-crews',
]
const headlinePhrase = computed(() => i18n.global.t(headlineTerms[activeHeadlineIndex.value]))
const headlineWords = computed(() => headlinePhrase.value.split(' ').map((word, index) => {
  const letters = Array.from(word)
  if (index === 0 && letters.length) letters[0] = letters[0].toLocaleUpperCase()
  return letters
}))

const marqueeItems = [
  't-home-feature-ewis',
  't-home-feature-fts',
  't-home-feature-human-factors',
  't-home-feature-flight-operations',
  't-home-feature-safety',
  't-home-feature-regulations',
  't-home-feature-cabin-crew',
  't-home-feature-ground-handling',
  't-home-feature-dangerous-goods',
]

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

const faqs = [
  ['t-landing-faq-who-q', 't-landing-faq-who-a'],
  ['t-landing-faq-schedule-q', 't-landing-faq-schedule-a'],
  ['t-landing-faq-price-q', 't-landing-faq-price-a'],
  ['t-landing-faq-certificate-q', 't-landing-faq-certificate-a'],
]

onMounted(() => {
  const root = pageRoot.value

  if (!root || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return

  gsap.registerPlugin(ScrollTrigger)
  root.classList.add('gsap-ready')

  gsapContext = gsap.context(() => {
    gsap.timeline({ defaults: { ease: 'power3.out' } })
      .from('.hero-load-1', { y: 38, autoAlpha: 0, duration: 0.85 })
      .from('.hero-load-2', { y: 24, autoAlpha: 0, duration: 0.7 }, '-=0.48')
      .from('.hero-load-3', { y: 18, autoAlpha: 0, duration: 0.65 }, '-=0.34')
      .from('.hero-load-4', { y: 12, autoAlpha: 0, duration: 0.55 }, '-=0.3')
      .from('.hero-visual', { x: 64, y: 18, scale: 0.96, autoAlpha: 0, duration: 1.1 }, '-=1.1')
      .from('.hero-visual-orbit', { scale: 0.76, autoAlpha: 0, duration: 1.25 }, '-=0.9')

    root.querySelectorAll('[data-reveal]').forEach(element => {
      const delay = Number.parseFloat(element.style.getPropertyValue('--reveal-delay')) || 0

      gsap.fromTo(element, {
        y: 28,
        autoAlpha: 0,
      }, {
        y: 0,
        autoAlpha: 1,
        duration: 0.8,
        delay: delay / 1000,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: element,
          start: 'top 88%',
          once: true,
        },
      })
    })

    gsap.to('.hero-visual', {
      yPercent: 5,
      ease: 'none',
      scrollTrigger: {
        trigger: '.hero-section',
        start: 'top top',
        end: 'bottom top',
        scrub: 0.8,
      },
    })
  }, root)

  const rotateHeadline = () => {
    const phrase = headlinePhraseElement.value
    if (!phrase) return

    gsap.to(phrase.querySelectorAll('.hero-rotate-char'), {
      yPercent: -120,
      autoAlpha: 0,
      duration: 0.28,
      stagger: 0.012,
      ease: 'power2.in',
      onComplete: () => {
        activeHeadlineIndex.value = (activeHeadlineIndex.value + 1) % headlineTerms.length
        nextTick(() => {
          const chars = phrase.querySelectorAll('.hero-rotate-char')
          gsap.fromTo(chars, { yPercent: 120, autoAlpha: 0 }, {
            yPercent: 0,
            autoAlpha: 1,
            duration: 0.55,
            stagger: 0.018,
            ease: 'back.out(1.6)',
          })
        })
      },
    })
  }

  headlineTimer = window.setInterval(rotateHeadline, 2600)
})

onBeforeUnmount(() => {
  window.clearInterval(headlineTimer)
  if (headlinePhraseElement.value) {
    gsap.killTweensOf(headlinePhraseElement.value.querySelectorAll('.hero-rotate-char'))
  }
  gsapContext?.revert()
  gsapContext = null
})
</script>

<template>
  <main ref="pageRoot" class="aviation-home">
    <!-- =====================================================
         HERO
         ===================================================== -->
    <section class="hero-section">
      <div class="page-container hero-container">
        <div class="hero-content">
          <h1 class="hero-title hero-load hero-load-1">
            <span class="hero-title-prefix">{{ $t('t-landing-headline-prefix') }}</span>{{ ' ' }}
            <span class="hero-rotator" aria-hidden="true">
              <span class="hero-rotator-sizer">{{ $t('t-home-rotate-maintenance') }}</span>
              <span ref="headlinePhraseElement" class="hero-rotator-phrase">
                <span
                  v-for="(word, wordIndex) in headlineWords"
                  :key="`${activeHeadlineIndex}-${wordIndex}`"
                  class="hero-rotate-word"
                >
                  <span
                    v-for="(character, charIndex) in word"
                    :key="charIndex"
                    class="hero-rotate-char"
                  >{{ character }}</span>
                </span>
              </span>
            </span>
            <span class="visually-hidden">{{ headlinePhrase }}</span>
          </h1>

          <p class="hero-description hero-load hero-load-2">
            {{ $t('t-landing-subheadline') }}
          </p>

          <div class="hero-actions hero-load hero-load-3">
            <Link href="/courses" class="home-btn home-btn-primary">
              <span>{{ $t('t-landing-cta') }}</span>
              <i class="ri-arrow-right-line"></i>
            </Link>
          </div>

          <p class="hero-note hero-load hero-load-4">{{ $t('t-landing-cta-note') }}</p>
        </div>

        <Link
          href="/courses"
          class="hero-visual hero-visual-load"
          :aria-label="$t('t-landing-visual-link')"
        >
          <div class="hero-visual-orbit"></div>
          <img :src="airplaneHero" alt="" class="hero-aircraft" draggable="false" />
          <span class="hero-visual-caption">{{ $t('t-landing-visual-caption') }}</span>
        </Link>
      </div>
    </section>

    <section class="topic-marquee" :aria-label="$t('t-home-categories-eyebrow')">
      <div class="page-container marquee-layout">
        <span class="marquee-label">
          <i class="ri-radar-line" aria-hidden="true"></i>
          {{ $t('t-home-categories-eyebrow') }}
        </span>

        <div class="marquee-window">
          <div class="marquee-track">
            <div v-for="copy in 2" :key="copy" class="marquee-group" :aria-hidden="copy === 2">
              <span v-for="topic in marqueeItems" :key="`${copy}-${topic}`" class="marquee-topic">
                <i class="ri-asterisk" aria-hidden="true"></i>
                {{ $t(topic) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- =====================================================
         TRUST STRIP
         ===================================================== -->
    <section class="trust-section" :aria-label="$t('ui-platform-benefits')">
      <div class="page-container trust-layout">
        <div class="trust-heading" data-reveal>
          <span class="trust-heading-mark" aria-hidden="true">
            <i class="ri-flight-takeoff-line"></i>
          </span>
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

    <section class="home-section landing-faq-section" id="faq">
      <div class="page-container landing-faq-layout">
        <div class="landing-faq-heading" data-reveal>
          <span class="section-eyebrow">{{ $t('t-landing-faq-label') }}</span>
          <h2>{{ $t('t-landing-faq-title') }}</h2>
        </div>
        <div class="landing-faq-list">
          <details
            v-for="([question, answer], index) in faqs"
            :key="question"
            :open="index === 0"
          >
            <summary>{{ $t(question) }}<span aria-hidden="true">+</span></summary>
            <p>{{ $t(answer) }}</p>
          </details>
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
  --shadow-card: 0 10px 26px rgba(7, 11, 20, 0.055);
  --shadow-card-hover: 0 16px 34px rgba(7, 11, 20, 0.1);

  overflow: hidden;
  color: var(--ink);
  background: var(--paper);
}

.aviation-home,
.aviation-home * {
  box-sizing: border-box;
}

.aviation-home a:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

.page-container {
  width: min(1380px, calc(100% - 48px));
  margin: 0 auto;
}

.home-section {
  padding: 96px 0;
}

.landing-faq-section {
  background: var(--paper);
}

.landing-faq-layout {
  display: grid;
  grid-template-columns: 0.8fr 1.2fr;
  gap: 9vw;
}

.landing-faq-heading h2 {
  max-width: 420px;
  margin: 18px 0 0;
  color: var(--ink);
  font-size: clamp(36px, 4.4vw, 54px);
  line-height: 1.03;
  letter-spacing: -0.055em;
}

.landing-faq-list details {
  border-top: 1px solid var(--sand);
}

.landing-faq-list details:last-child {
  border-bottom: 1px solid var(--sand);
}

.landing-faq-list summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  padding: 19px 0;
  cursor: pointer;
  list-style: none;
  color: var(--ink);
  font-size: 15px;
  font-weight: 750;
}

.landing-faq-list summary::-webkit-details-marker {
  display: none;
}

.landing-faq-list summary span {
  color: var(--signal);
  font-size: 22px;
  font-weight: 400;
  transition: transform 0.2s ease;
}

.landing-faq-list details[open] summary span {
  transform: rotate(45deg);
}

.landing-faq-list details p {
  max-width: 600px;
  margin: 0;
  padding: 0 36px 20px 0;
  color: var(--slate);
  font-size: 14px;
  line-height: 1.7;
}

/* =========================================================
   SHARED UI
   ========================================================= */
.section-eyebrow {
  width: fit-content;
  min-height: 36px;
  padding: 0 13px;
  border: 1px solid rgba(31, 92, 255, 0.16);
  border-radius: 10px;
  color: var(--signal-deep);
  background: var(--paper);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.075em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.section-eyebrow i {
  font-size: 16px;
}

.section-eyebrow-light {
  border-color: rgba(255, 255, 255, 0.34);
  color: var(--chalk);
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
  color: var(--ink);
  font-size: clamp(38px, 4.3vw, 60px);
  line-height: 1.05;
}

.section-heading p,
.categories-intro > p {
  max-width: 730px;
  margin: 0;
  color: var(--slate);
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
  border-bottom: 2px solid rgba(31, 92, 255, 0.2);
  color: var(--ink);
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
  color: var(--signal-deep);
  border-color: var(--signal);
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
  color: var(--chalk);
  background: var(--ink);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.22);
}

.hero-actions .home-btn-primary {
  color: var(--ink);
  background: var(--jet);
  box-shadow: none;
}

.hero-actions .home-btn-primary:hover {
  color: var(--ink);
  background: #74ebff;
  box-shadow: none;
}

.home-btn-primary:hover,
.home-btn-dark:hover {
  color: var(--chalk);
  background: var(--midnight);
  box-shadow: 0 20px 40px rgba(7, 17, 38, 0.26);
}

.home-btn-secondary {
  border-color: rgba(255, 255, 255, 0.58);
  color: var(--chalk);
  background: rgba(7, 17, 38, 0.2);
  box-shadow: 0 12px 28px rgba(7, 17, 38, 0.12);
}

.home-btn-secondary:hover {
  border-color: rgba(255, 255, 255, 0.82);
  color: var(--chalk);
  background: rgba(7, 17, 38, 0.34);
}

.home-btn-dark {
  margin-top: 30px;
}

.home-btn-white {
  flex: 0 0 auto;
  min-width: 220px;
  border-color: rgba(255, 255, 255, 0.82);
  color: var(--ink);
  background: var(--chalk);
  box-shadow: 0 16px 34px rgba(7, 17, 38, 0.2);
}

.home-btn-white:hover {
  color: var(--ink);
  background: var(--paper);
}

/* =========================================================
   HERO
   ========================================================= */
.hero-section {
  position: relative;
  height: 100vh;
  height: 100svh;
  min-height: 100vh;
  min-height: 100svh;
  padding: clamp(76px, 10vh, 104px) 0 clamp(24px, 4vh, 42px);
  overflow: hidden;
  color: var(--chalk);
  background: radial-gradient(ellipse at 73% 42%, rgba(56, 225, 255, 0.13), transparent 34%), var(--ink);
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
}

.hero-container {
  position: relative;
  z-index: 2;
  height: 100%;
  display: grid;
  grid-template-columns: minmax(430px, 0.92fr) minmax(500px, 1.08fr);
  align-items: center;
  gap: clamp(58px, 7vw, 104px);
}

.hero-content {
  max-width: 630px;
}

.hero-title {
  margin: 0;
  color: var(--chalk);
  font-size: clamp(46px, 5vw, 72px);
  line-height: 1;
  letter-spacing: -0.055em;
  font-weight: 950;
}

.hero-title > span { display: inline; }

.hero-rotator {
  display: inline;
  max-width: 100%;
  vertical-align: baseline;
  overflow-wrap: anywhere;
}

.hero-rotator-phrase {
  max-width: 100%;
}

.hero-rotator-sizer { display: none; }
.hero-rotator-phrase {
  display: inline;
  color: var(--jet);
  text-shadow: 0 0 24px rgba(56, 225, 255, 0.2);
}
.hero-rotate-word { display: inline-flex; margin-right: 0.12em; white-space: nowrap; }
.hero-rotate-char { display: inline-block; will-change: transform, opacity; }

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.hero-description {
  max-width: 520px;
  margin: 22px 0 0;
  color: var(--mist);
  font-size: 17px;
  line-height: 1.65;
  font-weight: 550;
}

.hero-actions {
  margin-top: 26px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}

.hero-note {
  margin: 14px 0 0;
  color: var(--mist);
  font-size: 12px;
}

.hero-visual {
  position: relative;
  min-width: 0;
  height: min(62svh, 520px);
  display: grid;
  place-items: center;
  border-radius: 24px;
  text-decoration: none;
  cursor: pointer;
}

.hero-visual:focus-visible {
  outline: 3px solid var(--jet);
  outline-offset: 6px;
}

@media (min-width: 992px) and (max-height: 700px) {
  .hero-section { padding-block: 72px 22px; }
  .hero-container { gap: clamp(24px, 4vw, 58px); }
  .hero-title { font-size: clamp(42px, 4vw, 58px); }
  .hero-description { margin-top: 16px; font-size: 15px; }
  .hero-actions { margin-top: 18px; }
  .hero-visual { height: min(54svh, 360px); }
}

.hero-visual-orbit {
  --orbit-size: min(30vw, 360px, 52svh);
  --orbit-radius: calc((var(--orbit-size) / 2) - 8px);
  position: absolute;
  width: var(--orbit-size);
  aspect-ratio: 1;
  border: 1px solid rgba(56, 225, 255, 0.18);
  border-radius: 50%;
  box-shadow: 0 0 0 42px rgba(56, 225, 255, 0.025);
  transition: transform 700ms cubic-bezier(0.2, 0.7, 0.2, 1);
}

.hero-visual-orbit::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--jet);
  box-shadow: 0 0 10px 2px rgba(56, 225, 255, 0.46);
  transform: rotate(0deg) translateX(calc(var(--orbit-radius) - 4px));
  transform-origin: 0 50%;
  animation: radar-sweep 12s linear infinite;
}

@keyframes radar-sweep {
  to { transform: rotate(1turn) translateX(calc(var(--orbit-radius) - 4px)); }
}

.hero-aircraft {
  --aircraft-lift: 0px;
  position: relative;
  z-index: 1;
  width: min(116%, 700px);
  max-height: min(52vh, 480px);
  object-fit: contain;
  transform: translateY(var(--aircraft-lift));
  filter: drop-shadow(0 26px 32px rgba(0, 0, 0, 0.32));
  transition:
    transform 500ms cubic-bezier(0.2, 0.7, 0.2, 1),
    filter 500ms ease;
}

.hero-visual-caption {
  position: absolute;
  right: 3%;
  bottom: 8%;
  z-index: 2;
  padding: 10px 13px;
  border: 1px solid rgba(56, 225, 255, 0.2);
  border-radius: 8px;
  color: #dce3ee;
  background: rgba(14, 22, 38, 0.9);
  font-size: 11px;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  transition: transform 350ms ease;
}

.hero-visual-caption::after {
  content: '↗';
  margin-left: 8px;
  color: var(--jet);
}

.hero-visual:hover .hero-visual-orbit,
.hero-visual:focus-visible .hero-visual-orbit {
  transform: rotate(54deg) scale(1.02);
}

.hero-visual:hover .hero-aircraft,
.hero-visual:focus-visible .hero-aircraft {
  transform: translateY(calc(var(--aircraft-lift) - 8px)) rotate(-0.7deg) scale(1.02);
  filter: drop-shadow(0 30px 34px rgba(0, 0, 0, 0.38));
}

.hero-visual:hover .hero-visual-caption,
.hero-visual:focus-visible .hero-visual-caption {
  transform: translateX(6px);
}

.topic-marquee {
  overflow: hidden;
  padding: 20px 0;
  border-bottom: 1px solid rgba(56, 225, 255, 0.16);
  color: var(--chalk);
  background: var(--night);
}

.marquee-layout {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  gap: clamp(22px, 4vw, 58px);
}

.marquee-label {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--jet);
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  white-space: nowrap;
}

.marquee-label i { font-size: 17px; }

.marquee-window {
  min-width: 0;
  overflow: hidden;
  mask-image: linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
}

.marquee-track,
.marquee-group {
  display: flex;
  width: max-content;
  align-items: center;
}

.marquee-track {
  animation: topic-marquee 42s linear infinite;
}

.topic-marquee:hover .marquee-track,
.topic-marquee:focus-within .marquee-track { animation-play-state: paused; }

.marquee-group { gap: 34px; padding-right: 34px; }

.marquee-topic {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: rgba(255, 255, 255, 0.78);
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.marquee-topic i { color: var(--jet); font-size: 13px; }

@keyframes topic-marquee {
  to { transform: translateX(-50%); }
}

/* =========================================================
   TRUST
   ========================================================= */
.trust-section {
  position: relative;
  z-index: 3;
  padding: 36px 0;
  border-top: 1px solid var(--sand);
  border-bottom: 1px solid var(--sand);
  background: var(--chalk);
}

.trust-layout {
  display: grid;
  grid-template-columns: 235px minmax(0, 1fr);
  align-items: center;
  gap: 38px;
}

.trust-heading {
  position: relative;
  padding: 2px 28px 2px 18px;
  border-left: 3px solid var(--jet);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 9px;
}

.trust-heading-mark {
  width: 34px;
  height: 34px;
  border: 1px solid rgba(56, 225, 255, 0.35);
  border-radius: 10px;
  color: var(--signal-deep);
  background: rgba(56, 225, 255, 0.12);
  display: grid;
  place-items: center;
}

.trust-heading-mark i {
  font-size: 18px;
}

.trust-heading > span:not(.trust-heading-mark) {
  color: var(--signal-deep);
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.trust-heading strong {
  color: var(--ink);
  font-size: clamp(20px, 1.8vw, 25px);
  line-height: 1.12;
  letter-spacing: -0.035em;
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
  border: 1px solid var(--sand);
  border-radius: 12px;
  color: var(--signal-deep);
  background: var(--paper);
  display: flex;
  align-items: center;
  justify-content: center;
}

.trust-icon i {
  font-size: 20px;
}

.trust-copy h3 {
  margin: 0 0 5px;
  color: var(--ink);
  font-size: 14px;
  line-height: 1.35;
  font-weight: 900;
}

.trust-copy p {
  margin: 0;
  color: var(--slate);
  font-size: 12px;
  line-height: 1.58;
}

/* =========================================================
   LEARNING PATHS
   ========================================================= */
.paths-section {
  padding-block: 104px 112px;
  border-bottom: 1px solid var(--sand);
  background: var(--paper);
}

.paths-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.path-card {
  position: relative;
  min-height: 590px;
  overflow: hidden;
  border: 1px solid var(--sand);
  border-radius: 20px;
  background: var(--chalk);
  box-shadow: var(--shadow-card);
  display: flex;
  flex-direction: column;
  transition:
    transform 0.25s ease,
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.path-card:hover {
  border-color: rgba(31, 92, 255, 0.28);
  box-shadow: var(--shadow-card-hover);
  transform: translateY(-7px);
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
    var(--signal),
    var(--signal)
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
  color: var(--chalk);
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
  border: 1px solid rgba(255, 255, 255, 0.86);
  border-radius: 12px;
  color: var(--ink);
  background: var(--paper);
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
  color: var(--signal-deep);
  font-size: 10px;
  font-weight: 950;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.path-card h3 {
  margin: 11px 0 13px;
  color: var(--ink);
  font-size: 25px;
  line-height: 1.18;
  letter-spacing: -0.028em;
  font-weight: 900;
}

.path-card-body > p {
  margin: 0;
  color: var(--slate);
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
  color: var(--slate);
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
  color: var(--signal);
  font-size: 16px;
}

.path-link {
  margin-top: auto;
  padding-top: 18px;
  border-top: 1px solid var(--sand);
  color: var(--ink);
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
  color: var(--signal-deep);
}

.path-link:hover i {
  transform: translate(3px, -3px);
}

/* =========================================================
   CATEGORIES
   ========================================================= */
.categories-section {
  padding-block: 112px 120px;
  border-bottom: 1px solid var(--sand);
  background: var(--chalk);
}

.categories-layout {
  display: grid;
  grid-template-columns: minmax(300px, 0.72fr) minmax(580px, 1.28fr);
  align-items: start;
  gap: clamp(60px, 7vw, 112px);
}

.categories-intro {
  position: relative;
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
  border: 1px solid var(--sand);
  border-radius: 17px;
  color: inherit;
  background: var(--chalk);
  box-shadow: var(--shadow-card);
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

.category-card:hover {
  border-color: rgba(31, 92, 255, 0.3);
  color: inherit;
  background: var(--paper);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.1);
  transform: translateY(-4px);
}

.category-icon {
  width: 50px;
  height: 50px;
  border: 1px solid var(--sand);
  border-radius: 13px;
  color: var(--signal-deep);
  background: var(--paper);
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
  color: var(--ink);
  font-size: 16px;
  line-height: 1.35;
  font-weight: 900;
}

.category-copy p {
  margin: 0;
  color: var(--slate);
  font-size: 12.5px;
  line-height: 1.62;
}

.category-arrow {
  margin-top: 5px;
  color: var(--signal-deep);
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
  padding-block: 108px;
  position: relative;
  overflow: hidden;
  color: var(--chalk);
  background: radial-gradient(ellipse at 92% 10%, rgba(56, 225, 255, 0.09), transparent 28%), var(--ink);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.12),
    inset 0 -1px 0 rgba(255, 255, 255, 0.12);
}

.experience-layout {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: minmax(0, 0.82fr) minmax(0, 1.18fr);
  align-items: center;
  gap: clamp(56px, 8vw, 118px);
}

.experience-main h2 {
  color: var(--chalk);
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
  border-radius: 10px;
  color: var(--mist);
  background: rgba(255, 255, 255, 0.06);
  font-size: 13px;
  line-height: 1.65;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.experience-note i {
  flex: 0 0 auto;
  margin-top: 1px;
  color: var(--jet);
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
  color: var(--chalk);
  font-size: 13px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 9px;
}

.experience-image-caption i {
  color: var(--signal);
  font-size: 22px;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.benefit-card {
  position: relative;
  min-height: 210px;
  padding: 24px;
  overflow: hidden;
  border: 1px solid var(--sand);
  border-radius: 16px;
  color: var(--ink);
  background: var(--paper);
  box-shadow: 0 10px 26px rgba(7, 11, 20, 0.12);
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.benefit-card:hover {
  border-color: rgba(56, 225, 255, 0.42);
  box-shadow: 0 16px 32px rgba(7, 11, 20, 0.2);
  transform: translateY(-5px);
}

.benefit-icon {
  width: 48px;
  height: 48px;
  margin-bottom: 20px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  color: var(--signal-deep);
  background: var(--chalk);
  display: flex;
  align-items: center;
  justify-content: center;
}

.benefit-icon i {
  font-size: 22px;
}

.benefit-copy h3 {
  margin: 0 0 10px;
  color: var(--ink);
  font-size: 18px;
  line-height: 1.3;
  font-weight: 900;
}

.benefit-copy p {
  margin: 0;
  color: var(--slate);
  font-size: 13px;
  line-height: 1.7;
}

.benefit-index {
  position: absolute;
  top: 22px;
  right: 22px;
  color: var(--mist);
  font-size: 11px;
  font-weight: 950;
  letter-spacing: 0.1em;
}

/* =========================================================
   JOURNEY
   ========================================================= */
.journey-section {
  padding-block: 92px 112px;
  border-bottom: 1px solid var(--sand);
  background: var(--paper);
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
  border: 1px solid var(--sand);
  border-radius: 18px;
  background: var(--chalk);
  box-shadow: var(--shadow-card);
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.journey-card:hover {
  border-color: rgba(31, 92, 255, 0.28);
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
  border-bottom: 3px solid var(--signal);
  color: var(--signal-deep);
  font-size: 12px;
  font-weight: 950;
  letter-spacing: 0.12em;
}

.journey-icon {
  width: 46px;
  height: 46px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  color: var(--signal-deep);
  background: var(--paper);
  display: flex;
  align-items: center;
  justify-content: center;
}

.journey-icon i {
  font-size: 20px;
}

.journey-card h3 {
  margin: 34px 0 11px;
  color: var(--ink);
  font-size: 22px;
  line-height: 1.25;
  font-weight: 900;
}

.journey-card p {
  margin: 0;
  color: var(--slate);
  font-size: 13.5px;
  line-height: 1.72;
}



/* =========================================================
   FINAL CTA
   ========================================================= */
.final-cta-section {
  padding: 0 0 100px;
  background: var(--paper);
}

.final-cta-card {
  position: relative;
  isolation: isolate;
  min-height: 360px;
  padding: clamp(42px, 6vw, 76px);
  overflow: hidden;
  border: 1px solid rgba(56, 225, 255, 0.24);
  border-radius: 22px;
  color: var(--chalk);
  background: var(--ink);
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
  border-left: 3px solid var(--signal);
}

.final-cta-label {
  color: var(--jet);
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
  color: var(--chalk);
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
.gsap-ready [data-reveal] { visibility: hidden; }

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 1199.98px) {
  .hero-container {
    grid-template-columns: minmax(390px, 0.92fr) minmax(430px, 1.08fr);
    gap: 46px;
  }

  .hero-visual { height: min(56svh, 430px); }

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

  .marquee-layout {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .landing-faq-layout {
    grid-template-columns: 1fr;
    gap: 36px;
  }

  .hero-section {
    height: auto;
    min-height: 100vh;
    min-height: 100svh;
    padding: 62px 0 78px;
  }

  .hero-container,
  .categories-layout,
  .experience-layout {
    grid-template-columns: 1fr;
  }

  .hero-container { height: auto; }

  .hero-content {
    max-width: 780px;
  }

  .hero-visual {
    height: auto;
    min-height: 230px;
    max-width: 780px;
  }

  .trust-layout {
    grid-template-columns: 1fr;
  }

  .trust-heading {
    padding: 0 0 20px 16px;
    border-bottom: 1px solid var(--sand);
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
      var(--ink) 0%,
      var(--midnight) 58%,
      var(--signal) 100%
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
    padding: 96px 0 32px;
  }

  .hero-container {
    gap: 22px;
  }

  .hero-title {
    font-size: clamp(43px, 12vw, 62px);
  }

  .hero-description {
    font-size: 15.5px;
  }

  .hero-actions {
    align-items: flex-start;
    flex-direction: row;
  }

  .hero-actions .home-btn {
    width: auto;
  }

  .hero-visual {
    height: clamp(180px, 52vw, 240px);
    min-height: 180px;
  }

  .hero-aircraft {
    --aircraft-lift: -2cm;
    width: 115%;
    max-height: 100%;
  }

  .hero-visual-orbit {
    --orbit-size: clamp(180px, min(64vw, 58svh), 260px);
    box-shadow:
      0 0 0 28px rgba(56, 225, 255, 0.035),
      0 0 0 48px rgba(56, 225, 255, 0.025);
  }

  .hero-visual-caption {
    right: 0;
    bottom: 0;
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

  .section-eyebrow {
    min-height: 34px;
    padding-inline: 11px;
    font-size: 9.5px;
  }

  .hero-title {
    font-size: clamp(42px, 12vw, 48px);
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

@media (max-width: 767.98px) and (max-height: 720px) {
  .hero-section {
    padding: 88px 0 20px;
  }

  .hero-container {
    gap: 14px;
  }

  .hero-title {
    font-size: clamp(39px, 11vw, 44px);
  }

  .hero-description {
    margin-top: 16px;
    font-size: 14.5px;
    line-height: 1.5;
  }

  .hero-actions {
    margin-top: 18px;
  }

  .hero-note {
    margin-top: 10px;
  }

  .hero-visual {
    height: clamp(168px, 47vw, 190px);
    min-height: 168px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .marquee-track {
    width: 100%;
    flex-wrap: wrap;
    animation: none !important;
  }

  .marquee-group {
    width: 100%;
    flex-wrap: wrap;
    gap: 12px 22px;
    padding: 0;
  }

  .marquee-group[aria-hidden="true"] { display: none; }

  .hero-load,
  .hero-visual-load,
  .hero-aircraft {
    opacity: 1 !important;
    animation: none !important;
    transform: none !important;
  }

  .hero-visual-orbit,
  .hero-aircraft,
  .hero-visual-caption {
    transition: none;
  }

  .hero-visual-orbit::after {
    animation: none;
    transform: rotate(0deg) translateX(calc(var(--orbit-radius) - 4px));
  }

  .hero-visual:hover .hero-visual-orbit,
  .hero-visual:focus-visible .hero-visual-orbit,
  .hero-visual:hover .hero-aircraft,
  .hero-visual:focus-visible .hero-aircraft {
    transform: none;
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
