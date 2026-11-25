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

class AdminMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminMonitoringService::class);
    }

    public function test_hanya_menghitung_pesanan_lunas_dan_mengabaikan_status_non_paid(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $paidOrder = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 75_000,
        ]);
        OrderItem::factory()->recyclable(5.00)->create([
            'order_id' => $paidOrder->id,
            'quantity' => 3,
            'unit_price' => 25_000,
            'subtotal' => 75_000,
        ]);

        // Status belum lunas / gagal / dibatalkan / PAYMENT_REVIEW wajib diabaikan
        foreach (['pending', 'paymentReview', 'cancelled', 'expired'] as $state) {
            $nonPaid = $state === 'pending'
                ? Order::factory()->create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'total' => 100_000])
                : Order::factory()->{$state}()->create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'total' => 100_000]);

            OrderItem::factory()->recyclable(10.00)->create([
                'order_id' => $nonPaid->id,
                'quantity' => 2,
                'subtotal' => 100_000,
            ]);
        }

        $data = $this->service->getDashboardData();

        $this->assertSame(1, $data['kpis']['total_paid_orders']);
        $this->assertSame(3, $data['kpis']['total_items_quantity']);
        $this->assertSame(75_000, $data['kpis']['total_amount']);
        $this->assertSame(15.0, $data['kpis']['known_weight_kg']);
        $this->assertSame(1, $data['by_type'][ListingType::Recyclable->value]['orders_count']);
    }

    public function test_satu_pesanan_dengan_banyak_item_tidak_dihitung_ganda(): void
    {
        $region = Region::create(['name' => 'Jawa Barat', 'code' => '32']);
        $hub = Hub::create([
            'name' => 'Bank Sampah Dago',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Dago No. 10',
            'status' => 'ACTIVE',
        ]);

        $seller = User::factory()->create(['region_id' => $region->id]);
        $buyer = User::factory()->create();

        $listing1 = Listing::factory()->create([
            'user_id' => $seller->id,
            'region_id' => $region->id,
            'hub_id' => $hub->id,
            'listing_type' => ListingType::Preloved->value,
        ]);
        $listing2 = Listing::factory()->create([
            'user_id' => $seller->id,
            'region_id' => $region->id,
            'hub_id' => $hub->id,
            'listing_type' => ListingType::Upcycle->value,
        ]);

        $order = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 120_000,
        ]);

        // 2 item PRELOVED + 1 item UPCYCLE dalam 1 pesanan yang sama
        OrderItem::factory()->preloved(2.00)->create([
            'order_id' => $order->id,
            'listing_id' => $listing1->id,
            'quantity' => 1,
            'unit_price' => 50_000,
            'subtotal' => 50_000,
        ]);
        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $order->id,
            'listing_id' => $listing1->id,
            'quantity' => 2,
            'unit_price' => 20_000,
            'subtotal' => 40_000,
        ]);
        OrderItem::factory()->upcycle(0.50)->create([
            'order_id' => $order->id,
            'listing_id' => $listing2->id,
            'quantity' => 1,
            'unit_price' => 30_000,
            'subtotal' => 30_000,
        ]);

        $data = $this->service->getDashboardData();

        $this->assertSame(1, $data['kpis']['total_paid_orders']);
        $this->assertSame(4, $data['kpis']['total_items_quantity']);
        $this->assertSame(120_000, $data['kpis']['total_amount']);
        $this->assertSame(2.5, $data['kpis']['known_weight_kg']);
        $this->assertSame(2, $data['kpis']['items_with_weight_count']);
        $this->assertSame(1, $data['kpis']['items_without_weight_count']);

        // Per tipe: PRELOVED punya 2 baris item tetapi tetap 1 pesanan unik
        $this->assertSame(1, $data['by_type'][ListingType::Preloved->value]['orders_count']);
        $this->assertSame(3, $data['by_type'][ListingType::Preloved->value]['quantity_total']);
        $this->assertSame(90_000, $data['by_type'][ListingType::Preloved->value]['subtotal_amount']);

        // Per wilayah & per hub: tetap 1 pesanan unik
        $this->assertCount(1, $data['by_region']);
        $this->assertSame(1, $data['by_region'][0]['orders_count']);
        $this->assertSame(120_000, $data['by_region'][0]['subtotal_amount']);

        $this->assertCount(1, $data['by_hub']);
        $this->assertSame('Jawa Barat', $data['by_hub'][0]['region_name']);
        $this->assertSame('Bank Sampah Dago', $data['by_hub'][0]['hub_name']);
        $this->assertSame(1, $data['by_hub'][0]['orders_count']);
        $this->assertSame(120_000, $data['by_hub'][0]['subtotal_amount']);
    }

    public function test_berat_tidak_diketahui_bernilai_null_dan_tidak_dianggap_nol(): void
    {
        $order = Order::factory()->paid()->create(['total' => 40_000]);
        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'subtotal' => 40_000,
        ]);

        $data = $this->service->getDashboardData();

        $this->assertNull($data['kpis']['known_weight_kg']);
        $this->assertSame(0, $data['kpis']['items_with_weight_count']);
        $this->assertSame(1, $data['kpis']['items_without_weight_count']);
        $this->assertNull($data['by_type'][ListingType::Preloved->value]['known_weight_kg']);
        $this->assertNull($data['by_region'][0]['known_weight_kg']);
        $this->assertNull($data['by_hub'][0]['known_weight_kg']);
    }

    public function test_menghitung_listing_aktif_dan_pengguna_aktif_sesuai_aturan_bisnis(): void
    {
        $regionA = Region::create(['name' => 'Jawa Barat', 'code' => '32']);
        $regionB = Region::create(['name' => 'DKI Jakarta', 'code' => '31']);

        $hubAktif = Hub::create([
            'name' => 'Hub Aktif',
            'type' => 'BANK_SAMPAH',
            'region_id' => $regionA->id,
            'public_address' => 'Jl. Aktif',
            'status' => 'ACTIVE',
        ]);
        $hubArsip = Hub::create([
            'name' => 'Hub Arsip',
            'type' => 'BANK_SAMPAH',
            'region_id' => $regionA->id,
            'public_address' => 'Jl. Arsip',
            'status' => 'ARCHIVED',
        ]);

        $sellerAktifA = User::factory()->create(['region_id' => $regionA->id]);
        $sellerAktifB = User::factory()->create(['region_id' => $regionB->id]);
        $sellerSuspended = User::factory()->suspended()->create(['region_id' => $regionA->id]);

        // 2 listing aktif di Region A (hub aktif), 1 listing aktif di Region B
        Listing::factory()->count(2)->create([
            'user_id' => $sellerAktifA->id,
            'region_id' => $regionA->id,
            'hub_id' => $hubAktif->id,
            'stock' => 3,
        ]);
        Listing::factory()->create([
            'user_id' => $sellerAktifB->id,
            'region_id' => $regionB->id,
            'hub_id' => null,
            'stock' => 2,
        ]);

        // Listing tidak memenuhi syarat aktif (wajib tidak dihitung)
        Listing::factory()->stok(0)->create(['user_id' => $sellerAktifA->id, 'region_id' => $regionA->id, 'hub_id' => $hubAktif->id]);
        Listing::factory()->belumDisetujui()->create(['user_id' => $sellerAktifA->id, 'region_id' => $regionA->id, 'hub_id' => $hubAktif->id]);
        Listing::factory()->create(['user_id' => $sellerAktifA->id, 'region_id' => $regionA->id, 'hub_id' => $hubAktif->id, 'publish_status' => 'INACTIVE']);
        Listing::factory()->create(['user_id' => $sellerSuspended->id, 'region_id' => $regionA->id, 'hub_id' => $hubAktif->id]);
        Listing::factory()->create(['user_id' => $sellerAktifA->id, 'region_id' => $regionA->id, 'hub_id' => $hubArsip->id]);

        $all = $this->service->getDashboardData();
        $this->assertSame(3, $all['kpis']['active_listings_count']);
        $this->assertSame(2, $all['kpis']['active_users_count']);

        $filteredRegionA = $this->service->getDashboardData(['region_id' => $regionA->id]);
        $this->assertSame(2, $filteredRegionA['kpis']['active_listings_count']);
        $this->assertSame(1, $filteredRegionA['kpis']['active_users_count']);

        $filteredHub = $this->service->getDashboardData(['hub_id' => $hubAktif->id]);
        $this->assertSame(2, $filteredHub['kpis']['active_listings_count']);
    }

    public function test_filter_periode_wilayah_dan_hub_serta_grafik_bulanan_wib(): void
    {
        $nowWib = CarbonImmutable::create(2026, 11, 20, 10, 0, 0, AdminMonitoringService::TIMEZONE);
        $this->travelTo($nowWib);

        $jabar = Region::create(['name' => 'Jawa Barat', 'code' => '32']);
        $jateng = Region::create(['name' => 'Jawa Tengah', 'code' => '33']);

        $hubBandung = Hub::create([
            'name' => 'Hub RW 05 Dago',
            'type' => 'RW',
            'region_id' => $jabar->id,
            'public_address' => 'Bandung',
            'status' => 'ACTIVE',
        ]);
        $hubSemarang = Hub::create([
            'name' => 'Hub Bank Sampah Semarang',
            'type' => 'BANK_SAMPAH',
            'region_id' => $jateng->id,
            'public_address' => 'Semarang',
            'status' => 'ACTIVE',
        ]);

        $sellerJabar = User::factory()->create(['region_id' => $jabar->id]);
        $sellerJateng = User::factory()->create(['region_id' => $jateng->id]);
        $buyer = User::factory()->create();

        $listingBandung = Listing::factory()->create([
            'user_id' => $sellerJabar->id,
            'region_id' => $jabar->id,
            'hub_id' => $hubBandung->id,
        ]);
        $listingSemarang = Listing::factory()->create([
            'user_id' => $sellerJateng->id,
            'region_id' => $jateng->id,
            'hub_id' => $hubSemarang->id,
        ]);

        // Pesanan 1 Nov 2026 01:00 WIB (= 31 Okt 2026 18:00 UTC) di Bandung -> wajib masuk bulan Nov 2026
        $novOrder = Order::factory()->paid(CarbonImmutable::create(2026, 11, 1, 1, 0, 0, AdminMonitoringService::TIMEZONE)->utc())->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $sellerJabar->id,
            'total' => 50_000,
        ]);
        OrderItem::factory()->recyclable(5.00)->create([
            'order_id' => $novOrder->id,
            'listing_id' => $listingBandung->id,
            'quantity' => 2,
            'subtotal' => 50_000,
        ]);

        // Pesanan 2 bulan lalu (Sep 2026) di Semarang
        $sepOrder = Order::factory()->paid(CarbonImmutable::create(2026, 9, 15, 14, 0, 0, AdminMonitoringService::TIMEZONE)->utc())->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $sellerJateng->id,
            'total' => 80_000,
        ]);
        OrderItem::factory()->upcycle(1.50)->create([
            'order_id' => $sepOrder->id,
            'listing_id' => $listingSemarang->id,
            'quantity' => 1,
            'subtotal' => 80_000,
        ]);

        // Filter bulan ini -> hanya Nov (Bandung)
        $thisMonth = $this->service->getDashboardData(['periode' => AdminMonitoringService::PERIOD_THIS_MONTH]);
        $this->assertSame(1, $thisMonth['kpis']['total_paid_orders']);
        $this->assertSame(50_000, $thisMonth['kpis']['total_amount']);

        // Filter wilayah Jawa Tengah -> hanya Semarang
        $onlyJateng = $this->service->getDashboardData(['region_id' => $jateng->id]);
        $this->assertSame(1, $onlyJateng['kpis']['total_paid_orders']);
        $this->assertSame(80_000, $onlyJateng['kpis']['total_amount']);
        $this->assertCount(1, $onlyJateng['by_hub']);
        $this->assertSame('Hub Bank Sampah Semarang', $onlyJateng['by_hub'][0]['hub_name']);

        // Filter hub Bandung -> hanya Bandung
        $onlyBandung = $this->service->getDashboardData(['hub_id' => $hubBandung->id]);
        $this->assertSame(1, $onlyBandung['kpis']['total_paid_orders']);
        $this->assertSame(50_000, $onlyBandung['kpis']['total_amount']);

        // Grafik bulanan semua data: 6 bulan (Jun..Nov 2026)
        $all = $this->service->getDashboardData();
        $this->assertCount(6, $all['monthly_series']);
        $this->assertSame(
            ['2026-06', '2026-07', '2026-08', '2026-09', '2026-10', '2026-11'],
            array_column($all['monthly_series'], 'month_key')
        );

        $novBucket = collect($all['monthly_series'])->firstWhere('month_key', '2026-11');
        $this->assertSame(1, $novBucket['orders_count']);
        $this->assertSame(50_000, $novBucket['total_amount']);
        $this->assertSame(10.0, $novBucket['known_weight_kg']);

        $sepBucket = collect($all['monthly_series'])->firstWhere('month_key', '2026-09');
        $this->assertSame(1, $sepBucket['orders_count']);
        $this->assertSame(80_000, $sepBucket['total_amount']);
    }
}
