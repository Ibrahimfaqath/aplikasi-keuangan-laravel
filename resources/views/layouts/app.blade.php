<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — dashboard keuangan pribadi: pantau saldo, pemasukan, pengeluaran, dan transaksi terakhir.">
    <meta name="theme-color" content="#10b981">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="{{ $title ?? 'Dashboard Keuangan - DompetKu' }}">
    <meta property="og:description" content="Pantau saldo, pemasukan, pengeluaran, dan transaksi terakhir dalam satu dashboard.">
    <meta property="og:url" content="{{ url()->current() }}">

    <title>{{ $title ?? 'Dashboard Keuangan - DompetKu' }}</title>

    <!-- 1. KUNCI WARNA CANVAS BROWSER (MEMATIKAN LIGHT FLASH SAMA SEKALI) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = savedTheme === 'dark' || (!savedTheme && systemPrefersDark);

            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#111827';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.backgroundColor = '#f9fafb';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen antialiased">

    <!-- NAVBAR WITH THEME TOGGLE & USER MENU -->
    <nav class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 px-4 py-3 sticky top-0 z-50">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-6">
                <a href="/" class="font-bold text-xl text-emerald-600 dark:text-emerald-400 tracking-tight">
                    DompetKu
                </a>

                <!-- Navigasi Utama -->
                <div class="hidden sm:flex items-center gap-1 text-sm font-medium">
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('transactions.index') }}"
                       class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('transactions.*') ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Transaksi
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Tombol Theme Switcher -->
                <button id="theme-toggle" type="button" 
                    class="p-2.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg text-sm"
                    aria-label="Toggle Theme">
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>

                <!-- Menu Pengguna -->
                <div class="relative" id="user-menu">
                    <button id="user-menu-btn" type="button"
                        class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-52 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1.5">
                        <div class="px-3.5 py-2 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('transactions.index') }}"
                           class="sm:hidden flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Transaksi
                        </a>

                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profil Saya
                        </a>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition text-left">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        (function () {
            const btn = document.getElementById('user-menu-btn');
            const dropdown = document.getElementById('user-menu-dropdown');

            if (!btn || !dropdown) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        })();
    </script>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

</body>
</html>