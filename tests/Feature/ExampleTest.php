<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Ubah Barang Bekas & Material Daur Ulang Menjadi Nilai Baru')
            ->assertSee('Jelajahi Katalog')
            ->assertSee('Daftar Sebagai Warga')
            ->assertSee('Pratinjau Katalog Sirkula')
            ->assertSee('Contoh')
            ->assertSee('Sepeda Lipat Bekas')
            ->assertSee('Botol PET Terpilah (lot 25 kg)')
            ->assertSee('Tas dari Banner Bekas')
            ->assertSee('Hub Bank Sampah Melati')
            ->assertSee('Hub Komunitas RW 05')
            ->assertSee('Etalase Kriya Nusantara')
            ->assertSee('Hub Komunitas Lestari')
            ->assertSee(ListingType::Preloved->label())
            ->assertSee(ListingType::Recyclable->label())
            ->assertSee(ListingType::Upcycle->label())
            ->assertSee('href="'.url('/').'"', false);
    }

    public function test_pengguna_yang_login_melihat_tombol_buka_beranda_di_landing_page(): void
    {
        $user = User::factory()->create(['name' => 'Rina Warga']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Buka Beranda')
            ->assertDontSee('Daftar Sebagai Warga');
    }
}
