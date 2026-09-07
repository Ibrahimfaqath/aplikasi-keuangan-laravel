/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode berbasis class, palette monochrome (spec: #0A0A0A / #171717 / #262626)
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
                // Alias semantik monochrome agar konsisten di seluruh UI.
                // Light: bg #FAFAFA, card #FFFFFF, text #111111
                // Dark: bg #0A0A0A, card #171717, secondary #262626, border #333333
                ink: {
                    DEFAULT: '#111111',
                    soft: '#171717',
                    muted: '#262626',
                },
            },
            boxShadow: {
                // Shadow sangat subtle — prioritaskan border + whitespace
                card: '0 1px 2px 0 rgb(0 0 0 / 0.04)',
            },
        },
    },
    plugins: [],
};

/**
 * DOMPETKU DESIGN TOKENS — neutral foundation + restrained semantic colors.
 * (Dokumentasi terpusat; memakai palet bawaan Tailwind yang hex-nya persis sama.)
 *
 * NEUTRAL (~85% UI): bg #FAFAFA / #F5F5F5, card #FFFFFF, text #171717 / #525252 /
 *   #737373 / #A3A3A3, border #E5E5E5 / #D4D4D4.
 *   Dark: bg #0A0A0A, card #171717, secondary #262626, border #333333,
 *   text #FAFAFA / #A3A3A3 / #737373.
 *
 * SEMANTIC (~15% UI, hanya untuk meaning — icon/badge/percentage/chart):
 *   income      green-600  #16A34A | soft bg green-50  #F0FDF4 | border green-200  #BBF7D0
 *   expense     red-600    #DC2626 | soft bg red-50    #FEF2F2 | border red-200    #FECACA
 *   savings     blue-600   #2563EB | soft bg blue-50   #EFF6FF | border blue-200   #BFDBFE
 *   investment  violet-600 #7C3AED | soft bg violet-50 #F5F3FF
 *   warning     amber-600  #D97706 | soft bg amber-50  #FFFBEB
 *   Dark mode: pakai {color}-400 untuk teks/icon + {color}-500/10 untuk soft bg
 *   (lebih soft di atas background gelap).
 *
 * ATURAN: card tetap bg-white + border; JANGAN warnai seluruh card.
 * Amount/nominal selalu neutral-900 (hitam); warna hanya accent.
 */
