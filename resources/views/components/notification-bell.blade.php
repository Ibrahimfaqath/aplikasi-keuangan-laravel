{{-- Lonceng notifikasi reminder. Dipasang di topbar desktop & mobile pada sidebar. --}}
<div class="relative" x-data="notificationBell()">
    <button type="button" @click="toggle()"
            :aria-expanded="open" aria-label="Notifikasi"
            aria-haspopup="true"
            class="relative flex items-center justify-center w-10 h-10 rounded-xl border border-neutral-200 dark:border-[#262626] bg-white dark:bg-[#171717] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        <span x-show="count > 0" x-text="count > 9 ? '9+' : count" x-cloak
              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center leading-none"></span>
    </button>

    <!-- Panel dropdown -->
    <div x-show="open" x-cloak
         @click.outside="open = false"
         class="absolute right-0 top-full mt-2 w-[min(92vw,360px)] max-h-[70vh] overflow-y-auto bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-xl z-50 flex flex-col">
        <div class="px-4 py-3 border-b border-neutral-200 dark:border-[#333333] flex items-center justify-between">
            <h2 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Notifikasi</h2>
            <span x-show="count > 0" class="text-[11px] font-semibold text-neutral-400 dark:text-neutral-500">
                <span x-text="count"></span> pemberitahuan
            </span>
        </div>
        <div class="divide-y divide-neutral-100 dark:divide-[#262626]">
            <template x-for="item in items" :key="item.title + ':' + item.message">
            <a :href="item.href" class="flex gap-3 px-4 py-3 hover:bg-neutral-50 dark:hover:bg-[#262626]/50 transition-colors">
                <span :class="iconClass(item.type)" class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold text-neutral-900 dark:text-neutral-50 leading-snug" x-text="item.title"></span>
                    <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 leading-relaxed" x-text="item.message"></span>
                </span>
            </a>
            </template>
            <div x-show="!loading && items.length === 0" class="px-4 py-6 text-center">
                <p class="text-sm font-semibold text-neutral-500 dark:text-neutral-400">Semua aman</p>
                <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">Tidak ada pengingat saat ini.</p>
            </div>
            <div x-show="loading" class="px-4 py-6 text-center">
                <p class="text-xs text-neutral-400 dark:text-neutral-500">Memuat…</p>
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
        iconClass(type) {
            if (type === 'budget-over') return 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20';
            if (type === 'budget-near') return 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20';
            return 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20';
        }
    };
}
</script>