import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ERGE MYFINANCE brand green
                brand: {
                    50: '#f0fdf6',
                    100: '#dcf9ea',
                    200: '#b9f0d5',
                    300: '#82e2b6',
                    400: '#46cb92',
                    500: '#1fb374',
                    600: '#12905c',
                    700: '#0f734c',
                    800: '#0f5b3f',
                    900: '#0d4b35',
                    950: '#052b1e',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.04)',
                'card-hover': '0 2px 4px -1px rgb(15 23 42 / 0.05), 0 8px 24px -6px rgb(15 23 42 / 0.08)',
                topbar: '0 1px 2px 0 rgb(15 23 42 / 0.03)',
            },
            borderRadius: {
                card: '0.875rem',
                panel: '1.125rem',
            },
        },
    },

    plugins: [forms],
};