<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Riwayat aktivitas keuangan di dompetku: semua perubahan transaksi, anggaran, kategori, dan akun dicatat otomatis.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Riwayat Aktivitas - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Riwayat Aktivitas - dompetku</title>

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

    <x-sidebar title="Riwayat Aktivitas" />

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
        $hasFilter = collect($filters)->filter()->isNotEmpty();

        $actionMeta = [
            'created'       => ['label' => 'Tambah',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>', 'pill' => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20'],
            'updated'       => ['label' => 'Ubah',          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>', 'pill' => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20'],
            'deleted'       => ['label' => 'Hapus',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>', 'pill' => 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20'],
            'restored'      => ['label' => 'Pulihkan',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>', 'pill' => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20'],
            'force_deleted' => ['label' => 'Hapus permanen','icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>', 'pill' => 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20'],
            'auth.login'    => ['label' => 'Login',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>', 'pill' => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20'],
            'auth.logout'   => ['label' => 'Logout',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>', 'pill' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]'],
            'auth.register' => ['label' => 'Registrasi',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>', 'pill' => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20'],
        ];

        $sourcePill = [
            'web'   => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]',
            'ai'    => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
            'demo'  => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
            'system'=> 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
        ];

        $fmtValue = function ($key, $value) {
            if ($value === null || $value === '') return '—';
            if ($key === 'amount') return 'Rp '.number_format((float) $value, 0, ',', '.');
            if (in_array($key, ['transaction_date'], true)) return substr((string) $value, 0, 10);
            if (is_string($value) && strlen($value) > 40) return substr($value, 0, 40).'…';
            if (is_bool($value)) return $value ? 'ya' : 'tidak';
            return (string) $value;
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
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Riwayat Aktivitas</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Semua perubahan dicatat otomatis oleh sistem</p>
            </div>
        </div>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-5 mb-6">
            <form method="GET" action="{{ route('audit.index') }}" class="space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Filter</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="audit-action" class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1">Jenis Aksi</label>
                        <select name="action" id="audit-action"
                                class="w-full px-3 py-2.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                            <option value="">Semua aksi</option>
                            <option value="created" @selected(($filters['action'] ?? '') === 'created')>Tambah</option>
                            <option value="updated" @selected(($filters['action'] ?? '') === 'updated')>Ubah</option>
                            <option value="deleted" @selected(($filters['action'] ?? '') === 'deleted')>Hapus</option>
                            <option value="restored" @selected(($filters['action'] ?? '') === 'restored')>Pulihkan</option>
                            <option value="force_deleted" @selected(($filters['action'] ?? '') === 'force_deleted')>Hapus permanen</option>
                            <option value="auth" @selected(($filters['action'] ?? '') === 'auth')>Login / Logout / Registrasi</option>
                        </select>
                    </div>

                    <div>
                        <label for="audit-model" class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1">Data</label>
                        <select name="model" id="audit-model"
                                class="w-full px-3 py-2.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                            <option value="">Semua data</option>
                            <option value="transaction" @selected(($filters['model'] ?? '') === 'transaction')>Transaksi</option>
                            <option value="budget" @selected(($filters['model'] ?? '') === 'budget')>Anggaran</option>
                            <option value="category" @selected(($filters['model'] ?? '') === 'category')>Kategori</option>
                            <option value="account" @selected(($filters['model'] ?? '') === 'account')>Profil</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="audit-period" class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1">Periode</label>
                        <select name="period" id="audit-period"
                                class="w-full px-3 py-2.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                            <option value="">Selama ini</option>
                            <option value="today" @selected(($filters['period'] ?? '') === 'today')>Hari ini</option>
                            <option value="week" @selected(($filters['period'] ?? '') === 'week')>7 hari terakhir</option>
                            <option value="month" @selected(($filters['period'] ?? '') === 'month')>Bulan ini</option>
                            <option value="quarter" @selected(($filters['period'] ?? '') === 'quarter')>90 hari terakhir</option>
                            <option value="year" @selected(($filters['period'] ?? '') === 'year')>Tahun ini</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        Terapkan Filter
                    </button>
                    @if($hasFilter)
                    <a href="{{ route('audit.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-neutral-600 dark:text-neutral-300 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] hover:bg-neutral-200 dark:hover:bg-[#333333] transition">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </section>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Log Aktivitas</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total {{ number_format($logs->total(), 0, ',', '.') }} catatan</span>
            </div>

            <ul class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($logs as $log)
                @php
                    $meta = $actionMeta[$log->action] ?? ['label' => $log->actionLabel(), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>', 'pill' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]'];
                @endphp
                <li class="px-5 py-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center flex-shrink-0 w-9 h-9 rounded-xl border {{ $meta['pill'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $meta['icon'] !!}</svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="text-xs font-bold text-neutral-900 dark:text-neutral-50">{{ $meta['label'] }} {{ $log->modelName() }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-md px-1.5 py-0.5">{{ $log->sourceLabel() }}</span>
                            </div>
                            <p class="text-xs text-neutral-600 dark:text-neutral-300 mt-1 leading-relaxed">{{ $log->description }}</p>

                            @if ($log->new_values)
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($log->new_values as $key => $value)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-neutral-50 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] px-2 py-1 text-[11px] text-neutral-600 dark:text-neutral-300">
                                    <span class="text-neutral-400 dark:text-neutral-500">{{ $key }}</span>:
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $fmtValue($key, $value) }}</span>
                                </span>
                                @endforeach
                            </div>
                            @endif

                            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-2">
                                {{ $log->created_at ? $log->created_at->format('d M Y, H:i') : '—' }}
                                @if($log->ip_address)
                                <span class="mx-1">·</span>{{ $log->ip_address }}
                                @endif
                            </p>
                        </div>
                    </div>
                </li>
                @empty
                <li class="py-12 px-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-1">Belum ada aktivitas</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 max-w-xs mx-auto">
                        @if($hasFilter)
                        Tidak ada catatan yang cocok dengan filter ini. Coba ubah atau reset filter.
                        @else
                        Perubahan transaksi, anggaran, kategori, dan aktivitas masuk/keluar akan muncul di sini.
                        @endif
                    </p>
                    @if($hasFilter)
                    <a href="{{ route('audit.index') }}" class="inline-flex mt-4 px-4 py-2 rounded-xl text-xs font-semibold text-white dark:text-neutral-900 bg-neutral-900 dark:bg-neutral-100 hover:bg-black dark:hover:bg-white transition">Tampilkan Semua</a>
                    @endif
                </li>
                @endforelse
            </ul>

            @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-neutral-200 dark:border-[#333333]">
                {{ $logs->links() }}
            </div>
            @endif
        </section>

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Riwayat disimpan selama-lamanya dan tidak bisa diubah atau dihapus demi keamanan datamu.
        </p>
    </div>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>