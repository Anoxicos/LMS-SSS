<script>
import { layoutComputed } from "@/state/helpers";

import Vertical from "./vertical.vue";
import Horizontal from "./horizontal.vue";
import TwoColumns from "./twocolumn.vue";

import SuperAdminPendingReminder from "@/Components/SuperAdminPendingReminder.vue";
import HomeArrival from "@/Components/HomeArrival.vue";
import ImmersiveHint from "@/Components/ImmersiveHint.vue";
import CustomCursor from "@/Components/CustomCursor.vue";

export default {
    components: {
        Vertical,
        Horizontal,
        TwoColumns,
        SuperAdminPendingReminder,
        HomeArrival,
        ImmersiveHint,
        CustomCursor,
    },

    data() {
        return {};
    },

    computed: {
        ...layoutComputed,

        /*
        |--------------------------------------------------------------------------
        | Check Super Admin
        |--------------------------------------------------------------------------
        |
        | HandleInertiaRequests partage déjà :
        |
        | auth.user.roles
        |
        | Exemple :
        | ['Super-admin']
        |
        */

        isSuperAdmin() {
            const roles =
                this.$page?.props?.auth?.user?.roles || [];

            return roles.includes("Super-admin");
        },
    },

    mounted() {
        // document
        //     .querySelector("html")
        //     .setAttribute("dir", "rtl");
    },
};
</script>

<template>
    <div>
        <!-- ========================================================= -->
        <!-- VERTICAL LAYOUT                                           -->
        <!-- ========================================================= -->

        <Vertical
            v-if="
                layoutType === 'vertical' ||
                layoutType === 'semibox'
            "
            :layout="layoutType"
        >
            <slot />
        </Vertical>

        <!-- ========================================================= -->
        <!-- HORIZONTAL LAYOUT                                         -->
        <!-- ========================================================= -->

        <Horizontal
            v-if="layoutType === 'horizontal'"
            :layout="layoutType"
        >
            <slot />
        </Horizontal>

        <!-- ========================================================= -->
        <!-- TWO COLUMNS LAYOUT                                        -->
        <!-- ========================================================= -->

        <TwoColumns
            v-if="layoutType === 'twocolumn'"
            :layout="layoutType"
        >
            <slot />
        </TwoColumns>

        <!-- ========================================================= -->
        <!-- SUPER ADMIN PENDING REMINDER                              -->
        <!-- ========================================================= -->
        <!--
            Le composant n'est chargé que pour le rôle Super-admin.

            Il fonctionne quel que soit le layout :
            - vertical
            - semibox
            - horizontal
            - twocolumn
        -->

        <SuperAdminPendingReminder
            v-if="isSuperAdmin"
        />
        <HomeArrival />
        <ImmersiveHint />
        <CustomCursor />
    </div>
</template>
