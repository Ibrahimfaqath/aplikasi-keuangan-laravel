<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola kategori transaksi pemasukan dan pengeluaran di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Kelola Kategori - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Kelola Kategori - dompetku</title>

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
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">

    <x-sidebar title="Kelola Kategori" />

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
        $incomeCategories = $categories->where('type', 'income');
        $expenseCategories = $categories->where('type', 'expense');

        $pillThemes = [
            'green'   => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
            'violet'  => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
            'orange'  => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
            'blue'    => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
            'amber'   => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
            'neutral' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]',
        ];
        $catPillMap = [
            'Gaji' => 'green', 'Bonus' => 'green', 'Bisnis' => 'green', 'Freelance Desain' => 'green',
            'Investasi' => 'violet', 'Hadiah' => 'green', 'Belanja' => 'violet',
            'Makanan & Minuman' => 'orange', 'Transportasi' => 'blue',
            'Tagihan & Utilitas' => 'amber', 'Hiburan' => 'violet',
        ];
        $pillFor = function ($cat) use ($pillThemes, $catPillMap) {
            return $pillThemes[$catPillMap[$cat] ?? 'neutral'];
        };
    @endphp

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.index') }}" aria-label="Kembali ke daftar transaksi"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center">
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Kelola Kategori</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Kategori bawaan + custom pribadimu</p>
            </div>
        </div>

        @if ($isDemo)
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3.5 mb-6">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                Mode demo — daftar kategori hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-amber-900 dark:hover:text-amber-200">Daftar gratis</a> untuk mencoba menambah kategori.
            </p>
        </div>
        @endif

        @if (! $isDemo)
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-5 sm:p-6 mb-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Tambah Kategori Baru</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 mb-4">Kategori custom langsung muncul di form transaksi, filter, dan asisten AI.</p>

            <form method="POST" action="{{ route('categories.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="category-name" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">Nama Kategori</label>
                    <input type="text" name="name" id="category-name" value="{{ old('name') }}"
                           placeholder="Contoh: Jualan Online"
                           required maxlength="50"
                           class="mt-1 w-full px-4 py-2.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition @error('name') border-red-400 bg-red-50/50 @enderror">
                    @error('name')
                        <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300">Jenis Transaksi</label>
                    <div class="mt-1 grid grid-cols-2 gap-1 p-1 bg-neutral-100 dark:bg-[#262626] rounded-xl border border-neutral-200 dark:border-[#333333]" role="radiogroup" aria-label="Jenis Transaksi">
                        <label class="relative flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg cursor-pointer select-none transition-colors has-[:checked]:bg-neutral-900 dark:has-[:checked]:bg-neutral-100 has-[:checked]:text-white dark:has-[:checked]:text-neutral-900 text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100">
                            <input type="radio" name="type" value="income" class="sr-only" {{ old('type', 'income') == 'income' ? 'checked' : '' }} required>
                            <span class="text-xs sm:text-sm font-bold">Pemasukan</span>
                        </label>
                        <label class="relative flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg cursor-pointer select-none transition-colors has-[:checked]:bg-neutral-900 dark:has-[:checked]:bg-neutral-100 has-[:checked]:text-white dark:has-[:checked]:text-neutral-900 text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100">
                            <input type="radio" name="type" value="expense" class="sr-only" {{ old('type') == 'expense' ? 'checked' : '' }} required>
                            <span class="text-xs sm:text-sm font-bold">Pengeluaran</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Tambah Kategori
                </button>
            </form>
        </section>
        @endif

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Kategori Pemasukan</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $incomeCategories->count() }} item</span>
            </div>
            <ul class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($incomeCategories as $cat)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $pillFor($cat->name) }}">
                        <span class="truncate max-w-[180px]">{{ $cat->name }}</span>
                    </span>
                    @if ($cat->user_id)
                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-md px-1.5 py-0.5">Custom</span>
                    @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-300 dark:text-neutral-600 border border-neutral-100 dark:border-[#262626] rounded-md px-1.5 py-0.5">Bawaan</span>
                    @endif
                    <div class="ml-auto">
                        @if ($cat->user_id)
                        <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus kategori \'{{ $cat->name }}\'? Riwayat transaksi lama tetap aman.')"
                                    class="p-1.5 text-neutral-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition" title="Hapus kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                        @else
                        <span class="text-[11px] text-neutral-300 dark:text-neutral-600 pr-1.5" title="Kategori bawaan tidak bisa dihapus">—</span>
                        @endif
                    </div>
                </li>
                @empty
                <li class="py-8 text-center text-xs text-neutral-500 dark:text-neutral-400">Belum ada kategori pemasukan.</li>
                @endforelse
            </ul>
        </section>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden mt-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Kategori Pengeluaran</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $expenseCategories->count() }} item</span>
            </div>
            <ul class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($expenseCategories as $cat)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $pillFor($cat->name) }}">
                        <span class="truncate max-w-[180px]">{{ $cat->name }}</span>
                    </span>
                    @if ($cat->user_id)
                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-md px-1.5 py-0.5">Custom</span>
                    @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-300 dark:text-neutral-600 border border-neutral-100 dark:border-[#262626] rounded-md px-1.5 py-0.5">Bawaan</span>
                    @endif
                    <div class="ml-auto">
                        @if ($cat->user_id)
                        <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus kategori \'{{ $cat->name }}\'? Riwayat transaksi lama tetap aman.')"
                                    class="p-1.5 text-neutral-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition" title="Hapus kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                        @else
                        <span class="text-[11px] text-neutral-300 dark:text-neutral-600 pr-1.5" title="Kategori bawaan tidak bisa dihapus">—</span>
                        @endif
                    </div>
                </li>
                @empty
                <li class="py-8 text-center text-xs text-neutral-500 dark:text-neutral-400">Belum ada kategori pengeluaran.</li>
                @endforelse
            </ul>
        </section>

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Menghapus kategori custom tidak menghapus riwayat transaksimu — kategori lama tetap tersimpan pada tiap transaksi.
        </p>
    </div>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>