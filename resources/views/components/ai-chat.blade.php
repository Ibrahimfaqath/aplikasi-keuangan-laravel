@php
    $messages = Session::get('ai_messages', []);
@endphp

<div x-data="aiChatWidget()" 
     x-init="initWidget()"
     class="fixed bottom-24 md:bottom-6 right-6 z-50"
     x-cloak>
    
    <!-- Tombol Floating -->
    <button @click="toggleChat()"
            type="button"
            aria-label="Buka AI Assistant"
            class="flex items-center justify-center w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-xl hover:scale-110 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-400/50"
            :class="isOpen ? 'bg-blue-700 scale-110' : ''">
        
        <!-- Icon Chat -->
        <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        
        <!-- Icon Close -->
        <svg x-show="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Chat Panel -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="absolute bottom-20 right-0 w-[calc(100vw-2rem)] sm:w-96 max-h-[70vh] bg-white dark:bg-navy-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-navy-800 overflow-hidden flex flex-col"
         @click.away="closeChat()">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-navy-800 bg-blue-50 dark:bg-navy-800/50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center flex-shrink-0 font-bold text-xs">AI</div>
                <div>
                    <p class="text-xs font-bold text-slate-900 dark:text-white">DompetKu AI</p>
                    <p class="text-[10px] text-slate-500 dark:text-white/50">Asisten Keuangan</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <a href="{{ route('ai.index') }}" 
                   class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white/80 rounded-lg transition"
                   title="Buka Chat Penuh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/>
                    </svg>
                </a>
                <button @click="closeChat()" 
                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white/80 rounded-lg transition"
                        aria-label="Tutup chat">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages -->
        <div x-ref="chatContainer" 
             class="flex-1 overflow-y-auto px-4 py-3 space-y-3 min-h-[200px] max-h-[50vh]">
            <template x-for="msg in messages" :key="messages.indexOf(msg)">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex gap-3'">
                    <template x-if="msg.role === 'assistant'">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center flex-shrink-0 font-bold text-[10px]">AI</div>
                    </template>
                    <div :class="msg.role === 'user' ? 'bg-blue-600 text-white rounded-2xl rounded-tr-md px-3 py-2 max-w-[85%]' : 'bg-slate-100 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-2xl rounded-tl-md px-3 py-2 max-w-[85%]'">
                        <p class="text-xs whitespace-pre-wrap break-words" x-text="msg.text"></p>
                    </div>
                </div>
            </template>
            <div x-show="messages.length === 0" class="text-center py-8 text-slate-400 text-xs">
                <div class="w-10 h-10 mx-auto mb-2 bg-blue-50 dark:bg-navy-800 text-blue-500 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <p class="font-medium text-slate-600 dark:text-white/70">Tanya apa saja tentang keuanganmu</p>
                <p class="text-[11px] text-slate-400 dark:text-white/50 mt-1">Contoh: "Berapa saldo saya?" atau "Catat makan 25 ribu"</p>
            </div>
            <div x-show="loading" class="flex justify-start">
                <div class="bg-slate-100 dark:bg-navy-800 border border-slate-200 dark:border-navy-700 rounded-2xl rounded-tl-md px-4 py-2">
                    <span class="inline-flex gap-1">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Area Konfirmasi -->
        <div x-show="showConfirm" 
             x-transition
             class="px-4 py-3 border-t border-blue-200 dark:border-navy-700 bg-blue-50 dark:bg-navy-800/60">
            <p class="text-xs font-semibold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600 dark:text-navy-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Konfirmasi Transaksi
            </p>
            <div class="space-y-0.5 text-xs text-slate-600 dark:text-white/80">
                <p><span class="font-medium">Judul:</span> <span x-text="pendingTransaction?.title"></span></p>
                <p><span class="font-medium">Jumlah:</span> <span x-text="'Rp ' + formatNumber(pendingTransaction?.amount)"></span></p>
                <p><span class="font-medium">Jenis:</span> <span x-text="pendingTransaction?.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span></p>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                <button @click="confirmTransaction()"
                        type="button"
                        :disabled="confirming"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 min-h-[2.5rem] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/60">
                    <svg x-show="!confirming" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="confirming" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="confirming ? 'Menyimpan...' : 'Ya, Simpan'"></span>
                </button>
                <button @click="cancelTransaction()"
                        type="button"
                        :disabled="confirming"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 min-h-[2.5rem] bg-slate-100 hover:bg-slate-200 active:bg-slate-300 dark:bg-navy-800 dark:hover:bg-navy-700 text-slate-700 dark:text-white/85 border border-slate-200 dark:border-navy-700 text-xs font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Batal</span>
                </button>
            </div>
        </div>

        <!-- Input -->
        <form @submit.prevent="sendMessage()" class="flex gap-2 p-3 border-t border-slate-200 dark:border-navy-800 bg-slate-50 dark:bg-navy-800/50">
            <input type="text" 
                   x-model="input" 
                   placeholder="Tanya atau catat transaksi..."
                   class="flex-1 px-3 py-2 bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-navy-400"
                   :disabled="loading">
            <button type="submit" 
                    :disabled="loading || !input.trim()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition disabled:opacity-50">
                <span x-show="!loading">Kirim</span>
                <span x-show="loading" class="inline-block animate-pulse">⏳</span>
            </button>
        </form>
    </div>
</div>

<script>
function aiChatWidget() {
    return {
        isOpen: false,
        input: '',
        loading: false,
        confirming: false,
        messages: @json($messages),
        pendingTransaction: null,
        showConfirm: false,

        initWidget() {
            // Load messages dari session
            this.messages = @json($messages);
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },

        closeChat() {
            this.isOpen = false;
        },

        scrollToBottom() {
            const container = this.$refs.chatContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        formatNumber(value) {
            if (!value) return '0';
            return new Intl.NumberFormat('id-ID').format(value);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        addMessage(role, text) {
            this.messages.push({ role, text });
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        async sendMessage() {
            if (!this.input.trim() || this.loading) return;

            const text = this.input;
            this.input = '';
            this.loading = true;

            this.addMessage('user', text);

            try {
                const response = await fetch('{{ route("ai.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: text }),
                });

                const data = await response.json();

                if (data.reply) {
                    this.addMessage('assistant', data.reply);
                } else if (data.message) {
                    this.addMessage('assistant', '❌ ' + data.message);
                }

                if (data.transaction) {
                    this.pendingTransaction = data.transaction;
                    this.showConfirm = true;
                    this.scrollToBottom();
                }

            } catch (error) {
                this.addMessage('assistant', '❌ Gagal mengirim pesan. Coba lagi ya!');
            }

            this.loading = false;
        },

        async confirmTransaction() {
            if (!this.pendingTransaction || this.confirming) return;
            this.confirming = true;

            try {
                const payload = {
                    ...this.pendingTransaction,
                    transaction_date: this.pendingTransaction.transaction_date ?? this.pendingTransaction.date,
                };
                const response = await fetch('{{ route("ai.confirm") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                let data = null;
                try { data = await response.json(); } catch (e) { data = null; }

                if (response.ok && data && data.success) {
                    this.addMessage('assistant', '✅ ' + data.message);
                    this.pendingTransaction = null;
                    this.showConfirm = false;
                } else {
                    const msg = (data && (data.message
                        || (data.errors ? Object.values(data.errors).flat().join(' ') : null)))
                        || 'Gagal menyimpan transaksi. Coba lagi ya!';
                    // Keep the candidate visible so the user can retry or cancel.
                    this.addMessage('assistant', '❌ ' + msg);
                }

            } catch (error) {
                this.addMessage('assistant', '❌ Gagal menyimpan transaksi. Periksa koneksi lalu coba lagi ya!');
            }

            this.confirming = false;
        },

        async cancelTransaction() {
            try {
                await fetch('{{ route("ai.cancel") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                this.pendingTransaction = null;
                this.showConfirm = false;
                this.addMessage('assistant', '❌ Transaksi dibatalkan.');

            } catch (error) {
                this.addMessage('assistant', '❌ Gagal membatalkan.');
            }
        }
    };
}
</script>