<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Import banyak transaksi sekaligus dari file CSV di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Import Transaksi - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Import Transaksi - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Import Transaksi" :back="route('transactions.index')" minimal />

    <x-flash />


    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
        {{-- `alert` hanya dipinjam untuk bentuk & radius-nya; tata letak tetap
             utility `flex` (menimpa grid bawaan daisyUI). Warna lewat token
             semantic supaya kontrasnya pasti di kedua mode. --}}
        <div class="alert flex items-start gap-2.5 p-3.5 mb-5 border border-warning/30 bg-warning/10 text-base-content">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-warning">
                Mode demo — import terkunci. Data hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="link">Daftar gratis</a> untuk mencoba mencatat.
            </p>
        </div>
        @endif

        <form action="{{ route('transactions.import-preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="importUpload()">
            @csrf

            <input type="file" name="file" id="fileInput" x-ref="fileInput" accept=".csv,.txt,text/csv" class="hidden" @change="fileSelected($event)">

            {{-- Drop zone tetap <button> + input[type=file] yang tersembunyi
                 (diklik lewat $refs), bukan <label for>. `card` dipinjam
                 hanya untuk bentuk & radius-nya; luas/tinggi tetap utility. --}}
            <button type="button"
                    @click="$refs.fileInput.click()"
                    class="card w-full border-2 border-dashed border-base-300 bg-base-100 hover:border-base-content/40 hover:bg-base-300/40 p-8 sm:p-10 text-center transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-base-content/20">
                <div x-show="!fileName" class="space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-box bg-base-content text-base-100 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-base-content">
                            <span class="font-bold">Klik</span> atau seret file CSV ke sini
                        </p>
                        <p class="text-xs text-base-content/60 mt-1">CSV / TXT — maks 5MB dan 500 baris data</p>
                    </div>
                </div>
                <div x-show="fileName" x-cloak class="space-y-2">
                    <div class="w-12 h-12 mx-auto rounded-box bg-base-content text-base-100 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-base-content truncate px-6" x-text="fileName"></p>
                    <p class="text-xs text-base-content/60">Klik untuk mengganti file</p>
                </div>
            </button>
            @error('file')
                <p class="alert flex items-start gap-1.5 p-3 mt-1 text-left text-error border border-error/30 bg-error/10">
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs font-medium">{{ $message }}</span>
                </p>
            @enderror

            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-2">
                <a href="{{ route('transactions.import-template') }}" class="btn btn-outline btn-sm w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v13.5m0 0l-4.5-4.5M12 16.5l4.5-4.5M3 21h18"/></svg>
                    Unduh Contoh Template
                </a>
                @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                {{-- `btn-disabled` hanya mematikan pointer-events, jadi tampilan
                     "terkunci"nya tetap dibentuk utility (latar + teks redup). --}}
                <span class="btn btn-sm btn-disabled w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Pratinjau Terkunci (Mode Demo)
                </span>
                @else
                <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    Pratinjau & Validasi
                </button>
                @endif
            </div>
        </form>

        <div class="card bg-base-100 border border-base-300 shadow-sm p-5 sm:p-6 mt-8">
            <h2 class="card-title text-sm font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-base-content/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                Format Kolom
            </h2>
            <p class="text-xs text-base-content/60 mb-4">Baris pertama harus nama kolom (koma atau titik koma). Kolom bisa berurutan bebas.</p>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-base-content/60">
                            <th class="font-bold">Kolom</th>
                            <th class="font-bold">Contoh</th>
                            <th class="font-bold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-semibold text-base-content">Tanggal Transaksi</td>
                            <td class="text-base-content/70 font-mono">2026-09-23</td>
                            <td class="text-base-content/60">Wajib. Format bebas (Y-m-d, d/m/Y, dst).</td>
                        </tr>
                        <tr>
                            <td class="font-semibold text-base-content">Keterangan / Judul</td>
                            <td class="text-base-content/70 font-mono">Belanja Mingguan</td>
                            <td class="text-base-content/60">Wajib.</td>
                        </tr>
                        <tr>
                            <td class="font-semibold text-base-content">Kategori</td>
                            <td class="text-base-content/70 font-mono">Belanja</td>
                            <td class="text-base-content/60">Wajib. Harus kategori yang tersedia (peka huruf besar/kecil).</td>
                        </tr>
                        <tr>
                            <td class="font-semibold text-base-content">Jenis Transaksi</td>
                            <td class="text-base-content/70 font-mono">Pengeluaran</td>
                            <td class="text-base-content/60">"Pemasukan" atau "Pengeluaran". Boleh kosong bila nominal diawali tanda − / +.</td>
                        </tr>
                        <tr>
                            <td class="font-semibold text-base-content">Nominal</td>
                            <td class="text-base-content/70 font-mono">25000</td>
                            <td class="text-base-content/60">Wajib. Dukung "25.000", "25000,50", "Rp 5.000".</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 rounded-box bg-base-100/60 border border-base-300 px-3.5 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">Tips</p>
                <p class="text-xs text-base-content/70 mt-1 leading-relaxed">
                    File CSV hasil <strong>Export Laporan (Excel/PDF)</strong> dan template di atas otomatis dikenali.
                    Paling mudah: unduh template, isi barisnya, lalu unggah kembali.
                </p>
            </div>
        </div>
    </div>

    <script>
        function importUpload() {
            return {
                fileName: '',
                chosenBy: null,
                fileSelected(e) {
                    const f = e.target.files && e.target.files[0];
                    this.fileName = f ? f.name : '';
                }
            };
        }
    </script>

    @include('components.export-modal')

</body>
</html>
