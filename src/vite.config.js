import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/reset.css',
                'resources/css/app.css',
                'resources/css/list.css',
                'resources/css/show.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
