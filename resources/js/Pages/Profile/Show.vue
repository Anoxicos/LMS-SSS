<script setup>
import { Head } from "@inertiajs/vue3"
import Layout from "@/Layouts/main.vue";
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <Layout>
        <Head :title="$t('t-profile')" />
        <template #header>
            <h2 class="fw-semibold">
                {{ $t('t-profile') }}
            </h2>
        </template>

        <div>
            <BRow class="justify-content-center">
                <BCol lg="6">
                    <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                        <UpdateProfileInformationForm :user="$page.props.auth.user" />
                    </div>

                    <div v-if="$page.props.jetstream.canUpdatePassword">
                        <UpdatePasswordForm />
                    </div>        
                </BCol>
            </BRow>
        </div>
    </Layout>
</template>
