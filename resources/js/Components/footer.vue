<template>
  <footer class="safesky-footer">
    <div class="safesky-footer__inner">
      <div class="safesky-footer__row">
        <div class="safesky-footer__brand">
          <h2>SafeSky Solution</h2>
          <p>{{ $t('t-footer-description') }}</p>
        </div>

        <nav class="safesky-footer__group" :aria-label="$t('t-footer-explore')">
          <h3>{{ $t('t-footer-explore') }}</h3>
          <Link :href="route('courses')">{{ $t('t-courses') }}</Link>
          <Link :href="route('login')">{{ $t('t-login') }}</Link>
        </nav>

        <nav class="safesky-footer__group" :aria-label="$t('t-footer-legal')">
          <h3>{{ $t('t-footer-legal') }}</h3>
          <Link :href="route('terms.show')">{{ $t('ui-terms-of-service') }}</Link>
          <Link :href="route('policy.show')">{{ $t('ui-privacy-policy') }}</Link>
        </nav>
      </div>

      <div class="safesky-footer__signature">
        <Link :href="route('dashboard')" aria-label="SafeSky Solution home">
          safesky<span>solution</span>
        </Link>
      </div>

      <div class="safesky-footer__bar">
        <span>© {{ year }} SafeSky Solution</span>
      </div>
    </div>
  </footer>
</template>

<script>
import { Link } from "@inertiajs/vue3";

export default {
  components: { Link },
  data() {
    return { year: new Date().getFullYear() };
  },
};
</script>

<style scoped>
.safesky-footer {
  color: var(--chalk, #fff);
  background: var(--ink, #070b14);
}

.safesky-footer__inner {
  width: min(1200px, calc(100% - 48px));
  margin-inline: auto;
  padding-block: 44px 16px;
}

.safesky-footer__row {
  display: grid;
  grid-template-columns: minmax(0, 2fr) repeat(2, minmax(140px, 1fr));
  gap: 40px;
  align-items: start;
}

.safesky-footer__brand h2 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.safesky-footer__brand p {
  max-width: 32ch;
  margin: 10px 0 0;
  color: var(--mist, #b7bdcb);
  font-size: 0.9rem;
  line-height: 1.6;
}

.safesky-footer__group {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 7px;
}

.safesky-footer__group h3 {
  margin: 0 0 8px;
  color: var(--mist, #b7bdcb);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}

.safesky-footer__group a {
  color: var(--chalk, #fff);
  font-size: 0.9rem;
  line-height: 1.6;
  text-decoration: none;
  transition: color 160ms ease;
}

.safesky-footer__group a:hover {
  color: var(--jet, #38e1ff);
}

.safesky-footer a:focus-visible {
  border-radius: 2px;
  outline: 2px solid var(--jet, #38e1ff);
  outline-offset: 3px;
}

.safesky-footer__signature {
  overflow: hidden;
  margin-top: 32px;
  border-bottom: 1px solid rgb(183 189 203 / 24%);
}

.safesky-footer__signature a {
  display: inline-flex;
  align-items: baseline;
  gap: 0.3em;
  color: var(--jet, #38e1ff);
  font-size: clamp(3.25rem, 12vw, 9.5rem);
  font-weight: 700;
  line-height: 0.85;
  letter-spacing: -0.085em;
  text-decoration: none;
  white-space: nowrap;
}

.safesky-footer__signature span {
  color: var(--mist, #b7bdcb);
  font-size: 0.16em;
  font-weight: 500;
  letter-spacing: 0.015em;
}

.safesky-footer__bar {
  display: flex;
  min-height: 64px;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 0;
  border-top: 1px solid rgb(183 189 203 / 24%);
  color: var(--mist, #b7bdcb);
  font-size: 0.8rem;
}

@media (max-width: 700px) {
  .safesky-footer__inner {
    width: calc(100% - 36px);
    padding-top: 32px;
  }

  .safesky-footer__row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px 20px;
  }

  .safesky-footer__brand {
    grid-column: 1 / -1;
  }
}

@media (max-width: 380px) {
  .safesky-footer__bar {
    align-items: flex-start;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    padding-block: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .safesky-footer__group a {
    transition: none;
  }
}
</style>
