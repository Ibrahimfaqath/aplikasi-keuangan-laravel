<!-- AI Chat Widget -->
<div x-data="aiChat()" x-cloak class="fixed bottom-6 right-6 z-50">

    <!-- Floating Button -->
    <button @click="toggleChat()" 
            class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 dark:bg-gold-400 dark:hover:bg-gold-300 dark:text-black text-white shadow-lg shadow-blue-500/30 dark:shadow-black/40 flex items-center justify-center transition-all duration-200"
            :class="open ? 'rotate-0' : 'hover:scale-105'">
        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Chat Panel -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="absolute bottom-20 right-0 w-[360px] sm:w-[400px] bg-white dark:bg-neutral-900 rounded-2xl border border-slate-200 dark:border-neutral-800 shadow-2xl shadow-slate-900/10 dark:shadow-black/30 overflow-hidden flex flex-col"
         style="max-height: 520px;">

        <!-- Header -->
        <div class="px-5 py-4 bg-blue-600 dark:bg-neutral-900 dark:border-b dark:border-neutral-800 text-white flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white/20 dark:bg-gold-400/20 flex items-center justify-center">
                <svg class="w-5 h-5 dark:text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold dark:text-white">AI Assistant</h3>
                <p class="text-[11px] text-white/70 dark:text-neutral-400">Asisten keuangan pintar</p>
            </div>
            <!-- Link ke halaman AI penuh -->
            <a href="{{ route('ai.index') }}"
               class="ml-auto text-[10px] font-semibold px-2.5 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 dark:bg-gold-400/10 dark:hover:bg-gold-400/20 dark:text-gold-400 text-white transition whitespace-nowrap"
               title="Buka halaman AI penuh">
                Buka penuh
            </a>
        </div>

        <!-- Messages -->
        <div x-ref="messages" class="flex-1 overflow-y-auto p-4 space-y-4" style="min-height: 280px; max-height: 340px;">
            <!-- Welcome -->
            <div class="flex gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-gold-400/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div class="bg-slate-100 dark:bg-neutral-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[85%]">
                    <p class="text-sm text-slate-700 dark:text-neutral-300">Halo! Saya bisa bantu analisis keuangan, saran budget, atau jawab pertanyaan seputar transaksi kamu.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-2">
                <button @click="sendMessage('Bagaimana ringkasan keuangan saya bulan ini?')" class="px-3 py-1.5 bg-blue-50 dark:bg-gold-400/10 text-blue-600 dark:text-gold-400 rounded-full text-xs font-semibold border border-blue-200/60 dark:border-gold-400/25 hover:bg-blue-100 dark:hover:bg-gold-400/20 transition">
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
                    <div x-show="msg.role === 'assistant'" class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-gold-400/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <div :class="msg.role === 'user' 
                        ? 'bg-blue-600 dark:bg-gold-400 dark:text-black text-white rounded-2xl rounded-tr-md px-4 py-3 max-w-[85%]' 
                        : 'bg-slate-100 dark:bg-neutral-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[85%]'">
                        <p class="text-sm whitespace-pre-wrap" :class="msg.role === 'user' ? 'text-white' : 'text-slate-700 dark:text-neutral-300'" x-html="formatMessage(msg.text)"></p>
                    </div>
                </div>
            </template>

            <!-- Loading -->
            <div x-show="loading" class="flex gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-gold-400/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div class="bg-slate-100 dark:bg-neutral-800 rounded-2xl rounded-tl-md px-4 py-3">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="px-4 py-3 border-t border-slate-100 dark:border-neutral-800 bg-white dark:bg-neutral-900">
            <form @submit.prevent="sendMessage(input)" class="flex items-center gap-2">
                <input type="text" x-model="input" placeholder="Tanya seputar keuangan..." 
                       class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-neutral-800/60 border border-slate-200 dark:border-neutral-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       :disabled="loading">
                <button type="submit" :disabled="loading || !input.trim()"
                        class="p-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-gold-400 dark:hover:bg-gold-300 dark:text-black disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl transition">
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
            // Bold
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Line breaks
            text = text.replace(/\n/g, '<br>');
            return text;
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
                    this.messages.push({ role: 'assistant', text: data.reply });
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
