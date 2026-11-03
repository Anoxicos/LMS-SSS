<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import ListeBanks from './ListeBanks.vue'

defineProps({
  banks: {
    type: Array,
    default: () => [],
  },
  courses: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-question-banks')" />
    <PageHeader :title="$t('ui-question-banks')" :page-title="$t('ui-lms')" />

    <BRow>
      <BCol xxl="12">
        <ListeBanks
          :banks="banks"
          :courses="courses"
        />
      </BCol>
    </BRow>
  </Layout>
</template>