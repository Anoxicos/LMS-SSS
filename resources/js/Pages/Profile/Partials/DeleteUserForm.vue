<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <BCard no-body>
        <BCardHeader>
            <BCardTitle>{{ $t('ui-delete-account') }}</BCardTitle>
            <p class="text-muted mb-0">{{ $t('ui-permanently-delete-your-account') }}</p>
        </BCardHeader>
        <BCardBody class="p-4">
            <div class="text-sm text-muted">
                {{ $t('ui-once-your-account-is-deleted-all-of-its-resources-and-') }}
            </div>

            <div class="mt-5">
                <BButton variant="danger w-100" @click="confirmUserDeletion">{{ $t('ui-delete-account') }}</BButton>
            </div>

            <BModal v-model="confirmingUserDeletion" :title="$t('ui-delete-account')" hide-footer header-class="bg-danger-subtle p-3">
                <p class="text-muted">{{ $t('ui-are-you-sure-you-want-to-delete-your-account-once-your') }}</p>
                <div class="mb-3">
                    <TextInput ref="passwordInput" v-model="form.password" type="password" :placeholder="$t('ui-password')" required autocomplete="current-password" @keyup.enter="deleteUser" :class="{ 'is-invalid': form.errors.password }" />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="text-end">
                    <BButton variant="danger" @click="closeModal">{{ $t('ui-close') }}</BButton>
                    <BButton variant="primary" class="ms-1" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteUser">{{ $t('ui-delete-account') }}</BButton>
                </div>
            </BModal>
        </BCardBody>
    </BCard>
</template>
