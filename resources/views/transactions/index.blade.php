<!DOCTYPE html>
<html lang="id" class="h-full bg-base-200"
      x-data="dashboardApp()"
      x-init="initDashboard()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="dompetku — kelola pemasukan, pengeluaran, dan anggaran bulanan dalam satu aplikasi pencatatan keuangan pribadi.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Transaksi - dompetku">
    <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Transaksi - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/chart.js'])

    <style>
        body { overflow-x: hidden; }
        @media print {
            body { background-color: #ffffff !important; color: #000000 !important; }
            header, form, button, .no-print, nav, #exportModal, .fixed { display: none !important; }
            .print-only { display: block !important; }
            .shadow-sm, .shadow-md, .shadow-xl, .shadow-lg { box-shadow: none !important; border: 1px solid #ccc !important; }
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

<body class="app-shell-content min-h-full bg-base-200 text-base-content font-sans antialiased flex flex-col">

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
                analisisTab: 'tren',
                // Di-seed dari server supaya render pertama benar, lalu
                // di-update dari event `filters-applied` setiap fetch.
                showAnalytics: @json((bool) ($showAnalytics ?? true)),
                trendChartInstance: null,
                categoryChartInstance: null,

                // Format Rupiah ringkas: di bawah 1 juta tampil lengkap;
                // dari 1 juta ke atas dipadatkan per besaran (rb -> juta ->
                // miliar -> triliun) dengan tulisan lengkap supaya tidak
                // ambigu. Angka persis selalu tersedia lewat attribute title.
                rupiah(n) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
                },
                rupiahCompact(n) {
                    const abs = Math.abs(Number(n) || 0);
                    if (abs < 1_000_000) {
                        return this.rupiah(abs);
                    }
                    const units = [
                        [1e12, 'triliun'],
                        [1e9, 'miliar'],
                        [1e6, 'juta'],
                    ];
                    for (const [base, word] of units) {
                        if (abs >= base) {
                            const frac = abs < base * 100 ? 2 : 0;
                            const val = new Intl.NumberFormat('id-ID', { maximumFractionDigits: frac }).format(abs / base);
                            return 'Rp ' + val + ' ' + word;
                        }
                    }
                    return this.rupiah(abs);
                },

                initDashboard() {
                    this.isLoading = true;

                    window.addEventListener('theme-changed', (e) => {
                        this.isDarkMode = e.detail?.isDark ?? document.documentElement.classList.contains('dark');
                        if (this.analisisTab === 'tren') {
                            this.initTrendChart();
                        } else {
                            this.initCategoryChart();
                        }
                    });

                    // Filter tanpa reload: app.js men-swap isi tabel lalu
                    // menyiarkan hasil baru lewat event ini. Angka Ringkasan dan
                    // grafik donat ikut berubah supaya tidak bertentangan dengan
                    // isi tabel.
                    window.addEventListener('filters-applied', (e) => {
                        const d = e.detail || {};
                        // Penting: `isLoading` controlling skeleton tabel. Kalau
                        // tidak di-clear di sini, setiap filter subsequent akan
                        // menampilkan skeleton tanpa pernah tampil lagi -- karena
                        // isLoading hanya di-reset sekali saat Chart.js load.
                        this.isLoading = false;
                        if (d.stats) {
                            this.totalBalance = Number(d.stats.totalBalance) || 0;
                            this.totalIncome = Number(d.stats.totalIncome) || 0;
                            this.totalExpense = Number(d.stats.totalExpense) || 0;
                        }
                        this.showAnalytics = d.showAnalytics !== false;
                        if (Array.isArray(d.categoryExpenses)) {
                            this.categoryExpenses = d.categoryExpenses;
                            this.initCategoryChart();
                        }
                        // Nilai baru datang dari Alpine, jadi mask privasi
                        // perlu dipasang ulang di sini.
                        if (typeof window.renderPrivacyUI === 'function') {
                            this.$nextTick(() => window.renderPrivacyUI());
                        }
                    });

                    // Perf: tanpa delay buatan 500ms. Render secepatnya setelah
                    // Alpine ready, tapi tunggu window.Chart (Vite module async).
                    this.$nextTick(() => {
                        this.initChartsWhenReady(0);
                    });
                },

                initChartsWhenReady(attempt) {
                    if (typeof window.Chart !== 'undefined') {
                        this.isLoading = false;
                        this.$nextTick(() => {
                            if (this.analisisTab === 'tren') {
                                this.initTrendChart();
                            } else {
                                this.initCategoryChart();
                            }
                        });
                        return;
                    }
                    // Fallback: setelah ~5 detik tampilkan konten walau grafik gagal load,
                    // agar halaman tidak terjebak di skeleton.
                    if (attempt >= 50) {
                        this.isLoading = false;
                        return;
                    }
                    setTimeout(() => this.initChartsWhenReady((attempt || 0) + 1), 100);
                },

                setAnalisisTab(tab) {
                    this.analisisTab = tab;
                    // Chart.js butuh canvas yang terlihat (bukan display:none), jadi
                    // chart di-render ulang pas tab benar-benar aktif.
                    this.$nextTick(() => {
                        if (tab === 'tren') {
                            this.initTrendChart();
                        } else {
                            this.initCategoryChart();
                        }
                    });
                },

                setTrendPeriod(period) {
                    this.trendPeriod = period;
                    const cached = this.trendData ? this.trendData[period] : null;
                    if (cached && Array.isArray(cached.labels) && cached.labels.length > 0) {
                        this.initTrendChart();
                        return;
                    }
                    // Lazy-load: 'month' & 'year' hanya di-fetch saat tab diklik.
                    // 'week' sudah tersedia dari server saat load awal.
                    fetch('/transactions/trend?period=' + encodeURIComponent(period), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then((r) => {
                            if (!r.ok) throw new Error('trend failed: ' + r.status);
                            return r.json();
                        })
                        .then((data) => {
                            if (data && Array.isArray(data.labels) && Array.isArray(data.income) && Array.isArray(data.expense)) {
                                this.trendData[period] = {
                                    labels: data.labels,
                                    income: data.income,
                                    expense: data.expense,
                                    ranges: Array.isArray(data.ranges) ? data.ranges : [],
                                };
                            }
                        })
                        .catch(() => {
                            // Biarkan series kosong — chart tampil kosong, halaman tetap jalan.
                            // Tidak boleh melempar error ke user.
                        })
                        .finally(() => {
                            this.initTrendChart();
                        });
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
                                        label: (ctx) => ' ' + ctx.dataset.label + ': ' + this.rupiahCompact(ctx.parsed.y),
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { color: textColor, maxTicksLimit: 10 }, grid: { display: false } },
                                y: { beginAtZero: true, ticks: { color: textColor, callback: (v) => this.rupiahCompact(v) }, grid: { color: gridColor } }
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
                                // Gap antar irisan dibuat lewat borderColor = warna
                                // kartu, bukan bg halaman. Kalau pakai bg halaman
                                // (#0A0A0A) di dark mode, celahnya muncul sebagai
                                // ring gelap di atas kartu #171717.
                                borderWidth: 3,
                                borderColor: isDark ? '#171717' : '#ffffff',
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
                                            return ` ${ctx.label}: ${this.rupiahCompact(ctx.parsed)} (${pct}%)`;
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

    <x-flash />


    <div class="flex-1 w-full min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-8 space-y-6 sm:space-y-8 overflow-x-hidden">

        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-base-content">Transaksi</h1>
                <p class="mt-1 text-sm text-base-content/60">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}</p>
            </div>
        </div>

        <div x-show="isLoading"
             class="relative overflow-hidden p-4 sm:p-5 lg:p-6 bg-base-100 border border-base-300 lg:border-0 rounded-2xl shadow-sm">
            <div class="relative space-y-5">
                <div class="h-3 w-20 skeleton"></div>
                <!-- Struktur placeholder meniru isi asli (label + nominal) supaya
                     tinggi kartu tidak melompat saat isLoading -> false. -->
                <div class="grid grid-cols-1 md:grid-cols-[1.25fr_1fr_1fr] gap-y-4 md:gap-y-0 md:gap-x-6 pt-5 border-t border-base-300">
                    <div class="text-center md:text-left space-y-2">
                        <div class="h-3 w-16 mx-auto md:mx-0 skeleton"></div>
                        <div class="h-9 lg:h-10 w-40 sm:w-44 mx-auto md:mx-0 skeleton"></div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-base-300/70 rounded-2xl bg-base-200/80 bg-base-200/50 py-3 md:contents">
                        <div class="px-3 text-center md:text-left md:px-3 lg:px-6 space-y-2">
                            <div class="h-3 w-16 mx-auto md:mx-0 skeleton"></div>
                            <div class="h-5 lg:h-7 w-20 mx-auto md:mx-0 skeleton"></div>
                        </div>
                        <div class="px-3 text-center md:text-left md:px-3 lg:px-6 space-y-2">
                            <div class="h-3 w-20 mx-auto md:mx-0 skeleton"></div>
                            <div class="h-5 lg:h-7 w-20 mx-auto md:mx-0 skeleton"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section x-show="!isLoading" x-cloak
                 class="relative overflow-hidden p-4 sm:p-5 lg:p-6 bg-base-100 border border-base-300 lg:border-0 rounded-2xl shadow-sm">

            <div class="relative flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <span class="text-xs font-medium text-base-content/60">Ringkasan</span>
                </div>

                <div class="flex items-center gap-2 shrink-0 no-print">
                    <a href="{{ route('transactions.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-base-content px-3 py-1.5 text-xs font-semibold text-base-100 shadow-sm transition hover:bg-base-content/80">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Tambah
                    </a>
                    <button type="button" data-privacy-toggle
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-base-200 hover:bg-base-300 hover:bg-base-content/10 text-base-content/70 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-base-content dark:focus-visible:ring-base-300"
                            aria-label="Sembunyikan saldo" title="Sembunyikan saldo">
                        <svg data-lock-open class="w-[18px] h-[18px] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        <svg data-lock-closed class="w-[18px] h-[18px] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Ringkasan.
                 Mobile: Saldo jadi hero besar & rata tengah (pola aplikasi
                 keuangan), lalu Pemasukan + Pengeluaran disatukan dalam satu
                 panel abu-abu 2 kolom yang membagi lebar — tidak lagi semua
                 menempel ke kiri. Desktop (md+) tetap 3 kolom dengan divide-x
                 dan teks rata kiri. Panel memakai `md:contents` agar kotaknya
                 meniadakan diri sendiri di desktop dan kedua anaknya kembali
                 menjadi grid item langsung (padding kolomnya yang berlaku). -->
            <div class="relative grid grid-cols-1 md:grid-cols-[1.25fr_1fr_1fr] md:divide-x md:divide-base-300 dark:md:divide-[#333333] mt-5 pt-5 border-t border-base-300">

                <div class="min-w-0 text-center md:text-left md:pr-3 lg:pr-6">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60 md:text-xs md:font-medium md:normal-case md:tracking-normal">Saldo</p>
                    <p class="mt-1 whitespace-nowrap text-3xl lg:text-4xl font-bold tracking-tight leading-tight tabular-nums text-base-content privacy-target"
                       x-bind:title="rupiah(totalBalance)"
                       x-bind:data-amount="rupiahCompact(totalBalance)"
                       x-text="rupiahCompact(totalBalance)"></p>
                </div>

                <div class="mt-4 md:mt-0 grid grid-cols-2 divide-x divide-base-300/70 rounded-2xl bg-base-200/80 bg-base-200/50 py-3 md:contents">
                    <div class="min-w-0 px-3 text-center md:text-left md:px-3 lg:px-6">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60 md:text-xs md:font-medium md:normal-case md:tracking-normal">Pemasukan</p>
                        <p class="mt-1 whitespace-nowrap text-sm md:text-lg lg:text-xl font-bold tabular-nums text-green-600 dark:text-green-400 privacy-target"
                           x-bind:data-amount="'+ ' + rupiahCompact(totalIncome)"
                           x-bind:title="'+ ' + rupiah(totalIncome)"
                           x-text="'+ ' + rupiahCompact(totalIncome)"></p>
                    </div>
                    <div class="min-w-0 px-3 text-center md:text-left md:px-3 lg:px-6">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60 md:text-xs md:font-medium md:normal-case md:tracking-normal">Pengeluaran</p>
                        <p class="mt-1 whitespace-nowrap text-sm md:text-lg lg:text-xl font-bold tabular-nums text-red-600 dark:text-red-400 privacy-target"
                           x-bind:data-amount="'− ' + rupiahCompact(totalExpense)"
                           x-bind:title="'− ' + rupiah(totalExpense)"
                           x-text="'− ' + rupiahCompact(totalExpense)"></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Saat transaksi masih sedikit (tanpa filter): tampilkan empty-state
             yang ramah alih-alih gugusan kartu angka nol & grafik kosong.
             Semua konten analitik baru muncul penuh setelah data cukup. -->
        {{-- Kartu anggaran + analisis. Visibility-nya dikendalikan Alpine lewat
             `showAnalytics` supaya ikut berubah saat filter berubah tanpa reload.
             Nilainya di-seed dari $showAnalytics (dihitung server) supaya render
             pertama sudah benar tanpa menunggu JS. --}}
        <div x-show="showAnalytics" x-cloak>
        <!-- ANGGARAN + ANALISIS, masing-masing separuh lebar (lg:grid-cols-2).
             Anggaran tetap ringkas: hanya batas, terpakai, sisa, dan pace
             harian — pengelolaan penuhnya ada di /budgets.
             "Transaksi Terakhir" dihapus: isinya persis 5 baris pertama tabel
             Riwayat di bawah, jadi tidak menambah informasi apa pun. -->
        <div class="grid grid-cols-1 lg:grid-cols-2 items-start gap-6">

        <!-- ANGGARAN (ringkas) -->
        <section class="bg-base-100 border border-base-300 lg:border-0 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold tracking-tight text-base-content">Anggaran</h2>
                        <p class="text-[11px] text-base-content/60">{{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
                <a href="{{ route('budgets.index') }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-base-200 hover:bg-base-300 text-base-content/70 border border-base-300 rounded-lg text-xs font-semibold transition no-print flex-shrink-0">
                    Kelola
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>

            @if($budget)
                @php
                    $now = \Carbon\Carbon::now();
                    // Rumus sama dengan halaman /budgets (BudgetSummaryService::progress).
                    $p = (new \App\Services\BudgetSummaryService)->progress((float) $budget->amount, (float) $monthlyExpense, $now);
                    $percentage = $p['percentage'];
                    $remaining  = $p['remaining'];
                    $isOver     = $p['isOver'];
                    $daily      = $p['daily'];
                    // Progress semantic: calm neutral, amber saat ≥80%, red saat over
                    $barColor   = $isOver ? 'bg-red-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-base-content');
                @endphp

                <div class="mt-4 flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium {{ $isOver ? 'text-red-600 dark:text-red-400' : 'text-base-content/60' }}">
                            {{ $isOver ? 'Melebihi anggaran' : 'Sisa anggaran' }}
                        </p>
                        <p class="mt-0.5 text-2xl sm:text-3xl font-bold tracking-tight leading-tight tabular-nums privacy-target {{ $isOver ? 'text-red-600 dark:text-red-400' : 'text-base-content' }}"
                           data-amount="{{ $isOver ? '−' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}">
                            {{ $isOver ? '−' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}
                        </p>
                    </div>
                    <p class="flex-shrink-0 text-xl sm:text-2xl font-bold tabular-nums leading-none pb-1 {{ $isOver ? 'text-red-600 dark:text-red-400' : 'text-base-content' }}">{{ $percentage }}%</p>
                </div>

                <div class="mt-3 w-full h-2.5 bg-base-300 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percentage }}%"></div>
                </div>

                {{-- Tiga angka dikasih lebar sama rata; kartu ini sudah selebar
                     Analisis, jadi muat tanpa dipadatkan. --}}
                <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-base-300">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-base-content/40">Batas</p>
                        <p class="mt-0.5 text-sm font-bold text-base-content break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($budget->amount) }}">{{ \App\Services\AmountFormatter::compact($budget->amount) }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-base-content/40">Terpakai</p>
                        <p class="mt-0.5 text-sm font-bold text-base-content break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}">{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-base-content/40">Sisa / hari</p>
                        <p class="mt-0.5 text-sm font-bold text-base-content break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($daily) }}">{{ \App\Services\AmountFormatter::compact($daily) }}</p>
                    </div>
                </div>

                @if($isOver)
                <p class="mt-3 text-xs font-semibold text-red-700 dark:text-red-400">
                    Batas bulan ini terlampaui —
                    <a href="{{ route('budgets.index') }}" class="underline underline-offset-2 hover:text-red-800 dark:hover:text-red-300">kelola anggaran</a>.
                </p>
                @endif
            @elseif($categoryBudgets->isNotEmpty())
                <p class="mt-3.5 px-3 py-2.5 text-[11px] text-base-content/60 bg-base-200/40 border border-base-300 rounded-lg">
                    {{ $categoryBudgets->count() }} anggaran per kategori aktif, belum ada batas keseluruhan —
                    <a href="{{ route('budgets.index') }}" class="font-semibold text-base-content/80 underline underline-offset-2">lanjutkan di halaman Anggaran</a>.
                </p>
            @else
                <div class="mt-3.5 flex items-center justify-between gap-3 rounded-lg border border-dashed border-base-300 bg-base-200/40 px-3 py-2.5">
                    <p class="text-[11px] font-semibold text-base-content/70">Belum ada anggaran bulan ini</p>
                    <a href="{{ route('budgets.index') }}"
                       class="flex-shrink-0 inline-flex items-center px-2.5 py-1.5 bg-base-content hover:bg-base-content/80 text-base-100 rounded-lg text-[11px] font-semibold transition no-print">
                        Atur
                    </a>
                </div>
            @endif
        </section>

        <!-- ANALISIS: tren & kategori digabung dalam satu kartu ber-tab supaya
             mobile tetap pendek. Di desktop kini duduk di sebelah kanan kartu
             Anggaran, jadi grafiknya jauh lebih lega. Subjudul ikut berubah
             mengikuti tab aktif — tidak ada dua lapis judul yang mengulang info. -->
        <section class="bg-base-100 border border-base-300 lg:border-0 rounded-2xl p-4 sm:p-5 shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold tracking-tight text-base-content">Analisis</h2>
                    <p class="text-xs text-base-content/60"
                       x-text="analisisTab === 'tren' ? 'Perbandingan pemasukan dan pengeluaran' : 'Pengeluaran per kategori'">Perbandingan pemasukan dan pengeluaran</p>
                </div>
                <div class="inline-flex items-center gap-1 p-1 bg-base-200 border border-base-300 rounded-full no-print flex-shrink-0 self-start sm:self-auto">
                    <button type="button" @click="setAnalisisTab('tren')"
                            class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                            :class="analisisTab === 'tren' ? 'bg-base-content text-base-100 shadow-sm' : 'text-base-content/60 hover:text-base-content dark:hover:text-base-content'">
                        Tren
                    </button>
                    <button type="button" @click="setAnalisisTab('kategori')"
                            class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                            :class="analisisTab === 'kategori' ? 'bg-base-content text-base-100 shadow-sm' : 'text-base-content/60 hover:text-base-content dark:hover:text-base-content'">
                        Kategori
                    </button>
                </div>
            </div>

            <!-- Panel: Tren. Judul periode dihapus karena sudah tertera pada
                 tombol periode yang sedang aktif. -->
            <div x-show="analisisTab === 'tren'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="inline-flex items-center gap-1 p-1 bg-base-200 border border-base-300 rounded-full no-print">
                        <button type="button" @click="setTrendPeriod('week')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'week' ? 'bg-base-content text-base-100 shadow-sm' : 'text-base-content/60 hover:text-base-content dark:hover:text-base-content'">
                            Minggu
                        </button>
                        <button type="button" @click="setTrendPeriod('month')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'month' ? 'bg-base-content text-base-100 shadow-sm' : 'text-base-content/60 hover:text-base-content dark:hover:text-base-content'">
                            Bulan
                        </button>
                        <button type="button" @click="setTrendPeriod('year')"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
                                :class="trendPeriod === 'year' ? 'bg-base-content text-base-100 shadow-sm' : 'text-base-content/60 hover:text-base-content dark:hover:text-base-content'">
                            Tahun
                        </button>
                    </div>
                    <span class="flex items-center gap-3 text-xs font-semibold text-base-content/70">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-600 flex-shrink-0"></span> Pemasukan</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-600 flex-shrink-0"></span> Pengeluaran</span>
                    </span>
                </div>

                <div class="h-64 sm:h-72 flex items-center justify-center skeleton"
                     x-show="isLoading">
                    <span class="text-base-content/40 text-sm">Memuat grafik...</span>
                </div>

                <div class="h-64 sm:h-72" x-show="!isLoading">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Panel: Kategori. Judul dihapus karena label tab sudah menyebut
                 isinya, dan daftar rincian di samping grafiknya sudah menandai
                 kategori mana yang paling besar. -->
            <div x-show="analisisTab === 'kategori'" x-cloak>
                <div class="h-64 sm:h-72 flex items-center justify-center skeleton"
                     x-show="isLoading">
                    <span class="text-base-content/40 text-sm">Memuat grafik...</span>
                </div>

                <div class="flex-col sm:flex-row flex items-center gap-4" x-show="!isLoading">
                    <div class="h-60 w-full sm:w-1/2 shrink-0">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <ul class="w-full sm:flex-1 min-w-0 space-y-1.5 max-h-60 overflow-y-auto" aria-label="Rincian kategori">
                        <template x-for="item in categoryLegend()" :key="item.label">
                            <li class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-base-300/60 transition">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color: ' + item.color"></span>
                                <span class="flex-1 min-w-0 truncate text-xs font-medium text-base-content/70" x-text="item.label"></span>
                                <span class="text-xs font-bold tabular-nums text-base-content" x-text="item.pct + '%'"></span>
                            </li>
                        </template>
                        <li x-show="categoryLegend().length === 0" class="px-2 py-4 text-center text-xs text-base-content/40">Belum ada data pengeluaran.</li>
                    </ul>
                </div>
            </div>
        </section>
        </div>

        </div>

        <div x-show="!showAnalytics" x-cloak>
        <section class="bg-base-100 border border-base-300 lg:border-0 rounded-2xl p-6 sm:p-8 shadow-sm text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-base-content text-base-100 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-bold tracking-tight text-base-content">{{ $transactions->total() === 0 ? 'Mulai Kelola Keuanganmu' : 'Transaksi Masih Sedikit' }}</h2>
            <p class="mx-auto mt-1.5 max-w-md text-sm text-base-content/60">{{ $transactions->total() === 0 ? 'Catat pemasukan dan pengeluaran pertama agar ringkasan, grafik, dan laporan muncul otomatis di dashboard ini.' : 'Tambahkan beberapa transaksi lagi agar grafik tren dan analisis kategori tampil di dashboard ini.' }}</p>
            <div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-base-content px-5 py-2.5 text-sm font-semibold text-base-100 shadow-sm transition hover:bg-base-content/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Catat Transaksi
                </a>
                <a href="{{ route('transactions.import') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-base-300 bg-base-100 px-5 py-2.5 text-sm font-semibold text-base-content/80 transition hover:bg-base-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Import CSV
                </a>
            </div>
            <p class="mt-4 text-xs text-base-content/40">Saldo di atas otomatis terisi setiap kamu mencatat transaksi.</p>
        </section>
        </div>

        <!-- FILTER -->
        <section class="bg-base-100 border border-base-300 lg:border-0 rounded-2xl p-4 sm:p-5 shadow-sm no-print">
            <form id="filterForm" method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-3 relative">
                    <label for="filterSearch" class="sr-only">Cari transaksi</label>
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" id="filterSearch" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..." autocomplete="off"
                           class="w-full pl-11 pr-4 py-2.5 bg-base-100 border border-base-300 rounded-xl text-xs sm:text-sm text-base-content placeholder:text-base-content/40 focus:outline-none focus:border-base-content focus:border-base-content focus:ring-1 focus:ring-base-content dark:focus:ring-base-300 transition">
                </div>

                {{-- Tipe memakai segoup radio asli, bukan tombol JS: tetap ikut
                     submit kalau JS mati, panah kiri/kanan langsung memindah
                     pilihan, dan terbaca screen reader sebagai radiogroup. --}}
                <div class="lg:col-span-4">
                    <fieldset>
                        <legend class="sr-only">Filter berdasarkan tipe transaksi</legend>
                        <div class="flex w-full gap-1 p-1 rounded-xl bg-base-200 border border-base-300">
                            @php
                                $typeFilters = ['' => 'Semua', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
                                $currentType = (string) request('type', '');
                            @endphp
                            @foreach ($typeFilters as $typeValue => $typeLabel)
                                @php $typeId = 'filterType' . ($typeValue === '' ? 'All' : ucfirst($typeValue)); @endphp
                                {{-- Tiap pasangan input+label dibungkus div sendiri.
                                     Kalau tidak, `peer-checked` akan menimpa label
                                     berikutnya begitu satu radio aktif. --}}
                                <div class="flex-1 min-w-0">
                                    <input type="radio" name="type" id="{{ $typeId }}" value="{{ $typeValue }}"
                                           class="peer sr-only" @checked($currentType === (string) $typeValue)
                                           onchange="applyFilters()">
                                    <label for="{{ $typeId }}"
                                           class="block px-1.5 sm:px-2 py-2 text-center text-xs sm:text-sm font-semibold rounded-lg cursor-pointer select-none truncate text-base-content/60 transition
                                                  peer-checked:bg-base-content peer-checked:text-base-100 peer-checked:shadow-sm
                                                  peer-focus-visible:ring-2 peer-focus-visible:ring-base-content dark:peer-focus-visible:ring-base-300">
                                        {{ $typeLabel }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="lg:col-span-2">
                    @php
                        $categoryFilterOptions = ['' => 'Semua Kategori'];
                        foreach (\App\Models\Category::allNames(auth()->id()) as $cat) { $categoryFilterOptions[$cat] = $cat; }
                    @endphp
                    <label for="filterCategory" class="sr-only">Filter berdasarkan kategori</label>
                    <select id="filterCategory" name="category" onchange="applyFilters()"
                            class="select select-bordered select-sm w-full">
                        @foreach ($categoryFilterOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('category', '') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="filterPeriod" class="sr-only">Filter berdasarkan periode waktu</label>
                    <select id="filterPeriod" name="period" onchange="applyFilters()"
                            class="select select-bordered select-sm w-full">
                        @foreach (['all' => 'Semua Waktu', 'today' => 'Hari Ini', '7_days' => '7 Hari Terakhir', 'this_month' => 'Bulan Ini'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('period', 'all') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kolom harus berjumlah pas 12, kalau tidak tombol Reset akan
                     terdorong ke baris sendiri dan terlihat berantakan:
                     3 (cari) + 4 (tipe) + 2 (kategori) + 2 (periode) + 1 (reset). --}}
                <div class="lg:col-span-1 flex gap-2 items-center justify-end">
                    <a id="filterReset" href="{{ route('transactions.index') }}" title="Reset filter" aria-label="Reset semua filter"
                       class="inline-flex items-center justify-center h-[38px] w-[38px] sm:h-[42px] sm:w-[42px] shrink-0 bg-base-100 text-base-content/60 border border-base-300 rounded-xl text-sm font-semibold hover:bg-base-200 hover:bg-base-content/10 hover:text-base-content transition {{ (request('search') || request('type') || request('category') || (request('period') && request('period') !== 'all')) ? '' : 'hidden' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                    <p id="filterStatus" role="status" aria-live="polite" class="sr-only"></p>
                </div>
            </form>
        </section>

        <!-- TABLE TRANSACTIONS -->
        <section id="riwayat" class="bg-base-100 border border-base-300 lg:border-0 rounded-2xl shadow-sm overflow-hidden">

            <div class="flex items-center justify-between px-4 sm:px-5 py-4 border-b border-base-300">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-base-content">Riwayat Transaksi</h2>
                    <p id="riwayatCount" class="text-xs text-base-content/60 mt-0.5">{{ $transactions->total() ?? 0 }} transaksi tercatat</p>
                </div>
                <div class="flex items-center gap-2">
                <a href="{{ route('transactions.trashed') }}" title="Lihat transaksi di Sampah"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-base-200 hover:bg-base-300 hover:bg-base-content/10 text-base-content/70 rounded-xl text-xs font-semibold transition no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Sampah
                </a>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-base-content hover:bg-base-content/80 text-base-100 rounded-xl text-xs font-semibold transition no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Tambah
                </a>
                </div>
            </div>

            <div x-show="isLoading">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-base-200/40 border-b border-base-300">
                                <th class="py-3.5 px-4"><div class="h-4 w-20 skeleton"></div></th>
                                <th class="py-3.5 px-4"><div class="h-4 w-16 skeleton"></div></th>
                                <th class="py-3.5 px-4"><div class="h-4 w-24 skeleton"></div></th>
                                <th class="py-3.5 px-4"><div class="h-4 w-16 skeleton"></div></th>
                                <th class="py-3.5 px-4 text-right"><div class="h-4 w-20 skeleton ml-auto"></div></th>
                                <th class="py-3.5 px-4 text-center"><div class="h-4 w-12 skeleton mx-auto"></div></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 skeleton"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 w-32 skeleton"></div>
                                            <div class="h-3 w-20 skeleton"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4"><div class="h-4 w-24 skeleton"></div></td>
                                <td class="py-4 px-4"><div class="h-4 w-16 skeleton"></div></td>
                                <td class="py-4 px-4 text-right"><div class="h-4 w-28 skeleton ml-auto"></div></td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="skeleton h-8 w-8 w-8 rounded-lg"></div>
                                        <div class="skeleton h-8 w-8 w-8 rounded-lg"></div>
                                    </div>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="riwayatTable" x-show="!isLoading">
@include('transactions.partials.table', ['transactions' => $transactions])
            </div>

        </section>

    </div>

<!-- BUDGET MODAL dipindah ke halaman /budget (resources/views/budgets/index.blade.php) -->

    <script>
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && typeof closeExportModal === 'function') closeExportModal();
        });
    </script>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

    </body>
</html>
