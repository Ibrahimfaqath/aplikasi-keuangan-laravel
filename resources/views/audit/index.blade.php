<!DOCTYPE html>
<html lang="id" class="h-full">

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

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Riwayat Aktivitas" :back="route('transactions.index')" minimal />

    <x-flash />


    @php
        $hasFilter = collect($filters)->filter()->isNotEmpty();

        // Peta aksi -> warna. Nama kelas ditulis LITERAL satu per cabang, bukan
        // dirangkai dari $action: Tailwind memindai file sebagai teks, jadi kelas
        // hasil rakitan ikut ter-purge dan ikonnya jadi tanpa warna sama sekali.
        // Violet lama tidak punya padanan di token semantic, jadi 'auth.register'
        // memakai warna netral.
        $actionMeta = [
            'created'       => ['label' => 'Tambah',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>', 'pill' => 'bg-success/10 text-success border-success/20'],
            'updated'       => ['label' => 'Ubah',          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>', 'pill' => 'bg-info/10 text-info border-info/20'],
            'deleted'       => ['label' => 'Hapus',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>', 'pill' => 'bg-error/10 text-error border-error/20'],
            'restored'      => ['label' => 'Pulihkan',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>', 'pill' => 'bg-warning/10 text-warning border-warning/20'],
            'force_deleted' => ['label' => 'Hapus permanen','icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>', 'pill' => 'bg-error/10 text-error border-error/20'],
            'auth.login'    => ['label' => 'Login',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>', 'pill' => 'bg-success/10 text-success border-success/20'],
            'auth.logout'   => ['label' => 'Logout',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>', 'pill' => 'bg-base-100 text-base-content/70 border-base-300'],
            'auth.register' => ['label' => 'Registrasi',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>', 'pill' => 'bg-base-100 text-base-content/70 border-base-300'],
        ];

        // Peta warna per sumber (web/ai/demo/system). Belum dipakai di view ini —
        // label sumber di bawah dirender sebagai badge netral — tapi tetap
        // disimpan dengan token yang sama agar tidak ada warna hex tersisa.
        $sourcePill = [
            'web'   => 'bg-base-100 text-base-content/70 border-base-300',
            'ai'    => 'bg-base-100 text-base-content/70 border-base-300',
            'demo'  => 'bg-warning/10 text-warning border-warning/20',
            'system'=> 'bg-info/10 text-info border-info/20',
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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <section class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-6">
            <form method="GET" action="{{ route('audit.index') }}" class="space-y-4">
                <h2 class="card-title text-xs font-bold uppercase tracking-wider">Filter</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="audit-action" class="label-text block text-[11px] font-semibold uppercase tracking-wider text-base-content/60 mb-1">Jenis Aksi</label>
                        <x-select-dropdown id="audit-action" name="action"
                                       :value="$filters['action'] ?? ''"
                                       :options="['' => 'Semua aksi', 'created' => 'Tambah', 'updated' => 'Ubah', 'deleted' => 'Hapus', 'restored' => 'Pulihkan', 'force_deleted' => 'Hapus permanen', 'auth' => 'Login / Logout / Registrasi']"
                                       placeholder="Semua aksi"
                                       label="Filter Semua aksi" />
                    </div>

                    <div>
                        <label for="audit-model" class="label-text block text-[11px] font-semibold uppercase tracking-wider text-base-content/60 mb-1">Data</label>
                        <x-select-dropdown id="audit-model" name="model"
                                       :value="$filters['model'] ?? ''"
                                       :options="['' => 'Semua data', 'transaction' => 'Transaksi', 'budget' => 'Anggaran', 'category' => 'Kategori', 'account' => 'Profil']"
                                       placeholder="Semua data"
                                       label="Filter Semua data" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="audit-period" class="label-text block text-[11px] font-semibold uppercase tracking-wider text-base-content/60 mb-1">Periode</label>
                        <x-select-dropdown id="audit-period" name="period"
                                       :value="$filters['period'] ?? ''"
                                       :options="['' => 'Selama ini', 'today' => 'Hari ini', 'week' => '7 hari terakhir', 'month' => 'Bulan ini', 'quarter' => '90 hari terakhir', 'year' => 'Tahun ini']"
                                       placeholder="Selama ini"
                                       label="Filter Selama ini" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        Terapkan Filter
                    </button>
                    @if($hasFilter)
                    <a href="{{ route('audit.index') }}" class="btn btn-outline btn-sm">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </section>

        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider">Log Aktivitas</h2>
                <span class="badge badge-ghost badge-sm font-medium">Total {{ number_format($logs->total(), 0, ',', '.') }} catatan</span>
            </div>

            <ul class="divide-y divide-base-300">
                @forelse ($logs as $log)
                @php
                    $meta = $actionMeta[$log->action] ?? ['label' => $log->actionLabel(), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>', 'pill' => 'bg-base-100 text-base-content/70 border-base-300'];
                @endphp
                <li class="px-5 py-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center flex-shrink-0 w-9 h-9 rounded-box border {{ $meta['pill'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $meta['icon'] !!}</svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="badge badge-sm text-[10px] font-bold uppercase tracking-wider {{ $meta['pill'] }}">{{ $meta['label'] }}</span>
                                <span class="text-xs font-bold text-base-content">{{ $log->modelName() }}</span>
                                <span class="badge badge-ghost badge-sm text-[10px] font-bold uppercase tracking-wider text-base-content/60">{{ $log->sourceLabel() }}</span>
                            </div>
                            <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ $log->description }}</p>

                            @if ($log->new_values)
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($log->new_values as $key => $value)
                                <span class="badge badge-ghost text-[11px] font-normal gap-1">
                                    <span class="text-base-content/60">{{ $key }}</span>:
                                    <span class="font-semibold text-base-content">{{ $fmtValue($key, $value) }}</span>
                                </span>
                                @endforeach
                            </div>
                            @endif

                            <p class="text-[11px] text-base-content/60 mt-2">
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
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-box border border-base-300 bg-base-100 text-base-content/60 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-base-content mb-1">Belum ada aktivitas</p>
                    <p class="text-xs text-base-content/60 max-w-xs mx-auto">
                        @if($hasFilter)
                        Tidak ada catatan yang cocok dengan filter ini. Coba ubah atau reset filter.
                        @else
                        Perubahan transaksi, anggaran, kategori, dan aktivitas masuk/keluar akan muncul di sini.
                        @endif
                    </p>
                    @if($hasFilter)
                    <a href="{{ route('audit.index') }}" class="btn btn-primary btn-sm mt-4">Tampilkan Semua</a>
                    @endif
                </li>
                @endforelse
            </ul>

            @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-base-300">
                {{ $logs->links() }}
            </div>
            @endif
        </section>

        <p class="text-[11px] text-base-content/60 text-center mt-6 px-4 leading-relaxed">
            Riwayat disimpan selama-lamanya dan tidak bisa diubah atau dihapus demi keamanan datamu.
        </p>
    </div>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
