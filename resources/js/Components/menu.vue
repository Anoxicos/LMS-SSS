<script setup>
import { Link, router } from '@inertiajs/vue3';
import simplebar from 'simplebar-vue';
import { layoutComputed } from '@/state/helpers';
import { computed, onMounted, onBeforeUnmount, reactive } from 'vue';
import { useStore } from 'vuex';

import { usePermissions } from '@/composables/permissions';

const settings = reactive({
  minScrollbarLength: 60,
});

const store = useStore();

const hasRole = (role)=> {
  const { hasRole  } = usePermissions();

  return hasRole(role);
}

const hasPermission = (permission)=> {
  const { hasPermission  } = usePermissions();

  return hasPermission(permission);
}


const { getUser } = usePermissions();
const user = getUser()

const layoutType = computed(() => {
  return store?.state?.layout?.layoutType ?? {};
});

function clearMenuActive() {
  const nav = document.querySelector('#navbar-nav');
  if (!nav) return;

  nav.querySelectorAll('.active').forEach((el) => el.classList.remove('active'));
  nav.querySelectorAll('.show').forEach((el) => el.classList.remove('show'));
}

function setActiveByKey(key) {
  const nav = document.querySelector('#navbar-nav');
  if (!nav || !key) return;

  clearMenuActive();

  const a = nav.querySelector('[data-key="' + key + '"]');
  if (!a) return;

  a.classList.add('active');

  let parentCollapseDiv = a.closest('.collapse.menu-dropdown');
  if (parentCollapseDiv) {
    parentCollapseDiv.classList.add('show');
    parentCollapseDiv.parentElement.children[0].classList.add('active');
    parentCollapseDiv.parentElement.children[0].setAttribute('aria-expanded', 'true');

    if (parentCollapseDiv.parentElement.closest('.collapse.menu-dropdown')) {
      const parentCollapse = parentCollapseDiv.parentElement.closest('.collapse');
      parentCollapse.classList.add('show');

      if (parentCollapse.previousElementSibling)
        parentCollapse.previousElementSibling.classList.add('active');

      const grandparent =
        parentCollapse.previousElementSibling?.parentElement?.closest('.collapse');

      if (grandparent && grandparent.previousElementSibling) {
        grandparent.previousElementSibling.classList.add('active');
        grandparent.classList.add('show');
      }
    }
  }
}

function initActiveMenu() {
  setTimeout(() => {
    const savedKey = localStorage.getItem('activeMenuKey');
    if (savedKey) {
      setActiveByKey(savedKey);
    }
  }, 0);
}

function onRoutechange() {
  setTimeout(() => {
    const nav = document.querySelector('#navbar-nav');
    if (!nav) return;

    const savedKey = localStorage.getItem('activeMenuKey');
    if (!savedKey) return;

    const activeLink = nav.querySelector('[data-key="' + savedKey + '"]');
    if (!activeLink) return;

    const currentPosition = activeLink.offsetTop;
    if (currentPosition > document.documentElement.clientHeight) {
      const wrapper = document.querySelector('#scrollbar .simplebar-content-wrapper');
      if (wrapper) {
        wrapper.scrollTop = currentPosition + 300;
      }
    }
  }, 500);
}

function attachClickHandlers() {
  const nav = document.querySelector('#navbar-nav');
  if (!nav) return;

  const links = nav.querySelectorAll('a[data-key]');
  links.forEach((link) => {
    link.addEventListener('click', () => {
      const key = link.getAttribute('data-key');
      if (!key) return;

      localStorage.setItem('activeMenuKey', key);
      setActiveByKey(key);
    });
  });
}

// handler pour l'event Inertia
function handleNavigate() {
  initActiveMenu();
  onRoutechange();
}

// --- lifecycle ---
onMounted(() => {
  // équivalent de created()
  router.on('navigate', handleNavigate);

  // équivalent de mounted()
  initActiveMenu();
  onRoutechange();
  attachClickHandlers();

  const collapses = document.querySelectorAll('.navbar-nav .collapse');
  if (collapses && collapses.length) {
    collapses.forEach((collapse) => {
      // show
      const showHandler = (e) => {
        e.stopPropagation();
        let closestCollapse = collapse.parentElement.closest('.collapse');
        if (closestCollapse) {
          let siblingCollapses = closestCollapse.querySelectorAll('.collapse');
          siblingCollapses.forEach((siblingCollapse) => {
            if (siblingCollapse.classList.contains('show')) {
              siblingCollapse.classList.remove('show');
              siblingCollapse.parentElement.firstChild.setAttribute('aria-expanded', 'false');
            }
          });
        } else {
          let getSiblings = (elem) => {
            let siblings = [];
            let sibling = elem.parentNode.firstChild;
            while (sibling) {
              if (sibling.nodeType === 1 && sibling !== elem) {
                siblings.push(sibling);
              }
              sibling = sibling.nextSibling;
            }
            return siblings;
          };
          let siblings = getSiblings(collapse.parentElement);
          siblings.forEach((item) => {
            if (item.childNodes.length > 2) {
              item.firstElementChild.setAttribute('aria-expanded', 'false');
              item.firstElementChild.classList.remove('active');
            }
            let ids = item.querySelectorAll('*[id]');
            ids.forEach((item1) => {
              item1.classList.remove('show');
              item1.parentElement.firstChild.setAttribute('aria-expanded', 'false');
              item1.parentElement.firstChild.classList.remove('active');
              if (item1.childNodes.length > 2) {
                let val = item1.querySelectorAll('ul li a');
                val.forEach((subitem) => {
                  if (subitem.hasAttribute('aria-expanded'))
                    subitem.setAttribute('aria-expanded', 'false');
                });
              }
            });
          });
        }
      };

      // hide
      const hideHandler = (e) => {
        e.stopPropagation();
        let childCollapses = collapse.querySelectorAll('.collapse');
        childCollapses.forEach((childCollapse) => {
          let childCollapseInstance = childCollapse;
          childCollapseInstance.classList.remove('show');
          childCollapseInstance.parentElement.firstChild.setAttribute('aria-expanded', 'false');
        });
      };

      collapse.addEventListener('show.bs.collapse', showHandler);
      collapse.addEventListener('hide.bs.collapse', hideHandler);
    });
  }
});

// Optionnel : si tu veux nettoyer les listeners (selon la version d’Inertia / Bootstrap)
onBeforeUnmount(() => {
  // Ici tu pourrais retirer les listeners si tu stockes les références (ids, callbacks, etc.)
});
</script>

<template>
  <BContainer fluid>
    <div id="two-column-menu"></div>

    <!-- <pre>{{  user.entreprise.pay.hashid }}</pre> -->

    <template v-if="layoutType === 'vertical' || layoutType === 'semibox'">
      <ul class="navbar-nav h-100" id="navbar-nav">
        <li class="menu-title">
          <span data-key="t-menu"> {{ $t("t-menu") }}</span>
        </li>

        <li class="nav-item">
          <Link class="nav-link menu-link" href="/" data-key="accueil">
            <i class="ri-home-8-line"></i>
            <span data-key="t-widgets">{{ $t("t-dashboard") }}</span>
          </Link>
        </li>      

        <li class="nav-item"  v-if="hasRole('Super-admin')">
            <Link class="nav-link menu-link" :href="route('roles.index')" data-key="roles">
            <i class="ri-shield-user-line"></i>
            <span>{{ $t('t-roles') }}</span>
            </Link>
        </li>

        <li class="nav-item"  v-if="hasRole('Super-admin')">
            <Link class="nav-link menu-link" :href="route('companies.index')" data-key="companies">
            <i class="ri-building-4-line"></i>
            <span>{{ $t('t-companies') }}</span>
            </Link>
        </li>

        <li class="nav-item"  v-if="hasPermission('read user')">
            <Link class="nav-link menu-link" :href="route('users.index')" data-key="users">
            <i class="ri-team-line"></i>
            <span>{{ $t('t-users') }}</span>
            </Link>
        </li>

        <li class="nav-item"  v-if="hasPermission('read learner')">
            <Link class="nav-link menu-link" :href="route('learners.index')" data-key="learners">
            <i class="ri-team-line"></i>
            <span>{{ $t('t-learners') }}</span>
            </Link>
        </li>

        <li class="nav-item" v-if="hasPermission('read category') ">
            <Link
              class="nav-link menu-link"
              :href="route('categories.index')"
              data-key="categories"
            >
              <i class="ri-folder-3-line"></i>
              <span>{{ $t('t-categories') }}</span>
            </Link>
        </li>

        <li class="nav-item" v-if="hasPermission('read course')">
          <Link
            class="nav-link menu-link"
            :href="route('courses.index')"
            data-key="courses"
          >
            <i class="ri-book-open-line"></i>
            <span>{{ $t('t-courses') }}</span>
          </Link>
        </li>

          
        <li class="nav-item" v-if="hasPermission('read course')">
          <Link
            class="nav-link menu-link"
            :href="route('courses.scorm.index')"
            data-key="SCORM courses"
          >
            <i class="ri-code-box-line me-1"></i>
            <span>{{ $t('t-scorm-courses') }}</span>
          </Link>

          
        </li>

         <li class="nav-item" v-if="hasPermission('read banks')">
          <Link
            class="nav-link menu-link"
            :href="route('banks.index')"
            data-key="banks"
          >
           <i class="ri-bank-line"></i>
            <span>{{ $t('t-question-banks') }}</span>
          </Link>
        </li>
      




         <li class="nav-item">
          <a class="nav-link menu-link" href="#exames" data-bs-toggle="collapse" role="button" aria-expanded="false"
            aria-controls="exames">
            <i class="ri-file-list-3-line"></i>
            <span>{{ $t('t-requests') }}</span>
          </a>
          <div class="collapse menu-dropdown" id="exames">
            <ul class="nav nav-sm flex-column">
               <li
                  class="nav-item"
                  v-if="hasPermission('read company request')"
                >
                  <Link
                    class="nav-link menu-link"
                    :href="route('company-requests.index')"
                    data-key="company-requests"
                  >
                    <i class="ri-building-4-line"></i>

                    <span>
                      {{ $t('t-company') }}
                    </span>
                  </Link>
              </li>

              <li
                  class="nav-item"
                  v-if="hasRole('Super-admin')"
                >
                  <Link
                    class="nav-link menu-link"
                    :href="route('individual-requests.index')"
                    data-key="individual-requests"
                  >
                    <i class="ri-user-received-2-line"></i>

                    <span>
                      {{ $t('t-individual') }}
                    </span>
                  </Link>
              </li>
            </ul>
          </div>
        </li>

       

         <li class="nav-item" v-if="hasRole('Learner')">
          <Link
            class="nav-link menu-link"
            :href="route('learner-space.my-courses')"
            data-key="learner-my-courses"
          >
            <i class="ri-file-list-3-line"></i>
            <span>{{ $t('t-my-courses') }}</span>
          </Link>
        </li>


       

        
                
      </ul>
    </template>
  </BContainer>
</template>
