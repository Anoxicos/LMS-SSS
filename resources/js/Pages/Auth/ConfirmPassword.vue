<script setup>
import { ref } from 'vue';
import { Link, Head, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();

            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head :title="$t('t-secure-area')" />

    <div class="auth-page-wrapper pt-5">
        <div class="auth-page-content">
            <BContainer>
                <BRow>
                    <BCol lg="12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <Link href="/" class="d-inline-block auth-logo">
                                <img src="@assets/images/logo_safesky blue.webp" alt="" height="20">
                                </Link>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">{{ $t('t-safesky-learning-management-system') }}</p>
                        </div>
                    </BCol>
                </BRow>

                <BRow class="justify-content-center">
                    <BCol md="8" lg="6" xl="5">
                        <BCard no-body class="mt-4 card-bg-fill">

                            <BCardBody class="p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">{{ $t('t-confirm-password') }}</h5>
                                    <p class="text-muted">{{ $t('t-confirm-password-description') }}</p>
                                </div>
                                <div class="user-thumb text-center">
                                    <img src="@assets/images/users/avatar-1.jpg" class="rounded-circle img-thumbnail avatar-lg" alt="thumbnail">
                                </div>
                                <div class="p-2 mt-4">
                                    <form @submit.prevent="submit">
                                        <!-- this is only for ignore console warning -->
                                        <TextInput id="username" type="text" class="" style="display: none;" autocomplete="username"  />
                                        <!-- end -->
                                        <div class="mb-3">
                                            <InputLabel for="password" :value="$t('t-password')" />
                                            <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" required :placeholder="$t('t-enter-your-password')" autocomplete="current-password" autofocus />
                                        </div>
                                        <div class="mb-2 mt-4">
                                            <BButton variant="success" class="w-100" type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">{{ $t('t-unlock') }}</BButton>
                                        </div>
                                    </form>

                                </div>
                            </BCardBody>
                        </BCard>

                        <div class="mt-4 text-center">
                            <p class="mb-0">{{ $t('t-not-you-return') }}
                                <Link :href="route('login')" class="fw-semibold text-primary text-decoration-underline">{{ $t('t-sign-in') }}</Link>
                            </p>
                        </div>

                    </BCol>
                </BRow>
            </BContainer>
        </div>

        <footer class="footer">
            <BContainer>
                <BRow>
                    <BCol lg="12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy; {{ new Date().getFullYear() }} SafeSky Solution. {{ $t('t-all-rights-reserved') }}</p>
                        </div>
                    </BCol>
                </BRow>
            </BContainer>
        </footer>
    </div>
</template> 
