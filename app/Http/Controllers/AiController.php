<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AiAssistantService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class AiController extends Controller
{
    public function __construct(private AiAssistantService $assistant) {}

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

        $userId = Auth::id();
        $result = $this->assistant->chat($request->message, (int) $userId, Carbon::now());

        $transaction = $result['transaction'];

        if ($result['unconfigured']) {
            return response()->json([
                'reply' => $result['reply'],
            ], 500);
        }

        // Simpan ke session untuk riwayat
        Session::push('ai_messages', ['role' => 'user', 'text' => $request->message]);
        Session::push('ai_messages', ['role' => 'assistant', 'text' => $result['reply']]);

        // Store pending transaction in session if AI detected transaction intent
        if ($transaction) {
            Session::put('pending_transaction', $transaction);
        }

        $history = array_slice(Session::get('ai_messages', []), -100);
        Session::put('ai_messages', $history);

        return response()->json([
            'reply' => $result['reply'],
            'transaction' => $transaction,
        ]);
    }

    public function confirmTransaction(Request $request)
    {
        // The server-side pending candidate is authoritative: the frontend
        // must send back the exact candidate it received from /ai/chat.
        // Without a valid pending candidate, confirmation is rejected.
        $pending = $this->assistant->normalizeCandidate(Session::get('pending_transaction'));

        if (! $pending) {
            Session::forget('pending_transaction');

            return response()->json([
                'success' => false,
                'message' => 'Sesi konfirmasi telah berakhir. Ketik ulang transaksi jika ingin mencatat ya!',
            ], 400);
        }

        // Accept the legacy "date" alias so older/verbatim AI payloads validate.
        $request->merge([
            'transaction_date' => $request->input('transaction_date', $request->input('date')),
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1|max:999999999999.99',
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'transaction_date' => 'required|date',
        ]);

        // Category must be valid for the selected type (e.g. an expense
        // cannot be saved with the income-only "Gaji" category).
        if (! in_array($validated['category'], Transaction::categoriesFor($validated['type']), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak sesuai dengan jenis transaksi. Periksa lagi ya!',
            ], 422);
        }

        // The request must match the pending candidate field-for-field.
        // This prevents the frontend from sending arbitrary transaction data.
        // Amount pakai toleransi 1 sen agar tidak false-negative karena presisi float.
        $matches =
            trim($validated['title']) === $pending['title']
            && abs((float) $validated['amount'] - (float) $pending['amount']) < 0.01
            && $validated['type'] === $pending['type']
            && $validated['category'] === $pending['category']
            && Carbon::parse($validated['transaction_date'])->format('Y-m-d') === $pending['transaction_date'];

        if (! $matches) {
            Log::warning('AI confirm request does not match pending candidate', [
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Data konfirmasi tidak sesuai. Ketik ulang transaksi jika ingin mencatat ya!',
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'title' => $pending['title'],
            'category' => $pending['category'],
            'amount' => $pending['amount'],
            'type' => $pending['type'],
            'transaction_date' => $pending['transaction_date'],
            'image' => null,
        ]);

        // Clear pending AFTER successful creation; a retry then gets 400,
        // so double confirmation can never create a duplicate.
        Session::forget('pending_transaction');

        $message = "Transaksi berhasil disimpan!\n📝 {$transaction->title}\n💰 Rp ".number_format($transaction->amount, 0, ',', '.')."\n📂 {$transaction->category}";

        Session::push('ai_messages', ['role' => 'assistant', 'text' => '✅ '.$message]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'transaction' => $transaction,
        ]);
    }

    public function cancelTransaction(Request $request)
    {
        // Clear pending transaction from session
        Session::forget('pending_transaction');

        Session::push('ai_messages', [
            'role' => 'assistant',
            'text' => '❌ Transaksi dibatalkan. Ketik ulang jika ingin mencatat lagi ya!',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi dibatalkan',
        ]);
    }

    public function storeTransactions(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:30',
            'items.*.title' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:1|max:999999999999.99',
            'items.*.type' => 'required|in:income,expense',
            'items.*.category' => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'items.*.transaction_date' => 'required|date',
        ]);

        // Kategori harus sesuai jenisnya (sama seperti confirmTransaction).
        // Contoh: expense tidak boleh pakai kategori "Gaji".
        foreach ($validated['items'] as $index => $item) {
            if (! in_array($item['category'], Transaction::categoriesFor($item['type']), true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ke-'.($index + 1).' kategorinya tidak sesuai dengan jenis transaksi. Periksa lagi ya!',
                ], 422);
            }
        }

        // Simpan atomik: kalau 1 item gagal, semua dibatalkan (tidak ada data setengah jadi).
        $created = DB::transaction(function () use ($validated) {
            $rows = [];
            foreach ($validated['items'] as $item) {
                $rows[] = Transaction::create([
                    'user_id' => Auth::id(),
                    'title' => trim($item['title']),
                    'category' => $item['category'],
                    'amount' => (float) $item['amount'],
                    'type' => $item['type'],
                    'transaction_date' => Carbon::parse($item['transaction_date'])->format('Y-m-d'),
                    'image' => null,
                ]);
            }

            return $rows;
        });

        return response()->json([
            'success' => true,
            'count' => count($created),
        ]);
    }
}
