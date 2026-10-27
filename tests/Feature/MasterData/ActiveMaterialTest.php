<?php

namespace Tests\Feature\MasterData;

use App\Models\Material;
use App\Rules\ActiveMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveMaterialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function validasi(mixed $nilai): array
    {
        $validator = Validator::make(
            ['material_id' => $nilai],
            ['material_id' => [new ActiveMaterial]],
        );

        return $validator->errors()->get('material_id');
    }

    public function test_menerima_material_aktif(): void
    {
        $material = Material::factory()->create();

        $this->assertSame([], $this->validasi($material->id));
        $this->assertSame([], $this->validasi((string) $material->id));
    }

    public function test_menolak_id_yang_tidak_ada(): void
    {
        $this->assertSame(['Material yang dipilih tidak ditemukan.'], $this->validasi(999));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function bukanId(): array
    {
        return [
            'teks' => ['abc'],
            'pecahan' => ['1.5'],
            'negatif' => ['-1'],
            'array' => [[1]],
        ];
    }

    #[DataProvider('bukanId')]
    public function test_nilai_yang_bukan_id_dianggap_tidak_ditemukan(mixed $nilai): void
    {
        Material::factory()->create();

        $this->assertSame(['Material yang dipilih tidak ditemukan.'], $this->validasi($nilai));
    }

    public function test_menolak_material_nonaktif(): void
    {
        $material = Material::factory()->inactive()->create();

        $this->assertSame(
            ['Material yang dipilih sudah nonaktif dan tidak dapat dipakai untuk listing baru.'],
            $this->validasi($material->id)
        );
    }
}
