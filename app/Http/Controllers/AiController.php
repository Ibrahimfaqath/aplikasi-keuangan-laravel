<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;

class AiController extends Controller
{
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
" . $recentTransactions->map(fn($t) => "- {$t->transaction_date}: {$t->title} ({$t->type}) Rp " . number_format($t->amount, 0, ',', '.') . " [{$t->category}]")->implode("\n");

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

            if ($response->failed()) {
                return response()->json(['error' => 'Gagal menghubungi AI. Coba lagi nanti.'], 500);
            }

            $data = $response->json();
            $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak bisa memproses permintaanmu.';

            // Simpan riwayat percakapan di session (untuk halaman AI)
            // dibatasi 100 pesan terakhir agar session tidak membengkak
            $request->session()->push('ai_messages', ['role' => 'user', 'text' => $request->message]);
            $request->session()->push('ai_messages', ['role' => 'assistant', 'text' => $reply]);
            $history = array_slice($request->session()->get('ai_messages', []), -100);
            $request->session()->put('ai_messages', $history);

            return response()->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * OCR struk/gambar menggunakan Gemini Vision
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
  "category": "kategori yang paling cocok dari: Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga, Lainnya",
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
}
