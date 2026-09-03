<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\Models\Transaction;
use App\Services\ReportingService;
use App\Services\TransactionParser;
use Illuminate\Support\Facades\Log;

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

        $apiKey = config('services.kiosapi.key');
        $apiUrl = config('services.kiosapi.url');
        $apiModel = config('services.kiosapi.model');

        if (blank($apiKey)) {
            Log::warning('KIOSAPI_API_KEY is not configured', [
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'reply' => 'Maaf, asisten belum dikonfigurasi. Silakan hubungi administrator.',
            ], 500);
        }

        $user = Auth::user();
        $now = Carbon::now();
        $chatStarted = microtime(true);

        // Financial context in 3 queries (was ~12): one conditional
        // aggregate for all periods, one category breakdown, one recent list.
        // The numbers produced are identical to the previous per-period
        // ReportingService queries; only the query count is reduced.
        $contextStarted = microtime(true);
        $ctx = $this->buildFinancialContext((int) $user->id, $now);
        $allTimeStats = $ctx['allTime'];
        $thisMonthStats = $ctx['thisMonth'];
        $thisMonthCategories = $ctx['monthCategories'];
        $lastMonthStats = $ctx['lastMonth'];
        $thisWeekStats = $ctx['thisWeek'];
        $thisYearStats = $ctx['thisYear'];
        $transactions = $ctx['recent'];
        $lastMonth = $ctx['lastMonthDate'];
        $contextMs = (int) round((microtime(true) - $contextStarted) * 1000);

        // Build system prompt dengan data lengkap
        $systemPrompt = "Kamu adalah asisten keuangan pribadi bernama DompetKu AI. Jawab dalam Bahasa Indonesia yang ramah dan santai.

DATA KEUANGAN USER (SEMUA WAKTU):
- Total Pemasukan: Rp " . number_format($allTimeStats['totalIncome'], 0, ',', '.') . "
- Total Pengeluaran: Rp " . number_format($allTimeStats['totalExpense'], 0, ',', '.') . "
- Saldo: Rp " . number_format($allTimeStats['totalBalance'], 0, ',', '.') . "

DATA BULAN INI (" . $now->isoFormat('MMMM YYYY') . "):
- Pemasukan: Rp " . number_format($thisMonthStats['totalIncome'], 0, ',', '.') . "
- Pengeluaran: Rp " . number_format($thisMonthStats['totalExpense'], 0, ',', '.') . "
- Saldo: Rp " . number_format($thisMonthStats['totalBalance'], 0, ',', '.') . "

KATEGORI PENGELUARAN BULAN INI:
" . $this->formatCategoryBreakdown($thisMonthCategories) . "

DATA BULAN LALU (" . $lastMonth->isoFormat('MMMM YYYY') . "):
- Pemasukan: Rp " . number_format($lastMonthStats['totalIncome'], 0, ',', '.') . "
- Pengeluaran: Rp " . number_format($lastMonthStats['totalExpense'], 0, ',', '.') . "
- Saldo: Rp " . number_format($lastMonthStats['totalBalance'], 0, ',', '.') . "

DATA MINGGU INI:
- Pemasukan: Rp " . number_format($thisWeekStats['totalIncome'], 0, ',', '.') . "
- Pengeluaran: Rp " . number_format($thisWeekStats['totalExpense'], 0, ',', '.') . "

DATA TAHUN INI:
- Pemasukan: Rp " . number_format($thisYearStats['totalIncome'], 0, ',', '.') . "
- Pengeluaran: Rp " . number_format($thisYearStats['totalExpense'], 0, ',', '.') . "

TRANSAKSI TERAKHIR (20):
" . $transactions->map(fn($t) => "- {$t->transaction_date}: {$t->title} ({$t->type}) Rp " . number_format($t->amount, 0, ',', '.'))->implode("\n") . "

ATURAN PENTING:
1. Gunakan DATA NYATA dari atas untuk menjawab pertanyaan user. JANGAN mengarang angka.
2. Jika user menanyakan data di luar periode yang tersedia, jawab dengan data yang ada atau minta klarifikasi.
3. Jika user ingin MENCATAT transaksi, ikuti aturan konfirmasi (lihat instruksi di bawah).
4. Format respons untuk transaksi: sertakan JSON di akhir respons.

" . ($this->isTransactionRequest($request->message) ? "
INSTRUKSI KHUSUS (user ingin mencatat transaksi):
- Deteksi transaksi dari pesan user.
- Berikan ringkasan dan MINTA KONFIRMASI.
- Sertakan JSON transaksi di akhir respons dengan format:
<<<JSON
{\"intent\":\"transaction\",\"title\":\"Judul\",\"amount\":25000,\"type\":\"expense\",\"category\":\"Makanan & Minuman\",\"date\":\"" . now()->format('Y-m-d') . "\"}
JSON>>>
" : "
INSTRUKSI KHUSUS (user bertanya tentang keuangan):
- Jawab berdasarkan DATA NYATA di atas.
- Berikan analisis sederhana jika diminta.
- Jika data tidak tersedia, katakan dengan jujur.
") . "
Kategori valid: " . self::CATEGORIES_HINT;

        $reply = null;
        $transaction = null;
        $httpStatus = null;
        $apiMs = null;
        $parseMs = null;

        try {
            $apiStarted = microtime(true);
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post($apiUrl, [
                    'model' => $apiModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $request->message],
                    ],
                    'temperature' => 0.7,
                ]);
            $apiMs = (int) round((microtime(true) - $apiStarted) * 1000);
            $httpStatus = $response->status();

            if ($response->successful()) {
                $parseStarted = microtime(true);
                $content = $this->extractContent($response);
                if ($content === null) {
                    Log::error('KiosAPI returned malformed response', [
                        'user_id' => Auth::id(),
                        'http_status' => $response->status(),
                        'body_sample' => mb_substr($response->body(), 0, 500),
                    ]);
                    $reply = 'Maaf, asisten sedang mengalami masalah. Coba lagi ya!';
                } else {
                    [$reply, $transaction] = $this->extractTransaction($content);
                }
                $parseMs = (int) round((microtime(true) - $parseStarted) * 1000);
            } else {
                $this->logApiError($response);
                $reply = 'Maaf, asisten sedang sibuk. Coba lagi dalam beberapa saat ya!';
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('KiosAPI connection error', [
                'user_id' => Auth::id(),
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            $reply = 'Maaf, koneksi ke asisten terputus. Periksa koneksi internetmu ya!';
        } catch (\Throwable $e) {
            Log::error('KiosAPI request exception', [
                'user_id' => Auth::id(),
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            $reply = 'Maaf, layanan AI sedang mengalami masalah teknis. Coba lagi ya!';
        }

        if ($reply === null) {
            $reply = 'Maaf, tidak ada balasan dari asisten. Coba lagi ya!';
        }

        // Safe timing breakdown (debug level): distinguishes application
        // latency (context/prompt building) from KiosAPI/model latency.
        // Never logs keys, headers, financial figures, or message content.
        Log::debug('AI chat timing', [
            'user_id' => Auth::id(),
            'context_ms' => $contextMs,
            'api_ms' => $apiMs,
            'parse_ms' => $parseMs,
            'total_ms' => (int) round((microtime(true) - $chatStarted) * 1000),
            'prompt_chars' => strlen($systemPrompt),
            'http_status' => $httpStatus,
            'has_transaction' => $transaction !== null,
        ]);

        // Simpan ke session untuk riwayat
        Session::push('ai_messages', ['role' => 'user', 'text' => $request->message]);
        Session::push('ai_messages', ['role' => 'assistant', 'text' => $reply]);
        
        // Store pending transaction in session if AI detected transaction intent
        if ($transaction) {
            Session::put('pending_transaction', $transaction);
        }
        
        $history = array_slice(Session::get('ai_messages', []), -100);
        Session::put('ai_messages', $history);

        return response()->json([
            'reply' => $reply,
            'transaction' => $transaction,
        ]);
    }

    public function confirmTransaction(Request $request)
    {
        // The server-side pending candidate is authoritative: the frontend
        // must send back the exact candidate it received from /ai/chat.
        // Without a valid pending candidate, confirmation is rejected.
        $pending = $this->normalizeCandidate(Session::get('pending_transaction'));

        if (!$pending) {
            Log::warning('AI confirm attempted without pending candidate', [
                'user_id' => Auth::id(),
            ]);
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
        if (!in_array($validated['category'], Transaction::categoriesFor($validated['type']), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak sesuai dengan jenis transaksi. Periksa lagi ya!',
            ], 422);
        }

        // The request must match the pending candidate field-for-field.
        // This prevents the frontend from sending arbitrary transaction data.
        $matches =
            trim($validated['title']) === $pending['title']
            && (float) $validated['amount'] === (float) $pending['amount']
            && $validated['type'] === $pending['type']
            && $validated['category'] === $pending['category']
            && Carbon::parse($validated['transaction_date'])->format('Y-m-d') === $pending['transaction_date'];

        if (!$matches) {
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
        // Clear pending transaction from session
        Session::forget('pending_transaction');

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
            $transaction = is_array($decoded) ? $this->normalizeCandidate($decoded) : null;
        }

        return [$reply, $transaction];
    }

    /**
     * Normalize an AI transaction candidate to the canonical server shape:
     * {title, amount, type, category, transaction_date}.
     *
     * Accepts the legacy "date" alias emitted by the model prompt so the
     * frontend can POST the candidate back verbatim. Returns null when the
     * candidate is not usable.
     */
    private function normalizeCandidate(mixed $candidate): ?array
    {
        if (!is_array($candidate)) {
            return null;
        }

        $title = trim((string) ($candidate['title'] ?? ''));
        $amount = (float) ($candidate['amount'] ?? 0);
        $type = (string) ($candidate['type'] ?? '');
        $category = trim((string) ($candidate['category'] ?? ''));
        $date = $candidate['transaction_date'] ?? $candidate['date'] ?? null;

        if ($title === '' || $amount < 1) {
            return null;
        }

        if (!in_array($type, ['income', 'expense'], true)) {
            return null;
        }

        if ($category === '' || !in_array($category, Transaction::allCategories(), true)) {
            return null;
        }

        try {
            $date = Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            $date = now()->format('Y-m-d');
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'amount' => $amount,
            'type' => $type,
            'category' => $category,
            'transaction_date' => $date,
        ];
    }

    /**
     * Validate and extract choices.0.message.content from an
     * OpenAI-compatible Chat Completions response.
     *
     * Returns null if the response is invalid/malformed.
     */
    private function extractContent($response): ?string
    {
        $data = $response->json();
        if (!is_array($data)) {
            return null;
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        return is_string($content) ? $content : null;
    }

    /**
     * Log a safe summary of a failed API response. Never logs the API key
     * or Authorization header.
     */
    private function logApiError($response): void
    {
        $status = $response->status();
        $reason = $response->reason();

        $context = [
            'user_id' => Auth::id(),
            'http_status' => $status,
            'reason' => $reason,
        ];

        if ($status === 401) {
            Log::warning('KiosAPI 401: invalid or missing API key', $context);
        } elseif ($status === 403) {
            Log::warning('KiosAPI 403: API access denied', $context);
        } elseif ($status === 400) {
            Log::warning('KiosAPI 400: invalid request/model/payload', $context);
        } elseif ($status === 404) {
            Log::warning('KiosAPI 404: wrong API endpoint', $context);
        } elseif ($status === 429) {
            Log::warning('KiosAPI 429: rate limit or quota exceeded', $context);
        } elseif ($status >= 500) {
            Log::error('KiosAPI 5xx: provider/server error', $context);
        } else {
            Log::error('KiosAPI unexpected HTTP status', $context);
        }
    }

    /**
     * Build all financial context for the system prompt in 3 queries:
     * one conditional aggregate across every period, one category
     * breakdown for the current month, and the 20 most recent transactions.
     * Period boundaries mirror ReportingService::getFilteredQuery so the
     * resulting figures are identical to the previous implementation.
     *
     * @return array{allTime: array, thisMonth: array, monthCategories: array, lastMonth: array, thisWeek: array, thisYear: array, recent: \Illuminate\Support\Collection, lastMonthDate: \Carbon\Carbon}
     */
    private function buildFinancialContext(int $userId, Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');
        $lastMonthDate = $now->copy()->subMonth();
        $lastStart = $lastMonthDate->copy()->startOfMonth()->format('Y-m-d');
        $lastEnd = $lastMonthDate->copy()->endOfMonth()->format('Y-m-d');
        $weekStart = $now->copy()->subDays(6)->format('Y-m-d');
        $yearStart = $now->copy()->startOfYear()->format('Y-m-d');
        $yearEnd = $now->copy()->endOfYear()->format('Y-m-d');

        $row = Transaction::where('user_id', $userId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS all_income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS all_expense")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS month_income", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS month_expense", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS last_income", [$lastStart, $lastEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS last_expense", [$lastStart, $lastEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date >= ? THEN amount ELSE 0 END), 0) AS week_income", [$weekStart])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date >= ? THEN amount ELSE 0 END), 0) AS week_expense", [$weekStart])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS year_income", [$yearStart, $yearEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS year_expense", [$yearStart, $yearEnd])
            ->first();

        $stats = static function (float $income, float $expense): array {
            return [
                'totalIncome' => $income,
                'totalExpense' => $expense,
                'totalBalance' => $income - $expense,
            ];
        };

        $reportingService = new ReportingService();
        $monthCategories = $reportingService->getCategoryBreakdown(
            $reportingService->getFilteredQuery(['period' => 'this_month'])
        );

        $recent = Transaction::where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get(['title', 'amount', 'type', 'category', 'transaction_date']);

        return [
            'allTime' => $stats((float) $row->all_income, (float) $row->all_expense),
            'thisMonth' => $stats((float) $row->month_income, (float) $row->month_expense),
            'monthCategories' => $monthCategories,
            'lastMonth' => $stats((float) $row->last_income, (float) $row->last_expense),
            'thisWeek' => $stats((float) $row->week_income, (float) $row->week_expense),
            'thisYear' => $stats((float) $row->year_income, (float) $row->year_expense),
            'recent' => $recent,
            'lastMonthDate' => $lastMonthDate,
        ];
    }

    private function formatCategoryBreakdown(array $categories): string
    {
        if (empty($categories)) {
            return "  (Belum ada data pengeluaran bulan ini)";
        }
        
        $result = [];
        foreach ($categories as $category => $amount) {
            $result[] = "  - {$category}: Rp " . number_format($amount, 0, ',', '.');
        }
        return implode("\n", $result);
    }

    private function isTransactionRequest(string $message): bool
    {
        return TransactionParser::fromText($message) !== null;
    }
}