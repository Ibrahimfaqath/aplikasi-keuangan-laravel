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
                // Palet navy untuk mode gelap (navy + biru + putih)
                navy: {
                    50: '#eef1f9',
                    100: '#d8e0f0',
                    200: '#b3c3e3',
                    300: '#7f9cd2', // aksen terang (hover)
                    400: '#3b63b8', // aksen utama
                    500: '#2b4d96', // aksen dalam
                    600: '#001F54', // biru tua utama (brand)
                    700: '#152649', // permukaan hover
                    800: '#101d3c', // permukaan kartu
                    900: '#0c1630', // permukaan dasar
                    950: '#0A1128', // latar halaman
                },
            },
        },
    },
    plugins: [],
};
