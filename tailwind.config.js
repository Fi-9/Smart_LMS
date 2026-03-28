import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#4B8B3B',
                    50: '#F0F7EE',
                    100: '#E3F2E1',
                    200: '#C8E5C2',
                    300: '#A3C6A0',
                    400: '#7BAF74',
                    500: '#4B8B3B',
                    600: '#3D7230',
                    700: '#2F5925',
                    800: '#21401A',
                    900: '#13260F',
                    soft: '#B7D9B1',
                },
                background: '#F7FAF6',
                surface: '#FFFFFF',
                border: '#E5E7EB',
                success: '#4B8B3B',
                warning: '#EAB308',
                danger: '#DC2626',
            },
        },
    },
    plugins: [],
};
