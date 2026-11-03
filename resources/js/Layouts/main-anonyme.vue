<script>
import { layoutComputed } from "@/state/helpers";


import Horizontal from "./horizontal-anonyme.vue";
import HomeArrival from "@/Components/HomeArrival.vue";


export default {
    components: {
        Horizontal,
        HomeArrival,
    },
    data() {
        return {};
    },
    computed: {
        ...layoutComputed,
    },
    mounted() {
        // document.querySelector("html").setAttribute('dir', 'rtl');
        document.documentElement.setAttribute("data-layout", "horizontal");
    }
};
</script>

<template>
    <div>

        <Horizontal  layout="horizontal">
            <slot />
        </Horizontal>
        <HomeArrival />


    </div>
</template>
