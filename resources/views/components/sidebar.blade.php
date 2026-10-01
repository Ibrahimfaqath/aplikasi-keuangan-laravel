{{-- ============================================================
  APP SHELL SIDEBAR — dompetku

  Komponen daisyUI yang dipakai: `menu` (+ `menu-title`), `badge`,
  `avatar placeholder`, `btn`, dan `drawer-overlay`.

  KENAPA TIDAK `drawer`?  daisyUI drawer dirancang untuk satu hal: sidebar
  yang kita BUKA/TUTUP. Yang ada di aplikasi ini beda arah: sidebar
  selalu terlihat di desktop dan bisa di-COLLAPSE dari 256px ke 76px, dengan
  state-nya disimpan di localStorage (`dompetku_sb`) lalu diwujudkan sebagai
  kelas `sb-collapsed` pada <html>. Itu sumbu lain yang tidak punya padanan di
  daisyUI. Memaksakan `drawer` berarti: (1) membungkus isi tiap halaman dengan
  `.drawer-content` yang artinya menyentuh 14 halaman, dan (2) kehilangan
  mode collapsed sama sekali. Jadi shell fixed + offset lewat
  `.app-shell-content` (app.css) tetap milik sendiri, dan yang daisyUI-kan
  adalah isi menunya. Class `sb-*` yang dirujuk app.css harus tetap ada.
  ============================================================ --}}
@props(['title' => 'dompetku', 'back' => null, 'minimal' => false])
<script>
    try {
        if (localStorage.getItem('dompetku_sb') === 'collapsed') {
            document.documentElement.classList.add('sb-collapsed');
        }
    } catch (e) {}
    function toggleSidebarCollapse(btn) {
        try {
            var collapsed = document.documentElement.classList.toggle('sb-collapsed');
            localStorage.setItem('dompetku_sb', collapsed ? 'collapsed' : 'expanded');
            if (btn) btn.setAttribute('aria-expanded', String(!collapsed));
        } catch (e) {}
    }
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var collapsed = document.documentElement.classList.contains('sb-collapsed');
            document.querySelectorAll('[data-sb-collapse]').forEach(function (b) {
                b.setAttribute('aria-expanded', String(!collapsed));
            });
        } catch (e) {}
    });
</script>

@php
    // State aktif memakai kelas `active` milik daisyUI v4 (BUKAN `menu-active`,
    // itu fitur v5). Label grup memakai `menu-title`.
    $sbIconSvg = 'class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

    // Badge "Demo": `alert-*`/modifier berwarna di komponen daisyUI dicampur
    // dengan base-100 sehingga kontrasnya tidak terduga di mode gelap, jadi
    // warnanya ditulis eksplisit lewat token (pola yang sama seperti
    // components/flash.blade.php).
    $demoBadge = 'badge badge-sm gap-1 border-warning/30 bg-warning/10 text-warning';

    // Berapa transaksi yang menunggu dipulihkan. Tanpa angka ini, user tidak
    // tahu ada yang nyangkut di Sampah sampai ia membuka menunya sendiri.
    // Satu COUNT() yang dilayani index (user_id, deleted_at) — murah, dan
    // nilainya benar terus (dipakai juga untuk menentukan tampil/tidaknya badge).
    $trashCount = Auth::check()
        ? \App\Models\Transaction::onlyTrashed()->where('user_id', Auth::id())->count()
        : 0;

    $sbGroups = [
    'UTAMA' => [
        [
            'label' => 'Dashboard',
            'href' => route('transactions.index'),
            'active' => request()->routeIs('transactions.index') || request()->routeIs('transactions.edit'),
            'icon' => '<path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>',
        ],
        [
            'label' => 'Tambah Transaksi',
            'href' => route('transactions.create'),
            'active' => request()->routeIs('transactions.create'),
            'icon' => '<path d="M12 4.5v15m7.5-7.5h-15"/>',
        ],
        [
            'label' => 'Import Transaksi',
            'href' => route('transactions.import'),
            'active' => request()->routeIs('transactions.import') || request()->routeIs('transactions.import-*'),
            'icon' => '<path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>',
        ],
        [
            'label' => 'Anggaran',
            'href' => route('budgets.index'),
            'active' => request()->routeIs('budgets.*'),
            // Sama dengan ikon kartu Anggaran di dashboard, biar kaitannya jelas.
            'icon' => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        ],
    ],
    'ANALISIS' => [
        [
            'label' => 'Asisten AI',
            'href' => route('ai.index'),
            'active' => request()->routeIs('ai.*'),
            'icon' => '<path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>',
        ],
        [
            'label' => 'Export Laporan',
            'action' => 'export',
            'active' => false,
            'icon' => '<path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>',
        ],
    ],
    'LAINNYA' => [
        [
            'label' => 'Kategori',
            'href' => route('categories.index'),
            'active' => request()->routeIs('categories.*'),
            'icon' => '<path d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path d="M6 6h.008v.008H6V6z"/>',
        ],
        [
            'label' => 'Riwayat Aktivitas',
            'href' => route('audit.index'),
            'active' => request()->routeIs('audit.*'),
            'icon' => '<path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
        [
            'label' => 'Sampah',
            'href' => route('transactions.trashed'),
            'active' => request()->routeIs('transactions.trashed'),
            'icon' => '<path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>',
            // 0 = tidak ada yang nyangkut, jadi menu dianggap "bersih" dan
            // badge-nya disembunyikan. Angka 0 yang ditampilkan cuma menambah
            // kebisingan visual di sidebar.
            'badge' => $trashCount > 0 ? $trashCount : null,
        ],
        [
            'label' => 'Profil & Pengaturan',
            'href' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
            'icon' => '<path d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>',
        ],
    ],
];

    // Satu daftar menu, dipakai dua kali: sidebar desktop dan drawer mobile.
    // $closer hanya diisi di versi mobile supaya klik menutup drawer-nya.
    $menuGroups = function (string $closer = '') use ($sbGroups, $sbIconSvg) {
        echo '<ul class="menu menu-sm w-full gap-0.5 p-0">';

        foreach ($sbGroups as $groupLabel => $items) {
            // `menu-title` untuk label grup. Class `sb-section-label` tetap
            // ditambahkan karena app.css memakainya untuk menyembunyikan label
            // saat sidebar dikecilkan.
            echo '<li class="sb-section-label menu-title px-3 pb-1 pt-2 text-[11px] uppercase tracking-wider text-base-content/60">'.e($groupLabel).'</li>';

            foreach ($items as $item) {
                $active = ! empty($item['active']) ? ' active' : '';
                $close = $closer !== '' ? $closer : '';

                if (($item['action'] ?? null) === 'export') {
                    echo '<li><button type="button"'
                        .' onclick="window.openExportModal ? openExportModal() : (window.location.href = \''.$closer.'\')"'
                        .' class="sb-link min-h-[44px] gap-3"'
                        .(! empty($item['badge']) ? '' : '').'>';
                    echo '<svg '.$sbIconSvg.'>'.$item['icon'].'</svg>';
                    echo '<span class="sb-label truncate">'.e($item['label']).'</span>';
                    echo '</button></li>';

                    continue;
                }

                echo '<li><a href="'.e($item['href']).'"'
                    .($active !== '' ? ' aria-current="page"' : '')
                    .($close !== '' ? ' @click="drawer = false"' : '')
                    // `sb-link` tetap dipakai agar aturan collapse di app.css
                    // (justify-content: center saat dikecilkan) ikut berlaku.
                    .' class="sb-link min-h-[44px] gap-3'.$active.'">';
                echo '<svg '.$sbIconSvg.'>'.$item['icon'].'</svg>';
                echo '<span class="sb-label truncate">'.e($item['label']).'</span>';

                if (! empty($item['badge'])) {
                    echo '<span class="sb-label badge badge-sm badge-neutral ml-auto shrink-0"'
                        .' aria-label="'.$item['badge'].' transaksi menunggu dipulihkan">'
                        .e($item['badge']).'</span>';
                }

                echo '</a></li>';
            }
        }

        echo '</ul>';
    };
@endphp

<div x-data="{ drawer: false }"
     @keydown.escape.window="drawer = false"
     x-effect="document.body.style.overflow = drawer ? 'hidden' : ''">

    <!-- ============ DESKTOP SIDEBAR (fixed, collapsible) ============ -->
    <aside class="sb-aside hidden lg:flex fixed inset-y-0 left-0 z-40 w-[256px] flex-col bg-base-100 border-r border-base-300 transition-all duration-200"
           aria-label="Navigasi utama dompetku">

        <!-- Brand FIXED (tidak ikut scroll) -->
        <div class="sb-brand-row flex items-center gap-2.5 px-4 h-16 shrink-0">
            <a href="{{ route('transactions.index') }}" class="flex items-center gap-2.5 min-w-0" aria-label="dompetku — ke Dashboard">
                <span class="w-9 h-9 rounded-box bg-base-content text-base-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/>
                    </svg>
                </span>
                <span class="sb-brand-text min-w-0">
                    <span class="block font-extrabold tracking-tight text-base-content leading-tight">dompetku</span>
                    <span class="block text-[11px] text-base-content/60 leading-tight">Keuangan Pribadi</span>
                    @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                    <span class="sb-badge {{ $demoBadge }} mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                        Demo
                    </span>
                    @endif
                </span>
            </a>
        </div>
        <div class="mx-4 border-t border-base-300 shrink-0"></div>

        <!-- SATU area scroll: nav + utilities (brand & profile fixed) -->
        <div class="sb-nav flex-1 overflow-y-auto min-h-0 px-4 py-4">

        <!-- Nav groups -->
        <nav aria-label="Menu aplikasi">
            {!! $menuGroups() !!}
        </nav>

            <!-- Bottom utilities (ikut scroll bersama nav) -->
            <div class="pt-2">
                <ul class="menu menu-sm w-full gap-0.5 p-0">
                    <li class="sb-section-label menu-title px-3 pb-1 pt-2 text-[11px] uppercase tracking-wider text-base-content/60">Utilitas</li>
                    <li>
                        <button type="button" data-privacy-toggle
                                aria-label="Sembunyikan atau tampilkan saldo" title="Sembunyikan / tampilkan saldo"
                                class="sb-link min-h-[44px] gap-3">
                            <svg data-lock-open class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            <svg data-lock-closed class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            <span class="sb-label truncate" data-privacy-label>Sembunyikan Saldo</span>
                        </button>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" aria-label="Keluar dari akun" title="Keluar dari akun" class="sb-link min-h-[44px] gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span class="sb-label truncate">Keluar</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profile fixed di bawah (tidak ikut scroll) -->
        <div class="shrink-0 border-t border-base-300 p-3">
            <a href="{{ route('profile.edit') }}" title="Profil & Pengaturan"
               class="sb-profile-card flex items-center gap-3 rounded-box px-2 py-1.5 hover:bg-base-300 transition-colors min-w-0">
                <div class="avatar placeholder">
                    <div class="w-10 h-10 rounded-box bg-base-content text-base-100">
                        <span class="text-sm font-bold">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    </div>
                </div>
                <span class="sb-user-meta min-w-0 flex-1">
                    <span class="block text-sm font-bold text-base-content truncate">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                    <span class="block text-[11px] text-base-content/60 truncate">{{ Auth::user()->email ?? 'Personal Account' }}</span>
                </span>
                <svg class="w-4 h-4 shrink-0 text-base-content/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>
    </aside>

    <!-- ============ MOBILE TOPBAR ============ -->
    <header class="lg:hidden sticky top-0 z-40 bg-base-100/90 backdrop-blur-xl border-b border-base-300">
        <div class="flex items-center gap-2 px-4 h-16">
            <button type="button" @click="drawer = true" :aria-expanded="drawer" aria-controls="mobile-drawer"
                    aria-label="Buka menu navigasi"
                    class="btn btn-ghost btn-sm btn-square -ml-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            @if ($minimal)
            <a href="{{ $back ?: route('transactions.index') }}" aria-label="Kembali" class="btn btn-ghost btn-sm btn-square">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <h1 class="flex-1 min-w-0 text-base font-bold tracking-tight text-base-content truncate">{{ $title }}</h1>
            <div class="ml-auto flex items-center gap-1.5">
                <button type="button" data-theme-toggle aria-label="Ganti tema terang atau gelap" title="Ganti tema terang atau gelap" class="btn btn-ghost btn-sm btn-square">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
            </div>
            @else
            <a href="{{ route('transactions.index') }}" class="flex items-center gap-2 min-w-0" aria-label="dompetku — ke Dashboard">
                <span class="w-8 h-8 rounded-box bg-base-content text-base-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </span>
                <span class="font-extrabold tracking-tight text-base-content truncate">dompetku</span>
                @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                <span class="{{ $demoBadge }} shrink-0">Demo</span>
                @endif
            </a>
            <div class="ml-auto flex items-center gap-1.5">
                <x-notification-bell />

                <button type="button" data-theme-toggle aria-label="Ganti tema terang atau gelap" title="Ganti tema terang atau gelap" class="btn btn-ghost btn-sm btn-square">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <a href="{{ route('transactions.create') }}" aria-label="Tambah transaksi" class="btn btn-sm btn-square bg-base-content text-base-100 hover:bg-base-content/80">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v16m8-8H4"/></svg>
                </a>
            </div>
            @endif
        </div>
    </header>

    <!-- ============ DESKTOP TOPBAR ============ -->
    <header class="hidden lg:flex sticky top-0 z-30 h-16 items-center gap-2 px-6 xl:px-8 bg-base-100/90 backdrop-blur-xl border-b border-base-300">
        <button type="button" data-sb-collapse onclick="toggleSidebarCollapse(this)"
                aria-expanded="true" aria-label="Toggle sidebar" title="Tampilkan / sembunyikan sidebar"
                class="btn btn-ghost btn-sm btn-square -ml-2.5">
            <svg class="sb-ico-collapse w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            <svg class="sb-ico-expand w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </button>
        <h1 class="text-base font-bold tracking-tight text-base-content truncate">{{ $title }}</h1>
        @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
        <span class="{{ $demoBadge }}" title="Mode demo — data tidak bisa diubah">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
            Mode Demo
        </span>
        @endif
        <div class="ml-auto flex items-center gap-2">
            @unless ($minimal)
            <x-notification-bell />
            @endunless

            <button type="button" data-theme-toggle aria-label="Ganti tema terang atau gelap" title="Ganti tema terang atau gelap"
                    class="btn btn-ghost btn-sm gap-2">
                <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-[18px] h-[18px] block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <span class="hidden lg:inline">Tema</span>
            </button>
        </div>
    </header>

    <!-- ============ MOBILE DRAWER ============
         Overlay memakai `drawer-overlay` daisyUI (class itu memang disediakan
         untuk menutupi area gelap di belakang panel geser). Panelnya tetap
         dikendalikan Alpine (`drawer`) karena state buka/tutupnya perlu ikut
        dikunci scroll body dan ditutup dengan Escape. -->
    <div x-show="drawer" x-cloak class="lg:hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Menu navigasi">
        <div class="drawer-overlay bg-neutral-950/60"
             @click="drawer = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div id="mobile-drawer"
             class="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] bg-base-100 border-r border-base-300 flex flex-col overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">

            <div class="flex items-center gap-2.5 px-4 h-16 shrink-0 border-b border-base-300">
                <span class="w-8 h-8 rounded-box bg-base-content text-base-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </span>
                <span class="font-extrabold tracking-tight text-base-content">dompetku</span>
                @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                <span class="{{ $demoBadge }}">Demo</span>
                @endif
                <button type="button" @click="drawer = false" aria-label="Tutup menu" class="btn btn-ghost btn-sm btn-square ml-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="Menu aplikasi">
                {!! $menuGroups('drawer = false; ') !!}

                <div>
                    <ul class="menu menu-sm w-full gap-0.5 p-0">
                        <li class="menu-title px-3 pb-1 pt-2 text-[11px] uppercase tracking-wider text-base-content/60">Tampilan</li>
                        <li>
                            <button type="button" data-privacy-toggle class="sb-link min-h-[44px] gap-3">
                                <svg data-lock-open class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                <svg data-lock-closed class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                <span class="truncate" data-privacy-label>Sembunyikan Saldo</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="p-4 border-t border-base-300 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                        <div class="w-10 h-10 rounded-box bg-base-content text-base-100">
                            <span class="text-sm font-bold">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                        </div>
                    </div>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-base-content truncate">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                        <span class="block text-[11px] text-base-content/60 truncate">{{ Auth::user()->email ?? 'Personal Account' }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm w-full gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
