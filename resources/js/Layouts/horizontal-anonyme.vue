<script>
import NavBar from "@/Components/nav-bar-anonyme.vue";
import Footer from "@/Components/footer.vue";

export default {
    components: {
        NavBar,
        Footer,
    },
};
</script>

<template>
    <div id="layout-wrapper" class="public-layout">
        <NavBar />

        <main class="public-main">
            <slot />
        </main>

        <Footer />
    </div>
</template>

<style scoped>
.public-layout {
    min-height: 100vh;
    background: #fff;
}

.public-main {
    min-height: calc(100vh - 90px);
}
</style>