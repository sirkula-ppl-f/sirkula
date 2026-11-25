<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingType;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Region;
use App\Models\User;
use App\Services\AdminMonitoringService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_admin_aktif_yang_dapat_mengunduh_ekspor_csv(): void
    {
        $this->get(route('admin.monitoring.export'))
            ->assertRedirect(route('login'));

        $warga = User::factory()->create();
        $this->actingAs($warga)
            ->get(route('admin.monitoring.export'))
            ->assertForbidden();

        $suspendedAdmin = User::factory()->admin()->suspended()->create();
        $this->actingAs($suspendedAdmin)
            ->get(route('admin.monitoring.export'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_mengunduh_csv_berisi_agregat_akurat_tanpa_pii_dan_aman_dari_formula_injection(): void
    {
        $nowWib = CarbonImmutable::create(2026, 11, 25, 15, 0, 0, AdminMonitoringService::TIMEZONE);
        $this->travelTo($nowWib);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin Rahasia',
            'email' => 'admin.rahasia@sirkula.test',
        ]);

        $region = Region::create([
            'name' => '=Jawa Barat',
            'code' => '32',
        ]);

        $hub = Hub::create([
            'name' => '+Bank Sampah Dago',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Dago No. 10',
            'status' => 'ACTIVE',
        ]);

        $seller = User::factory()->create([
            'name' => 'Penjual Pribadi Rahasia',
            'email' => 'penjual.rahasia@sirkula.test',
            'region_id' => $region->id,
        ]);

        $buyer = User::factory()->create([
            'name' => 'Pembeli Pribadi Rahasia',
            'email' => 'pembeli.rahasia@sirkula.test',
        ]);

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'region_id' => $region->id,
            'hub_id' => $hub->id,
            'listing_type' => ListingType::Preloved->value,
        ]);

        // 1 pesanan PAID berisi 2 item: 1 dengan berat, 1 tanpa berat
        $paidOrder = Order::factory()->paid($nowWib->utc())->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 90_000,
        ]);

        OrderItem::factory()->recyclable(4.50)->create([
            'order_id' => $paidOrder->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'subtotal' => 50_000,
        ]);

        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $paidOrder->id,
            'listing_id' => $listing->id,
            'quantity' => 1,
            'subtotal' => 40_000,
        ]);

        // 1 pesanan PAYMENT_REVIEW (wajib diabaikan dari CSV)
        $reviewOrder = Order::factory()->paymentReview()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 500_000,
        ]);
        OrderItem::factory()->upcycle(3.00)->create([
            'order_id' => $reviewOrder->id,
            'listing_id' => $listing->id,
            'quantity' => 1,
            'subtotal' => 500_000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring.export', [
                'periode' => AdminMonitoringService::PERIOD_THIS_MONTH,
                'region_id' => $region->id,
                'hub_id' => $hub->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('x-content-type-options', 'nosniff');
        $response->assertDownload('laporan-monitoring-sirkula-2026-11-25.csv');

        $csv = $response->streamedContent();

        // Payload formula injection wajib dinetralkan dengan apostrof
        $this->assertStringContainsString("'=Jawa Barat", $csv);
        $this->assertStringContainsString("'+Bank Sampah Dago", $csv);

        // Nilai agregat lunas benar (1 pesanan, 90000, bukan 590000) & berat null ditulis "Tidak diketahui"
        $this->assertStringContainsString('Ringkasan,"Total Pesanan Lunas",1', $csv);
        $this->assertStringContainsString('Ringkasan,"Total Nilai Transaksi (Rp)",90000', $csv);
        $this->assertStringContainsString('Ringkasan,"Estimasi Berat Tercatat (kg)",9', $csv);
        $this->assertStringContainsString('"Per Tipe Listing","Barang Bekas",1,1,40000,"Tidak diketahui",1', $csv);
        $this->assertStringNotContainsString('500000', $csv);

        // Tidak boleh ada informasi pribadi (PII) pengguna di CSV
        $this->assertStringNotContainsString('penjual.rahasia@sirkula.test', $csv);
        $this->assertStringNotContainsString('pembeli.rahasia@sirkula.test', $csv);
        $this->assertStringNotContainsString('admin.rahasia@sirkula.test', $csv);
        $this->assertStringNotContainsString('Penjual Pribadi Rahasia', $csv);
        $this->assertStringNotContainsString('Pembeli Pribadi Rahasia', $csv);
        $this->assertStringNotContainsString('Admin Rahasia', $csv);
    }

    public function test_filter_tidak_valid_atau_array_pada_ekspor_csv_tetap_aman(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.monitoring.export', [
                'periode' => ['ngawur'],
                'region_id' => ['abc'],
                'hub_id' => '999999',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
