<?php

namespace App\Services;

use App\Models\Category;
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
     * @param  array<int, array{role: string, content?: string, text?: string}>  $history  riwayat chat (memory), opsional
     * @return array{reply: string, transaction: ?array, timing: array{context_ms: int, api_ms: ?int, parse_ms: ?int}, unconfigured: bool}
     */
    public function chat(string $message, int $userId, Carbon $now, array $history = []): array
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

        $systemPrompt = $this->buildSystemPrompt($message, $now, $ctx, $userId);
        $cleanHistory = $this->sanitizeHistory($history);

        // Dua jalur: LangChain bila service dikonfigurasi, selain itu KiosAPI
        // langsung (perilaku lama). Situs live aman karena default null.
        if (blank($langchainUrl)) {
            [$reply, $transaction, $timing['api_ms'], $timing['parse_ms']] =
                $this->attemptViaKiosApi($systemPrompt, $message, $userId, $cleanHistory);
        } else {
            [$reply, $transaction, $timing['api_ms']] =
                $this->attemptViaLangChain($langchainUrl, $systemPrompt, $message, $userId, $cleanHistory);
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
            'history_items' => count($cleanHistory),
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
    private function attemptViaKiosApi(string $systemPrompt, string $message, int $userId, array $history = []): array
    {
        $apiMs = null;
        $parseMs = null;
        $reply = null;
        $transaction = null;

        try {
            $apiStarted = microtime(true);
            $messages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $history,
                [['role' => 'user', 'content' => $message]]
            );
            $response = Http::withToken(config('services.kiosapi.key'))
                ->acceptJson()
                ->timeout(60)
                ->post(config('services.kiosapi.url'), [
                    'model' => config('services.kiosapi.model'),
                    'messages' => $messages,
                    // 0 = deterministik. Penting untuk data keuangan agar angka
                    // tidak "kreatif", selaras dengan temperature: 0 di langchain-svc.
                    'temperature' => 0,
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
                    [$reply, $transaction] = $this->extractTransaction($content, $userId);
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
    private function attemptViaLangChain(string $url, string $systemPrompt, string $message, int $userId, array $history = []): array
    {
        $apiMs = null;

        try {
            $apiStarted = microtime(true);
            $endpoint = str_ends_with($url, '/chat') ? $url : rtrim($url, '/').'/chat';

            $pending = Http::acceptJson()->timeout(90);
            $token = (string) config('services.langchain.token', '');
            if ($token !== '') {
                $pending = $pending->withHeader('x-internal-token', $token);
            }

            $response = $pending->post($endpoint, [
                'system' => $systemPrompt,
                'message' => $message,
                'history' => $history,
                // Kategori user (bawaan + custom) agar service langchain-svc
                // memberi tahu model list yang valid dan menyetujuinya juga.
                'categories' => implode(', ', Category::allNames($userId)),
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
            $transaction = is_array($candidate) ? $this->normalizeCandidate($candidate, $userId) : null;

            if ($reply === '') {
                $reply = 'Maaf, tidak ada balasan dari asisten. Coba lagi ya!';
            }

            // Observability gratis (tanpa PII): catat token usage + versi prompt
            // bila service mengirimnya. Field tambahan diabaikan bila tidak ada
            // (backward-compatible dengan service versi lama).
            $usage = $data['usage'] ?? null;
            $usageLog = null;
            if (is_array($usage)) {
                $in = (int) ($usage['input_tokens'] ?? 0);
                $out = (int) ($usage['output_tokens'] ?? 0);
                $total = (int) ($usage['total_tokens'] ?? ($in + $out));
                if ($in >= 0 && $out >= 0 && $total >= 0 && ($in + $out + $total) > 0) {
                    $usageLog = ['input' => $in, 'output' => $out, 'total' => $total];
                }
            }
            Log::debug('LangChain usage', [
                'user_id' => $userId,
                'usage' => $usageLog,
                'prompt_version' => is_string($data['prompt_version'] ?? null) ? $data['prompt_version'] : null,
                'model' => is_string($data['model'] ?? null) ? $data['model'] : null,
                'has_transaction' => $transaction !== null,
            ]);

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
     * Bersihkan riwayat chat (memory) sebelum dikirim ke AI.
     * - Hanya role user/assistant (buang system agar tidak bisa di-inject).
     * - Terima alias 'text' dari session Laravel maupun 'content' dari API.
     * - Maks 20 pesan terakhir, tiap pesan max 2000 karakter (hemat token).
     *
     * @param  array<int, mixed>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function sanitizeHistory(array $history): array
    {
        $clean = [];
        foreach ($history as $item) {
            if (! is_array($item)) {
                continue;
            }
            $role = $item['role'] ?? null;
            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }
            $content = $item['content'] ?? $item['text'] ?? '';
            if (! is_string($content)) {
                continue;
            }
            $content = trim($content);
            if ($content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
        }

        return array_values(array_slice($clean, -20));
    }

    /**
     * Normalize kandidat transaksi dari AI ke bentuk kanonik server:
     * {title, amount, type, category, transaction_date}.
     *
     * Menerima alias "date" agar frontend bisa mengirim balik kandidat
     * secara verbatim. Mengembalikan null bila kandidat tidak bisa dipakai.
     */
    public function normalizeCandidate(mixed $candidate, ?int $userId = null): ?array
    {
        if (! is_array($candidate)) {
            return null;
        }

        $title = trim((string) ($candidate['title'] ?? ''));
        $amount = (float) ($candidate['amount'] ?? 0);
        $type = (string) ($candidate['type'] ?? '');
        $category = trim((string) ($candidate['category'] ?? ''));
        $date = $candidate['transaction_date'] ?? $candidate['date'] ?? null;

        if ($title === '' || $amount < 1 || $amount > 999999999999.99) {
            return null;
        }

        if (! in_array($type, ['income', 'expense'], true)) {
            return null;
        }

        $allowedCategories = $userId !== null ? Category::allNames($userId) : Transaction::allCategories();

        if ($category === '' || ! in_array($category, $allowedCategories, true)) {
            return null;
        }

        try {
            $parsed = Carbon::parse($date);
            $today = Carbon::today();

            // Jaring pengaman tanggal AI: tanggal masa depan (lebih dari +2 hari)
            // atau sebelum 2000 nyaris pasti tebakan salah. Turunkan ke hari ini.
            if ($parsed->gt($today->copy()->addDays(2)) || $parsed->lt(Carbon::parse('2000-01-01'))) {
                Log::warning('Tanggal transaksi AI di luar rentang wajar, dikembalikan ke hari ini', [
                    'parsed' => $parsed->format('Y-m-d'),
                    'today' => $today->format('Y-m-d'),
                ]);
                $date = $today->format('Y-m-d');
            } else {
                $date = $parsed->format('Y-m-d');
            }
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

    private function buildSystemPrompt(string $message, Carbon $now, array $ctx, ?int $userId = null): string
    {
        $allTimeStats = $ctx['allTime'];
        $thisMonthStats = $ctx['thisMonth'];
        $thisMonthCategories = $ctx['monthCategories'];
        $lastMonthStats = $ctx['lastMonth'];
        $thisWeekStats = $ctx['thisWeek'];
        $thisYearStats = $ctx['thisYear'];
        $transactions = $ctx['recent'];
        $lastMonth = $ctx['lastMonthDate'];

        $prompt = 'Kamu adalah asisten keuangan pribadi bernama dompetku AI. Jawab dalam Bahasa Indonesia yang ramah dan santai.

HARI INI: '.$now->isoFormat('dddd, D MMMM YYYY').' ('.$now->format('Y-m-d').').
Jika user menyebut "hari ini", "sekarang", atau "kemarin", hitung dari TANGGAL HARI INI di atas — jangan menebak dari data transaksi.

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
Kategori valid: '.$this->categoriesHint($userId);

        return $prompt;
    }

    private function extractTransaction(string $reply, ?int $userId = null): array
    {
        $transaction = null;
        if (preg_match('/<<<JSON(.*?)JSON>>>/s', $reply, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            $reply = trim(str_replace($m[0], '', $reply));
            $transaction = is_array($decoded) ? $this->normalizeCandidate($decoded, $userId) : null;
        }

        return [$reply, $transaction];
    }

    /**
     * Petunjuk daftar kategori untuk prompt AI, memakai kategori milik user
     * (bawaan global + custom). Tanpa userId memakai konstanta bawaan agar
     * pemanggil lama/test tetap berjalan.
     */
    private function categoriesHint(?int $userId = null): string
    {
        if ($userId === null) {
            return self::CATEGORIES_HINT;
        }

        return implode(', ', Category::allNames($userId));
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
