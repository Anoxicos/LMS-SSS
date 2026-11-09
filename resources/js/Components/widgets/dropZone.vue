<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  id: { type: String, default: 'dropzone-file' },
  accept: { type: String, default: '' },
  multiple: { type: Boolean, default: false },
  maxSizeMb: { type: Number, default: 0 },
  progress: { type: Number, default: null },
})

const emit = defineEmits(['files-selected', 'file-selected'])
const input = ref(null)
const active = ref(false)
const files = ref([])
const error = ref('')

const acceptLabel = computed(() => props.accept || 'Any file type')

const selectFiles = fileList => {
  const picked = Array.from(fileList || [])
  if (!picked.length) return

  const acceptedTypes = props.accept.split(',').map(value => value.trim().toLowerCase()).filter(Boolean)
  const unsupported = acceptedTypes.length && picked.find(file => {
    const extension = `.${file.name.split('.').pop()?.toLowerCase()}`
    return !acceptedTypes.some(type => {
      if (type.startsWith('.')) return extension === type
      if (type.endsWith('/*')) return file.type.startsWith(type.slice(0, -1))
      return file.type === type
    })
  })
  if (unsupported) {
    error.value = 'One or more files do not match the accepted format.'
    return
  }

  const tooLarge = props.maxSizeMb > 0 && picked.find(file => file.size > props.maxSizeMb * 1024 * 1024)
  if (tooLarge) {
    error.value = `Each file must be ${props.maxSizeMb} MB or smaller.`
    return
  }

  error.value = ''
  files.value = props.multiple ? [...files.value, ...picked] : picked.slice(0, 1)
  emit('files-selected', files.value)
  if (!props.multiple) emit('file-selected', files.value[0])
}

const onChange = event => {
  selectFiles(event.target.files)
  event.target.value = ''
}

const onDrop = event => {
  active.value = false
  selectFiles(event.dataTransfer?.files)
}

const removeFile = index => {
  files.value.splice(index, 1)
  emit('files-selected', files.value)
  emit('file-selected', files.value[0] || null)
}
</script>

<template>
  <div
    class="dropzone position-relative"
    :class="{ 'active-dropzone': active }"
    @dragenter.prevent="active = true"
    @dragover.prevent="active = true"
    @dragleave.prevent="active = false"
    @drop.prevent="onDrop"
  >
    <div class="mb-1"><i class="display-4 text-muted ri-upload-cloud-2-fill" aria-hidden="true"></i></div>
    <h5>{{ $t('ui-drop-files-here-or-click-to-upload') }}</h5>
    <p class="small text-muted mb-2">{{ acceptLabel }}<span v-if="maxSizeMb"> · up to {{ maxSizeMb }} MB</span></p>
    <button type="button" class="btn btn-soft-primary btn-sm" @click="input?.click()">{{ $t('ui-upload') }}</button>
    <input ref="input" :id="id" type="file" class="visually-hidden" :accept="accept" :multiple="multiple" @change="onChange">

    <ul v-if="files.length" class="dropzone-file-list list-unstyled mt-3 mb-0" aria-live="polite">
      <li v-for="(file, index) in files" :key="`${file.name}-${file.lastModified}`" class="d-flex align-items-center gap-2">
        <i class="ri-check-line text-success" aria-hidden="true"></i>
        <span class="text-truncate">{{ file.name }}</span>
        <button type="button" class="btn btn-link btn-sm ms-auto" :aria-label="`Remove ${file.name}`" @click="removeFile(index)"><i class="ri-close-line" aria-hidden="true"></i></button>
      </li>
    </ul>
    <div v-if="error" class="text-danger small mt-2" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ error }}</div>
    <div v-if="progress !== null" class="progress mt-3" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" :style="{ width: `${progress}%` }"></div></div>
  </div>
</template>

<style scoped>
.dropzone { transition: border-color 140ms ease, background-color 140ms ease; }
.active-dropzone { border-color: var(--signal); background: rgba(31, 92, 255, 0.08); }
.dropzone-file-list li { min-width: 0; }
</style>
