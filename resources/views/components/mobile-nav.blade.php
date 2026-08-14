<!-- ============================================================
     MOBILE BOTTOM NAV — gaya prototipe (4 tab, fixed bottom)
     Dipakai di halaman dashboard & AI. Tersembunyi di desktop.
     ============================================================ -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md md:hidden bg-white dark:bg-slate-900 border-t border-slate-200/80 dark:border-slate-800 flex justify-around items-stretch z-40 pb-[env(safe-area-inset-bottom)]">

    <a href="{{ route('transactions.index') }}"
       class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('transactions.index') && !request()->routeIs('ai.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/></svg>
        <span>Beranda</span>
    </a>

    <a href="{{ route('ai.index') }}"
       class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('ai.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 3v3m0 12v3m9-9h-3M6 12H3m13.5-6.5l-2 2m-7 7l-2 2m11 0l-2-2m-7-7l-2-2"/></svg>
        <span>AI</span>
    </a>

    <a href="{{ route('transactions.index') }}#riwayat"
       class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold text-slate-400 dark:text-slate-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Riwayat</span>
    </a>

    <a href="{{ route('profile.edit') }}"
       class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('profile.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span>Profil</span>
    </a>
</nav>
