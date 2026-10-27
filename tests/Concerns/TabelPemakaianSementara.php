<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel listings (PBI-07/08) dan reference_prices (PBI-05) belum ada di develop. Trait ini
 * membuat versi minimal yang hanya berisi kolom yang dibaca data master.
 *
 * Tabel yang dibuat di sini di-drop lagi setelah test. Di SQLite tabel sudah ikut rollback
 * transaksi RefreshDatabase, tetapi di MySQL CREATE TABLE melakukan commit implisit.
 *
 * Setelah tabel asli masuk develop, ganti pemakaian trait ini dengan factory listing/harga acuan,
 * karena kolom wajib di tabel asli tidak diisi oleh insert di sini.
 */
trait TabelPemakaianSementara
{
    protected function buatTabelListings(bool $denganListingType = true): void
    {
        if (Schema::hasTable('listings')) {
            return;
        }

        $this->hapusSetelahTest('listings');

        Schema::create('listings', function (Blueprint $table) use ($denganListingType) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('material_id')->nullable();

            if ($denganListingType) {
                $table->string('listing_type', 20)->nullable();
            }
        });
    }

    protected function buatTabelReferencePrices(): void
    {
        if (Schema::hasTable('reference_prices')) {
            return;
        }

        $this->hapusSetelahTest('reference_prices');

        Schema::create('reference_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id');
        });
    }

    /**
     * Callback ditaruh paling depan supaya berjalan sebelum RefreshDatabase me-rollback
     * transaksi dan memutus koneksi database.
     */
    private function hapusSetelahTest(string $tabel): void
    {
        array_unshift($this->beforeApplicationDestroyedCallbacks, fn () => Schema::dropIfExists($tabel));
    }

    /**
     * @param  array<string, mixed>  $kolom
     */
    protected function tambahListing(array $kolom): void
    {
        $this->buatTabelListings();

        DB::table('listings')->insert($kolom);
    }

    protected function tambahHargaAcuan(int $materialId): void
    {
        $this->buatTabelReferencePrices();

        DB::table('reference_prices')->insert(['material_id' => $materialId]);
    }

    /**
     * Test "tanpa tabel" tidak relevan lagi setelah tabel aslinya dibuat oleh PBI lain.
     */
    protected function lewatiBilaTabelSudahAda(string $tabel): void
    {
        if (Schema::hasTable($tabel)) {
            $this->markTestSkipped("Tabel {$tabel} sudah ada, guard skema tidak lagi diuji di sini.");
        }
    }
}
