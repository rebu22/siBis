import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

/**
 * Frontend terpisah dari Laravel:
 * - root Vite = frontend/, sumber di src/
 * - hasil build & file `hot` ditulis ke backend/public agar @vite membacanya
 * - alias `@` diarahkan ke src/ (bukan default plugin laravel)
 */
export default defineConfig({
    // Baca APP_URL/VITE_* dari backend/.env supaya perilaku dev sama seperti sebelum split.
    envDir: '../backend',
    plugins: [
        laravel({
            input: 'src/app.js',
            publicDirectory: '../backend/public',
            refresh: ['../backend/resources/views/**/*.blade.php'],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/src',
        },
    },
    build: {
        // publicDirectory berada di luar root Vite (../backend/public),
        // jadi harus diminta eksplisit agar aset build lama tidak menumpuk.
        emptyOutDir: true,
    },
});
