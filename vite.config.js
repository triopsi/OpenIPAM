import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css'
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: process.env.VITE_PORT ? { clientPort: Number(process.env.VITE_PORT) } : undefined,
        cors: { origin: [/^http:\/\/localhost(?::\d+)?$/] },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
