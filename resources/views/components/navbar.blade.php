<div x-data="{ sidebarOpen: false }"
     @keydown.escape.window="sidebarOpen = false">

<nav class="sticky top-0 z-50 w-full border-b border-slate-200/80 dark:border-navy-800 bg-white/80 dark:bg-navy-950/80 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-2 sm:gap-3">

            <!-- KIRI: Tombol Burger + Logo -->
            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                <button @click="sidebarOpen = true"
                        type="button"
                        class="hidden md:flex p-2 -ml-1 rounded-xl text-slate-600 dark:text-white/90 hover:bg-slate-100 dark:hover:bg-navy-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-navy-300 transition flex-shrink-0"
                        aria-label="Buka menu navigasi"
                        aria-haspopup="dialog"
                        :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <a href="{{ route('transactions.index') }}"
                   class="flex items-center gap-2 group min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 via-blue-500 to-blue-400 dark:from-navy-400 dark:via-navy-400 dark:to-navy-500 dark:text-white flex items-center justify-center text-white shadow-md shadow-blue-500/20 dark:shadow-black/40 group-hover:scale-105 transition-transform duration-200 flex-shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/>
                        </svg>
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white truncate">DompetKu</span>
                </a>
            </div>

            <!-- KANAN: Tombol Tambah Transaksi -->
            <a href="{{ route('transactions.create') }}"
               class="hidden md:inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white text-sm font-semibold shadow-sm shadow-blue-600/20 dark:shadow-black/40 transition flex-shrink-0"
               title="Tambah Transaksi">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="hidden sm:inline">Tambah Transaksi</span>
            </a>
        </div>
    </div>
</nav>

    <!-- SIDEBAR DRAWER -->
    <div x-show="sidebarOpen"
         x-cloak
         class="fixed inset-0 z-[100] hidden md:block"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="sidebarOpen = false"></div>

        <div class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-white dark:bg-navy-900 border-r border-slate-200 dark:border-navy-800 shadow-2xl flex flex-col overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">

            <!-- Header Profil -->
            <div class="px-5 py-5 border-b border-slate-100 dark:border-navy-800 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 dark:from-navy-400 dark:to-navy-500 dark:text-white flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-white/70 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <button @click="sidebarOpen = false" type="button"
                        class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-navy-800 transition"
                        aria-label="Tutup menu">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigasi -->
            <div class="px-3 py-3 space-y-1">
                <a href="{{ route('transactions.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('transactions.*') ? 'bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300' : 'text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/>
                    </svg>
                    <span>Beranda</span>
                </a>

                <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('profile.*') ? 'bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300' : 'text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Profil Saya</span>
                </a>
            </div>

            <div class="mx-5 my-2 border-t border-slate-100 dark:border-navy-800"></div>

            <!-- Export Laporan -->
            <div class="px-3 pb-2">
                <button type="button"
                        onclick="window.openExportModal ? openExportModal() : (window.location.href = '{{ route('transactions.export-pdf') }}')"
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 transition-colors text-left">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Export Laporan</span>
                </button>
            </div>

            <!-- Tema & Privasi -->
            <div class="px-3 py-2 space-y-1">
                <button type="button" data-theme-toggle
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 transition-colors text-left"
                        aria-label="Ganti tema terang atau gelap">
                    <svg class="w-4 h-4 hidden dark:block text-navy-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg class="w-4 h-4 block dark:hidden text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span>Tema Gelap / Terang</span>
                </button>

                <button type="button" data-privacy-toggle
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 transition-colors text-left"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg data-eye-open class="w-4 h-4 block text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <svg data-eye-closed class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Sembunyikan Saldo</span>
                </button>
            </div>

            <div class="mt-auto px-3 py-3 border-t border-slate-100 dark:border-navy-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION (mobile only) -->
    <nav id="bottomNav"
         class="fixed bottom-0 inset-x-0 z-50 md:hidden bg-white/95 dark:bg-navy-950/95 backdrop-blur-xl border-t border-slate-200/80 dark:border-navy-800 pb-[env(safe-area-inset-bottom)] transition-transform duration-300"
         aria-label="Navigasi utama">
        <div class="grid grid-cols-5 h-16 items-center">

            <!-- 1. Beranda -->
            <a href="{{ route('transactions.index') }}"
               class="flex flex-col items-center justify-center gap-1 text-[10px] font-semibold transition-colors {{ request()->routeIs('transactions.*') && !request()->routeIs('profile.*') ? 'text-blue-600 dark:text-navy-300' : 'text-slate-500 dark:text-white/60 hover:text-slate-800 dark:hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/>
                </svg>
                <span>Beranda</span>
            </a>

            <!-- 2. Anggaran -->
            <button type="button" onclick="window.openBudgetModal ? openBudgetModal() : null"
                    class="flex flex-col items-center justify-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-white/60 hover:text-slate-800 dark:hover:text-white transition-colors"
                    aria-label="Atur Anggaran">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>Anggaran</span>
            </button>

            <!-- 3. + Tambah (Tengah / FAB) -->
            <div class="flex items-center justify-center">
                <a href="{{ route('transactions.create') }}"
                   class="-mt-6 w-12 h-12 rounded-full bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-black/50 ring-4 ring-white dark:ring-navy-950 flex items-center justify-center transition-transform active:scale-95 flex-shrink-0"
                   title="Tambah Transaksi" aria-label="Tambah Transaksi">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>
            </div>

            <!-- 4. Laporan -->
            <button type="button" onclick="window.openExportModal ? openExportModal() : (window.location.href = '{{ route('transactions.export-pdf') }}')"
                    class="flex flex-col items-center justify-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-white/60 hover:text-slate-800 dark:hover:text-white transition-colors"
                    aria-label="Export Laporan">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Laporan</span>
            </button>

            <!-- 5. Profil -->
            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center gap-1 text-[10px] font-semibold transition-colors {{ request()->routeIs('profile.*') ? 'text-blue-600 dark:text-navy-300' : 'text-slate-500 dark:text-white/60 hover:text-slate-800 dark:hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>
</div>