<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import defaultImage from '@assets/images/default-user.png'

const page = usePage()

const logout = () => {
  router.post(route('logout'))
}

/* ================= AUTH USER PHOTO FIX ================= */
const isInvalidPhotoUrl = (photo) => {
  if (!photo) return true

  const value = String(photo).trim()

  return (
    value === '' ||
    value === '/' ||
    value === '/api' ||
    value === '/api/' ||
    value === 'api' ||
    value === 'api/' ||
    value.endsWith('/api') ||
    value.endsWith('/api/') ||
    value.includes('/api/') ||
    value.includes('default-user.png') ||
    value.includes('profile-photos/default')
  )
}

const normalizePhotoUrl = (photo) => {
  if (isInvalidPhotoUrl(photo)) {
    return defaultImage
  }

  const value = String(photo).trim()

  if (value.startsWith('data:image')) {
    return value
  }

  if (value.startsWith('blob:')) {
    return value
  }

  if (value.startsWith('http://') || value.startsWith('https://')) {
    try {
      const url = new URL(value)

      if (
        url.pathname === '/api' ||
        url.pathname === '/api/' ||
        url.pathname.startsWith('/api/')
      ) {
        return defaultImage
      }

      return `${window.location.origin}${url.pathname}`
    } catch (error) {
      return defaultImage
    }
  }

  if (value.startsWith('/api') || value.startsWith('api')) {
    return defaultImage
  }

  if (value.startsWith('/storage/')) {
    return value
  }

  if (value.startsWith('storage/')) {
    return `/${value}`
  }

  if (value.startsWith('/')) {
    return value
  }

  return `/storage/${value}`
}

const getAuthUserPhoto = computed(() => {
  const user = page.props.auth?.user

  if (!user) {
    return defaultImage
  }

  const possiblePhotos = [
    user.profile_photo_path,
    user.photo,
    user.profile_photo_url,
  ]

  const validPhoto = possiblePhotos.find((photo) => !isInvalidPhotoUrl(photo))

  if (!validPhoto) {
    return defaultImage
  }

  return normalizePhotoUrl(validPhoto)
})

const handleAuthImageError = (event) => {
  if (event?.target && event.target.src !== defaultImage) {
    event.target.src = defaultImage
  }
}
</script>

<script>
import { Link, router } from '@inertiajs/vue3'
import simplebar from 'simplebar-vue'

import { layoutMethods } from '@/state/helpers'
import i18n from '../i18n'

import us_flag from '@assets/images/flags/us.svg'
import spain from '@assets/images/flags/spain.svg'
import germany from '@assets/images/flags/germany.svg'
import italy from '@assets/images/flags/italy.svg'
import russia from '@assets/images/flags/russia.svg'
import china from '@assets/images/flags/china.svg'
import french from '@assets/images/flags/french.svg'
import ae from '@assets/images/flags/ae.svg'

import img1 from '@assets/images/products/img-1.png'
import img2 from '@assets/images/products/img-2.png'
import img3 from '@assets/images/products/img-3.png'
import img4 from '@assets/images/products/img-4.png'
import img5 from '@assets/images/products/img-5.png'

export default {
  components: {
    simplebar,
    Link,
  },

  data() {
    return {
      is_hidden: false,

      languages: [
        { flag: us_flag, language: 'en', title: 'English' },
        { flag: french, language: 'fr', title: 'Français' },
      ],

      cartItems: [
        {
          id: 1,
          productImage: img1,
          productName: 'Branded T-Shirts',
          productLink: '/ecommerce/product-details',
          quantity: '10 x $32',
          itemPrice: '320',
        },
        {
          id: 2,
          productImage: img2,
          productName: 'Bentwood Chair',
          productLink: '/ecommerce/product-details',
          quantity: '5 x $18',
          itemPrice: '89',
        },
        {
          id: 3,
          productImage: img3,
          productName: 'Borosil Paper Cup',
          productLink: '/ecommerce/product-details',
          quantity: '3 x $250',
          itemPrice: '750',
        },
        {
          id: 4,
          productImage: img4,
          productName: 'Gray Styled T-Shirt',
          productLink: '/ecommerce/product-details',
          quantity: '1 x $1250',
          itemPrice: '1250',
        },
        {
          id: 5,
          productImage: img5,
          productName: 'Stillbird Helmet',
          productLink: '/ecommerce/product-details',
          quantity: '2 x $495',
          itemPrice: '990',
        },
      ],

      lan: typeof window !== 'undefined' && window.localStorage.getItem('lms_language') === 'fr' ? 'fr' : 'en',
      text: null,
      flag: null,
      value: null,
      myVar: 1,
      removeNavigateListener: null,
    }
  },

  computed: {
    calculateTotalPrice() {
      return this.cartItems
        .reduce((total, item) => total + parseFloat(item.itemPrice), 0)
        .toFixed(2)
    },
  },

  mounted() {
    this.flag = this.$i18n?.locale || i18n.global?.locale || i18n.locale

    this.languages.forEach((item) => {
      if (item.language === this.flag) {
        const headerLangImg = document.getElementById('header-lang-img')

        if (headerLangImg) {
          headerLangImg.setAttribute('src', item.flag)
        }
      }
    })

    document.addEventListener('scroll', this.handleTopbarShadow)
    this.removeNavigateListener = router.on('navigate', () => {
      document.body.classList.remove('vertical-sidebar-enable')
      document.querySelector('.hamburger-icon')?.classList.remove('open')
      document.getElementById('topnav-hamburger-icon')?.setAttribute('aria-expanded', 'false')
    })

    const hamburgerIcon = document.getElementById('topnav-hamburger-icon')

    if (hamburgerIcon) {
      hamburgerIcon.addEventListener('click', this.toggleHamburgerMenu)
    }

    this.isCustomDropdown()
  },

  beforeUnmount() {
    document.removeEventListener('scroll', this.handleTopbarShadow)
    this.removeNavigateListener?.()

    const hamburgerIcon = document.getElementById('topnav-hamburger-icon')

    if (hamburgerIcon) {
      hamburgerIcon.removeEventListener('click', this.toggleHamburgerMenu)
    }
  },

  methods: {
    ...layoutMethods,

    handleTopbarShadow() {
      const pageTopbar = document.getElementById('page-topbar')

      if (pageTopbar) {
        document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50
          ? pageTopbar.classList.add('topbar-shadow')
          : pageTopbar.classList.remove('topbar-shadow')
      }
    },

    isCustomDropdown() {
      const searchOptions = document.getElementById('search-close-options')
      const dropdown = document.getElementById('search-dropdown')
      const searchInput = document.getElementById('search-options')

      return {
        searchOptions,
        dropdown,
        searchInput,
      }
    },

    toggleHamburgerMenu() {
      const windowSize = document.documentElement.clientWidth
      const layoutType = document.documentElement.getAttribute('data-layout')
      let isExpanded = false

      document.documentElement.setAttribute('data-sidebar-visibility', 'show')

      const visibilityType = document.documentElement.getAttribute('data-sidebar-visibility')

      document.querySelector('.hamburger-icon')?.classList.toggle('open')

      if (document.documentElement.getAttribute('data-layout') === 'horizontal') {
        document.body.classList.contains('menu')
          ? document.body.classList.remove('menu')
          : document.body.classList.add('menu')
      }

      if (
        visibilityType === 'show' &&
        (layoutType === 'vertical' || layoutType === 'semibox')
      ) {
        if (windowSize < 1025 && windowSize > 767) {
          document.body.classList.remove('vertical-sidebar-enable')

          document.documentElement.getAttribute('data-sidebar-size') === 'sm'
            ? document.documentElement.setAttribute('data-sidebar-size', '')
            : document.documentElement.setAttribute('data-sidebar-size', 'sm')
        } else if (windowSize > 1025) {
          document.body.classList.remove('vertical-sidebar-enable')

          document.documentElement.getAttribute('data-sidebar-size') === 'lg'
            ? document.documentElement.setAttribute('data-sidebar-size', 'sm')
            : document.documentElement.setAttribute('data-sidebar-size', 'lg')
        } else if (windowSize <= 767) {
          document.body.classList.toggle('vertical-sidebar-enable')
          document.documentElement.setAttribute('data-sidebar-size', 'lg')
        }

        isExpanded = windowSize <= 767
          ? document.body.classList.contains('vertical-sidebar-enable')
          : document.documentElement.getAttribute('data-sidebar-size') !== 'sm'
      }

      if (document.documentElement.getAttribute('data-layout') === 'twocolumn') {
        document.body.classList.contains('twocolumn-panel')
          ? document.body.classList.remove('twocolumn-panel')
          : document.body.classList.add('twocolumn-panel')
        isExpanded = document.body.classList.contains('twocolumn-panel')
      }

      if (layoutType === 'horizontal') isExpanded = document.body.classList.contains('menu')
      document.querySelector('.hamburger-icon')?.classList.toggle('open', isExpanded)
      document.getElementById('topnav-hamburger-icon')?.setAttribute('aria-expanded', String(isExpanded))
    },

    toggleMenu() {
      this.$parent.toggleMenu()
    },

    toggleRightSidebar() {
      this.$parent.toggleRightSidebar()
    },

    initFullScreen() {
      document.body.classList.toggle('fullscreen-enable')

      if (
        !document.fullscreenElement &&
        !document.mozFullScreenElement &&
        !document.webkitFullscreenElement
      ) {
        if (document.documentElement.requestFullscreen) {
          document.documentElement.requestFullscreen()
        } else if (document.documentElement.mozRequestFullScreen) {
          document.documentElement.mozRequestFullScreen()
        } else if (document.documentElement.webkitRequestFullscreen) {
          document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT)
        }
      } else {
        if (document.cancelFullScreen) {
          document.cancelFullScreen()
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen()
        } else if (document.webkitCancelFullScreen) {
          document.webkitCancelFullScreen()
        }
      }
    },

    setLanguage(locale, country, flag) {
      this.lan = locale
      this.text = country
      this.flag = flag

      window.localStorage.setItem('lms_language', locale)
      document.cookie = `lms_language=${locale}; path=/; max-age=31536000; samesite=lax`
      window.axios.defaults.headers.common['X-Locale'] = locale
      document.documentElement.setAttribute('lang', locale)
      document.documentElement.setAttribute('dir', 'ltr')

      const headerLangImg = document.getElementById('header-lang-img')

      if (headerLangImg) {
        headerLangImg.setAttribute('src', flag)
      }

      if (i18n.global) {
        i18n.global.locale = locale
      } else {
        i18n.locale = locale
      }
    },

    toggleDarkMode() {
      if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
        document.documentElement.setAttribute('data-bs-theme', 'light')
      } else {
        document.documentElement.setAttribute('data-bs-theme', 'dark')
      }

      const mode = document.documentElement.getAttribute('data-bs-theme')

      this.changeMode({
        mode,
      })
    },

    removeItem(cartItem) {
      this.cartItems = this.cartItems.filter((item) => item.id !== cartItem.id)
      this.$emit('cart-item-price', this.cartItems.length)
    },
  },
}
</script>

<template>
  <header id="page-topbar">
    <div class="layout-width">
      <div class="navbar-header">
        <div class="d-flex">
          <!-- LOGO -->
          <div class="navbar-brand-box horizontal-logo">
            <Link href="/" class="logo logo-dark" :aria-label="$t('ui-safesky-solution-home')">
              <span class="logo-sm">
                <img src="@assets/images/logo-sm.png" alt="" height="22" />
              </span>

              <span class="logo-lg">
                <img src="@assets/images/logo-blanche-.png" alt="" height="65" />
              </span>
            </Link>

            <Link href="/" class="logo logo-light" :aria-label="$t('ui-safesky-solution-home')">
              <span class="logo-sm">
                <img src="@assets/images/logo-sm.png" alt="" height="22" />
              </span>

              <span class="logo-lg">
                <img src="@assets/images/logo_safesky blue.webp" alt="" style="width: 75%;" />
              </span>
            </Link>
          </div>

          <button
            type="button"
            class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
            id="topnav-hamburger-icon"
            :aria-label="$t('t-menu')"
            aria-controls="navbar-nav"
            aria-expanded="false"
          >
            <span class="hamburger-icon">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </button>

          <!-- App Search -->
          <form class="app-search d-none d-md-block" v-if="is_hidden">
            <div class="position-relative">
              <input
                type="text"
                class="form-control"
                :placeholder="$t('ui-search')"
                autocomplete="off"
                id="search-options"
                value=""
              />

              <span class="mdi mdi-magnify search-widget-icon"></span>

              <span
                class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none"
                id="search-close-options"
              ></span>
            </div>
          </form>
        </div>

        <div class="d-flex align-items-center">
          <div class="ms-1 header-item d-none d-sm-flex" v-if="is_hidden">
            <BButton
              type="button"
              variant="ghost-secondary"
              class="btn-icon btn-topbar rounded-circle"
              data-toggle="fullscreen"
              @click="initFullScreen"
            >
              <i class="bx bx-fullscreen fs-22"></i>
            </BButton>
          </div>

          <div class="ms-1 header-item d-none d-sm-flex" v-if="is_hidden">
            <BButton
              type="button"
              variant="ghost-secondary"
              class="btn-icon btn-topbar rounded-circle light-dark-mode"
              @click="toggleDarkMode"
            >
              <i class="bx bx-moon fs-22"></i>
            </BButton>
          </div>

          <div class="ms-1 header-item d-flex">
            <BButton
              type="button"
              variant="ghost-secondary"
              class="btn-topbar d-inline-flex align-items-center gap-2"
              :aria-label="lan === 'fr' ? 'Switch language to English' : 'Passer l’interface en français'"
              :title="lan === 'fr' ? 'Switch language to English' : 'Passer l’interface en français'"
              @click="toggleLanguage"
            >
              <img :src="flag" alt="" width="20" height="15" />
              <span>{{ lan.toUpperCase() }}</span>
            </BButton>
          </div>

          <!-- User Dropdown -->
          <BDropdown
            variant="link"
            class="ms-sm-3 header-item topbar-user"
            toggle-class="rounded-circle arrow-none"
            menu-class="dropdown-menu-end"
            :offset="{ alignmentAxis: -14, crossAxis: 0, mainAxis: 0 }"
          >
            <template #button-content>
              <span class="d-flex align-items-center" v-if="$page.props.auth.user?.name">
                <img
                  v-if="$page.props.jetstream.managesProfilePhotos"
                  class="rounded-circle header-profile-user"
                  :src="getAuthUserPhoto"
                  :alt="$page.props.auth.user.name || 'User photo'"
                  @error="handleAuthImageError"
                />

                <span class="text-start ms-xl-2">
                  <span
                    class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"
                    v-if="$page.props.auth.user?.name"
                  >
                    {{ $page.props.auth.user.name }}
                  </span>

                  <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text"></span>
                </span>
              </span>
            </template>

            <h6 class="dropdown-header" v-if="$page.props.auth.user?.name">
              {{ $t('t-welcome-user', { name: $page.props.auth.user.name }) }}
            </h6>

            <Link class="dropdown-item" :href="route('profile.show')">
              <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
              <span class="align-middle">{{ $t('t-profile') }}</span>
            </Link>

            <div class="dropdown-divider"></div>

            <form method="POST" @submit.prevent="logout" class="dropdown-item">
              <BButton variant="none" type="submit" class="btn p-0">
                <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                {{ $t('t-logout') }}
              </BButton>
            </form>
          </BDropdown>
        </div>
      </div>
    </div>
  </header>
</template>
