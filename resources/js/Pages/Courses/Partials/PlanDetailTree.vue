<script setup>
import { ref } from 'vue'

const props = defineProps({
  details: {
    type: Array,
    required: true,
  },
  level: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits([
  'add-child',
  'edit-detail',
  'delete-detail',
])

const openedNodes = ref({})

const toggleNode = (detail) => {
  openedNodes.value[detail.id] = !openedNodes.value[detail.id]
}

const isOpen = (detail) => {
  return openedNodes.value[detail.id] ?? true
}

const hasChildren = (detail) => {
  return detail.children && detail.children.length
}
</script>

<template>
  <div class="plan-tree">
    <div
      v-for="detail in details"
      :key="detail.id"
      class="plan-tree-node"
      :style="{ marginLeft: `${level * 20}px` }"
    >
      <div class="plan-tree-card">
        <div class="plan-tree-icon">
          <i :class="hasChildren(detail) ? 'ri-folder-3-line' : 'ri-file-list-3-line'"></i>
        </div>

        <div class="min-width-0 flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h6 class="mb-0 text-truncate">
              {{ detail.name }}
            </h6>

            <span class="mini-badge file">
              {{ $t('ui-own') }} {{ detail.own_questions || 0 }}
            </span>

            <span class="mini-badge success">
              {{ $t('ui-total') }} {{ detail.total_questions || 0 }}
            </span>
          </div>

          <p class="text-muted mb-0">
            {{ hasChildren(detail) ? `${detail.children.length} child items` : 'No child items' }}
          </p>
        </div>

        <div class="d-flex gap-2">
          <BButton
            v-if="hasChildren(detail)"
            type="button"
            size="sm"
            variant="soft-secondary"
            class="action-btn"
            :title="isOpen(detail) ? 'Hide children' : 'Show children'"
            @click="toggleNode(detail)"
          >
            <i :class="isOpen(detail) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
          </BButton>

          <BButton
            type="button"
            size="sm"
            variant="soft-success"
            class="action-btn"
            :title="$t('ui-add-child')"
            @click="emit('add-child', detail)"
          >
            <i class="ri-add-line"></i>
          </BButton>

          <BButton
            type="button"
            size="sm"
            variant="soft-primary"
            class="action-btn"
            :title="$t('ui-edit')"
            @click="emit('edit-detail', detail)"
          >
            <i class="ri-pencil-line"></i>
          </BButton>

          <BButton
            type="button"
            size="sm"
            variant="soft-danger"
            class="action-btn"
            :title="$t('ui-delete')"
            @click="emit('delete-detail', detail)"
          >
            <i class="ri-delete-bin-6-line"></i>
          </BButton>
        </div>
      </div>

      <PlanDetailTree
        v-if="hasChildren(detail) && isOpen(detail)"
        :details="detail.children"
        :level="level + 1"
        @add-child="emit('add-child', $event)"
        @edit-detail="emit('edit-detail', $event)"
        @delete-detail="emit('delete-detail', $event)"
      />
    </div>
  </div>
</template>