<script setup>
import {
    Link,
    Head,
    useForm
} from '@inertiajs/vue3';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(
        route('password.email'),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>

    <Head :title="$t('t-forgot-password')" />

    <div class="auth-page-wrapper pt-5">

        <div class="auth-page-content">

            <BContainer>

                <!-- Logo -->
                <BRow>
                    <BCol lg="12">

                        <div
                            class="
                                text-center
                                mt-sm-5
                                mb-4
                            "
                        >

                            <Link
                                href="/"
                                class="
                                    d-inline-block
                                    auth-logo
                                "
                            >
                                <img
                                    src="@assets/images/logo_safesky blue.webp"
                                    :alt="$t('t-safesky-lms')"
                                    height="45"
                                >
                            </Link>

                            <p
                                class="
                                    mt-3
                                    fs-15
                                    text-muted
                                "
                            >
                                {{ $t('t-safesky-learning-management-system') }}
                            </p>

                        </div>

                    </BCol>
                </BRow>

                <BRow
                    class="justify-content-center"
                >

                    <BCol
                        md="8"
                        lg="6"
                        xl="5"
                    >

                        <BCard
                            no-body
                            class="
                                mt-4
                                card-bg-fill
                                forgot-card
                            "
                        >

                            <BCardBody
                                class="p-4 p-md-5"
                            >

                                <!-- Icon -->

                                <div
                                    class="
                                        text-center
                                        mb-4
                                    "
                                >

                                    <div
                                        class="
                                            avatar-lg
                                            mx-auto
                                        "
                                    >

                                        <div
                                            class="
                                                avatar-title
                                                bg-light
                                                text-primary
                                                display-5
                                                rounded-circle
                                            "
                                        >

                                            <i
                                                class="
                                                    ri-lock-password-line
                                                "
                                            ></i>

                                        </div>

                                    </div>

                                </div>

                                <!-- Title -->

                                <div
                                    class="
                                        text-center
                                        mb-4
                                    "
                                >

                                    <h4
                                        class="
                                            fw-semibold
                                            mb-2
                                        "
                                    >
                                        {{ $t('t-forgot-password-question') }}
                                    </h4>

                                    <p
                                        class="
                                            text-muted
                                            mb-0
                                        "
                                    >
                                        {{ $t('t-forgot-password-description') }}
                                    </p>

                                </div>

                                <!-- Success -->

                                <BAlert
                                    v-if="status"
                                    :model-value="true"
                                    variant="success"
                                    class="
                                        border-0
                                        text-center
                                        mb-4
                                    "
                                >

                                    <i
                                        class="
                                            ri-checkbox-circle-line
                                            me-1
                                        "
                                    ></i>

                                    {{ status }}

                                </BAlert>

                                <!-- Form -->

                                <form
                                    @submit.prevent="submit"
                                >

                                    <div class="mb-4">

                                        <InputLabel
                                            for="email"
                                            :value="$t('t-email-address')"
                                        />

                                        <TextInput
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            class="
                                                mt-1
                                                form-control
                                            "
                                            required
                                            autofocus
                                            autocomplete="email"
                                            placeholder="name@example.com"
                                            :class="{
                                                'is-invalid':
                                                    form.errors.email
                                            }"
                                        />

                                        <InputError
                                            :message="
                                                form.errors.email
                                            "
                                        />

                                    </div>

                                    <div class="d-grid">

                                        <BButton
                                            variant="primary"
                                            size="lg"
                                            type="submit"
                                            :disabled="
                                                form.processing
                                            "
                                        >

                                            <BSpinner
                                                v-if="
                                                    form.processing
                                                "
                                                small
                                                class="me-1"
                                            />

                                            <i
                                                v-else
                                                class="
                                                    ri-mail-send-line
                                                    me-1
                                                "
                                            ></i>

                                            {{
                                                form.processing
                                                    ? $t('t-sending')
                                                    : $t('t-send-reset-link')
                                            }}

                                        </BButton>

                                    </div>

                                </form>

                            </BCardBody>

                        </BCard>

                        <!-- Login -->

                        <div
                            class="
                                mt-4
                                text-center
                            "
                        >

                            <p class="mb-0">

                                {{ $t('t-remember-password') }}

                                <Link
                                    :href="
                                        route('login')
                                    "
                                    class="
                                        fw-semibold
                                        text-primary
                                        text-decoration-none
                                    "
                                >
                                    {{ $t('t-sign-in') }}
                                </Link>

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

                            <p
                                class="
                                    mb-0
                                    text-muted
                                "
                            >
                                &copy;
                                {{ new Date().getFullYear() }}

                                SafeSky Solution.
                                {{ $t('t-all-rights-reserved') }}
                            </p>

                        </div>

                    </BCol>

                </BRow>

            </BContainer>

        </footer>

    </div>

</template>

<style scoped>
.forgot-card {
    border: 1px solid #e4edf4;
    border-radius: 16px;
    box-shadow:
        0 10px 35px
        rgba(7, 17, 38, 0.08);
}
</style>
