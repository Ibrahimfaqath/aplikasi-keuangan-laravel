<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tambahkan transaksi pemasukan atau pengeluaran di DompetKu.">
    <meta name="theme-color" content="#0A1128">
    <title>Tambah Transaksi - DompetKu</title>
    
    <script>
        (function() {
            const isDark = localStorage.getItem('theme') !== 'light';
            if (isDark) document.documentElement.classList.add('dark');
            document.documentElement.style.backgroundColor = isDark ? '#0A1128' : '#f8fafc';
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .cat-chip.active { background-color: var(--cat-color, #2563eb) !important; color: #fff !important; }
        .cat-row { scrollbar-width: none; } .cat-row::-webkit-scrollbar { display: none; }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white antialiased flex flex-col">
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-navy-900 rounded-2xl shadow-xl border border-slate-100 dark:border-navy-800">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-white/90">{{ session('success') }}</div>
    </div>
    @endif

    <div class="flex-1 max-w-2xl mx-auto w-full px-4 sm:px-6 py-6 sm:py-8">
        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8">
            <a href="{{ route('transactions.index') }}" 
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-800 text-slate-600 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-navy-800 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tambah Transaksi</h1>
        </div>

        <!-- Form -->
        <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- 1. JENIS TRANSAKSI -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Jenis Transaksi</label>
                <div class="grid grid-cols-2 gap-3 p-1 bg-slate-100 dark:bg-navy-800/80 rounded-xl border border-slate-200 dark:border-navy-700">
                    @foreach(['income' => '📈 Pemasukan', 'expense' => '📉 Pengeluaran'] as $val => $label)
                    <label class="relative flex items-center justify-center py-3 px-4 rounded-lg cursor-pointer has-[:checked]:bg-white dark:has-[:checked]:bg-slate-900 has-[:checked]:shadow-sm text-slate-600 dark:text-white/70 hover:text-slate-900 dark:hover:text-white transition">
                        <input type="radio" name="type" value="{{ $val }}" {{ old('type', 'income') == $val ? 'checked' : '' }} class="sr-only" required>
                        <span class="font-bold text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('type')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <!-- CARD SECTION -->
            <div class="bg-slate-50 dark:bg-navy-900 rounded-xl border border-slate-200 dark:border-navy-800 p-6 sm:p-8 space-y-6">

                <!-- 2. NOMINAL & TANGGAL -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label for="amount" class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Nominal (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 dark:text-white/50 font-bold">Rp</span>
                            <input type="number" name="amount" id="amount" placeholder="0" required min="1" step="any"
                                   value="{{ old('amount') }}"
                                   class="w-full pl-12 pr-4 py-3 bg-white dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-lg text-slate-900 dark:text-white font-bold text-lg placeholder-slate-300 dark:placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        @error('amount')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="transaction_date" class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Tanggal</label>
                            <input type="date" name="transaction_date" id="transaction_date" required
                                   value="{{ old('transaction_date', date('Y-m-d')) }}"
                                   class="w-full px-4 py-3 bg-white dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            @error('transaction_date')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>

                        <div class="space-y-2">
                            <label for="title" class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Keterangan</label>
                            <input type="text" name="title" id="title" required placeholder="Contoh: Gaji, Beli Kopi"
                                   value="{{ old('title') }}"
                                   class="w-full px-4 py-3 bg-white dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            @error('title')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <!-- 3. KATEGORI -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Kategori</label>
                    <input type="hidden" name="category" id="category" value="{{ old('category', $transaction->category ?? '') }}">

                    @php
                        $catColors = [
                            'Gaji' => '#10b981', 'Bonus' => '#f59e0b', 'Bisnis' => '#2563eb',
                            'Makanan & Minuman' => '#f97316', 'Transportasi' => '#64748b',
                            'Belanja' => '#a855f7', 'Hiburan' => '#f43f5e', 'Kesehatan' => '#ef4444',
                        ];
                        $type = old('type', 'income');
                        $categories = $type === 'income' ? \App\Models\Transaction::INCOME_CATEGORIES : \App\Models\Transaction::EXPENSE_CATEGORIES;
                    @endphp

                    <div class="flex flex-wrap gap-2">
                        @foreach($categories as $cat)
                        <button type="button" data-category="{{ $cat }}" style="--cat-color: {{ $catColors[$cat] ?? '#6b7280' }}"
                                class="cat-chip px-3 py-2 rounded-lg border text-xs font-semibold bg-white dark:bg-navy-800 border-slate-200 dark:border-navy-700 text-slate-700 dark:text-white/80 hover:border-slate-300 dark:hover:border-navy-600 transition {{ old('category', $transaction->category ?? '') == $cat ? 'active' : '' }}">
                            {{ $cat }}
                        </button>
                        @endforeach
                    </div>
                    @error('category')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>

                <!-- 4. UPLOAD BUKTI -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase text-slate-600 dark:text-white/70">Upload Bukti <span class="font-normal text-slate-400">(Opsional)</span></label>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="btnGallery" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-lg hover:bg-slate-50 dark:hover:bg-navy-700 font-semibold text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            Galeri
                        </button>
                        <button type="button" id="btnCamera" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-lg hover:bg-slate-50 dark:hover:bg-navy-700 font-semibold text-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                            Kamera
                        </button>
                    </div>

                    <input type="file" name="image" id="fileInput" accept="image/*" class="hidden">

                    <!-- Preview -->
                    <div id="previewContainer" class="hidden">
                        <img id="imagePreview" src="#" alt="Preview" class="w-full max-h-60 object-contain rounded-lg border border-slate-200 dark:border-navy-700">
                    </div>

                    @error('image')<p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>

                <!-- 5. AI INPUT (COLLAPSIBLE) -->
                <details class="group border-t border-slate-200 dark:border-navy-700 pt-4">
                    <summary class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-white cursor-pointer select-none">
                        <svg class="w-4 h-4 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Input Cepat (AI) - Voice & OCR
                    </summary>

                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="button" id="btnVoice" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-300 rounded-lg font-semibold text-sm hover:bg-blue-100 dark:hover:bg-blue-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            Suara
                        </button>
                        <button type="button" id="btnOcr" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-300 rounded-lg font-semibold text-sm hover:bg-blue-100 dark:hover:bg-blue-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Struk (OCR)
                        </button>
                    </div>
                </details>

                <!-- SUBMIT -->
                <div class="flex flex-col-reverse sm:flex-row gap-3 pt-6 border-t border-slate-200 dark:border-navy-700">
                    <a href="{{ route('transactions.index') }}" class="w-full sm:w-auto px-5 py-2.5 bg-white dark:bg-navy-800 text-slate-700 dark:text-white/80 border border-slate-200 dark:border-navy-700 rounded-lg font-semibold hover:bg-slate-50 dark:hover:bg-navy-700 transition text-center">
                        Batal
                    </a>
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 text-white rounded-lg font-semibold shadow-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Transaksi
                    </button>
                </div>

            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category selection
        document.querySelectorAll('.cat-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('category').value = this.dataset.category;
            });
        });

        // File upload
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('imagePreview');
        const previewContainer = document.getElementById('previewContainer');
        
        fileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('btnGallery').addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.removeAttribute('capture');
            fileInput.click();
        });

        document.getElementById('btnCamera').addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
        });

        // Type change - sync categories
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Reload categories for selected type
                location.reload();
            });
        });
    });
    </script>

</body>
</html>
