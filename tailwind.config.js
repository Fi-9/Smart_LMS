import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                success: '#4B8B3B',
                warning: '#EAB308',
                danger: '#DC2626',
                /* Semantic tokens via CSS variables */
                foreground: 'rgb(var(--app-foreground) / <alpha-value>)',
                muted: 'rgb(var(--app-muted) / <alpha-value>)',
                border: 'rgb(var(--app-border) / <alpha-value>)',
                'border-strong': 'rgb(var(--app-border-strong) / <alpha-value>)',
                surface: 'rgb(var(--app-surface) / <alpha-value>)',
                background: 'rgb(var(--app-background) / <alpha-value>)',
                /* Sidebar tokens */
                sidebar: 'rgb(var(--sidebar-bg) / <alpha-value>)',
                'sidebar-foreground': 'rgb(var(--sidebar-foreground) / <alpha-value>)',
                'sidebar-muted': 'rgb(var(--sidebar-muted) / <alpha-value>)',
                'sidebar-border': 'rgb(var(--sidebar-border) / <alpha-value>)',
                'sidebar-hover': 'rgb(var(--sidebar-hover) / <alpha-value>)',
            },
        },
    },
    plugins: [],
};
