<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Cadangkan database dompetku secara rutin atau manual, lalu unduh file .sql-nya.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Pencadangan - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Pencadangan & Restore - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Pencadangan & Restore" :back="route('transactions.index')" minimal />

    <x-flash />


    @php
        $humanSize = function (int $bytes): string {
            if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
            if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', '.') . ' kB';
            return $bytes . ' B';
        };
        $diskFree = function_exists('disk_free_space') && is_dir($backupDir)
            ? disk_free_space($backupDir)
            : null;
    @endphp

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <section class="card bg-base-100 border border-base-300 shadow-sm p-5 sm:p-6 mb-6">
            <h2 class="card-title text-sm font-bold uppercase tracking-wider">Cadangkan Sekarang</h2>
            <p class="text-xs text-base-content/60 mt-0.5 mb-4">
                Simpan seluruh database ke satu file <code class="font-mono text-base-content">.sql</code>. Aman untuk diunduh kapan pun.
            </p>

            <form method="POST" action="{{ route('backups.store') }}"
                  onsubmit="return confirm('Buat backup baru sekarang? Proses biasanya hanya beberapa detik.')">
                @csrf
                {{-- BUKAN btn-error: aksi ini menambah file baru, bukan menimpa
                     atau menghapus data — warna merah akan menyesatkan. --}}
                <button type="submit" class="btn btn-primary btn-block">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    Buat Backup Sekarang
                </button>
            </form>

            <p class="text-[11px] text-base-content/60 mt-3 leading-relaxed">
                Otomatis: backup dibuat tiap hari pukul 03:00 WIB dan 30 file terakhir dipertahankan.
                Pastikan <strong>cron</strong> di cPanel sudah diaktifkan — lihat <em>Petunjuk Cron</em> di bawah.
            </p>
        </section>

        {{-- Ringkasan. `stats` untuk baris tile, `stat-title`/`stat-value` untuk
             tipologi label/angka. Semuanya angka yang sudah ada di halaman ini
             (jumlah file, batas retensi, sisa disk) — tidak ada data baru. --}}
        <div class="stats stats-vertical sm:stats-horizontal w-full bg-base-100 border border-base-300 shadow-sm mb-6">
            <div class="stat">
                <div class="stat-title text-[11px] font-semibold uppercase tracking-wider">File tersimpan</div>
                <div class="stat-value text-xl tabular-nums">{{ $backups->count() }}</div>
            </div>
            <div class="stat">
                <div class="stat-title text-[11px] font-semibold uppercase tracking-wider">Batas retensi</div>
                <div class="stat-value text-xl tabular-nums">{{ $retentionKeep }}</div>
            </div>
            <div class="stat">
                <div class="stat-title text-[11px] font-semibold uppercase tracking-wider">Ruang disk tersisa</div>
                <div class="stat-value text-xl tabular-nums">
                    @if ($diskFree !== null && $diskFree !== false)
                    {{ $humanSize((int) $diskFree) }}
                    @else
                    &mdash;
                    @endif
                </div>
            </div>
        </div>

        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider">File Backup</h2>
                <span class="badge badge-ghost badge-sm font-medium">{{ $backups->count() }} file</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-base-content/60">
                            <th>File</th>
                            <th>Dibuat</th>
                            <th>Ukuran</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex-shrink-0 w-9 h-9 rounded-box bg-info/10 text-info border border-info/20 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </span>
                                    <span class="min-w-0 block text-xs font-bold text-base-content">{{ $backup['filename'] }}</span>
                                </div>
                            </td>
                            <td class="text-xs text-base-content/60 whitespace-nowrap">{{ $backup['created_at'] }}</td>
                            <td><span class="badge badge-ghost badge-sm font-semibold">{{ $humanSize($backup['size']) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('backups.download', $backup['filename']) }}" class="btn btn-outline btn-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Unduh
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center">
                                <p class="text-sm font-semibold text-base-content/60">Belum ada backup.</p>
                                <p class="text-xs text-base-content/60 mt-1">Klik "Buat Backup Sekarang" untuk membuat file pertama.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-4 mt-6">
            <section class="card bg-base-100 border border-base-300 shadow-sm p-5 sm:p-6">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider mb-3">Petunjuk Cron Otomatis</h2>
                <p class="text-xs text-base-content/60 leading-relaxed">
                    Backup otomatis dijalankan Laravel Scheduler lewat <strong>satu</strong> baris cron. Buka
                    <strong>cPanel &rarr; Cron Jobs</strong> lalu tambahkan:
                </p>
                <pre class="mt-3 p-3 bg-base-100 border border-base-300 rounded-box text-[11px] leading-relaxed overflow-x-auto font-mono text-base-content/80">{{ $cronCommand }}</pre>
                <p class="text-xs text-base-content/60 mt-3 leading-relaxed">
                    Jadwal harian 03:00 WIB akan dipicu sendiri oleh baris ini. Bagian <code class="font-mono text-base-content">cd</code> wajib ada — cPanel menjalankan cron dari home directory, jadi tanpa itu <code class="font-mono text-base-content">php artisan</code> tidak ditemukan dan backup diam-diam tidak jalan. Kode rahasia & data tidak pernah bocor — backup disimpan di <code class="font-mono text-base-content">{{ $backupDir }}</code> (di luar area web).
                </p>
            </section>

            <section class="card bg-base-100 border border-base-300 shadow-sm p-5 sm:p-6">
                <h2 class="card-title text-sm font-bold uppercase tracking-wider mb-3">Cara Restore</h2>
                <ol class="list-decimal ml-5 space-y-1.5 text-xs text-base-content/70 leading-relaxed">
                    <li>Unduh file <code class="font-mono font-semibold text-base-content">{{ $backups->first()['filename'] ?? 'dompetku-...sql' }}</code> di atas, lalu simpan salinan di tempat aman.</li>
                    <li>Untuk pemulihan penuh buka <strong>phpMyAdmin</strong> di cPanel &rarr; database <code class="font-mono font-semibold text-base-content">almahir_keuangan</code> &rarr; tab <em>Import</em> &rarr; pilih file <code class="font-mono font-semibold text-base-content">.sql</code>.</li>
                    <li>File backup sudah berisi <code class="font-mono font-semibold text-base-content">DROP TABLE IF EXISTS</code>, jadi aman diimpor di atas data lama.</li>
                    <li>Praktik terbaik: coba restore ke database uji di komputer lokal sebelum benar-benar butuh.</li>
                </ol>
            </section>
        </div>

        <p class="text-[11px] text-base-content/60 text-center mt-6 px-4 leading-relaxed">
            Backup tersimpan maksimal {{ $retentionKeep }} file. Unduh dan simpan salinan di luar server
            (laptop/cloud) untuk perlindungan berlapis terhadap kegagalan disk.
        </p>
    </div>

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
