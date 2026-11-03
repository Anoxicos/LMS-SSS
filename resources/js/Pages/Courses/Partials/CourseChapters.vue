<script setup>
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  courseId: {
    type: [Number, String],
    required: true,
  },
})

const editingChapter = ref(null)

const chapterForm = useForm({
  title: '',
  description: '',
})

const chapters = computed(() => {
  return [...(props.form.chapters || [])].sort((a, b) => {
    return Number(a.sort_order || 0) - Number(b.sort_order || 0)
  })
})

const isEditing = computed(() => {
  return Boolean(editingChapter.value)
})

const resetChapterForm = () => {
  editingChapter.value = null
  chapterForm.reset()
  chapterForm.clearErrors()
}

const showSuccess = (message) => {
  Swal.fire({
    icon: 'success',
    title: t('ui-success'),
    text: message,
    timer: 1600,
    showConfirmButton: false,
  })
}

const showError = (message = 'Please check the form and try again.') => {
  Swal.fire({
    icon: 'error',
    title: t('t-error'),
    text: message,
    timer: 2200,
    showConfirmButton: false,
  })
}

const editChapter = (chapter) => {
  editingChapter.value = chapter
  chapterForm.title = chapter.title || ''
  chapterForm.description = chapter.description || ''
  chapterForm.clearErrors()
}

const submitChapter = () => {
  if (isEditing.value) {
    chapterForm.post(
      route('courses.chapters.update', [props.courseId, editingChapter.value.id]),
      {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
          showSuccess('Chapter has been updated successfully.')
          resetChapterForm()
        },
        onError: () => {
          showError('Please check chapter information.')
        },
      }
    )

    return
  }

  chapterForm.post(route('courses.chapters.store', props.courseId), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      showSuccess('Chapter has been created successfully.')
      resetChapterForm()
    },
    onError: () => {
      showError('Please check chapter information.')
    },
  })
}

const deleteChapter = (chapter) => {
  Swal.fire({
    title: t('ui-delete-chapter'),
    text: `Are you sure you want to delete "${chapter.title}"? All slides inside this chapter may also be removed.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('ui-yes-delete'),
    cancelButtonText: t('ui-cancel'),
    confirmButtonColor: '#f06548',
  }).then((result) => {
    if (!result.isConfirmed) return

    router.delete(route('courses.chapters.delete', [props.courseId, chapter.id]), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showSuccess('Chapter has been deleted successfully.')
      },
      onError: () => {
        showError('Unable to delete chapter.')
      },
    })
  })
}

const reorderChapters = (orderedChapters) => {
  const payload = orderedChapters.map((chapter, index) => {
    return {
      id: chapter.id,
      sort_order: index + 1,
    }
  })

  router.post(
    route('courses.chapters.reorder', props.courseId),
    {
      chapters: payload,
    },
    {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showSuccess('Chapters order has been updated successfully.')
      },
      onError: () => {
        showError('Unable to reorder chapters.')
      },
    }
  )
}

const moveChapter = (index, direction) => {
  const ordered = [...chapters.value]
  const targetIndex = index + direction

  if (targetIndex < 0 || targetIndex >= ordered.length) return

  const current = ordered[index]
  ordered[index] = ordered[targetIndex]
  ordered[targetIndex] = current

  reorderChapters(ordered)
}
</script>

<template>
  <div class="builder-section">
    <div class="section-heading">
      <h5>{{ $t('ui-course-chapters') }}</h5>
      <p>{{ $t('ui-create-and-organize-the-chapters-of-this-course') }}</p>
    </div>

    <BRow class="g-4">
      <BCol xl="8">
        <div v-if="chapters.length" class="items-list">
          <div
            v-for="(chapter, index) in chapters"
            :key="chapter.id"
            class="builder-item-card"
          >
            <div class="builder-item-icon">
              {{ index + 1 }}
            </div>

            <div class="flex-grow-1 min-width-0">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h6 class="mb-0 text-truncate">
                  {{ chapter.title }}
                </h6>

                <span class="mini-badge">
                  {{ chapter.slides?.length || 0 }} {{ $t('ui-slides-label') }}
                </span>
              </div>

              <p class="text-muted mb-0">
                {{ chapter.description || 'No description provided.' }}
              </p>
            </div>

            <div class="d-flex gap-2">
              <BButton
                type="button"
                variant="soft-secondary"
                size="sm"
                class="action-btn"
                :disabled="index === 0"
                :title="$t('ui-move-up')"
                @click="moveChapter(index, -1)"
              >
                <i class="ri-arrow-up-line"></i>
              </BButton>

              <BButton
                type="button"
                variant="soft-secondary"
                size="sm"
                class="action-btn"
                :disabled="index === chapters.length - 1"
                :title="$t('ui-move-down')"
                @click="moveChapter(index, 1)"
              >
                <i class="ri-arrow-down-line"></i>
              </BButton>

              <BButton
                type="button"
                variant="soft-primary"
                size="sm"
                class="action-btn"
                :title="$t('ui-edit')"
                @click="editChapter(chapter)"
              >
                <i class="ri-edit-2-line"></i>
              </BButton>

              <BButton
                type="button"
                variant="soft-danger"
                size="sm"
                class="action-btn"
                :title="$t('ui-delete')"
                @click="deleteChapter(chapter)"
              >
                <i class="ri-delete-bin-6-line"></i>
              </BButton>
            </div>
          </div>
        </div>

        <div v-else class="empty-box">
          <i class="ri-list-check-2"></i>
          <h6>{{ $t('ui-no-chapters-yet') }}</h6>
          <p>{{ $t('ui-add-the-first-chapter-using-the-form-on-the-right') }}</p>
        </div>
      </BCol>

      <BCol xl="4">
        <div class="builder-side-form">
          <h6 class="mb-3">
            <i :class="isEditing ? 'ri-edit-2-line' : 'ri-add-line'" class="me-1"></i>
            {{ isEditing ? 'Edit Chapter' : 'Add Chapter' }}
          </h6>

          <form @submit.prevent="submitChapter">
            <div class="mb-3">
              <label class="form-label">
                {{ $t('ui-title') }} <span class="text-danger">*</span>
              </label>

              <div class="input-icon-box">
                <i class="ri-bookmark-line input-icon"></i>

                <input
                  v-model="chapterForm.title"
                  type="text"
                  class="form-control modern-input"
                  :class="{ 'is-invalid': chapterForm.errors.title }"
                  :placeholder="$t('ui-chapter-title')"
                />
              </div>

              <div v-if="chapterForm.errors.title" class="invalid-feedback d-block">
                {{ chapterForm.errors.title }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $t('ui-description') }}</label>

              <textarea
                v-model="chapterForm.description"
                rows="4"
                class="form-control modern-textarea"
                :class="{ 'is-invalid': chapterForm.errors.description }"
                :placeholder="$t('ui-short-chapter-description')"
              ></textarea>

              <div
                v-if="chapterForm.errors.description"
                class="invalid-feedback d-block"
              >
                {{ chapterForm.errors.description }}
              </div>
            </div>

            <div class="d-flex gap-2">
              <BButton
                type="submit"
                variant="primary"
                class="builder-action-btn flex-grow-1"
                :disabled="chapterForm.processing"
              >
                <span
                  v-if="chapterForm.processing"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                {{ isEditing ? 'Update Chapter' : 'Add Chapter' }}
              </BButton>

              <BButton
                v-if="isEditing"
                type="button"
                variant="light"
                class="builder-action-btn"
                @click="resetChapterForm"
              >
                {{ $t('ui-cancel') }}
              </BButton>
            </div>
          </form>
        </div>
      </BCol>
    </BRow>
  </div>
</template>