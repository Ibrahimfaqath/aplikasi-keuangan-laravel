<!--
  Modal export laporan.

 Angka ini memakai komponen `modal` daisyUI. Visibilitasnya dikendalikan
  JS (bukan `<dialog>`/checkbox bawaan daisyUI), jadi yang kita lakukan hanya
  toggle kelas `modal-open`; posisi tengah, overlay, radius, dan animasinya
  semuanya milik daisyUI.

  Scrim tidak disediakan daisyUI 4 untuk modal yang dibuka lewat
  `.modal-open` (`.modal-backdrop` hanya `color: transparent`), jadi
  `modal-backdrop` di bawah kita isi sendiri. Warnanya `neutral-950`, sengaja
  bukan `base-content`: di tema gelap base-content terang dan scrim-nya akan
  terbalik jadi menerang.
-->
<div id="exportModal" class="modal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="modal-box p-0 sm:max-w-lg">

        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-base-300">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-box bg-base-content text-base-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 id="modal-title" class="text-base font-bold text-base-content">Export &amp; Report Generator</h3>
                    <p class="text-xs text-base-content/60">Pilih format &amp; rentang waktu laporan keuangan</p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal()" aria-label="Tutup" class="btn btn-ghost btn-sm btn-square">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="exportForm" method="GET" action="" class="p-6 space-y-5">
            <!-- Filter yang sedang aktif ikut dibawa, supaya hasil export sama
                 dengan tabel yang sedang dilihat. -->
            <input type="hidden" name="search" value="{{ request('search') }}">

            <!-- Format: radio + satu tombol cetak, digabung sebagai segoup -->
            <div>
                <p class="label-text text-base-content/60 mb-2">Pilih Format Dokumentasi</p>
                <div class="grid grid-cols-3 gap-3">
                    <label class="flex flex-col items-center justify-center p-3 rounded-box border border-base-300 bg-base-200/50 cursor-pointer hover:border-base-content has-[:checked]:bg-base-content has-[:checked]:border-base-content has-[:checked]:text-base-100 transition">
                        <input type="radio" name="export_format" value="pdf" class="radio radio-xs sr-only" checked onchange="updateExportAction('/transactions/export-pdf')">
                        <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-bold">PDF Report</span>
                    </label>
                    <label class="flex flex-col items-center justify-center p-3 rounded-box border border-base-300 bg-base-200/50 cursor-pointer hover:border-base-content has-[:checked]:bg-base-content has-[:checked]:border-base-content has-[:checked]:text-base-100 transition">
                        <input type="radio" name="export_format" value="excel" class="radio radio-xs sr-only" onchange="updateExportAction('/transactions/export-excel')">
                        <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs font-bold">Excel Spreadsheet</span>
                    </label>
                    <button type="button" onclick="triggerPrintMode()" class="flex flex-col items-center justify-center p-3 rounded-box border border-base-300 bg-base-200/50 hover:border-base-content text-base-content transition">
                        <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span class="text-xs font-bold">Print Screen</span>
                    </button>
                </div>
            </div>

            <!-- Periode. onchange mengirim value ke toggleCustomDates; versi
                 sebelumnya memanggilnya tanpa argumen sehingga rentang tanggal
                 khusus tidak pernah tampil. -->
            <div>
                <label for="modalPeriod" class="label-text text-base-content/60 mb-1.5">Periode Laporan</label>
                <select id="modalPeriod" name="period" onchange="toggleCustomDates(this.value)"
                        class="select select-bordered select-sm w-full">
                    <option value="all" selected>Semua Transaksi</option>
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="7_days">7 Hari Terakhir</option>
                    <option value="30_days">30 Hari Terakhir</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Rentang Tanggal Khusus</option>
                </select>
            </div>

            <!-- Rentang khusus, hanya tampil saat periode = "custom" -->
            <div id="modalCustomDates" class="grid grid-cols-2 gap-3 hidden">
                <div>
                    <label for="start_date" class="label-text text-base-content/60 mb-1">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" class="input input-bordered input-sm w-full">
                </div>
                <div>
                    <label for="end_date" class="label-text text-base-content/60 mb-1">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="input input-bordered input-sm w-full">
                </div>
            </div>

            <!-- Tipe. SENGAJA tanpa onchange: nilai di sini hanya ikut ke
                 form export, bukan ke filter halaman. -->
            <div>
                <label for="modalType" class="label-text text-base-content/60 mb-1.5">Tipe Transaksi</label>
                <select id="modalType" name="type" class="select select-bordered select-sm w-full">
                    <option value="">Semua Tipe (Pemasukan &amp; Pengeluaran)</option>
                    <option value="income">Hanya Pemasukan</option>
                    <option value="expense">Hanya Pengeluaran</option>
                </select>
            </div>

            <div class="modal-action mt-0 pt-4 border-t border-base-300">
                <button type="button" onclick="closeExportModal()" class="btn btn-ghost btn-sm">Batal</button>
                <button type="submit" id="btnSubmitExport" class="btn btn-primary btn-sm gap-2">
                    <svg id="iconExportSubmit" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span id="textExportSubmit">Generate &amp; Download</span>
                </button>
            </div>
        </form>
    </div>

    <div class="modal-backdrop bg-neutral-950/70 backdrop-blur-sm" onclick="closeExportModal()"></div>
</div>

<script>
    // Semua fungsi ini dipanggil dari sidebar ("Export Laporan") dan dari
    // tombol di halaman transaksi, jadi nama globalnya tidak boleh berubah.
    function openExportModal() {
        document.getElementById('exportModal').classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        updateExportAction('/transactions/export-pdf');
    }

    function closeExportModal() {
        document.getElementById('exportModal').classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    function updateExportAction(url) {
        document.getElementById('exportForm').action = url;
    }

    function toggleCustomDates(value) {
        var container = document.getElementById('modalCustomDates');
        if (value === 'custom') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    function triggerPrintMode() {
        closeExportModal();
        window.print();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('exportForm');
        if (!form) return;

        form.addEventListener('submit', function () {
            var btn = document.getElementById('btnSubmitExport');
            var text = document.getElementById('textExportSubmit');

            btn.disabled = true;
            text.textContent = 'Prosedur Export...';

            setTimeout(function () {
                btn.disabled = false;
                text.textContent = 'Generate & Download';
                closeExportModal();
            }, 3000);
        });
    });
</script>
