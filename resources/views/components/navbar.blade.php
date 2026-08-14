<nav x-data="{ mobileMenuOpen: false, profileDropdownOpen: false }"
     @keydown.escape.window="mobileMenuOpen = false; profileDropdownOpen = false"
     class="sticky top-0 z-50 w-full border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl transition-all duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-4">

            <!-- LOGO & BRANDING -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('transactions.index') }}" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M2.273 5.625A4.483 4.483 0 0 1 5.25 4.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 3H5.25a3 3 0 0 0-2.977 2.625ZM2.273 8.625A4.483 4.483 0 0 1 5.25 7.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 6H5.25a3 3 0 0 0-2.977 2.625ZM2.273 11.625A4.483 4.483 0 0 1 5.25 10.5h13.5c1.141 0 2.183.425 2.977 1.125A3 3 0 0 0 18.75 9H5.25a3 3 0 0 0-2.977 2.625ZM5.25 12a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h13.5a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3H5.25ZM15 16.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/>
                        </svg>
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">DompetKu</span>
                </a>

                <!-- NAVIGASI UTAMA (DESKTOP) -->
                <div class="hidden md:flex items-center gap-1 text-sm font-medium">
                    <a href="{{ route('transactions.index') }}"
                       class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                        Transaksi
                    </a>
                    <a href="{{ route('transactions.create') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah
                    </a>
                </div>
            </div>

            <!-- KANAN: PRIVACY, THEME, PROFIL, MOBILE MENU -->
            <div class="flex items-center gap-1.5 sm:gap-2">

                <!-- Privacy Toggle -->
                <button type="button"
                        id="privacy-toggle-btn"
                        class="p-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg id="privacy-eye-open" class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="privacy-eye-closed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.98-.923c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-6.115-3.473a3 3 0 01-4.242-4.242M3 3l18 18"/>
                    </svg>
                </button>

                <!-- Theme Toggle (ikon dikendalikan CSS dark:) -->
                <button type="button"
                        id="theme-toggle"
                        class="p-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        aria-label="Ganti tema terang atau gelap">
                    <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Profil Dropdown -->
                <div class="relative" @click.outside="profileDropdownOpen = false">
                    <button @click="profileDropdownOpen = !profileDropdownOpen"
                            type="button"
                            class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100/80 dark:hover:bg-slate-800/60 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                            aria-haspopup="menu"
                            :aria-expanded="profileDropdownOpen">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden sm:flex items-center gap-2.5 pr-1.5 text-left">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': profileDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    <div x-show="profileDropdownOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         role="menu"
                         class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xl shadow-slate-900/5 dark:shadow-black/20 py-1.5 z-50">

                        <div class="px-3.5 py-2 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('profile.edit') }}"
                           role="menuitem"
                           class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Profil Saya</span>
                        </a>

                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    role="menuitem"
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hamburger (Mobile) -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button"
                        class="md:hidden p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        aria-label="Buka menu navigasi"
                        :aria-expanded="mobileMenuOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- MOBILE MENU -->
    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl px-4 py-3 space-y-1.5 shadow-xl">

        <a href="{{ route('transactions.index') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Transaksi</span>
        </a>

        <a href="{{ route('transactions.create') }}"
           class="flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-600/30 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Transaksi</span>
        </a>
    </div>
</nav>
