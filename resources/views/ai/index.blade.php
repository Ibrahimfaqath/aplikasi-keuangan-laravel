<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Assistant - DompetKu</title>
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            var isDark = savedTheme !== 'light';
            if (isDark) document.documentElement.classList.add('dark');
            document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased flex flex-col">

    <x-sidebar title="Asisten AI" />

    <div class="flex-1 max-w-4xl w-full mx-auto px-4 py-6 flex flex-col" x-data="aiFullChat()" x-init="init()">
        <!-- Header -->
        <div class="flex items-center justify-between gap-3 mb-4 pb-4 border-b border-neutral-200 dark:border-[#333333]">
            <div class="flex items-center gap-3">
                <a href="{{ route('transactions.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#171717] border border-neutral-300 dark:border-[#333333] text-neutral-700 dark:text-neutral-200 text-xs font-semibold rounded-xl hover:bg-neutral-100 dark:hover:bg-[#262626] transition flex-shrink-0"
                   aria-label="Kembali ke Transaksi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="hidden sm:inline">Kembali</span>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight">AI Financial Assistant</h1>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Tanya data keuangan atau catat transaksi via chat.</p>
                </div>
            </div>
            @if(count($messages) > 0)
            <form action="{{ route('ai.clear') }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 bg-white dark:bg-transparent text-neutral-900 dark:text-neutral-100 border border-neutral-300 dark:border-[#333333] text-xs font-semibold rounded-xl hover:bg-neutral-100 dark:hover:bg-[#262626] transition">
                    Bersihkan Riwayat
                </button>
            </form>
            @endif
        </div>

        <!-- Chat Box -->
        <div x-ref="chatContainer" class="flex-1 overflow-y-auto space-y-4 mb-4 pr-2" style="max-height: 60vh;">
            @forelse($messages as $msg)
            <div class="{{ $msg['role'] === 'user' ? 'flex justify-end' : 'flex gap-3' }}">
                @if($msg['role'] === 'assistant')
                <div class="w-8 h-8 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center flex-shrink-0 font-bold text-xs">AI</div>
                @endif
                <div class="{{ $msg['role'] === 'user' ? 'bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]' : 'bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-900 dark:text-neutral-100 rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]' }}">
                    <p class="text-sm whitespace-pre-wrap break-words">{!! nl2br(e($msg['text'])) !!}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-neutral-400 text-sm">
                Belum ada percakapan. Ketik pesan di bawah untuk mulai!
            </div>
            @endforelse
        </div>

        <!-- Area Konfirmasi Transaksi -->
        <div x-show="showConfirm"
             x-transition
             class="mb-4 p-4 bg-white dark:bg-[#171717] border border-neutral-900 dark:border-neutral-100 rounded-2xl">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-50 mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Konfirmasi Transaksi
            </p>
            <div class="space-y-1 text-sm text-neutral-600 dark:text-neutral-300">
                <p><span class="font-medium">Judul:</span> <span x-text="pendingTransaction?.title"></span></p>
                <p><span class="font-medium">Jumlah:</span> <span x-text="'Rp ' + formatNumber(pendingTransaction?.amount)"></span></p>
                <p><span class="font-medium">Jenis:</span> <span x-text="pendingTransaction?.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span></p>
                <p><span class="font-medium">Kategori:</span> <span x-text="pendingTransaction?.category"></span></p>
                <p><span class="font-medium">Tanggal:</span> <span x-text="pendingTransaction?.transaction_date"></span></p>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <button @click="confirmTransaction()"
                        type="button"
                        :disabled="confirming"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[2.75rem] bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white text-sm font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900">
                    <svg x-show="!confirming" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="confirming" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="confirming ? 'Menyimpan...' : 'Ya, Simpan'"></span>
                </button>
                <button @click="cancelTransaction()"
                        type="button"
                        :disabled="confirming"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[2.75rem] bg-white dark:bg-transparent hover:bg-neutral-100 dark:hover:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] text-sm font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Batal</span>
                </button>
            </div>
        </div>

        <!-- Input Form -->
        <form @submit.prevent="send()" class="flex gap-2 bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] p-2 rounded-2xl shadow-sm">
            <input type="text" x-model="input" placeholder="Contoh: Beli makan siang 25 ribu..."
                   class="flex-1 px-4 py-2 bg-transparent text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none" :disabled="loading">
            <button type="submit" :disabled="loading || !input.trim()" class="px-5 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white text-sm font-semibold rounded-xl transition disabled:opacity-50">
                <span x-show="!loading">Kirim</span>
                <span x-show="loading" class="inline-block animate-pulse">•••</span>
            </button>
        </form>
    </div>

    <script>
    function aiFullChat() {
        return {
            input: '',
            loading: false,
            confirming: false,
            pendingTransaction: null,
            showConfirm: false,

            init() {
                this.scroll();
            },

            scroll() {
                const c = this.$refs.chatContainer;
                if(c) c.scrollTop = c.scrollHeight;
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
                const container = this.$refs.chatContainer;
                const div = document.createElement('div');

                // Hapus pesan kosong jika ada
                const emptyMsg = container.querySelector('.text-center');
                if (emptyMsg) emptyMsg.remove();

                if (role === 'user') {
                    div.className = 'flex justify-end';
                    div.innerHTML = `
                        <div class="bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]">
                            <p class="text-sm whitespace-pre-wrap break-words">${this.escapeHtml(text)}</p>
                        </div>
                    `;
                } else {
                    div.className = 'flex gap-3';
                    div.innerHTML = `
                        <div class="w-8 h-8 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center flex-shrink-0 font-bold text-xs">AI</div>
                        <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-900 dark:text-neutral-100 rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]">
                            <p class="text-sm whitespace-pre-wrap break-words">${this.escapeHtml(text)}</p>
                        </div>
                    `;
                }

                container.appendChild(div);
                this.scroll();
            },

            async send() {
                if(!this.input.trim() || this.loading) return;

                const txt = this.input;
                this.input = '';
                this.loading = true;

                // Tampilkan pesan user dulu
                this.addMessage('user', txt);

                try {
                    const res = await fetch('{{ route("ai.chat") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message: txt })
                    });

                    const data = await res.json();

                    // Tampilkan balasan AI
                    if (data.reply) {
                        this.addMessage('assistant', data.reply);
                    } else if (data.message) {
                        this.addMessage('assistant', '⚠ ' + data.message);
                    }

                    // Jika ada transaksi, tampilkan konfirmasi
                    if (data.transaction) {
                        this.pendingTransaction = data.transaction;
                        this.showConfirm = true;
                        this.scroll();
                    }

                } catch(e) {
                    this.addMessage('assistant', '⚠ Gagal mengirim pesan. Coba lagi ya!');
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
                    const res = await fetch('{{ route("ai.confirm") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    let data = null;
                    try { data = await res.json(); } catch (e) { data = null; }

                    if (res.ok && data && data.success) {
                        this.addMessage('assistant', '✓ ' + data.message);
                        this.pendingTransaction = null;
                        this.showConfirm = false;
                    } else {
                        const msg = (data && (data.message
                            || (data.errors ? Object.values(data.errors).flat().join(' ') : null)))
                            || 'Gagal menyimpan transaksi. Coba lagi ya!';
                        // Biarkan kartu konfirmasi tampil agar user bisa coba lagi / batal.
                        this.addMessage('assistant', '⚠ ' + msg);
                    }

                } catch(e) {
                    this.addMessage('assistant', '⚠ Gagal menyimpan transaksi. Periksa koneksi lalu coba lagi ya!');
                }
                this.confirming = false;
            },

            async cancelTransaction() {
                try {
                    const res = await fetch('{{ route("ai.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    this.pendingTransaction = null;
                    this.showConfirm = false;
                    this.addMessage('assistant', 'Transaksi dibatalkan.');

                } catch(e) {
                    this.addMessage('assistant', '⚠ Gagal membatalkan.');
                }
            }
        }
    }
    </script>
</body>
</html>
