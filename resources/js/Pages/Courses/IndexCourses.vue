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
    <Head title="Courses" />
    <PageHeader title="Courses" pageTitle="LMS" />

    <ListesCourses :courses="courses" />
  </Layout>
</template>