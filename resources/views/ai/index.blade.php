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
            {{-- Komponen `chat` daisyUI, bukan <div> susun manual: ini bubble
                 sungguhan (sudut tumpul asimetris + ekor bubble kecil di dekat
                 avatar), otomatis handle posisi kiri/kanan lewat chat-start /
                 chat-end. Gaya & radius ikut tema, jadi guard tetap hijau. --}}
            @if($msg['role'] === 'user')
            <div class="chat chat-end">
                <div class="chat-header text-xs text-base-content/60 pb-1 text-right">Kamu</div>
                <div class="chat-bubble bg-base-content text-base-100 text-sm break-words">
                    {!! \App\Support\Markdown::render($msg['text']) !!}
                </div>
            </div>
            @else
            <div class="chat chat-start">
                <div class="chat-header text-xs text-base-content/60 pb-1">Asisten AI</div>
                <div class="chat-image avatar placeholder self-start">
                    <div class="bg-base-content text-base-100 w-8 rounded-field">
                        <span class="text-xs font-bold">AI</span>
                    </div>
                </div>
                {{-- Urutan WAJIB header -> image -> bubble: `.chat` daisyUI itu grid, bukan
                     flex. `chat-image` membentang dua baris di kolom 1, jadi kalau
                     ditulis sebelum `chat-header`, avatar jatuh ke bawah gelembung.
                     `self-start` menarik avatar ke atas agar rata dengan label.
                     --}}
                {{-- Reply asisten masih markdown mentah (`**tebal**`, `- butir`).
                     Tanpa renderer ini user melihat asteriknya. --}}
                <div class="chat-bubble bg-base-100 border border-base-content/20 text-sm break-words">
                    {!! \App\Support\Markdown::render($msg['text']) !!}
                </div>
            </div>
            @endif
            @empty
            <div class="text-center py-12 text-base-content/60 text-sm">
                Belum ada percakapan. Ketik pesan di bawah untuk mulai!
            </div>
            @endforelse
        </div>

{{-- Kartu konfirmasi. Sengaja memakai gaya `chat` yang sama dengan gelembung
     di atas supaya alurnya terbaca sebagai percakapan: AI bertanya ->
     transaksi ditampilkan -> klik "Ya, Simpan" -> baru masuk database.
     Kalau ditutup atau jawab "Batal", transaksi tidak pernah tersimpan
     (pendingTransaction disimpan di session dan dibuang server). --}}
<div x-show="showConfirm"
     x-transition
     class="chat chat-start mb-4">
    <div class="chat-header text-xs text-base-content/60 pb-1">Asisten AI</div>
    <div class="chat-image avatar placeholder self-start">
        <div class="bg-base-content text-base-100 w-8 rounded-field">
            <span class="text-xs font-bold">AI</span>
        </div>
    </div>
    <div class="chat-bubble p-0 bg-base-100 border border-base-content/40 overflow-hidden w-full max-w-[92%]">
        <div class="bg-base-content/5 border-b border-base-content/10 px-4 py-2 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span class="text-sm font-semibold">Transaksi ini mau disimpan?</span>
        </div>

        <dl class="px-4 py-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
            <dt class="text-base-content/60">Judul</dt>
            <dd class="font-medium text-right break-words" x-text="pendingTransaction?.title"></dd>

            <dt class="text-base-content/60">Jumlah</dt>
            <dd class="font-semibold text-right text-primary" x-text="'Rp ' + formatNumber(pendingTransaction?.amount)"></dd>

            <dt class="text-base-content/60">Jenis</dt>
            <dd class="text-right">
                <span class="badge badge-sm"
                      :class="pendingTransaction?.type === 'income' ? 'badge-success' : 'badge-error'"
                      x-text="pendingTransaction?.type === 'income' ? 'Pemasukan' : 'Pengeluaran'"></span>
            </dd>

            <dt class="text-base-content/60">Kategori</dt>
            <dd class="text-right" x-text="pendingTransaction?.category"></dd>

            <dt class="text-base-content/60">Tanggal</dt>
            <dd class="text-right" x-text="pendingTransaction?.transaction_date"></dd>
        </dl>

        <div class="px-4 pb-4 flex flex-wrap gap-2">
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
                    class="btn btn-ghost btn-sm border border-base-content/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Batal, jangan simpan</span>
            </button>
        </div>
    </div>
</div>

        {{-- Composer. Tetap <input type="text">, BUKAN textarea: mengganti ke
             textarea mengubah perilaku tombol Enter (mengirim pesan), dan itu
             di luar tujuan restyle ini. Input pakai `input-ghost` supaya tidak
             ada border ganda di dalam card composer. --}}
        <form @submit.prevent="send()" class="card flex-row items-center gap-2 bg-base-100 border border-base-300 shadow-sm p-2">
            <input type="text" x-model="input" placeholder="Contoh: Beli makan siang 25 ribu..."
                   class="input input-ghost input-sm flex-1 placeholder:text-base-content/60" :disabled="loading">
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
                // Isi bubble lewat renderAiMarkdown (resources/js/ai-markdown.js) —
                // cermin dari app/Support/Markdown.php — supaya `**tebal**` dan
                // `- butir` tampil sebagai bold/list, bukan asterik mentah.
                const html = window.renderAiMarkdown
                    ? window.renderAiMarkdown(text)
                    : this.escapeHtml(text);

                if (role === 'user') {
                    div.className = 'chat chat-end';
                    div.innerHTML = `
                        <div class="chat-header text-xs text-base-content/60 pb-1 text-right">Kamu</div>
                        <div class="chat-bubble bg-base-content text-base-100 text-sm break-words">${html}</div>
                    `;
                } else {
                    div.className = 'chat chat-start';
                    div.innerHTML = `
                        <div class="chat-header text-xs text-base-content/60 pb-1">Asisten AI</div>
                        <div class="chat-image avatar placeholder self-start">
                            <div class="bg-base-content text-base-100 w-8 rounded-field">
                                <span class="text-xs font-bold">AI</span>
                            </div>
                        </div>
                        <div class="chat-bubble bg-base-100 border border-base-content/20 text-sm break-words">${html}</div>
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
