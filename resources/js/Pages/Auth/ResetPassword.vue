<script setup>
import { ref } from 'vue';
import {
    Head,
    Link,
    useForm,
} from '@inertiajs/vue3';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },

    token: {
        type: String,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| Password visibility
|--------------------------------------------------------------------------
*/

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

/*
|--------------------------------------------------------------------------
| Reset password form
|--------------------------------------------------------------------------
*/

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submit = () => {
    form.post(
        route('password.update'),
        {
            preserveScroll: true,

            onFinish: () => {
                form.reset(
                    'password',
                    'password_confirmation'
                );
            },
        }
    );
};
</script>

<template>
    <Head :title="$t('t-reset-password')" />

    <div class="auth-page-wrapper pt-5">

        <div class="auth-page-content">

            <BContainer>

                <!-- =========================================================
                     Logo
                ========================================================== -->

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
                                    mb-0
                                    fs-15
                                    text-muted
                                "
                            >
                                {{ $t('t-safesky-learning-management-system') }}
                            </p>
                        </div>

                    </BCol>
                </BRow>

                <!-- =========================================================
                     Card
                ========================================================== -->

                <BRow class="justify-content-center">

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
                                reset-card
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
                                                reset-icon
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
                                            reset-title
                                        "
                                    >
                                        {{ $t('t-create-new-password') }}
                                    </h4>

                                    <p
                                        class="
                                            text-muted
                                            mb-0
                                            reset-description
                                        "
                                    >
                                        {{ $t('t-create-secure-password-description') }}
                                    </p>
                                </div>

                                <!-- =================================================
                                     Form
                                ================================================== -->

                                <form
                                    @submit.prevent="submit"
                                >

                                    <!-- Email -->

                                    <div class="mb-3">

                                        <InputLabel
                                            for="email"
                                            :value="$t('t-email-address')"
                                        />

                                        <div
                                            class="
                                                position-relative
                                                mt-1
                                            "
                                        >
                                            <input
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                class="
                                                    form-control
                                                    form-control-lg
                                                    email-input
                                                "
                                                :class="{
                                                    'is-invalid':
                                                        form.errors.email
                                                }"
                                                autocomplete="email"
                                                readonly
                                            >

                                            <span
                                                class="
                                                    position-absolute
                                                    top-50
                                                    end-0
                                                    translate-middle-y
                                                    me-3
                                                    text-muted
                                                "
                                            >
                                                <i
                                                    class="
                                                        ri-mail-line
                                                    "
                                                ></i>
                                            </span>
                                        </div>

                                        <InputError
                                            class="mt-1"
                                            :message="
                                                form.errors.email
                                            "
                                        />

                                    </div>

                                    <!-- New password -->

                                    <div class="mb-3">

                                        <InputLabel
                                            for="password"
                                            :value="$t('t-new-password')"
                                        />

                                        <div
                                            class="
                                                position-relative
                                                auth-pass-inputgroup
                                                mt-1
                                            "
                                        >

                                            <input
                                                id="password"
                                                v-model="form.password"
                                                :type="
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                class="
                                                    form-control
                                                    form-control-lg
                                                    pe-5
                                                "
                                                :class="{
                                                    'is-invalid':
                                                        form.errors.password
                                                }"
                                                :placeholder="$t('t-enter-new-password')"
                                                required
                                                autofocus
                                                autocomplete="new-password"
                                            >

                                            <BButton
                                                variant="link"
                                                type="button"
                                                class="
                                                    position-absolute
                                                    end-0
                                                    top-50
                                                    translate-middle-y
                                                    text-decoration-none
                                                    text-muted
                                                    password-addon
                                                    me-1
                                                "
                                                tabindex="-1"
                                                @click="
                                                    showPassword =
                                                        !showPassword
                                                "
                                            >
                                                <i
                                                    :class="
                                                        showPassword
                                                            ? 'ri-eye-off-line'
                                                            : 'ri-eye-line'
                                                    "
                                                ></i>
                                            </BButton>

                                        </div>

                                        <InputError
                                            class="mt-1"
                                            :message="
                                                form.errors.password
                                            "
                                        />

                                        <div
                                            class="
                                                password-hint
                                                mt-2
                                            "
                                        >
                                            <i
                                                class="
                                                    ri-information-line
                                                    me-1
                                                "
                                            ></i>

                                            {{ $t('t-strong-password-hint') }}
                                        </div>

                                    </div>

                                    <!-- Confirm password -->

                                    <div class="mb-4">

                                        <InputLabel
                                            for="password_confirmation"
                                            :value="$t('t-confirm-new-password')"
                                        />

                                        <div
                                            class="
                                                position-relative
                                                auth-pass-inputgroup
                                                mt-1
                                            "
                                        >

                                            <input
                                                id="password_confirmation"
                                                v-model="
                                                    form.password_confirmation
                                                "
                                                :type="
                                                    showPasswordConfirmation
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                class="
                                                    form-control
                                                    form-control-lg
                                                    pe-5
                                                "
                                                :class="{
                                                    'is-invalid':
                                                        form.errors
                                                            .password_confirmation
                                                }"
                                                :placeholder="$t('t-confirm-new-password')"
                                                required
                                                autocomplete="new-password"
                                            >

                                            <BButton
                                                variant="link"
                                                type="button"
                                                class="
                                                    position-absolute
                                                    end-0
                                                    top-50
                                                    translate-middle-y
                                                    text-decoration-none
                                                    text-muted
                                                    password-addon
                                                    me-1
                                                "
                                                tabindex="-1"
                                                @click="
                                                    showPasswordConfirmation =
                                                        !showPasswordConfirmation
                                                "
                                            >
                                                <i
                                                    :class="
                                                        showPasswordConfirmation
                                                            ? 'ri-eye-off-line'
                                                            : 'ri-eye-line'
                                                    "
                                                ></i>
                                            </BButton>

                                        </div>

                                        <InputError
                                            class="mt-1"
                                            :message="
                                                form.errors
                                                    .password_confirmation
                                            "
                                        />

                                    </div>

                                    <!-- Submit -->

                                    <div class="d-grid">

                                        <BButton
                                            type="submit"
                                            size="lg"
                                            class="
                                                reset-button
                                                w-100
                                            "
                                            :disabled="
                                                form.processing
                                            "
                                        >

                                            <BSpinner
                                                v-if="
                                                    form.processing
                                                "
                                                small
                                                class="me-2"
                                            />

                                            <i
                                                v-else
                                                class="
                                                    ri-lock-unlock-line
                                                    me-2
                                                "
                                            ></i>

                                            {{
                                                form.processing
                                                    ? $t('t-resetting-password')
                                                    : $t('t-reset-password')
                                            }}

                                        </BButton>

                                    </div>

                                </form>

                            </BCardBody>

                        </BCard>

                        <!-- =================================================
                             Back to login
                        ================================================== -->

                        <div
                            class="
                                mt-4
                                text-center
                            "
                        >

                            <p
                                class="
                                    mb-0
                                    text-muted
                                "
                            >
                                {{ $t('t-remember-password') }}

                                <Link
                                    :href="route('login')"
                                    class="
                                        fw-semibold
                                        text-primary
                                        text-decoration-none
                                        ms-1
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

        <!-- =============================================================
             Footer
        ============================================================== -->

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
/*
|--------------------------------------------------------------------------
| Card
|--------------------------------------------------------------------------
*/

.reset-card {
    border: 1px solid #e4edf4;
    border-radius: 16px;
    overflow: hidden;
    box-shadow:
        0 10px 35px
        rgba(7, 17, 38, 0.08);
}

/*
|--------------------------------------------------------------------------
| Icon
|--------------------------------------------------------------------------
*/

.reset-icon {
    color: #0d8fe9;
    background: #eaf7ff;
}

/*
|--------------------------------------------------------------------------
| Typography
|--------------------------------------------------------------------------
*/

.reset-title {
    color: #071126;
}

.reset-description {
    max-width: 390px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/*
|--------------------------------------------------------------------------
| Inputs
|--------------------------------------------------------------------------
*/

.form-control {
    min-height: 50px;
    border-color: #dce6ee;
    border-radius: 9px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #0d8fe9;
    box-shadow:
        0 0 0 0.2rem
        rgba(13, 143, 233, 0.1);
}

/*
|--------------------------------------------------------------------------
| Readonly email
|--------------------------------------------------------------------------
*/

.email-input[readonly] {
    background-color: #f8fafc;
    color: #64748b;
    cursor: default;
}

/*
|--------------------------------------------------------------------------
| Password button
|--------------------------------------------------------------------------
*/

.password-addon {
    z-index: 5;
    padding: 0.5rem 0.75rem;
}

.password-addon:hover,
.password-addon:focus {
    color: #0d8fe9 !important;
}

/*
|--------------------------------------------------------------------------
| Hint
|--------------------------------------------------------------------------
*/

.password-hint {
    color: #7c8b9a;
    font-size: 12px;
}

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

.reset-button {
    min-height: 50px;

    background: #071126;
    border-color: #071126;

    font-weight: 600;

    border-radius: 9px;

    transition:
        background-color 0.2s ease,
        border-color 0.2s ease,
        transform 0.2s ease;
}

.reset-button:hover:not(:disabled),
.reset-button:focus:not(:disabled) {
    background: #132b4a;
    border-color: #132b4a;
}

.reset-button:disabled {
    opacity: 0.65;
}
</style>
