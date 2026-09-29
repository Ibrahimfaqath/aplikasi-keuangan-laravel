<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

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

    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
            } catch (e) {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#0A0A0A';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">

    <x-sidebar title="Anggaran" :back="route('transactions.index')" minimal />

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
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

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-[#171717] rounded-2xl shadow-sm border border-red-200 dark:border-red-500/30">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-neutral-700 dark:text-neutral-200">{{ session('error') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @php
        $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());
        $now        = \Carbon\Carbon::now();
        $percentage = $budget?->amount > 0 ? min(100, round(($monthlyExpense / $budget->amount) * 100)) : 0;
        $remaining  = ($budget?->amount ?? 0) - $monthlyExpense;
        $isOver     = $remaining < 0;
        $daysLeft   = max(1, $now->daysInMonth - $now->day + 1);
        $daily      = $remaining > 0 ? floor($remaining / $daysLeft) : 0;
        $barColor   = $isOver ? 'bg-red-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-neutral-900 dark:bg-neutral-100');
    @endphp

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        @if ($isDemo)
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3.5 mb-6">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                Mode demo — anggaran hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-amber-900 dark:hover:text-amber-200">Daftar gratis</a> untuk mencoba mengatur anggaran.
            </p>
        </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="min-w-0">
                <p class="text-sm font-bold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $now->isoFormat('MMMM YYYY') }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    {{ $categoryBudgets->count() }} anggaran kategori{{ $budget ? ' · 1 anggaran total' : '' }}
                </p>
            </div>
            @unless ($isDemo)
            <button type="button" onclick="openBudgetModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition no-print">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Tambah Anggaran
            </button>
            @endunless
        </div>

        <!-- RINGKASAN ANGGARAN -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 rounded-xl flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-neutral-900 dark:text-neutral-50">Anggaran Bulanan</h2>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Batas pengeluaran keseluruhan</p>
                    </div>
                </div>
                @if ($isDemo)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-xl text-xs font-semibold no-print">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Demo
                </span>
                @endif
            </div>

            @if($budget)
                <div class="flex items-end justify-between gap-3 mt-5">
                    <div class="min-w-0">
                        <p class="text-xs font-medium {{ $isOver ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-500 dark:text-neutral-400' }}">
                            {{ $isOver ? 'Melebihi anggaran' : 'Sisa anggaran' }}
                        </p>
                        <p class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight leading-tight break-words tabular-nums text-neutral-900 dark:text-neutral-50 privacy-target inline-block"
                           data-amount="{{ $isOver ? '-' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}">
                            {{ $isOver ? '−' : '' }}{{ \App\Services\AmountFormatter::compact(abs($remaining)) }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xl sm:text-2xl font-bold tracking-tight tabular-nums text-neutral-900 dark:text-neutral-50">{{ $percentage }}%</p>
                        <p class="mt-0.5 text-xs font-medium text-neutral-400 dark:text-neutral-500">terpakai</p>
                    </div>
                </div>

                <div class="mt-3 w-full h-2.5 bg-neutral-200 dark:bg-[#262626] rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percentage }}%"></div>
                </div>

                <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-neutral-400 dark:text-neutral-500">Batas</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($budget->amount) }}">{{ \App\Services\AmountFormatter::compact($budget->amount) }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-neutral-400 dark:text-neutral-500">Terpakai</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}">{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-neutral-400 dark:text-neutral-500">Sisa / hari</p>
                        <p class="mt-0.5 text-sm font-bold text-neutral-900 dark:text-neutral-50 break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($daily) }}">{{ \App\Services\AmountFormatter::compact($daily) }}</p>
                    </div>
                </div>

                @if($isOver)
                <p class="mt-4 flex items-start gap-1.5 text-xs font-semibold text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl px-3 py-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Pengeluaran sudah melewati batas bulan ini. Pertimbangkan untuk menyesuaikan anggaranmu.</span>
                </p>
                @endif
            @else
                <div class="flex flex-col items-center text-center py-6 px-4 mt-4 bg-neutral-50 dark:bg-[#262626]/40 border border-dashed border-neutral-300 dark:border-[#333333] rounded-2xl">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Belum ada anggaran bulan ini</h3>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 max-w-xs">Tetapkan batas pengeluaran untuk mengontrol keuanganmu lebih disiplin.</p>
                    @unless ($isDemo)
                    <button type="button" onclick="openBudgetModal()"
                            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Atur Anggaran
                    </button>
                    @endunless
                </div>
            @endif
        </section>

        <!-- ANGGARAN PER KATEGORI -->
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold tracking-tight text-neutral-900 dark:text-neutral-50">Anggaran per Kategori</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $categoryBudgets->count() }} item</span>
            </div>

            <div class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse($categoryBudgets as $cb)
                    @php
                        $spent      = $categorySpent[$cb->category] ?? 0;
                        $cPct       = $cb->amount > 0 ? min(100, round(($spent / $cb->amount) * 100)) : 0;
                        $cRemaining = $cb->amount - $spent;
                        $cOver      = $cRemaining < 0;
                        $cBar       = $cOver ? 'bg-red-500' : ($cPct >= 80 ? 'bg-amber-500' : 'bg-neutral-900 dark:bg-neutral-100');
                    @endphp
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-50">{{ $cb->category }}</p>
                            @unless ($isDemo)
                            <div class="flex items-center gap-1.5 no-print">
                                <a href="#" onclick="event.preventDefault(); openBudgetModal({{ Js::from($cb->category) }}, {{ $cb->amount }})"
                                   class="px-2.5 py-1 text-[11px] font-semibold text-neutral-600 dark:text-neutral-300 border border-neutral-300 dark:border-[#333333] rounded-lg hover:bg-neutral-100 dark:hover:bg-[#333333] transition">Ubah</a>
                                <form action="{{ route('budgets.destroy', $cb) }}" method="POST" onsubmit="return confirm('Hapus anggaran {{ addslashes($cb->category) }} bulan ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition">Hapus</button>
                                </form>
                            </div>
                            @endunless
                        </div>
                        <div class="w-full h-2 bg-neutral-200 dark:bg-[#262626] rounded-full overflow-hidden mt-2.5">
                            <div class="h-full rounded-full transition-all duration-500 {{ $cBar }}" style="width: {{ $cPct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 break-all sm:break-words privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($spent) }} dari {{ \App\Services\AmountFormatter::compact($cb->amount) }}">{{ \App\Services\AmountFormatter::compact($spent) }} dari {{ \App\Services\AmountFormatter::compact($cb->amount) }}</p>
                            <p class="text-[11px] font-bold {{ $cOver ? 'text-red-600 dark:text-red-400' : 'text-neutral-600 dark:text-neutral-300' }}">
                                {{ $cPct }}%{{ $cOver ? ' · melebihi' : '' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 px-5 text-center">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Belum ada anggaran per kategori.</p>
                        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5 px-6">Tambah anggaran khusus per kategori bila ingin mengendalikan pengeluaran tertentu (mis. "Makanan &amp; Minuman").</p>
                    </div>
                @endforelse
            </div>
        </section>

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Anggaran dihitung ulang otomatis per bulan. Progress punya 3 status: aman, mendekati batas (80%), dan melebihi batas.
        </p>
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

                <form x-data="budgetForm({{ $budget?->amount ?? 0 }})" action="{{ route('budgets.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf

                    @if($monthlyExpense > 0)
                    <div class="flex items-center justify-between px-4 py-3 bg-neutral-50 dark:bg-[#262626]/60 border border-neutral-200 dark:border-[#333333] rounded-xl">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Pengeluaran bulan ini</span>
                        <span class="text-sm font-bold text-neutral-900 dark:text-neutral-50 privacy-target" data-amount="{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}">{{ \App\Services\AmountFormatter::compact($monthlyExpense) }}</span>
                    </div>
                    @endif

                    <div>
                        <label for="budget-amount-input" class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Batas Pengeluaran</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400 font-bold text-sm">Rp</div>
                            <input type="text" inputmode="numeric" id="budget-amount-input" name="amount"
                                   x-model="displayAmount" @input="amount = onAmountInput($event.target.value)"
                                   placeholder="0" autocomplete="off"
                                   class="w-full pl-10 pr-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-neutral-900 dark:text-neutral-100 font-bold text-base sm:text-lg tracking-tight placeholder-neutral-300 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 focus:bg-white dark:focus:bg-[#262626] transition">
                        </div>
                        <p class="mt-1.5 text-[11px] text-neutral-400 dark:text-neutral-500">Ketik angka, otomatis diformat. Contoh: 1.500.000</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">Pilih cepat</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="preset in presets" :key="preset">
                                <button type="button" @click="setPreset(preset)"
                                        class="px-3 py-1.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-full text-xs font-semibold text-neutral-600 dark:text-neutral-300 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-white transition"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(preset)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="budget-category" class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Kategori</label>
                            <select id="budget-category" name="category"
                                    class="w-full px-3 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                                <option value="">Keseluruhan (semua pengeluaran)</option>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-[11px] text-neutral-400 dark:text-neutral-500">Pilih kategori untuk anggaran khusus, mis. "Makanan &amp; Minuman".</p>
                        </div>
                        <div>
                            <label for="budget-months" class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Berlaku Selama</label>
                            <select id="budget-months" name="months"
                                    class="w-full px-3 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($m === 1)>{{ $m === 1 ? '1 Bulan Ini' : $m.' Bulan' }}</option>
                                @endfor
                            </select>
                            <p class="mt-1.5 text-[11px] text-neutral-400 dark:text-neutral-500">Terapkan nominal yang sama ke beberapa bulan sekaligus.</p>
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
            document.getElementById('budgetModal').classList.remove('hidden');
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
            document.getElementById('budgetModal').classList.add('hidden');
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