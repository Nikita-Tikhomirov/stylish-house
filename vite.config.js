import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/main.css',
                'resources/css/front-components.css',
                'resources/css/category.css',
                'resources/css/index.css',
                'resources/css/prod.css',
                'resources/css/swiper.css',
                'resources/css/cart.css',
                'resources/css/user.css',
                'resources/css/checkout.css',
                'resources/css/login.css',
                'resources/css/admin/bootstrap.min.css',
                'resources/css/admin/style-1.css',
                'resources/css/admin/style.css',
                'resources/css/admin/fontawesome-all.css',
                'resources/css/admin/chartist.css',
                'resources/css/admin/morris.css',
                'resources/css/admin/materialdesignicons.min.css',
                'resources/css/admin/c3.css',
                'resources/css/admin/flag-icon.min.css',
                'resources/js/swiper.js',
                'resources/js/main.js',
                'resources/js/shop.js',
                'resources/js/admin/jquery.js',
                'resources/js/admin/bootstrap.js',
                'resources/js/admin/slimscroll.js',
                'resources/js/admin/main-js.js',
                'resources/js/admin/chartist.js',
                'resources/js/admin/sparkline.js',
                'resources/js/admin/raphael.js',
                'resources/js/admin/morris.js',
                'resources/js/admin/c3.js',
                'resources/js/admin/d3.js',
                'resources/js/admin/C3chartjs.js',
                'resources/js/admin/dashboard-ecommerce.js',
                'resources/js/admin/js.js',
                'node_modules/fabric/dist/fabric.mjs'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
        extensions: ['.js', '.mjs', '.json'], // Добавьте .mjs если не работает
    },
});
