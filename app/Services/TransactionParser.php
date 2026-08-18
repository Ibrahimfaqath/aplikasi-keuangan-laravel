<?php

namespace App\Services;

use App\Models\Transaction;

/**
 * Parser lokal (fallback) untuk mendeteksi niat transaksi dari teks chat
 * biasa berbahasa Indonesia — dipakai bila Gemini tidak mengembalikan
 * blok JSON transaksi, supaya fitur "input transaksi lewat AI" selalu
 * berfungsi cepat dan tepat.
 */
class TransactionParser
{
    /** Kata kunci yang menandakan PEMASUKAN (uang masuk). */
    private const INCOME_KEYWORDS = [
        'gaji', 'bonus', 'thr', 'pendapatan', 'pemasukan', 'cashback',
        'refund', 'pengembalian', 'honor', 'upah', 'kiriman', 'dikirim',
        'transfer masuk', 'masuk', 'terima', 'jual', 'penjualan',
        'hasil', 'dividen', 'bunga', 'rejeki', 'rezeki',
    ];

    /** Kata kunci yang menandakan PENGELUARAN (uang keluar). */
    private const EXPENSE_KEYWORDS = [
        'beli', 'bayar', 'jajan', 'makan', 'minum', 'habis', 'isi',
        'top up', 'topup', 'langganan', 'sewa', 'cicil', 'angsur',
        'kirim', 'transfer keluar', 'tarik', 'ambil', 'belanja',
        'pembelian', 'pembayaran', 'pinjam', 'minjem', 'utang', 'hutang',
        'kehabisan', 'pakai', 'pake', 'ngopi',
    ];

    /** Pemetaan kata kunci → kategori (dicocokkan pada teks lengkap). */
    private const CATEGORY_KEYWORDS = [
        'Makanan & Minuman'    => ['makan', 'nasi', 'kopi', 'minum', 'ngopi', 'goreng', 'ayam', 'mie', 'sate', 'bakso', 'warteg', 'restoran', 'kafe', 'jajan', 'camilan', 'snack', 'buah', 'sarapan', 'makanan'],
        'Transportasi'         => ['bensin', 'pertalite', 'solar', 'ojek', 'grab', 'gojek', 'maxim', 'tol', 'parkir', 'bbm', 'kendaraan', 'angkut', 'transport'],
        'Tagihan & Utilitas'   => ['listrik', 'air', 'pulsa', 'token', 'wifi', 'internet', 'bpjs', 'pajak', 'telepon', 'telp', 'tagihan', 'iuran'],
        'Belanja'              => ['belanja', 'alfamart', 'indomaret', 'minimarket', 'supermarket', 'swalayan', 'pasar', 'kebutuhan'],
        'Hiburan'              => ['nonton', 'film', 'bioskop', 'game', 'netflix', 'spotify', 'konser', 'liburan', 'hiburan'],
        'Kesehatan'            => ['obat', 'dokter', 'klinik', 'apotik', 'apotek', 'berobat', 'vitamin', 'rumah sakit', 'kesehatan'],
        'Pendidikan'           => ['buku', 'kursus', 'sekolah', 'kuliah', 'spp', 'les', 'seminar', 'pelatihan', 'pendidikan'],
        'Keluarga'             => ['keluarga', 'anak', 'ibu', 'ayah', 'orang tua', 'adik', 'adek', 'kakak', 'rumah tangga'],
        'Gaji'                 => ['gaji', 'upah', 'honor'],
        'Bonus'                => ['bonus', 'thr'],
        'Bisnis'               => ['bisnis', 'jualan', 'dagang', 'usaha', 'toko'],
        'Investasi'            => ['investasi', 'saham', 'reksadana', 'dividen', 'bunga', 'emas'],
        'Hadiah'               => ['hadiah', 'kado'],
    ];

    /**
     * Deteksi niat transaksi dari teks chat.
     *
     * @return array{title:string, amount:float, type:string, category:string, transaction_date:string}|null
     */
    public static function fromText(string $message): ?array
    {
        $text = mb_strtolower(trim($message));
        if ($text === '') {
            return null;
        }

        [$amount, $amountStr] = self::extractAmount($text);
        if ($amount === null) {
            return null;
        }

        $type = self::detectType($text);
        if ($type === null) {
            // Ada nominal tapi tidak ada kata kunci tegas (mis. "uang 50 ribu") —
            // biarkan Gemini yang memutuskan, hindari salah tangkap.
            // KECUALI pesan berisi kata perintah mencatat ("catat ...",
            // "input ...", "tambah ...") — itu jelas niat transaksi, default pengeluaran.
            if (!self::hasRecordIntent($text)) {
                return null;
            }
            $type = 'expense';
        }

        $title = self::extractTitle($message, $amountStr, $type);
        $category = self::detectCategory($text, $type);

        if ($title === '') {
            $title = $category;
        }

        return [
            'title'            => mb_substr($title, 0, 255),
            'amount'           => $amount,
            'type'             => $type,
            'category'         => $category,
            'transaction_date' => now()->format('Y-m-d'),
        ];
    }

    /**
     * Ekstrak nominal dari teks. Mendukung:
     *   "25 ribu", "5 juta", "200rb", "1,5 juta", "2.5 juta",
     *   "Rp 25.000", "25.000", "25000", "15k".
     *
     * @return array{0: float|null, 1: string|null} [nilai, substring mentah yang cocok]
     */
    private static function extractAmount(string $text): array
    {
        // Pola satuan: angka + (ribu|rb|juta|jt|miliar|k)
        if (preg_match('/(\d{1,3}(?:[.,]\d{1,3})?)\s*(ribu|rb|juta|jt|miliar|k)\b/u', $text, $m)) {
            $num = (float) str_replace(',', '.', $m[1]);
            $multiplier = match ($m[2]) {
                'ribu', 'rb', 'k' => 1000,
                'juta', 'jt'      => 1_000_000,
                'miliar'          => 1_000_000_000,
                default           => 1000,
            };
            return [round($num * $multiplier, 2), $m[0]];
        }

        // Pola format Rp: "Rp 25.000", "Rp25.000", "Rp 25.000,50"
        if (preg_match('/(?:rp\s?)(\d{1,3}(?:[.,]\d{3})+(?:[.,]\d+)?|\d+(?:[.,]\d+)?)/u', $text, $m)) {
            return [self::parsePlainNumber($m[1]), $m[0]];
        }

        // Pola angka biasa (minimal 4 digit = ribuan): "25.000", "25000", "1.250.000"
        if (preg_match('/(\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d{4,}(?:,\d+)?)/u', $text, $m)) {
            return [self::parsePlainNumber($m[1]), $m[0]];
        }

        // Pola angka TERUCAP (hasil voice input Chrome id-ID):
        //   "dua puluh lima ribu", "seratus ribu", "lima juta"
        // Chrome sering mengubah angka jadi kata saat diketik via suara.
        $numWord = '(?:nol|satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh|sebelas|belas|puluh|ratus|ribu|juta|miliar|seratus|seribu|sejuta)';
        if (preg_match('/\b' . $numWord . '(?:\s+' . $numWord . ')*\b/u', $text, $m)) {
            // Hanya terima jika ada kata skala uang (ribu/juta/miliar) —
            // hindari salah tangkap kata unit tunggal ("satu kopi", "dua buku").
            if (preg_match('/\b(?:ribu|juta|miliar)\b/u', $m[0])) {
                $value = self::parseSpelledNumber($m[0]);
                if ($value !== null && $value > 0) {
                    return [$value, $m[0]];
                }
            }
        }

        return [null, null];
    }

    /**
     * Ubah angka yang diucapkan dalam Bahasa Indonesia menjadi float.
     * Mendukung kombinasi umum: "dua puluh lima ribu", "seratus ribu",
     * "satu juta lima ratus ribu", "lima belas ribu", "dua ratus lima puluh".
     *
     * Algoritma: susun di stack, lalu skala (ribu/juta/miliar) mengunci
     * seluruh nilai yang sudah terakumulasi. Contoh:
     *   dua ratus lima puluh  => [200, 50]  => 250
     *   dua puluh lima ribu   => [20, 5] *1000 => 25000
     */
    private static function parseSpelledNumber(string $phrase): ?float
    {
        $small = [
            'nol' => 0, 'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4,
            'lima' => 5, 'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9,
            'sepuluh' => 10, 'sebelas' => 11,
            'seratus' => 100, 'seribu' => 1000, 'sejuta' => 1_000_000,
        ];
        $scale = [
            'ribu'   => 1_000,
            'juta'   => 1_000_000,
            'miliar' => 1_000_000_000,
        ];

        $words = preg_split('/\s+/u', trim(mb_strtolower($phrase))) ?: [];
        if (!$words) {
            return null;
        }

        $stack = [];
        $total = 0;

        foreach ($words as $word) {
            if (isset($small[$word])) {
                $stack[] = $small[$word];
                continue;
            }
            if ($word === 'belas') {
                $last = array_pop($stack);
                if ($last === null || $last < 1 || $last > 9) {
                    return null;
                }
                $stack[] = $last + 10;   // 11-19
                continue;
            }
            if ($word === 'puluh') {
                $last = array_pop($stack);
                if ($last === null || $last < 1 || $last > 9) {
                    return null;
                }
                $stack[] = $last * 10;   // 20-90
                continue;
            }
            if ($word === 'ratus') {
                $last = array_pop($stack);
                if ($last === null || $last < 1 || $last > 9) {
                    return null;
                }
                $stack[] = $last * 100;  // 100-900
                continue;
            }
            if (isset($scale[$word])) {
                $value = array_sum($stack);
                if ($value <= 0) {
                    $value = 1;          // "ribu" saja = 1000
                }
                $total += $value * $scale[$word];
                $stack = [];
                continue;
            }
            // kata asing di tengah frasa — hentikan parsing
            return null;
        }

        $value = $total + array_sum($stack);

        return $value > 0 ? round($value, 2) : null;
    }

    /**
     * Ubah angka berformat Indonesia ("25.000" / "25,5" / "25000") ke float.
     * Titik = pemisah ribuan, koma = desimal.
     */
    private static function parsePlainNumber(string $s): float
    {
        $s = str_replace('.', '', $s);       // 25.000 -> 25000
        $s = str_replace(',', '.', $s);      // 25,5 -> 25.5
        return round(max(0, (float) $s), 2);
    }

    /**
     * Tentukan jenis transaksi dari kata kunci.
     * @return 'income'|'expense'|null
     */
    private static function detectType(string $text): ?string
    {
        // Kata kunci multi-kata ("transfer masuk") harus dicek lebih dulu
        // supaya tidak kalah oleh kata kunci tunggal yang lebih pendek.
        foreach (self::INCOME_KEYWORDS as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '/u', $text)) {
                return 'income';
            }
        }
        foreach (self::EXPENSE_KEYWORDS as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '/u', $text)) {
                return 'expense';
            }
        }
        return null;
    }

    /**
     * Kata perintah yang menandakan user ingin MENCATAT transaksi,
     * dipakai saat jenis (income/expense) belum terdeteksi.
     */
    private const RECORD_INTENT = [
        'catat', 'catatkan', 'input', 'tambah', 'rekam', 'jangan lupa catat',
    ];

    private static function hasRecordIntent(string $text): bool
    {
        foreach (self::RECORD_INTENT as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '/u', $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tebak kategori dari kata kunci pada teks lengkap.
     * Pencocokan pakai batas kata (\b) supaya "ibu" tidak cocok dengan
     * "r-ibu" ("10 ribu") dan sejenisnya.
     */
    private static function detectCategory(string $text, string $type): string
    {
        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '/u', $text)) {
                    return $category;
                }
            }
        }
        return 'Lainnya';
    }

    /**
     * Buat judul dari sisa kalimat setelah nominal & kata kunci dibuang.
     * Semua penghapusan pakai batas kata (\b) supaya kata tidak rusak
     * (mis. "minuman" tidak menjadi "an" gara-gara kata kunci "minum").
     */
    private static function extractTitle(string $original, ?string $amountStr, string $type): string
    {
        $text = mb_strtolower(trim($original));

        if ($amountStr !== null) {
            $text = trim(str_replace($amountStr, '', $text));
        }

        $filler = [
            'tolong', 'catat', 'catatkan', 'ayo', 'aku', 'saya', 'gue', 'mau',
            'sudah', 'tadi', 'hari ini', 'hari', 'kemarin', 'kemarinnya',
            'uang', 'duit', 'dengan', 'sebesar', 'sebanyak', 'sekitar', 'kurang lebih',
            'rp', 'untuk', 'buat', 'nih', 'deh', 'ya', 'dong', 'lah', 'ke', 'di',
            'input', 'tambah', 'rekam',
        ];
        $words = array_merge($filler, self::INCOME_KEYWORDS, self::EXPENSE_KEYWORDS);
        // Urutkan dari yang terpanjang dulu supaya frasa multi-kata
        // ("transfer masuk", "kurang lebih") terhapus utuh sebelum kata tunggalnya.
        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($words as $word) {
            $text = trim(preg_replace('/\b' . preg_quote($word, '/') . '\b/u', ' ', $text));
        }

        $title = trim(preg_replace('/\s+/', ' ', $text));
        $title = ucfirst($title);

        return $title;
    }
}
