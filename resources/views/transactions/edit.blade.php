<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Perbarui detail transaksi atau ganti bukti foto di DompetKu.">
    <meta name="theme-color" content="#0A1128">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Edit Transaksi - DompetKu">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Edit Transaksi - DompetKu</title>
    
    <!-- Theme Init -->
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

    <!-- CSS & JS hasil build (Vite) — Alpine dibundle, tanpa CDN -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { overflow-x: hidden; }
        [x-cloak] { display: none !important; }

        /* Custom select dropdown arrow */
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
        /* Date input calendar icon */
        .date-field {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.15em 1.15em;
            padding-right: 2.5rem !important;
        }
        .dark .date-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
        }
        /* Upload button active state */
        .btn-upload.active {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }
        .dark .btn-upload.active {
            background-color: #3b63b8 !important;
            border-color: #3b63b8 !important;
            color: #ffffff !important;
        }
        /* Chip kategori aktif */
        .cat-chip.active {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .dark .cat-chip.active {
            background-color: #3b63b8 !important;
            border-color: #3b63b8 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(59, 99, 184, 0.35);
        }
        /* Ikon di dalam chip aktif ikut berwarna putih */
        .cat-chip.active > span:first-child {
            background-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }
        /* Baris kategori: sembunyikan scrollbar horizontal */
        .cat-row::-webkit-scrollbar { display: none; }
        .cat-row { scrollbar-width: none; }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased">

    <!-- AI Chat Widget -->
    <x-ai-chat />

    <!-- Toast -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
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

    <!-- MAIN CONTENT -->
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <!-- Header: tombol back di kiri, judul di tengah -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.index') }}" aria-label="Kembali ke daftar transaksi"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-800 text-slate-600 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Edit Transaksi</h1>
        </div>

        <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Jenis Transaksi (langsung di bawah header) -->
            <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                        Jenis Transaksi
                    </label>
                    
                    <div class="grid grid-cols-2 gap-3 p-1 bg-slate-100 dark:bg-navy-800/80 rounded-xl border border-slate-200/60 dark:border-navy-700/60">
                        <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white dark:has-[:checked]:bg-slate-900 has-[:checked]:text-emerald-700 dark:has-[:checked]:text-emerald-400 has-[:checked]:shadow-sm has-[:checked]:border-emerald-200/80 text-slate-500 dark:text-white/70 hover:text-slate-800 dark:hover:text-white/80">
                            <input type="radio" name="type" value="income" class="sr-only" {{ old('type', $transaction->type) == 'income' ? 'checked' : '' }} required>
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-xs sm:text-sm font-bold">Pemasukan</span>
                        </label>

                        <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white dark:has-[:checked]:bg-slate-900 has-[:checked]:text-rose-700 dark:has-[:checked]:text-rose-400 has-[:checked]:shadow-sm has-[:checked]:border-rose-200/80 text-slate-500 dark:text-white/70 hover:text-slate-800 dark:hover:text-white/80">
                            <input type="radio" name="type" value="expense" class="sr-only" {{ old('type', $transaction->type) == 'expense' ? 'checked' : '' }} required>
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-xs sm:text-sm font-bold">Pengeluaran</span>
                        </label>
                    </div>
                    @error('type')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Form Card -->
                <main class="bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm overflow-hidden p-6 sm:p-8 space-y-6">

                <!-- Nominal -->
                <div class="space-y-2">
                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                        Nominal Transaksi
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-white/50 font-bold text-base sm:text-lg">
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
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-slate-900 dark:text-white font-extrabold text-base sm:text-lg placeholder-slate-300 dark:placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-navy-800 transition @error('amount') border-rose-400 bg-rose-50/20 @enderror"
                        >
                    </div>
                    @error('amount')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Grid: Tanggal + Judul -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="transaction_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                            Tanggal
                        </label>
                        <input 
                            type="date" 
                            name="transaction_date" 
                            id="transaction_date"
                            value="{{ old('transaction_date', \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d')) }}" 
                            required 
                            class="date-field w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-white/90 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-navy-800 transition @error('transaction_date') border-rose-400 bg-rose-50/20 @enderror"
                        >
                        @error('transaction_date')
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                            Keterangan / Judul
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            id="title"
                            value="{{ old('title', $transaction->title) }}" 
                            placeholder="Contoh: Gaji Bulanan, Beli Kopi" 
                            required 
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-white/90 placeholder-slate-400 dark:placeholder-white/40 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-navy-800 transition @error('title') border-rose-400 bg-rose-50/20 @enderror"
                        >
                        @error('title')
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Kategori -->
                <div class="space-y-2">
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                        Kategori
                    </label>
                    <!-- Nilai kategori disimpan di sini (tetap dipakai validasi server) -->
                    <input type="hidden" name="category" id="category" value="{{ old('category', $transaction->category ?? '') }}">

                    @php
                        // Ikon profesional gaya garis (Heroicons v1 / Lucide) — konsisten dengan seluruh aplikasi
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

                        // Render tombol chip kategori (mode baris = satu baris scroll, mode grid = semua)
                        $chipBtn = function ($cat, $rowMode) use ($catIcons) {
                            $selected = old('category', $transaction->category ?? '') == $cat;
                            $width = $rowMode ? ' w-24 flex-shrink-0' : '';
                            $active = $selected ? ' active' : '';
                            return '<button type="button" data-category="' . e($cat) . '"' .
                                ' class="cat-chip flex flex-col items-center justify-center gap-1.5 py-2.5 px-1 rounded-xl border text-[11px] font-semibold transition bg-slate-50 dark:bg-navy-800/60 border-slate-200 dark:border-navy-700/80 text-slate-700 dark:text-white/80 hover:border-blue-400 dark:hover:border-navy-400' . $width . $active . '">' .
                                '<span class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center transition">' .
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($catIcons[$cat] ?? '') . '</svg></span>' .
                                '<span class="truncate w-full text-center">' . e($cat) . '</span></button>';
                        };
                    @endphp

                    <!-- Kategori Pemasukan: satu baris (scroll) + tombol tampilkan semua -->
                    <div id="cat-income" class="space-y-1.5">
                        <div id="cat-income-row" class="cat-row flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                            @foreach (\App\Models\Transaction::INCOME_CATEGORIES as $cat){!! $chipBtn($cat, true) !!}@endforeach
                        </div>
                        <div id="cat-income-grid" class="cat-grid hidden grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach (\App\Models\Transaction::INCOME_CATEGORIES as $cat){!! $chipBtn($cat, false) !!}@endforeach
                        </div>
                        <button type="button" id="toggle-cat-income"
                                class="cat-toggle inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 dark:text-navy-300 hover:text-blue-700 dark:hover:text-navy-200 transition">
                            <span class="cat-toggle-label">Tampilkan semua</span>
                            <svg class="cat-toggle-icon w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Kategori Pengeluaran: satu baris (scroll) + tombol tampilkan semua -->
                    <div id="cat-expense" class="space-y-1.5">
                        <div id="cat-expense-row" class="cat-row flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                            @foreach (\App\Models\Transaction::EXPENSE_CATEGORIES as $cat){!! $chipBtn($cat, true) !!}@endforeach
                        </div>
                        <div id="cat-expense-grid" class="cat-grid hidden grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach (\App\Models\Transaction::EXPENSE_CATEGORIES as $cat){!! $chipBtn($cat, false) !!}@endforeach
                        </div>
                        <button type="button" id="toggle-cat-expense"
                                class="cat-toggle inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 dark:text-navy-300 hover:text-blue-700 dark:hover:text-navy-200 transition">
                            <span class="cat-toggle-label">Tampilkan semua</span>
                            <svg class="cat-toggle-icon w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    @error('category')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- AI Quick Input: Voice & OCR Struk -->
                <div x-data="aiInput()" class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                        Input Cepat <span class="text-slate-400 font-normal lowercase">(AI)</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="toggleVoice()"
                                :class="recording ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-300 dark:border-rose-800' : 'bg-slate-100 dark:bg-navy-800 text-slate-600 dark:text-white/70 border-slate-200 dark:border-navy-700'"
                                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border font-semibold text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            <span x-text="recording ? 'Merekam...' : 'Suara'" x-cloak>Suara</span>
                        </button>
                        <button type="button" @click="$refs.ocrInput.click()"
                                class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-navy-800 hover:bg-slate-200 dark:hover:bg-navy-700 text-slate-600 dark:text-white/70 rounded-xl border border-slate-200 dark:border-navy-700 font-semibold text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Struk AI
                        </button>
                    </div>
                    <input type="file" x-ref="ocrInput" accept="image/*" capture="environment" class="hidden" @change="processOcr($event)">
                    <p x-show="ocrLoading" x-cloak class="text-xs text-blue-600 dark:text-navy-300 font-semibold flex items-center gap-1">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Memproses struk...
                    </p>
                    <p x-show="ocrResult" x-cloak class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Struk berhasil dibaca!
                    </p>
                </div>

                <script>
                function aiInput() {
                    return {
                        recording: false, ocrLoading: false, ocrResult: false, voiceResult: '', recognition: null,
                        toggleVoice() {
                            if (this.recording) { this.recognition?.stop(); this.recording = false; return; }
                            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                            if (!SR) { alert('Gunakan Chrome atau Edge untuk voice input.'); return; }
                            this.recognition = new SR();
                            this.recognition.lang = 'id-ID';
                            this.recognition.continuous = false;
                            this.recognition.interimResults = true;
                            const self = this;
                            this.recognition.onresult = function(e) {
                                let t = '';
                                for (let i = e.resultIndex; i < e.results.length; i++) t += e.results[i][0].transcript;
                                self.voiceResult = t;
                                if (e.results[e.results.length - 1].isFinal) {
                                    const l = t.toLowerCase();
                                    if (l.includes('pemasukan') || l.includes('gaji') || l.includes('masuk')) document.querySelector('input[name=type][value=income]').checked = true;
                                    else if (l.includes('pengeluaran') || l.includes('beli') || l.includes('bayar')) document.querySelector('input[name=type][value=expense]').checked = true;
                                    const n = t.match(/[\d.,]+/);
                                    if (n) document.getElementById('amount').value = n[0].replace(/[^\d]/g, '');
                                    document.querySelector('input[name=title]').value = t;
                                    self.recording = false;
                                }
                            };
                            this.recognition.onerror = function() { self.recording = false; };
                            this.recognition.onend = function() { self.recording = false; };
                            this.recognition.start(); this.recording = true;
                        },
                        async processOcr(e) {
                            const file = e.target.files[0]; if (!file) return;
                            this.ocrLoading = true; this.ocrResult = false;
                            const fd = new FormData(); fd.append('image', file);
                            try {
                                const res = await fetch('/ai/ocr', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: fd });
                                const data = await res.json();
                                if (data.data) {
                                    if (data.data.title) document.querySelector('input[name=title]').value = data.data.title;
                                    if (data.data.amount) document.getElementById('amount').value = data.data.amount;
                                    if (data.data.type) { const r = document.querySelector('input[name=type][value=' + data.data.type + ']'); if (r) r.checked = true; }
                                    if (data.data.category) { const chip = document.querySelector('.cat-chip[data-category="' + CSS.escape(data.data.category) + '"]'); if (chip) chip.click(); }
                                    if (data.data.date) document.getElementById('transaction_date').value = data.data.date;
                                    this.ocrResult = true;
                                } else if (data.error) alert('Error: ' + data.error);
                            } catch (err) { alert('Gagal: ' + err.message); }
                            this.ocrLoading = false; e.target.value = '';
                        }
                    };
                }
                </script>

                <!-- Upload Gambar - GALERI + KAMERA -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
                        Upload Bukti Transaksi <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                    </label>

                    <!-- Tombol Pilihan: Galeri & Kamera -->
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                id="btnGallery"
                                class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-navy-800 hover:bg-slate-200 dark:hover:bg-navy-700 text-slate-600 dark:text-white/70 rounded-lg border border-slate-200 dark:border-navy-700 font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Galeri
                        </button>
                        
                        <button type="button" 
                                id="btnCamera"
                                class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-navy-800 hover:bg-slate-200 dark:hover:bg-navy-700 text-slate-600 dark:text-white/70 rounded-lg border border-slate-200 dark:border-navy-700 font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kamera
                        </button>
                    </div>

                    <!-- Hidden file input (accessible by Galeri & Kamera buttons) -->
                    <input type="file" name="image" id="fileInput" accept="image/*" class="hidden">

                    <!-- Drop Zone (desktop only) -->
                    <div id="dropZone" 
                         class="relative border-2 border-dashed border-slate-200 dark:border-navy-800 hover:border-blue-400 dark:hover:border-blue-500 rounded-xl p-6 text-center bg-slate-50/50 dark:bg-navy-800/40 hover:bg-slate-50 dark:hover:bg-navy-800/50 transition cursor-pointer hidden md:block">

                        <!-- Placeholder -->
                        <div id="uploadPlaceholder" class="space-y-2">
                            <div class="w-12 h-12 mx-auto bg-blue-50 dark:bg-navy-400/10 text-blue-500 dark:text-navy-300 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-white/80">
                                <span class="text-blue-600 dark:text-navy-300">Klik</span> atau tarik gambar ke sini
                            </p>
                            <p class="text-xs text-slate-400 dark:text-white/50">PNG, JPG, JPEG — maks 20MB (otomatis dikompres)</p>
                        </div>
                    </div>

                    <!-- Preview Gambar (semua ukuran layar) -->
                    <div id="previewContainer" class="hidden">
                        <div class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-navy-800 bg-white dark:bg-navy-900">
                            <img id="imagePreview" src="#" alt="Preview bukti transaksi"
                                 class="w-full max-h-80 object-contain bg-slate-50 dark:bg-navy-950">
                            <div class="flex items-center justify-between gap-2 px-3 py-2.5 border-t border-slate-100 dark:border-navy-800">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p id="fileName" class="text-xs font-bold text-slate-800 dark:text-white/90 truncate"></p>
                                        <p id="fileSize" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold"></p>
                                    </div>
                                </div>
                                <button type="button"
                                        id="removeFileBtn"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    @error('image')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-navy-800">
                    <a href="{{ route('transactions.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-navy-900 text-slate-700 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 border border-slate-200 dark:border-navy-800 rounded-xl text-xs sm:text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-xs sm:text-sm font-semibold shadow-md shadow-blue-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>

            </main>
        </form>
    </div>

    <!-- SCRIPT UPLOAD - EDIT -->
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

        // Cek apakah ada gambar existing
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

        // KATEGORI: pilih lewat chip, daftar menyesuaikan jenis transaksi (Pemasukan/Pengeluaran)
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const categoryInput = document.getElementById('category');
        const catIncome = document.getElementById('cat-income');
        const catExpense = document.getElementById('cat-expense');
        const allChips = document.querySelectorAll('.cat-chip');

        // Klik chip = pilih kategori
        allChips.forEach(chip => {
            chip.addEventListener('click', function() {
                allChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                if (categoryInput) categoryInput.value = this.dataset.category;
            });
        });

        // Toggle kategori: satu baris <-> tampilkan semua
        document.querySelectorAll('.cat-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const group = this.id.replace('toggle-cat-', '');
                const row = document.getElementById('cat-' + group + '-row');
                const grid = document.getElementById('cat-' + group + '-grid');
                const expanded = !grid.classList.contains('hidden');
                if (expanded) {
                    grid.classList.add('hidden');
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                    grid.classList.remove('hidden');
                }
                this.querySelector('.cat-toggle-label').textContent = expanded ? 'Tampilkan semua' : 'Tampilkan sedikit';
                this.querySelector('.cat-toggle-icon').classList.toggle('-rotate-180', !expanded);
            });
        });

        function syncCategoryGroups() {
            const isIncome = document.querySelector('input[name="type"]:checked')?.value === 'income';
            if (catIncome) catIncome.style.display = isIncome ? '' : 'none';
            if (catExpense) catExpense.style.display = isIncome ? 'none' : '';
            // Jika kategori terpilih tidak ada di jenis aktif, kosongkan pilihan
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
            this.classList.add('border-blue-400', 'bg-blue-50/20', 'dark:bg-navy-400/10');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-400', 'bg-blue-50/20', 'dark:bg-navy-400/10');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-400', 'bg-blue-50/20', 'dark:bg-navy-400/10');
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

    <!-- Export Laporan (PDF/Excel/Print) -->
    @include('components.export-modal')

    <!-- FOOTER -->
    <x-footer />

</body>
</html>