<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import ListesCompanyRequests from './ListesCompanyRequests.vue'

defineProps({
  companyRequests: {
    type: Array,
    default: () => [],
  },
  companies: {
    type: Array,
    default: () => [],
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <Layout>
    <Head title="Company Requests" />
    <PageHeader title="Company Requests" pageTitle="LMS" />

    <ListesCompanyRequests
      :company-requests="companyRequests"
      :companies="companies"
      :can-approve="canApprove"
    />
  </Layout>
</template>
