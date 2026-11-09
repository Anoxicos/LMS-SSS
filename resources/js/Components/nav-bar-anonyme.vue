<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import safeSkyHomeLogo from '@assets/images/brand/safesky-home.svg'
import i18n from '../i18n'
import { useIndividualCourseCart } from '@/Composables/useIndividualCourseCart'

import usFlag from '@assets/images/flags/us.svg'
import franceFlag from '@assets/images/flags/fr.svg'

const page = usePage()

const { cartCount } = useIndividualCourseCart()

const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const openedDropdown = ref(null)
const languageMenuOpen = ref(false)
const languageTransitioning = ref(false)
const navCollapsed = ref(false)
let lastScrollY = 0
let collapseScrollY = 0
let languageTransitionTimer = null

const { proxy } = getCurrentInstance()

let dropdownTimer = null

const LANGUAGE_STORAGE_KEY = 'lms_language'
const PUBLIC_ACTIVE_MENU_KEY = 'activePublicMenuKey'


const languages = [
  {
    code: 'en',
    label: 'English',
    shortLabel: 'EN',
    flag: usFlag,
  },
  {
    code: 'fr',
    label: 'Français',
    shortLabel: 'FR',
    flag: franceFlag,
  },
  // {
  //   code: 'ar',
  //   label: 'العربية',
  //   shortLabel: 'AR',
  //   flag: aeFlag,
  // },
]

const getInitialLanguage = () => {
  if (typeof window === 'undefined') return 'en'

  const storedLanguage = window.localStorage.getItem(LANGUAGE_STORAGE_KEY)

  return languages.some(language => language.code === storedLanguage)
    ? storedLanguage
    : 'en'
}

const currentLanguageCode = ref(getInitialLanguage())

const currentLanguage = computed(() => {
  return (
    languages.find(language => language.code === currentLanguageCode.value) ||
    languages[0]
  )
})

const applyI18nLocale = locale => {
  const globalLocale = i18n.global.locale

  if (globalLocale && typeof globalLocale === 'object' && 'value' in globalLocale) {
    globalLocale.value = locale
  } else {
    i18n.global.locale = locale
  }
}

const applyDocumentLanguage = locale => {
  document.documentElement.setAttribute('lang', locale)

  // La direction reste toujours de gauche à droite, même en arabe.
  document.documentElement.setAttribute('dir', 'ltr')
}

const setLanguage = language => {
  if (!language?.code || language.code === currentLanguageCode.value) {
    languageMenuOpen.value = false
    return
  }

  window.clearTimeout(languageTransitionTimer)
  languageTransitioning.value = true
  languageMenuOpen.value = false

  languageTransitionTimer = window.setTimeout(() => {
    currentLanguageCode.value = language.code
    window.localStorage.setItem(LANGUAGE_STORAGE_KEY, language.code)
    document.cookie = `${LANGUAGE_STORAGE_KEY}=${language.code}; path=/; max-age=31536000; samesite=lax`
    window.axios.defaults.headers.common['X-Locale'] = language.code
    applyI18nLocale(language.code)
    applyDocumentLanguage(language.code)

    languageTransitionTimer = window.setTimeout(() => {
      languageTransitioning.value = false
      languageTransitionTimer = null
    }, 40)
  }, 180)
}

const toggleLanguageMenu = () => {
  languageMenuOpen.value = !languageMenuOpen.value
  userMenuOpen.value = false
  openedDropdown.value = null
}


const user = computed(() => page.props.auth?.user || null)

const userRoles = computed(() => {
  const roles = user.value?.roles || []

  if (Array.isArray(roles)) {
    return roles
      .map(role => {
        return String(
          typeof role === 'string'
            ? role
            : role?.name || ''
        ).toLowerCase()
      })
      .filter(Boolean)
  }

  if (typeof roles === 'string') {
    return [roles.toLowerCase()]
  }

  return []
})

const isLearner = computed(() => {
  return (
    userRoles.value.includes('learner') ||
    String(user.value?.role || '').toLowerCase() === 'learner'
  )
})

const dashboardHref = computed(() => {
  if (!user.value) return route('login')

  return isLearner.value
    ? route('learner-space.dashboard')
    : route('dashboard')
})

const learnerRegisterHref = computed(() => {
  return route('login', { mode: 'register' })
})

const canUseIndividualCart = computed(() => {
  return !user.value || isLearner.value
})

const cartHref = computed(() => {
  if (!user.value) {
    return learnerRegisterHref.value
  }

  if (isLearner.value) {
    return route('public.individual-requests.create')
  }

  return dashboardHref.value
})

const visibleCartCount = computed(() => {
  return cartCount.value > 99
    ? '99+'
    : cartCount.value
})

const userDisplayName = computed(() => {
  if (!user.value) return ''

  const fullName = [
    user.value.first_name,
    user.value.middle_name,
    user.value.last_name,
  ]
    .filter(Boolean)
    .map(part => String(part).trim())
    .filter(Boolean)
    .join(' ')

  return (
    fullName ||
    user.value.name ||
    user.value.email ||
    proxy.$i18n.t('t-learner')
  )
})

const userPhotoUrl = computed(() => {
  if (!user.value) return null

  if (user.value.profile_photo_url) {
    return user.value.profile_photo_url
  }

  if (user.value.profile_photo_path) {
    return `/storage/${String(user.value.profile_photo_path).replace(/^\/+/, '')}`
  }

  return null
})

const initials = computed(() => {
  const source = userDisplayName.value || user.value?.email || ''

  if (!source) return 'LR'

  return source
    .split(/\s+/)
    .filter(Boolean)
    .map(word => word.charAt(0))
    .join('')
    .substring(0, 2)
    .toUpperCase()
})

const navItems = computed(() => {
  const items = [{
    key: 'public-courses',
    label: proxy.$i18n.t('t-courses'),
    href: route('courses'),
    icon: 'ri-graduation-cap-line',
  }]

  if (isLearner.value) {
    items.push({
      key: 'learner-my-courses',
      label: proxy.$i18n.t('t-my-courses'),
      href: route('learner-space.my-courses'),
      icon: 'ri-book-open-line',
    })
  }

  return items
})

const hasChildren = item => {
  return Array.isArray(item.children) && item.children.length > 0
}

const getInitialActiveMenuKey = () => {
  if (typeof window === 'undefined') return 'home'

  return window.localStorage.getItem(PUBLIC_ACTIVE_MENU_KEY) || 'home'
}

const activePublicMenuKey = ref(getInitialActiveMenuKey())

const isPublicMenuActive = key => {
  return activePublicMenuKey.value === key
}

function savePublicActiveKey(key) {
  if (!key) return

  activePublicMenuKey.value = key

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(PUBLIC_ACTIVE_MENU_KEY, key)
  }
}

function activatePublicMenu(key) {
  savePublicActiveKey(key)
  closeAllMenus()
}

function handleExternalPublicMenu(event) {
  const key = event?.detail?.key

  if (!key) return

  savePublicActiveKey(key)
}

function initPublicActiveMenu() {
  if (typeof window === 'undefined') return

  const savedKey = window.localStorage.getItem(PUBLIC_ACTIVE_MENU_KEY)

  if (savedKey) {
    activePublicMenuKey.value = savedKey
    return
  }

  savePublicActiveKey('home')
}

const syncActiveMenuFromCurrentUrl = () => {
  const currentUrl = String(
    page.url ||
    (typeof window !== 'undefined'
      ? window.location.pathname
      : '/')
  )

  if (currentUrl.startsWith('/learner-space/my-courses')) {
    savePublicActiveKey('learner-my-courses')
    return
  }

  if (
    currentUrl.startsWith('/courses') ||
    currentUrl.startsWith('/individual-training-request') ||
    currentUrl.startsWith('/learner/register') ||
    currentUrl.startsWith('/learner-account/complete')
  ) {
    savePublicActiveKey('public-courses')
    return
  }

  savePublicActiveKey('home')
}

const openDropdown = key => {
  if (window.innerWidth <= 991) return

  if (dropdownTimer) {
    clearTimeout(dropdownTimer)
    dropdownTimer = null
  }

  openedDropdown.value = key
  userMenuOpen.value = false
  languageMenuOpen.value = false
}

const closeDropdownWithDelay = () => {
  if (window.innerWidth <= 991) return

  if (dropdownTimer) {
    clearTimeout(dropdownTimer)
  }

  dropdownTimer = setTimeout(() => {
    openedDropdown.value = null
    dropdownTimer = null
  }, 160)
}

const toggleDropdown = key => {
  if (dropdownTimer) {
    clearTimeout(dropdownTimer)
    dropdownTimer = null
  }

  openedDropdown.value = openedDropdown.value === key ? null : key
  userMenuOpen.value = false
  languageMenuOpen.value = false
}

const setMobileMenuState = value => {
  document.documentElement.classList.toggle('mobile-menu-lock', value)
  document.body.classList.toggle('mobile-menu-lock', value)
}

const closeAllMenus = () => {
  mobileMenuOpen.value = false
  userMenuOpen.value = false
  languageMenuOpen.value = false
  openedDropdown.value = null

  if (dropdownTimer) {
    clearTimeout(dropdownTimer)
    dropdownTimer = null
  }
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  userMenuOpen.value = false
  languageMenuOpen.value = false
  openedDropdown.value = null
}

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
  languageMenuOpen.value = false
  openedDropdown.value = null
}

const logout = () => {
  router.post(route('logout'), {}, {
    onFinish: () => closeAllMenus(),
  })
}

const handleDocumentClick = event => {
  const target = event?.target

  if (!(target instanceof Element)) return

  if (!target.closest('.language-dropdown')) {
    languageMenuOpen.value = false
  }

  if (!target.closest('.user-dropdown')) {
    userMenuOpen.value = false
  }
}

const handleEscape = event => {
  if (event.key !== 'Escape') return

  const mobileWasOpen = mobileMenuOpen.value
  if (!mobileWasOpen && !userMenuOpen.value && !languageMenuOpen.value && !openedDropdown.value) return

  event.preventDefault()
  closeAllMenus()
  if (mobileWasOpen) document.querySelector('.mobile-menu-btn')?.focus()
}

const handleWindowScroll = () => {
  const currentY = window.scrollY

  if (window.innerWidth > 991) {
    if (!navCollapsed.value && currentY > lastScrollY && currentY > 150) {
      navCollapsed.value = true
      collapseScrollY = currentY
      closeAllMenus()
    } else if (navCollapsed.value && currentY < lastScrollY && collapseScrollY - currentY > 80) {
      navCollapsed.value = false
    }
  } else {
    navCollapsed.value = false
  }

  lastScrollY = currentY
}

const handleWindowResize = () => {
  if (window.innerWidth <= 991) navCollapsed.value = false
}

watch(mobileMenuOpen, value => {
  setMobileMenuState(value)
})

watch(
  () => page.url,
  () => {
    syncActiveMenuFromCurrentUrl()
    closeAllMenus()
  }
)

onMounted(() => {
  applyI18nLocale(currentLanguageCode.value)
  applyDocumentLanguage(currentLanguageCode.value)

  window.addEventListener(
    'public-menu-active',
    handleExternalPublicMenu
  )

  document.addEventListener(
    'click',
    handleDocumentClick
  )
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleWindowScroll, { passive: true })
  window.addEventListener('resize', handleWindowResize)

  initPublicActiveMenu()
  syncActiveMenuFromCurrentUrl()
})

onBeforeUnmount(() => {
  setMobileMenuState(false)

  window.removeEventListener(
    'public-menu-active',
    handleExternalPublicMenu
  )

  document.removeEventListener(
    'click',
    handleDocumentClick
  )
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleWindowScroll)
  window.removeEventListener('resize', handleWindowResize)

  if (dropdownTimer) {
    clearTimeout(dropdownTimer)
    dropdownTimer = null
  }
  window.clearTimeout(languageTransitionTimer)
})
</script>

<template>
  <header class="public-header" :class="{ 'is-collapsed': navCollapsed, 'is-language-transitioning': languageTransitioning }">
    <div class="public-header-inner">
      <!-- Logo -->
      <Link
        href="/"
        class="public-logo"
        :aria-label="$t('ui-safesky-solution-home')"
        @click="activatePublicMenu('home')"
      >
        <img
          :src="safeSkyHomeLogo"
          :alt="$t('t-arrival-logo-alt')"
          class="public-logo-image"
        >
      </Link>

      <!-- Overlay mobile -->
      <button
        type="button"
        class="mobile-menu-overlay"
        :class="{ show: mobileMenuOpen }"
        :aria-label="$t('ui-close-menu')"
        @click="closeAllMenus"
      ></button>

      <!-- Navigation -->
      <nav
        id="public-navbar-nav"
        class="public-menu"
        :class="{ 'is-open': mobileMenuOpen }"
      >
        <div class="mobile-menu-head">
          <div>
            <strong>{{ $t('t-menu') }}</strong>
            <span>Safe Sky Solution</span>
          </div>

          <button
            type="button"
            class="mobile-close-btn"
            :aria-label="$t('ui-close-menu')"
            @click="closeAllMenus"
          >
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div
          v-for="item in navItems"
          :key="item.key"
          class="public-nav-item"
          :class="{
            'has-dropdown': hasChildren(item),
            'is-open': openedDropdown === item.key,
          }"
          @mouseenter="hasChildren(item) ? openDropdown(item.key) : null"
          @mouseleave="hasChildren(item) ? closeDropdownWithDelay() : null"
        >
          <template v-if="hasChildren(item)">
            <button
              type="button"
              class="public-nav-link dropdown-trigger"
              :aria-expanded="openedDropdown === item.key"
              aria-haspopup="true"
              @click.stop="toggleDropdown(item.key)"
            >
              <span class="nav-link-content">
                <i v-if="item.icon" :class="item.icon"></i>
                <span>{{ item.label }}</span>
              </span>

              <i class="ri-arrow-down-s-line nav-chevron"></i>
            </button>

            <div class="public-dropdown">
              <Link
                :href="item.href"
                :data-key="item.key"
                class="dropdown-feature-link"
                :class="{ 'is-active': isPublicMenuActive(item.key) }"
                @click="activatePublicMenu(item.key)"
              >
                <span>
                  <span class="dropdown-feature-title">
                    {{ item.label }} {{ $t('t-overview') }}
                  </span>

                  <span class="dropdown-feature-subtitle">
                    {{ $t('t-view-all') }} {{ item.label.toLowerCase() }}
                  </span>
                </span>

                <i class="ri-arrow-right-line"></i>
              </Link>

              <Link
                v-for="child in item.children"
                :key="child.key || child.href"
                :href="child.href"
                :data-key="child.key"
                class="public-dropdown-link"
                :class="{ 'is-active': isPublicMenuActive(child.key) }"
                @click="activatePublicMenu(child.key)"
              >
                <span class="dropdown-link-icon">
                  <i :class="child.icon"></i>
                </span>

                <span class="dropdown-link-content">
                  <span class="dropdown-link-title">
                    {{ child.label }}
                  </span>

                  <span class="dropdown-link-description">
                    {{ child.description }}
                  </span>
                </span>
              </Link>
            </div>
          </template>

          <template v-else>
            <Link
              :href="item.href"
              :data-key="item.key"
              class="public-nav-link"
              :class="{ 'is-active': isPublicMenuActive(item.key) }"
              @click="activatePublicMenu(item.key)"
            >
              <span class="nav-link-content">
                <i v-if="item.icon" :class="item.icon"></i>
                <span>{{ item.label }}</span>
              </span>
            </Link>
          </template>
        </div>

        <!-- Mobile actions -->
        <div class="mobile-actions">
          <div class="mobile-language-section">
            <div class="mobile-language-title">
              <span>
                <i class="ri-global-line"></i>
                {{ $t('t-language') }}
              </span>

              <strong>{{ currentLanguage.shortLabel }}</strong>
            </div>

            <div class="mobile-language-options">
              <button
                v-for="language in languages"
                :key="language.code"
                type="button"
                class="mobile-language-option"
                :class="{ selected: currentLanguageCode === language.code }"
                @click="setLanguage(language)"
              >
                <img
                  :src="language.flag"
                  :alt="language.label"
                >

                <span>{{ language.label }}</span>

                <i
                  v-if="currentLanguageCode === language.code"
                  class="ri-check-line"
                ></i>
              </button>
            </div>
          </div>
          <Link
            v-if="canUseIndividualCart"
            :href="cartHref"
            class="mobile-cart-link"
            @click="activatePublicMenu('public-courses')"
          >
            <span class="mobile-cart-main">
              <i class="ri-shopping-cart-2-line"></i>
              <span>{{ $t('t-my-cart') }}</span>
            </span>

            <strong v-if="cartCount">
              {{ visibleCartCount }}
            </strong>
          </Link>

          <template v-if="!user">
            <Link
              :href="route('login')"
              class="mobile-action-link"
              @click="closeAllMenus"
            >
              <i class="ri-login-box-line"></i>
              {{ $t('t-login') }}
            </Link>

            <Link
              :href="learnerRegisterHref"
              class="mobile-signup-btn"
              @click="closeAllMenus"
            >
              <i class="ri-user-add-line"></i>
              {{ $t('t-sign-up') }}
            </Link>
          </template>

          <template v-else>
            <div class="mobile-user-summary">
              <span class="mobile-user-avatar">
                <img
                  v-if="userPhotoUrl"
                  :src="userPhotoUrl"
                  :alt="userDisplayName"
                >
                <span v-else>{{ initials }}</span>
              </span>

              <span class="mobile-user-copy">
                <strong>{{ userDisplayName }}</strong>
                <small>{{ user.email }}</small>
              </span>
            </div>

            <Link
              :href="dashboardHref"
              class="mobile-action-link"
              data-key="home"
              @click="activatePublicMenu('home')"
            >
              <i class="ri-dashboard-line"></i>
              {{ $t('t-home') }}
            </Link>

            <Link
              v-if="isLearner"
              :href="route('learner-account.edit')"
              class="mobile-action-link"
              @click="closeAllMenus"
            >
              <i class="ri-user-settings-line"></i>
              {{ $t('t-personal-information') }}
            </Link>

            <button
              type="button"
              class="mobile-action-link logout-mobile"
              @click="logout"
            >
              <i class="ri-logout-box-r-line"></i>
              {{ $t('t-logout') }}
            </button>
          </template>
        </div>
      </nav>

      <!-- Desktop actions -->
      <div class="public-actions">
        <div class="language-dropdown">
          <button
            type="button"
            class="language-button"
            :class="{ active: languageMenuOpen }"
            :aria-expanded="languageMenuOpen"
            :aria-label="$t('ui-change-language')"
            @click.stop="toggleLanguageMenu"
          >
            <img
              :src="currentLanguage.flag"
              :alt="currentLanguage.label"
              class="language-current-flag"
            >

            <span class="language-current-code">
              {{ currentLanguage.shortLabel }}
            </span>

            <i
              class="ri-arrow-down-s-line language-chevron"
              :class="{ rotated: languageMenuOpen }"
            ></i>
          </button>

          <div
            v-if="languageMenuOpen"
            class="language-menu"
          >
            <div class="language-menu-header">
              <span>{{ $t('t-language') }}</span>
              <small>{{ $t('t-select-your-language') }}</small>
            </div>

            <button
              v-for="language in languages"
              :key="language.code"
              type="button"
              class="language-menu-item"
              :class="{ selected: currentLanguageCode === language.code }"
              @click="setLanguage(language)"
            >
              <img
                :src="language.flag"
                :alt="language.label"
                class="language-item-flag"
              >

              <span class="language-item-content">
                <strong>{{ language.label }}</strong>
                <small>{{ language.shortLabel }}</small>
              </span>

              <i
                v-if="currentLanguageCode === language.code"
                class="ri-check-line language-check"
              ></i>
            </button>
          </div>
        </div>

        <Link
          v-if="canUseIndividualCart"
          :href="cartHref"
          class="cart-action-button"
          :class="{ 'has-items': cartCount > 0 }"
          :title="$t('t-my-cart')"
          @click="activatePublicMenu('public-courses')"
        >
          <i class="ri-shopping-cart-2-line"></i>

          <span class="cart-action-label">
            {{ $t('t-my-cart') }}
          </span>

          <strong v-if="cartCount" class="cart-action-count">
            {{ visibleCartCount }}
          </strong>
        </Link>

        <template v-if="!user">
          <Link :href="route('login')" class="icon-btn" :aria-label="$t('t-login')" :title="$t('t-login')">
            <i class="ri-login-box-line"></i>
          </Link>

          <!-- <Link href="/cart" class="icon-btn" title="Cart">
            <i class="ri-shopping-cart-line"></i>
          </Link> -->

          <!-- <button type="button" class="icon-btn" title="Search">
            <i class="ri-search-line"></i>
          </button> -->

          <Link :href="learnerRegisterHref" class="signup-btn">
            <i class="ri-user-add-line"></i>
            {{ $t('t-sign-up') }}
          </Link>
        </template>

        <template v-else>
          <div class="user-dropdown">
            <button
              type="button"
              class="user-button"
              :class="{ active: userMenuOpen }"
              :aria-expanded="userMenuOpen"
              @click.stop="toggleUserMenu"
            >
              <span class="avatar">
                <img
                  v-if="userPhotoUrl"
                  :src="userPhotoUrl"
                  :alt="userDisplayName"
                >
                <span v-else>{{ initials }}</span>
              </span>

              <span class="user-button-copy">
                <strong class="user-name">
                  {{ userDisplayName }}
                </strong>
                <small v-if="isLearner">{{ $t('t-learner') }}</small>
              </span>

              <i
                class="ri-arrow-down-s-line user-chevron"
                :class="{ rotated: userMenuOpen }"
              ></i>
            </button>

            <div v-if="userMenuOpen" class="user-menu">
              <div class="user-menu-header">
                <span class="user-menu-avatar">
                  <img
                    v-if="userPhotoUrl"
                    :src="userPhotoUrl"
                    :alt="userDisplayName"
                  >
                  <span v-else>{{ initials }}</span>
                </span>

                <span class="user-menu-identity">
                  <strong>{{ userDisplayName }}</strong>
                  <small>{{ user.email }}</small>
                </span>
              </div>

              <Link
                :href="dashboardHref"
                class="user-menu-item"
                data-key="home"
                @click="activatePublicMenu('home')"
              >
                <i class="ri-dashboard-line"></i>
                {{ $t('t-home') }}
              </Link>

              <Link
                v-if="isLearner"
                :href="route('learner-space.my-courses')"
                class="user-menu-item"
                @click="activatePublicMenu('learner-my-courses')"
              >
                <i class="ri-book-open-line"></i>
                {{ $t('t-my-courses') }}
              </Link>

              <Link
                v-if="isLearner"
                :href="route('learner-account.edit')"
                class="user-menu-item"
                @click="closeAllMenus"
              >
                <i class="ri-user-settings-line"></i>
                {{ $t('t-personal-information') }}
              </Link>

              <div class="user-menu-separator"></div>

              <button
                type="button"
                class="user-menu-item logout-btn"
                @click="logout"
              >
                <i class="ri-logout-box-r-line"></i>
                {{ $t('t-logout') }}
              </button>
            </div>
          </div>

          <!-- <button type="button" class="icon-btn" title="Notifications">
            <i class="ri-notification-3-line"></i>
          </button>

          <Link href="/cart" class="icon-btn" title="Cart">
            <i class="ri-shopping-cart-line"></i>
          </Link> -->

          <!-- <button type="button" class="icon-btn" title="Search">
            <i class="ri-search-line"></i>
          </button> -->
        </template>
      </div>

      <!-- Mobile button -->
      <button
        type="button"
        class="mobile-menu-btn"
        :class="{ 'is-open': mobileMenuOpen }"
        :aria-label="mobileMenuOpen ? $t('ui-close-menu') : $t('ui-open-menu')"
        :aria-expanded="mobileMenuOpen"
        aria-controls="public-navbar-nav"
        @click="toggleMobileMenu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
    <button
      v-if="navCollapsed"
      type="button"
      class="desktop-expand-btn"
      :aria-label="$t('ui-expand-navigation')"
      @click="navCollapsed = false"
    >
      <i class="ri-menu-line"></i>
    </button>
  </header>
</template>

<style scoped>
:global(html),
:global(body) {
  width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

:global(html.mobile-menu-lock),
:global(body.mobile-menu-lock) {
  overflow: hidden !important;
  height: 100dvh;
}

.public-header {
  position: fixed;
  top: 18px;
  left: 50%;
  transform: translateX(-50%);
  width: min(1440px, calc(100% - 64px));
  height: 68px;
  background: rgba(242, 239, 232, 0.96);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.76);
  border-radius: 18px;
  z-index: 5000;
  overflow: visible;
  box-shadow: 0 10px 28px rgba(7, 11, 20, 0.16), inset 0 1px rgba(255, 255, 255, 0.8);
  transition: width 260ms ease, height 260ms ease, border-radius 260ms ease;
}

.public-header.is-collapsed {
  width: 56px;
  height: 56px;
  border-radius: 50%;
}

.public-header.is-collapsed .public-header-inner {
  display: none;
}

.desktop-expand-btn {
  display: none;
}

.public-header.is-collapsed .desktop-expand-btn {
  width: 100%;
  height: 100%;
  border: 0;
  border-radius: inherit;
  background: transparent;
  color: var(--ink);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  cursor: pointer;
}

.public-header-inner {
  width: 100%;
  max-width: 1480px;
  height: 100%;
  margin: 0 auto;
  padding: 0 clamp(16px, 2vw, 28px);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: clamp(16px, 2vw, 28px);
}

.public-header a:focus-visible,
.public-header button:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 3px;
}

/* Logo */
.public-logo {
  min-width: 156px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  text-decoration: none;
  flex-shrink: 0;
}

.public-logo-image {
  display: block;
  width: auto;
  max-width: 156px;
  height: 44px;
  max-height: 44px;
  object-fit: contain;
  object-position: left center;
}

/* Navigation */
.public-menu {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  flex: 1;
}

.mobile-menu-head {
  display: none;
}

.public-nav-item {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.public-nav-link {
  position: relative;
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 0 15px;
  border-radius: 11px;
  color: var(--slate);
  font-size: 14px;
  font-weight: 750;
  text-decoration: none;
  transition: color 160ms ease, background-color 160ms ease;
}

.nav-link-content {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.nav-link-content i {
  font-size: 17px;
}

.dropdown-trigger {
  border: none;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
}

.public-nav-link:hover {
  color: var(--ink);
  background: var(--paper);
}

.public-nav-link.is-active {
  color: var(--signal-deep);
  background: rgba(31, 92, 255, 0.09);
}

.public-nav-link.is-active::after {
  display: none;
}

.nav-chevron {
  font-size: 17px;
  color: var(--slate);
  transition: transform 0.2s ease;
}

.public-nav-item.is-open .nav-chevron {
  transform: rotate(180deg);
}

/* Dropdown */
.public-dropdown {
  position: absolute;
  top: calc(100% + 16px);
  left: 0;
  width: 340px;
  padding: 12px;
  border: 1px solid var(--sand);
  border-radius: 22px;
  background: var(--chalk);
  box-shadow: 0 26px 70px rgba(15, 23, 42, 0.16);
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  pointer-events: none;
  transition: all 0.2s ease;
  z-index: 5050;
}

.public-dropdown::before {
  content: "";
  position: absolute;
  top: -18px;
  left: 0;
  right: 0;
  height: 18px;
}

.public-nav-item.is-open .public-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
  pointer-events: auto;
}

.dropdown-feature-link {
  width: 100%;
  min-height: 70px;
  padding: 14px;
  margin-bottom: 8px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--paper), var(--paper));
  color: var(--ink);
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-decoration: none;
  transition: all 0.2s ease;
}

.dropdown-feature-link:hover {
  background: linear-gradient(135deg, rgba(31, 92, 255, 0.1), var(--paper));
  transform: translateY(-1px);
}

.dropdown-feature-link.is-active {
  background: linear-gradient(135deg, rgba(31, 92, 255, 0.1), var(--paper));
  color: var(--signal);
}

.dropdown-feature-title {
  display: block;
  font-size: 15px;
  font-weight: 900;
}

.dropdown-feature-subtitle {
  display: block;
  margin-top: 4px;
  font-size: 13px;
  color: var(--slate);
  font-weight: 600;
}

.public-dropdown-link {
  width: 100%;
  min-height: 64px;
  padding: 10px 12px;
  border-radius: 15px;
  color: var(--slate);
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.public-dropdown-link:hover {
  background: var(--paper);
  color: var(--signal);
}

.public-dropdown-link.is-active {
  background: var(--paper);
  color: var(--signal);
}

.dropdown-link-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: var(--paper);
  color: var(--signal);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
  flex-shrink: 0;
}

.public-dropdown-link:hover .dropdown-link-icon,
.public-dropdown-link.is-active .dropdown-link-icon {
  background: rgba(31, 92, 255, 0.1);
}

.dropdown-link-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.dropdown-link-title {
  font-size: 14px;
  font-weight: 900;
  color: inherit;
}

.dropdown-link-description {
  margin-top: 3px;
  font-size: 12px;
  font-weight: 600;
  color: var(--slate);
}

/* Desktop actions */
.public-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.icon-btn {
  width: 42px;
  height: 42px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  background: var(--chalk);
  color: var(--ink);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  background: var(--paper);
  border-color: var(--sand);
  transform: translateY(-1px);
}

.cart-action-button {
  position: relative;
  min-height: 42px;
  padding: 0 12px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  color: var(--ink);
  background: var(--chalk);
  text-decoration: none;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.cart-action-button > i {
  color: var(--signal);
  font-size: 20px;
}

.cart-action-button:hover,
.cart-action-button.has-items {
  color: var(--ink);
  border-color: rgba(31, 92, 255, 0.34);
  background: var(--paper);
  transform: translateY(-1px);
}

.cart-action-label {
  white-space: nowrap;
}

.cart-action-count {
  min-width: 24px;
  height: 23px;
  padding: 0 7px;
  border-radius: 8px;
  color: var(--chalk);
  background: var(--signal);
  font-size: 10px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.signup-btn {
  min-height: 42px;
  padding: 0 19px;
  border-radius: 12px;
  background: var(--signal);
  color: var(--chalk);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 800;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(31, 92, 255, 0.2);
  transition: background-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
}

.signup-btn:hover {
  color: var(--chalk);
  background: var(--signal-deep);
  transform: translateY(-1px);
}


/* Language dropdown */
.language-dropdown {
  position: relative;
}

.language-button {
  height: 42px;
  padding: 4px 12px 4px 6px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  background: var(--chalk);
  color: var(--ink);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.language-button:hover,
.language-button.active {
  background: var(--paper);
  border-color: var(--sand);
}

.language-button.active {
  box-shadow: 0 0 0 3px rgba(31, 92, 255, 0.1);
}

.language-current-flag {
  width: 27px;
  height: 27px;
  border: 1px solid var(--sand);
  border-radius: 7px;
  object-fit: cover;
  flex-shrink: 0;
}

.language-current-code {
  min-width: 22px;
  color: var(--ink);
  font-size: 13px;
  font-weight: 900;
}

.language-chevron {
  color: var(--slate);
  font-size: 18px;
  transition: transform 0.2s ease;
}

.language-chevron.rotated {
  transform: rotate(180deg);
}

.language-menu {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 245px;
  padding: 8px;
  border: 1px solid var(--sand);
  border-radius: 18px;
  background: var(--chalk);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
  z-index: 5050;
}

.language-menu-header {
  padding: 10px 12px 12px;
  margin-bottom: 5px;
  border-bottom: 1px solid var(--paper);
}

.language-menu-header span {
  display: block;
  color: var(--ink);
  font-size: 14px;
  font-weight: 900;
}

.language-menu-header small {
  display: block;
  margin-top: 2px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 600;
}

.language-menu-item {
  width: 100%;
  min-height: 52px;
  padding: 8px 10px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: var(--slate);
  display: flex;
  align-items: center;
  gap: 11px;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.language-menu-item:hover {
  background: var(--paper);
  color: var(--ink);
}

.language-menu-item.selected {
  background: var(--paper);
  color: var(--signal);
}

.language-item-flag {
  width: 34px;
  height: 34px;
  border: 1px solid var(--sand);
  border-radius: 9px;
  object-fit: cover;
  flex-shrink: 0;
}

.language-item-content {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.language-item-content strong {
  font-size: 13px;
  font-weight: 900;
}

.language-item-content small {
  margin-top: 2px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 700;
}

.language-check {
  color: var(--signal);
  font-size: 19px;
}

.nav-link-content,
.dropdown-link-content,
.language-current-code,
.mobile-language-title,
.mobile-language-option span,
.mobile-action-link,
.language-item-content {
  transition: opacity 180ms ease, transform 180ms ease;
}

.public-header.is-language-transitioning .nav-link-content,
.public-header.is-language-transitioning .dropdown-link-content,
.public-header.is-language-transitioning .language-current-code,
.public-header.is-language-transitioning .mobile-language-title,
.public-header.is-language-transitioning .mobile-language-option span,
.public-header.is-language-transitioning .mobile-action-link,
.public-header.is-language-transitioning .language-item-content {
  opacity: 0;
  transform: translateY(4px);
}

@media (prefers-reduced-motion: reduce) {
  .nav-link-content,
  .dropdown-link-content,
  .language-current-code,
  .mobile-language-title,
  .mobile-language-option span,
  .mobile-action-link,
  .language-item-content {
    transition: none;
  }
}

/* User dropdown */
.user-dropdown {
  position: relative;
}

.user-button {
  height: 42px;
  padding: 4px 11px 4px 5px;
  border: 1px solid var(--sand);
  border-radius: 12px;
  background: var(--chalk);
  color: var(--ink);
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-weight: 900;
  transition: all 0.2s ease;
}

.user-button:hover,
.user-button.active {
  background: var(--paper);
  border-color: var(--sand);
}

.user-button.active {
  box-shadow: 0 0 0 3px rgba(31, 92, 255, 0.1);
}

.avatar,
.user-menu-avatar,
.mobile-user-avatar {
  overflow: hidden;
  color: var(--chalk);
  background: linear-gradient(135deg, var(--signal) 0%, var(--signal) 100%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  flex-shrink: 0;
}

.avatar {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  font-size: 13px;
}

.avatar img,
.user-menu-avatar img,
.mobile-user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-button-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.user-name {
  max-width: 145px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-size: 12px;
  line-height: 1.15;
}

.user-button-copy small {
  margin-top: 2px;
  color: var(--slate);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.user-chevron {
  color: var(--slate);
  font-size: 17px;
  transition: transform 0.2s ease;
}

.user-chevron.rotated {
  transform: rotate(180deg);
}

.user-menu {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 285px;
  padding: 8px;
  border: 1px solid var(--sand);
  border-radius: 18px;
  background: var(--chalk);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
  z-index: 5050;
}

.user-menu-header {
  min-width: 0;
  padding: 10px 10px 13px;
  margin-bottom: 5px;
  border-bottom: 1px solid var(--paper);
  display: flex;
  align-items: center;
  gap: 11px;
}

.user-menu-avatar {
  width: 43px;
  height: 43px;
  border-radius: 12px;
  font-size: 13px;
}

.user-menu-identity {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.user-menu-identity strong,
.user-menu-identity small {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.user-menu-identity strong {
  color: var(--ink);
  font-size: 13px;
  font-weight: 900;
}

.user-menu-identity small {
  margin-top: 3px;
  color: var(--slate);
  font-size: 11px;
  font-weight: 650;
}

.user-menu-item {
  width: 100%;
  padding: 12px 14px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: var(--slate);
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 850;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-menu-item i {
  color: var(--signal);
  font-size: 18px;
}

.user-menu-item:hover {
  background: var(--paper);
  color: var(--ink);
}

.user-menu-separator {
  height: 1px;
  margin: 6px 8px;
  background: var(--paper);
}

.logout-btn {
  color: var(--danger);
}

.logout-btn i {
  color: var(--danger);
}

/* Mobile */
.mobile-menu-btn {
  width: 46px;
  height: 46px;
  border: 1px solid var(--sand);
  border-radius: 15px;
  background: var(--chalk);
  display: none;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 5px;
  cursor: pointer;
  flex-shrink: 0;
  z-index: 5100;
}

.mobile-menu-btn span {
  width: 18px;
  height: 2px;
  border-radius: 999px;
  background: var(--ink);
  transition: all 0.2s ease;
}

.mobile-menu-btn.is-open span:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.mobile-menu-btn.is-open span:nth-child(2) {
  opacity: 0;
}

.mobile-menu-btn.is-open span:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

.mobile-menu-overlay {
  display: none;
  border: none;
  padding: 0;
  background: rgba(7, 17, 38, 0.52);
}

.mobile-actions {
  display: none;
}

/* Tablet */
@media (max-width: 1120px) {
  .public-header-inner {
    padding: 0 22px;
    gap: 18px;
  }

  .public-menu {
    gap: 2px;
  }

  .public-nav-link {
    padding: 0 10px;
    font-size: 14px;
  }

  .user-button-copy {
    display: none;
  }

  .cart-action-button {
    width: 48px;
    padding: 0;
  }

  .cart-action-label {
    display: none;
  }

  .cart-action-count {
    position: absolute;
    top: -7px;
    right: -7px;
    min-width: 22px;
    height: 21px;
    padding: 0 6px;
    border: 2px solid var(--chalk);
    font-size: 9px;
  }
}

/* Mobile drawer */
@media (max-width: 991.98px) {
  .public-header {
    width: calc(100% - 32px);
    top: 12px;
    height: 64px;
    border-radius: 18px;
  }

  .public-header-inner {
    max-width: 100%;
    height: 64px;
    padding: 0 16px;
    position: relative;
  }

  .public-logo {
    min-width: 156px;
    height: 58px;
  }

  .public-logo-image {
    max-width: 156px;
    max-height: 52px;
  }

  .public-actions {
    display: none;
  }

  .mobile-menu-btn {
    display: inline-flex;
    position: relative;
    z-index: 5100;
  }

  .mobile-menu-overlay {
    display: block;
    position: fixed;
    inset: 76px 0 0 0;
    width: 100%;
    height: calc(100dvh - 76px);
    border: 0;
    padding: 0;
    background: rgba(7, 17, 38, 0.52);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease, visibility 0.25s ease;
    z-index: 5010;
  }

  .mobile-menu-overlay.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  .public-menu {
    position: fixed !important;
    top: 76px !important;
    right: 0 !important;
    bottom: 0 !important;
    left: auto !important;

    width: min(390px, 90vw) !important;
    height: calc(100dvh - 76px) !important;
    max-height: calc(100dvh - 76px) !important;

    padding: 18px !important;
    border-left: 1px solid var(--sand);
    background: var(--chalk);
    box-shadow: -20px 0 50px rgba(15, 23, 42, 0.18);

    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    justify-content: flex-start !important;
    gap: 8px !important;

    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain;

    transform: translate3d(100%, 0, 0);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;

    transition:
      transform 0.25s ease,
      opacity 0.2s ease,
      visibility 0.25s ease;

    z-index: 5020;
    will-change: transform;
  }

  .public-menu.is-open {
    transform: translate3d(0, 0, 0);
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  .mobile-menu-head {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 16px;
    margin-bottom: 8px;
    border-bottom: 1px solid var(--paper);
    flex-shrink: 0;
  }

  .mobile-menu-head strong {
    display: block;
    color: var(--ink);
    font-size: 20px;
    font-weight: 950;
  }

  .mobile-menu-head span {
    display: block;
    color: var(--slate);
    font-size: 13px;
    font-weight: 700;
    margin-top: 2px;
  }

  .mobile-close-btn {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border: none;
    border-radius: 14px;
    background: var(--paper);
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
  }

  .public-nav-item {
    width: 100%;
    display: block;
    flex-shrink: 0;
  }

  .public-nav-link {
    width: 100%;
    min-height: 52px;
    justify-content: space-between;
    padding: 0 15px;
    border-radius: 15px;
    font-size: 15px;
  }

  .public-nav-link.is-active::after {
    display: none;
  }

  .nav-link-content {
    min-width: 0;
  }

  .public-dropdown {
    position: static !important;
    width: 100%;
    margin-top: 8px;
    padding: 8px;
    box-shadow: none;
    border-radius: 16px;
    opacity: 1;
    visibility: visible;
    transform: none;
    pointer-events: auto;
    display: none;
    border-color: var(--paper);
    background: var(--paper);
  }

  .public-dropdown::before {
    display: none;
  }

  .public-nav-item.is-open .public-dropdown {
    display: block;
  }

  .dropdown-feature-link,
  .public-dropdown-link {
    background: var(--chalk);
  }


  .mobile-language-section {
    width: 100%;
    padding-bottom: 14px;
    margin-bottom: 4px;
    border-bottom: 1px solid var(--paper);
  }

  .mobile-language-title {
    min-height: 42px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .mobile-language-title > span {
    color: var(--slate);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 900;
  }

  .mobile-language-title i {
    color: var(--signal);
    font-size: 18px;
  }

  .mobile-language-title strong {
    padding: 4px 9px;
    border-radius: 8px;
    background: var(--paper);
    color: var(--signal);
    font-size: 11px;
    font-weight: 900;
  }

  .mobile-language-options {
    display: grid;
    grid-template-columns: 1fr;
    gap: 7px;
  }

  .mobile-language-option {
    width: 100%;
    min-height: 48px;
    padding: 7px 10px;
    border: 1px solid var(--sand);
    border-radius: 13px;
    background: var(--chalk);
    color: var(--slate);
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .mobile-language-option:hover {
    background: var(--paper);
  }

  .mobile-language-option.selected {
    border-color: rgba(31, 92, 255, 0.18);
    background: var(--paper);
    color: var(--signal);
  }

  .mobile-language-option img {
    width: 31px;
    height: 31px;
    border: 1px solid var(--sand);
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
  }

  .mobile-language-option span {
    flex: 1;
    font-size: 13px;
    font-weight: 900;
  }

  .mobile-language-option i {
    color: var(--signal);
    font-size: 18px;
  }

  .mobile-cart-link {
    width: 100%;
    min-height: 54px;
    padding: 0 13px;
    border: 1px solid rgba(13, 143, 233, 0.24);
    border-radius: 14px;
    color: var(--ink);
    background: var(--paper);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .mobile-cart-main {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 900;
  }

  .mobile-cart-main i {
    color: var(--signal);
    font-size: 20px;
  }

  .mobile-cart-link > strong {
    min-width: 27px;
    height: 25px;
    padding: 0 7px;
    border-radius: 8px;
    color: var(--chalk);
    background: var(--signal);
    font-size: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .mobile-user-summary {
    min-width: 0;
    padding: 11px;
    border: 1px solid var(--sand);
    border-radius: 14px;
    background: linear-gradient(180deg, var(--chalk) 0%, var(--paper) 100%);
    display: flex;
    align-items: center;
    gap: 11px;
  }

  .mobile-user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 13px;
  }

  .mobile-user-copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  .mobile-user-copy strong,
  .mobile-user-copy small {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .mobile-user-copy strong {
    color: var(--ink);
    font-size: 13px;
    font-weight: 900;
  }

  .mobile-user-copy small {
    margin-top: 3px;
    color: var(--slate);
    font-size: 11px;
    font-weight: 650;
  }

  .mobile-actions {
    display: grid !important;
    grid-template-columns: 1fr;
    gap: 10px;
    margin-top: 12px;
    padding-top: 14px;
    border-top: 1px solid var(--paper);
    flex-shrink: 0;
  }

  .mobile-action-link,
  .mobile-signup-btn {
    width: 100%;
    min-height: 50px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 950;
    text-decoration: none;
  }

  .mobile-action-link {
    border: 1px solid var(--sand);
    color: var(--ink);
    background: var(--chalk);
  }

  .mobile-signup-btn {
    background: var(--ink);
    color: var(--chalk);
  }

  .logout-mobile {
    border: 1px solid rgba(255, 77, 77, 0.1);
    color: var(--danger);
    cursor: pointer;
  }
}

@media (max-width: 520px) {
  .public-header {
    width: calc(100% - 24px);
    top: 12px;
    height: 64px;
  }

  .public-header-inner {
    height: 64px;
    padding: 0 18px;
  }

  .public-logo {
    min-width: 138px;
    height: 54px;
  }

  .public-logo-image {
    max-width: 128px;
    height: 40px;
    max-height: 40px;
  }

  .mobile-menu-overlay {
    inset: 76px 0 0 0;
    height: calc(100dvh - 76px);
  }

  .public-menu {
    top: 76px !important;
    width: 100% !important;
    max-width: 100% !important;
    height: calc(100dvh - 76px) !important;
    max-height: calc(100dvh - 76px) !important;
    border-left: none;
  }
}

</style>
