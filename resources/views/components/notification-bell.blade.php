{{-- Lonceng notifikasi reminder. Dipasang di topbar desktop & mobile pada sidebar. --}}
{{--
  PENTING: kelas warna TIDAK boleh dirakit di JavaScript.

  Tailwind memindai file .blade.php sebagai TEKS dan menghasilkan kelas yang
  dia lihat di sana. String yang dibangun di dalam <script> (`iconClass()`)
  tidak terlihat sama sekali, jadi kalau warnanya hanya muncul di JS, kelasnya
  akan ter-purge dan ikon jadi tidak berwarna — tanpa error apa pun.

  Karena itu pemilihan warnanya dipindah ke Alpine di dalam markup (lihat
  `:class` di bawah): seluruh string-nya literal di file ini, jadi pasti
  ikut ter-build. Semuanya juga memakai token semantic daisyUI
  (error / warning / info), bukan pasangan red-600 + dark:red-400.
--}}
<div class="relative" x-data="notificationBell()">
    <button type="button" @click="toggle()"
            :aria-expanded="open" aria-label="Notifikasi"
            aria-haspopup="true"
            class="btn btn-ghost btn-sm btn-square relative">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        <span x-show="count > 0" x-text="count > 9 ? '9+' : count" x-cloak
              class="badge badge-error badge-sm absolute -top-1 -right-1 min-w-[18px] h-[18px] p-0 text-[10px] leading-none"></span>
    </button>

    {{-- Panel dropdown --}}
    <div x-show="open" x-cloak
         @click.outside="open = false"
         class="card absolute right-0 top-full mt-2 w-[min(92vw,360px)] max-h-[70vh] overflow-y-auto bg-base-100 border border-base-300 z-50 flex flex-col">
        <div class="card-body p-0 flex flex-col min-h-0">
            <div class="px-4 py-3 border-b border-base-300 flex items-center justify-between shrink-0">
                <h2 class="card-title text-sm">Notifikasi</h2>
                <span x-show="count > 0" class="text-[11px] font-semibold text-base-content/40">
                    <span x-text="count"></span> pemberitahuan
                </span>
            </div>
            <div class="divide-y divide-base-300 overflow-y-auto min-h-0">
                <template x-for="item in items" :key="item.title + ':' + item.message">
                <a :href="item.href" class="flex gap-3 px-4 py-3 hover:bg-base-300 transition-colors">
                    {{-- Semua kelas di sini literal: bg-error/10 text-error,
                         bg-warning/10 text-warning, bg-info/10 text-info --}}
                    <span class="flex-shrink-0 w-9 h-9 rounded-box flex items-center justify-center"
                          :class="item.type === 'budget-over' ? 'bg-error/10 text-error' : item.type === 'budget-near' ? 'bg-warning/10 text-warning' : 'bg-info/10 text-info'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-bold text-base-content leading-snug" x-text="item.title"></span>
                        <span class="block text-[11px] text-base-content/60 mt-0.5 leading-relaxed" x-text="item.message"></span>
                    </span>
                </a>
                </template>
                <div x-show="!loading && items.length === 0" class="px-4 py-6 text-center">
                    <p class="text-sm font-semibold text-base-content/60">Semua aman</p>
                    <p class="text-xs text-base-content/40 mt-1">Tidak ada pengingat saat ini.</p>
                </div>
                <div x-show="loading" class="px-4 py-6 text-center">
                    <span class="loading loading-dots loading-sm text-base-content/40"></span>
                    <p class="sr-only">Memuat…</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        open: false,
        loading: true,
        count: 0,
        items: [],
        init() { this.refresh(); },
        toggle() {
            this.open = !this.open;
            if (this.open && !this.loading && this.count === 0 && this.items.length === 0) {
                this.refresh();
            }
        },
        async refresh() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('notifications.index') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                this.count = data.count || 0;
                this.items = data.items || [];
            } catch (e) {
                // ignore — panel hanya tampil kosong
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
