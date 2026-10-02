<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Atur anggaran bulanan dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Anggaran - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Anggaran - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Anggaran" :back="route('transactions.index')" minimal />

    <x-flash />


    @php
        $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());
        $now        = \Carbon\Carbon::now();
        $summary    = new \App\Services\BudgetSummaryService;
        // Rumus sama dengan kartu dashboard (BudgetSummaryService::progress).
        $p          = $summary->progress((float) ($budget?->amount ?? 0), (float) $monthlyExpense, $now);
        $percentage = $p['percentage'];
        $remaining  = $p['remaining'];
        $isOver     = $p['isOver'];
        $daily      = $p['daily'];
        // Nama kelas daisyUI ditulis LITERAL, bukan dirangkai dari variabel —
        // scanner Tailwind membaca file sebagai teks, jadi kelas hasil
        // penggabungan tidak pernah terlihat dan ikut ter-purge
        // (lihat components/flash.blade.php).
        $barVariant = $isOver ? 'progress-error' : ($percentage >= 80 ? 'progress-warning' : 'progress-success');
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        @if ($isDemo)
        {{-- `alert` dipinjam untuk bentuk + radius-nya saja, tata letaknya tetap
             baris (utility `flex` menimpa grid bawaan daisyUI); warna lewat token
             semantic supaya kontrasnya pasti di kedua mode. --}}
        <div class="alert flex items-start gap-2.5 p-3.5 mb-6 border border-warning/30 bg-warning/10 text-base-content">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-warning">
                Mode demo — anggaran hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="link">Daftar gratis</a> untuk mencoba mengatur anggaran.
            </p>
        </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="min-w-0">
                <p class="text-sm font-bold tracking-tight text-base-content">{{ $now->isoFormat('MMMM YYYY') }}</p>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ $categoryBudgets->count() }} anggaran kategori{{ $budget ? ' · 1 anggaran total' : '' }}
                </p>
            </div>
            @unless ($isDemo)
            <button type="button" onclick="openBudgetModal()" class="btn btn-primary btn-sm no-print">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Tambah Anggaran
            </button>
            @endunless
        </div>

        <!-- RINGKASAN ANGGARAN -->
        <section class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-5 mb-6">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-box bg-info/10 text-info border border-info/20 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-base-content">Anggaran Bulanan</h2>
                        <p class="text-xs text-base-content/60">Batas pengeluaran keseluruhan</p>
                    </div>
                </div>
                @if ($isDemo)
                <span class="badge badge-ghost gap-1.5 font-semibold no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Demo
                </span>
                @endif
            </div>

            @if($budget)
                <div class="flex items-end justify-between gap-3 mt-5">
                    <div class="min-w-0">
                        <p class="stat-title text-xs font-medium {{ $isOver ? 'text-base-content' : 'text-base-content/60' }}">
                            {{ $isOver ? 'Melebihi anggaran' : 'Sisa anggaran' }}
                        </p>
                        <p class="stat-value text-2xl sm:text-3xl whitespace-normal tracking-tight leading-tight break-words tabular-nums privacy-target inline-block"
                           data-amount="{{ $isOver ? '-' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}">
                            {{ $isOver ? '−' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="stat-value text-xl sm:text-2xl tracking-tight tabular-nums">{{ $percentage }}%</p>
                        <p class="stat-desc text-xs font-medium mt-0.5">terpakai</p>
                    </div>
                </div>

                {{-- Komponen `progress` daisyUI = elemen <progress> sungguhan,
                     jadi panjang isian dibaca dari atribut value/max (bukan
                     style="width: X%" pada sebuah div). Sumber angkanya tetap
                     sama: `$percentage` dihitung server oleh
                     BudgetSummaryService, tanpa JS. Ambang warnanya juga sama
                     seperti sebelumnya: < 80% hijau, 80-99% kuning,
                     >= 100% merah. --}}
                <progress class="progress {{ $barVariant }} w-full h-2.5 mt-3" value="{{ $percentage }}" max="100">{{ $percentage }}%</progress>

                <div class="stats stats-vertical sm:stats-horizontal w-full bg-transparent mt-4 pt-4 border-t border-base-300">
                    <div class="stat py-4 px-0 sm:px-4 sm:py-0">
                        <p class="stat-title text-[11px] font-medium">Batas</p>
                        <p class="stat-value text-sm whitespace-normal break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($budget->amount) }}">{{ \App\Services\AmountFormatter::compact($budget->amount) }}</p>
                    </div>
                    <div class="stat py-4 px-0 sm:px-4 sm:py-0">
                        <p class="stat-title text-[11px] font-medium">Terpakai</p>
                        <p class="stat-value text-sm whitespace-normal break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}">{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}</p>
                    </div>
                    <div class="stat py-4 px-0 sm:px-4 sm:py-0">
                        <p class="stat-title text-[11px] font-medium">Sisa / hari</p>
                        <p class="stat-value text-sm whitespace-normal break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($daily) }}">{{ \App\Services\AmountFormatter::compact($daily) }}</p>
                    </div>
                </div>

                @if($isOver)
                <p class="alert flex items-start gap-1.5 text-error border border-error/30 bg-error/10 p-3 mt-4">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Pengeluaran sudah melewati batas bulan ini. Pertimbangkan untuk menyesuaikan anggaranmu.</span>
                </p>
                @endif
            @else
                <div class="card items-center text-center py-6 px-4 mt-4 bg-base-100/40 border border-dashed border-base-300">
                    <div class="w-12 h-12 rounded-box bg-info/10 text-info border border-info/20 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-base-content">Belum ada anggaran bulan ini</h3>
                    <p class="text-xs text-base-content/60 mt-1 max-w-xs">Tetapkan batas pengeluaran untuk mengontrol keuanganmu lebih disiplin.</p>
                    @unless ($isDemo)
                    <button type="button" onclick="openBudgetModal()" class="btn btn-primary btn-sm mt-4 no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Atur Anggaran
                    </button>
                    @endunless
                </div>
            @endif
        </section>

        <!-- ANGGARAN PER KATEGORI -->
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                <h2 class="text-sm font-bold tracking-tight text-base-content">Anggaran per Kategori</h2>
                <span class="badge badge-ghost badge-sm font-medium">{{ $categoryBudgets->count() }} item</span>
            </div>

            {{-- Daftar anggaran per kategori diubah dari tumpukan kartu jadi TABEL
                 daisyUI. Alasannya: dengan halaman yang sudah lebar, kolom
                 (kategori / batas / terpakai / sisa / progres) jauh lebih
                 mudah dibandingkan secara vertikal, dan tidak ada lagi baris tinggi
                 yang boros. Di layar sempit tabel tetap bisa digeser
                 horizontal lewat `overflow-x-auto`. --}}
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-right whitespace-nowrap">Batas</th>
                            <th class="text-right whitespace-nowrap">Terpakai</th>
                            <th class="text-right whitespace-nowrap">Sisa</th>
                            <th class="w-48">Progres</th>
                            <th class="text-right whitespace-nowrap no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryBudgets as $cb)
                            @php
                                $spent = $categorySpent[$cb->category] ?? 0;
                                $cp    = $summary->progress((float) $cb->amount, (float) $spent, $now);
                                $cPct  = $cp['percentage'];
                                $cOver = $cp['isOver'];
                                $cBar  = $cOver ? 'progress-error' : ($cPct >= 80 ? 'progress-warning' : 'progress-success');
                            @endphp
                            <tr>
                                <td class="font-semibold text-sm">{{ $cb->category }}</td>

                                <td class="text-right whitespace-nowrap tabular-nums text-sm privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact((float) $cb->amount) }}">{{ \App\Services\AmountFormatter::compact($cb->amount) }}</td>

                                <td class="text-right whitespace-nowrap tabular-nums text-sm privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact((float) $spent) }}">{{ \App\Services\AmountFormatter::compact($spent) }}</td>

                                <td class="text-right whitespace-nowrap tabular-nums text-sm font-semibold {{ $cOver ? 'text-error' : 'text-base-content' }} privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact((float) $cp['remaining']) }}">{{ \App\Services\AmountFormatter::compact($cp['remaining']) }}</td>

                                <td>
                                    <div class="flex items-center gap-2">
                                        <progress class="progress {{ $cBar }} h-2 flex-1" value="{{ $cPct }}" max="100">{{ $cPct }}%</progress>
                                        <span class="text-[11px] font-bold tabular-nums w-16 text-right {{ $cOver ? 'text-error' : 'text-base-content/70' }}">{{ $cPct }}%</span>
                                    </div>
                                    @if ($cOver)
                                        <p class="text-[11px] text-error mt-1">Melebihi batas</p>
                                    @endif
                                </td>

                                <td class="text-right whitespace-nowrap no-print">
                                    @unless ($isDemo)
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="#" onclick="event.preventDefault(); openBudgetModal({{ Js::from($cb->category) }}, {{ $cb->amount }})"
                                               class="btn btn-xs btn-outline">Ubah</a>
                                            <form action="{{ route('budgets.destroy', $cb) }}" method="POST" onsubmit="return confirm('Hapus anggaran {{ addslashes($cb->category) }} bulan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline btn-error">Hapus</button>
                                            </form>
                                        </div>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center">
                                    <p class="text-xs text-base-content/60">Belum ada anggaran per kategori.</p>
                                    <p class="text-[11px] text-base-content/60 mt-0.5">Tambah anggaran khusus per kategori bila ingin batas yang lebih ketat untuk pos tertentu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </section>

        <p class="text-[11px] text-base-content/60 text-center mt-6 px-4 leading-relaxed">
            Anggaran dihitung ulang otomatis per bulan. Progress punya 3 status: aman, mendekati batas (80%), dan melebihi batas.
        </p>
    </div>

    <!-- BUDGET MODAL — `modal`/`modal-box` daisyUI. `.modal` tersembunyi
         secara bawaan dan tampil hanya saat punya kelas `modal-open`, jadi
         openBudgetModal()/closeBudgetModal() me-toggle `modal-open`, bukan
         `hidden`. Id tetap sama, tidak ada selector yang berubah. -->
    <div id="budgetModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="budget-modal-title">
        <div class="modal-box p-0 sm:max-w-md">

            <div class="flex items-center justify-between p-6 border-b border-base-300">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-box bg-info/10 text-info border border-info/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="budget-modal-title" class="text-base font-bold text-base-content">Anggaran Bulanan</h3>
                        <p class="text-xs text-base-content/60">Atur batas pengeluaran bulan {{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeBudgetModal()" class="btn btn-ghost btn-sm btn-circle text-base-content/60">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form x-data="budgetForm({{ $budget?->amount ?? 0 }})" action="{{ route('budgets.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                @if($monthlyExpense > 0)
                {{-- `stat` untuk pasangan label/nilai. `flex` menimpa
                     `display: inline-grid` bawaan daisyUI supaya label dan
                     nominal bisa berjajar, bukan bertumpuk. --}}
                <div class="stat flex flex-row items-center justify-between gap-4 bg-base-100 border border-base-300 p-3.5">
                    <span class="stat-title text-xs">Pengeluaran bulan ini</span>
                    <span class="stat-value text-sm whitespace-normal text-right privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}">{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}</span>
                </div>
                @endif

                <div>
                    <label for="budget-amount-input" class="block text-xs font-semibold text-base-content/70 mb-2">Batas Pengeluaran</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/60 font-bold text-sm">Rp</div>
                        <input type="text" inputmode="numeric" id="budget-amount-input" name="amount"
                               x-model="displayAmount" @input="amount = onAmountInput($event.target.value)"
                               placeholder="0" autocomplete="off"
                               class="input input-bordered w-full pl-10 pr-4 font-bold text-base sm:text-lg tracking-tight placeholder:text-base-content/60">
                    </div>
                    <p class="mt-1.5 text-[11px] text-base-content/60">Ketik angka, otomatis diformat. Contoh: 1.500.000</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-base-content/60 mb-2">Pilih cepat</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="preset in presets" :key="preset">
                            <button type="button" @click="setPreset(preset)"
                                    class="btn btn-sm btn-outline rounded-full"
                                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(preset)">
                            </button>
                        </template>
                    </div>
                </div>

                @php
                    // <x-select-dropdown> butuh [ nilai => label ]; <select> dulu
                    // memakai @foreach langsung ke $expenseCategories.
                    $budgetCategoryOptions = ['' => 'Keseluruhan (semua pengeluaran)'];
                    foreach ($expenseCategories as $cat) { $budgetCategoryOptions[$cat] = $cat; }

                    $budgetMonthOptions = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $budgetMonthOptions[(string) $m] = $m === 1 ? '1 Bulan Ini' : $m.' Bulan';
                    }
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="budget-category" class="block text-xs font-semibold text-base-content/70 mb-2">Kategori</label>
                        {{-- on-change kosong: form ini disubmit normal (POST), bukan filter AJAX.
                                     Kalau dibiarkan applyFilters(), kategori yang
                                     dipilih akan memicu permintaan yang tak berarti. --}}
                        <x-select-dropdown id="budget-category" name="category" size="md"
                                           :value="$budget?->category ?? ''"
                                           :options="$budgetCategoryOptions"
                                           placeholder="Keseluruhan (semua pengeluaran)"
                                           label="Kategori anggaran"
                                           :on-change="''" />
                        <p class="mt-1.5 text-[11px] text-base-content/60">Pilih kategori untuk anggaran khusus, mis. "Makanan &amp; Minuman".</p>
                    </div>
                    <div>
                        <label for="budget-months" class="block text-xs font-semibold text-base-content/70 mb-2">Berlaku Selama</label>
                                                <x-select-dropdown id="budget-months" name="months" size="md"
                                           :value="(string) ($budget?->months ?? 1)"
                                           :options="$budgetMonthOptions"
                                           placeholder="1 Bulan"
                                           label="Berlaku selama berapa bulan"
                                           :on-change="''" />
                        <p class="mt-1.5 text-[11px] text-base-content/60">Terapkan nominal yang sama ke beberapa bulan sekaligus.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-300">
                    <button type="button" onclick="closeBudgetModal()" class="btn btn-outline btn-sm">Batal</button>
                    <button type="submit" :disabled="!(parseFloat(amount) > 0)"
                            class="btn btn-primary btn-sm disabled:opacity-40">Simpan Anggaran</button>
                </div>
            </form>

        </div>

        <div class="modal-backdrop bg-neutral-950/60 backdrop-blur-sm" onclick="closeBudgetModal()"></div>
    </div>

    <script>
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

        function openBudgetModal(category = '', amount = null) {
            // daisyUI `.modal` hanya tampil saat punya kelas `modal-open`
            // (bukan `hidden`), jadi itu yang di-toggle di sini.
            document.getElementById('budgetModal').classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            const catSel = document.getElementById('budget-category');
            if (catSel) catSel.value = category || '';
            const monSel = document.getElementById('budget-months');
            if (monSel) monSel.value = '1';
            if (amount) {
                const input = document.getElementById('budget-amount-input');
                const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                setter.call(input, new Intl.NumberFormat('id-ID').format(Number(amount)));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        function closeBudgetModal() {
            document.getElementById('budgetModal').classList.remove('modal-open');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            closeBudgetModal();
            // Tombol "Export Laporan" di sidebar memanggil openExportModal() di
            // halaman ini juga, jadi modalnya ikut harus ketutup.
            if (typeof closeExportModal === 'function') closeExportModal();
        });
    </script>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>