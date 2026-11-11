<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Trait yang mengosongkan atau membuat ulang tabel database.
     */
    private const TRAIT_DATABASE = [
        RefreshDatabase::class,
        LazilyRefreshDatabase::class,
        DatabaseMigrations::class,
        DatabaseTruncation::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak butuh aset hasil build Vite (public/build).
        $this->withoutVite();
    }

    /**
     * Pastikan DB tes aktif sebelum trait database berjalan (pola sama dengan DuskTestCase).
     *
     * Pengecekan tidak bisa ditaruh setelah parent::setUp(), karena migrate:fresh atau
     * truncation sudah berjalan di dalamnya.
     */
    protected function setUpTraits()
    {
        $this->pastikanDatabaseTesAman();

        return parent::setUpTraits();
    }

    /**
     * Hentikan tes yang mengosongkan database bila DB aktif bukan DB tes.
     *
     * Bila config ter-cache (php artisan config:cache), phpunit.xml diabaikan dan tes akan
     * memakai DB di .env, termasuk DB online bersama tim. Pengaman ini mencegah data itu terhapus.
     *
     * @param  class-string|null  $kelas  kelas tes yang diperiksa, bawaan kelas tes ini
     */
    protected function pastikanDatabaseTesAman(?string $kelas = null): void
    {
        $traitDipakai = class_uses_recursive($kelas ?? static::class);

        if (array_intersect(self::TRAIT_DATABASE, $traitDipakai) === []) {
            return;
        }

        $nama = (string) config('database.connections.'.config('database.default').'.database');

        if ($nama === ':memory:' || str_ends_with($nama, '_test') || str_ends_with($nama, '_testing')) {
            return;
        }

        throw new RuntimeException(
            "Tes dibatalkan: DB aktif '{$nama}' bukan DB tes, jadi datanya bisa terhapus. "
            .'Jalankan php artisan config:clear lalu ulangi php artisan test (phpunit.xml memakai SQLite :memory:).'
        );
    }
}
