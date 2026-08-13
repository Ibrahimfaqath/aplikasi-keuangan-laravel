<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard Keuangan' }}</title>

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

    <!-- NAVBAR WITH THEME TOGGLE -->
    <nav class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 px-4 py-3 sticky top-0 z-50">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <a href="/" class="font-bold text-xl text-emerald-600 dark:text-emerald-400 tracking-tight">
                DompetKu
            </a>

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
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

</body>
</html>