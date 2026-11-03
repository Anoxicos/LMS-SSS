<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'

import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'

import defaultImage from '@assets/images/default-user.png'

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

const form = useForm({
  _method: 'PUT',
  name: props.user.name,
  email: props.user.email,
  photo: null,
})

const verificationLinkSent = ref(false)
const photoPreview = ref(null)
const photoInput = ref(null)

/* ================= USER PHOTO ================= */
const normalizePhotoUrl = photo => {
  if (!photo) {
    return defaultImage
  }

  if (photo.startsWith('http://') || photo.startsWith('https://')) {
    try {
      const url = new URL(photo)

      // Corrige automatiquement le port 8000 vers 8002
      return `${window.location.origin}${url.pathname}`
    } catch (error) {
      return photo
    }
  }

  if (photo.startsWith('/storage/')) {
    return photo
  }

  if (photo.startsWith('storage/')) {
    return `/${photo}`
  }

  return `/storage/${photo}`
}

const getUserPhoto = user => {
  if (!user) {
    return defaultImage
  }

  if (user.profile_photo_path) {
    return normalizePhotoUrl(user.profile_photo_path)
  }

  if (user.photo) {
    return normalizePhotoUrl(user.photo)
  }

  if (
    user.profile_photo_url &&
    !user.profile_photo_url.includes('default-user.png') &&
    !user.profile_photo_url.includes('profile-photos/default')
  ) {
    return normalizePhotoUrl(user.profile_photo_url)
  }

  return defaultImage
}

const currentPhoto = computed(() => {
  if (photoPreview.value) {
    return photoPreview.value
  }

  return getUserPhoto(props.user)
})

const handleImageError = event => {
  console.log('IMAGE ERROR SRC:', event.target.src)
  event.target.src = defaultImage
}

/* ================= SUBMIT ================= */
const updateProfileInformation = () => {
  if (photoInput.value && photoInput.value.files[0]) {
    form.photo = photoInput.value.files[0]
  }

  form.post(route('user-profile-information.update'), {
    forceFormData: true,
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    onSuccess: () => {
      clearPhotoFileInput()
      photoPreview.value = null
      form.photo = null

      router.reload({
        only: ['auth'],
      })
    },
  })
}

/* ================= EMAIL VERIFICATION ================= */
const sendEmailVerification = () => {
  verificationLinkSent.value = true
}

/* ================= PHOTO ACTIONS ================= */
const selectNewPhoto = () => {
  photoInput.value?.click()
}

const updatePhotoPreview = () => {
  const photo = photoInput.value?.files[0]

  if (!photo) return

  form.photo = photo

  const reader = new FileReader()

  reader.onload = e => {
    photoPreview.value = e.target.result
  }

  reader.readAsDataURL(photo)
}

const deletePhoto = () => {
  router.delete(route('current-user-photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null
      form.photo = null
      clearPhotoFileInput()

      router.reload({
        only: ['auth'],
      })
    },
  })
}

const clearPhotoFileInput = () => {
  if (photoInput.value) {
    photoInput.value.value = null
  }
}
</script>

<template>
  <FormSection
    @submitted="updateProfileInformation"
    :title="$t('ui-profile-information')"
    :description="$t('ui-update-your-account-s-profile-information-and-email-ad')"
  >
    <template #form>
      <!-- Profile Photo -->
      <div v-if="$page.props.jetstream.managesProfilePhotos" class="mb-4">
        <input
          ref="photoInput"
          type="file"
          class="d-none form-control"
          accept="image/*"
          @change="updatePhotoPreview"
        />

        <InputLabel for="photo" :value="$t('ui-photo')" />

        <div class="profile-photo-wrapper mt-3">
          <div class="profile-photo-box">
            <img
              :src="currentPhoto"
              :alt="user.name || 'Profile photo'"
              class="profile-photo-img"
              @error="handleImageError"
            />
          </div>
        </div>

        <div class="mt-3 d-flex flex-wrap gap-2">
          <BButton
            variant="primary"
            class="btn-sm"
            type="button"
            @click.prevent="selectNewPhoto"
          >
            {{ $t('ui-select-a-new-photo') }}
          </BButton>

          <BButton
            v-if="user.profile_photo_path || user.profile_photo_url || user.photo"
            variant="danger"
            type="button"
            class="btn-sm"
            @click.prevent="deletePhoto"
          >
            {{ $t('ui-remove-photo') }}
          </BButton>
        </div>

        <InputError :message="form.errors.photo" class="mt-2" />
      </div>

      <!-- Name -->
      <div class="mb-3">
        <InputLabel for="name" :value="$t('ui-name')" />

        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          required
          autocomplete="name"
          :class="{ 'is-invalid': form.errors.name }"
        />

        <InputError :message="form.errors.name" class="mt-2" />
      </div>

      <!-- Email -->
      <div class="mb-3">
        <InputLabel for="email" :value="$t('t-email')" />

        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="username"
          :class="{ 'is-invalid': form.errors.email }"
        />

        <InputError :message="form.errors.email" class="mt-2" />

        <div
          v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null"
        >
          <p class="text-sm mt-2 text-muted">
            {{ $t('ui-your-email-address-is-unverified') }}

            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="btn btn-sm btn-warning ms-2"
              @click.prevent="sendEmailVerification"
            >
              {{ $t('ui-click-here-to-re-send-the-verification-email') }}
            </Link>
          </p>

          <div
            v-show="verificationLinkSent"
            class="alert alert-success text-success mt-2"
          >
            {{ $t('ui-a-new-verification-link-has-been-sent-to-your-email-ad') }}
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <BButton
        variant="primary"
        class="w-100"
        type="submit"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        {{ $t('ui-save') }}
      </BButton>

      <p v-if="form.recentlySuccessful" class="alert alert-info mt-3">
        {{ $t('ui-profile-saved') }}
      </p>
    </template>
  </FormSection>
</template>

<style scoped>
.profile-photo-wrapper {
  display: flex;
  align-items: center;
}

.profile-photo-box {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid #ffffff;
  background-color: #f1f5f9;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.16);
}

.profile-photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>