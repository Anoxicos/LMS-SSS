<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const visible = ref(false)
const position = ref({ x: 0, y: 0 })
const cursorState = ref('default')
const supportsCustomCursor = ref(false)
let previousTarget = null

const stateForCursor = cursor => {
  if (cursor === 'pointer') return 'pointer'
  if (cursor === 'text' || cursor === 'vertical-text') return 'text'
  if (cursor === 'wait' || cursor === 'progress') return 'busy'
  if (cursor === 'grab' || cursor === 'grabbing') return 'grab'
  if (cursor === 'not-allowed' || cursor === 'no-drop') return 'blocked'
  if (['e-resize', 'w-resize', 'ew-resize', 'col-resize'].includes(cursor)) return 'resize-x'
  if (['n-resize', 's-resize', 'ns-resize', 'row-resize'].includes(cursor)) return 'resize-y'
  if (cursor.endsWith('-resize') || cursor === 'move' || cursor === 'all-scroll') return 'move'
  if (cursor === 'crosshair') return 'crosshair'
  return 'default'
}

const onPointerMove = event => {
  if (event.pointerType === 'touch') return

  position.value = { x: event.clientX, y: event.clientY }
  if (event.target !== previousTarget) {
    const root = document.documentElement
    root.classList.remove('has-custom-cursor')
    const nativeCursor = window.getComputedStyle(event.target).cursor
    root.classList.add('has-custom-cursor')
    cursorState.value = stateForCursor(nativeCursor)
    previousTarget = event.target
  }
  visible.value = true
}

const onPointerLeave = event => {
  if (event.relatedTarget) return
  visible.value = false
  previousTarget = null
}

onMounted(() => {
  supportsCustomCursor.value = window.matchMedia('(hover: hover) and (pointer: fine)').matches
  if (!supportsCustomCursor.value) return

  document.documentElement.classList.add('has-custom-cursor')
  window.addEventListener('pointermove', onPointerMove, { passive: true })
  window.addEventListener('pointerout', onPointerLeave)
})

onBeforeUnmount(() => {
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerout', onPointerLeave)
  document.documentElement.classList.remove('has-custom-cursor')
})
</script>

<template>
  <div
    v-if="supportsCustomCursor"
    v-show="visible"
    class="custom-cursor"
    :class="`is-${cursorState}`"
    :style="{ left: `${position.x}px`, top: `${position.y}px` }"
    aria-hidden="true"
  >
    <svg v-if="cursorState === 'default'" viewBox="0 0 28 28">
      <path d="M3 2 23 16 14 16.5 9.5 25 6.5 23.5 11 15Z" />
    </svg>
    <svg v-else-if="cursorState === 'pointer'" viewBox="0 0 28 28">
      <circle cx="14" cy="14" r="8" />
      <circle class="cursor-center" cx="14" cy="14" r="2" />
    </svg>
    <svg v-else-if="cursorState === 'text'" viewBox="0 0 28 28">
      <path d="M14 4v20M8 4h12M8 24h12" />
    </svg>
    <svg v-else-if="cursorState === 'busy'" viewBox="0 0 28 28">
      <circle class="cursor-spinner" cx="14" cy="14" r="9" />
    </svg>
    <svg v-else-if="cursorState === 'grab'" viewBox="0 0 28 28">
      <path d="M14 4v20M4 14h20M7 7l14 14M21 7 7 21" />
    </svg>
    <svg v-else-if="cursorState === 'blocked'" viewBox="0 0 28 28">
      <circle cx="14" cy="14" r="9" />
      <path d="m8 8 12 12" />
    </svg>
    <svg v-else-if="cursorState === 'resize-x'" viewBox="0 0 28 28">
      <path d="M4 14h20M4 14l5-5M4 14l5 5M24 14l-5-5M24 14l-5 5" />
    </svg>
    <svg v-else-if="cursorState === 'resize-y'" viewBox="0 0 28 28">
      <path d="M14 4v20M14 4l-5 5M14 4l5 5M14 24l-5-5M14 24l5-5" />
    </svg>
    <svg v-else-if="cursorState === 'crosshair' || cursorState === 'move'" viewBox="0 0 28 28">
      <circle cx="14" cy="14" r="3" />
      <path d="M14 2v7M14 19v7M2 14h7M19 14h7" />
      <path v-if="cursorState === 'move'" d="m14 2-2 3m2-3 2 3M14 26l-2-3m2 3 2-3M2 14l3-2m-3 2 3 2m23-2-3-2m3 2-3 2" />
    </svg>
    <svg v-else viewBox="0 0 28 28">
      <path d="M3 2 23 16 14 16.5 9.5 25 6.5 23.5 11 15Z" />
    </svg>
  </div>
</template>

<style scoped>
.custom-cursor {
  position: fixed;
  z-index: 2147483647;
  width: 28px;
  height: 28px;
  pointer-events: none;
  transform: translate(-2px, -2px);
  transition: width 120ms ease, height 120ms ease, opacity 80ms ease;
  will-change: left, top;
}

.custom-cursor svg {
  display: block;
  width: 100%;
  height: 100%;
  overflow: visible;
  fill: var(--jet, #38e1ff);
  stroke: var(--ink, #070b14);
  stroke-width: 1.6;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.custom-cursor.is-pointer {
  width: 32px;
  height: 32px;
}

.custom-cursor.is-pointer svg,
.custom-cursor.is-busy svg {
  fill: none;
  stroke: var(--ui-focus-color, var(--signal, #1f5cff));
  stroke-width: 2;
}

.custom-cursor .cursor-center {
  fill: var(--ui-focus-color, var(--signal, #1f5cff));
}

.custom-cursor.is-text svg,
.custom-cursor.is-resize-x svg,
.custom-cursor.is-resize-y svg,
.custom-cursor.is-crosshair svg,
.custom-cursor.is-move svg,
.custom-cursor.is-grab svg {
  fill: none;
  stroke: var(--ui-focus-color, var(--signal, #1f5cff));
  stroke-width: 2.2;
}

.custom-cursor.is-blocked svg {
  fill: none;
  stroke: var(--danger, #ff4d4d);
  stroke-width: 2.2;
}

.cursor-spinner {
  stroke-dasharray: 42 16;
  transform-origin: 14px 14px;
  animation: cursor-spin 900ms linear infinite;
}

@keyframes cursor-spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .custom-cursor,
  .cursor-spinner {
    animation: none;
    transition: none;
  }
}
</style>
