import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import basicSsl from '@vitejs/plugin-basic-ssl';

export default defineConfig(({ mode }) => {
    const env = { ...process.env, ...loadEnv(mode, process.cwd(), '') };
    const devHost = env.VITE_DEV_SERVER_HOST || 'localhost';
    const devPort = Number(env.VITE_DEV_SERVER_PORT) || 5173;
    const useHttps = env.VITE_DEV_SERVER_HTTPS !== 'false';

    return {
        server: {
            host: devHost,
            port: devPort,
            https: useHttps ? true : false,
            strictPort: true,
            hmr: {
                host: devHost,
                port: devPort,
                protocol: useHttps ? 'wss' : 'ws',
            },
            origin: `${useHttps ? 'https' : 'http'}://${devHost}:${devPort}`,
        },
        plugins: [
            useHttps ? basicSsl() : undefined,
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ].filter(Boolean),
    };
});
