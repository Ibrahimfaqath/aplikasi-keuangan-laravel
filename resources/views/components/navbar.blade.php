<nav x-data="{ mobileMenuOpen: false, profileDropdownOpen: false }" 
     class="sticky top-0 z-50 h-[64px] md:h-[56px] w-full border-b border-gray-200/80 dark:border-gray-800/80 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl transition-all duration-200">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
        <div class="flex items-center justify-between h-full gap-4">
            
            <!-- 1. LEFT: LOGO & BRANDING -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <!-- Icon Gradient Logo -->
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <!-- Brand Name & Badge -->
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-lg tracking-tight text-gray-900 dark:text-white">DompetKu</span>
                    </div>
                </a>
            </div>

            <!-- 3. RIGHT: PROFILE & MOBILE MENU TOGGLE -->
            <div class="flex items-center gap-2">
                
                <!-- Profile Dropdown (Desktop & Tablet) -->
                <div class="relative" @click.outside="profileDropdownOpen = false">
                    <button @click="profileDropdownOpen = !profileDropdownOpen" 
                            type="button" 
                            class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-gray-100/80 dark:hover:bg-gray-800/60 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <!-- Avatar Huruf Inisial -->
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name ?? 'Ibrahim', 0, 1)) }}
                        </div>
                        <!-- Name & Arrow Dropdown -->
                        <div class="hidden sm:flex items-center gap-1.5 text-left">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name ?? 'Ibrahim' }}</span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': profileDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div x-show="profileDropdownOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         class="absolute right-0 mt-2 w-52 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 shadow-lg shadow-gray-900/5 dark:shadow-black/20 py-1.5 z-50"
                         style="display: none;">
                        
                        <div class="px-3.5 py-2 border-b border-gray-100 dark:border-gray-700/60 sm:hidden">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ Auth::user()->name ?? 'Ibrahim' }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ Auth::user()->email ?? 'ibrahim@example.com' }}</p>
                        </div>

                        <!-- Menu Profil -->
                        <a href="{{ route('profile.edit') ?? '#' }}" 
                           class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Profil Saya</span>
                        </a>

                        <!-- Menu Export Data -->
                        <a href="#" 
                           class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export Data</span>
                        </a>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700/60"></div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') ?? '#' }}">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hamburger Button (Mobile Only) -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        type="button" 
                        class="md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none"
                        aria-label="Toggle Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- 4. MOBILE DROPDOWN MENU -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl px-4 py-3 space-y-2 shadow-xl"
         style="display: none;">
        
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('transactions.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            <span>Transaksi</span>
        </a>

        <a href="{{ route('transactions.create') }}" 
           class="flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-lg text-sm font-semibold bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
            <span>Tambah Transaksi</span>
        </a>
    </div>
</nav>