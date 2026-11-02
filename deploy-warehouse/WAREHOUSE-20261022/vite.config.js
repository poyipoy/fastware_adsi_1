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
                'resources/js/warehouse/dashboard.js',
                'resources/js/warehouse/transaction-form.js',
                'resources/js/warehouse/stock-in.js',
                'resources/css/warehouse/transaction-form.css',
                'resources/css/warehouse/dashboard.css',
                'resources/css/warehouse/foundation.css',
                'resources/css/warehouse/management.css',
                'resources/css/warehouse/reporting.css',
                'resources/css/warehouse/stock-in.css',
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
