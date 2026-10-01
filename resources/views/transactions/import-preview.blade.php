<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Pratinjau hasil import CSV transaksi di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Pratinjau Import - dompetku">
    <title>Pratinjau Import - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Pratinjau Import" :back="route('transactions.import')" minimal />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <p class="text-xs font-medium text-base-content/60 mb-4 truncate">File: {{ $filename }}</p>

        <!-- Ringkasan -->
        {{-- `alert` hanya untuk bentuk/radius; warna ditulis eksplisit lewat
             token semantic (LIHAT components/flash.blade.php) karena modifier
             alert-success/alert-error mencampur base-100 dan kontrasnya tidak
             bisa diprediksi di mode gelap. --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-6">
            <div class="alert flex flex-col items-center text-center p-4 border border-success/30 bg-success/10 text-base-content">
                <p class="stat-value text-2xl font-extrabold tabular-nums text-success">{{ count($valid) }}</p>
                <p class="stat-title text-[11px] font-semibold uppercase tracking-wider text-base-content/70">Siap Diimport</p>
            </div>
            <div class="alert flex flex-col items-center text-center p-4 border border-error/30 bg-error/10 text-base-content">
                <p class="stat-value text-2xl font-extrabold tabular-nums text-error">{{ count($invalid) }}</p>
                <p class="stat-title text-[11px] font-semibold uppercase tracking-wider text-base-content/70">Perlu Diperbaiki</p>
            </div>
        </div>

        @if (count($valid) > 0)
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider">Transaksi Valid</h2>
                <span class="badge badge-ghost badge-sm font-semibold">Total: Rp {{ number_format(collect($valid)->sum('amount'), 0, ',', '.') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-base-content/50">
                            <th class="font-bold">Tanggal</th>
                            <th class="font-bold">Keterangan</th>
                            <th class="font-bold">Kategori</th>
                            <th class="font-bold">Jenis</th>
                            <th class="font-bold text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($valid as $row)
                        <tr>
                            <td class="text-base-content/70 font-mono whitespace-nowrap">{{ $row['transaction_date'] }}</td>
                            <td class="font-medium text-base-content">{{ $row['title'] }}</td>
                            <td>
                                <span class="badge badge-ghost badge-sm text-[11px]">{{ $row['category'] }}</span>
                            </td>
                            <td>
                                @if ($row['type'] === 'income')
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-success">+ Pemasukan</span>
                                @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-error">− Pengeluaran</span>
                                @endif
                            </td>
                            <td class="text-right font-bold tabular-nums text-base-content whitespace-nowrap">Rp {{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @else
        <div class="card items-center bg-base-100 border border-base-300 shadow-sm p-6 text-center mb-6">
            <p class="text-sm font-semibold text-base-content/60">Tidak ada transaksi valid di file ini. Periksa baris yang bermasalah lalu unggah ulang.</p>
        </div>
        @endif

        @if (count($invalid) > 0)
        <section class="card bg-base-100 border border-error/30 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-base-300">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider">Transaksi Bermasalah <span class="text-base-content/40 normal-case">(tidak akan diimport)</span></h2>
            </div>
            <div class="divide-y divide-base-300">
                @foreach ($invalid as $item)
                <div class="flex gap-3 px-5 py-3.5">
                    <span class="flex-shrink-0 w-9 h-9 rounded-box bg-error/10 text-error border border-error/20 flex items-center justify-center text-xs font-bold">{{ $item['row'] }}</span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/50">Baris #{{ $item['row'] }}</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($item['errors'] as $error)
                            <li class="text-xs text-error flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $error }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        <form action="{{ route('transactions.import-store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('transactions.import') }}" class="btn btn-outline btn-sm w-full sm:w-auto">
                    Pilih File Lain
                </a>
                @if (count($valid) > 0)
                <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan {{ count($valid) }} Transaksi
                </button>
                @endif
            </div>
        </form>
    </div>

    @include('components.export-modal')

</body>
</html>
