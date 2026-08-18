<?php

namespace Tests\Unit;

use App\Services\TransactionParser;
use PHPUnit\Framework\TestCase;

class TransactionParserTest extends TestCase
{
    public function test_detects_expense_with_thousand_word(): void
    {
        $item = TransactionParser::fromText('beli nasi goreng 25 ribu');

        $this->assertNotNull($item);
        $this->assertEquals(25000, $item['amount']);
        $this->assertSame('expense', $item['type']);
    }

    public function test_detects_income_gaji(): void
    {
        $item = TransactionParser::fromText('gaji masuk 5 juta');

        $this->assertNotNull($item);
        $this->assertEquals(5000000, $item['amount']);
        $this->assertSame('income', $item['type']);
    }

    public function test_detects_rp_formatted_amount(): void
    {
        $item = TransactionParser::fromText('bayar listrik Rp 200.000');

        $this->assertNotNull($item);
        $this->assertEquals(200000, $item['amount']);
        $this->assertSame('Tagihan & Utilitas', $item['category']);
    }

    public function test_detects_singkatan_rb(): void
    {
        $item = TransactionParser::fromText('jajan 15rb');

        $this->assertNotNull($item);
        $this->assertEquals(15000, $item['amount']);
    }

    public function test_detects_singkatan_k(): void
    {
        $item = TransactionParser::fromText('beli kopi 20k');

        $this->assertNotNull($item);
        $this->assertEquals(20000, $item['amount']);
        $this->assertSame('Makanan & Minuman', $item['category']);
    }

    public function test_detects_comma_juta(): void
    {
        $item = TransactionParser::fromText('terima transfer 1,5 juta');

        $this->assertNotNull($item);
        $this->assertEquals(1500000, $item['amount']);
        $this->assertSame('income', $item['type']);
    }

    public function test_detects_plain_amount(): void
    {
        $item = TransactionParser::fromText('beli bensin 50000');

        $this->assertNotNull($item);
        $this->assertEquals(50000, $item['amount']);
        $this->assertSame('Transportasi', $item['category']);
    }

    public function test_returns_null_without_amount(): void
    {
        $this->assertNull(TransactionParser::fromText('bagaimana kondisi keuangan saya?'));
    }

    public function test_returns_null_without_intent_keyword(): void
    {
        $this->assertNull(TransactionParser::fromText('uang 50 ribu saja'));
    }

    public function test_category_fallback_lainnya(): void
    {
        $item = TransactionParser::fromText('bayar hal aneh 10 ribu');

        $this->assertNotNull($item);
        $this->assertSame('Lainnya', $item['category']);
    }

    public function test_detects_transport_category(): void
    {
        $item = TransactionParser::fromText('isi bensin 100 ribu');

        $this->assertNotNull($item);
        $this->assertSame('Transportasi', $item['category']);
        $this->assertEquals(100000, $item['amount']);
    }

    public function test_ribu_does_not_match_keluarga_ibu(): void
    {
        // Regresi: kata kunci "ibu" tidak boleh cocok dengan "r-ibu" dalam "10 ribu"
        $item = TransactionParser::fromText('bayar hal aneh 10 ribu');

        $this->assertNotNull($item);
        $this->assertSame('Lainnya', $item['category']);
    }

    public function test_title_keeps_minuman_utuh(): void
    {
        // Regresi: kata kunci "minum" tidak boleh merusak kata "minuman"
        $item = TransactionParser::fromText('beli minuman 10 ribu');

        $this->assertNotNull($item);
        $this->assertStringContainsString('minuman', mb_strtolower($item['title']));
        $this->assertSame('Makanan & Minuman', $item['category']);
    }

    public function test_income_transfer_masuk(): void
    {
        $item = TransactionParser::fromText('transfer masuk 500 ribu');

        $this->assertNotNull($item);
        $this->assertSame('income', $item['type']);
        $this->assertEquals(500000, $item['amount']);
    }

    public function test_expense_kirim_uang(): void
    {
        $item = TransactionParser::fromText('kirim uang 100 ribu ke mama');

        $this->assertNotNull($item);
        $this->assertSame('expense', $item['type']);
    }

    public function test_spelled_number_voice_dua_puluh_lima_ribu(): void
    {
        // Voice input Chrome id-ID sering mengucapkan angka menjadi kata
        $item = TransactionParser::fromText('beli nasi goreng dua puluh lima ribu');

        $this->assertNotNull($item);
        $this->assertEquals(25000, $item['amount']);
        $this->assertSame('expense', $item['type']);
    }

    public function test_spelled_number_seratus_ribu(): void
    {
        $item = TransactionParser::fromText('bayar listrik seratus ribu');

        $this->assertNotNull($item);
        $this->assertEquals(100000, $item['amount']);
        $this->assertSame('Tagihan & Utilitas', $item['category']);
    }

    public function test_spelled_number_satu_juta_lima_ratus_ribu(): void
    {
        $item = TransactionParser::fromText('gaji masuk satu juta lima ratus ribu');

        $this->assertNotNull($item);
        $this->assertEquals(1500000, $item['amount']);
        $this->assertSame('income', $item['type']);
    }

    public function test_spelled_number_lima_belas_ribu(): void
    {
        $item = TransactionParser::fromText('jajan lima belas ribu');

        $this->assertNotNull($item);
        $this->assertEquals(15000, $item['amount']);
    }

    public function test_spelled_number_dua_ratus_lima_puluh(): void
    {
        $item = TransactionParser::fromText('beli buku dua ratus lima puluh ribu');

        $this->assertNotNull($item);
        $this->assertEquals(250000, $item['amount']);
    }

    public function test_pembelian_pulsa(): void
    {
        $item = TransactionParser::fromText('catat pembelian pulsa 100 ribu');

        $this->assertNotNull($item);
        $this->assertEquals(100000, $item['amount']);
        $this->assertSame('Tagihan & Utilitas', $item['category']);
    }

    public function test_pinjam_uang(): void
    {
        $item = TransactionParser::fromText('minjem duit 50 ribu ke adek');

        $this->assertNotNull($item);
        $this->assertEquals(50000, $item['amount']);
        $this->assertSame('expense', $item['type']);
    }

    public function test_spelled_unit_without_scale_is_ignored(): void
    {
        // "satu kopi" bukan nominal — jangan salah tangkap
        $this->assertNull(TransactionParser::fromText('beli satu kopi'));
    }

    public function test_spelled_seratus_ribu_ratusan(): void
    {
        $item = TransactionParser::fromText('belanja di indomaret tiga ratus ribu');

        $this->assertNotNull($item);
        $this->assertEquals(300000, $item['amount']);
        $this->assertSame('Belanja', $item['category']);
    }

    public function test_record_intent_without_type_keyword(): void
    {
        // "catat ..." tanpa kata beli/bayar = jelas niat mencatat → expense
        $item = TransactionParser::fromText('catat nasi goreng 25 ribu');

        $this->assertNotNull($item);
        $this->assertEquals(25000, $item['amount']);
        $this->assertSame('expense', $item['type']);
        $this->assertSame('Makanan & Minuman', $item['category']);
    }

    public function test_record_intent_input(): void
    {
        $item = TransactionParser::fromText('input gaji 3 juta');

        $this->assertNotNull($item);
        $this->assertEquals(3000000, $item['amount']);
        $this->assertSame('income', $item['type']);
        $this->assertSame('Gaji', $item['category']);
    }

    public function test_question_without_record_intent_still_null(): void
    {
        // "uang 50 ribu saja" bukan perintah catat → tetap null (jangan salah tangkap)
        $this->assertNull(TransactionParser::fromText('uang 50 ribu saja'));
    }
}
