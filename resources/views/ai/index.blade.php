<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Assistant - DompetKu</title>
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            if (savedTheme !== 'light') document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased flex flex-col">

    <x-navbar />

    <div class="flex-1 max-w-4xl w-full mx-auto px-4 py-6 flex flex-col" x-data="aiFullChat()" x-init="init()">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-200 dark:border-navy-800">
            <div>
                <h1 class="text-xl font-bold">AI Financial Assistant</h1>
                <p class="text-xs text-slate-500 dark:text-white/60">Tanya data keuangan atau catat transaksi via chat.</p>
            </div>
            @if(count($messages) > 0)
            <form action="{{ route('ai.clear') }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-semibold rounded-xl hover:bg-rose-100 transition">
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
                <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center flex-shrink-0 font-bold">AI</div>
                @endif
                <div class="{{ $msg['role'] === 'user' ? 'bg-blue-600 text-white rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]' : 'bg-slate-100 dark:bg-navy-900 border border-slate-200 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]' }}">
                    <p class="text-sm whitespace-pre-wrap break-words">{!! nl2br(e($msg['text'])) !!}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-slate-400 text-sm">
                Belum ada percakapan. Ketik pesan di bawah untuk mulai!
            </div>
            @endforelse
        </div>

        <!-- Area Konfirmasi Transaksi -->
        <div x-show="showConfirm" 
             x-transition
             class="mb-4 p-4 bg-blue-50 dark:bg-navy-800/60 border border-blue-200 dark:border-navy-700 rounded-2xl">
            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-2">📋 Konfirmasi Transaksi</p>
            <div class="space-y-1 text-sm text-slate-600 dark:text-white/80">
                <p><span class="font-medium">Judul:</span> <span x-text="pendingTransaction?.title"></span></p>
                <p><span class="font-medium">Jumlah:</span> <span x-text="'Rp ' + formatNumber(pendingTransaction?.amount)"></span></p>
                <p><span class="font-medium">Jenis:</span> <span x-text="pendingTransaction?.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span></p>
                <p><span class="font-medium">Kategori:</span> <span x-text="pendingTransaction?.category"></span></p>
                <p><span class="font-medium">Tanggal:</span> <span x-text="pendingTransaction?.transaction_date"></span></p>
            </div>
            <div class="flex gap-2 mt-3">
                <button @click="confirmTransaction()" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition">
                    ✅ Simpan
                </button>
                <button @click="cancelTransaction()" 
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl transition">
                    ❌ Batal
                </button>
            </div>
        </div>

        <!-- Input Form -->
        <form @submit.prevent="send()" class="flex gap-2 bg-slate-50 dark:bg-navy-900 border border-slate-200 dark:border-navy-800 p-2 rounded-2xl shadow-sm">
            <input type="text" x-model="input" placeholder="Contoh: Beli makan siang 25 ribu..." 
                   class="flex-1 px-4 py-2 bg-transparent text-sm focus:outline-none" :disabled="loading">
            <button type="submit" :disabled="loading || !input.trim()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition disabled:opacity-50">
                <span x-show="!loading">Kirim</span>
                <span x-show="loading" class="inline-block animate-pulse">⏳</span>
            </button>
        </form>
    </div>

    <script>
    function aiFullChat() {
        return {
            input: '', 
            loading: false,
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
                        <div class="bg-blue-600 text-white rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]">
                            <p class="text-sm whitespace-pre-wrap break-words">${this.escapeHtml(text)}</p>
                        </div>
                    `;
                } else {
                    div.className = 'flex gap-3';
                    div.innerHTML = `
                        <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-navy-400/10 text-blue-600 dark:text-navy-300 flex items-center justify-center flex-shrink-0 font-bold">AI</div>
                        <div class="bg-slate-100 dark:bg-navy-900 border border-slate-200 dark:border-navy-800 rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]">
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
                    }
                    
                    // Jika ada transaksi, tampilkan konfirmasi
                    if (data.transaction) {
                        this.pendingTransaction = data.transaction;
                        this.showConfirm = true;
                        this.scroll();
                    }
                    
                } catch(e) { 
                    this.addMessage('assistant', '❌ Gagal mengirim pesan. Coba lagi ya!');
                }
                this.loading = false;
            },
            
            async confirmTransaction() {
                if (!this.pendingTransaction) return;
                
                try {
                    const res = await fetch('{{ route("ai.confirm") }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                            'Accept': 'application/json' 
                        },
                        body: JSON.stringify(this.pendingTransaction)
                    });
                    
                    const data = await res.json();
                    
                    if (data.success) {
                        this.addMessage('assistant', '✅ ' + data.message);
                        this.pendingTransaction = null;
                        this.showConfirm = false;
                    }
                    
                } catch(e) { 
                    this.addMessage('assistant', '❌ Gagal menyimpan transaksi.'); 
                }
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
                    this.addMessage('assistant', '❌ Transaksi dibatalkan.');
                    
                } catch(e) { 
                    this.addMessage('assistant', '❌ Gagal membatalkan.'); 
                }
            }
        }
    }
    </script>
</body>
</html>