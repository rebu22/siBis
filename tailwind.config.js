import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Token dari prompt.txt, design system "Corporate Trust".
 *
 * Palet-nya kebetulan persis sama dengan palette bawaan Tailwind, jadi token
 * dipetakan langsung ke kelas bawaan (tanpa warna kustom yang bisa menyimpang):
 *   Background #F8FAFC = slate-50     Primary  #4F46E5 = indigo-600
 *   Surface    #FFFFFF = white        Secondary #7C3AED = violet-600
 *   Text main  #0F172A = slate-900    Success  #10B981 = emerald-500
 *   Text muted #64748B = slate-500    Border   #E2E8F0 = slate-200
 * Ikon/penanda boleh emerald-500, tapi teks hijau pakai emerald-700 (AA, lihat ContrastTest).
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Prompt: Plus Jakarta Sans. Self-hosted lewat @fontsource-variable.
                sans: ['Plus Jakarta Sans Variable', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },

            // Skala Major Third (1.250) dari 16px, dibulatkan ke angka yang sudah lazim.
            fontSize: {
                caption: ['12px', { lineHeight: '1.4', letterSpacing: '0' }],
                'body-sm': ['14px', { lineHeight: '1.65', letterSpacing: '0' }],
                body: ['16px', { lineHeight: '1.7', letterSpacing: '0' }],
                'sub-sm': ['20px', { lineHeight: '1.4', letterSpacing: '-0.2px' }],
                sub: ['24px', { lineHeight: '1.3', letterSpacing: '-0.48px' }],
                'h-sm': ['30px', { lineHeight: '1.2', letterSpacing: '-0.6px' }],
                'h-md': ['36px', { lineHeight: '1.15', letterSpacing: '-0.72px' }],
                h: ['48px', { lineHeight: '1.1', letterSpacing: '-0.96px' }],
                'h-lg': ['60px', { lineHeight: '1.1', letterSpacing: '-1.2px' }],
                display: ['72px', { lineHeight: '1.05', letterSpacing: '-1.44px' }],
            },

            letterSpacing: {
                eyebrow: '0.1em',
            },

            // Prompt: kartu rounded-xl (12px), input rounded-lg (8px).
            borderRadius: {
                card: '12px',
                surface: '16px',
                panel: '24px',
            },

            /* Prompt: bayangan berwarna (tinted indigo), bukan abu netral. */
            boxShadow: {
                soft: '0 4px 20px -2px rgba(79, 70, 229, 0.10)',
                'soft-hover': '0 10px 25px -5px rgba(79, 70, 229, 0.15), 0 8px 10px -6px rgba(79, 70, 229, 0.10)',
                button: '0 4px 14px 0 rgba(79, 70, 229, 0.30)',
                'button-hover': '0 8px 24px 0 rgba(79, 70, 229, 0.35)',
                glow: '0 0 20px rgba(79, 70, 229, 0.50)',
            },
        },
    },

    plugins: [forms],
};
