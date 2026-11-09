<script setup>
import { ref } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  defaultValue: {
    type: String,
    default: '',
  },
})

const openItem = ref(props.defaultValue)

function toggleItem(id) {
  openItem.value = openItem.value === id ? '' : id
}
</script>

<template>
  <div class="numbered-accordion">
    <section
      v-for="(item, index) in items"
      :key="item.id"
      class="numbered-accordion__item"
    >
      <h3 class="numbered-accordion__heading">
        <button
          :id="`accordion-trigger-${item.id}`"
          class="numbered-accordion__trigger"
          type="button"
          :aria-expanded="openItem === item.id"
          :aria-controls="`accordion-panel-${item.id}`"
          @click="toggleItem(item.id)"
        >
          <span class="numbered-accordion__title">
            <span class="numbered-accordion__number">{{ String(index + 1).padStart(2, '0') }}</span>
            <span>{{ item.title }}</span>
          </span>
          <span class="numbered-accordion__mark" aria-hidden="true">+</span>
        </button>
      </h3>
      <div
        :id="`accordion-panel-${item.id}`"
        class="numbered-accordion__panel"
        :data-open="openItem === item.id"
        :aria-labelledby="`accordion-trigger-${item.id}`"
        :aria-hidden="openItem !== item.id"
        :inert="openItem !== item.id"
      >
        <div class="numbered-accordion__panel-inner">
          <p>{{ item.content }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.numbered-accordion__item {
  border-top: 1px solid var(--sand);
}

.numbered-accordion__item:last-child {
  border-bottom: 1px solid var(--sand);
}

.numbered-accordion__heading {
  margin: 0;
}

.numbered-accordion__trigger {
  display: flex;
  width: 100%;
  min-height: 64px;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 0;
  border: 0;
  background: transparent;
  color: color-mix(in srgb, var(--ink) 35%, transparent);
  cursor: pointer;
  text-align: left;
  transition: color 180ms ease;
}

.numbered-accordion__trigger:hover,
.numbered-accordion__trigger[aria-expanded='true'] {
  color: var(--ink);
}

.numbered-accordion__trigger:focus-visible {
  outline: 2px solid var(--signal);
  outline-offset: 4px;
}

.numbered-accordion__title {
  display: flex;
  min-width: 0;
  align-items: flex-start;
  gap: 16px;
  font-size: clamp(22px, 3vw, 34px);
  font-weight: 750;
  line-height: 1.12;
  text-transform: uppercase;
  letter-spacing: -0.045em;
}

.numbered-accordion__number {
  flex: 0 0 auto;
  padding-top: 5px;
  color: var(--signal-deep);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0;
}

.numbered-accordion__mark {
  flex: 0 0 auto;
  color: var(--signal-deep);
  font-size: 24px;
  font-weight: 400;
  line-height: 1;
  transition: transform 180ms ease;
}

.numbered-accordion__trigger[aria-expanded='true'] .numbered-accordion__mark {
  transform: rotate(45deg);
}

.numbered-accordion__panel p {
  max-width: 600px;
  margin: 0;
  padding: 0 38px 22px 28px;
  color: var(--slate);
  font-size: 14px;
  line-height: 1.7;
}

.numbered-accordion__panel {
  display: grid;
  grid-template-rows: 0fr;
  opacity: 0;
  transition: grid-template-rows 240ms ease, opacity 180ms ease;
}

.numbered-accordion__panel[data-open='true'] {
  grid-template-rows: 1fr;
  opacity: 1;
}

.numbered-accordion__panel-inner {
  min-height: 0;
  overflow: hidden;
}

@media (max-width: 575.98px) {
  .numbered-accordion__trigger {
    gap: 10px;
  }

  .numbered-accordion__title {
    gap: 10px;
    font-size: clamp(19px, 5.4vw, 25px);
  }

  .numbered-accordion__number {
    padding-top: 4px;
    font-size: 10px;
  }

  .numbered-accordion__panel p {
    padding: 0 8px 20px 22px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .numbered-accordion__trigger,
  .numbered-accordion__mark,
  .numbered-accordion__panel {
    transition: none;
  }
}
</style>
