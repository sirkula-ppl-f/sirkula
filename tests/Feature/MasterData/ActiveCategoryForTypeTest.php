<?php

namespace Tests\Feature\MasterData;

use App\Enums\ListingType;
use App\Models\Category;
use App\Rules\ActiveCategoryForType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveCategoryForTypeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Aturan seperti yang dipakai FormRequest listing.
     *
     * @return list<string>
     */
    private function validasi(mixed $nilai, ListingType $type = ListingType::Preloved): array
    {
        $validator = Validator::make(
            ['category_id' => $nilai],
            ['category_id' => ['required', 'integer', new ActiveCategoryForType($type)]],
        );

        return $validator->errors()->get('category_id');
    }

    public function test_menerima_kategori_aktif_yang_berlaku_untuk_tipe(): void
    {
        $category = Category::factory()->forTypes(ListingType::Preloved, ListingType::Upcycle)->create();

        $this->assertSame([], $this->validasi($category->id));
        $this->assertSame([], $this->validasi((string) $category->id, ListingType::Upcycle));
    }

    public function test_menolak_id_yang_tidak_ada(): void
    {
        $this->assertSame(['Kategori yang dipilih tidak ditemukan.'], $this->validasi(999));
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
        Category::factory()->create();

        $validator = Validator::make(
            ['category_id' => $nilai],
            ['category_id' => [new ActiveCategoryForType(ListingType::Preloved)]],
        );

        $this->assertSame(['Kategori yang dipilih tidak ditemukan.'], $validator->errors()->get('category_id'));
    }

    public function test_menolak_kategori_nonaktif(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->assertSame(
            ['Kategori yang dipilih sudah nonaktif dan tidak dapat dipakai untuk listing baru.'],
            $this->validasi($category->id)
        );
    }

    public function test_menolak_kategori_yang_tidak_berlaku_untuk_tipe(): void
    {
        $category = Category::factory()->forTypes(ListingType::Recyclable)->create();

        $this->assertSame(
            ['Kategori yang dipilih tidak berlaku untuk Barang Bekas.'],
            $this->validasi($category->id, ListingType::Preloved)
        );
        $this->assertSame([], $this->validasi($category->id, ListingType::Recyclable));
    }
}
