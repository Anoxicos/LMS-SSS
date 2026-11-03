<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)
import PageHeader from '@/Components/page-header.vue'
import { computed, ref } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
  role: {
    type: Object,
    required: true
  },
  permissions: {
    type: Array,
    required: true
  },
  permissionsRole: {
    type: Array,
    required: true
  },
  editAction: {
    type: Boolean,
    required: true
  }
})

const form = useForm({
  permissions: [...props.permissionsRole]
})

const searchQuery = ref('')

const filteredPermissions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()

  if (!q) return props.permissions

  return props.permissions.filter(permission =>
    String(permission.name ?? '').toLowerCase().includes(q)
  )
})

const selectedCount = computed(() => {
  return form.permissions.length
})

const totalPermissions = computed(() => {
  return props.permissions.length
})

const allSelected = computed(() => {
  const visibleIds = filteredPermissions.value.map(permission => permission.id)

  if (visibleIds.length === 0) return false

  return visibleIds.every(id => form.permissions.includes(id))
})

function toggleAllPermissions() {
  const visibleIds = filteredPermissions.value.map(permission => permission.id)

  if (allSelected.value) {
    form.permissions = form.permissions.filter(id => !visibleIds.includes(id))
  } else {
    form.permissions = Array.from(new Set([...form.permissions, ...visibleIds]))
  }
}

function UpdateRole() {
  form.put(route('roles.storeEditRole', { role: props.role.id }), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: t('ui-success'),
        text: t('ui-role-updated-successfully'),
        timer: 2000,
        showConfirmButton: false
      })
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: t('t-error'),
        text: t('t-try-again'),
        timer: 3000,
        showConfirmButton: false
      })
    }
  })
}

function handleClickReadOnly(event) {
  if (!props.editAction) {
    event.preventDefault()
  }
}
</script>

<template>
  <Layout>
    <Head :title="$t('ui-role')" />

    <PageHeader
      :title="editAction ? $t('ui-update-role') : $t('ui-show-role')"
      :page-title="$t('ui-admin')"
    />

    <BRow>
      <BCol cols="12">
        <BCard class="role-page-card border-0 shadow-sm">
          <BCardBody>
            <!-- Header -->
            <div class="role-hero">
              <div class="role-hero-left">
                <div class="role-avatar">
                  {{ role.name ? role.name.charAt(0).toUpperCase() : 'R' }}
                </div>

                <div>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <h4 class="mb-0 fw-bold">
                      {{ role.name }}
                    </h4>

                    <span
                      class="badge"
                      :class="editAction ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info'"
                    >
                      {{ editAction ? 'Edit Mode' : 'Read Only' }}
                    </span>
                  </div>

                  <p class="text-muted mb-0 mt-1">
                    {{ $t('ui-manage-role-permissions-and-access-control') }}
                  </p>
                </div>
              </div>

              <div class="role-stats">
                <div class="role-stat-box">
                  <span class="role-stat-number">
                    {{ selectedCount }}
                  </span>
                  <span class="role-stat-label">
                    {{ $t('ui-selected') }}
                  </span>
                </div>

                <div class="role-stat-box">
                  <span class="role-stat-number">
                    {{ totalPermissions }}
                  </span>
                  <span class="role-stat-label">
                    {{ $t('ui-total') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Permissions section -->
            <div class="permissions-section">
              <div class="permissions-toolbar">
                <div>
                  <h5 class="fw-bold mb-1">
                    {{ $t('ui-permissions') }}
                  </h5>
                  <p class="text-muted mb-0">
                    {{ $t('ui-select-the-permissions-assigned-to-this-role') }}
                  </p>
                </div>

                <div class="permissions-actions">
                  <div class="search-box permissions-search">
                    <input
                      v-model="searchQuery"
                      type="text"
                      class="form-control"
                      :placeholder="$t('ui-search-permission')"
                      :disabled="!permissions?.length"
                    />
                    <i class="ri-search-line search-icon"></i>
                  </div>

                  <button
                    v-if="editAction"
                    @click="toggleAllPermissions"
                    class="btn btn-outline-primary permission-toggle-btn"
                    :disabled="filteredPermissions.length === 0"
                    type="button"
                  >
                    <i
                      class="me-1"
                      :class="allSelected ? 'ri-checkbox-blank-line' : 'ri-checkbox-multiple-line'"
                    ></i>
                    {{ allSelected ? 'Unselect All' : 'Select All' }}
                  </button>
                </div>
              </div>

              <!-- Empty result -->
              <div
                v-if="filteredPermissions.length === 0"
                class="empty-permissions"
              >
                <div class="empty-icon">
                  <i class="ri-search-eye-line"></i>
                </div>
                <h5 class="mb-1">
                  {{ $t('ui-no-permissions-found') }}
                </h5>
                <p class="text-muted mb-0">
                  {{ $t('ui-try-changing-your-search-keywords') }}
                </p>
              </div>

              <!-- Permissions grid -->
              <BRow v-else class="g-3 permissions-grid">
                <BCol
                  xxl="4"
                  xl="4"
                  lg="6"
                  md="6"
                  cols="12"
                  v-for="permission in filteredPermissions"
                  :key="permission.id"
                >
                  <label
                    class="permission-card"
                    :class="{
                      'permission-selected': form.permissions.includes(permission.id),
                      'permission-readonly': !editAction
                    }"
                    :for="'permission-' + permission.id"
                  >
                    <div class="permission-icon">
                      <i class="ri-shield-check-line"></i>
                    </div>

                    <div class="permission-content">
                      <span class="permission-name">
                        {{ permission.name }}
                      </span>
                      <span class="permission-subtitle">
                        {{ $t('ui-permission-access') }}
                      </span>
                    </div>

                    <div class="form-check form-switch form-check-info permission-switch">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :id="'permission-' + permission.id"
                        :value="permission.id"
                        v-model="form.permissions"
                        :class="editAction ? '' : 'readonly'"
                        :tabindex="editAction ? 0 : -1"
                        @click="handleClickReadOnly($event)"
                      />
                    </div>
                  </label>
                </BCol>
              </BRow>

              <!-- Submit -->
              <div
                v-if="editAction"
                class="permissions-footer"
              >
                <div class="text-muted small">
                  {{ selectedCount }} {{ $t('ui-permission-s-selected-from') }} {{ totalPermissions }}.
                </div>

                <button
                  @click="UpdateRole"
                  class="btn btn-primary save-permissions-btn"
                  :disabled="form.processing"
                  type="button"
                >
                  <span
                    v-if="form.processing"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  <i
                    v-else
                    class="ri-save-3-line me-1"
                  ></i>
                  {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>

<style scoped>
.role-page-card {
  border-radius: 20px;
  overflow: hidden;
}

.role-page-card :deep(.card-body) {
  padding: 24px;
}

.role-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  padding: 24px;
  border-radius: 18px;
  background: linear-gradient(135deg, #eef2ff 0%, #ffffff 58%, #f8fafc 100%);
  border: 1px solid #e6eaf5;
  margin-bottom: 24px;
}

.role-hero-left {
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: 0;
}

.role-avatar {
  width: 64px;
  height: 64px;
  min-width: 64px;
  border-radius: 18px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  font-weight: 800;
  box-shadow: 0 12px 28px rgba(104, 124, 254, 0.28);
}

.role-stats {
  display: flex;
  gap: 12px;
}

.role-stat-box {
  min-width: 110px;
  padding: 14px 16px;
  border-radius: 16px;
  background-color: #ffffff;
  border: 1px solid #e9edf5;
  text-align: center;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
}

.role-stat-number {
  display: block;
  font-size: 22px;
  font-weight: 800;
  color: #405189;
  line-height: 1;
}

.role-stat-label {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.permissions-section {
  padding: 22px;
  border-radius: 18px;
  background-color: #ffffff;
  border: 1px solid #edf2f7;
}

.permissions-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 22px;
}

.permissions-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.permissions-search {
  width: 300px;
}

.permissions-search .form-control {
  height: 42px;
  border-radius: 12px;
}

.permission-toggle-btn {
  height: 42px;
  border-radius: 12px;
  white-space: nowrap;
}

.permissions-grid {
  margin-top: 2px;
}

.permission-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 82px;
  padding: 16px;
  border-radius: 16px;
  background-color: #ffffff;
  border: 1px solid #e9edf5;
  cursor: pointer;
  transition: all 0.22s ease;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
}

.permission-card:hover {
  border-color: #687cfe;
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
}

.permission-selected {
  border-color: rgba(104, 124, 254, 0.45);
  background: linear-gradient(135deg, #f6f8ff 0%, #ffffff 100%);
}

.permission-selected .permission-icon {
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
}

.permission-readonly {
  cursor: default;
}

.permission-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 13px;
  background-color: #eef2ff;
  color: #687cfe;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.permission-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.permission-name {
  font-size: 14px;
  font-weight: 700;
  color: #334155;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.permission-subtitle {
  margin-top: 4px;
  font-size: 12px;
  color: #94a3b8;
}

.permission-switch {
  margin-left: auto;
  padding-left: 0;
}

.permission-switch .form-check-input {
  cursor: pointer;
  width: 2.4em;
  height: 1.2em;
}

.permissions-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding-top: 22px;
  margin-top: 22px;
  border-top: 1px solid #edf2f7;
}

.save-permissions-btn {
  min-width: 170px;
  height: 44px;
  border-radius: 12px;
}

.empty-permissions {
  padding: 48px 20px;
  text-align: center;
  border-radius: 16px;
  background-color: #f8fafc;
  border: 1px dashed #dbe3ef;
}

.empty-icon {
  width: 58px;
  height: 58px;
  margin: 0 auto 14px;
  border-radius: 18px;
  background-color: #eef2ff;
  color: #687cfe;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}

.form-check-input.readonly {
  pointer-events: none;
  cursor: default;
}

.form-control {
  border-radius: 10px;
}

.form-control:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.18rem rgba(104, 124, 254, 0.16);
}

/* ================= RESPONSIVE ================= */
@media (max-width: 991.98px) {
  .role-page-card :deep(.card-body) {
    padding: 18px;
  }

  .role-hero {
    flex-direction: column;
    align-items: stretch;
    padding: 20px;
  }

  .role-stats {
    width: 100%;
  }

  .role-stat-box {
    flex: 1;
  }

  .permissions-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .permissions-actions {
    width: 100%;
    flex-direction: column;
  }

  .permissions-search {
    width: 100%;
  }

  .permission-toggle-btn {
    width: 100%;
  }
}

@media (max-width: 575.98px) {
  .role-page-card :deep(.card-body) {
    padding: 14px;
  }

  .role-hero {
    padding: 16px;
    border-radius: 16px;
  }

  .role-hero-left {
    align-items: flex-start;
  }

  .role-avatar {
    width: 54px;
    height: 54px;
    min-width: 54px;
    font-size: 22px;
    border-radius: 16px;
  }

  .role-hero h4 {
    font-size: 18px;
  }

  .permissions-section {
    padding: 16px;
    border-radius: 16px;
  }

  .role-stats {
    flex-direction: column;
  }

  .permission-card {
    align-items: flex-start;
    padding: 14px;
  }

  .permission-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
  }

  .permission-switch {
    position: absolute;
    top: 14px;
    right: 14px;
  }

  .permission-content {
    padding-right: 44px;
  }

  .permissions-footer {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .save-permissions-btn {
    width: 100%;
  }
}
</style>