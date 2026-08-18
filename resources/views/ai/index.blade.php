<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu AI — asisten keuangan pintar untuk analisis, saran hemat, dan baca struk otomatis.">
    <meta name="theme-color" content="#0A1128">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <title>DompetKu AI — Asisten Keuangan</title>

    <!-- Theme Init -->
    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                // Default tema gelap navy (#0A1128) — mode terang hanya jika user memilihnya
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A1128' : '#f8fafc';
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { overflow-x: hidden; }

        /* Animasi bubble masuk */
        @keyframes bubble-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .bubble { animation: bubble-in .25s ease both; }

        /* Titik mengetik (typing indicator) */
        @keyframes typing-bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: .5; }
            40% { transform: translateY(-4px); opacity: 1; }
        }
        .typing-dot { animation: typing-bounce 1.2s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: .15s; }
        .typing-dot:nth-child(3) { animation-delay: .3s; }

        /* Scrollbar chat yang halus */
        #chatScroll::-webkit-scrollbar { width: 6px; }
        #chatScroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .dark #chatScroll::-webkit-scrollbar-thumb { background: #404040; }

        /* Sembunyikan bottom nav saat kolom chat difokus (keyboard terbuka di mobile) */
        body.chat-input-focus #bottomNav { transform: translateY(110%); }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased flex flex-col">

    <!-- NAVBAR -->
    <x-navbar />

    <!-- Toast (mis. setelah bersihkan riwayat) -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-navy-900 rounded-2xl shadow-xl border border-slate-100 dark:border-navy-800">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-white/90">{{ session('success') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <!-- ============================================================
         HALAMAN AI — layout chat penuh ala Gemini:
         kolom chat di tengah + input mengambang di bawah
         ============================================================ -->
    <div class="w-full flex-1 min-h-0 flex flex-col"
         x-data="aiPage()"
         x-init="init()">

        <!-- AREA CHAT (scroll) -->
        <div id="chatScroll" x-ref="chatScroll" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden pb-52 md:pb-32">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-5">

                <!-- Tombol bersihkan riwayat (muncul jika ada chat) -->
                <div class="flex justify-end" x-show="messages.length > 0" x-cloak>
                    <form method="POST" action="{{ route('ai.clear') }}" onsubmit="return confirm('Hapus seluruh riwayat chat AI?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold text-slate-500 dark:text-white/70 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-full transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Bersihkan riwayat
                        </button>
                    </form>
                </div>

                <!-- EMPTY STATE: saran awal -->
                <template x-if="messages.length === 0 && !sending">
                    <div class="text-center pt-10 sm:pt-16">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-50 dark:bg-navy-400/10 border border-blue-100 dark:border-navy-400/25 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Halo, ada yang bisa dibantu?</h2>
                        <p class="text-sm text-slate-500 dark:text-white/70 mt-1.5 max-w-sm mx-auto">
                            Tanya apa saja tentang keuanganmu, atau foto struk belanja untuk dicatat otomatis.
                        </p>

                        <!-- Kartu saran -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-8 max-w-lg mx-auto">
                            <button type="button" @click="sendMessage('Bagaimana ringkasan keuangan saya bulan ini?')"
                                    class="text-left p-4 bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm hover:border-blue-300 dark:hover:border-navy-400/40 hover:shadow-md transition group">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center mb-2.5 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Ringkasan bulan ini</p>
                                <p class="text-[11px] text-slate-500 dark:text-white/70 mt-0.5">Saldo, pemasukan, dan pengeluaran</p>
                            </button>
                            <button type="button" @click="sendMessage('Kategori mana yang paling banyak pengeluaran saya?')"
                                    class="text-left p-4 bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm hover:border-blue-300 dark:hover:border-navy-400/40 hover:shadow-md transition group">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center mb-2.5 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Analisis kategori</p>
                                <p class="text-[11px] text-slate-500 dark:text-white/70 mt-0.5">Lihat pengeluaran terbesar</p>
                            </button>
                            <button type="button" @click="sendMessage('Berikan tips menghemat pengeluaran saya')"
                                    class="text-left p-4 bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm hover:border-blue-300 dark:hover:border-navy-400/40 hover:shadow-md transition group">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center mb-2.5 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Tips hemat</p>
                                <p class="text-[11px] text-slate-500 dark:text-white/70 mt-0.5">Saran mengelola pengeluaran</p>
                            </button>
                            <button type="button" @click="$refs.ocrInput.click()"
                                    class="text-left p-4 bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm hover:border-blue-300 dark:hover:border-navy-400/40 hover:shadow-md transition group">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center mb-2.5 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">Scan struk</p>
                                <p class="text-[11px] text-slate-500 dark:text-white/70 mt-0.5">Foto struk, data otomatis terisi</p>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- PESAN (x-for dari riwayat session + pesan baru) -->
                <template x-for="(msg, i) in messages" :key="i">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex gap-2.5'" class="bubble">

                        <!-- Avatar AI -->
                        <div x-show="msg.role !== 'user'"
                             class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-navy-400/10 border border-blue-100 dark:border-navy-400/25 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                        </div>

                        <div :class="msg.role === 'user'
                                    ? 'bg-blue-600 dark:bg-navy-400 dark:text-white text-white rounded-2xl rounded-tr-md px-4 py-2.5 max-w-[85%]'
                                    : 'bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-2.5 max-w-[85%] shadow-sm'">

                            <!-- Pesan teks biasa -->
                            <p class="text-sm whitespace-pre-wrap break-words" :class="msg.role === 'user' ? 'text-white dark:text-white' : 'text-slate-700 dark:text-white/80'" x-html="formatMessage(msg.text)"></p>

                            <!-- KARTU KONFIRMASI TRANSAKSI (niat transaksi dari chat) -->
                            <div x-show="msg.transaction" class="mt-2.5 rounded-xl border border-slate-200 dark:border-navy-700 overflow-hidden bg-white dark:bg-navy-950/40">
                                <div class="px-3.5 py-2.5 space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-bold text-slate-800 dark:text-white truncate" x-text="msg.transaction?.title"></span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase flex-shrink-0"
                                              :class="msg.transaction?.type === 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'"
                                              x-text="msg.transaction?.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs text-slate-500 dark:text-white/60" x-text="msg.transaction?.category"></span>
                                        <span class="text-sm font-extrabold text-slate-900 dark:text-white" x-text="formatRp(msg.transaction?.amount)"></span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-white/50" x-text="msg.transaction?.transaction_date"></p>
                                </div>
                                <button type="button" @click="saveTransaction(msg)" :disabled="msg.saved || msg.saving"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 text-xs font-bold border-t border-slate-100 dark:border-navy-800 transition"
                                        :class="msg.saved ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white disabled:opacity-40 disabled:cursor-not-allowed'">
                                    <svg x-show="msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="!msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span x-text="msg.saved ? 'Tersimpan ✓' : (msg.saving ? 'Menyimpan...' : 'Simpan Transaksi')"></span>
                                </button>
                            </div>

                            <!-- KARTU HASIL SCAN STRUK (item per item) -->
                            <div x-show="msg.ocrItems" class="mt-2.5 rounded-xl border border-slate-200 dark:border-navy-700 overflow-hidden bg-white dark:bg-navy-950/40">
                                <div class="px-3.5 py-2 border-b border-slate-100 dark:border-navy-800 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold text-slate-700 dark:text-white/80">Hasil scan — <span x-text="msg.ocrItems?.length"></span> item</span>
                                    <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-white/60 cursor-pointer select-none">
                                        <input type="checkbox" class="rounded border-slate-300 dark:border-navy-600 text-blue-600 dark:text-navy-400 focus:ring-blue-500 w-3.5 h-3.5"
                                               :checked="msg.selected?.every(Boolean)"
                                               @change="msg.selected = msg.selected.map(() => $event.target.checked)">
                                        Semua
                                    </label>
                                </div>
                                <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-navy-800">
                                    <template x-for="(it, idx) in (msg.ocrItems || [])" :key="idx">
                                        <label class="flex items-start gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-navy-800/50 transition">
                                            <input type="checkbox" x-model="msg.selected[idx]"
                                                   class="mt-0.5 rounded border-slate-300 dark:border-navy-600 text-blue-600 dark:text-navy-400 focus:ring-blue-500 w-3.5 h-3.5 flex-shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="it.title"></span>
                                                    <span class="text-xs font-extrabold text-slate-900 dark:text-white flex-shrink-0" x-text="formatRp(it.amount)"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 dark:text-white/60 mt-0.5 truncate" x-text="it.category + ' · ' + (it.type === 'income' ? 'Pemasukan' : 'Pengeluaran')"></p>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                                <button type="button" @click="saveTransaction(msg)"
                                        :disabled="msg.saved || msg.saving || !(msg.selected || []).some(Boolean)"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 text-xs font-bold border-t border-slate-100 dark:border-navy-800 transition"
                                        :class="msg.saved ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white disabled:opacity-40 disabled:cursor-not-allowed'">
                                    <svg x-show="msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="!msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span x-text="msg.saved ? 'Semua tersimpan ✓' : (msg.saving ? 'Menyimpan...' : 'Simpan ' + selectedCount(msg) + ' transaksi')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TYPING INDICATOR -->
                <div x-show="sending" class="flex gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-navy-400/10 border border-blue-100 dark:border-navy-400/25 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                    </div>
                    <div class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-3 flex items-center gap-1.5">
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- INPUT MENGAMBANG: menempel sampai bawah layar.
             Lapisan SOLID menutup penuh celah antara pil input & navbar (pesan
             benar-benar hilang saat di-scroll), lapisan GRADIEN di atas pil memberi
             efek memudar (transisi) saat pesan mendekati kolom input -->
        <div class="fixed inset-x-0 bottom-0 z-40 px-4 sm:px-6 pt-10 pb-24 md:pb-6 pointer-events-none">
            <!-- latar solid: menutup seluruh area dari pil input sampai navbar -->
            <div class="absolute inset-0 bg-white dark:bg-navy-950"></div>
            <!-- gradien memudar di atas kolom input -->
            <div class="absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-transparent to-white dark:to-navy-950"></div>

            <div class="relative max-w-3xl mx-auto pointer-events-auto">
                <form @submit.prevent="sendMessage(input)" class="flex items-center gap-1.5 bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-700 rounded-full pl-2 pr-1.5 py-1.5 shadow-lg shadow-slate-900/10 dark:shadow-black/50">

                    <!-- Tombol microfon -->
                    <button type="button" @click="toggleVoice()"
                            :class="recording ? 'bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-white/50 hover:bg-slate-100 dark:hover:bg-navy-800'"
                            class="w-9 h-9 rounded-full flex items-center justify-center transition flex-shrink-0" title="Input suara">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    </button>

                    <!-- Tombol scan struk -->
                    <button type="button" @click="$refs.ocrInput.click()"
                            class="w-9 h-9 rounded-full text-slate-400 dark:text-white/50 hover:bg-slate-100 dark:hover:bg-navy-800 flex items-center justify-center transition flex-shrink-0" title="Scan struk">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                    <input type="file" x-ref="ocrInput" accept="image/*" capture="environment" class="hidden" @change="processOcr($event)">

                    <!-- Text input -->
                    <input type="text" x-model="input" x-ref="chatInput" placeholder="Tanya seputar keuanganmu..." autocomplete="off" enterkeyhint="send"
                           class="flex-1 min-w-0 px-3 py-2 bg-transparent text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 focus:outline-none"
                           :disabled="sending">

                    <!-- Tombol submit (lingkaran) -->
                    <button type="submit" :disabled="sending || !input.trim()"
                            class="w-9 h-9 rounded-full bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
                <p x-show="recording" x-cloak class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-2 text-center flex items-center justify-center gap-1">
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full animate-pulse inline-block"></span>
                    Mendengarkan... bicaralah sekarang
                </p>
                <p x-show="autoSending" x-cloak class="text-[11px] text-blue-600 dark:text-navy-300 font-semibold mt-2 text-center flex items-center justify-center gap-1">
                    <span class="w-3 h-3 border-2 border-blue-600 dark:border-navy-300 border-t-transparent rounded-full animate-spin inline-block"></span>
                    Transkrip diterima — mengirim otomatis...
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================
         LOGIKA HALAMAN AI (Alpine)
         ============================================================ -->
    <script>
        function aiPage() {
            return {
                // Riwayat chat dari session (di-render ulang oleh Alpine)
                messages: @json($messages ?? []),
                input: '',
                sending: false,
                recording: false,
                autoSending: false,
                manualStop: false,
                recognition: null,
                silenceTimer: null,

                init() {
                    this.$nextTick(() => this.scrollBottom());

                    // Sembunyikan bottom nav saat kolom chat difokus (keyboard terbuka di mobile)
                    const input = this.$refs.chatInput;
                    if (input) {
                        input.addEventListener('focus', () => document.body.classList.add('chat-input-focus'));
                        input.addEventListener('blur', () => document.body.classList.remove('chat-input-focus'));
                    }

                    // Fokus input otomatis di desktop (di mobile biarkan —
                    // keyboard tidak muncul dengan sendirinya)
                    if (window.matchMedia('(min-width: 768px)').matches) {
                        input?.focus();
                    }
                },

                scrollBottom() {
                    const el = this.$refs.chatScroll;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                // Format sederhana: **tebal** + baris baru (HTML disanitasi dulu — cegah XSS)
                formatMessage(text) {
                    if (!text) return '';
                    let t = String(text);
                    t = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    t = t.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    t = t.replace(/\n/g, '<br>');
                    return t;
                },

                // Format angka ke Rupiah
                formatRp(n) {
                    const v = parseFloat(n) || 0;
                    return 'Rp ' + v.toLocaleString('id-ID');
                },

                // Jumlah item tercentang di kartu hasil scan struk
                selectedCount(msg) {
                    if (!msg.ocrItems) return 0;
                    return msg.ocrItems.reduce((n, _, i) => n + (msg.selected?.[i] ? 1 : 0), 0);
                },

                // Simpan transaksi dari kartu konfirmasi chat / hasil scan struk
                async saveTransaction(msg) {
                    if (msg.saving || msg.saved) return;

                    let items = null;
                    if (msg.transaction) {
                        items = [msg.transaction];
                    } else if (msg.ocrItems) {
                        items = msg.ocrItems.filter((_, i) => msg.selected?.[i]);
                    }
                    if (!items || !items.length) return;

                    msg.saving = true;
                    try {
                        const res = await fetch('/ai/transactions', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ items }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            msg.saved = true;
                            this.messages.push({ role: 'assistant', text: '✅ ' + data.count + ' transaksi berhasil disimpan ke laporan keuanganmu.' });
                        } else {
                            this.messages.push({ role: 'assistant', text: data.error || 'Gagal menyimpan transaksi.' });
                        }
                    } catch (e) {
                        this.messages.push({ role: 'assistant', text: 'Gagal menyimpan transaksi. Periksa koneksi internet kamu.' });
                    }
                    msg.saving = false;
                    this.$nextTick(() => this.scrollBottom());
                },

                async sendMessage(text) {
                    if (!text || !text.trim() || this.sending) return;
                    const msg = text.trim();

                    this.messages.push({ role: 'user', text: msg });
                    this.input = '';
                    this.sending = true;
                    this.$nextTick(() => this.scrollBottom());

                    try {
                        const res = await fetch('/ai/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ message: msg }),
                        });
                        const data = await res.json();
                        if (data.reply) {
                            this.messages.push({ role: 'assistant', text: data.reply, transaction: data.transaction || null });
                        } else if (data.error) {
                            this.messages.push({ role: 'assistant', text: data.error });
                        }
                    } catch (e) {
                        this.messages.push({ role: 'assistant', text: 'Gagal menghubungi AI. Periksa koneksi internet kamu.' });
                    }

                    this.sending = false;
                    this.$nextTick(() => this.scrollBottom());
                },

                // Input suara (Web Speech API — Chrome/Edge)
                // Optimasi: teks langsung terkirim otomatis setelah selesai bicara
                toggleVoice() {
                    if (this.recording) {
                        // Ketuk lagi = hentikan manual, transkrip tetap di kolom untuk diedit
                        this.manualStop = true;
                        clearTimeout(this.silenceTimer);
                        this.recognition?.stop();
                        this.recording = false;
                        return;
                    }
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        alert('Browser kamu tidak mendukung voice input. Gunakan Chrome atau Edge.');
                        return;
                    }
                    this.manualStop = false;
                    this.recognition = new SpeechRecognition();
                    this.recognition.lang = 'id-ID';
                    // continuous=true: tidak berhenti di tengah kalimat saat ada jeda
                    // sesaat — berhenti otomatis via timer diam di bawah.
                    this.recognition.continuous = true;
                    this.recognition.interimResults = true;
                    const self = this;
                    this.recognition.onresult = function (e) {
                        let transcript = '';
                        for (let i = 0; i < e.results.length; i++) {
                            transcript += e.results[i][0].transcript;
                        }
                        self.input = transcript;
                        // Reset timer diam: 1,2 dtk tanpa suara = selesai bicara
                        clearTimeout(self.silenceTimer);
                        self.silenceTimer = setTimeout(() => {
                            self.recognition?.stop();
                        }, 1200);
                    };
                    this.recognition.onend = function () {
                        self.recording = false;
                        // Kirim otomatis setelah selesai bicara (kecuali user menghentikan manual)
                        const transcript = self.input.trim();
                        if (!self.manualStop && transcript) {
                            // Tampilkan indikator sebentar supaya user melihat
                            // transkripnya terkirim, baru kirim pesannya.
                            self.autoSending = true;
                            setTimeout(() => {
                                self.autoSending = false;
                                self.sendMessage(transcript);
                            }, 350);
                        }
                        self.manualStop = false;
                    };
                    this.recognition.onerror = function () {
                        self.recording = false;
                        clearTimeout(self.silenceTimer);
                    };
                    this.recognition.start();
                    this.recording = true;
                },

                // OCR struk: kirim gambar ke /ai/ocr, tampilkan kartu data di chat
                async processOcr(e) {
                    const file = e.target.files[0];
                    e.target.value = '';
                    if (!file) return;

                    this.sending = true;
                    this.messages.push({ role: 'user', text: 'Scan struk: ' + file.name });
                    this.$nextTick(() => this.scrollBottom());

                    const formData = new FormData();
                    formData.append('image', file);

                    try {
                        const res = await fetch('/ai/ocr-items', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: formData,
                        });
                        const data = await res.json();
                        if (data.items && data.items.length) {
                            this.messages.push({
                                role: 'assistant',
                                text: 'Struk berhasil dibaca (' + data.items.length + ' item' + (data.store ? ' — ' + data.store : '') + '). Centang item yang ingin disimpan:',
                                ocrItems: data.items,
                                selected: data.items.map(() => true),
                            });
                        } else if (data.error) {
                            this.messages.push({ role: 'assistant', text: data.error });
                        }
                    } catch (err) {
                        this.messages.push({ role: 'assistant', text: 'Gagal memproses struk: ' + err.message });
                    }

                    this.sending = false;
                    this.$nextTick(() => this.scrollBottom());
                },
            };
        }
    </script>

    <!-- Export Laporan (PDF/Excel/Print) -->
    @include('components.export-modal')

</body>
</html>
