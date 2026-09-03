<script setup>
import { computed } from 'vue';
import {
    Head,
    Link,
    useForm,
    usePage,
} from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();

const email = computed(() => {
    return page.props.auth?.user?.email ?? '';
});

/*
|--------------------------------------------------------------------------
| Formulaire validation
|--------------------------------------------------------------------------
*/

const verifyForm = useForm({
    code: '',
});

const submitCode = () => {
    verifyForm.post(
        route('verification.code.verify'),
        {
            preserveScroll: true,
        }
    );
};

/*
|--------------------------------------------------------------------------
| Formulaire resend
|--------------------------------------------------------------------------
*/

const resendForm = useForm({});

const resendCode = () => {
    resendForm.post(
        route('verification.send'),
        {
            preserveScroll: true,
        }
    );
};

/*
|--------------------------------------------------------------------------
| Message après resend
|--------------------------------------------------------------------------
|
| Fortify utilise encore le status "verification-link-sent".
| Même si maintenant nous envoyons un code.
|
*/

const verificationCodeSent = computed(() => {
    return [
        'verification-link-sent',
        'verification-code-sent',
    ].includes(props.status);
});

/*
|--------------------------------------------------------------------------
| Seulement chiffres
|--------------------------------------------------------------------------
*/

const handleCodeInput = (event) => {
    verifyForm.code = event.target.value
        .replace(/\D/g, '')
        .slice(0, 6);
};
</script>

<template>
    <Head title="Email Verification" />

    <div class="auth-page-wrapper pt-5">

        <div class="auth-page-content">

            <BContainer>

                <!-- Logo -->
                <BRow>
                    <BCol lg="12">

                        <div
                            class="text-center mt-sm-5 mb-4"
                        >
                            <Link
                                href="/"
                                class="d-inline-block auth-logo"
                            >
                                <img
                                    src="@assets/images/logo_safesky blue.webp"
                                    alt="SafeSky LMS"
                                    height="45"
                                >
                            </Link>

                            <p
                                class="mt-3 fs-15 text-muted"
                            >
                                SafeSky Learning Management System
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
                            class="mt-4 card-bg-fill verification-card"
                        >

                            <BCardBody
                                class="p-4 p-md-5"
                            >

                                <!-- Icon -->
                                <div
                                    class="mb-4 text-center"
                                >
                                    <div
                                        class="avatar-lg mx-auto"
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
                                                class="ri-mail-check-line"
                                            ></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div
                                    class="text-center mb-4"
                                >
                                    <h4
                                        class="fw-semibold mb-2"
                                    >
                                        Verify Your Email
                                    </h4>

                                    <p
                                        class="text-muted mb-2"
                                    >
                                        We sent a
                                        <strong>
                                            6-digit verification code
                                        </strong>
                                        to
                                    </p>

                                    <p
                                        v-if="email"
                                        class="
                                            fw-semibold
                                            text-primary
                                            mb-0
                                        "
                                    >
                                        {{ email }}
                                    </p>
                                </div>

                                <!-- Resend success -->
                                <div
                                    v-if="verificationCodeSent"
                                    class="
                                        alert
                                        alert-success
                                        text-center
                                    "
                                >
                                    <i
                                        class="
                                            ri-checkbox-circle-line
                                            me-1
                                        "
                                    ></i>

                                    A new verification code
                                    has been sent to your email.
                                </div>

                                <!-- Verify form -->
                                <form
                                    @submit.prevent="submitCode"
                                >

                                    <div class="mb-3">

                                        <label
                                            class="
                                                form-label
                                                text-center
                                                d-block
                                            "
                                        >
                                            Verification Code
                                        </label>

                                        <input
                                            :value="verifyForm.code"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="one-time-code"
                                            maxlength="6"
                                            placeholder="000000"
                                            class="
                                                form-control
                                                verification-code-input
                                            "
                                            :class="{
                                                'is-invalid':
                                                    verifyForm.errors.code
                                            }"
                                            @input="handleCodeInput"
                                        >

                                        <div
                                            v-if="verifyForm.errors.code"
                                            class="
                                                invalid-feedback
                                                d-block
                                                text-center
                                            "
                                        >
                                            {{
                                                verifyForm.errors.code
                                            }}
                                        </div>

                                        <div
                                            class="
                                                form-text
                                                text-center
                                                mt-2
                                            "
                                        >
                                            The code expires
                                            after 10 minutes.
                                        </div>

                                    </div>

                                    <div class="d-grid mb-3">

                                        <BButton
                                            type="submit"
                                            variant="primary"
                                            size="lg"
                                            :disabled="
                                                verifyForm.processing ||
                                                verifyForm.code.length !== 6
                                            "
                                        >
                                            <span
                                                v-if="
                                                    verifyForm.processing
                                                "
                                            >
                                                <BSpinner
                                                    small
                                                    class="me-1"
                                                />

                                                Verifying...
                                            </span>

                                            <span v-else>
                                                <i
                                                    class="
                                                        ri-shield-check-line
                                                        me-1
                                                    "
                                                ></i>

                                                Verify Email
                                            </span>
                                        </BButton>

                                    </div>

                                </form>

                                <!-- Resend -->
                                <div
                                    class="text-center mt-4"
                                >
                                    <p
                                        class="
                                            text-muted
                                            mb-2
                                        "
                                    >
                                        Didn't receive the code?
                                    </p>

                                    <BButton
                                        type="button"
                                        variant="link"
                                        class="
                                            p-0
                                            fw-semibold
                                            text-decoration-none
                                        "
                                        :disabled="
                                            resendForm.processing
                                        "
                                        @click="resendCode"
                                    >
                                        <BSpinner
                                            v-if="
                                                resendForm.processing
                                            "
                                            small
                                            class="me-1"
                                        />

                                        <i
                                            v-else
                                            class="
                                                ri-refresh-line
                                                me-1
                                            "
                                        ></i>

                                        Resend Verification Code
                                    </BButton>
                                </div>

                                <hr class="my-4">

                                <!-- Actions -->
                                <div
                                    class="
                                        d-flex
                                        justify-content-center
                                        gap-3
                                        flex-wrap
                                    "
                                >

                                    <Link
                                        :href="
                                            route('profile.show')
                                        "
                                        class="
                                            text-muted
                                            text-decoration-none
                                        "
                                    >
                                        <i
                                            class="
                                                ri-user-settings-line
                                                me-1
                                            "
                                        ></i>

                                        Edit Profile
                                    </Link>

                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        type="button"
                                        class="
                                            btn
                                            btn-link
                                            text-danger
                                            text-decoration-none
                                            p-0
                                        "
                                    >
                                        <i
                                            class="
                                                ri-logout-box-r-line
                                                me-1
                                            "
                                        ></i>

                                        Log Out
                                    </Link>

                                </div>

                            </BCardBody>
                        </BCard>

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
                                class="mb-0 text-muted"
                            >
                                &copy;
                                {{ new Date().getFullYear() }}
                                SafeSky Solution.
                                All rights reserved.
                            </p>

                        </div>

                    </BCol>
                </BRow>
            </BContainer>
        </footer>

    </div>
</template>

<style scoped>
.verification-card {
    border: 1px solid #e4edf4;
    border-radius: 16px;
    box-shadow: 0 10px 35px rgba(7, 17, 38, 0.08);
}

.verification-code-input {
    height: 64px;
    text-align: center;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: 12px;
    border-radius: 10px;
}

.verification-code-input:focus {
    border-color: #0d8fe9;
    box-shadow: 0 0 0 0.2rem rgba(13, 143, 233, 0.12);
}

.verification-code-input::placeholder {
    color: #c9d4df;
}
</style>