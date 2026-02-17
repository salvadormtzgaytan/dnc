import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: false,
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/**/*.blade.php',
        './storage/framework/views/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/views/vendor/filament/**/*.blade.php',
        './app/Filament/Widgets/**/*.{php,blade.php}',
        './app/Providers/Filament/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Comex', ...defaultTheme.fontFamily.sans],
                condensed: ['Comex Condensed', ...defaultTheme.fontFamily.sans],
                ultraCondensed: ['Comex Ultra Condensed', ...defaultTheme.fontFamily.sans],
                logos: ['Comex Logos', ...defaultTheme.fontFamily.sans],
            },
            fontWeight: {
                light: 300,
                normal: 500,
                medium: 500,
                bold: 700,
                black: 900,
            },
            colors: {
                'blue-1': '#0063a7',
                'blue-2': '#0085b7',
                'gris-1': '#808080',
                azulejo: '#0063a7',
                mallorca: '#f9c2ad',
                orca: '#b1cad2',
            },
            borderWidth: {
                10: '10px',
            },
            screens: {
                xl: '1200px',
                xxl: '1400px',
            },
        },
    },
    plugins: [forms, daisyui],
    daisyui: {
        themes: ['corporate'],
        darkTheme: false,
        base: true,
        styled: true,
        utils: true,
        logs: false,
        safelist: [
            // Clases de badge
            'badge-error',
            'badge-warning',
            'badge-success',
            'badge-neutral',
            // Clases de texto
            'text-error',
            'text-warning',
            'text-success',
            // Patrones para clases dinámicas
            {
                pattern: /badge-(error|warning|success|neutral)/,
            },
            {
                pattern: /text-(error|warning|success)/,
            },
        ],
        themes: [
            {
                corporate: {
                    ...require('daisyui/src/theming/themes')['corporate'],
                    /* Colores base */
                    primary: '#00AEEF',
                    'primary-focus': '#0092CC',
                    'primary-content': '#ffffff',
                    secondary: '#EB8B23',
                    'secondary-focus': '#D07A1E',
                    'secondary-content': '#ffffff',
                    /* Semánticos */
                    accent: '#1E376C',
                    'accent-focus': '#172D5A',
                    'accent-content': '#ffffff',
                    neutral: '#A3AAAD',
                    'neutral-focus': '#8A9093',
                    'neutral-content': '#111827',
                    /* Fondos y texto */
                    'base-100': '#ffffff',
                    'base-content': '#1F2937',
                    /* Mensajes */
                    info: '#58C1E9',
                    success: '#00877E',
                    warning: '#EB8B23',
                    error: '#DB2A2D',
                    /* Bordes redondeados */
                    '--rounded-box': '1.3rem',
                    '--rounded-btn': '0.5rem',
                    '--rounded-badge': '1.9rem',
                },
            },
        ],
    },
}
