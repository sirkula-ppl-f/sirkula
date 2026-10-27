<?php

namespace Tests\Feature\MasterData;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\CategoryListingType;
use App\Models\Material;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_kategori_dan_material_contoh_yang_aktif(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, Material::count());
        $this->assertSame(0, Category::where('is_active', false)->count());
        $this->assertSame(0, Material::where('is_active', false)->count());
        $this->assertSame(0, Category::doesntHave('categoryListingTypes')->count(), 'Setiap kategori harus punya tipe listing.');

        $this->assertDatabaseHas('materials', ['slug' => 'plastik-pet', 'name' => 'Plastik PET']);
        $this->assertSame(
            [ListingType::Preloved, ListingType::Upcycle],
            Category::where('slug', 'pakaian-dan-aksesori')->sole()->listingTypes()->all()
        );
    }

    public function test_setiap_tipe_listing_punya_kategori_aktif(): void
    {
        $this->seed(MasterDataSeeder::class);

        foreach (ListingType::cases() as $type) {
            $this->assertTrue(
                Category::active()->forListingType($type)->exists(),
                "Tipe {$type->value} harus punya minimal satu kategori aktif."
            );
        }
    }

    public function test_aman_dijalankan_berulang_kali(): void
    {
        $hitung = fn () => [Category::count(), CategoryListingType::count(), Material::count()];

        $this->seed(MasterDataSeeder::class);
        $jumlahAwal = $hitung();

        $this->seed(MasterDataSeeder::class);

        $this->assertSame($jumlahAwal, $hitung());
    }

    public function test_perubahan_admin_tidak_tertimpa_saat_dijalankan_ulang(): void
    {
        $this->seed(MasterDataSeeder::class);

        $category = Category::where('slug', 'elektronik-dan-gawai')->sole();
        $category->update(['name' => 'Elektronik', 'description' => 'Diubah admin.', 'is_active' => false]);
        $material = Material::where('slug', 'plastik-pet')->sole();
        $material->update(['name' => 'Botol PET', 'is_active' => false]);

        $this->seed(MasterDataSeeder::class);

        $category->refresh();
        $material->refresh();
        $this->assertSame('Elektronik', $category->name);
        $this->assertSame('Diubah admin.', $category->description);
        $this->assertFalse($category->is_active);
        $this->assertSame('Botol PET', $material->name);
        $this->assertFalse($material->is_active);
    }

    public function test_tipe_listing_yang_dihapus_admin_tidak_dipasang_ulang(): void
    {
        $this->seed(MasterDataSeeder::class);

        $category = Category::where('slug', 'pakaian-dan-aksesori')->sole();
        $category->categoryListingTypes()->where('listing_type', ListingType::Upcycle->value)->delete();

        $this->seed(MasterDataSeeder::class);

        $this->assertSame([ListingType::Preloved], $category->fresh()->listingTypes()->all());
    }

    public function test_dipanggil_oleh_database_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('categories', ['slug' => 'pakaian-dan-aksesori']);
        $this->assertDatabaseHas('materials', ['slug' => 'plastik-pet']);
    }
}
