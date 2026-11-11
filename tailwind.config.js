import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#203B32',
                    deep: '#172B25',
                    50: '#F1F5F2',
                    100: '#DCE6DF',
                    200: '#B9CDC0',
                    300: '#8FAF9C',
                    400: '#5E8570',
                    500: '#3F6653',
                    600: '#2E5142',
                    700: '#203B32',
                    800: '#1A302A',
                    900: '#172B25',
                },
                ivory: '#F6F2E9',
                champagne: '#C4A46B',
                'champagne-deep': '#A98A55',
                ink: '#343832',
                sage: '#879889',
                stone: '#E5DED1',
                success: { DEFAULT: '#2F7D57', soft: '#E7F3EC' },
                warning: { DEFAULT: '#A9761B', soft: '#FBF1DD' },
                danger: { DEFAULT: '#A93B34', soft: '#FAEAE8' },
                info: { DEFAULT: '#2F5F7D', soft: '#E8F1F7' },
                'dark-bg': '#151C19',
                'dark-surface': '#202925',
                'dark-elevated': '#29342E',
                'dark-ink': '#F3F0E8',
                'dark-sage': '#B8C1B9',
                'dark-border': '#3A463E',
                'dark-champagne': '#D0B47E',
                'dark-success': '#86B99A',
                'dark-warning': '#E2BE79',
                'dark-danger': '#E39A95',
            },
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['"Playfair Display"', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                card: '0 1px 2px 0 rgba(23, 43, 37, 0.06)',
                lift: '0 10px 30px -12px rgba(23, 43, 37, 0.25)',
            },
            borderRadius: {
                xl: '0.875rem',
            },
        },
    },

    plugins: [forms],
};
