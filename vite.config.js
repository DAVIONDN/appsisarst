import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * SISARST - compilacion de los recursos de la capa Vista.
 *
 * Las hojas de estilo y el JavaScript viven en app/Vista/recursos, junto a
 * las plantillas Blade, para que toda la capa Vista quede en un solo lugar.
 *
 * Bootstrap 5.3, Bootstrap Icons, SweetAlert2, Flatpickr y Chart.js se
 * empaquetan localmente: el sistema no depende de CDN externos, lo que
 * permite operarlo en la intranet de la Red de Salud (RNF-13, RNF-15).
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'app/Vista/recursos/scss/app.scss',
                'app/Vista/recursos/js/app.js',
            ],
            refresh: [
                'app/Vista/**',
                'routes/**',
            ],
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
