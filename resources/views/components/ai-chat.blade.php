<!-- AI Chat Widget -->
@props(['class' => ''])
<div x-data="aiChat()" x-cloak class="fixed bottom-20 md:bottom-6 right-6 z-50 {{ $class }}">

    <!-- Floating Button -->
    <button @click="toggleChat()" 
            class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white shadow-lg shadow-blue-500/30 dark:shadow-black/40 flex items-center justify-center transition-all duration-200"
            :class="open ? 'rotate-0' : 'hover:scale-105'">
        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
        </svg>
        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Chat Panel -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="absolute bottom-20 right-0 w-[360px] sm:w-[400px] bg-white dark:bg-navy-900 rounded-2xl border border-slate-200 dark:border-navy-800 shadow-2xl shadow-slate-900/10 dark:shadow-black/30 overflow-hidden flex flex-col"
         style="max-height: 520px;">

        <!-- Header -->
        <div class="px-5 py-4 bg-blue-600 dark:bg-navy-900 dark:border-b dark:border-navy-800 text-white flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white/20 dark:bg-navy-400/20 flex items-center justify-center">
                <svg class="w-5 h-5 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold dark:text-white">AI Assistant</h3>
                <p class="text-[11px] text-white/70 dark:text-white/70">Asisten keuangan pintar</p>
            </div>
            <!-- Link ke halaman AI penuh -->
            <a href="{{ route('ai.index') }}"
               class="ml-auto text-[10px] font-semibold px-2.5 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 dark:bg-navy-400/10 dark:hover:bg-navy-400/20 dark:text-navy-300 text-white transition whitespace-nowrap"
               title="Buka halaman AI penuh">
                Buka penuh
            </a>
        </div>

        <!-- Messages -->
        <div x-ref="messages" class="flex-1 overflow-y-auto p-4 space-y-4" style="min-height: 280px; max-height: 340px;">
            <!-- Welcome -->
            <div class="flex gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-navy-400/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                </div>
                <div class="bg-slate-100 dark:bg-navy-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[85%]">
                    <p class="text-sm text-slate-700 dark:text-white/80">Halo! Saya bisa bantu analisis keuangan, saran budget, atau jawab pertanyaan seputar transaksi kamu.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-2">
                <button @click="sendMessage('Bagaimana ringkasan keuangan saya bulan ini?')" class="px-3 py-1.5 bg-blue-50 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 rounded-full text-xs font-semibold border border-blue-200/60 dark:border-navy-400/25 hover:bg-blue-100 dark:hover:bg-navy-400/20 transition">
                    Ringkasan bulan ini
                </button>
                <button @click="sendMessage('Tips menghemat pengeluaran saya?')" class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-semibold border border-emerald-200/60 dark:border-emerald-800/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition">
                    Tips hemat
                </button>
                <button @click="sendMessage('Kategori mana yang paling banyak pengeluaran saya?')" class="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-full text-xs font-semibold border border-amber-200/60 dark:border-amber-800/50 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition">
                    Analisis kategori
                </button>
            </div>

            <!-- Dynamic Messages -->
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex gap-3'">
                    <div x-show="msg.role === 'assistant'" class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-navy-400/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                    </div>
                    <div :class="msg.role === 'user' 
                        ? 'bg-blue-600 dark:bg-navy-400 dark:text-white text-white rounded-2xl rounded-tr-md px-4 py-3 max-w-[85%]' 
                        : 'bg-slate-100 dark:bg-navy-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[85%]'">
                        <p class="text-sm whitespace-pre-wrap break-words" :class="msg.role === 'user' ? 'text-white' : 'text-slate-700 dark:text-white/80'" x-html="formatMessage(msg.text)"></p>

                        <!-- Kartu konfirmasi transaksi (niat transaksi dari chat) -->
                        <div x-show="msg.transaction" class="mt-2.5 rounded-xl border border-slate-200 dark:border-navy-700 overflow-hidden bg-white dark:bg-navy-950/40">
                            <div class="px-3 py-2.5 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="msg.transaction?.title"></span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase flex-shrink-0"
                                          :class="msg.transaction?.type === 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'"
                                          x-text="msg.transaction?.type === 'income' ? 'Masuk' : 'Keluar'"></span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] text-slate-500 dark:text-white/60" x-text="msg.transaction?.category"></span>
                                    <span class="text-xs font-extrabold text-slate-900 dark:text-white" x-text="formatRp(msg.transaction?.amount)"></span>
                                </div>
                            </div>
                            <button type="button" @click="saveTransaction(msg)" :disabled="msg.saved || msg.saving"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold border-t border-slate-100 dark:border-navy-800 transition"
                                    :class="msg.saved ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white disabled:opacity-40 disabled:cursor-not-allowed'">
                                <svg x-show="msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="!msg.saved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span x-text="msg.saved ? 'Tersimpan ✓' : (msg.saving ? 'Menyimpan...' : 'Simpan Transaksi')"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Loading -->
            <div x-show="loading" class="flex gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-navy-400/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                </div>
                <div class="bg-slate-100 dark:bg-navy-800 rounded-2xl rounded-tl-md px-4 py-3">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="px-4 py-3 border-t border-slate-100 dark:border-navy-800 bg-white dark:bg-navy-900">
            <form @submit.prevent="sendMessage(input)" class="flex items-center gap-2">
                <input type="text" x-model="input" placeholder="Tanya seputar keuangan..." 
                       class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       :disabled="loading">
                <button type="submit" :disabled="loading || !input.trim()"
                        class="p-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function aiChat() {
    return {
        open: false,
        input: '',
        loading: false,
        messages: [],

        toggleChat() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.scrollBottom());
            }
        },

        scrollBottom() {
            if (this.$refs.messages) {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            }
        },

        formatMessage(text) {
            // Sanitasi HTML dulu (cegah XSS dari balasan AI), lalu format **tebal** & baris baru
            let t = String(text);
            t = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            t = t.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            t = t.replace(/\n/g, '<br>');
            return t;
        },

        formatRp(n) {
            const v = parseFloat(n) || 0;
            return 'Rp ' + v.toLocaleString('id-ID');
        },

        async saveTransaction(msg) {
            if (msg.saving || msg.saved) return;
            if (!msg.transaction) return;
            msg.saving = true;
            try {
                const res = await fetch('/ai/transactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ items: [msg.transaction] }),
                });
                const data = await res.json();
                if (data.success) {
                    msg.saved = true;
                    this.messages.push({ role: 'assistant', text: '✅ Transaksi berhasil disimpan ke laporan keuanganmu.' });
                } else {
                    this.messages.push({ role: 'assistant', text: data.error || 'Gagal menyimpan transaksi.' });
                }
            } catch (e) {
                this.messages.push({ role: 'assistant', text: 'Gagal menyimpan transaksi. Periksa koneksi internet kamu.' });
            }
            msg.saving = false;
            this.scrollBottom();
        },

        async sendMessage(text) {
            if (!text || !text.trim() || this.loading) return;

            this.messages.push({ role: 'user', text: text.trim() });
            this.input = '';
            this.loading = true;
            this.scrollBottom();

            try {
                const res = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: text.trim() }),
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

            this.loading = false;
            this.scrollBottom();
        }
    };
}
</script>
