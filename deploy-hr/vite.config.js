import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Knowledge Management — Jangka Menengah
                'resources/css/km/foundation.css',
                'resources/js/km/dashboard.js',
                'resources/css/km/dashboard.css',
                'resources/js/km/authoring.js',
                'resources/js/km/approval.js',
                'resources/js/km/shell.js',
            ],
            refresh: true,
        }),
    ],
    optimizeDeps: {
        include: ['pdfjs-dist'],
    },
    build: {
        rollupOptions: {
            output: {
                // Pastikan worker pdfjs dibundel lokal, bukan di-resolve dari CDN
                manualChunks: {
                    pdfjs: ['pdfjs-dist'],
                },
            },
        },
    },
});
