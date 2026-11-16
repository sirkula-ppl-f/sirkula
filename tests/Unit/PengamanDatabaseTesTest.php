<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Pengaman di Tests\TestCase: tes yang mengosongkan database hanya boleh berjalan di DB tes.
 *
 * Kelas ini sengaja tidak memakai trait database, jadi nama DB bisa diubah lewat config()
 * tanpa menyentuh database mana pun.
 */
class PengamanDatabaseTesTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function kelasDenganTraitDatabase(): array
    {
        return [
            'RefreshDatabase' => [get_class(new class
            {
                use RefreshDatabase;
            })],
            'LazilyRefreshDatabase' => [get_class(new class
            {
                use LazilyRefreshDatabase;
            })],
            'DatabaseMigrations' => [get_class(new class
            {
                use DatabaseMigrations;
            })],
            'DatabaseTruncation' => [get_class(new class
            {
                use DatabaseTruncation;
            })],
        ];
    }

    private function aturNamaDatabase(string $nama): void
    {
        config(['database.connections.'.config('database.default').'.database' => $nama]);
    }

    /**
     * @param  class-string  $kelas
     */
    #[DataProvider('kelasDenganTraitDatabase')]
    public function test_tes_dibatalkan_bila_db_aktif_bukan_db_tes(string $kelas): void
    {
        $this->aturNamaDatabase('sirkula');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Tes dibatalkan: DB aktif 'sirkula' bukan DB tes");
        $this->expectExceptionMessage('php artisan config:clear');

        $this->pastikanDatabaseTesAman($kelas);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function namaDatabaseTes(): array
    {
        return [
            'SQLite memory' => [':memory:'],
            'akhiran _test' => ['sirkula_test'],
            'akhiran _testing' => ['sirkula_testing'],
        ];
    }

    #[DataProvider('namaDatabaseTes')]
    public function test_tes_lolos_bila_db_aktif_adalah_db_tes(string $nama): void
    {
        $this->aturNamaDatabase($nama);

        $this->pastikanDatabaseTesAman(get_class(new class
        {
            use RefreshDatabase;
        }));

        $this->addToAssertionCount(1);
    }

    public function test_nama_yang_hanya_memuat_kata_test_tetap_ditolak(): void
    {
        $this->aturNamaDatabase('sirkula_test_lama');

        $this->expectException(RuntimeException::class);

        $this->pastikanDatabaseTesAman(get_class(new class
        {
            use RefreshDatabase;
        }));
    }

    public function test_kelas_tanpa_trait_database_tidak_diperiksa(): void
    {
        $this->aturNamaDatabase('sirkula');

        $this->pastikanDatabaseTesAman(self::class);

        $this->addToAssertionCount(1);
    }
}
