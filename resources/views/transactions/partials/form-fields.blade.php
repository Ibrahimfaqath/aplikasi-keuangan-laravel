{{-- Partial isi form transaksi. Dipakai create & edit agar tidak duplikat.
     Variabel: $transaction (?Transaction, null saat create), $submitLabel (string) --}}
@php
    $typeValue = old('type', $transaction->type ?? 'income');
    $amountValue = old('amount', $transaction->amount ?? '');
    $dateValue = old('transaction_date', isset($transaction) && $transaction->transaction_date
        ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d')
        : date('Y-m-d'));
    $titleValue = old('title', $transaction->title ?? '');
    $categoryValue = old('category', $transaction->category ?? '');
@endphp

<div class="space-y-2">
    @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
    <div class="flex items-start gap-2.5 rounded-box border border-warning/30 bg-warning/10 p-3.5 mb-2">
        <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        <p class="text-xs font-semibold text-base-content">
            Mode demo — form ini terkunci. Data hanya bisa dilihat, bukan diubah.
            <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-warning">Daftar gratis</a> untuk mencoba mencatat.
        </p>
    </div>
    @endif

    <label class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
        Jenis Transaksi
    </label>

    <div class="grid grid-cols-2 gap-1 p-1 bg-base-200 rounded-box border border-base-300"
         role="radiogroup" aria-label="Jenis Transaksi">
        <label class="relative flex items-center justify-center gap-2 py-2.5 sm:py-3 px-4 rounded-lg cursor-pointer select-none transition-colors has-[:checked]:bg-base-content has-[:checked]:text-base-100 has-[:checked]:shadow-sm text-base-content/60 hover:text-base-content dark:hover:text-base-content focus-within:ring-2 focus-within:ring-base-content dark:focus-within:ring-base-300">
            <input type="radio" name="type" value="income" class="radio radio-sm sr-only" {{ $typeValue == 'income' ? 'checked' : '' }} required>
            <span class="w-6 h-6 rounded-lg bg-success text-success-content flex items-center justify-center text-sm font-mono font-bold shrink-0">+</span>
            <span class="text-xs sm:text-sm font-bold">Pemasukan</span>
        </label>

        <label class="relative flex items-center justify-center gap-2 py-2.5 sm:py-3 px-4 rounded-lg cursor-pointer select-none transition-colors has-[:checked]:bg-base-content has-[:checked]:text-base-100 has-[:checked]:shadow-sm text-base-content/60 hover:text-base-content dark:hover:text-base-content focus-within:ring-2 focus-within:ring-base-content dark:focus-within:ring-base-300">
            <input type="radio" name="type" value="expense" class="radio radio-sm sr-only" {{ $typeValue == 'expense' ? 'checked' : '' }} required>
            <span class="w-6 h-6 rounded-lg bg-error text-error-content flex items-center justify-center text-sm font-mono font-bold shrink-0">−</span>
            <span class="text-xs sm:text-sm font-bold">Pengeluaran</span>
        </label>
    </div>
    @error('type')
        <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $message }}
        </p>
    @enderror
</div>

<main class="bg-base-100 rounded-2xl border border-base-300 shadow-sm overflow-hidden p-6 sm:p-8 space-y-6">

    <div class="space-y-2">
        <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
            Nominal Transaksi
        </label>
        <div class="relative rounded-box shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/40 font-bold text-base sm:text-lg">
                Rp
            </div>
            <input
                type="number"
                name="amount"
                id="amount"
                value="{{ $amountValue }}"
                placeholder="0"
                required
                min="1"
                step="any"
                class="input input-bordered w-full pl-12 font-extrabold text-base sm:text-lg @error('amount') input-error @enderror"
            >
        </div>
        @error('amount')
            <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="space-y-2">
            <label for="transaction_date" class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
                Tanggal
            </label>
            <div class="relative">
                <input
                    type="date"
                    name="transaction_date"
                    id="transaction_date"
                    value="{{ $dateValue }}"
                    required
                    class="date-field input input-bordered w-full font-medium @error('transaction_date') input-error @enderror"
                >
                <svg class="date-field-icon pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            @error('transaction_date')
                <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
                Keterangan / Judul
            </label>
            <input
                type="text"
                name="title"
                id="title"
                value="{{ $titleValue }}"
                placeholder="Contoh: Gaji Bulanan, Beli Kopi"
                required
                class="input input-bordered w-full font-medium @error('title') input-error @enderror"
            >
            @error('title')
                <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div class="space-y-2">
        <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
            Kategori
        </label>
        <input type="hidden" name="category" id="category" value="{{ $categoryValue }}">

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

            $chipBtn = function ($cat) use ($catIcons, $categoryValue) {
                $selected = $categoryValue == $cat;
                $active = $selected ? ' active' : '';
                return '<button type="button" data-category="' . e($cat) . '"' .
                    ' class="cat-chip shrink-0 w-24 sm:w-28 snap-start flex flex-col items-center justify-center gap-1.5 py-2.5 px-1 rounded-box border text-xs font-semibold transition bg-base-200/60 border-base-300 text-base-content/80 hover:border-base-content dark:hover:border-base-300' . $active . '">' .
                    '<span class="w-8 h-8 rounded-lg bg-base-300 flex items-center justify-center transition">' .
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($catIcons[$cat] ?? '') . '</svg></span>' .
                    '<span class="truncate w-full text-center">' . e($cat) . '</span></button>';
            };
        @endphp

        <div id="cat-income">
            <div class="cat-scroll flex gap-2 overflow-x-auto pb-2 snap-x snap-mandatory -mx-1 px-1" role="list">
                @foreach (\App\Models\Category::namesFor(Auth::id(), 'income') as $cat){!! $chipBtn($cat) !!}@endforeach
            </div>
        </div>

        <div id="cat-expense">
            <div class="cat-scroll flex gap-2 overflow-x-auto pb-2 snap-x snap-mandatory -mx-1 px-1" role="list">
                @foreach (\App\Models\Category::namesFor(Auth::id(), 'expense') as $cat){!! $chipBtn($cat) !!}@endforeach
            </div>
        </div>
        @error('category')
            <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    <!-- FITUR LANJUTAN (opsional): Input Suara + Bukti Foto — dilipat
         agar form cepat diisi tanpa gulir jauh. -->
    <div x-data="{ open: @json(isset($transaction) && $transaction->image) }" class="space-y-3">
        <button type="button" @click="open = !open"
                aria-expanded="false" :aria-expanded="open.toString()"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-box border border-base-300 bg-base-200/50 text-base-content/80 hover:bg-base-300 transition">
            <span class="flex items-center gap-2.5 min-w-0">
                <svg class="w-4 h-4 shrink-0 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-sm font-semibold text-base-content">Fitur Lanjutan</span>
                <span class="text-[11px] font-semibold text-base-content/40">(opsional)</span>
            </span>
            <span class="flex items-center gap-1 text-xs text-base-content/40">
                <span class="hidden sm:inline">Suara · Bukti foto</span>
                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </button>

        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">

    <!-- Web Speech API Input Suara (Lokal Bawaan Browser) -->
    <div x-data="voiceInput()" class="space-y-2">
        <label class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
            Input Cepat via Suara <span class="text-base-content/40 font-normal lowercase">(Bawaan Browser)</span>
        </label>
        <button type="button" @click="toggleVoice()"
                :class="recording ? 'border-error/30 bg-error/10 text-error' : 'bg-base-200 text-base-content/80 border-base-300'"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-box border font-semibold text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            <span x-text="recording ? 'Merekam... Ucapkan transaksi (mis: Beli nasi goreng 25 ribu)' : 'Mulai Catat dengan Suara'" x-cloak>Mulai Catat dengan Suara</span>
        </button>
        <p x-show="voiceResult" x-cloak class="text-xs text-base-content font-semibold flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
            Terdengar: <span x-text="voiceResult"></span>
        </p>
    </div>

    <!-- Upload Gambar -->
    <div class="space-y-2">
        <label class="block text-xs font-semibold uppercase tracking-wider text-base-content/70">
            Upload Bukti Transaksi <span class="text-base-content/40 font-normal lowercase">(opsional)</span>
        </label>

        <div class="grid grid-cols-2 gap-3">
            <button type="button"
                    id="btnGallery"
                    class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-base-200 hover:bg-base-300 text-base-content/80 rounded-box border border-base-300 font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Galeri
            </button>

            <button type="button"
                    id="btnCamera"
                    class="btn-upload flex items-center justify-center gap-2 px-4 py-2.5 bg-base-200 hover:bg-base-300 text-base-content/80 rounded-box border border-base-300 font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Kamera
            </button>
        </div>

        <input type="file" name="image" id="fileInput" accept="image/*" class="hidden">

        <div id="dropZone"
             class="relative border-2 border-dashed border-base-300 hover:border-base-content dark:hover:border-base-300 rounded-box p-6 text-center bg-base-200/40 hover:bg-base-300 transition cursor-pointer hidden md:block">

            <div id="uploadPlaceholder" class="space-y-2">
                <div class="w-12 h-12 mx-auto bg-base-content text-base-100 rounded-box flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-base-content/80">
                    <span class="text-base-content font-bold">Klik</span> atau tarik gambar ke sini
                </p>
                <p class="text-xs text-base-content/40">PNG, JPG, JPEG — maks 20MB (otomatis dikompres)</p>
            </div>
        </div>

        <div id="previewContainer" class="hidden">
            <div class="relative overflow-hidden rounded-box border border-base-300 bg-base-100">
                <img id="imagePreview" src="#" alt="Preview bukti transaksi"
                     class="w-full max-h-80 object-contain bg-base-200">
                <div class="flex items-center justify-between gap-2 px-3 py-2.5 border-t border-base-300">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-base-content flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div class="min-w-0">
                            <p id="fileName" class="text-xs font-bold text-base-content truncate"></p>
                            <p id="fileSize" class="text-xs text-base-content/60 font-semibold"></p>
                        </div>
                    </div>
                    <button type="button"
                            id="removeFileBtn"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-base-content border border-base-300 hover:bg-base-content hover:text-white dark:hover:bg-base-200 dark:hover:text-base-content rounded-lg transition flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        @error('image')
            <p class="text-xs text-error font-medium mt-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $message }}
            </p>
        @enderror
    </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 -mx-6 sm:-mx-8 px-6 sm:px-8 py-4 border-t border-base-300 bg-base-100 rounded-b-2xl"
         style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
        <a href="{{ route('transactions.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-base-100 text-base-content/80 hover:bg-base-200 hover:bg-base-content/10 border border-base-300 rounded-box text-xs sm:text-sm font-semibold transition">
            Batal
        </a>
        @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
        <span class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-base-200 text-base-content/40 border border-base-300 rounded-box text-xs sm:text-sm font-semibold cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            Simpan Terkunci (Mode Demo)
        </span>
        @else
        <button type="submit" class="btn btn-primary w-full sm:w-auto gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ $submitLabel ?? 'Simpan Transaksi' }}
        </button>
        @endif
    </div>

</main>
