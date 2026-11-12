<?php

namespace Tests\Unit\Models;

use App\Enums\ReferencePriceStatus;
use App\Exceptions\ReferencePriceImmutableException;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ReferencePriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 12.00 WIB, 11 November 2026.
        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));
    }

    private function provinsi(string $kode, string $nama = 'Provinsi Contoh'): Region
    {
        return Region::create(['name' => $nama.' '.$kode, 'code' => $kode]);
    }

    public function test_kolom_tabel_sesuai_kontrak_tanpa_updated_at(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'material_id', 'region_id', 'price_per_kg', 'effective_date', 'source', 'published_by', 'created_at'],
            Schema::getColumnListing('reference_prices')
        );
    }

    public function test_tanggal_berlaku_disimpan_sebagai_tanggal_saja_dan_dibaca_dalam_wib(): void
    {
        $harga = ReferencePrice::factory()->effectiveOn('2026-11-12')->create();

        $this->assertSame('2026-11-12', DB::table('reference_prices')->where('id', $harga->id)->value('effective_date'));

        $tanggal = $harga->fresh()->effective_date;
        $this->assertInstanceOf(CarbonImmutable::class, $tanggal);
        $this->assertSame('2026-11-12 00:00:00', $tanggal->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', $tanggal->getTimezone()->getName());
    }

    public function test_tanggal_berlaku_dari_objek_tanggal_memakai_tanggal_di_zona_objek_itu(): void
    {
        // 00.30 WIB tanggal 12 = 17.30 UTC tanggal 11.
        $harga = ReferencePrice::factory()->create([
            'effective_date' => CarbonImmutable::parse('2026-11-12 00:30:00', 'Asia/Jakarta'),
        ]);

        $this->assertSame('2026-11-12', DB::table('reference_prices')->where('id', $harga->id)->value('effective_date'));
    }

    public function test_waktu_terbit_terisi_otomatis_termasuk_lewat_query_builder(): void
    {
        $harga = ReferencePrice::factory()->create();
        $this->assertTrue($harga->fresh()->created_at->equalTo(now()));

        DB::table('reference_prices')->insert([
            'material_id' => $harga->material_id,
            'region_id' => $harga->region_id,
            'price_per_kg' => 1000,
            'effective_date' => '2026-11-01',
            'source' => 'Sumber uji',
            'published_by' => $harga->published_by,
        ]);

        $this->assertNotNull(DB::table('reference_prices')->latest('id')->value('created_at'));
    }

    public function test_mengubah_harga_lewat_model_ditolak_dan_data_tetap_utuh(): void
    {
        $harga = ReferencePrice::factory()->create(['price_per_kg' => 3500]);
        $sebelum = DB::table('reference_prices')->get();

        try {
            $harga->update(['price_per_kg' => 4000]);
            $this->fail('Perubahan harga acuan seharusnya ditolak.');
        } catch (ReferencePriceImmutableException $e) {
            $this->assertSame('Harga acuan yang sudah terbit tidak dapat diubah. Terbitkan entri baru untuk mengoreksinya.', $e->getMessage());
        }

        $this->assertEquals($sebelum, DB::table('reference_prices')->get());
    }

    public function test_save_setelah_mengubah_atribut_juga_ditolak(): void
    {
        $harga = ReferencePrice::factory()->create(['source' => 'Sumber awal']);

        $harga->source = 'Sumber diubah';

        $this->expectException(ReferencePriceImmutableException::class);

        try {
            $harga->save();
        } finally {
            $this->assertSame('Sumber awal', DB::table('reference_prices')->where('id', $harga->id)->value('source'));
        }
    }

    public function test_menghapus_harga_lewat_model_ditolak_dan_data_tetap_utuh(): void
    {
        $harga = ReferencePrice::factory()->create();
        $sebelum = DB::table('reference_prices')->get();

        try {
            $harga->delete();
            $this->fail('Penghapusan harga acuan seharusnya ditolak.');
        } catch (ReferencePriceImmutableException $e) {
            $this->assertSame('Harga acuan yang sudah terbit tidak dapat dihapus. Terbitkan entri baru untuk mengoreksinya.', $e->getMessage());
        }

        $this->assertEquals($sebelum, DB::table('reference_prices')->get());
        $this->assertTrue($harga->exists);
    }

    public function test_relasi_material_provinsi_dan_penerbit(): void
    {
        $material = Material::factory()->create();
        $provinsi = $this->provinsi('P1');
        $admin = User::factory()->admin()->create();

        $harga = ReferencePrice::factory()->forMaterial($material)->forRegion($provinsi)->publishedBy($admin)->create();

        $this->assertTrue($harga->material->is($material));
        $this->assertTrue($harga->region->is($provinsi));
        $this->assertTrue($harga->publisher->is($admin));
    }

    public function test_factory_scheduled_membuat_tanggal_setelah_hari_ini_wib(): void
    {
        $harga = ReferencePrice::factory()->scheduled(3)->create();

        $this->assertSame('2026-11-14', $harga->effective_date->toDateString());
    }

    public function test_scope_latest_per_pair_mengembalikan_satu_baris_terbaru_per_pasangan(): void
    {
        $plastik = Material::factory()->create();
        $kertas = Material::factory()->create();
        $a = $this->provinsi('PA');
        $b = $this->provinsi('PB');

        $buat = fn (Material $material, Region $provinsi, string $tanggal) => ReferencePrice::factory()
            ->forMaterial($material)->forRegion($provinsi)->effectiveOn($tanggal)->create();

        $buat($plastik, $a, '2026-09-01');
        $plastikA = $buat($plastik, $a, '2026-10-01');
        $buat($plastik, $a, '2026-11-20'); // terjadwal
        $buat($plastik, $b, '2026-11-11');
        $plastikB = $buat($plastik, $b, '2026-11-11'); // tanggal sama, id lebih besar
        $kertasA = $buat($kertas, $a, '2025-01-15');
        $buat($kertas, $b, '2026-12-01'); // hanya terjadwal → tidak ada baris

        $this->assertEqualsCanonicalizing(
            [$plastikA->id, $plastikB->id, $kertasA->id],
            ReferencePrice::latestPerPair('2026-11-11')->pluck('id')->all()
        );
    }

    public function test_status_dihitung_dari_current_id_dan_tanggal_hari_ini(): void
    {
        $material = Material::factory()->create();
        $provinsi = $this->provinsi('PS');
        $buat = fn (string $tanggal) => ReferencePrice::factory()->forMaterial($material)->forRegion($provinsi)->effectiveOn($tanggal)->create();

        $riwayat = $buat('2026-10-01');
        $berlaku = $buat('2026-11-11');
        $terjadwal = $buat('2026-11-12');
        $hariIni = CarbonImmutable::parse('2026-11-11', 'Asia/Jakarta');

        $hasil = ReferencePrice::withCurrentId('2026-11-11')->get()->keyBy('id');

        $this->assertSame(ReferencePriceStatus::History, $hasil[$riwayat->id]->statusOn($hariIni));
        $this->assertSame(ReferencePriceStatus::Current, $hasil[$berlaku->id]->statusOn($hariIni));
        $this->assertSame(ReferencePriceStatus::Scheduled, $hasil[$terjadwal->id]->statusOn($hariIni));
        $this->assertSame('Berlaku', ReferencePriceStatus::Current->label());
        $this->assertSame('Terjadwal', ReferencePriceStatus::Scheduled->label());
        $this->assertSame('Riwayat', ReferencePriceStatus::History->label());
    }

    public function test_status_berlaku_atau_riwayat_butuh_scope_with_current_id(): void
    {
        $harga = ReferencePrice::factory()->effectiveOn('2026-11-01')->create();

        $this->expectException(LogicException::class);

        $harga->fresh()->statusOn(CarbonImmutable::parse('2026-11-11', 'Asia/Jakarta'));
    }

    public function test_scope_newest_first_mengurutkan_tanggal_lalu_id(): void
    {
        $lama = ReferencePrice::factory()->effectiveOn('2026-01-01')->create();
        $samaPertama = ReferencePrice::factory()->effectiveOn('2026-05-01')->create();
        $samaKedua = ReferencePrice::factory()->effectiveOn('2026-05-01')->create();

        $this->assertSame(
            [$samaKedua->id, $samaPertama->id, $lama->id],
            ReferencePrice::newestFirst()->pluck('id')->all()
        );
    }
}
