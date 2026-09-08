<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]"
      x-data="dashboardApp()"
      x-init="initDashboard()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — kelola pemasukan, pengeluaran, dan anggaran bulanan dalam satu aplikasi pencatatan keuangan pribadi.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

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

    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

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
            background: linear-gradient(90deg, rgba(229, 229, 229, 0.9) 25%, rgba(245, 245, 245, 0.95) 37%, rgba(229, 229, 229, 0.9) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }
        .dark .animate-shimmer {
            background: linear-gradient(90deg, rgba(23, 23, 23, 0.95) 25%, rgba(38, 38, 38, 0.95) 37%, rgba(23, 23, 23, 0.95) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }

        img[loading="lazy"] { background: #F5F5F5; }
        .dark img[loading="lazy"] { background: #262626; }

        [x-cloak] { display: none !important; }

        .select-field {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23737373' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem !important;
        }
        .dark .select-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23A3A3A3' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased flex flex-col">

    <script>
        function dashboardApp() {
            return {
                isLoading: true,
                isDarkMode: document.documentElement.classList.contains('dark'),
                totalBalance: {{ $totalBalance ?? $totalSaldo ?? 0 }},
                totalIncome: {{ $totalIncome ?? $pemasukan ?? 0 }},
                totalExpense: {{ $totalExpense ?? $pengeluaran ?? 0 }},
                categoryExpenses: @json($categoryExpenses ?? []),
                @php
                    $trendDataJson = json_encode($trendData ?? [
                        'week'  => ['labels' => [], 'income' => [], 'expense' => [], 'ranges' => []],
                        'month' => ['labels' => [], 'income' => [], 'expense' => [], 'ranges' => []],
                        'year'  => ['labels' => [], 'income' => [], 'expense' => [], 'ranges' => []],
                    ]);
                @endphp
                trendData: {!! $trendDataJson !!},
                trendPeriod: 'week',
                trendChartInstance: null,
                categoryChartInstance: null,

                initDashboard() {
                    this.isLoading = true;

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

                setTrendPeriod(period) {
                    this.trendPeriod = period;
                    this.initTrendChart();
                },

                initTrendChart() {
                    const canvas = document.getElementById('trendChart');
                    if (!canvas) return;

                    if (this.trendChartInstance) {
                        this.trendChartInstance.destroy();
                        this.trendChartInstance = null;
                    }

                    const isDark = this.isDarkMode;
                    const textColor = isDark ? '#A3A3A3' : '#737373';
                    const gridColor = isDark ? '#262626' : '#E5E5E5';

                    const series = this.trendData[this.trendPeriod] ?? { labels: [], income: [], expense: [], ranges: [] };

                    // Semantic restrained: income = green, expense = red (muted, profesional)
                    const incomeColor = isDark ? '#4ADE80' : '#16A34A';
                    const expenseColor = isDark ? '#F87171' : '#DC2626';
                    const incomeFill = isDark ? 'rgba(74, 222, 128, 0.07)' : 'rgba(22, 163, 74, 0.07)';
                    const expenseFill = isDark ? 'rgba(248, 113, 113, 0.07)' : 'rgba(220, 38, 38, 0.05)';

                    this.trendChartInstance = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: series.labels ?? [],
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: series.income ?? [],
                                    backgroundColor: incomeColor,
                                    borderColor: incomeColor,
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    borderSkipped: 'start',
                                    categoryPercentage: 0.65,
                                    barPercentage: 0.9,
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: series.expense ?? [],
                                    backgroundColor: expenseColor,
                                    borderColor: expenseColor,
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    borderSkipped: 'start',
                                    categoryPercentage: 0.65,
                                    barPercentage: 0.9,
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
                                        title: (items) => {
                                            const idx = items[0]?.dataIndex;
                                            const label = (series.labels ?? [])[idx] ?? '';
                                            const range = (series.ranges ?? [])[idx];
                                            return range ? label + ' · ' + range : label;
                                        },
                                        label: (ctx) => ' ' + ctx.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y),
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { color: textColor, maxTicksLimit: 10 }, grid: { display: false } },
                                y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }
                            }
                        }
                    });
                },

                categoryPalette() {
                    // Grayscale palette — premium monochrome, dipakai chart + legend
                    return this.isDarkMode
                        ? ['#FAFAFA', '#E5E5E5', '#D4D4D4', '#A3A3A3', '#737373', '#525252', '#404040', '#333333', '#262626', '#1a1a1a']
                        : ['#111111', '#262626', '#404040', '#525252', '#737373', '#A3A3A3', '#C9C9C9', '#D4D4D4', '#E5E5E5', '#8a8a8a'];
                },

                categoryLegend() {
                    const entries = Object.entries(this.categoryExpenses ?? {}).map(([label, value]) => ({ label, value: Number(value) || 0 }));
                    const total = entries.reduce((sum, e) => sum + e.value, 0);
                    const pal = this.categoryPalette();
                    return entries
                        .sort((a, b) => b.value - a.value)
                        .map((e, i) => ({ label: e.label, value: e.value, pct: total > 0 ? Math.round((e.value / total) * 100) : 0, color: pal[i % pal.length] }));
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
                    const palette = this.categoryPalette();

                    this.categoryChartInstance = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: entries.map(e => e.label),
                            datasets: [{
                                data: entries.map(e => e.value),
                                backgroundColor: entries.map((_, i) => palette[i % palette.length]),
                                borderWidth: 2,
                                borderColor: isDark ? '#0A0A0A' : '#ffffff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            plugins: {
                                legend: { display: false },
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

    <x-sidebar title="Dashboard" />

    @if(session('success'))
    <div id="toast-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-[#171717] rounded-2xl shadow-sm border border-neutral-200 dark:border-[#333333]">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 bg-green-50 text-green-600 border border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-neutral-700 dark:text-neutral-200">{{ session('success') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="flex-1 w-full min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-8 space-y-6 sm:space-y-8 overflow-x-hidden">

        <div x-show="isLoading"
             class="relative overflow-hidden p-4 sm:p-6 bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm">
            <div class="relative space-y-4">
                <div class="h-3 w-16 bg-neutral-200 dark:bg-[#262626] rounded animate-shimmer"></div>
                <div class="h-12 sm:h-14 w-full max-w-xs bg-neutral-200 dark:bg-[#262626] rounded-xl animate-shimmer"></div>
                <div class="pt-4 border-t border-neutral-200 dark:border-[#333333] grid grid-cols-2 gap-2 sm:gap-3">
                    <div class="h-16 rounded-xl bg-neutral-100 dark:bg-[#262626] animate-shimmer"></div>
                    <div class="h-16 rounded-xl bg-neutral-100 dark:bg-[#262626] animate-shimmer"></div>
                </div>
            </div>
        </div>

        <section x-show="!isLoading" x-cloak
                 class="relative overflow-hidden p-4 sm:p-6 bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm">

            <div class="relative flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Total Saldo</span>
                    <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5">Seluruh pemasukan dikurangi pengeluaran</p>
                </div>

                <button type="button" data-privacy-toggle
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-600 dark:text-neutral-300 transition"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg data-eye-open class="w-[18px] h-[18px] block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <svg data-eye-closed class="w-[18px] h-[18px] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </button>
            </div>

            <div class="relative mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight break-words text-neutral-900 dark:text-neutral-50 privacy-target inline-block"
                 x-bind:data-amount="'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)"
                 x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(totalBalance)">
            </div>

            <div class="relative grid grid-cols-2 gap-2 sm:gap-3 mt-4 pt-4 border-t border-neutral-200 dark:border-[#333333]">

                <div class="flex items-center gap-2.5 sm:gap-3 rounded-xl bg-neutral-50 dark:bg-[#262626]/60 border border-neutral-200 dark:border-[#333333] px-2.5 sm:px-4 py-2.5 sm:py-3 min-w-0">
                    <span class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-green-50 text-green-600 border border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Pemasukan</p>
                        <p class="mt-0.5 text-sm sm:text-base md:text-lg font-bold text-green-600 dark:text-green-400 leading-tight break-all sm:break-words privacy-target"
                           x-bind:data-amount="'+ Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)"
                           x-text="'+ Rp ' + new Intl.NumberFormat('id-ID').format(totalIncome)"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-3 rounded-xl bg-neutral-50 dark:bg-[#262626]/60 border border-neutral-200 dark:border-[#333333] px-2.5 sm:px-4 py-2.5 sm:py-3 min-w-0">
                    <span class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Pengeluaran</p>
                        <p class="mt-0.5 text-sm sm:text-base md:text-lg font-bold text-red-600 dark:text-red-400 leading-tight break-all sm:break-words privacy-target"
                           x-bind:data-amount="'− Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)"
                           x-text="'− Rp ' + new Intl.NumberFormat('id-ID').format(totalExpense)"></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ROW: BUDGET + INCOME VS EXPENSE (side by side di desktop) -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">

        <!-- BUDGET CARD -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 rounded-xl flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Anggaran Bulanan</h2>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
                <button type="button" onclick="openBudgetModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-neutral-100 dark:bg-[#262626] text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-200 dark:hover:bg-[#333333] transition no-print">
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
                    // Progress semantic: calm neutral, amber saat ≥80%, red saat over
                    $barColor   = $isOver ? 'bg-red-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-neutral-900 dark:bg-neutral-100');
                @endphp

                <div class="flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider {{ $isOver ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-500 dark:text-neutral-400' }}">
                            {{ $isOver ? '⚠ Melebihi anggaran' : 'Sisa anggaran' }}
                        </p>
                        <p class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight break-words text-neutral-900 dark:text-neutral-50 privacy-target inline-block"
                           data-amount="{{ $isOver ? '-' : '' }}Rp {{ number_format(abs($remaining), 0, ',', '.') }}">
                            {{ $isOver ? '−' : '' }}Rp {{ number_format(abs($remaining), 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $percentage }}%</p>
                        <p class="text-[11px] font-medium text-neutral-400 dark:text-neutral-500">terpakai</p>
                    </div>
                </div>

                <div class="mt-4 w-full h-2.5 bg-neutral-200 dark:bg-[#262626] rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percentage }}%"></div>
                </div>

                <div class="grid grid-cols-3 gap-3 mt-5 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Batas</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="Rp {{ number_format($budget->amount, 0, ',', '.') }}">Rp {{ number_format($budget->amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Terpakai</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Sisa / hari</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="Rp {{ number_format($daily, 0, ',', '.') }}">Rp {{ number_format($daily, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($isOver)
                <p class="mt-4 flex items-start gap-1.5 text-xs font-semibold text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl px-3 py-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Pengeluaran sudah melewati batas bulan ini. Pertimbangkan untuk menyesuaikan anggaranmu.</span>
                </p>
                @endif
            @else
                <div class="flex flex-col items-center text-center py-6 px-4 bg-neutral-50 dark:bg-[#262626]/40 border border-dashed border-neutral-300 dark:border-[#333333] rounded-2xl">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Belum ada anggaran bulan ini</h3>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 max-w-xs">Tetapkan batas pengeluaran untuk mengontrol keuanganmu lebih disiplin.</p>
                    <button type="button" onclick="openBudgetModal()"
                            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Atur Anggaran
                    </button>
                </div>
            @endif
        </section>

        <!-- CHART TREN -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50" x-text="(trendPeriod === 'week' ? 'Minggu Ini' : trendPeriod === 'month' ? 'Bulan Ini' : 'Tahun Ini')"></h2>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Perbandingan pemasukan dan pengeluaran</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex items-center gap-1 p-1 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-full no-print">
                        <button type="button" @click="setTrendPeriod('week')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'week' ? 'bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900 text-white shadow-sm' : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100'">
                            Minggu
                        </button>
                        <button type="button" @click="setTrendPeriod('month')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'month' ? 'bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900 text-white shadow-sm' : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100'">
                            Bulan
                        </button>
                        <button type="button" @click="setTrendPeriod('year')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'year' ? 'bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900 text-white shadow-sm' : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100'">
                            Tahun
                        </button>
                    </div>
                    <span class="flex items-center gap-3 text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-600 flex-shrink-0"></span> Pemasukan</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-600 flex-shrink-0"></span> Pengeluaran</span>
                    </span>
                </div>
            </div>

            <div class="h-56 sm:h-72 flex items-center justify-center bg-neutral-100 dark:bg-[#262626]/50 rounded-xl animate-shimmer"
                 x-show="isLoading">
                <span class="text-neutral-400 dark:text-neutral-500 text-sm">Memuat grafik...</span>
            </div>

            <div class="h-56 sm:h-72" x-show="!isLoading">
                <canvas id="trendChart"></canvas>
            </div>
        </section>
        </div>

        <!-- ROW: EXPENSE BY CATEGORY + RECENT TRANSACTIONS (side by side di desktop) -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">

        <!-- EXPENSE BY CATEGORY (half width) -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden">
            <div class="mb-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Pengeluaran per Kategori</h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Lihat di mana uangmu paling banyak terpakai.</p>
            </div>

            <div class="h-56 sm:h-72 flex items-center justify-center bg-neutral-100 dark:bg-[#262626]/50 rounded-xl animate-shimmer"
                 x-show="isLoading">
                <span class="text-neutral-400 dark:text-neutral-500 text-sm">Memuat grafik...</span>
            </div>

            <div class="flex-col sm:flex-row flex items-center gap-4" x-show="!isLoading" x-cloak>
                <div class="h-60 w-full sm:w-1/2 shrink-0">
                    <canvas id="categoryChart"></canvas>
                </div>
                <ul class="w-full sm:flex-1 min-w-0 space-y-0.5 max-h-60 overflow-y-auto" aria-label="Rincian kategori">
                    <template x-for="item in categoryLegend()" :key="item.label">
                        <li class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-neutral-50 dark:hover:bg-[#262626]/60 transition">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color: ' + item.color"></span>
                            <span class="flex-1 min-w-0 truncate text-xs font-medium text-neutral-600 dark:text-neutral-300" x-text="item.label"></span>
                            <span class="text-xs font-bold tabular-nums text-neutral-900 dark:text-neutral-50" x-text="item.pct + '%'"></span>
                        </li>
                    </template>
                    <li x-show="categoryLegend().length === 0" class="px-2 py-4 text-center text-xs text-neutral-400 dark:text-neutral-500">Belum ada data pengeluaran.</li>
                </ul>
            </div>
        </section>

        <!-- RECENT TRANSACTIONS (compact, half width) -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Transaksi Terakhir</h2>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">5 aktivitas terbaru</p>
                </div>
                <a href="#riwayat"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-neutral-100 dark:bg-[#262626] hover:bg-neutral-200 dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-xl text-xs font-semibold transition">
                    Lihat semua
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($transactions ?? [] as $item)
                    @if ($loop->index >= 5) @break @endif
                    <div class="flex items-center gap-3 px-4 sm:px-6 py-3 hover:bg-neutral-50 dark:hover:bg-[#262626]/50 transition">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ ($item->type ?? 'income') == 'income' ? 'bg-green-600' : 'bg-red-600' }}"></span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm text-neutral-900 dark:text-neutral-50 truncate">{{ $item->title ?? $item->nama ?? $item->kategori }}</p>
                            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 truncate">{{ $item->category ?? 'Lainnya' }} · {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}</p>
                        </div>
                        <p class="font-extrabold text-sm whitespace-nowrap privacy-target {{ ($item->type ?? 'income') == 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                           data-amount="{{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                            {{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Tidak ada transaksi</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                    </div>
                @endforelse
            </div>
        </section>
        </div>

        <!-- FILTER -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 shadow-sm no-print">
            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-4 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400 dark:text-neutral-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..."
                           class="w-full pl-11 pr-4 py-2.5 bg-white dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:border-neutral-900 dark:focus:border-neutral-100 focus:ring-1 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                </div>

                <div class="lg:col-span-2">
                    <select name="type" class="select-field w-full px-3 py-2 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100">
                        <option value="">Semua Tipe</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="category" class="select-field w-full px-3 py-2 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100">
                        <option value="">Semua Kategori</option>
                        @foreach (\App\Models\Transaction::allCategories() as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="period" class="select-field w-full px-3 py-2 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100">
                        <option value="all">Semua Waktu</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7_days" {{ request('period') == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                        Filter
                    </button>
                    @if(request('search') || request('type') || request('category') || request('period'))
                        <a href="{{ route('transactions.index') }}" title="Reset filter" class="inline-flex items-center justify-center w-9 h-9 bg-white dark:bg-[#262626] text-neutral-500 dark:text-neutral-400 border border-neutral-300 dark:border-[#333333] rounded-xl text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-[#333333] hover:text-neutral-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE TRANSACTIONS -->
        <section id="riwayat" class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">

            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Riwayat Transaksi</h2>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $transactions->total() ?? 0 }} transaksi tercatat</p>
                </div>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold transition no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Tambah
                </a>
            </div>

            <div x-show="isLoading">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-neutral-50 dark:bg-[#262626]/40 border-b border-neutral-200 dark:border-[#333333]">
                                <th class="py-3.5 px-6"><div class="h-4 w-20 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-24 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6"><div class="h-4 w-16 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></th>
                                <th class="py-3.5 px-6 text-right"><div class="h-4 w-20 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer ml-auto"></div></th>
                                <th class="py-3.5 px-6 text-center"><div class="h-4 w-12 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer mx-auto"></div></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-[#333333]">
                            @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-neutral-200 dark:bg-[#333333] rounded-xl animate-shimmer"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 w-32 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div>
                                            <div class="h-3 w-20 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6"><div class="h-4 w-24 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6"><div class="h-4 w-16 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer"></div></td>
                                <td class="py-4 px-6 text-right"><div class="h-4 w-28 bg-neutral-200 dark:bg-[#333333] rounded animate-shimmer ml-auto"></div></td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="h-8 w-8 bg-neutral-200 dark:bg-[#333333] rounded-lg animate-shimmer"></div>
                                        <div class="h-8 w-8 bg-neutral-200 dark:bg-[#333333] rounded-lg animate-shimmer"></div>
                                    </div>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="!isLoading">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left border-collapse">
                        <thead>
                            <tr class="bg-neutral-50 dark:bg-[#262626]/40 border-b border-neutral-200 dark:border-[#333333] text-neutral-500 dark:text-neutral-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Bukti</th>
                                <th class="py-3.5 px-6">Keterangan</th>
                                <th class="py-3.5 px-6">Kategori</th>
                                <th class="py-3.5 px-6 text-right">Nominal</th>
                                <th class="py-3.5 px-6 text-center no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-[#262626] text-xs sm:text-sm">
                            @php
                                $items = $transactions ?? $transaksi ?? [];
                                // Soft semantic icon pills — restrained: income green, investasi violet,
                                // food orange, transport blue, bills amber, shopping violet, lainnya neutral
                                $pillThemes = [
                                    'green'   => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
                                    'violet'  => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
                                    'orange'  => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
                                    'blue'    => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                    'amber'   => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                    'neutral' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]',
                                ];
                                $catPillMap = [
                                    'Gaji' => 'green', 'Bonus' => 'green', 'Bisnis' => 'green',
                                    'Investasi' => 'violet', 'Hadiah' => 'green',
                                    'Makanan & Minuman' => 'orange', 'Transportasi' => 'blue',
                                    'Tagihan & Utilitas' => 'amber', 'Belanja' => 'violet',
                                ];
                                $pillFor = function ($cat) use ($pillThemes, $catPillMap) {
                                    return $pillThemes[$catPillMap[$cat] ?? 'neutral'];
                                };
                                $catVisuals = [
                                    'Gaji'               => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>'],
                                    'Bonus'              => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>'],
                                    'Bisnis'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
                                    'Investasi'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                                    'Hadiah'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>'],
                                    'Makanan & Minuman'  => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>'],
                                    'Transportasi'       => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>'],
                                    'Tagihan & Utilitas' => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                                    'Belanja'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>'],
                                    'Hiburan'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>'],
                                    'Kesehatan'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                                    'Pendidikan'         => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
                                    'Keluarga'           => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                                ];
                                $defaultCat = ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>'];
                            @endphp
                            @forelse ($items as $item)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-[#262626]/50 transition">
                                <td class="py-4 px-6 font-medium text-neutral-500 dark:text-neutral-400 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                                </td>
                                <td class="py-4 px-6">
                                    @php
                                        $cv = $catVisuals[$item->category ?? ''] ?? $defaultCat;
                                        $hasImg = !empty($item->image);
                                        $pill = $pillFor($item->category ?? '');
                                    @endphp
                                    <div class="relative w-8 h-8">
                                        @if($hasImg)
                                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $item->image) }}"
                                                 loading="lazy"
                                                 class="w-8 h-8 rounded-lg object-cover border border-neutral-200 dark:border-[#333333]"
                                                 alt="Bukti">
                                        </a>
                                        @else
                                        <div class="w-8 h-8 {{ $pill }} border rounded-lg flex items-center justify-center"
                                             title="{{ $item->category ?? 'Lainnya' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $cv['path'] !!}</svg>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-semibold text-neutral-900 dark:text-neutral-50">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-200 dark:border-[#333333]">
                                        {{ $item->category ?? 'Lainnya' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right font-extrabold whitespace-nowrap privacy-target {{ ($item->type ?? 'income') == 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                    data-amount="{{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                                    {{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-4 px-6 text-center no-print">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('transactions.edit', $item->id) }}" class="p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-[#262626] rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-[#262626] rounded-lg transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-neutral-100 dark:bg-[#262626] text-neutral-500 dark:text-neutral-400 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Tidak ada transaksi</h3>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="block md:hidden divide-y divide-neutral-100 dark:divide-[#262626]">
                    @forelse ($items as $item)
                    <div class="p-4" x-data="{ open: false }">
                        <div class="flex items-center text-xs">
                            <span class="text-neutral-400 font-medium">
                                {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            </span>
                        </div>

                        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="tx-actions-{{ $item->id }}"
                                class="w-full flex items-center gap-3 pt-1 text-left group">
                            @php
                                $cv = $catVisuals[$item->category ?? ''] ?? $defaultCat;
                                $hasImg = !empty($item->image);
                                $pill = $pillFor($item->category ?? '');
                            @endphp
                            @if($hasImg)
                            <a href="{{ asset('storage/' . $item->image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $item->image) }}"
                                     loading="lazy"
                                     class="w-12 h-12 rounded-xl object-cover border border-neutral-200 dark:border-[#333333]"
                                     alt="Bukti transaksi">
                            </a>
                            @else
                            <div class="flex-shrink-0 w-12 h-12 {{ $pill }} border rounded-xl flex items-center justify-center"
                                 title="{{ $item->category ?? 'Lainnya' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $cv['path'] !!}</svg>
                            </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-neutral-900 dark:text-neutral-50 truncate text-sm sm:text-base">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </p>
                                <p class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 mt-0.5">
                                    {{ $item->category ?? 'Lainnya' }}
                                </p>
                            </div>

                            <div class="flex-shrink-0 flex flex-col items-end gap-1">
                                <p class="font-extrabold text-sm sm:text-base whitespace-nowrap privacy-target {{ ($item->type ?? 'income') == 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                   data-amount="{{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}">
                                    {{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount ?? $item->nominal ?? 0, 0, ',', '.') }}
                                </p>
                                <svg class="w-4 h-4 text-neutral-400 dark:text-neutral-500 transition-transform duration-200"
                                     :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <div id="tx-actions-{{ $item->id }}" x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="no-print flex items-center justify-end gap-2 pt-3 mt-3 border-t border-neutral-100 dark:border-[#262626]">
                            @if(!empty($item->image))
                            <a href="{{ asset('storage/' . $item->image) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Bukti</span>
                            </a>
                            @endif
                            <a href="{{ route('transactions.edit', $item->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit</span>
                            </a>

                            <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-lg text-xs font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-neutral-100 dark:bg-[#262626] text-neutral-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Tidak ada transaksi</h3>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                    </div>
                    @endforelse
                </div>

                @if(isset($transactions) && method_exists($transactions, 'links') && $transactions->hasPages())
                <div class="px-6 py-3 border-t border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#0A0A0A]">
                    {{ $transactions->links('vendor.pagination.tailwind') }}
                </div>
                @endif
            </div>

        </section>

    </div>

    <!-- BUDGET MODAL -->
    <div id="budgetModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="budget-modal-title">
        <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeBudgetModal()"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm transition-all sm:my-8 sm:w-full sm:max-w-md">

                <div class="flex items-center justify-between p-6 border-b border-neutral-200 dark:border-[#333333]">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="budget-modal-title" class="text-base font-bold text-neutral-900 dark:text-neutral-50">Anggaran Bulanan</h3>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Atur batas pengeluaran bulan {{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeBudgetModal()" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('budgets.store') }}" method="POST" class="p-6 space-y-4" x-data="budgetForm({{ $budget?->amount ?? 0 }})">
                    @csrf

                    @if($monthlyExpense > 0)
                    <div class="flex items-center justify-between px-4 py-3 bg-neutral-50 dark:bg-[#262626]/60 border border-neutral-200 dark:border-[#333333] rounded-xl">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Pengeluaran bulan ini</span>
                        <span class="text-sm font-bold text-neutral-900 dark:text-neutral-50 privacy-target" data-amount="Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div>
                        <label for="budget-amount-input" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-2">Batas Pengeluaran</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400 font-bold text-sm">Rp</div>
                            <input type="text" inputmode="numeric" id="budget-amount-input" name="amount"
                                   x-model="displayAmount" @input="amount = onAmountInput($event.target.value)"
                                   placeholder="0" autocomplete="off"
                                   class="w-full pl-10 pr-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-neutral-900 dark:text-neutral-50 font-bold text-base sm:text-lg tracking-tight placeholder-neutral-300 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 focus:bg-white dark:focus:bg-[#262626] transition">
                        </div>
                        <p class="mt-1.5 text-[11px] text-neutral-400 dark:text-neutral-500">Ketik angka, otomatis diformat. Contoh: 1.500.000</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">Pilih cepat</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="preset in presets" :key="preset">
                                <button type="button" @click="setPreset(preset)"
                                        class="px-3 py-1.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-full text-xs font-semibold text-neutral-600 dark:text-neutral-300 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-white transition"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(preset)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                        <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-50 dark:hover:bg-[#333333] transition">Batal</button>
                        <button type="submit"
                                :disabled="!(parseFloat(amount) > 0)"
                                class="px-5 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed">Simpan Anggaran</button>
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
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeBudgetModal();
                closeExportModal();
            }
        });

        function budgetForm(initialAmount = 0) {
            const init = Number(initialAmount) || 0;
            return {
                amount: init,
                displayAmount: init > 0 ? new Intl.NumberFormat('id-ID').format(init) : '',
                presets: [500000, 1000000, 2000000, 5000000, 10000000],

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

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

    </body>
</html>
