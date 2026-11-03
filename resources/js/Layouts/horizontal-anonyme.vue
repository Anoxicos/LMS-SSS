<script>
import NavBar from "@/Components/nav-bar-anonyme.vue";
import Footer from "@/Components/footer.vue";
import BackToTop from "@/Components/BackToTop.vue";

export default {
    components: {
        NavBar,
        Footer,
        BackToTop,
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
        <BackToTop />
    </div>
</template>

<style scoped>
.public-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #fff;
    margin: 0;
    padding: 0;
    position: relative;
}

.public-main {
    flex: 1;
    min-height: 0;
    margin: 0;
    padding: 0;
}
</style>
