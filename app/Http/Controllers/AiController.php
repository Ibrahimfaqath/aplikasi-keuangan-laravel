<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Transaction;
use App\Services\TransactionParser;

class AiController extends Controller
{
    /** Daftar kategori valid — dipakai di prompt AI & normalisasi. */
    public const CATEGORIES_HINT = 'Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga';

    /**
     * Halaman AI Assistant (chat penuh) — riwayat chat disimpan di session
     * agar percakapan tetap ada saat halaman di-refresh.
     */
    public function page()
    {
        $messages = session('ai_messages', []);

        return view('ai.index', [
            'messages' => $messages,
        ]);
    }

    /**
     * Hapus riwayat chat AI (dipanggil dari tombol "Bersihkan" di halaman AI).
     */
    public function clear(Request $request)
    {
        $request->session()->forget('ai_messages');

        return redirect()->route('ai.index')->with('success', 'Riwayat chat AI dibersihkan.');
    }

    /**
     * Kirim pesan ke AI assistant (Google Gemini)
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'API key belum dikonfigurasi.'], 500);
        }

        // Ambil data transaksi user untuk konteks
        $user = Auth::user();
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get(['title', 'amount', 'type', 'category', 'transaction_date']);

        $totalIncome = Transaction::where('user_id', $user->id)->where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('user_id', $user->id)->where('type', 'expense')->sum('amount');

        $systemPrompt = "Kamu adalah asisten keuangan pribadi bernama DompetKu AI. Kamu membantu pengguna mengelola keuangan mereka. 
Jawab dalam Bahasa Indonesia yang ramah dan singkat. 
Gunakan data transaksi pengguna untuk memberikan analisis dan saran yang relevan.

Data pengguna:
- Total Pemasukan: Rp " . number_format($totalIncome, 0, ',', '.') . "
- Total Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "
- Saldo: Rp " . number_format($totalIncome - $totalExpense, 0, ',', '.') . "

20 transaksi terakhir:
" . $recentTransactions->map(fn($t) => "- {$t->transaction_date}: {$t->title} ({$t->type}) Rp " . number_format($t->amount, 0, ',', '.') . " [{$t->category}]")->implode("\n") . "

PENTING — DETEKSI TRANSAKSI:
Jika pesan pengguna berisi NIAT mencatat transaksi (menyebut nominal uang, misal: \"beli nasi goreng 25 ribu\", \"gaji masuk 5 juta\", \"bayar listrik 200rb\", \"jajan 15k\", \"terima transfer 500 ribu\", \"bonus masuk bulan ini 1jt\"), maka:
1. Tulis balasan singkat ramah (1-2 kalimat), lalu di baris berikutnya sertakan blok JSON PERSIS dengan format:
<<<JSON
{\"intent\":\"transaction\",\"title\":\"Nasi Goreng\",\"amount\":25000,\"type\":\"expense\",\"category\":\"Makanan & Minuman\",\"date\":\"" . now()->format('Y-m-d') . "\"}
JSON>>>
2. amount adalah ANGKA tanpa simbol/format (misal 25000, bukan Rp25.000). type \"income\" untuk pemasukan, \"expense\" untuk pengeluaran. category salah satu dari daftar kategori valid. date format YYYY-MM-DD (tebak dari konteks; jika tidak jelas gunakan hari ini).
Jika pesan BUKAN transaksi, jawab seperti biasa TANPA blok JSON.
Kategori valid: " . self::CATEGORIES_HINT;

        $reply = null;
        $transaction = null;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(90)->connectTimeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $request->message]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ]);

            if (!$response->failed()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                // Deteksi intent transaksi dari balasan (blok <<<JSON ... JSON>>>)
                [$reply, $transaction] = $this->extractTransaction($reply ?? '');
            }
        } catch (\Throwable $e) {
            // Gemini error/offline — lanjut ke parser lokal di bawah.
            $reply = null;
        }

        // Parser lokal (fallback): selalu coba, baik Gemini tidak membalas,
        // tidak mengembalikan JSON transaksi, maupun API-nya sedang bermasalah.
        // Supaya input transaksi lewat chat TETAP berfungsi cepat & tepat.
        if ($transaction === null) {
            $transaction = TransactionParser::fromText($request->message);
        }

        if ($reply === null) {
            $reply = $transaction !== null
                ? 'Siap! Aku catat transaksinya ya. Periksa datanya dulu sebelum disimpan.'
                : 'Maaf, AI sedang tidak tersedia. Coba lagi sebentar ya.';
        }

        // Simpan riwayat percakapan di session (untuk halaman AI)
        // dibatasi 100 pesan terakhir agar session tidak membengkak
        $request->session()->push('ai_messages', ['role' => 'user', 'text' => $request->message]);
        $request->session()->push('ai_messages', ['role' => 'assistant', 'text' => $reply]);
        $history = array_slice($request->session()->get('ai_messages', []), -100);
        $request->session()->put('ai_messages', $history);

        return response()->json([
            'reply'       => $reply,
            'transaction' => $transaction, // null jika bukan niat transaksi
        ]);
    }

    /**
     * OCR struk/gambar menggunakan Gemini Vision (SINGLE transaksi)
     * — dipakai halaman Tambah/Edisi transaksi untuk mengisi form.
     */
    public function ocr(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'API key belum dikonfigurasi.'], 500);
        }

        $image = $request->file('image');
        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType = $image->getMimeType();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(90)->connectTimeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Ekstrak data dari struk/bukti transaksi ini. Kembalikan dalam format JSON:
{
  "title": "keterangan/nama toko",
  "amount": angka nominal (tanpa format, tanpa Rp),
  "type": "income" atau "expense" (tebak dari konteks),
  "category": "kategori yang paling cocok dari: ' . self::CATEGORIES_HINT . '",
  "date": "YYYY-MM-DD" (tanggal transaksi jika ada, jika tidak gunakan hari ini)
}
Jika tidak bisa menentukan, gunakan nilai default yang masuk akal. Hanya kembalikan JSON, tidak ada teks lain.'],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 512,
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Gagal memproses gambar.'], 500);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            // Bersihkan response (hapus markdown code block jika ada)
            $text = trim($text);
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);

            $result = json_decode($text, true);

            if (!$result) {
                return response()->json(['error' => 'Gagal memproses data struk.'], 500);
            }

            // Simpan hasil OCR sebagai "old input" session — jadi saat user
            // membuka form tambah transaksi, field-nya sudah terisi otomatis
            // (dipakai tombol "Tambah transaksi ini" di halaman AI).
            $request->session()->flash('_old_input', [
                'title'            => $result['title'] ?? '',
                'amount'           => $result['amount'] ?? '',
                'type'             => $result['type'] ?? 'expense',
                'category'         => $result['category'] ?? '',
                'transaction_date' => $result['date'] ?? now()->format('Y-m-d'),
            ]);

            return response()->json(['data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * OCR struk ITEM PER ITEM — dipakai halaman AI Assistant.
     * Gemini mengembalikan array item (baris belanja), bukan satu transaksi.
     */
    public function ocrItems(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'API key belum dikonfigurasi.'], 500);
        }

        $image = $request->file('image');
        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType = $image->getMimeType();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(90)->connectTimeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Ekstrak struk/bukti ini ITEM PER ITEM (bukan satu transaksi gabungan). Kembalikan JSON SAJA:
{
  "store": "nama toko jika terbaca, jika tidak null",
  "date": "YYYY-MM-DD" (tanggal struk; jika tidak terbaca gunakan hari ini),
  "items": [
    {"title": "nama barang/layanan", "amount": angka, "type": "expense", "category": "kategori terbaik", "date": "YYYY-MM-DD"},
    {"title": "...", "amount": angka, ...}
  ]
}
Aturan:
- Struk belanja (supermarket/minimarket/warung/alfamart/indomaret): SATU ITEM PER BARIS barang belanjaan.
- Struk satu transaksi (makanan jadi, bensin, tagihan): cukup satu item.
- Abaikan baris total, subtotal, diskon, PPN, tunai, kembalian, poin, barcode.
- amount adalah ANGKA tanpa simbol/format (contoh 25000, bukan Rp25.000).
- type "expense" umumnya; "income" hanya jika struk menunjukkan penerimaan.
- category dari daftar: ' . self::CATEGORIES_HINT . '.
- Maksimal 30 item; jika lebih, prioritaskan item bernilai terbesar.
Hanya kembalikan JSON, tidak ada teks lain.'],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 2048,
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Gagal memproses gambar.'], 500);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            // Bersihkan response (hapus markdown code block jika ada)
            $text = trim($text);
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);

            $result = json_decode($text, true);

            if (!$result || empty($result['items'])) {
                return response()->json(['error' => 'Gagal memproses data struk. Pastikan foto struk jelas.'], 500);
            }

            // Normalisasi setiap item (kategori, nominal, tanggal, jenis)
            $items = [];
            foreach ($result['items'] as $raw) {
                $item = $this->parseTransactionItem($raw);
                if ($item) {
                    $items[] = $item;
                }
            }

            if (empty($items)) {
                return response()->json(['error' => 'Gagal memproses data struk.'], 500);
            }

            return response()->json([
                'store' => $result['store'] ?? null,
                'date'  => $result['date'] ?? now()->format('Y-m-d'),
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Simpan transaksi dari AI (1 atau banyak item) — dipanggil setelah
     * pengguna menekan tombol konfirmasi di kartu chat / hasil scan struk.
     */
    public function storeTransactions(Request $request)
    {
        $request->validate([
            'items'               => 'required|array|min:1|max:30',
            'items.*.title'       => 'required|string|max:255',
            'items.*.amount'      => 'required|numeric|min:1',
            'items.*.type'        => 'required|in:income,expense',
            'items.*.category'    => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'items.*.transaction_date' => 'required|date',
        ]);

        $created = [];
        foreach ($request->items as $item) {
            $created[] = Transaction::create([
                'user_id'          => Auth::id(),
                'title'            => trim($item['title']),
                'category'         => $item['category'],
                'amount'           => (float) $item['amount'],
                'type'             => $item['type'],
                'transaction_date' => Carbon::parse($item['transaction_date'])->format('Y-m-d'),
                'image'            => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'count'   => count($created),
        ]);
    }

    /* ------------------------------------------------------------------
     | Helper: deteksi & normalisasi transaksi dari balasan AI
     * ------------------------------------------------------------------ */

    /**
     * Cari blok <<<JSON ... JSON>>> di balasan AI; jika ada, artinya
     * pesan pengguna berisi niat mencatat transaksi.
     *
     * @return array{0: string, 1: array|null} [balasan bersih, item transaksi]
     */
    private function extractTransaction(string $reply): array
    {
        $transaction = null;

        if (preg_match('/<<<JSON(.*?)JSON>>>/s', $reply, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            $reply = trim(str_replace($m[0], '', $reply));
            $transaction = is_array($decoded) ? $decoded : null;
        } elseif (preg_match('/\{"intent"\s*:\s*"transaction".*?\}/s', $reply, $m2)) {
            $decoded = json_decode($m2[0], true);
            $reply = trim(str_replace($m2[0], '', $reply));
            $transaction = is_array($decoded) ? $decoded : null;
        }

        $item = $transaction ? $this->parseTransactionItem($transaction) : null;

        return [$reply, $item];
    }

    /**
     * Normalisasi satu item transaksi mentah (dari chat/OCR) menjadi
     * struktur yang valid & aman untuk disimpan.
     */
    private function parseTransactionItem(array $raw): ?array
    {
        $title = trim((string) ($raw['title'] ?? $raw['name'] ?? ''));
        $amount = $this->normalizeAmount($raw['amount'] ?? 0);

        if ($title === '' || $amount < 1) {
            return null;
        }

        return [
            'title'            => mb_substr($title, 0, 255),
            'amount'           => $amount,
            'type'             => in_array($raw['type'] ?? '', ['income', 'expense'], true) ? $raw['type'] : 'expense',
            'category'         => $this->normalizeCategory($raw['category'] ?? ''),
            'transaction_date' => $this->normalizeDate($raw['date'] ?? $raw['transaction_date'] ?? null),
        ];
    }

    /**
     * Ubah nominal mentah ("Rp 25.000", "25.000", "25000,50") menjadi float.
     */
    private function normalizeAmount($value): float
    {
        $s = preg_replace('/[^0-9.,]/', '', (string) $value);
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);      // 1.250,50 -> 1250,50
            $s = str_replace(',', '.', $s);     // -> 1250.50
        }
        return round(max(0, (float) $s), 2);
    }

    /**
     * Cocokkan kategori AI ke daftar kategori valid; fallback "Lainnya".
     */
    private function normalizeCategory($category): string
    {
        $cat = strtolower(trim((string) $category));
        $cat = str_replace(['kategori:', 'kategori ', 'category:', 'category '], '', $cat);

        foreach (Transaction::allCategories() as $valid) {
            if (strtolower($valid) === $cat) {
                return $valid;
            }
        }
        // Pencocokan sebagian: "makanan" -> "Makanan & Minuman", "transport" -> "Transportasi"
        foreach (Transaction::allCategories() as $valid) {
            $v = strtolower($valid);
            if ($v !== 'lainnya' && (str_contains($cat, $v) || str_contains($v, $cat))) {
                return $valid;
            }
        }

        return 'Lainnya';
    }

    /**
     * Normalisasi tanggal; fallback hari ini.
     */
    private function normalizeDate($value): string
    {
        if ($value) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // lanjut ke fallback
            }
        }
        return now()->format('Y-m-d');
    }
}
