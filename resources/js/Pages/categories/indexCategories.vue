

<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'

import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

import Lottie from '@/Components/widgets/lottie.vue'
import FormulaireListeCategorie from './FormulaireListeCategorie.vue';
import ListeCategories from './ListeCategories.vue';

defineProps({
  categories: {
    type: Array,
    required: true
  },
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-categories')" />
    <PageHeader :title="$t('ui-categories')" :page-title="$t('ui-admin')" />
    <BRow>
      <BCol xxl="8">
          <ListeCategories :categories="categories" />  
      </BCol>
      <BCol xxl="4">
        <FormulaireListeCategorie />
      </BCol>
    </BRow>
  </Layout>
</template>
