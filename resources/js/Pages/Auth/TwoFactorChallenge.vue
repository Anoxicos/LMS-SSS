<script setup>
import { nextTick, ref } from 'vue';
import { Link, Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head :title="$t('t-two-factor-authentication')" />

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
                                <div class="mb-4">
                                    <div class="avatar-lg mx-auto">
                                        <div class="avatar-title bg-light text-primary display-5 rounded-circle">
                                            <i class="ri-rotate-lock-line"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2 mt-4">
                                    <div class="text-muted text-center mb-4 mx-lg-3">
                                        <h4 class="">{{ $t('t-two-factor-authentication') }}</h4>
                                        <div class="text-sm text-muted">
                                            <template v-if="! recovery">
                                                {{ $t('t-two-factor-code-instructions') }}
                                            </template>
                                
                                            <template v-else>
                                                {{ $t('t-two-factor-recovery-instructions') }}
                                            </template>
                                        </div>
                                    </div>

                                    <form @submit.prevent="submit">
                                        <div v-if="! recovery">
                                            <InputLabel for="code" :value="$t('t-code')" />
                                            <TextInput
                                                id="code"
                                                ref="codeInput"
                                                v-model="form.code"
                                                type="text"
                                                inputmode="numeric"
                                                autofocus
                                                :placeholder="$t('t-enter-authenticator-code')"
                                                autocomplete="one-time-code"
                                                :class="{ 'is-invalid' : form.errors.code }"
                                            />
                                            <InputError class="mt-2" :message="form.errors.code" />
                                        </div>
                            
                                        <div v-else>
                                            <InputLabel for="recovery_code" :value="$t('t-recovery-code')" />
                                            <TextInput
                                                id="recovery_code"
                                                ref="recoveryCodeInput"
                                                v-model="form.recovery_code"
                                                type="text"
                                                autocomplete="one-time-code"
                                                :class="{ 'is-invalid' : form.errors.recovery_code }"
                                            />
                                            <InputError class="mt-2" :message="form.errors.recovery_code" />
                                        </div>
                            
                                        <div class="d-flex align-items-center justify-content-end mt-4">
                                            <button type="button" class="btn btn-primary me-1" @click.prevent="toggleRecovery">
                                                <template v-if="! recovery">
                                                    {{ $t('t-use-recovery-code') }}
                                                </template>
                            
                                                <template v-else>
                                                    {{ $t('t-use-authentication-code') }}
                                                </template>
                                            </button>
                            
                                            <BButton variant="success" type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">{{ $t('t-login') }}</BButton>
                                        </div>
                                    </form>
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
                            <p class="mb-0 text-muted">&copy; {{ new Date().getFullYear() }} SafeSky Solution. {{ $t('t-all-rights-reserved') }}</p>
                        </div>
                    </BCol>
                </BRow>
            </BContainer>
        </footer>
    </div>
</template>
