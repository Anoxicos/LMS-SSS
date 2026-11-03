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
    <Head :title="$t('ui-company-requests')" />
    <PageHeader :title="$t('ui-company-requests')" :page-title="$t('ui-lms')" />

    <ListesCompanyRequests
      :company-requests="companyRequests"
      :companies="companies"
      :can-approve="canApprove"
    />
  </Layout>
</template>
