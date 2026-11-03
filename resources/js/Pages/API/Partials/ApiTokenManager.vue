<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});
const permissionModal = ref(false)
const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);
const apiTokenDeleteModal = ref(false);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
    permissionModal.value = true;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null, permissionModal.value = false),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
    apiTokenDeleteModal.value = true;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null, apiTokenDeleteModal.value = false),
    });
};
</script>

<template>
    <div>
        <!-- Generate API Token -->
        <FormSection @submitted="createApiToken" :title="$t('ui-create-api-token')" :description="$t('ui-api-tokens-allow-third-party-services-to-authenticate-')">

            <template #form>
                <!-- Token Name -->
                <div class="mb-3">
                    <InputLabel for="name" :value="$t('ui-name')" />
                    <TextInput id="name" v-model="createApiTokenForm.name" type="text" autofocus :class="{ 'is-invalid': createApiTokenForm.errors.name }" />
                    <InputError :message="createApiTokenForm.errors.name" class="mt-2" />
                </div>

                <!-- Token Permissions -->
                <div v-if="availablePermissions.length > 0" class="">
                    <InputLabel for="permissions" :value="$t('ui-permissions')" />

                    <div v-for="permission in availablePermissions" :key="permission">
                        <label class="d-flex align-items-center">
                            <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                            <span class="ms-2 text-sm text-muted">{{ permission }}</span>
                        </label>
                    </div>
                </div>
            </template>

            <template #actions>
                <BButton variant="primary w-100" type="submit" :class="{ 'opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">{{ $t('ui-create') }}</BButton>
                <p v-if="createApiTokenForm.recentlySuccessful" class="alert alert-success mt-3">{{ $t('ui-created-label') }}</p>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <!-- Manage API Tokens -->
            <BCard no-body>
                <BCardHeader>
                    <BCardTitle>{{ $t('ui-manage-api-tokens') }}</BCardTitle>
                    <p class="text-muted mb-0">{{ $t('ui-you-may-delete-any-of-your-existing-tokens-if-they-are') }}</p>
                </BCardHeader>

                <!-- API Token List -->
                <BCardBody class="p-4">
                    <div v-for="token in tokens" :key="token.id" class="d-flex align-items-center justify-content-between mb-2">
                        <div class="break-all">
                            {{ token.name }}
                        </div>

                        <div class="d-flex align-items-center ms-2">
                            <div v-if="token.last_used_ago" class="text-sm text-muted">
                                {{ $t('ui-last-used') }} {{ token.last_used_ago }}
                            </div>

                            <BButton variant="primary" v-if="availablePermissions.length > 0" class="btn-sm me-1" @click="manageApiTokenPermissions(token)">
                                {{ $t('ui-permissions') }}
                            </BButton>

                            <BButton variant="danger" class="btn-sm" @click="confirmApiTokenDeletion(token)">
                                {{ $t('ui-delete') }}
                            </BButton>
                        </div>
                    </div>
                </BCardBody>
            </BCard>
        </div>

        <!-- Token Value Modal -->
        <BModal v-model="displayingToken" :title="$t('ui-api-token')" hide-footer header-class="bg-success-subtle p-3">
            <p class="fs-14 text-muted">{{ $t('ui-please-copy-your-new-api-token-for-your-security-it-wo-label') }}</p>
            <div v-if="$page.props.jetstream.flash.token" class="mt-4 rounded mb-4">
                <pre class="language-markup"><code>{{ $page.props.jetstream.flash.token }}</code></pre>
            </div>
            <div class="text-end">
                <BButton variant="danger" @click="displayingToken = false">{{ $t('ui-cancel') }}</BButton>
            </div>
        </BModal>

        <!-- API Token Permissions Modal -->
        <BModal v-model="permissionModal" :title="$t('ui-api-token-permissions')" hide-footer>
            <div v-for="permission in availablePermissions" :key="permission">
                <label class="d-flex align-items-center">
                    <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                    <span class="ms-2 text-sm text-muted">{{ permission }}</span>
                </label>
            </div>
            <div class="text-end">
                <BButton variant="danger" @click="permissionModal = !permissionModal">{{ $t('ui-cancel') }}</BButton>
                <BButton variant="primary" class="ms-1" :class="{ 'opacity-25': updateApiTokenForm.processing }" :disabled="updateApiTokenForm.processing" @click="updateApiToken">{{ $t('ui-save') }}</BButton>
            </div>
        </BModal>

        <!-- Delete Token Confirmation Modal -->
        <BModal v-model="apiTokenDeleteModal" :title="$t('ui-delete-api-token')" header-class="bg-danger-subtle p-2"  hide-footer>
            <p class="fs-14">{{ $t('ui-are-you-sure-you-would-like-to-delete-this-api-token') }}</p>
            <div class="text-end">
                <BButton variant="danger" class="btn-sm" @click="apiTokenDeleteModal = !apiTokenDeleteModal">{{ $t('ui-cancel') }}</BButton>
                <BButton variant="primary" class="ms-1 btn-sm" :class="{ 'opacity-25': deleteApiTokenForm.processing }" :disabled="deleteApiTokenForm.processing" @click="deleteApiToken">{{ $t('ui-delete') }}</BButton>
            </div>
        </BModal>
    </div>
</template>
