import './bootstrap';
// import '../scss/config/galaxy/app.scss';
import '../scss/config/corporate/app.scss';
import '@vueform/slider/themes/default.css';
import '../scss/mermaid.min.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import BootstrapVueNext from 'bootstrap-vue-next';
import vClickOutside from 'click-outside-vue3';
import VueApexCharts from 'vue3-apexcharts';
import VueFeather from 'vue-feather';
import VueTheMask from 'vue-the-mask';

import AOS from 'aos';
import 'aos/dist/aos.css';

import store from './state/store';
import i18n from './i18n';
import { applyLayoutConfig } from './state/modules/layout';

AOS.init({
    easing: 'ease-out',
    duration: 650,
    disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
});

createInertiaApp({
    title: title => title ? `${title} - LMS` : 'LMS',

    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Appliquer la configuration globale du layout au démarrage
        applyLayoutConfig(store.state.layout);

        return app
            .use(plugin)
            .use(store)
            .use(i18n)
            .use(ZiggyVue)
            .use(BootstrapVueNext)
            .use(VueApexCharts)
            .use(VueTheMask)
            .use(vClickOutside)
            .component(VueFeather.type, VueFeather)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
