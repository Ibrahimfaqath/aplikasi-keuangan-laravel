<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\Models\Transaction;

class AiController extends Controller
{
    public const CATEGORIES_HINT = 'Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga';

    public function page()
    {
        $messages = Session::get('ai_messages', []);
        return view('ai.index', [
            'messages' => $messages,
        ]);
    }

    public function clear(Request $request)
    {
        Session::forget('ai_messages');
        return redirect()->route('ai.index')->with('success', 'Riwayat chat dibersihkan.');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $apiKey = config('services.deepseek.key');
        $apiUrl = config('services.deepseek.url');

        if (!$apiKey) {
            return response()->json(['error' => 'API key belum dikonfigurasi.'], 500);
        }

        $user = Auth::user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get(['title', 'amount', 'type', 'category', 'transaction_date']);

        $totalIncome = Transaction::where('user_id', $user->id)->where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('user_id', $user->id)->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $systemPrompt = "Kamu adalah asisten keuangan pribadi bernama DompetKu AI. Jawab dalam Bahasa Indonesia yang ramah dan santai.

DATA KEUANGAN USER:
- Total Pemasukan: Rp " . number_format($totalIncome, 0, ',', '.') . "
- Total Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "
- Saldo: Rp " . number_format($balance, 0, ',', '.') . "

TRANSAKSI TERAKHIR:
" . $transactions->map(fn($t) => "- {$t->transaction_date}: {$t->title} ({$t->type}) Rp " . number_format($t->amount, 0, ',', '.'))->implode("\n") . "

ATURAN PENTING:
1. Jika user ingin MENCATAT transaksi, jangan langsung simpan. Berikan ringkasan transaksi dan MINTA KONFIRMASI dulu.
2. Format respons untuk transaksi:
   'Aku tangkap ya: [detail transaksi]. Setujui dengan klik tombol Simpan di bawah, atau ketik batal.'
3. Sertakan JSON transaksi di akhir respons dengan format:
   <<<JSON
   {\"intent\":\"transaction\",\"title\":\"Judul\",\"amount\":25000,\"type\":\"expense\",\"category\":\"Makanan & Minuman\",\"date\":\"" . now()->format('Y-m-d') . "\"}
   JSON>>>
4. Kategori valid: " . self::CATEGORIES_HINT;

        $reply = null;
        $transaction = null;

        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(30)->post($apiUrl, [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $request->message],
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? null;
                
                [$reply, $transaction] = $this->extractTransaction($reply ?? '');
            }
        } catch (\Throwable $e) {
            $reply = null;
        }

        if ($reply === null) {
            $reply = 'Maaf, layanan sedang sibuk. Coba lagi ya!';
        }

        // Simpan ke session untuk riwayat
        Session::push('ai_messages', ['role' => 'user', 'text' => $request->message]);
        Session::push('ai_messages', ['role' => 'assistant', 'text' => $reply]);
        
        $history = array_slice(Session::get('ai_messages', []), -100);
        Session::put('ai_messages', $history);

        return response()->json([
            'reply' => $reply,
            'transaction' => $transaction,
        ]);
    }

    public function confirmTransaction(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:50',
            'transaction_date' => 'required|date',
        ]);

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'title' => trim($request->title),
            'category' => $request->category,
            'amount' => (float) $request->amount,
            'type' => $request->type,
            'transaction_date' => Carbon::parse($request->transaction_date)->format('Y-m-d'),
            'image' => null,
        ]);

        $successMsg = "✅ Transaksi berhasil disimpan!\n📝 {$transaction->title}\n💰 Rp " . number_format($transaction->amount, 0, ',', '.') . "\n📂 {$transaction->category}";
        
        Session::push('ai_messages', [
            'role' => 'assistant', 
            'text' => $successMsg,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Transaksi berhasil disimpan!\n📝 {$transaction->title}\n💰 Rp " . number_format($transaction->amount, 0, ',', '.') . "\n📂 {$transaction->category}",
            'transaction' => $transaction
        ]);
    }

    public function cancelTransaction(Request $request)
    {
        Session::push('ai_messages', [
            'role' => 'assistant', 
            'text' => '❌ Transaksi dibatalkan. Ketik ulang jika ingin mencatat lagi ya!',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi dibatalkan'
        ]);
    }

    public function ocr(Request $request)
    {
        return response()->json(['error' => 'Fitur scan struk saat ini dinonaktifkan. Silakan gunakan Voice atau Chat AI.'], 400);
    }

    public function ocrItems(Request $request)
    {
        return response()->json(['error' => 'Fitur scan struk saat ini dinonaktifkan.'], 400);
    }

    public function storeTransactions(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1|max:30',
            'items.*.title' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:1',
            'items.*.type' => 'required|in:income,expense',
            'items.*.category' => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'items.*.transaction_date' => 'required|date',
        ]);

        $created = [];
        foreach ($request->items as $item) {
            $created[] = Transaction::create([
                'user_id' => Auth::id(),
                'title' => trim($item['title']),
                'category' => $item['category'],
                'amount' => (float) $item['amount'],
                'type' => $item['type'],
                'transaction_date' => Carbon::parse($item['transaction_date'])->format('Y-m-d'),
                'image' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => count($created),
        ]);
    }

    private function extractTransaction(string $reply): array
    {
        $transaction = null;
        if (preg_match('/<<<JSON(.*?)JSON>>>/s', $reply, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            $reply = trim(str_replace($m[0], '', $reply));
            $transaction = is_array($decoded) ? $decoded : null;
        }

        if ($transaction) {
            $title = trim((string) ($transaction['title'] ?? ''));
            $amount = (float) ($transaction['amount'] ?? 0);
            if ($title === '' || $amount < 1) {
                $transaction = null;
            }
        }

        return [$reply, $transaction];
    }
}