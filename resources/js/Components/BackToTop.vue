<template>
  <button
    v-show="visible"
    class="back-to-top"
    type="button"
    :aria-label="$t('t-back-to-top')"
    @click="backToTop"
  >
    <svg viewBox="0 0 24 34" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
      <path d="M12 33V2M2 12 12 2l10 10" />
    </svg>
  </button>
</template>
<script>
export default {
  data() {
    return { visible: false };
  },
  mounted() {
    this.onScroll = () => {
      this.visible = window.scrollY > 100;
    };
    window.addEventListener('scroll', this.onScroll, { passive: true });
    this.onScroll();
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this.onScroll);
  },
  methods: {
    backToTop() {
      window.scrollTo({
        top: 0,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
      });
    },
  },
};
</script>
<style scoped>
.back-to-top {
  position: fixed;
  z-index: 1000;
  right: max(20px, env(safe-area-inset-right));
  bottom: max(20px, env(safe-area-inset-bottom));
  display: grid;
  width: 48px;
  height: 48px;
  place-items: center;
  border: 1px solid rgb(56 225 255 / 36%);
  border-radius: 50%;
  color: var(--jet, #38e1ff);
  background: var(--night, #0e1626);
  box-shadow: 0 3px 12px rgb(7 11 20 / 22%);
  cursor: pointer;
  transition: color 160ms ease, background-color 160ms ease, border-color 160ms ease;
}

.back-to-top svg {
  width: 16px;
  height: 22px;
}
.back-to-top:hover {
  border-color: var(--jet, #38e1ff);
  color: var(--ink, #070b14);
  background: var(--jet, #38e1ff);
}
.back-to-top:focus-visible {
  outline: 2px solid var(--ink, #070b14);
  outline-offset: 3px;
  box-shadow: 0 0 0 5px var(--jet, #38e1ff);
}
@media (prefers-reduced-motion: reduce) {
  .back-to-top {
    transition: none;
  }
}
</style>