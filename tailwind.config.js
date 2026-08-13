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
        extend: {},
    },
    plugins: [],
};