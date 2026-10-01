<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Assistant - dompetku</title>
    @include('partials.theme-boot')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content flex flex-col">

    <x-sidebar title="Asisten AI" :back="route('transactions.index')" minimal />

    <div class="flex-1 max-w-4xl w-full mx-auto px-4 py-6 flex flex-col" x-data="aiFullChat()" x-init="init()">
        <!-- Header -->
        <div class="flex items-center justify-end gap-3 mb-4 pb-4 border-b border-base-300">
            @if(count($messages) > 0)
            <form action="{{ route('ai.clear') }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm">
                    Bersihkan Riwayat
                </button>
            </form>
            @endif
        </div>

        <!-- Chat Box -->
        {{-- Gelembung pesan sengaja TIDAK memakai radius tema (rounded-box
             0.25rem) supaya siluet percakapan tetap membulat seperti aslinya;
            Warnanya cukup lewat token surface. --}}
        <div x-ref="chatContainer" class="flex-1 overflow-y-auto space-y-4 mb-4 pr-2" style="max-height: 60vh;">
            @forelse($messages as $msg)
            <div class="{{ $msg['role'] === 'user' ? 'flex justify-end' : 'flex gap-3' }}">
                @if($msg['role'] === 'assistant')
                <div class="w-8 h-8 rounded-xl bg-base-content text-base-100 flex items-center justify-center flex-shrink-0 font-bold text-xs">AI</div>
                @endif
                <div class="{{ $msg['role'] === 'user' ? 'bg-base-content text-base-100 rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]' : 'bg-base-100 border border-base-300 text-base-content rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]' }}">
                    <p class="text-sm whitespace-pre-wrap break-words">{!! nl2br(e($msg['text'])) !!}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-base-content/40 text-sm">
                Belum ada percakapan. Ketik pesan di bawah untuk mulai!
            </div>
            @endforelse
        </div>

        <!-- Area Konfirmasi Transaksi -->
        <div x-show="showConfirm"
             x-transition
             class="card bg-base-100 border border-base-content/40 shadow-sm p-4 mb-4">
            <p class="card-title text-sm font-semibold mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Konfirmasi Transaksi
            </p>
            <div class="space-y-1 text-sm text-base-content/70">
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
                        class="btn btn-primary btn-sm">
                    <svg x-show="!confirming" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{-- `loading loading-spinner` menggantikan SVG spinner lama; binding
                         x-show-nya tetap sama. --}}
                    <span x-show="confirming" class="loading loading-spinner w-4 h-4"></span>
                    <span x-text="confirming ? 'Menyimpan...' : 'Ya, Simpan'"></span>
                </button>
                <button @click="cancelTransaction()"
                        type="button"
                        :disabled="confirming"
                        class="btn btn-outline btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Batal</span>
                </button>
            </div>
        </div>

        {{-- Composer. Tetap <input type="text">, BUKAN textarea: mengganti ke
             textarea mengubah perilaku tombol Enter (mengirim pesan), dan itu
             di luar tujuan restyle ini. Input pakai `input-ghost` supaya tidak
             ada border ganda di dalam card composer. --}}
        <form @submit.prevent="send()" class="card flex-row items-center gap-2 bg-base-100 border border-base-300 shadow-sm p-2">
            <input type="text" x-model="input" placeholder="Contoh: Beli makan siang 25 ribu..."
                   class="input input-ghost input-sm flex-1 placeholder:text-base-content/40" :disabled="loading">
            <button type="submit" :disabled="loading || !input.trim()" class="btn btn-primary btn-sm">
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
            pendingTransaction: @json($pendingTransaction ?? null),
            showConfirm: @json(!empty($pendingTransaction)),

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

                // Kelas di string HTML di bawah harus PERSIS sama dengan gelembung
                // di Blade (dan tetap literal, bukan dirangkai), supaya pesan yang
                // dirender server dan yang dikirim dari sini tidak berbeda tampilan.
                if (role === 'user') {
                    div.className = 'flex justify-end';
                    div.innerHTML = `
                        <div class="bg-base-content text-base-100 rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%]">
                            <p class="text-sm whitespace-pre-wrap break-words">${this.escapeHtml(text)}</p>
                        </div>
                    `;
                } else {
                    div.className = 'flex gap-3';
                    div.innerHTML = `
                        <div class="w-8 h-8 rounded-xl bg-base-content text-base-100 flex items-center justify-center flex-shrink-0 font-bold text-xs">AI</div>
                        <div class="bg-base-100 border border-base-300 text-base-content rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%]">
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

                    // Kena batas kecepatan (429) — backend sudah kasih pesan Indonesia yang ramah.
                    if (res.status === 429) {
                        this.addMessage('assistant', data.reply || data.message || 'Sabar ya, terlalu cepat! Tunggu sebentar baru coba lagi ⏳');
                        return;
                    }

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
