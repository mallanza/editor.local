import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const devHost = process.env.VITE_DEV_SERVER_HOST || 'editor.local';
const useHttps = process.env.VITE_DEV_SERVER_HTTPS !== 'false';

export default defineConfig({
    server: {
        host: devHost,
        https: useHttps,
        hmr: {
            host: devHost,
            protocol: useHttps ? 'https' : 'http',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
