import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/estoque.css', 'resources/css/campeonatos.css', 'resources/css/clientes.css', 'resources/css/vendas.css', 'resources/css/configuracoes.css', 'resources/css/login.css', 'resources/js/app.js', 'resources/js/login.js'],
            refresh: true,
        }),
    ],
});
