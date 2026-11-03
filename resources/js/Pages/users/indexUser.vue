<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import ListeUsers from './ListeUsers.vue'

defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  roles: {
    type: Array,
    default: () => [],
  },
  companies: {
    type: Array,
    default: () => [],
  },
  authUser: {
    type: Object,
    default: () => ({}),
  },
})
</script>

<template>
  <Layout>
    <Head :title="$t('ui-users')" />

    <PageHeader
      :title="$t('ui-users')"
      :page-title="$t('ui-admin')"
    />

    <BRow>
      <BCol xxl="12">
        <ListeUsers
          :users="users"
          :roles="roles"
          :companies="companies"
          :auth-user="authUser"
        />
      </BCol>
    </BRow>
  </Layout>
</template>
