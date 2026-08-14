<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950" 
      x-data="dashboardApp()" 
      x-init="initDashboard()"
      :class="{ 'dark': isDarkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — kelola pemasukan, pengeluaran, dan anggaran bulanan dalam satu aplikasi pencatatan keuangan pribadi.">
    <meta name="theme-color" content="#10b981">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="DompetKu — Catatan Keuangan Pribadi">
    <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Dashboard Keuangan - DompetKu</title>
    
    <!-- Theme Init - KRITIKAL Sebelum Render -->
    <script>
        (function initTheme() {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = stored === 'dark' || (!stored && prefersDark);
                if (isDark) document.documentElement.classList.add('dark');
                window.__initialTheme = isDark ? 'dark' : 'light';
            } catch (e) {
                document.documentElement.classList.remove('dark');
                window.__initialTheme = 'light';
            }
        })();
    </script>

    <!-- CSS hasil build (Vite) — menggantikan Tailwind CDN agar jauh lebih ringan -->
    @vite(['resources/css/app.css'])

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { overflow-x: hidden; }
        @media print {
            body { background-color: #ffffff !important; color: #000000 !important; }
            header, form, button, .no-print, nav, #exportModal { display: none !important; }
            .print-only { display: block !important; }
            .shadow-sm, .shadow-md, .shadow-xl { shadow: none !important; border: 1px solid #ccc !important; }
        }
        
        @keyframes skeleton-shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .animate-shimmer {
            background: linear-gradient(90deg, rgba(229, 231, 235, 0.8) 25%, rgba(243, 244, 246, 0.9) 37%, rgba(229, 231, 235, 0.8) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }
        .dark .animate-shimmer {
            background: linear-gradient(90deg, rgba(31, 41, 55, 0.8) 25%, rgba(55, 65, 81, 0.9) 37%, rgba(31, 41, 55, 0.8) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }
        
        img[loading="lazy"] {
            background: #f1f5f9;
        }
        .dark img[loading="lazy"] {
            background: #1e293b;
        }
        
        /* Dropdown transition */
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="min-h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased">

    <!-- ============================================================
    ALPINE.JS APP
    ============================================================ -->
    <script>
        function dashboardApp() {
            return {
                isLoading: true,
                isDarkMode: window.__initialTheme === 'dark',
                isPrivate: false,
                totalBalance: {{ $totalBalance ?? $totalSaldo ?? 0 }},
                totalIncome: {{ $totalIncome ?? $pemasukan ?? 0 }},
                totalExpense: {{ $totalExpense ?? $pengeluaran ?? 0 }},
                transactions: @json($transactions ?? $transaksi ?? []),
                chartInstance: null,
                
                initDashboard() {
                    const privacyStored = localStorage.getItem('privacy_mode');
                    this.isPrivate = privacyStored === 'enabled';
                    
                    if (this.isDarkMode) {
                        document.documentElement.classList.add('dark');
                    }
                    
                    this.isLoading = true;
                    
                    setTimeout(() => {
                        this.isLoading = false;
                        this.$nextTick(() => {
                            this.applyPrivacyMask();
                            setTimeout(() => this.initChart(), 100);
                        });
                    }, 500);
                },
                
                togglePrivacy() {
                    this.isPrivate = !this.isPrivate;
                    localStorage.setItem('privacy_mode', this.isPrivate ? 'enabled' : 'disabled');
                    this.applyPrivacyMask();
                },
                
                applyPrivacyMask() {
                    const targets = document.querySelectorAll('.privacy-target');
                    targets.forEach(el => {
                        const rawValue = el.getAttribute('data-amount');
                        if (this.isPrivate) {
                            el.textContent = '••••••••';
                        } else {
                            el.textContent = rawValue || 'Rp 0';
                        }
                    });
                    
                    const btn = document.getElementById('privacyToggleBtn');
                    if (btn) {
                        const eyeOpen = btn.querySelector('#eyeOpenIcon');
                        const eyeClosed = btn.querySelector('#eyeClosedIcon');
                        if (this.isPrivate) {
                            eyeOpen?.classList.add('hidden');
                            eyeClosed?.classList.remove('hidden');
                        } else {
                            eyeOpen?.classList.remove('hidden');
                            eyeClosed?.classList.add('hidden');
                        }
                    }
                },
                
                toggleDarkMode() {
                    this.isDarkMode = !this.isDarkMode;
                    localStorage.setItem('theme', this.isDarkMode ? 'dark' : 'light');
                    if (this.isDarkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    setTimeout(() => this.initChart(), 200);
                },
                
                initChart() {
                    const canvas = document.getElementById('mainChart');
                    if (!canvas) return;
                    
                    if (this.chartInstance) {
                        this.chartInstance.destroy();
                        this.chartInstance = null;
                    }
                    
                    const isDark = this.isDarkMode;
                    const textColor = isDark ? '#94a3b8' : '#64748b';
                    const gridColor = isDark ? '#1e293b' : '#f1f5f9';
                    
                    this.chartInstance = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: ['Pemasukan', 'Pengeluaran'],
                            datasets: [{
                                data: [this.totalIncome, this.totalExpense],
                                backgroundColor: ['rgba(16, 185, 129, 0.85)', 'rgba(244, 63, 94, 0.85)'],
                                borderRadius: 8,
                                barThickness: 40
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: textColor }, grid: { display: false } },
                                y: { ticks: { color: textColor }, grid: { color: gridColor } }
                            }
                        }
                    });
                }
            }
        }
    </script>

    <!-- ============================================================
    NAVBAR MODERN (HEIGHT: 56px/64px WITH BACKDROP BLUR & ALPINE.JS)
    ============================================================ -->
    <nav x-data="{ mobileMenuOpen: false, profileDropdownOpen: false }" 
         class="sticky top-0 z-50 h-[64px] md:h-[56px] w-full border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl transition-all duration-200">
        
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
            <div class="flex items-center justify-between h-full gap-4">
                
                <!-- 1. LOGO & BRANDING -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">DompetKu</span>
                        </div>
                    </a>
                </div>

                <!-- 3. RIGHT UTILITY (PRIVACY, THEME, PROFILE) -->
                <div class="flex items-center gap-1.5 sm:gap-2">
                    
                    <!-- Privacy Toggle Button -->
                    <button type="button" 
                            id="privacyToggleBtn"
                            @click="togglePrivacy()"
                            class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition" 
                            aria-label="Toggle Privacy">
                        <svg id="eyeOpenIcon" class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="eyeClosedIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.98-.923c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-6.115-3.473a3 3 0 01-4.242-4.242M3 3l18 18" />
                        </svg>
                    </button>

                    <!-- Theme Toggle Button -->
                    <button type="button" 
                            @click="toggleDarkMode()" 
                            class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition" 
                            aria-label="Toggle Dark Mode">
                        <svg id="sunIcon" class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg id="moonIcon" class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- Profile Dropdown (Desktop & Mobile) -->
                    <div class="relative" @click.outside="profileDropdownOpen = false">
                        <button @click="profileDropdownOpen = !profileDropdownOpen" 
                                type="button" 
                                class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100/80 dark:hover:bg-slate-800/60 transition-colors focus:outline-none">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="hidden sm:flex items-center gap-1.5 text-left">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ Auth::user()->name ?? 'User' }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': profileDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <div x-show="profileDropdownOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                             class="absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xl py-1.5 z-50">
                            
                            <div class="px-3.5 py-2 border-b border-slate-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ Auth::user()->name ?? 'User' }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}" 
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profil Saya</span>
                            </a>

                            <button type="button" 
                                    onclick="openExportModal()" 
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition text-left">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Export Data</span>
                            </button>

                            <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition text-left">
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
                            class="md:hidden p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none"
                            aria-label="Toggle Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- 4. MOBILE MENU DROPDOWN -->
        <div x-show="mobileMenuOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl px-4 py-3 space-y-2 shadow-xl">
            
            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('transactions.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                <span>Transaksi</span>
            </a>

            <a href="{{ route('transactions.create') }}" 
               class="flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                <span>Tambah Transaksi</span>
            </a>
        </div>
    </nav>

    <!-- Toast Notification -->
    @if(session('success'))
    <div id="toast-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ session('success') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <!-- MAIN CONTENT BODY -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8 overflow-x-hidden">

        <!-- DASHBOARD HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Ringkasan Keuangan
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau seluruh pemasukan, pengeluaran, dan arus kas kamu.
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-2 no-print flex-shrink-0">
                <button type="button" onclick="openExportModal()" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </button>
                
                <a href="{{ route('transactions.create') }}" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Transaksi Baru
                </a>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
            
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                    <span>Saldo Saat Ini</span>
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                
                <div x-show="isLoading">
                    <div class="h-10 w-48 bg-slate-200 dark:bg-slate-700 rounded-lg animate-shimmer"></div>
                </div>
                
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white privacy-target" 
                     x-show="!isLoading"
                     x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)"
                     x-text="isPrivate ? '••••••••' : 'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)">
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                    <span>Total Pemasukan</span>
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                
                <div x-show="isLoading">
                    <div class="h-10 w-40 bg-slate-200 dark:bg-slate-700 rounded-lg animate-shimmer"></div>
                </div>
                
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400 privacy-target" 
                     x-show="!isLoading"
                     x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)"
                     x-text="isPrivate ? '••••••••' : 'Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)">
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                    <span>Total Pengeluaran</span>
                    <div class="p-2 bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                </div>
                
                <div x-show="isLoading">
                    <div class="h-10 w-40 bg-slate-200 dark:bg-slate-700 rounded-lg animate-shimmer"></div>
                </div>
                
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400 privacy-target" 
                     x-show="!isLoading"
                     x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)"
                     x-text="isPrivate ? '••••••••' : 'Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)">
                </div>
            </div>

        </section>

        <!-- BUDGET CARD -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Anggaran Bulanan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                </div>
                <button type="button" onclick="openBudgetModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 rounded-xl text-sm font-semibold hover:bg-amber-100 dark:hover:bg-amber-900/40 transition no-print">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $budget ? 'Ubah Anggaran' : 'Atur Anggaran' }}
                </button>
            </div>

            @if($budget)
                @php
                    $percentage = $budget->amount > 0 ? min(100, round(($monthlyExpense / $budget->amount) * 100)) : 0;
                    $remaining  = $budget->amount - $monthlyExpense;
                    $isOver     = $remaining < 0;
                    $barColor   = $isOver ? 'bg-rose-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
                    <span>Terpakai: <span class="privacy-target" data-amount="Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span></span>
                    <span>Batas: <span class="privacy-target" data-amount="Rp {{ number_format($budget->amount, 0, ',', '.') }}">Rp {{ number_format($budget->amount, 0, ',', '.') }}</span></span>
                </div>

                <div class="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percentage }}%"></div>
                </div>

                <p class="mt-2 text-xs font-semibold {{ $isOver ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                    @if($isOver)
                        ⚠️ Melebihi batas sebesar Rp {{ number_format(abs($remaining), 0, ',', '.') }}
                    @else
                        Sisa <span class="privacy-target" data-amount="Rp {{ number_format($remaining, 0, ',', '.') }}">Rp {{ number_format($remaining, 0, ',', '.') }}</span> · {{ $percentage }}% dari anggaran terpakai
                    @endif
                </p>
            @else
                <div class="flex items-start gap-3 p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/50 rounded-xl">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Kamu belum mengatur anggaran untuk bulan ini. Klik
                        <button type="button" onclick="openBudgetModal()" class="font-semibold text-amber-600 dark:text-amber-400 underline underline-offset-2 hover:text-amber-700">Atur Anggaran</button>
                        untuk menetapkan batas pengeluaranmu.
                    </p>
                </div>
            @endif
        </section>

        <!-- CHART -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Arus Kas</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Perbandingan pemasukan vs pengeluaran</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs font-semibold">
                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span> Pemasukan
                    </span>
                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span> Pengeluaran
                    </span>
                </div>
            </div>
            
            <div class="h-56 sm:h-72 flex items-center justify-center bg-slate-100 dark:bg-slate-800/50 rounded-xl animate-shimmer" 
                 x-show="isLoading">
                <span class="text-slate-400 dark:text-slate-500 text-sm">Memuat grafik...</span>
            </div>
            
            <div class="h-56 sm:h-72" x-show="!isLoading">
                <canvas id="mainChart"></canvas>
            </div>
        </section>

        <!-- FILTER -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm no-print">
            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-5 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..." class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="lg:col-span-3">
                    <select name="type" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Tipe</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="period" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all">Semua Waktu</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7_days" {{ request('period') == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm shadow-indigo-600/20 transition">
                        Filter
                    </button>
                    @if(request('search') || request('type') || request('period'))
                        <a href="{{ route('transactions.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE TRANSACTIONS -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- SKELETON LOAD -->
            <div x-show="isLoading">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800">
                                <th class="py-3.5 px-6"><div class="h-4 w-20 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6 text-right"><div class="h-4 w-20 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer ml-auto"></div></th>
                                <th class="py-3.5 px-6 text-center"><div class="h-4 w-12 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer mx-auto"></div></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-slate-200 dark:bg-slate-700 rounded-xl animate-shimmer"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div>
                                            <div class="h-3 w-20 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6"><div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6"><div class="h-6 w-20 bg-slate-200 dark:bg-slate-700 rounded-full animate-shimmer"></div></td>
                                <td class="py-4 px-6 text-right"><div class="h-4 w-28 bg-slate-200 dark:bg-slate-700 rounded animate-shimmer ml-auto"></div></td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-lg animate-shimmer"></div>
                                        <div class="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-lg animate-shimmer"></div>
                                    </div>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CONTENT TRANSACTIONS -->
            <div x-show="!isLoading">
                <!-- DESKTOP TABLE -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Bukti</th>
                                <th class="py-3.5 px-6">Keterangan</th>
                                <th class="py-3.5 px-6">Jenis</th>
                                <th class="py-3.5 px-6 text-right">Nominal</th>
                                <th class="py-3.5 px-6 text-center no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                            @php $items = $transactions ?? $transaksi ?? []; @endphp
                            @forelse ($items as $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-4 px-6 font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                                </td>
                                <td class="py-4 px-6">
                                    @if(!empty($item->image))
                                    <a href="{{ asset('storage/' . $item->image) }}" target="_blank" class="block w-8 h-8 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700">
                                        <img src="{{ asset('storage/' . $item->image) }}" 
                                             loading="lazy"
                                             class="w-full h-full object-cover"
                                             alt="Bukti">
                                    </a>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-semibold text-slate-900 dark:text-white">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ ($item->type ?? 'income') == 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60' }}">
                                        {{ ($item->type ?? 'income') == 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right font-extrabold text-slate-900 dark:text-white whitespace-nowrap privacy-target" 
                                    data-amount="Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                                    Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-4 px-6 text-center no-print">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('transactions.edit', $item->id) }}" class="p-1.5 text-slate-400 hover:text-amber-600 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-indigo-50 dark:bg-slate-800 text-indigo-500 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada transaksi</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- MOBILE LIST VIEW -->
                <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($items as $item)
                    <div class="p-4 space-y-2.5">
                        
                        <!-- Baris 1: Tanggal + Jenis -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">
                                {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            </span>
                            <span class="font-semibold px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider {{ ($item->type ?? 'income') == 'income' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' }}">
                                {{ ($item->type ?? 'income') == 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </div>

                        <!-- Baris 2: Gambar + Judul + Nominal -->
                        <div class="flex items-center gap-3 pt-1">
                            @if(!empty($item->image))
                            <a href="{{ asset('storage/' . $item->image) }}" target="_blank" class="flex-shrink-0">
                                <img src="{{ asset('storage/' . $item->image) }}" 
                                     loading="lazy"
                                     class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700"
                                     alt="Bukti transaksi">
                            </a>
                            @else
                            <div class="flex-shrink-0 w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center border border-slate-200 dark:border-slate-700">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate text-sm sm:text-base">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </p>
                            </div>

                            <p class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base whitespace-nowrap privacy-target" 
                               data-amount="Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                                Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                            </p>
                        </div>

                        <!-- Baris 3: Action Buttons -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100/60 dark:border-slate-800/60">
                            <a href="{{ route('transactions.edit', $item->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit</span>
                            </a>

                            <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-lg text-xs font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-indigo-50 dark:bg-slate-800 text-indigo-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada transaksi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                    </div>
                    @endforelse
                </div>

                <!-- PAGINATION WITH RAPIH SPACING -->
                @if(isset($transactions) && method_exists($transactions, 'links') && $transactions->hasPages())
                <div class="px-6 py-3 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $transactions->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>

        </section>

    </div>

    <!-- BUDGET MODAL -->
    <div id="budgetModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <!-- Backdrop Blur -->
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="closeBudgetModal()"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">

                <!-- Header Modal -->
                <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Anggaran Bulanan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Atur batas pengeluaran bulan {{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeBudgetModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form action="{{ route('budgets.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">Batas Pengeluaran (Rp)</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-sm">Rp</div>
                            <input type="number" name="amount" value="{{ $budget?->amount }}" placeholder="Contoh: 5000000" required min="0" step="any" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 transition">Simpan Anggaran</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function openBudgetModal() { document.getElementById('budgetModal').classList.remove('hidden'); }
        function closeBudgetModal() { document.getElementById('budgetModal').classList.add('hidden'); }
    </script>

    <!-- EXPORT MODAL -->
    @include('components.export-modal')

    <script>
        function openExportModal() {
            const modal = document.getElementById('exportModal');
            if (modal) modal.classList.remove('hidden');
        }
    </script>

</body>
</html>