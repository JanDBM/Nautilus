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
                purple: {
                    400: '#9333EA',
                    500: '#7C3AED',
                    600: '#6B46C1',
                    700: '#5B21B6',
                    800: '#4C1D95',
                },
                gray: {
                    700: '#374151',
                    800: '#1F2937',
                    900: '#111827',
                }
            }
        },
    },

    plugins: [forms],
};
