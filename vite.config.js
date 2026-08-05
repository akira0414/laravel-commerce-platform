
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const pages = [
    'account',
    'cart',
    'checkout',
    'dashboard',
    'home',
    'login',
    'payment-simulator',
    'storefront',
];

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/common.scss',
                'resources/js/script/common.js',
                ...pages.map(page => `resources/scss/pages/${page}.scss`),
                ...pages.map(page => `resources/js/script/pages/${page}.js`),
            ],
            refresh: true,
        }),
    ],
});


