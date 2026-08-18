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
        <div id="chatScroll" x-ref="chatScroll" class="flex-1 min-h-0 overflow-y-auto pb-40 md:pb-24">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
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
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
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
                            <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>

                        <div :class="msg.role === 'user'
                                    ? 'bg-blue-600 dark:bg-navy-400 dark:text-white text-white rounded-2xl rounded-tr-md px-4 py-2.5 max-w-[85%]'
                                    : 'bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-2.5 max-w-[85%] shadow-sm'">

                            <!-- Pesan teks biasa -->
                            <div x-show="!msg.ocr">
                                <p class="text-sm whitespace-pre-wrap" :class="msg.role === 'user' ? 'text-white dark:text-white' : 'text-slate-700 dark:text-white/80'" x-html="formatMessage(msg.text)"></p>
                            </div>

                            <!-- Hasil OCR struk (kartu data) -->
                            <div x-show="msg.ocr">
                                <p class="text-sm text-slate-700 dark:text-white/80 mb-2.5" x-text="msg.text"></p>
                                <div class="rounded-xl bg-slate-50 dark:bg-navy-800/60 border border-slate-200/70 dark:border-navy-700/60 p-3 space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-white/70">Keterangan</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-white text-right" x-text="msg.ocr.title || '-'"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-white/70">Nominal</span>
                                        <span class="text-xs font-extrabold text-slate-900 dark:text-white" x-text="formatRp(msg.ocr.amount)"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-white/70">Jenis</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                              :class="msg.ocr.type === 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'"
                                              x-text="msg.ocr.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-white/70">Kategori</span>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-white/80" x-text="msg.ocr.category || '-'"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-white/70">Tanggal</span>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-white/80" x-text="msg.ocr.date || '-'"></span>
                                    </div>
                                </div>
                                <!-- Tombol: isi form tambah transaksi (form sudah ter-prefill via session) -->
                                <a href="{{ route('transactions.create') }}"
                                   class="mt-2.5 inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-600/20 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    Tambah transaksi ini
                                </a>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TYPING INDICATOR -->
                <div x-show="sending" class="flex gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-navy-400/10 border border-blue-100 dark:border-navy-400/25 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div class="bg-white dark:bg-navy-900 border border-slate-200/80 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-3 flex items-center gap-1.5">
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                        <span class="typing-dot w-1.5 h-1.5 bg-slate-400 dark:bg-navy-400 rounded-full inline-block"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- INPUT MENGAMBANG (fixed: di atas bottom nav mobile, di bawah di desktop) -->
        <div class="fixed bottom-24 md:bottom-6 inset-x-0 z-40 px-4 sm:px-6 pb-2 pt-1 pointer-events-none">
            <div class="max-w-3xl mx-auto pointer-events-auto">
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
                recognition: null,

                init() {
                    this.$nextTick(() => this.scrollBottom());
                    // Fokus input otomatis di desktop (di mobile biarkan —
                    // keyboard tidak muncul dengan sendirinya)
                    if (window.matchMedia('(min-width: 768px)').matches) {
                        this.$refs.chatInput?.focus();
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
                            this.messages.push({ role: 'assistant', text: data.reply });
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
                toggleVoice() {
                    if (this.recording) {
                        this.recognition?.stop();
                        this.recording = false;
                        return;
                    }
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        alert('Browser kamu tidak mendukung voice input. Gunakan Chrome atau Edge.');
                        return;
                    }
                    this.recognition = new SpeechRecognition();
                    this.recognition.lang = 'id-ID';
                    this.recognition.interimResults = true;
                    const self = this;
                    this.recognition.onresult = function (e) {
                        let transcript = '';
                        for (let i = e.resultIndex; i < e.results.length; i++) {
                            transcript += e.results[i][0].transcript;
                        }
                        self.input = transcript;
                    };
                    this.recognition.onend = function () { self.recording = false; };
                    this.recognition.onerror = function () { self.recording = false; };
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
                        const res = await fetch('/ai/ocr', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: formData,
                        });
                        const data = await res.json();
                        if (data.data) {
                            this.messages.push({
                                role: 'assistant',
                                text: 'Struk berhasil dibaca. Berikut datanya — klik tombol di bawah untuk mengisi form transaksi:',
                                ocr: data.data,
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
