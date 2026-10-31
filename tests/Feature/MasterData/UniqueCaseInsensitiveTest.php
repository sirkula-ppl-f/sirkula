<?php

namespace Tests\Feature\MasterData;

use App\Models\Category;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueCaseInsensitiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function validasi(mixed $nilai, ?int $ignoreId = null): array
    {
        $validator = Validator::make(
            ['name' => $nilai],
            ['name' => [new UniqueCaseInsensitive('categories', 'name', $ignoreId)]],
        );

        return $validator->errors()->get('name');
    }

    public function test_menerima_nama_yang_belum_ada(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);

        $this->assertSame([], $this->validasi('Elektronika'));
    }

    public function test_menolak_nama_yang_sama_tanpa_membedakan_huruf_kapital(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);

        foreach (['Elektronik', 'elektronik', 'ELEKTRONIK', 'eLeKtRoNiK'] as $nama) {
            $this->assertSame(['Nama sudah digunakan.'], $this->validasi($nama), "Nama {$nama} seharusnya ditolak.");
        }
    }

    public function test_mengabaikan_baris_dengan_id_sendiri(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);
        Category::factory()->create(['name' => 'Buku']);

        $this->assertSame([], $this->validasi('ELEKTRONIK', $category->id));
        $this->assertSame(['Nama sudah digunakan.'], $this->validasi('buku', $category->id));
    }

    public function test_nilai_bukan_teks_diserahkan_ke_rule_lain(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);

        $this->assertSame([], $this->validasi(['Elektronik']));
    }
}
