<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  color: { type: String, default: '56, 225, 255' },
  quantity: { type: Number, default: 42 },
  opacity: { type: Number, default: 0.6 },
  size: { type: Number, default: 1.4 },
  layer: { type: String, default: 'panel' },
})

const canvas = ref(null)
let frame = 0
let observer
let particles = []
let pointer = { x: -1000, y: -1000 }
let width = 0
let height = 0
let context
let reducedMotion = false

const draw = () => {
  if (!context || !width || !height) return
  context.clearRect(0, 0, width, height)

  for (const particle of particles) {
    let x = particle.x * width
    let y = particle.y * height
    const dx = x - pointer.x
    const dy = y - pointer.y
    const distance = Math.hypot(dx, dy)

    if (distance < 90 && distance > 0) {
      const force = (90 - distance) / 90 * 22
      x += dx / distance * force
      y += dy / distance * force
    }

    context.beginPath()
    context.arc(x, y, particle.radius, 0, Math.PI * 2)
    context.fillStyle = `rgba(${props.color}, ${particle.opacity})`
    context.fill()

    if (!reducedMotion) {
      particle.x += particle.vx
      particle.y += particle.vy
      if (particle.x < 0 || particle.x > 1) particle.vx *= -1
      if (particle.y < 0 || particle.y > 1) particle.vy *= -1
      particle.x = Math.max(0, Math.min(1, particle.x))
      particle.y = Math.max(0, Math.min(1, particle.y))
    }
  }

  if (!reducedMotion) frame = requestAnimationFrame(draw)
}

const resize = () => {
  const element = canvas.value
  if (!element) return
  const bounds = element.getBoundingClientRect()
  const ratio = Math.min(window.devicePixelRatio || 1, 2)
  width = bounds.width
  height = bounds.height
  element.width = width * ratio
  element.height = height * ratio
  context = element.getContext('2d')
  context.setTransform(ratio, 0, 0, ratio, 0, 0)
  if (!particles.length) particles = Array.from({ length: props.quantity }, () => ({
    x: Math.random(), y: Math.random(),
    vx: (Math.random() - 0.5) * 0.00009,
    vy: (Math.random() - 0.5) * 0.00009,
    radius: Math.random() * props.size + 0.6,
    opacity: Math.random() * props.opacity + props.opacity * 0.3,
  }))
  cancelAnimationFrame(frame)
  draw()
}

const movePointer = event => {
  const bounds = canvas.value?.getBoundingClientRect()
  if (bounds) pointer = { x: event.clientX - bounds.left, y: event.clientY - bounds.top }
}
const leavePointer = () => { pointer = { x: -1000, y: -1000 } }

onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const host = canvas.value?.parentElement
  host?.addEventListener('pointermove', movePointer, { passive: true })
  host?.addEventListener('pointerleave', leavePointer, { passive: true })
  observer = new ResizeObserver(resize)
  if (canvas.value) observer.observe(canvas.value)
  resize()
})

onBeforeUnmount(() => {
  cancelAnimationFrame(frame)
  observer?.disconnect()
  const host = canvas.value?.parentElement
  host?.removeEventListener('pointermove', movePointer)
  host?.removeEventListener('pointerleave', leavePointer)
})
</script>

<template>
  <canvas ref="canvas" class="auth-particles" :class="`is-${layer}`" aria-hidden="true" />
</template>

<style scoped>
.auth-particles { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.is-panel { z-index: 1; }
.is-backdrop { z-index: 0; }
</style>
