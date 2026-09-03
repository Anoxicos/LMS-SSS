<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

import Lottie from '@/Components/widgets/lottie.vue'
import ListeRoles from '@/Pages/roles/LIsteRoles.vue'

defineProps({
    roles: {
        type: Array,
        required: true
    }
})
</script>

<template>
  <Layout>
    <Head title="Roles" />
    <PageHeader title="Roles" pageTitle="Admin" />
    <BRow>
      <BCol xxl="12">
        <BCard no-body id="companyList">
          <ListeRoles :roles="roles" />
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
