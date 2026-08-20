<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950"
      x-data="dashboardApp()"
      x-init="initDashboard()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — kelola pemasukan, pengeluaran, dan anggaran bulanan dalam satu aplikasi pencatatan keuangan pribadi.">
    <meta name="theme-color" content="#0A1128">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Transaksi - DompetKu">
    <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Transaksi - DompetKu</title>

    <!-- Theme Init - KRITIKAL Sebelum Render -->
    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                // Default tema gelap navy (#0A1128) — mode terang hanya jika user memilihnya
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A1128' : '#f8fafc';
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- CSS & JS hasil build (Vite) — Alpine & Chart.js ikut dibundle, tanpa CDN -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/chart.js'])

    <style>
        body { overflow-x: hidden; }
        @media print {
            body { background-color: #ffffff !important; color: #000000 !important; }
            header, form, button, .no-print, nav, #exportModal, .fixed { display: none !important; }
            .print-only { display: block !important; }
            .shadow-sm, .shadow-md, .shadow-xl, .shadow-lg { box-shadow: none !important; border: 1px solid #ccc !important; }
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
            background: linear-gradient(90deg, rgba(16, 29, 60, 0.8) 25%, rgba(21, 38, 73, 0.9) 37%, rgba(16, 29, 60, 0.8) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }

        img[loading="lazy"] {
            background: #f1f5f9;
        }
        .dark img[loading="lazy"] {
            background: #101d3c;
        }

        /* Dropdown transition */
        [x-cloak] { display: none !important; }

        /* Custom select dropdown arrow — professional & consistent */
        .select-field {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem !important;
        }
        .dark .select-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased flex flex-col">

    <!-- ============================================================
    ALPINE.JS APP — loading, statistik & grafik (theme/privacy ditangani app.js)
    ============================================================ -->
    <script>
        function dashboardApp() {
            return {
                isLoading: true,
                isDarkMode: document.documentElement.classList.contains('dark'),
                totalBalance: {{ $totalBalance ?? $totalSaldo ?? 0 }},
                totalIncome: {{ $totalIncome ?? $pemasukan ?? 0 }},
                totalExpense: {{ $totalExpense ?? $pengeluaran ?? 0 }},
                categoryExpenses: @json($categoryExpenses ?? []),
                // Data grafik gabungan per periode: minggu / bulan / tahun
                @php
                    $trendDataJson = json_encode($trendData ?? [
                        'week'  => ['labels' => [], 'income' => [], 'expense' => []],
                        'month' => ['labels' => [], 'income' => [], 'expense' => []],
                        'year'  => ['labels' => [], 'income' => [], 'expense' => []],
                    ]);
                @endphp
                trendData: {!! $trendDataJson !!},
                trendPeriod: 'week',
                trendChartInstance: null,
                categoryChartInstance: null,

                initDashboard() {
                    this.isLoading = true;

                    // Sinkronkan grafik saat tema diganti lewat navbar (app.js)
                    window.addEventListener('theme-changed', (e) => {
                        this.isDarkMode = e.detail?.isDark ?? document.documentElement.classList.contains('dark');
                        this.initTrendChart();
                        this.initCategoryChart();
                    });

                    setTimeout(() => {
                        this.isLoading = false;
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.initTrendChart();
                                this.initCategoryChart();
                            }, 100);
                        });
                    }, 500);
                },

                // Ganti periode grafik: minggu / bulan / tahun
                setTrendPeriod(period) {
                    this.trendPeriod = period;
                    this.initTrendChart();
                },

                // Line chart gabungan pemasukan + pengeluaran per periode
                initTrendChart() {
                    const canvas = document.getElementById('trendChart');
                    if (!canvas) return;

                    if (this.trendChartInstance) {
                        this.trendChartInstance.destroy();
                        this.trendChartInstance = null;
                    }

                    const isDark = this.isDarkMode;
                    const textColor = isDark ? 'rgba(255,255,255,0.65)' : '#64748b';
                    const gridColor = isDark ? '#1d2b52' : '#f1f5f9';

                    const series = this.trendData[this.trendPeriod] ?? { labels: [], income: [], expense: [] };

                    // Warna profesional: biru/navy untuk pemasukan, merah untuk pengeluaran
                    const incomeColor = isDark ? '#3b63b8' : '#2563eb';
                    const expenseColor = isDark ? '#f87171' : '#ef4444';
                    const incomeFill = isDark ? 'rgba(59, 99, 184, 0.08)' : 'rgba(37, 99, 235, 0.08)';
                    const expenseFill = isDark ? 'rgba(248, 113, 113, 0.08)' : 'rgba(239, 68, 68, 0.08)';

                    this.trendChartInstance = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: series.labels ?? [],
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: series.income ?? [],
                                    borderColor: incomeColor,
                                    backgroundColor: incomeFill,
                                    borderWidth: 2.5,
                                    tension: 0.35,
                                    fill: false,
                                    pointRadius: 3.5,
                                    pointBackgroundColor: incomeColor,
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: series.expense ?? [],
                                    borderColor: expenseColor,
                                    backgroundColor: expenseFill,
                                    borderWidth: 2.5,
                                    tension: 0.35,
                                    fill: false,
                                    pointRadius: 3.5,
                                    pointBackgroundColor: expenseColor,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ' ' + ctx.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y),
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { color: textColor, maxTicksLimit: 10 }, grid: { display: false } },
                                y: { ticks: { color: textColor }, grid: { color: gridColor } }
                            }
                        }
                    });
                },

                initCategoryChart() {
                    const canvas = document.getElementById('categoryChart');
                    if (!canvas) return;

                    if (this.categoryChartInstance) {
                        this.categoryChartInstance.destroy();
                        this.categoryChartInstance = null;
                    }

                    const entries = Object.entries(this.categoryExpenses ?? {}).map(([label, value]) => ({ label, value }));
                    if (entries.length === 0) return;

                    const isDark = this.isDarkMode;
                    // Palet profesional & berkelas (tidak mencolok)
                    const palette = isDark
                        ? ['#3b63b8', '#f87171', '#60a5fa', '#a3a3a3', '#c084fc', '#34d399', '#fbbf24', '#f97316', '#94a3b8', '#f472b6']
                        : ['#2563eb', '#3b63b8', '#0ea5e9', '#8b5cf6', '#14b8a6', '#f97316', '#ef4444', '#84cc16', '#64748b', '#f59e0b'];

                    this.categoryChartInstance = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: entries.map(e => e.label),
                            datasets: [{
                                data: entries.map(e => e.value),
                                backgroundColor: entries.map((_, i) => palette[i % palette.length]),
                                borderWidth: 2,
                                borderColor: isDark ? '#0a0a0a' : '#ffffff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: isDark ? 'rgba(255,255,255,0.65)' : '#64748b', boxWidth: 12, boxHeight: 12, padding: 12, font: { size: 11 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                            const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                            return ` ${ctx.label}: Rp ${new Intl.NumberFormat('id-ID').format(ctx.parsed)} (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>

    <!-- NAVBAR (komponen terpadu) -->
    <x-navbar />

    <!-- Toast Notification -->
    @if(session('success'))
    <div id="toast-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-navy-900 rounded-2xl shadow-xl border border-slate-100 dark:border-navy-800">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-white/90">{{ session('success') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <!-- MAIN CONTENT BODY -->
    <div class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-24 md:pb-8 space-y-6 sm:space-y-8 overflow-x-hidden">

        <!-- SKELETON KARTU SALDO — satu blok utuh meniru bentuk kartu (label, saldo, dua tile) -->
        <div x-show="isLoading"
             class="relative overflow-hidden p-4 sm:p-6 bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 dark:from-navy-900 dark:via-navy-900 dark:to-navy-950 text-white rounded-2xl shadow-lg shadow-blue-900/25 dark:shadow-black/50 dark:border dark:border-navy-400/25">
            <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-10 -right-4 w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>

            <div class="relative space-y-4">
                <div class="h-3 w-16 bg-white/15 rounded animate-shimmer"></div>
                <div class="h-12 sm:h-14 w-full max-w-xs bg-white/15 rounded-xl animate-shimmer"></div>
                <div class="pt-4 border-t border-white/15 grid grid-cols-2 gap-2 sm:gap-3">
                    <div class="h-16 rounded-xl bg-white/10 animate-shimmer"></div>
                    <div class="h-16 rounded-xl bg-white/10 animate-shimmer"></div>
                </div>
            </div>
        </div>

        <!-- KARTU SALDO — satu persegi panjang: saldo besar + tombol privasi di kanan, pemasukan/pengeluaran kecil di bawah -->
        <section x-show="!isLoading"
                 class="relative overflow-hidden p-4 sm:p-6 bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 dark:from-navy-900 dark:via-navy-900 dark:to-navy-950 text-white rounded-2xl shadow-lg shadow-blue-900/25 dark:shadow-black/50 dark:border dark:border-navy-400/25">

            <!-- dekorasi lingkaran transparan -->
            <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-10 -right-4 w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>

            <!-- Baris atas: label Saldo + tombol privasi di kanan -->
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-blue-100/90 dark:text-navy-300/90">Saldo</span>

                <button type="button" data-privacy-toggle
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white/15 hover:bg-white/25 text-white transition"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg data-eye-open class="w-5 h-5 block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <svg data-eye-closed class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </button>
            </div>

            <!-- Saldo BESAR -->
            <div class="relative mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight break-words privacy-target"
                 x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)"
                 x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)">
            </div>

            <!-- Pemasukan & Pengeluaran (tile berisi, berdampingan rapat) -->
            <div class="relative grid grid-cols-2 gap-2 sm:gap-3 mt-4 pt-4 border-t border-white/15">

                <div class="flex items-center gap-2.5 sm:gap-3 rounded-xl bg-white dark:bg-navy-800/80 border border-slate-200/80 dark:border-navy-700/60 px-2.5 sm:px-3 py-2.5 min-w-0">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-white/70">Pemasukan</p>
                        <p class="mt-0.5 text-sm sm:text-lg font-bold text-emerald-600 dark:text-emerald-400 leading-tight truncate privacy-target"
                           x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)"
                           x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-3 rounded-xl bg-white dark:bg-navy-800/80 border border-slate-200/80 dark:border-navy-700/60 px-2.5 sm:px-3 py-2.5 min-w-0">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-white/70">Pengeluaran</p>
                        <p class="mt-0.5 text-sm sm:text-lg font-bold text-rose-600 dark:text-rose-400 leading-tight truncate privacy-target"
                           x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)"
                           x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)"></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- BUDGET CARD -->
        <section class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl p-4 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 rounded-xl flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Anggaran Bulanan</h2>
                        <p class="text-xs text-slate-500 dark:text-white/70">{{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
                <button type="button" onclick="openBudgetModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 rounded-xl text-xs font-semibold hover:bg-blue-100 dark:hover:bg-navy-400/20 transition no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    {{ $budget ? 'Ubah' : 'Atur' }}
                </button>
            </div>

            @if($budget)
                @php
                    $now        = \Carbon\Carbon::now();
                    $percentage = $budget->amount > 0 ? min(100, round(($monthlyExpense / $budget->amount) * 100)) : 0;
                    $remaining  = $budget->amount - $monthlyExpense;
                    $isOver     = $remaining < 0;
                    $daysLeft   = max(1, $now->daysInMonth - $now->day + 1);
                    $daily      = $remaining > 0 ? floor($remaining / $daysLeft) : 0;
                    $barColor   = $isOver ? 'bg-rose-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                    $statusTxt  = $isOver ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-white/70';
                    $pctColor   = $isOver ? 'text-rose-600 dark:text-rose-400' : ($percentage >= 80 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                @endphp

                <!-- Angka utama: sisa anggaran (atau kelebihan) -->
                <div class="flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider {{ $statusTxt }}">
                            {{ $isOver ? 'Melebihi anggaran' : 'Sisa anggaran' }}
                        </p>
                        <p class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight break-words {{ $isOver ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} privacy-target"
                           data-amount="{{ $isOver ? '-' : '' }}Rp {{ number_format(abs($remaining), 0, ',', '.') }}">
                            {{ $isOver ? '−' : '' }}Rp {{ number_format(abs($remaining), 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-2xl sm:text-3xl font-extrabold tracking-tight {{ $pctColor }}">{{ $percentage }}%</p>
                        <p class="text-[11px] font-medium text-slate-400 dark:text-white/50">terpakai</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mt-4 w-full h-3.5 bg-slate-100 dark:bg-navy-800 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percentage }}%"></div>
                </div>

                <!-- Ringkasan: batas / terpakai / sisa per hari -->
                <div class="grid grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100 dark:border-navy-800">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-white/50">Batas</p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white truncate privacy-target" data-amount="Rp {{ number_format($budget->amount, 0, ',', '.') }}">Rp {{ number_format($budget->amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-white/50">Terpakai</p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white truncate privacy-target" data-amount="Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-white/50">Sisa / hari</p>
                        <p class="mt-0.5 text-sm font-bold {{ $isOver ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} truncate privacy-target" data-amount="Rp {{ number_format($daily, 0, ',', '.') }}">Rp {{ number_format($daily, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($isOver)
                <p class="mt-4 flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Pengeluaran sudah melewati batas bulan ini. Pertimbangkan untuk menyesuaikan anggaranmu.
                </p>
                @endif
            @else
                <div class="flex flex-col items-center text-center py-6 px-4 bg-slate-50/60 dark:bg-navy-800/40 border border-dashed border-slate-200 dark:border-navy-700 rounded-2xl">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum ada anggaran bulan ini</h3>
                    <p class="text-xs text-slate-500 dark:text-white/70 mt-1 max-w-xs">Tetapkan batas pengeluaran untuk mengontrol keuanganmu lebih disiplin.</p>
                    <button type="button" onclick="openBudgetModal()"
                            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-xs font-semibold shadow-sm shadow-blue-600/20 transition no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Atur Anggaran
                    </button>
                </div>
            @endif
        </section>

        <!-- CHART TREN — line chart gabungan pemasukan + pengeluaran, toggle Minggu/Bulan/Tahun -->
        <section class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white" x-text="(trendPeriod === 'week' ? 'Minggu Ini' : trendPeriod === 'month' ? 'Bulan Ini' : 'Tahun Ini')"></h2>
                    <p class="text-xs text-slate-500 dark:text-white/70">Perbandingan pemasukan dan pengeluaran</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Toggle periode: Minggu / Bulan / Tahun -->
                    <div class="inline-flex items-center gap-1 p-1 bg-slate-100 dark:bg-navy-800 rounded-full no-print">
                        <button type="button" @click="setTrendPeriod('week')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'week' ? 'bg-white dark:bg-navy-400 dark:text-white text-blue-600 shadow-sm' : 'text-slate-500 dark:text-white/70 hover:text-slate-800 dark:hover:text-white'">
                            Minggu
                        </button>
                        <button type="button" @click="setTrendPeriod('month')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'month' ? 'bg-white dark:bg-navy-400 dark:text-white text-blue-600 shadow-sm' : 'text-slate-500 dark:text-white/70 hover:text-slate-800 dark:hover:text-white'">
                            Bulan
                        </button>
                        <button type="button" @click="setTrendPeriod('year')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'year' ? 'bg-white dark:bg-navy-400 dark:text-white text-blue-600 shadow-sm' : 'text-slate-500 dark:text-white/70 hover:text-slate-800 dark:hover:text-white'">
                            Tahun
                        </button>
                    </div>
                    <!-- Legenda pemasukan / pengeluaran -->
                    <span class="flex items-center gap-3 text-xs font-semibold text-slate-600 dark:text-white/80">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 dark:bg-navy-400 flex-shrink-0"></span> Pemasukan</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span> Pengeluaran</span>
                    </span>
                </div>
            </div>

            <div class="h-56 sm:h-72 flex items-center justify-center bg-slate-100 dark:bg-navy-800/50 rounded-xl animate-shimmer"
                 x-show="isLoading">
                <span class="text-slate-400 dark:text-white/50 text-sm">Memuat grafik...</span>
            </div>

            <div class="h-56 sm:h-72" x-show="!isLoading">
                <canvas id="trendChart"></canvas>
            </div>
        </section>

        <!-- CHART KATEGORI -->
        <section class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden">
            <div class="mb-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Pengeluaran per Kategori</h2>
                <p class="text-xs text-slate-500 dark:text-white/70">Lihat di mana uangmu paling banyak terpakai.</p>
            </div>

            <div class="h-56 sm:h-72 flex items-center justify-center bg-slate-100 dark:bg-navy-800/50 rounded-xl animate-shimmer"
                 x-show="isLoading">
                <span class="text-slate-400 dark:text-white/50 text-sm">Memuat grafik...</span>
            </div>

            <div class="h-56 sm:h-72" x-show="!isLoading">
                <canvas id="categoryChart"></canvas>
            </div>
        </section>

        <!-- FILTER — search pill ala Gemini + filter rapi -->
        <section class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl p-4 shadow-sm no-print">
            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-4 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-white/50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..."
                           class="w-full pl-11 pr-4 py-2.5 bg-transparent dark:bg-navy-800/40 border border-slate-200 dark:border-navy-700/80 rounded-full text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 focus:outline-none focus:border-blue-500 dark:focus:border-navy-400 transition">
                </div>

                <div class="lg:col-span-2">
                    <select name="type" class="select-field w-full px-3 py-2 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-full text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-navy-400">
                        <option value="">Semua Tipe</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="category" class="select-field w-full px-3 py-2 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-full text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-navy-400">
                        <option value="">Semua Kategori</option>
                        @foreach (\App\Models\Transaction::allCategories() as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="period" class="select-field w-full px-3 py-2 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-full text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-navy-400">
                        <option value="all">Semua Waktu</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7_days" {{ request('period') == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-full text-xs sm:text-sm font-semibold shadow-sm shadow-blue-600/20 transition">
                        Filter
                    </button>
                    @if(request('search') || request('type') || request('category') || request('period'))
                        <a href="{{ route('transactions.index') }}" title="Reset filter" class="inline-flex items-center justify-center w-9 h-9 bg-white dark:bg-navy-800 text-slate-500 dark:text-white/70 border border-slate-200 dark:border-navy-700 rounded-full text-sm font-semibold hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE TRANSACTIONS (RIWAYAT) -->
        <section id="riwayat" class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl shadow-sm overflow-hidden">

            <!-- Header: Transaksi Terakhir (gaya prototipe section 3) -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-200/80 dark:border-navy-800">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Transaksi Terakhir</h2>
                    <p class="text-xs text-slate-500 dark:text-white/70 mt-0.5">{{ $transactions->total() ?? 0 }} transaksi tercatat</p>
                </div>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 rounded-lg text-xs font-semibold hover:bg-blue-100 dark:hover:bg-navy-400/20 transition no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Tambah
                </a>
            </div>

            <!-- SKELETON LOAD -->
            <div x-show="isLoading">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-navy-800/40 border-b border-slate-200/80 dark:border-navy-800">
                                <th class="py-3.5 px-6"><div class="h-4 w-20 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-24 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6 text-right"><div class="h-4 w-20 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer ml-auto"></div></th>
                                <th class="py-3.5 px-6 text-center"><div class="h-4 w-12 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer mx-auto"></div></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-navy-800">
                            @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-slate-200 dark:bg-navy-700 rounded-xl animate-shimmer"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 w-32 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div>
                                            <div class="h-3 w-20 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6"><div class="h-4 w-24 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6"><div class="h-4 w-16 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6"><div class="h-6 w-20 bg-slate-200 dark:bg-navy-700 rounded-full animate-shimmer"></div></td>
                                <td class="py-4 px-6 text-right"><div class="h-4 w-28 bg-slate-200 dark:bg-navy-700 rounded animate-shimmer ml-auto"></div></td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="h-8 w-8 bg-slate-200 dark:bg-navy-700 rounded-lg animate-shimmer"></div>
                                        <div class="h-8 w-8 bg-slate-200 dark:bg-navy-700 rounded-lg animate-shimmer"></div>
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
                            <tr class="bg-slate-50/80 dark:bg-navy-800/40 border-b border-slate-200/80 dark:border-navy-800 text-slate-500 dark:text-white/70 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Bukti</th>
                                <th class="py-3.5 px-6">Keterangan</th>
                                <th class="py-3.5 px-6">Kategori</th>
                                <th class="py-3.5 px-6">Jenis</th>
                                <th class="py-3.5 px-6 text-right">Nominal</th>
                                <th class="py-3.5 px-6 text-center no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-navy-800 text-xs sm:text-sm">
                            @php
                                $items = $transactions ?? $transaksi ?? [];
                                // Icon & warna kategori sebagai placeholder saat tidak ada gambar bukti
                                $catVisuals = [
                                    'Gaji'               => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/60', 'color' => 'text-emerald-600 dark:text-emerald-400'],
                                    'Bonus'              => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>', 'bg' => 'bg-amber-50 dark:bg-amber-950/60', 'color' => 'text-amber-600 dark:text-amber-400'],
                                    'Bisnis'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>', 'bg' => 'bg-blue-50 dark:bg-blue-950/60', 'color' => 'text-blue-600 dark:text-blue-400'],
                                    'Investasi'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/60', 'color' => 'text-indigo-600 dark:text-indigo-400'],
                                    'Hadiah'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>', 'bg' => 'bg-pink-50 dark:bg-pink-950/60', 'color' => 'text-pink-600 dark:text-pink-400'],
                                    'Makanan & Minuman'  => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>', 'bg' => 'bg-orange-50 dark:bg-orange-950/60', 'color' => 'text-orange-600 dark:text-orange-400'],
                                    'Transportasi'       => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>', 'bg' => 'bg-slate-100 dark:bg-slate-800', 'color' => 'text-slate-600 dark:text-slate-400'],
                                    'Tagihan & Utilitas' => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>', 'bg' => 'bg-yellow-50 dark:bg-yellow-950/60', 'color' => 'text-yellow-600 dark:text-yellow-400'],
                                    'Belanja'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>', 'bg' => 'bg-purple-50 dark:bg-purple-950/60', 'color' => 'text-purple-600 dark:text-purple-400'],
                                    'Hiburan'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>', 'bg' => 'bg-rose-50 dark:bg-rose-950/60', 'color' => 'text-rose-600 dark:text-rose-400'],
                                    'Kesehatan'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>', 'bg' => 'bg-red-50 dark:bg-red-950/60', 'color' => 'text-red-600 dark:text-red-400'],
                                    'Pendidikan'         => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>', 'bg' => 'bg-cyan-50 dark:bg-cyan-950/60', 'color' => 'text-cyan-600 dark:text-cyan-400'],
                                    'Keluarga'           => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>', 'bg' => 'bg-teal-50 dark:bg-teal-950/60', 'color' => 'text-teal-600 dark:text-teal-400'],
                                ];
                                $defaultCat = ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>', 'bg' => 'bg-slate-100 dark:bg-slate-800', 'color' => 'text-slate-500 dark:text-slate-400'];
                            @endphp
                            @forelse ($items as $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-navy-800/50 transition">
                                <td class="py-4 px-6 font-medium text-slate-500 dark:text-white/70 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                                </td>
                                <td class="py-4 px-6">
                                    @php $cv = $catVisuals[$item->category ?? ''] ?? $defaultCat; @endphp
                                    <div class="relative w-8 h-8" x-data="txProof({{ $loop->index }})">
                                        {{-- Icon kategori: selalu tampil, fade-out jika ada gambar --}}
                                        <div class="absolute inset-0 {{ $cv['bg'] }} {{ $cv['color'] }} rounded-lg flex items-center justify-center transition-opacity duration-500"
                                             :class="show ? 'opacity-0' : 'opacity-100'" title="{{ $item->category ?? 'Lainnya' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $cv['path'] !!}</svg>
                                        </div>
                                        {{-- Gambar bukti: fade-in setelah delay --}}
                                        @if(!empty($item->image))
                                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank"
                                           class="absolute inset-0 rounded-lg overflow-hidden border border-slate-200 dark:border-navy-700 transition-opacity duration-500"
                                           :class="show ? 'opacity-100' : 'opacity-0'">
                                            <img src="{{ asset('storage/' . $item->image) }}"
                                                 loading="lazy"
                                                 class="w-full h-full object-cover"
                                                 alt="Bukti">
                                        </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-semibold text-slate-900 dark:text-white">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-navy-400/10 dark:text-navy-300 border border-blue-200/60 dark:border-navy-400/25">
                                        {{ $item->category ?? 'Lainnya' }}
                                    </span>
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
                                <td colspan="7" class="py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 dark:bg-navy-800 text-blue-500 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada transaksi</h3>
                                    <p class="text-xs text-slate-500 dark:text-white/70 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- MOBILE LIST VIEW -->
                <div class="block md:hidden divide-y divide-slate-100 dark:divide-navy-800">
                    @forelse ($items as $item)
                    <div class="p-4" x-data="{ open: false }">

                        <!-- Baris 1: Tanggal + Jenis -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">
                                {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            </span>
                            <span class="font-semibold px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider {{ ($item->type ?? 'income') == 'income' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' }}">
                                {{ ($item->type ?? 'income') == 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </div>

                        <!-- Baris 2: Ringkasan — tap untuk membuka aksi -->
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="tx-actions-{{ $item->id }}"
                                class="w-full flex items-center gap-3 pt-1 text-left group">
                            @php $cv = $catVisuals[$item->category ?? ''] ?? $defaultCat; @endphp
                            <div class="flex-shrink-0 relative w-12 h-12" x-data="txProof({{ $loop->index }})">
                                {{-- Icon kategori --}}
                                <div class="absolute inset-0 {{ $cv['bg'] }} {{ $cv['color'] }} rounded-xl flex items-center justify-center transition-opacity duration-500"
                                     :class="show ? 'opacity-0' : 'opacity-100'" title="{{ $item->category ?? 'Lainnya' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $cv['path'] !!}</svg>
                                </div>
                                {{-- Gambar bukti --}}
                                @if(!empty($item->image))
                                <div class="absolute inset-0 rounded-xl overflow-hidden border border-slate-200 dark:border-navy-700 transition-opacity duration-500"
                                     :class="show ? 'opacity-100' : 'opacity-0'">
                                    <img src="{{ asset('storage/' . $item->image) }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover"
                                         alt="Bukti transaksi">
                                </div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate text-sm sm:text-base">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </p>
                                <p class="text-[11px] font-medium text-blue-600 dark:text-navy-300 mt-0.5">
                                    {{ $item->category ?? 'Lainnya' }}
                                </p>
                            </div>

                            <div class="flex-shrink-0 flex flex-col items-end gap-1">
                                <p class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base whitespace-nowrap privacy-target"
                                   data-amount="Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                                    Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                                </p>
                                <svg class="w-4 h-4 text-slate-400 dark:text-white/40 transition-transform duration-200"
                                     :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Panel aksi: muncul saat baris di-tap (disembunyikan saat print) -->
                        <div id="tx-actions-{{ $item->id }}" x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="no-print flex items-center justify-end gap-2 pt-3 mt-3 border-t border-slate-100/60 dark:border-navy-800/60">
                            @if(!empty($item->image))
                            <a href="{{ asset('storage/' . $item->image) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-navy-800 dark:hover:bg-navy-700 text-slate-600 dark:text-white/80 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Bukti</span>
                            </a>
                            @endif
                            <a href="{{ route('transactions.edit', $item->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-navy-800 dark:hover:bg-navy-700 text-slate-600 dark:text-white/80 rounded-lg text-xs font-semibold transition">
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
                        <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 dark:bg-navy-800 text-blue-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada transaksi</h3>
                        <p class="text-xs text-slate-500 dark:text-white/70 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                    </div>
                    @endforelse
                </div>

                <!-- PAGINATION WITH RAPIH SPACING -->
                @if(isset($transactions) && method_exists($transactions, 'links') && $transactions->hasPages())
                <div class="px-6 py-3 border-t border-slate-200/80 dark:border-navy-800 bg-slate-50/50 dark:bg-navy-950/50">
                    {{ $transactions->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>

        </section>

    </div>

    <!-- BUDGET MODAL -->
    <div id="budgetModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="budget-modal-title">
        <!-- Backdrop Blur -->
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-navy-950/80 backdrop-blur-sm transition-opacity" onclick="closeBudgetModal()"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">

                <!-- Header Modal -->
                <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-navy-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="budget-modal-title" class="text-base font-bold text-slate-900 dark:text-white">Anggaran Bulanan</h3>
                            <p class="text-xs text-slate-500 dark:text-white/70">Atur batas pengeluaran bulan {{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeBudgetModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white/80 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form action="{{ route('budgets.store') }}" method="POST" class="p-6 space-y-4" x-data="budgetForm({{ $budget?->amount ?? 0 }})">
                    @csrf

                    <!-- Ringkasan pengeluaran bulan ini (konteks saat menyetel anggaran) -->
                    @if($monthlyExpense > 0)
                    <div class="flex items-center justify-between px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-100 dark:border-navy-700/60 rounded-xl">
                        <span class="text-xs font-medium text-slate-500 dark:text-white/70">Pengeluaran bulan ini</span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white privacy-target" data-amount="Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div>
                        <label for="budget-amount-input" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-2">Batas Pengeluaran</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-sm">Rp</div>
                            <input type="text" inputmode="numeric" id="budget-amount-input" name="amount"
                                   x-model="displayAmount" @input="amount = onAmountInput($event.target.value)"
                                   placeholder="0" autocomplete="off"
                                   class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-xl text-slate-900 dark:text-white font-bold text-base sm:text-lg tracking-tight placeholder-slate-300 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-navy-400 focus:bg-white dark:focus:bg-navy-800 transition">
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400 dark:text-white/50">Ketik angka, otomatis diformat. Contoh: 1.500.000</p>
                    </div>

                    <!-- Chip nominal cepat -->
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-white/60 mb-2">Pilih cepat</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="preset in presets" :key="preset">
                                <button type="button" @click="setPreset(preset)"
                                        class="px-3 py-1.5 bg-slate-50 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-full text-xs font-semibold text-slate-600 dark:text-white/80 hover:border-blue-400 dark:hover:border-navy-400 hover:text-blue-600 dark:hover:text-navy-300 transition"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(preset)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-navy-800">
                        <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 bg-white dark:bg-navy-800 text-slate-700 dark:text-white/80 border border-slate-200 dark:border-navy-700 rounded-xl text-xs font-semibold hover:bg-slate-50 dark:hover:bg-navy-700 transition">Batal</button>
                        <button type="submit"
                                :disabled="!(parseFloat(amount) > 0)"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-600/20 transition disabled:opacity-40 disabled:cursor-not-allowed">Simpan Anggaran</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function openBudgetModal() {
            document.getElementById('budgetModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeBudgetModal() {
            document.getElementById('budgetModal').classList.add('hidden');
            document.body.style.overflow = '';
        }
        // Tutup modal dengan ESC (anggaran & export) + kunci scroll body
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeBudgetModal();
                closeExportModal();
            }
        });

        // Form anggaran: input Rupiah otomatis + chip nominal cepat
        function budgetForm(initialAmount = 0) {
            const init = Number(initialAmount) || 0;
            return {
                amount: init,
                displayAmount: init > 0 ? new Intl.NumberFormat('id-ID').format(init) : '',
                presets: [500000, 1000000, 2000000, 5000000, 10000000],

                // Format angka mentah -> "1.500.000" (hanya angka & pemisah ribuan)
                onAmountInput(value) {
                    const digits = String(value).replace(/\D/g, '');
                    this.amount = digits === '' ? 0 : parseInt(digits, 10);
                    this.displayAmount = this.amount > 0 ? new Intl.NumberFormat('id-ID').format(this.amount) : '';
                    return this.displayAmount;
                },

                setPreset(value) {
                    this.amount = value;
                    this.displayAmount = new Intl.NumberFormat('id-ID').format(value);
                },
            };
        }
    </script>

    <!-- Alpine component: animasi transisi icon → gambar bukti -->
    <script>
        function txProof(index) {
            return {
                show: false,
                init() {
                    // Cek apakah ada elemen gambar di dalam container ini
                    const hasImage = this.$el.querySelector('img');
                    if (hasImage) {
                        // Staggered delay: 600ms awal + 80ms per item
                        setTimeout(() => { this.show = true; }, 600 + (index * 80));
                    }
                }
            };
        }
    </script>

    <!-- EXPORT MODAL -->
    @include('components.export-modal')

    <!-- AI Chat Widget (disembunyikan di mobile — FAB dashboard sudah punya akses AI) -->
    <x-ai-chat class="hidden md:block" />

</body>
</html>
