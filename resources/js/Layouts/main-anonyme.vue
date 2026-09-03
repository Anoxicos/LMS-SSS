<script>
import { layoutComputed } from "@/state/helpers";


import Horizontal from "./horizontal-anonyme.vue";


export default {
    components: {
        Horizontal
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


    </div>
</template>
