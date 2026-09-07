<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Perbarui detail transaksi atau ganti bukti foto di DompetKu.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Edit Transaksi - DompetKu">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Edit Transaksi - DompetKu</title>

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { overflow-x: hidden; }
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
        .date-field {
            position: relative;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23737373' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.15em 1.15em;
            padding-right: 2.5rem !important;
        }
        .dark .date-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23A3A3A3' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
        }
        /* Satu ikon saja: indikator bawaan browser dibuat transparan
           tetapi tetap mencakup area klik kanan agar date picker native jalan */
        .date-field::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: 2.75rem;
            height: 100%;
            cursor: pointer;
        }
        .btn-upload.active {
            background-color: #111111 !important;
            border-color: #111111 !important;
            color: #ffffff !important;
        }
        .dark .btn-upload.active {
            background-color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            color: #0A0A0A !important;
        }
        .cat-chip.active {
            background-color: #111111 !important;
            border-color: #111111 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.15);
        }
        .dark .cat-chip.active {
            background-color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            color: #0A0A0A !important;
        }
        .cat-chip.active > span:first-child {
            background-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }
        .dark .cat-chip.active > span:first-child {
            background-color: rgba(0, 0, 0, 0.08) !important;
            color: #0A0A0A !important;
        }
    </style>
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">

    <x-sidebar title="Edit Transaksi" />

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

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.index') }}" aria-label="Kembali ke daftar transaksi"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center">
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Edit Transaksi</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Perbarui detail transaksi kamu</p>
            </div>
        </div>

        <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                    Jenis Transaksi
                </label>

                <div class="grid grid-cols-2 gap-3 p-1 bg-neutral-100 dark:bg-[#262626] rounded-xl border border-neutral-200 dark:border-[#333333]">
                    <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all border border-transparent has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-500/10 has-[:checked]:text-neutral-900 dark:has-[:checked]:text-white has-[:checked]:shadow-sm has-[:checked]:border-green-600 dark:has-[:checked]:border-green-500/50 text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100">
                        <input type="radio" name="type" value="income" class="sr-only" {{ old('type', $transaction->type) == 'income' ? 'checked' : '' }} required>
                        <span class="w-6 h-6 rounded-lg bg-green-600 text-white flex items-center justify-center text-sm font-mono font-bold">+</span>
                        <span class="text-xs sm:text-sm font-bold">Pemasukan</span>
                    </label>

                    <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all border border-transparent has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-500/10 has-[:checked]:text-neutral-900 dark:has-[:checked]:text-white has-[:checked]:shadow-sm has-[:checked]:border-red-600 dark:has-[:checked]:border-red-500/50 text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100">
                        <input type="radio" name="type" value="expense" class="sr-only" {{ old('type', $transaction->type) == 'expense' ? 'checked' : '' }} required>
                        <span class="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center text-sm font-mono font-bold">−</span>
                        <span class="text-xs sm:text-sm font-bold">Pengeluaran</span>
                    </label>
                </div>
                @error('type')
                    <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <main class="bg-white dark:bg-[#171717] rounded-2xl border border-neutral-200 dark:border-[#333333] shadow-sm overflow-hidden p-6 sm:p-8 space-y-6">

                <div class="space-y-2">
                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                        Nominal Transaksi
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400 dark:text-neutral-500 font-bold text-base sm:text-lg">
                            Rp
                        </div>
                        <input
                            type="number"
                            name="amount"
                            id="amount"
                            value="{{ old('amount', $transaction->amount) }}"
                            placeholder="0"
                            required
                            min="1"
                            step="any"
                            class="w-full pl-12 pr-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-neutral-900 dark:text-neutral-50 font-extrabold text-base sm:text-lg placeholder-neutral-300 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition @error('amount') border-red-400 bg-red-50/50 @enderror"
                        >
                    </div>
                    @error('amount')
                        <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="transaction_date" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                            Tanggal
                        </label>
                        <input
                            type="date"
                            name="transaction_date"
                            id="transaction_date"
                            value="{{ old('transaction_date', \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d')) }}"
                            required
                            class="date-field w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 font-medium focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 focus:bg-white dark:focus:bg-[#262626] transition @error('transaction_date') border-red-400 @enderror"
                        >
                        @error('transaction_date')
                            <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                            Keterangan / Judul
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            value="{{ old('title', $transaction->title) }}"
                            placeholder="Contoh: Gaji Bulanan, Beli Kopi"
                            required
                            class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 font-medium focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 focus:bg-white dark:focus:bg-[#262626] transition @error('title') border-red-400 @enderror"
                        >
                        @error('title')
                            <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                        Kategori
                    </label>
                    <input type="hidden" name="category" id="category" value="{{ old('category', $transaction->category ?? '') }}">

                    @php
                        $catIcons = [
                            'Gaji' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
                            'Bonus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>',
                            'Bisnis' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                            'Investasi' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
                            'Hadiah' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
                            'Lainnya' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>',
                            'Makanan & Minuman' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
                            'Transportasi' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
                            'Tagihan & Utilitas' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                            'Belanja' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
                            'Hiburan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>',
                            'Kesehatan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
                            'Pendidikan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
                            'Keluarga' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                        ];

                        $chipBtn = function ($cat) use ($catIcons) {
                            $selected = old('category', $transaction->category ?? '') == $cat;
                            $active = $selected ? ' active' : '';
                            return '<button type="button" data-category="' . e($cat) . '"' .
                                ' class="cat-chip flex flex-col items-center justify-center gap-1.5 py-2.5 px-1 rounded-xl border text-xs font-semibold transition bg-neutral-50 dark:bg-[#262626]/60 border-neutral-200 dark:border-[#333333] text-neutral-700 dark:text-neutral-200 hover:border-neutral-900 dark:hover:border-neutral-100' . $active . '">' .
                                '<span class="w-8 h-8 rounded-lg bg-neutral-200 dark:bg-[#333333] flex items-center justify-center transition">' .
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($catIcons[$cat] ?? '') . '</svg></span>' .
                                '<span class="truncate w-full text-center">' . e($cat) . '</span></button>';
                        };
                    @endphp

                    <div id="cat-income">
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach (\App\Models\Transaction::INCOME_CATEGORIES as $cat){!! $chipBtn($cat) !!}@endforeach
                        </div>
                    </div>

                    <div id="cat-expense">
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach (\App\Models\Transaction::EXPENSE_CATEGORIES as $cat){!! $chipBtn($cat) !!}@endforeach
                        </div>
                    </div>
                    @error('category')
                        <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Web Speech API Input Suara (Lokal Bawaan Browser) -->
                <div x-data="voiceInput()" class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                        Input Cepat via Suara <span class="text-neutral-400 font-normal lowercase">(Bawaan Browser)</span>
                    </label>
                    <button type="button" @click="toggleVoice()"
                            :class="recording ? 'bg-red-50 border-red-300 text-red-700 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-400' : 'bg-neutral-100 dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border-neutral-200 dark:border-[#333333]'"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl border font-semibold text-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <span x-text="recording ? 'Merekam... Ucapkan transaksi (mis: Beli nasi goreng 25 ribu)' : 'Mulai Catat dengan Suara'" x-cloak>Mulai Catat dengan Suara</span>
                    </button>
                    <p x-show="voiceResult" x-cloak class="text-xs text-neutral-900 dark:text-neutral-100 font-semibold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                        Terdengar: <span x-text="voiceResult"></span>
                    </p>
                </div>

                <script>
                function voiceInput() {
                    return {
                        recording: false,
                        voiceResult: '',
                        recognition: null,
                        silenceTimer: null,

                        toggleVoice() {
                            if (this.recording) {
                                clearTimeout(this.silenceTimer);
                                this.recognition?.stop();
                                this.recording = false;
                                return;
                            }
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            if (!SpeechRecognition) {
                                alert('Browser kamu tidak mendukung voice input. Gunakan Chrome atau Edge.');
                                return;
                            }
                            this.recognition = new SpeechRecognition();
                            this.recognition.lang = 'id-ID';
                            this.recognition.continuous = true;
                            this.recognition.interimResults = true;
                            const self = this;
                            this.recognition.onresult = function(e) {
                                let transcript = '';
                                for (let i = 0; i < e.results.length; i++) {
                                    transcript += e.results[i][0].transcript;
                                }
                                self.voiceResult = transcript;
                                clearTimeout(self.silenceTimer);
                                self.silenceTimer = setTimeout(() => {
                                    self.recognition?.stop();
                                }, 1200);
                                if (e.results[e.results.length - 1].isFinal) {
                                    self.parseText(transcript);
                                }
                            };
                            this.recognition.onerror = function() { self.recording = false; };
                            this.recognition.onend = function() { self.recording = false; };
                            this.recognition.start();
                            this.recording = true;
                        },

                        async parseText(text) {
                            try {
                                const res = await fetch('/transactions/parse-voice', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ text: text }),
                                });
                                const json = await res.json();
                                if (json.data) {
                                    if (json.data.title) document.getElementById('title').value = json.data.title;
                                    if (json.data.amount) document.getElementById('amount').value = json.data.amount;
                                    if (json.data.type) {
                                        const radio = document.querySelector('input[name=type][value=' + json.data.type + ']');
                                        if (radio) radio.checked = true;
                                    }
                                    if (json.data.category) {
                                        const chip = document.querySelector('.cat-chip[data-category="' + CSS.escape(json.data.category) + '"]');
                                        if (chip) chip.click();
                                    }
                                }
                            } catch (err) {
                                console.error('Voice parse error:', err);
                            }
                        }
                    };
                }
                </script>

                <!-- Upload Gambar -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">
                        Upload Bukti Transaksi <span class="text-neutral-400 font-normal lowercase">(opsional)</span>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                                id="btnGallery"
                                class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-neutral-100 dark:bg-[#262626] hover:bg-neutral-200 dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-xl border border-neutral-200 dark:border-[#333333] font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Galeri
                        </button>

                        <button type="button"
                                id="btnCamera"
                                class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-neutral-100 dark:bg-[#262626] hover:bg-neutral-200 dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-xl border border-neutral-200 dark:border-[#333333] font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kamera
                        </button>
                    </div>

                    <input type="file" name="image" id="fileInput" accept="image/*" class="hidden">

                    <div id="dropZone"
                         class="relative border-2 border-dashed border-neutral-300 dark:border-[#333333] hover:border-neutral-900 dark:hover:border-neutral-100 rounded-xl p-6 text-center bg-neutral-50 dark:bg-[#262626]/40 hover:bg-neutral-100 dark:hover:bg-[#262626] transition cursor-pointer hidden md:block">

                        <div id="uploadPlaceholder" class="space-y-2">
                            <div class="w-12 h-12 mx-auto bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-neutral-700 dark:text-neutral-200">
                                <span class="text-neutral-900 dark:text-white font-bold">Klik</span> atau tarik gambar ke sini
                            </p>
                            <p class="text-xs text-neutral-400 dark:text-neutral-500">PNG, JPG, JPEG — maks 20MB (otomatis dikompres)</p>
                        </div>
                    </div>

                    <div id="previewContainer" class="hidden">
                        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-[#333333] bg-white dark:bg-[#171717]">
                            <img id="imagePreview" src="#" alt="Preview bukti transaksi"
                                 class="w-full max-h-80 object-contain bg-neutral-50 dark:bg-[#0A0A0A]">
                            <div class="flex items-center justify-between gap-2 px-3 py-2.5 border-t border-neutral-200 dark:border-[#333333]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p id="fileName" class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate"></p>
                                        <p id="fileSize" class="text-xs text-neutral-500 dark:text-neutral-400 font-semibold"></p>
                                    </div>
                                </div>
                                <button type="button"
                                        id="removeFileBtn"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-neutral-900 dark:text-neutral-100 border border-neutral-300 dark:border-[#333333] hover:bg-neutral-900 hover:text-white dark:hover:bg-neutral-100 dark:hover:text-neutral-900 rounded-lg transition flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    @error('image')
                        <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-6 border-t border-neutral-200 dark:border-[#333333]">
                    <a href="{{ route('transactions.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#333333] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>

            </main>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const dropZone = document.getElementById('dropZone');
        const placeholder = document.getElementById('uploadPlaceholder');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeBtn = document.getElementById('removeFileBtn');
        const btnGallery = document.getElementById('btnGallery');
        const btnCamera = document.getElementById('btnCamera');

        @if($transaction->image)
            const existingImage = "{{ asset('storage/' . $transaction->image) }}";
            const existingName = "{{ basename($transaction->image) }}";
            imagePreview.src = existingImage;
            fileName.textContent = existingName;
            fileSize.textContent = 'Foto tersimpan';
            placeholder.classList.add('hidden');
            dropZone.classList.add('hidden');
            previewContainer.classList.remove('hidden');
        @endif

        function showPreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                placeholder.classList.add('hidden');
                dropZone.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function resetUpload() {
            fileInput.value = '';
            @if($transaction->image)
                imagePreview.src = "{{ asset('storage/' . $transaction->image) }}";
                fileName.textContent = "{{ basename($transaction->image) }}";
                fileSize.textContent = 'Foto tersimpan';
                placeholder.classList.add('hidden');
                dropZone.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            @else
                imagePreview.src = '#';
                fileName.textContent = '';
                fileSize.textContent = '';
                placeholder.classList.remove('hidden');
                dropZone.classList.remove('hidden');
                previewContainer.classList.add('hidden');
            @endif
        }

        fileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                showPreview(file);
            }
        });

        function setActiveBtn(el) {
            document.querySelectorAll('.btn-upload').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
        }

        btnGallery.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.removeAttribute('capture');
            fileInput.click();
            setActiveBtn(this);
        });

        btnCamera.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
            setActiveBtn(this);
        });

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            resetUpload();
        });

        const typeRadios = document.querySelectorAll('input[name="type"]');
        const categoryInput = document.getElementById('category');
        const catIncome = document.getElementById('cat-income');
        const catExpense = document.getElementById('cat-expense');
        const allChips = document.querySelectorAll('.cat-chip');

        allChips.forEach(chip => {
            chip.addEventListener('click', function() {
                allChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                if (categoryInput) categoryInput.value = this.dataset.category;
            });
        });

        function syncCategoryGroups() {
            const isIncome = document.querySelector('input[name="type"]:checked')?.value === 'income';
            if (catIncome) catIncome.style.display = isIncome ? '' : 'none';
            if (catExpense) catExpense.style.display = isIncome ? 'none' : '';
            const valid = Array.from(allChips).some(c =>
                c.dataset.category === (categoryInput?.value || '') &&
                c.closest(isIncome ? '#cat-income' : '#cat-expense')
            );
            if (!valid && categoryInput?.value) {
                categoryInput.value = '';
                allChips.forEach(c => c.classList.remove('active'));
            }
        }
        typeRadios.forEach(r => r.addEventListener('change', syncCategoryGroups));
        syncCategoryGroups();

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-neutral-900', 'bg-neutral-100');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-neutral-900', 'bg-neutral-100');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-neutral-900', 'bg-neutral-100');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                    showPreview(file);
                }
            }
        });
    });
    </script>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
