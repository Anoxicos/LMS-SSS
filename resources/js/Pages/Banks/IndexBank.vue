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
    <Head title="Question Banks" />
    <PageHeader title="Question Banks" pageTitle="LMS" />

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