<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

import Lottie from '@/Components/widgets/lottie.vue'
import ListeCompanies from './ListeCompanies.vue'

defineProps({
    companies: { type: Array, required: false },
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-companies')" />
    <PageHeader :title="$t('ui-companies')" :page-title="$t('ui-admin')" />
    <BRow>
      <BCol xxl="12">
        
          <ListeCompanies 
            :companies="companies" 
          />
        
      </BCol>
    </BRow>
  </Layout>
</template>
