<?php

namespace App\Support;

/**
 * Sumber tunggal untuk tampilan kategori: palet warna dan set ikon.
 *
 * Nilai yang disimpan di tabel `categories` hanya berupa *key* ringkas
 * (mis. "green", "cart"), bukan kelas Tailwind atau path SVG. Semua
 * pemetaan key -> tampilan ada di sini, sehingga:
 *
 * - tidak ada kelas warna yang bocor ke dalam data,
 * - mengganti palet cukup di satu tempat,
 * - kolom di DB tetap kecil & stabil.
 *
 * CATATAN PENTING (Tailwind): kelas warna di bawah HARUS ikut di-build.
 * Tailwind v3 memurge kelas yang tidak ditemukan di file `content`, dan
 * folder app tidak ada di sana secara bawaan. Karena itu folder app
 * sengaja ditambahkan ke `content` pada tailwind.config.js. Kalau glob
 * itu dihapus, kartu kategori akan kehilangan semua warnanya.
 */
class CategoryStyle
{
    /**
     * Palet warna. Setiap nilai adalah key yang disimpan di kolom
     * `categories.color`.
     *
     * @var array<string, string>
     */
    public const COLORS = [
        'green' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
        'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        'cyan' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400',
        'neutral' => 'bg-neutral-100 text-neutral-500 dark:bg-[#262626] dark:text-neutral-300',
    ];

    /**
     * Warna netral — dipakai saat kategori belum punya warna (mis. baris
     * lama sebelum migration, atau fallback tanpa seeder).
     */
    public const DEFAULT_COLOR = 'neutral';

    /**
     * Set ikon. Setiap nilai adalah key yang disimpan di kolom
     * `categories.icon`, dipetakan ke path SVG (isi `d` attribute).
     *
     * @var array<string, string>
     */
    public const ICONS = [
        // bawaan — income
        'wallet' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'gift' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'trend' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-0l-8 8-4-4-6 6"/>',
        'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4L12 3z"/>',
        // bawaan — expense
        'utensils' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
        'car' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.707.293V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
        'bolt' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'cart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'ticket' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>',
        'heart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
        'book' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        // umum / fallback
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.569 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>',
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>',
        'phone' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>',
        'paw' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 11.5c-1.5 0-2.5.5-3 1.5-.5 1 .5 2 1.5 2.5.8.4 1.5 1 1.5 2 0 .8-.7 1.5-1.5 1.5H7c-1.7 0-3-1.3-3-3 0-1.9 1.3-3.4 3-3.8M7.5 6.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm9 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-2.5 2a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-7 2a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>',
        'plane' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5V15m-6 0l3-9a2.25 2.25 0 012.19-1.8h1.62a2.25 2.25 0 012.19 1.8l3 9m-12 0H4.5a1.5 1.5 0 01-1.32-.75L2 11.25l1.5-1.5a1.5 1.5 0 011.32-.75H18m-12 0h12m-6-6v6m0-6l-2.5-2.5"/>',
        'coffee' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25a2.25 2.25 0 00-2.25 2.25v15a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-15a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>',
        'gym' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.5 6.5v11m11-11v11m-14 0v3.5A1.5 1.5 0 005 11.5h-1m17 0h-1a1.5 1.5 0 00-1.5 1.5V18M3 10.5h18M6.5 7.5H5A2 2 0 003 9.5v1M20.5 7.5H19a2 2 0 012-2v1"/>',
        'giftbox' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 006.75 7.5H4.5m2.25 0H21m-9 12.75h-2.25m0-12.75v12.75m6-12.75v12.75m-4.5-15v15m6-15v15"/>',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
        'piggy' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ];

    /**
     * Ikon netral — dipakai saat kategori belum punya ikon.
     */
    public const DEFAULT_ICON = 'tag';

    /**
     * Warna & ikon baku untuk kategori bawaan aplikasi.
     *
     * Dipakai migration (menambal baris yang sudah ada) dan CategorySeeder,
     * sehingga tampilan bawaan tidak berubah walau tabelnya kosong.
     *
     * @var array<string, array{color: string, icon: string}>
     */
    public const DEFAULTS = [
        // pemasukan
        'Gaji' => ['color' => 'green', 'icon' => 'wallet'],
        'Bonus' => ['color' => 'green', 'icon' => 'gift'],
        'Bisnis' => ['color' => 'green', 'icon' => 'briefcase'],
        'Investasi' => ['color' => 'violet', 'icon' => 'trend'],
        'Hadiah' => ['color' => 'green', 'icon' => 'sparkles'],
        // pengeluaran
        'Makanan & Minuman' => ['color' => 'orange', 'icon' => 'utensils'],
        'Transportasi' => ['color' => 'blue', 'icon' => 'car'],
        'Tagihan & Utilitas' => ['color' => 'amber', 'icon' => 'bolt'],
        'Belanja' => ['color' => 'violet', 'icon' => 'cart'],
        'Hiburan' => ['color' => 'violet', 'icon' => 'ticket'],
        'Kesehatan' => ['color' => 'amber', 'icon' => 'heart'],
        'Pendidikan' => ['color' => 'blue', 'icon' => 'book'],
        'Keluarga' => ['color' => 'blue', 'icon' => 'users'],
    ];

    /**
     * Urutan palet offered di picker. Kunci DEFAULTS sengaja memakai
     * warna yang ada di sini supaya bawaan & custom tidak memakai dua
     * bahasa visual yang berbeda.
     *
     * @return list<string>
     */
    public static function colorKeys(): array
    {
        return array_keys(self::COLORS);
    }

    /**
     * Ikon yang ditawarkan di picker: semua bawaan lebih dulu, lalu
     * umum/fallback.
     *
     * @return list<string>
     */
    public static function iconKeys(): array
    {
        return array_keys(self::ICONS);
    }

    /**
     * Kelas Tailwind untuk sebuah key warna. Key yang tidak dikenal
     * jatuh ke netral supaya view tidak pernah menghasilkan kelas
     * yang tidak ada di CSS.
     */
    public static function colorClasses(?string $color): string
    {
        return self::COLORS[$color] ?? self::COLORS[self::DEFAULT_COLOR];
    }

    /**
     * Path SVG untuk sebuah key ikon, dalam bentuk markup utuh
     * (`<path .../>`) — untuk `{!! !!}` di Blade.
     */
    public static function iconPath(?string $icon): string
    {
        return self::ICONS[$icon] ?? self::ICONS[self::DEFAULT_ICON];
    }

    /**
     * Isi atribut `d` untuk sebuah key ikon, digabung jadi satu string.
     *
     * Dipakai Alpine untuk preview di modal dan untuk ikon di picker.
     * Bentuknya harus `d` polos, BUKAN markup: mengikat markup ke
     * atribut `d` akan menghasilkan `d="<path ..."` yang tidak valid
     * dan ikonnya tidak akan tergambar sama sekali.
     *
     * Beberapa ikon punya lebih dari satu subpath; `d` memang boleh
     * memuat banyak subpath dalam satu atribut, jadi digabung dengan
     * spasi aman untuk ikon stroke ini.
     */
    public static function iconD(?string $icon): string
    {
        preg_match_all('/\sd="([^"]+)"/', self::iconPath($icon), $matches);

        return implode(' ', $matches[1]);
    }

    /**
     * Versi ICONS untuk dikirim ke browser: key -> atribut `d` polos.
     * Diturunkan dari ICONS, jadi tidak ada sumber kedua yang bisa
     * berbeda sendiri.
     *
     * @return array<string, string>
     */
    public static function iconDSet(): array
    {
        $set = [];

        foreach (self::ICONS as $key => $markup) {
            preg_match_all('/\sd="([^"]+)"/', $markup, $matches);
            $set[$key] = implode(' ', $matches[1]);
        }

        return $set;
    }

    /**
     * Warna & ikon baku untuk nama kategori, atau null bila bukan
     * kategori bawaan.
     *
     * @return array{color: string, icon: string}|null
     */
    public static function defaultsFor(string $name): ?array
    {
        return self::DEFAULTS[$name] ?? null;
    }

    /**
     * Warna & ikon untuk kategori custom yang tidak memilih apa pun.
     *
     * Dipilih dari nama supaya kategori dengan nama sama selalu mendapat
     * warna yang sama (user tidak perlu Remember preferensinya), dan
     * memakai palet penuh (tanpa netral) supaya tidak terlihat seperti
     * "kategori tanpa identitas".
     *
     * @return array{color: string, icon: string}
     */
    public static function fallbackFor(string $name): array
    {
        $colors = self::colorKeys();
        $colors = array_values(array_filter($colors, fn ($key) => $key !== self::DEFAULT_COLOR));

        $hash = crc32(mb_strtolower(trim($name)));

        return [
            'color' => $colors[$hash % count($colors)],
            'icon' => self::iconKeys()[$hash % count(self::iconKeys())],
        ];
    }

    /**
     * Buang key yang tidak dikenal supaya nilai dari form tidak bisa
     * menyuntikkan apa pun ke luar palet.
     *
     * @return array{color: string, icon: string}
     */
    public static function sanitize(?string $color, ?string $icon, string $name): array
    {
        return [
            'color' => isset(self::COLORS[$color]) ? $color : self::fallbackFor($name)['color'],
            'icon' => isset(self::ICONS[$icon]) ? $icon : self::fallbackFor($name)['icon'],
        ];
    }
}
