<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    public const CATEGORIES_HINT = 'Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga';

    public function __construct(private FinancialContextBuilder $contextBuilder) {}

    /**
     * Bangun system prompt berisi data keuangan nyata user, lalu panggil API,
     * dan ekstrak balasan + kandidat transaksi (bila ada).
     *
     * @return array{reply: string, transaction: ?array, timing: array{context_ms: int, api_ms: ?int, parse_ms: ?int}, unconfigured: bool}
     */
    public function chat(string $message, int $userId, Carbon $now): array
    {
        $chatStarted = microtime(true);
        $langchainUrl = config('services.langchain.url');

        if (blank($langchainUrl) && blank(config('services.kiosapi.key'))) {
            Log::warning('KIOSAPI_API_KEY is not configured', [
                'user_id' => $userId,
            ]);

            return [
                'reply' => 'Maaf, asisten belum dikonfigurasi. Silakan hubungi administrator.',
                'transaction' => null,
                'timing' => [
                    'context_ms' => 0,
                    'api_ms' => null,
                    'parse_ms' => null,
                ],
                'unconfigured' => true,
            ];
        }

        $contextStarted = microtime(true);
        $ctx = $this->contextBuilder->build($userId, $now);
        $timing['context_ms'] = (int) round((microtime(true) - $contextStarted) * 1000);

        $systemPrompt = $this->buildSystemPrompt($message, $now, $ctx);

        // Dua jalur: LangChain bila service dikonfigurasi, selain itu KiosAPI
        // langsung (perilaku lama). Situs live aman karena default null.
        if (blank($langchainUrl)) {
            [$reply, $transaction, $timing['api_ms'], $timing['parse_ms']] =
                $this->attemptViaKiosApi($systemPrompt, $message, $userId);
        } else {
            [$reply, $transaction, $timing['api_ms']] =
                $this->attemptViaLangChain($langchainUrl, $systemPrompt, $message, $userId);
            $timing['parse_ms'] = null;
        }

        if ($reply === null) {
            $reply = 'Maaf, tidak ada balasan dari asisten. Coba lagi ya!';
        }

        $timing['total_ms'] = (int) round((microtime(true) - $chatStarted) * 1000);

        // Safe timing breakdown (debug level): never logs keys, headers,
        // financial figures, or message content.
        Log::debug('AI chat timing', array_merge([
            'user_id' => $userId,
            'prompt_chars' => strlen($systemPrompt),
            'has_transaction' => $transaction !== null,
            'source' => blank($langchainUrl) ? 'kiosapi' : 'langchain',
        ], $timing));

        return [
            'reply' => $reply,
            'transaction' => $transaction,
            'timing' => $timing,
            'unconfigured' => false,
        ];
    }

    /**
     * Jalur langsung ke KiosAPI (OpenAI-compatible). Ini perilaku lama dan
     * dipakai sebagai fallback bila LANGCHAIN_SERVICE_URL tidak di-set.
     *
     * @return array{0: ?string, 1: ?array, 2: ?int, 3: ?int} [reply, transaction, api_ms, parse_ms]
     */
    private function attemptViaKiosApi(string $systemPrompt, string $message, int $userId): array
    {
        $apiMs = null;
        $parseMs = null;
        $reply = null;
        $transaction = null;

        try {
            $apiStarted = microtime(true);
            $response = Http::withToken(config('services.kiosapi.key'))
                ->acceptJson()
                ->timeout(60)
                ->post(config('services.kiosapi.url'), [
                    'model' => config('services.kiosapi.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.7,
                ]);
            $apiMs = (int) round((microtime(true) - $apiStarted) * 1000);

            if ($response->successful()) {
                $parseStarted = microtime(true);
                $content = $this->extractContent($response);
                if ($content === null) {
                    Log::error('KiosAPI returned malformed response', [
                        'user_id' => $userId,
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
        } catch (ConnectionException $e) {
            Log::error('KiosAPI connection error', [
                'user_id' => $userId,
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            $reply = 'Maaf, koneksi ke asisten terputus. Periksa koneksi internetmu ya!';
        } catch (\Throwable $e) {
            Log::error('KiosAPI request exception', [
                'user_id' => $userId,
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            $reply = 'Maaf, layanan AI sedang mengalami masalah teknis. Coba lagi ya!';
        }

        return [$reply, $transaction, $apiMs, $parseMs];
    }

    /**
     * Jalur LangChain: memanggil service langchain-svc/server.js yang sudah
     * mengembalikan JSON terstruktur {reply, transaction} via rantai LangChain
     * (PromptTemplate -> ChatOpenAI/KiosAPI -> JsonOutputParser).
     *
     * @return array{0: ?string, 1: ?array, 2: ?int} [reply, transaction, api_ms]
     */
    private function attemptViaLangChain(string $url, string $systemPrompt, string $message, int $userId): array
    {
        $apiMs = null;

        try {
            $apiStarted = microtime(true);
            $endpoint = str_ends_with($url, '/chat') ? $url : rtrim($url, '/').'/chat';

            $response = Http::acceptJson()
                ->timeout(90)
                ->post($endpoint, [
                    'system' => $systemPrompt,
                    'message' => $message,
                ]);
            $apiMs = (int) round((microtime(true) - $apiStarted) * 1000);

            if (! $response->successful()) {
                $this->logApiError($response);

                return ['Maaf, asisten sedang sibuk. Coba lagi dalam beberapa saat ya!', null, $apiMs];
            }

            $data = $response->json();
            if (! is_array($data)) {
                Log::error('LangChain returned malformed response', [
                    'user_id' => $userId,
                    'http_status' => $response->status(),
                    'body_sample' => mb_substr($response->body(), 0, 500),
                ]);

                return ['Maaf, asisten sedang mengalami masalah. Coba lagi ya!', null, $apiMs];
            }

            $reply = trim((string) ($data['reply'] ?? ''));
            $candidate = $data['transaction'] ?? null;
            $transaction = is_array($candidate) ? $this->normalizeCandidate($candidate) : null;

            if ($reply === '') {
                $reply = 'Maaf, tidak ada balasan dari asisten. Coba lagi ya!';
            }

            return [$reply, $transaction, $apiMs];
        } catch (ConnectionException $e) {
            Log::error('LangChain connection error', [
                'user_id' => $userId,
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return ['Maaf, koneksi ke asisten terputus. Periksa koneksi internetmu ya!', null, $apiMs];
        } catch (\Throwable $e) {
            Log::error('LangChain request exception', [
                'user_id' => $userId,
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return ['Maaf, layanan AI sedang mengalami masalah teknis. Coba lagi ya!', null, $apiMs];
        }
    }

    /**
     * Normalize kandidat transaksi dari AI ke bentuk kanonik server:
     * {title, amount, type, category, transaction_date}.
     *
     * Menerima alias "date" agar frontend bisa mengirim balik kandidat
     * secara verbatim. Mengembalikan null bila kandidat tidak bisa dipakai.
     */
    public function normalizeCandidate(mixed $candidate): ?array
    {
        if (! is_array($candidate)) {
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

        if (! in_array($type, ['income', 'expense'], true)) {
            return null;
        }

        if ($category === '' || ! in_array($category, Transaction::allCategories(), true)) {
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

    private function buildSystemPrompt(string $message, Carbon $now, array $ctx): string
    {
        $allTimeStats = $ctx['allTime'];
        $thisMonthStats = $ctx['thisMonth'];
        $thisMonthCategories = $ctx['monthCategories'];
        $lastMonthStats = $ctx['lastMonth'];
        $thisWeekStats = $ctx['thisWeek'];
        $thisYearStats = $ctx['thisYear'];
        $transactions = $ctx['recent'];
        $lastMonth = $ctx['lastMonthDate'];

        $prompt = 'Kamu adalah asisten keuangan pribadi bernama DompetKu AI. Jawab dalam Bahasa Indonesia yang ramah dan santai.

DATA KEUANGAN USER (SEMUA WAKTU):
- Total Pemasukan: Rp '.number_format($allTimeStats['totalIncome'], 0, ',', '.').'
- Total Pengeluaran: Rp '.number_format($allTimeStats['totalExpense'], 0, ',', '.').'
- Saldo: Rp '.number_format($allTimeStats['totalBalance'], 0, ',', '.').'

DATA BULAN INI ('.$now->isoFormat('MMMM YYYY').'):
- Pemasukan: Rp '.number_format($thisMonthStats['totalIncome'], 0, ',', '.').'
- Pengeluaran: Rp '.number_format($thisMonthStats['totalExpense'], 0, ',', '.').'
- Saldo: Rp '.number_format($thisMonthStats['totalBalance'], 0, ',', '.').'

KATEGORI PENGELUARAN BULAN INI:
'.$this->contextBuilder->formatCategoryBreakdown($thisMonthCategories).'

DATA BULAN LALU ('.$lastMonth->isoFormat('MMMM YYYY').'):
- Pemasukan: Rp '.number_format($lastMonthStats['totalIncome'], 0, ',', '.').'
- Pengeluaran: Rp '.number_format($lastMonthStats['totalExpense'], 0, ',', '.').'
- Saldo: Rp '.number_format($lastMonthStats['totalBalance'], 0, ',', '.').'

DATA MINGGU INI:
- Pemasukan: Rp '.number_format($thisWeekStats['totalIncome'], 0, ',', '.').'
- Pengeluaran: Rp '.number_format($thisWeekStats['totalExpense'], 0, ',', '.').'

DATA TAHUN INI:
- Pemasukan: Rp '.number_format($thisYearStats['totalIncome'], 0, ',', '.').'
- Pengeluaran: Rp '.number_format($thisYearStats['totalExpense'], 0, ',', '.').'

TRANSAKSI TERAKHIR (20):
'.$transactions->map(fn ($t) => "- {$t->transaction_date}: {$t->title} ({$t->type}) Rp ".number_format($t->amount, 0, ',', '.'))->implode("\n").'

ATURAN PENTING:
1. Gunakan DATA NYATA dari atas untuk menjawab pertanyaan user. JANGAN mengarang angka.
2. Jika user menanyakan data di luar periode yang tersedia, jawab dengan data yang ada atau minta klarifikasi.
3. Jika user ingin MENCATAT transaksi, ikuti aturan konfirmasi (lihat instruksi di bawah).
4. Format respons untuk transaksi: sertakan JSON di akhir respons.

'.(TransactionParser::fromText($message) !== null ? '
INSTRUKSI KHUSUS (user ingin mencatat transaksi):
- Deteksi transaksi dari pesan user.
- Berikan ringkasan dan MINTA KONFIRMASI.
- Sertakan JSON transaksi di akhir respons dengan format:
<<<JSON
{"intent":"transaction","title":"Judul","amount":25000,"type":"expense","category":"Makanan & Minuman","date":"'.now()->format('Y-m-d').'"}
JSON>>>
' : '
INSTRUKSI KHUSUS (user bertanya tentang keuangan):
- Jawab berdasarkan DATA NYATA di atas.
- Berikan analisis sederhana jika diminta.
- Jika data tidak tersedia, katakan dengan jujur.
').'
Kategori valid: '.self::CATEGORIES_HINT;

        return $prompt;
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
     * Validasi dan ekstrak choices.0.message.content dari respons
     * OpenAI-compatible Chat Completions. Mengembalikan null bila respons
     * invalid/malformed.
     */
    private function extractContent($response): ?string
    {
        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        return is_string($content) ? $content : null;
    }

    /**
     * Log ringkasan aman dari respons API yang gagal. Tidak pernah mencatat
     * API key atau header Authorization.
     */
    private function logApiError($response): void
    {
        $status = $response->status();
        $reason = $response->reason();

        $context = [
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
}
