<template>
  <div @dragenter.prevent="toggleActive" @dragleave.prevent="toggleActive" @dragover.prevent
    @drop.prevent="toggleActive" :class="{ 'active-dropzone': active }" class="dropzone position-relative">
    <label for="dropzoneFile" class="stretched-link">{{ $t('ui-drag-and-drop-your-files-or-browse') }}</label>
    <input type="file" id="dropzoneFile" class="dropzoneFile btn btn-primary" />
  </div>
</template>


<script>
  import {
    ref
  } from "vue";
  export default {
    name: "dropzone",
    setup() {
      const active = ref(false);
      const toggleActive = () => {
        active.value = !active.value;
      };
      return {
        active,
        toggleActive
      };
    },
  };
</script>