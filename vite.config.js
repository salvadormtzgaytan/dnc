import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    css: {
        devSourcemap: true,     // <— habilita source maps para CSS
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/gauge-chart.js',
                'resources/js/category-chart.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@img': '/public/img', // Alias para imágenes en public/img/
        },
    },
});