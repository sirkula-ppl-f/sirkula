<?php

namespace Tests\Unit\Support;

use App\Support\IndonesianFormat;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndonesianFormatTest extends TestCase
{
    public function test_rupiah_memakai_titik_ribuan_dan_spasi_setelah_rp(): void
    {
        $this->assertSame('Rp 0', IndonesianFormat::rupiah(0));
        $this->assertSame('Rp 950', IndonesianFormat::rupiah(950));
        $this->assertSame('Rp 3.500', IndonesianFormat::rupiah(3500));
        $this->assertSame('Rp 10.000.000', IndonesianFormat::rupiah(10_000_000));
    }

    public function test_harga_per_kg(): void
    {
        $this->assertSame('Rp 3.500/kg', IndonesianFormat::pricePerKg(3500));
    }

    public function test_tanggal_memakai_nama_bulan_indonesia_walau_locale_aplikasi_berbeda(): void
    {
        app()->setLocale('en');

        $this->assertSame('8 Nov 2026', IndonesianFormat::date(CarbonImmutable::parse('2026-11-08')));
        $this->assertSame('17 Agt 2026', IndonesianFormat::date(CarbonImmutable::parse('2026-08-17')));
        $this->assertSame('1 Mei 2026', IndonesianFormat::date(Carbon::parse('2026-05-01')));
        $this->assertSame('31 Des 2026', IndonesianFormat::date(CarbonImmutable::parse('2026-12-31')));
    }

    public function test_tanggal_tidak_dipindah_zona_waktu(): void
    {
        $this->assertSame('12 Nov 2026', IndonesianFormat::date(CarbonImmutable::parse('2026-11-12 00:00', 'Asia/Jakarta')));
    }

    public function test_waktu_terbit_dipindah_ke_wib(): void
    {
        $this->assertSame('11 Nov 2026, 12.00 WIB', IndonesianFormat::dateTimeWib(Carbon::parse('2026-11-11 05:00:00', 'UTC')));
        $this->assertSame('12 Nov 2026, 06.30 WIB', IndonesianFormat::dateTimeWib(Carbon::parse('2026-11-11 23:30:00', 'UTC')));
    }

    public function test_objek_asli_tidak_ikut_berubah(): void
    {
        $waktu = Carbon::parse('2026-11-11 23:30:00', 'UTC');

        IndonesianFormat::dateTimeWib($waktu);
        IndonesianFormat::date($waktu);

        $this->assertSame('UTC', $waktu->getTimezone()->getName());
        $this->assertSame('2026-11-11 23:30', $waktu->format('Y-m-d H:i'));
    }
}
