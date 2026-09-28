<?php

namespace Tests\Unit;

use App\Services\AmountFormatter;
use PHPUnit\Framework\TestCase;

class AmountFormatterTest extends TestCase
{
    public function test_format_rupiah_penuh(): void
    {
        $this->assertSame('Rp 750.000', AmountFormatter::format(750000));
        $this->assertSame('Rp 12.345', AmountFormatter::format(12345));
        $this->assertSame('Rp 0', AmountFormatter::format(0));
    }

    public function test_compact_menampilkan_penuh_di_bawah_juta(): void
    {
        $this->assertSame('Rp 999.999', AmountFormatter::compact(999999));
        $this->assertSame('Rp 750.000', AmountFormatter::compact(750000));
        $this->assertSame('Rp 0', AmountFormatter::compact(0));
    }

    public function test_compact_dari_juta_ke_atas(): void
    {
        $this->assertSame('Rp 1,5 juta', AmountFormatter::compact(1500000));
        $this->assertSame('Rp 12,5 juta', AmountFormatter::compact(12500000));
        $this->assertSame('Rp 1,5 miliar', AmountFormatter::compact(1500000000));
        $this->assertSame('Rp 1,23 triliun', AmountFormatter::compact(1230000000000));
    }

    public function test_compact_membulatkan_tanpa_puluhan_saat_besar(): void
    {
        $this->assertSame('Rp 123 juta', AmountFormatter::compact(123456789));
        $this->assertSame('Rp 250 miliar', AmountFormatter::compact(250000000000));
    }

    public function test_compact_merapikan_desimal_trail_zero(): void
    {
        $this->assertSame('Rp 1 juta', AmountFormatter::compact(1000000));
        $this->assertSame('Rp 2,5 juta', AmountFormatter::compact(2500000));
    }
}
