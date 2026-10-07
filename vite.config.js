import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/clientes.css', 'resources/css/vendas.css', 'resources/css/configuracoes.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
