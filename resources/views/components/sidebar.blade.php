{{-- ============================================================
  APP SHELL SIDEBAR — DompetKu
  - Desktop (lg+): fixed left sidebar 264px, collapsible ke 76px
  - Mobile: sticky topbar + off-canvas drawer
  - SEMUA link memakai named route yang ada (tanpa halaman fiktif):
    Dashboard -> transactions.index (+ edit), Tambah -> transactions.create,
    Asisten AI -> ai.index, Export -> modal/fallback export-pdf,
    Profil & Pengaturan -> profile.edit
  - Toggle tema/privasi memakai data-attribute yang sudah ditangani
    resources/js/app.js (mendukung banyak tombol sekaligus).
  - Prop `title`: judul halaman untuk topbar desktop.
    Contoh: <x-sidebar title="Dashboard" />
  ============================================================ --}}
@props(['title' => 'DompetKu'])
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
$sbActive = 'bg-neutral-900 text-white dark:bg-[#262626] dark:text-neutral-50 shadow-sm';
$sbIdle = 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50';
$sbIconSvg = 'class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
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
            'label' => 'Profil & Pengaturan',
            'href' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
            'icon' => '<path d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>',
        ],
    ],
];
@endphp

<div x-data="{ drawer: false }"
     @keydown.escape.window="drawer = false"
     x-effect="document.body.style.overflow = drawer ? 'hidden' : ''">

    <!-- ============ DESKTOP SIDEBAR (fixed, collapsible) ============ -->
    <aside class="sb-aside hidden lg:flex fixed inset-y-0 left-0 z-40 w-[256px] flex-col bg-white dark:bg-[#0A0A0A] border-r border-neutral-200 dark:border-[#262626] transition-all duration-200"
           aria-label="Navigasi utama DompetKu">

        <!-- Brand FIXED (tidak ikut scroll) -->
        <div class="sb-brand-row flex items-center gap-2.5 px-4 h-16 shrink-0">
            <a href="{{ route('transactions.index') }}" class="flex items-center gap-2.5 min-w-0" aria-label="DompetKu — ke Dashboard">
                <span class="w-9 h-9 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/>
                    </svg>
                </span>
                <span class="sb-brand-text min-w-0">
                    <span class="block font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 leading-tight">DompetKu</span>
                    <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 leading-tight">Keuangan Pribadi</span>
                </span>
            </a>
        </div>
        <div class="mx-4 border-t border-neutral-200 dark:border-[#262626] shrink-0"></div>

        <!-- SATU area scroll: nav + utilities (brand & profile fixed) -->
        <div class="sb-nav flex-1 overflow-y-auto min-h-0">

        <!-- Nav groups -->
        <nav class="px-4 py-4 space-y-6" aria-label="Menu aplikasi">
            @foreach ($sbGroups as $groupLabel => $items)
            <div>
                <p class="sb-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">{{ $groupLabel }}</p>
                <div class="space-y-1">
                    @foreach ($items as $item)
                        @if (($item['action'] ?? null) === 'export')
                        <button type="button"
                                onclick="window.openExportModal ? openExportModal() : (window.location.href = '{{ route('transactions.export-pdf') }}')"
                                title="Export Laporan"
                                class="sb-link w-full flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-semibold transition-colors duration-150 {{ $sbIdle }} text-left">
                            <svg {!! $sbIconSvg !!}>{!! $item['icon'] !!}</svg>
                            <span class="sb-label truncate">{{ $item['label'] }}</span>
                        </button>
                        @else
                        <a href="{{ $item['href'] }}"
                           title="{{ $item['label'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           class="sb-link flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-semibold transition-colors duration-150 {{ $item['active'] ? $sbActive : $sbIdle }}">
                            <svg {!! $sbIconSvg !!}>{!! $item['icon'] !!}</svg>
                            <span class="sb-label truncate">{{ $item['label'] }}</span>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endforeach
        </nav>

            <!-- Bottom utilities (ikut scroll bersama nav) -->
            <div class="px-4 pt-2 pb-4">
                <p class="sb-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Utilitas</p>
                <div class="space-y-1">
                <button type="button" data-privacy-toggle
                        aria-label="Sembunyikan atau tampilkan saldo" title="Sembunyikan / tampilkan saldo"
                        class="sb-link w-full flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-medium transition-colors duration-150 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                    <svg data-eye-open class="w-5 h-5 shrink-0 block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    <svg data-eye-closed class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    <span class="sb-label truncate">Sembunyikan Saldo</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" aria-label="Keluar dari akun" title="Keluar dari akun"
                            class="sb-link w-full flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-medium transition-colors duration-150 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="sb-label truncate">Keluar</span>
                    </button>
                </form>
                </div>
            </div>
        </div>

        <!-- Profile fixed di bawah (tidak ikut scroll) -->
        <div class="shrink-0 border-t border-neutral-200 dark:border-[#262626] p-3">
            <a href="{{ route('profile.edit') }}" title="Profil & Pengaturan"
               class="flex items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-neutral-100 dark:hover:bg-[#262626] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center text-sm font-bold shrink-0" aria-hidden="true">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </span>
                <span class="sb-user-meta min-w-0 flex-1">
                    <span class="block text-sm font-bold text-neutral-900 dark:text-neutral-50 truncate">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                    <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 truncate">{{ Auth::user()->email ?? 'Personal Account' }}</span>
                </span>
                <svg class="w-4 h-4 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>
    </aside>

    <!-- ============ MOBILE TOPBAR ============ -->
    <header class="lg:hidden sticky top-0 z-40 bg-white/90 dark:bg-[#0A0A0A]/90 backdrop-blur-xl border-b border-neutral-200 dark:border-[#262626]">
        <div class="flex items-center gap-2 px-4 h-16">
            <button type="button" @click="drawer = true" :aria-expanded="drawer" aria-controls="mobile-drawer"
                    aria-label="Buka menu navigasi"
                    class="p-2 -ml-2 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <a href="{{ route('transactions.index') }}" class="flex items-center gap-2 min-w-0" aria-label="DompetKu — ke Dashboard">
                <span class="w-8 h-8 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </span>
                <span class="font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 truncate">DompetKu</span>
            </a>
            <div class="ml-auto flex items-center gap-1.5">
                <div x-data="{ open: false }" class="relative shrink-0">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="Change appearance" title="Appearance"
                            class="p-2 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 010 18z" fill="currentColor" stroke="none"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false" role="menu" aria-label="Appearance"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-52 rounded-xl border border-neutral-200 dark:border-[#262626] bg-white dark:bg-[#171717] shadow-sm p-1.5 z-50 origin-top-right">
                        <p class="px-2.5 pt-1.5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Appearance</p>
                        <button type="button" role="menuitemradio" data-theme-option="light" aria-checked="false" @click="window.setTheme('light'); open = false"
                                class="w-full flex items-center gap-2.5 px-2.5 min-h-[40px] rounded-lg text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                            <span class="flex-1 text-left">Light</span>
                            <span data-theme-check class="hidden"><svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
                        </button>
                        <button type="button" role="menuitemradio" data-theme-option="dark" aria-checked="false" @click="window.setTheme('dark'); open = false"
                                class="w-full flex items-center gap-2.5 px-2.5 min-h-[40px] rounded-lg text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span class="flex-1 text-left">Dark</span>
                            <span data-theme-check class="hidden"><svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
                        </button>
                    </div>
                </div>
                <a href="{{ route('transactions.create') }}" aria-label="Tambah transaksi"
                   class="w-10 h-10 rounded-xl bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white text-white dark:text-neutral-900 flex items-center justify-center shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v16m8-8H4"/></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- ============ DESKTOP TOPBAR ============ -->
    <header class="hidden lg:flex sticky top-0 z-30 h-16 items-center gap-2 px-6 xl:px-8 bg-white/90 dark:bg-[#0A0A0A]/90 backdrop-blur-xl border-b border-neutral-200 dark:border-[#262626]">
        <button type="button" data-sb-collapse onclick="toggleSidebarCollapse(this)"
                aria-expanded="true" aria-label="Toggle sidebar" title="Tampilkan / sembunyikan sidebar"
                class="p-2.5 -ml-2.5 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
            <svg class="sb-ico-collapse w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            <svg class="sb-ico-expand w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </button>
        <h1 class="text-base font-bold tracking-tight text-neutral-900 dark:text-neutral-50 truncate">{{ $title }}</h1>
        <div class="ml-auto flex items-center gap-2">
            <div x-data="{ open: false }" class="relative shrink-0">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="Change appearance" title="Appearance"
                        class="flex items-center gap-2 h-10 px-3 rounded-xl border border-neutral-200 dark:border-[#262626] bg-white dark:bg-[#171717] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 010 18z" fill="currentColor" stroke="none"/></svg>
                    <span class="hidden lg:inline text-sm font-semibold">Appearance</span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false" role="menu" aria-label="Appearance"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-52 rounded-xl border border-neutral-200 dark:border-[#262626] bg-white dark:bg-[#171717] shadow-sm p-1.5 z-50 origin-top-right">
                    <p class="px-2.5 pt-1.5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Appearance</p>
                    <button type="button" role="menuitemradio" data-theme-option="light" aria-checked="false" @click="window.setTheme('light'); open = false"
                            class="w-full flex items-center gap-2.5 px-2.5 min-h-[40px] rounded-lg text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <span class="flex-1 text-left">Light</span>
                        <span data-theme-check class="hidden"><svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
                    </button>
                    <button type="button" role="menuitemradio" data-theme-option="dark" aria-checked="false" @click="window.setTheme('dark'); open = false"
                            class="w-full flex items-center gap-2.5 px-2.5 min-h-[40px] rounded-lg text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span class="flex-1 text-left">Dark</span>
                        <span data-theme-check class="hidden"><svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ============ MOBILE DRAWER ============ -->
    <div x-show="drawer" x-cloak class="lg:hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Menu navigasi">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             @click="drawer = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div id="mobile-drawer"
             class="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] bg-white dark:bg-[#0A0A0A] border-r border-neutral-200 dark:border-[#262626] shadow-sm flex flex-col overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">

            <div class="flex items-center gap-2.5 px-4 h-16 shrink-0 border-b border-neutral-200 dark:border-[#262626]">
                <span class="w-8 h-8 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/></svg>
                </span>
                <span class="font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">DompetKu</span>
                <button type="button" @click="drawer = false" aria-label="Tutup menu"
                        class="ml-auto p-2 rounded-xl text-neutral-500 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6" aria-label="Menu aplikasi">
                @foreach ($sbGroups as $groupLabel => $items)
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">{{ $groupLabel }}</p>
                    <div class="space-y-1">
                        @foreach ($items as $item)
                            @if (($item['action'] ?? null) === 'export')
                            <button type="button"
                                    @click="drawer = false; window.openExportModal ? openExportModal() : (window.location.href = '{{ route('transactions.export-pdf') }}')"
                                    class="w-full flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-semibold transition-colors duration-150 {{ $sbIdle }} text-left">
                                <svg {!! $sbIconSvg !!}>{!! $item['icon'] !!}</svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </button>
                            @else
                            <a href="{{ $item['href'] }}"
                               @click="drawer = false"
                               @if ($item['active']) aria-current="page" @endif
                               class="flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-semibold transition-colors duration-150 {{ $item['active'] ? $sbActive : $sbIdle }}">
                                <svg {!! $sbIconSvg !!}>{!! $item['icon'] !!}</svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endforeach

                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Tampilan</p>
                    <div class="space-y-1">
                        <button type="button" data-privacy-toggle
                                class="w-full flex items-center gap-3 px-3 min-h-[44px] rounded-xl text-sm font-semibold transition-colors duration-150 {{ $sbIdle }} text-left">
                            <svg data-eye-open class="w-5 h-5 shrink-0 block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            <svg data-eye-closed class="w-5 h-5 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span class="truncate">Sembunyikan Saldo</span>
                        </button>
                    </div>
                </div>
            </nav>

            <div class="p-4 border-t border-neutral-200 dark:border-[#262626] space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center text-sm font-bold shrink-0" aria-hidden="true">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-neutral-900 dark:text-neutral-50 truncate">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                        <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 truncate">{{ Auth::user()->email ?? 'Personal Account' }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-3 min-h-[44px] rounded-xl text-sm font-semibold bg-neutral-100 dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 hover:bg-neutral-200 dark:hover:bg-[#333333] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
