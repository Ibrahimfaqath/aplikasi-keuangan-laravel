<!-- Modal Container -->
<div id="exportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop Blur -->
    <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeExportModal()"></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm transition-all sm:my-8 sm:w-full sm:max-w-lg">

            <!-- Header Modal -->
            <div class="flex items-center justify-between p-6 border-b border-neutral-200 dark:border-[#333333]">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-50">Export & Report Generator</h3>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Pilih format & rentang waktu laporan keuangan</p>
                    </div>
                </div>
                <button type="button" onclick="closeExportModal()" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="exportForm" method="GET" action="" class="p-6 space-y-5">
                <!-- Preset Filter Hidden Keep -->
                <input type="hidden" name="search" value="{{ request('search') }}">

                <!-- Format Selection -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">Pilih Format Dokumentasi</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#262626]/50 cursor-pointer hover:border-neutral-900 dark:hover:border-neutral-100 has-[:checked]:bg-neutral-900 dark:has-[:checked]:bg-neutral-100 has-[:checked]:border-neutral-900 dark:has-[:checked]:border-neutral-100 has-[:checked]:text-white dark:has-[:checked]:text-neutral-900 transition group">
                            <input type="radio" name="export_format" value="pdf" class="sr-only" checked onchange="updateExportAction('/transactions/export-pdf')">
                            <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span class="text-xs font-bold">PDF Report</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#262626]/50 cursor-pointer hover:border-neutral-900 dark:hover:border-neutral-100 has-[:checked]:bg-neutral-900 dark:has-[:checked]:bg-neutral-100 has-[:checked]:border-neutral-900 dark:has-[:checked]:border-neutral-100 has-[:checked]:text-white dark:has-[:checked]:text-neutral-900 transition group">
                            <input type="radio" name="export_format" value="excel" class="sr-only" onchange="updateExportAction('/transactions/export-excel')">
                            <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-xs font-bold">Excel Spreadsheet</span>
                        </label>
                        <button type="button" onclick="triggerPrintMode()" class="flex flex-col items-center justify-center p-3 rounded-xl border border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#262626]/50 hover:border-neutral-900 dark:hover:border-neutral-100 text-neutral-900 dark:text-neutral-100 transition">
                            <svg class="w-6 h-6 mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span class="text-xs font-bold">Print Screen</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Period Filter -->
                <div>
                    <label for="modalPeriod" class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">Periode Laporan</label>
                    <x-custom-select name="period" id="modalPeriod" label="Periode laporan" hide-label
                        :options="['all' => 'Semua Transaksi', 'today' => 'Hari Ini', 'yesterday' => 'Kemarin', '7_days' => '7 Hari Terakhir', '30_days' => '30 Hari Terakhir', 'this_month' => 'Bulan Ini', 'last_month' => 'Bulan Lalu', 'this_year' => 'Tahun Ini', 'custom' => 'Rentang Tanggal Khusus']"
                        selected="all" onchange="toggleCustomDates" />
                </div>

                <!-- Custom Range Inputs -->
                <div id="modalCustomDates" class="grid grid-cols-2 gap-3 hidden">
                    <div>
                        <label class="block text-xs text-neutral-500 dark:text-neutral-400 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" class="w-full px-3 py-2 bg-neutral-50 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl text-xs text-neutral-900 dark:text-neutral-100">
                    </div>
                    <div>
                        <label class="block text-xs text-neutral-500 dark:text-neutral-400 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 bg-neutral-50 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl text-xs text-neutral-900 dark:text-neutral-100">
                    </div>
                </div>

                <!-- Filter Tipe Transaksi -->
                <div>
                    <label for="modalType" class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">Tipe Transaksi</label>
                    <x-custom-select name="type" id="modalType" label="Tipe transaksi" hide-label
                        :options="['' => 'Semua Tipe (Pemasukan & Pengeluaran)', 'income' => 'Hanya Pemasukan', 'expense' => 'Hanya Pengeluaran']"
                        selected="" />
                </div>

                <!-- Submit Button with Loading State -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                    <button type="button" onclick="closeExportModal()" class="px-4 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm font-semibold hover:bg-neutral-50 dark:hover:bg-[#333333] transition">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitExport" class="inline-flex items-center gap-2 px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                        <svg id="iconExportSubmit" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span id="textExportSubmit">Generate & Download</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    function openExportModal() {
        document.getElementById('exportModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateExportAction('/transactions/export-pdf');
    }

    function closeExportModal() {
        document.getElementById('exportModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function updateExportAction(url) {
        document.getElementById('exportForm').action = url;
    }

    function toggleCustomDates(value) {
        const container = document.getElementById('modalCustomDates');
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

    document.getElementById('exportForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitExport');
        const text = document.getElementById('textExportSubmit');

        btn.disabled = true;
        text.textContent = 'Prosedur Export...';

        setTimeout(() => {
            btn.disabled = false;
            text.textContent = 'Generate & Download';
            closeExportModal();
        }, 3000);
    });
</script>
