<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import ListesCourses from './ListesCourses.vue'

defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-courses')" />
    <PageHeader :title="$t('ui-courses')" :page-title="$t('ui-lms')" />

    <ListesCourses :courses="courses" />
  </Layout>
</template>