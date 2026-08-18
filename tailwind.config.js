/** @type {import('tailwindcss').Config} */
export default {
    // WAJIB: Aktifkan mode dark berbasis class
    darkMode: 'class',

    // Daftarkan semua file tempat kamu menulis class Tailwind
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Palet emas untuk mode gelap (hitam + emas + putih)
                gold: {
                    50: '#fbf8ec',
                    100: '#f5ecc7',
                    200: '#ecd99a',
                    300: '#e2c468',
                    400: '#d4af37', // emas klasik
                    500: '#c19b2e',
                    600: '#a37f26',
                    700: '#82621f',
                    800: '#654c19',
                    900: '#4a3812',
                },
            },
        },
    },
    plugins: [],
};
