<script setup>
import { ref, computed, watch } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import animationData from "@/Components/widgets/msoeawqm.json";
import Lottie from "@/Components/widgets/lottie.vue";
import Swal from "sweetalert2";

const props = defineProps({
  roles: {
    type: Array,
    required: true,
  },
});

/* ================= STATE ================= */
const searchQuery = ref("");
const currentPageRoles = ref(1);
const perPageRoles = 10;

const sortKey = ref("name");
const sortOrders = ref({
  name: 1
});

const rolesLocal = ref(Array.isArray(props.roles) ? [...props.roles] : []);

/* ================= WATCH ================= */
watch(
  () => props.roles,
  (newVal) => {
    rolesLocal.value = Array.isArray(newVal) ? [...newVal] : [];
  },
  { immediate: true }
);

watch(searchQuery, () => {
  currentPageRoles.value = 1;
});

/* ================= HELPERS ================= */
const formatDate = (date) => {
  if (!date) return "-";

  return new Date(date).toLocaleDateString("en-GB", {
    year: "numeric",
    month: "short",
    day: "2-digit",
  });
};

const getRoleInitial = (name) => {
  if (!name) return "R";
  return name.charAt(0).toUpperCase();
};

/* ================= SEARCH ================= */
const filtereRoles = computed(() => {
  const list = Array.isArray(rolesLocal.value) ? rolesLocal.value : [];
  const search = searchQuery.value.toLowerCase().trim();

  if (!search) return list;

  return list.filter((element) =>
    String(element.name ?? "").toLowerCase().includes(search)
  );
});

/* ================= PAGINATION ================= */
const paginatedRoles = computed(() => {
  const list = Array.isArray(filtereRoles.value) ? filtereRoles.value : [];
  const start = (currentPageRoles.value - 1) * perPageRoles;

  return list.slice(start, start + perPageRoles);
});

const totalPagesRoles = computed(() => {
  const length = Array.isArray(filtereRoles.value)
    ? filtereRoles.value.length
    : 0;

  return Math.ceil(length / perPageRoles) || 1;
});

function changePage(page) {
  if (page < 1 || page > totalPagesRoles.value) return;
  currentPageRoles.value = page;
}

/* ================= SORT ================= */
function onSort(column) {
  if (sortKey.value === column) {
    sortOrders.value[column] = -sortOrders.value[column];
  } else {
    sortKey.value = column;
    if (!sortOrders.value[column]) sortOrders.value[column] = 1;
  }

  const order = sortOrders.value[column];

  rolesLocal.value.sort((a, b) => {
    const av = String(a[column] ?? "").toLowerCase();
    const bv = String(b[column] ?? "").toLowerCase();

    const res = av < bv ? -1 : av > bv ? 1 : 0;

    return order === 1 ? res : -res;
  });

  currentPageRoles.value = 1;
}

/* ================= CREATE MODAL ================= */
const showAddModal = ref(false);

const createForm = useForm({
  name: "",
});

function openAddModal() {
  createForm.reset();
  createForm.clearErrors();
  showAddModal.value = true;
}

function closeAddModal() {
  showAddModal.value = false;
}

function submitCreateRole() {
  createForm.post(route("roles.store"), {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false;

      Swal.fire({
        icon: "success",
        title: "Success",
        text: "Role added successfully.",
        timer: 2000,
        showConfirmButton: false,
      });
    },
    onError: () => {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Please check the form and try again.",
        timer: 2500,
        showConfirmButton: false,
      });
    },
  });
}

/* ================= DELETE PLACEHOLDER ================= */
function confirmDeleteRole(role) {
  console.log("Confirm role deletion", role);
}

/* ================= LOTTIE ================= */
const defaultOptions = {
  animationData,
  loop: true,
  autoplay: true,
};
</script>

<template>
  <div>
    <BCard class="role-card border-0 shadow-sm">
      <!-- Responsive Header -->
      <BCardHeader class="role-card-header border-0">
        <BRow class="g-3 align-items-center role-header-row">
          <BCol xl="5" lg="12" md="12" cols="12">
            <div class="role-header-title">
              <div class="role-header-icon">
                <i class="ri-shield-keyhole-line"></i>
              </div>

              <div>
                <h5 class="mb-1 fw-semibold">Roles</h5>
                <p class="text-muted mb-0">
                  Manage user roles and access permissions.
                </p>
              </div>
            </div>
          </BCol>

          <BCol xl="7" lg="12" md="12" cols="12">
            <div class="role-header-actions">
              <div class="search-box role-search-box">
                <input
                  type="text"
                  class="form-control"
                  v-model="searchQuery"
                  placeholder="Search role..."
                />
                <i class="ri-search-line search-icon"></i>
              </div>

              <BButton
                type="button"
                variant="primary"
                class="add-btn role-add-btn"
                id="create-btn"
                @click="openAddModal"
              >
                <i class="ri-add-line align-bottom me-1"></i>
                Add Role
              </BButton>
            </div>
          </BCol>
        </BRow>
      </BCardHeader>

      <BCardBody>
        <!-- Desktop Table -->
        <div class="role-table-wrapper d-none d-lg-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 role-table">
              <thead>
                <tr>
                  <th class="sort" @click="onSort('name')">
                    Role
                    <i class="ri-arrow-up-down-line ms-1"></i>
                  </th>

                  <th class="text-center">
                    Actions
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="role in paginatedRoles" :key="role.id">
                  <td>
                    <Link
                      :href="route('roles.showRole', role.id)"
                      class="text-decoration-none text-dark"
                    >
                      <div class="d-flex align-items-center gap-3">
                        <div class="role-avatar">
                          {{ getRoleInitial(role.name) }}
                        </div>

                        <div>
                          <h6 class="mb-1 fw-semibold">
                            {{ role.name || "-" }}
                          </h6>
                        </div>
                      </div>
                    </Link>
                  </td>

                 

                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <BButton
                        variant="soft-success"
                        size="sm"
                        class="action-btn"
                        :href="route('roles.showRole', role.id)"
                        title="View"
                      >
                        <i class="ri-eye-fill"></i>
                      </BButton>

                      <BButton
                        variant="soft-primary"
                        size="sm"
                        class="action-btn"
                        :href="route('roles.editRole', role.id)"
                        title="Edit"
                      >
                        <i class="ri-pencil-fill"></i>
                      </BButton>

                      <!-- Optional delete button -->
                      <!--
                      <BButton
                        variant="soft-danger"
                        size="sm"
                        class="action-btn"
                        title="Delete"
                        @click="confirmDeleteRole(role)"
                      >
                        <i class="ri-delete-bin-5-fill"></i>
                      </BButton>
                      -->
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="role-mobile-list d-lg-none">
          <div
            v-for="role in paginatedRoles"
            :key="role.id"
            class="role-mobile-card"
          >
            <div class="role-mobile-header">
              <div class="d-flex align-items-center gap-3">
                <div class="role-avatar mobile-avatar">
                  {{ getRoleInitial(role.name) }}
                </div>

                <div class="min-width-0">
                  <h6 class="mb-1 fw-semibold role-mobile-title">
                    {{ role.name || "-" }}
                  </h6>

                  <span class="text-muted small">
                    ID: {{ role.id || "-" }}
                  </span>
                </div>
              </div>

              <div class="d-flex gap-2">
                <BButton
                  variant="soft-success"
                  size="sm"
                  class="action-btn"
                  :href="route('roles.showRole', role.id)"
                  title="View"
                >
                  <i class="ri-eye-fill"></i>
                </BButton>

                <BButton
                  variant="soft-primary"
                  size="sm"
                  class="action-btn"
                  :href="route('roles.editRole', role.id)"
                  title="Edit"
                >
                  <i class="ri-pencil-fill"></i>
                </BButton>

                <!-- Optional delete button -->
                <!--
                <BButton
                  variant="soft-danger"
                  size="sm"
                  class="action-btn"
                  title="Delete"
                  @click="confirmDeleteRole(role)"
                >
                  <i class="ri-delete-bin-5-fill"></i>
                </BButton>
                -->
              </div>
            </div>

            <div class="role-mobile-body">
              <div class="role-mobile-item">
                <span class="role-mobile-label">
                  <i class="ri-shield-user-line me-1"></i>
                  Role Name
                </span>
                <span class="role-mobile-value">
                  {{ role.name || "-" }}
                </span>
              </div>

              
            </div>
          </div>
        </div>

        <!-- No Result -->
        <div
          class="text-center py-5"
          v-if="paginatedRoles.length < 1"
        >
          <lottie
            class="avatar-xl mx-auto"
            colors="primary:#121331,secondary:#08a88a"
            :options="defaultOptions"
            :height="85"
            :width="85"
          />

          <h5 class="mt-3 mb-1">No roles found</h5>
          <p class="text-muted mb-0">
            Try changing your search keywords.
          </p>
        </div>

        <!-- Pagination -->
        <div
          class="role-pagination"
          v-if="filtereRoles.length > 0"
        >
          <div class="text-muted role-pagination-text">
            Showing
            <span class="fw-semibold">{{ paginatedRoles.length }}</span>
            of
            <span class="fw-semibold">{{ filtereRoles.length }}</span>
            result(s)
          </div>

          <ul class="pagination listjs-pagination mb-0">
            <li
              class="page-item"
              :class="{ disabled: currentPageRoles === 1 }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="changePage(currentPageRoles - 1)"
              >
                <i class="ri-arrow-left-s-line"></i>
              </BLink>
            </li>

            <li
              class="page-item"
              :class="{ active: n === currentPageRoles }"
              v-for="n in totalPagesRoles"
              :key="n"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="changePage(n)"
              >
                {{ n }}
              </BLink>
            </li>

            <li
              class="page-item"
              :class="{ disabled: currentPageRoles === totalPagesRoles }"
            >
              <BLink
                href="#"
                class="page-link"
                @click.prevent="changePage(currentPageRoles + 1)"
              >
                <i class="ri-arrow-right-s-line"></i>
              </BLink>
            </li>
          </ul>
        </div>
      </BCardBody>
    </BCard>

    <!-- Add Role Modal -->
    <BModal
      v-model="showAddModal"
      title="Add Role"
      centered
      hide-footer
      size="md"
      class="role-modal"
    >
      <form @submit.prevent="submitCreateRole">
        <div class="mb-3">
          <label class="form-label">Role Name</label>
          <input
            v-model="createForm.name"
            type="text"
            class="form-control"
            placeholder="Example: HR"
            :class="{ 'is-invalid': createForm.errors.name }"
          />

          <div
            v-if="createForm.errors.name"
            class="invalid-feedback"
          >
            {{ createForm.errors.name }}
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 modal-actions">
          <BButton
            variant="light"
            type="button"
            @click="closeAddModal"
          >
            Cancel
          </BButton>

          <BButton
            variant="primary"
            type="submit"
            :disabled="createForm.processing"
          >
            <span
              v-if="createForm.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            {{ createForm.processing ? "Saving..." : "Save" }}
          </BButton>
        </div>
      </form>
    </BModal>
  </div>
</template>

<style scoped>
.role-card {
  border-radius: 18px;
  overflow: hidden;
}

/* ================= RESPONSIVE HEADER ================= */
.role-card-header {
  padding: 24px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.role-header-row {
  width: 100%;
}

.role-header-title {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.role-header-icon {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 10px 24px rgba(104, 124, 254, 0.25);
}

.role-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.role-search-box {
  width: 320px;
  min-width: 260px;
}

.role-search-box .form-control {
  height: 42px;
  border-radius: 12px;
  border-color: #e5e7eb;
}

.role-add-btn {
  height: 42px;
  border-radius: 12px;
  white-space: nowrap;
  padding-inline: 18px;
}

/* ================= TABLE ================= */
.role-table-wrapper {
  border: 1px solid #edf2f7;
  border-radius: 16px;
  overflow: hidden;
  background-color: #ffffff;
}

.role-table thead {
  background-color: #687cfe;
}

.role-table thead th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: #f2f4f6;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.role-table tbody td {
  padding: 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.role-table tbody tr:hover {
  background-color: #f8fafc;
}

.sort {
  cursor: pointer;
  user-select: none;
}

.role-avatar {
  width: 52px;
  height: 52px;
  min-width: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, #687cfe 0%, #405189 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  box-shadow: 0 8px 18px rgba(104, 124, 254, 0.28);
}

.mobile-avatar {
  width: 56px;
  height: 56px;
  min-width: 56px;
}

.action-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.pagination .page-link {
  border-radius: 10px;
  margin: 0 3px;
  border: 1px solid #e5e7eb;
}

.pagination .page-item.active .page-link {
  background-color: #687cfe;
  border-color: #687cfe;
}

/* ================= MOBILE CARDS ================= */
.role-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.role-mobile-card {
  border: 1px solid #edf2f7;
  border-radius: 18px;
  background-color: #ffffff;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.role-mobile-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.role-mobile-title {
  max-width: 170px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.role-mobile-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 14px;
}

.role-mobile-item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.role-mobile-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  min-width: 105px;
}

.role-mobile-value {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

.role-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.role-pagination-text {
  font-size: 14px;
}

.min-width-0 {
  min-width: 0;
}

.form-label {
  font-weight: 600;
  color: #334155;
}

.form-control {
  border-radius: 10px;
  min-height: 42px;
}

.form-control:focus {
  border-color: #687cfe;
  box-shadow: 0 0 0 0.18rem rgba(104, 124, 254, 0.16);
}

/* ================= RESPONSIVE ================= */
@media (max-width: 1199.98px) {
  .role-header-actions {
    justify-content: flex-start;
  }

  .role-search-box {
    flex: 1;
    width: auto;
    min-width: 240px;
  }
}

@media (max-width: 991.98px) {
  .role-card-header {
    padding: 18px;
  }

  .role-pagination {
    justify-content: center;
    text-align: center;
  }

  .role-pagination-text {
    width: 100%;
  }

  .pagination {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 767.98px) {
  .role-header-title {
    align-items: flex-start;
  }

  .role-header-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    font-size: 20px;
  }

  .role-header-title h5 {
    font-size: 17px;
  }

  .role-header-title p {
    font-size: 13px;
  }

  .role-header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .role-search-box,
  .role-add-btn {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 575.98px) {
  .role-card {
    border-radius: 14px;
  }

  .role-card-header {
    padding: 16px;
  }

  .role-mobile-card {
    padding: 14px;
    border-radius: 16px;
  }

  .role-mobile-header {
    flex-direction: column;
  }

  .role-mobile-header > .d-flex:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  .role-mobile-title {
    max-width: 220px;
  }

  .role-mobile-item {
    flex-direction: column;
    gap: 4px;
  }

  .role-mobile-value {
    text-align: left;
  }

  .pagination .page-link {
    min-width: 34px;
    height: 34px;
    padding: 6px 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-actions {
    flex-direction: column;
  }

  .modal-actions .btn {
    width: 100%;
  }
}

@media (max-width: 420px) {
  .role-header-title {
    flex-direction: column;
  }
}
</style>