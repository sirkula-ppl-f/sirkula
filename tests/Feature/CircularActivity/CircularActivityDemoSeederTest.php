<?php

namespace Tests\Feature\CircularActivity;

use App\Models\Order;
use App\Models\User;
use App\Services\CircularActivityService;
use Database\Seeders\CircularActivityDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CircularActivityDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengisi_pesanan_demo_enam_bulan_dan_satu_pesanan_pending(): void
    {
        $this->seed(CircularActivityDemoSeeder::class);

        $warga = User::query()->where('email', CircularActivityDemoSeeder::DEMO_EMAIL)->firstOrFail();
        $report = app(CircularActivityService::class)->summarize($warga);

        // 6 pesanan PAID + 1 pesanan PENDING_PAYMENT (hanya 6 PAID yang dihitung)
        $this->assertDatabaseCount('orders', 7);
        $this->assertSame(6, $report['summary']['total_paid_orders']);
        $this->assertSame(13, $report['summary']['total_items_quantity']);
        $this->assertSame(2, $report['summary']['items_without_weight_count']);
        $this->assertNotNull($report['summary']['known_weight_kg']);
    }

    public function test_dijalankan_ulang_tidak_menggandakan_data(): void
    {
        $this->seed(CircularActivityDemoSeeder::class);
        $jumlahOrder = Order::query()->count();

        $this->seed(CircularActivityDemoSeeder::class);

        $this->assertDatabaseCount('orders', $jumlahOrder);
    }

    public function test_ditolak_bila_db_aktif_bukan_db_lokal(): void
    {
        $default = config('database.default');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '157.10.253.27',
        ]);

        try {
            (new CircularActivityDemoSeeder)->run();
            $this->fail('Seeder demo seharusnya ditolak bila terhubung ke DB bukan lokal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('hanya untuk DB lokal', $e->getMessage());
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tidak_didaftarkan_di_database_seeder(): void
    {
        $this->assertStringNotContainsString(
            'CircularActivityDemoSeeder',
            (string) file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }
}
