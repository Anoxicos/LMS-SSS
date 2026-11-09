<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const visible = ref(false)
const storageKey = 'safesky-immersive-hint-seen'
let showTimer
let dismissTimer

const dismiss = () => {
  visible.value = false
  window.clearTimeout(dismissTimer)
}

onMounted(() => {
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return
  if (document.fullscreenElement || window.matchMedia('(display-mode: fullscreen)').matches) return

  try {
    if (window.sessionStorage.getItem(storageKey)) return
  } catch {
    // Keep the hint available when browser storage is disabled.
  }

  showTimer = window.setTimeout(() => {
    visible.value = true
    try {
      window.sessionStorage.setItem(storageKey, '1')
    } catch {
      // The hint still works when browser storage is disabled.
    }
    dismissTimer = window.setTimeout(dismiss, 8500)
  }, 4800)
})

onBeforeUnmount(() => {
  window.clearTimeout(showTimer)
  window.clearTimeout(dismissTimer)
})
</script>

<template>
  <Transition name="immersive-hint">
    <div v-if="visible" class="immersive-hint" role="status" aria-live="polite">
      <span class="immersive-hint-key" aria-hidden="true">F11</span>
      <span>{{ $t('t-immersive-hint') }}</span>
      <button type="button" :aria-label="$t('ui-close')" @click="dismiss">
        <i class="ri-close-line" aria-hidden="true"></i>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.immersive-hint {
  position: fixed;
  z-index: 2100;
  right: 20px;
  bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: min(440px, calc(100vw - 32px));
  padding: 9px 10px 9px 12px;
  border: 1px solid var(--sand, #e2ded3);
  border-radius: 6px;
  background: var(--chalk, #fff);
  color: var(--ink, #070b14);
  box-shadow: 0 8px 24px rgb(7 11 20 / 14%);
  font-size: 13px;
  line-height: 1.35;
}

.immersive-hint-key {
  flex: 0 0 auto;
  padding: 3px 6px;
  border: 1px solid var(--sand, #e2ded3);
  border-bottom-width: 2px;
  border-radius: 4px;
  color: var(--signal, #1f5cff);
  font-size: 11px;
  font-weight: 750;
}

.immersive-hint button {
  display: grid;
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  place-items: center;
  margin-left: auto;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: var(--slate, #3a4256);
  font-size: 17px;
}

.immersive-hint button:hover {
  background: var(--paper, #f2efe8);
}

:global(html[data-bs-theme="dark"]) .immersive-hint {
  border-color: var(--midnight, #16233d);
  background: var(--night, #0e1626);
  color: var(--chalk, #fff);
}

:global(html[data-bs-theme="dark"]) .immersive-hint-key {
  border-color: var(--midnight, #16233d);
  color: var(--jet, #38e1ff);
}

:global(html[data-bs-theme="dark"]) .immersive-hint button {
  color: var(--mist, #b7bdcb);
}

:global(html[data-bs-theme="dark"]) .immersive-hint button:hover {
  background: var(--midnight, #16233d);
}

.immersive-hint button:focus-visible {
  outline: 2px solid var(--signal, #1f5cff);
  outline-offset: 2px;
}

.immersive-hint-enter-active,
.immersive-hint-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.immersive-hint-enter-from,
.immersive-hint-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (max-width: 480px) {
  .immersive-hint {
    right: 12px;
    bottom: 12px;
    left: 12px;
    max-width: none;
    font-size: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .immersive-hint-enter-active,
  .immersive-hint-leave-active {
    transition: none;
  }
}
</style>
