<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tambahkan transaksi pemasukan atau pengeluaran dengan bukti foto di DompetKu.">
    <meta name="theme-color" content="#4f46e5">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Tambah Transaksi - DompetKu">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Tambah Transaksi - DompetKu</title>
    
    <!-- Theme Init -->
    <script>
        (function initTheme() {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = stored === 'dark' || (!stored && prefersDark);
                if (isDark) document.documentElement.classList.add('dark');
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
    </style>
</head>

<body class="min-h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased">

    <!-- NAVBAR -->
    <x-navbar />

    <!-- Toast -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
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

    <!-- MAIN CONTENT -->
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <!-- Page Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100/60 dark:border-indigo-900/50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Tambah Transaksi Baru</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Catat pemasukan atau pengeluaranmu secara akurat.</p>
            </div>
        </div>

        <!-- Form Card -->
        <main class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">

            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">
                @csrf

                <!-- Jenis Transaksi -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Jenis Transaksi
                    </label>
                    
                    <div class="grid grid-cols-2 gap-3 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                        <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white dark:has-[:checked]:bg-slate-900 has-[:checked]:text-emerald-700 dark:has-[:checked]:text-emerald-400 has-[:checked]:shadow-sm has-[:checked]:border-emerald-200/80 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
                            <input type="radio" name="type" value="income" class="sr-only" {{ old('type', 'income') == 'income' ? 'checked' : '' }} required>
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-xs sm:text-sm font-bold">Pemasukan</span>
                        </label>

                        <label class="relative flex items-center justify-center gap-2 py-3 px-4 rounded-lg cursor-pointer transition-all has-[:checked]:bg-white dark:has-[:checked]:bg-slate-900 has-[:checked]:text-rose-700 dark:has-[:checked]:text-rose-400 has-[:checked]:shadow-sm has-[:checked]:border-rose-200/80 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
                            <input type="radio" name="type" value="expense" class="sr-only" {{ old('type') == 'expense' ? 'checked' : '' }} required>
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

                <!-- Nominal -->
                <div class="space-y-2">
                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Nominal Transaksi
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 font-bold text-base sm:text-lg">
                            Rp
                        </div>
                        <input 
                            type="number" 
                            name="amount" 
                            id="amount"
                            value="{{ old('amount') }}" 
                            placeholder="0" 
                            required 
                            min="1"
                            step="any"
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-slate-900 dark:text-white font-extrabold text-base sm:text-lg placeholder-slate-300 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition @error('amount') border-rose-400 bg-rose-50/20 @enderror"
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
                        <label for="transaction_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Tanggal
                        </label>
                        <input 
                            type="date" 
                            name="transaction_date" 
                            id="transaction_date"
                            value="{{ old('transaction_date', date('Y-m-d')) }}" 
                            required 
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition @error('transaction_date') border-rose-400 bg-rose-50/20 @enderror"
                        >
                        @error('transaction_date')
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Keterangan / Judul
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            id="title"
                            value="{{ old('title') }}" 
                            placeholder="Contoh: Gaji Bulanan, Beli Kopi" 
                            required 
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition @error('title') border-rose-400 bg-rose-50/20 @enderror"
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
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Kategori
                    </label>
                    <select name="category" id="category" required
                            class="select-field w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition @error('category') border-rose-400 bg-rose-50/20 @enderror">
                        <option value="" disabled {{ old('category', $transaction->category ?? '') ? '' : 'selected' }}>Pilih kategori...</option>
                        <optgroup id="cat-income" label="Pemasukan">
                            @foreach (\App\Models\Transaction::INCOME_CATEGORIES as $cat)
                                <option value="{{ $cat }}" {{ old('category', $transaction->category ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup id="cat-expense" label="Pengeluaran">
                            @foreach (\App\Models\Transaction::EXPENSE_CATEGORIES as $cat)
                                <option value="{{ $cat }}" {{ old('category', $transaction->category ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('category')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Upload Gambar - GALERI + KAMERA -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Upload Bukti Transaksi <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                    </label>

                    <!-- Tombol Pilihan: Galeri & Kamera -->
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                id="btnGallery"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg border border-slate-200 dark:border-slate-700 font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Galeri
                        </button>
                        
                        <button type="button" 
                                id="btnCamera"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 rounded-lg border border-emerald-200 dark:border-emerald-800/50 font-medium text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kamera
                        </button>
                    </div>

                    <!-- Drop Zone -->
                    <div id="dropZone" 
                         class="relative border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500 rounded-xl p-6 text-center bg-slate-50/50 dark:bg-slate-800/30 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer">
                        
                        <input type="file" 
                               name="image" 
                               id="fileInput" 
                               accept="image/*"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <!-- Placeholder -->
                        <div id="uploadPlaceholder" class="space-y-2">
                            <div class="w-12 h-12 mx-auto bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <span class="text-indigo-600 dark:text-indigo-400">Klik</span> atau tarik gambar ke sini
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">PNG, JPG, JPEG — maks 20MB (otomatis dikompres)</p>
                        </div>

                        <!-- Preview -->
                        <div id="previewContainer" 
                             class="hidden items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <img id="imagePreview" src="#" alt="Preview" class="w-12 h-12 rounded-lg object-cover border border-slate-100 dark:border-slate-800 flex-shrink-0">
                                <div class="text-left min-w-0">
                                    <p id="fileName" class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate"></p>
                                    <p id="fileSize" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold"></p>
                                </div>
                            </div>
                            <button type="button" 
                                    id="removeFileBtn"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
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
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-slate-800">
                    <a href="{{ route('transactions.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Transaksi
                    </button>
                </div>

            </form>
        </main>
    </div>

    <!-- SCRIPT UPLOAD - CREATE -->
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

        function showPreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                placeholder.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                previewContainer.classList.add('flex');
            };
            reader.readAsDataURL(file);
        }

        function resetUpload() {
            fileInput.value = '';
            imagePreview.src = '#';
            fileName.textContent = '';
            fileSize.textContent = '';
            placeholder.classList.remove('hidden');
            previewContainer.classList.add('hidden');
            previewContainer.classList.remove('flex');
        }

        fileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                showPreview(file);
            }
        });

        btnGallery.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.removeAttribute('capture');
            fileInput.click();
        });

        btnCamera.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
        });

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            resetUpload();
        });

        // KATEGORI: pilihan menyesuaikan jenis transaksi (Pemasukan/Pengeluaran)
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const categorySelect = document.getElementById('category');
        const catIncome = document.getElementById('cat-income');
        const catExpense = document.getElementById('cat-expense');

        function syncCategoryGroups() {
            const isIncome = document.querySelector('input[name="type"]:checked')?.value === 'income';
            if (catIncome) catIncome.style.display = isIncome ? '' : 'none';
            if (catExpense) catExpense.style.display = isIncome ? 'none' : '';
            if (categorySelect) {
                const group = isIncome ? catIncome : catExpense;
                const valid = Array.from(group?.querySelectorAll('option') ?? []).some(o => o.value === categorySelect.value);
                if (!valid) {
                    categorySelect.value = group?.querySelector('option')?.value ?? '';
                }
            }
        }
        typeRadios.forEach(r => r.addEventListener('change', syncCategoryGroups));
        syncCategoryGroups();

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-400', 'bg-indigo-50/20', 'dark:bg-indigo-950/20');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-400', 'bg-indigo-50/20', 'dark:bg-indigo-950/20');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-400', 'bg-indigo-50/20', 'dark:bg-indigo-950/20');
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

    <!-- FOOTER -->
    <x-footer />

</body>
</html>