import { computed, ref } from 'vue'

const CART_STORAGE_KEY = 'lms:individual-course-cart'
const MAX_CART_ITEMS = 20

const cartItems = ref([])
let initialized = false

const normalizeCourse = course => ({
  id: Number(course.id),
  hashid: course.hashid || null,
  title: course.title || '',
  description: course.description || '',
  image_url: course.image_url || null,
  image: course.image || null,
  price: Number(course.price || 0),
  currency: course.currency || 'MAD',
  category: course.category
    ? {
        id: course.category.id,
        name: course.category.name,
      }
    : null,
})

const dispatchCartUpdate = () => {
  if (typeof window === 'undefined') return

  window.dispatchEvent(
    new CustomEvent('individual-cart-updated', {
      detail: {
        count: cartItems.value.length,
      },
    })
  )
}

const saveCart = () => {
  if (typeof window === 'undefined') return

  window.localStorage.setItem(
    CART_STORAGE_KEY,
    JSON.stringify(cartItems.value)
  )

  dispatchCartUpdate()
}

const loadCart = () => {
  if (initialized || typeof window === 'undefined') return

  initialized = true

  try {
    const saved = window.localStorage.getItem(CART_STORAGE_KEY)
    const parsed = saved ? JSON.parse(saved) : []

    cartItems.value = Array.isArray(parsed)
      ? parsed
          .filter(item => Number(item?.id) > 0)
          .slice(0, MAX_CART_ITEMS)
      : []
  } catch (error) {
    cartItems.value = []
    window.localStorage.removeItem(CART_STORAGE_KEY)
  }
}

export const useIndividualCourseCart = () => {
  loadCart()

  const cartCount = computed(() => cartItems.value.length)

  const cartTotal = computed(() => {
    return cartItems.value.reduce(
      (total, item) => total + Number(item.price || 0),
      0
    )
  })

  const isCourseInCart = courseId => {
    return cartItems.value.some(
      item => Number(item.id) === Number(courseId)
    )
  }

  const addCourse = course => {
    if (!course?.id || isCourseInCart(course.id)) return false
    if (cartItems.value.length >= MAX_CART_ITEMS) return false

    cartItems.value.push(normalizeCourse(course))
    saveCart()

    return true
  }

  const removeCourse = courseId => {
    const initialCount = cartItems.value.length

    cartItems.value = cartItems.value.filter(
      item => Number(item.id) !== Number(courseId)
    )

    if (cartItems.value.length !== initialCount) {
      saveCart()
      return true
    }

    return false
  }

  const toggleCourse = course => {
    if (isCourseInCart(course.id)) {
      removeCourse(course.id)
      return 'removed'
    }

    return addCourse(course) ? 'added' : 'unchanged'
  }

  const clearCart = () => {
    cartItems.value = []
    saveCart()
  }

  return {
    cartItems,
    cartCount,
    cartTotal,
    isCourseInCart,
    addCourse,
    removeCourse,
    toggleCourse,
    clearCart,
  }
}
