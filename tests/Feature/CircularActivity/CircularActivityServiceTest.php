<?php

namespace Tests\Feature\CircularActivity;

use App\Enums\ListingType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CircularActivityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircularActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private CircularActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CircularActivityService::class);
    }

    public function test_hanya_menghitung_pesanan_lunas_dan_mengisolasi_antar_warga(): void
    {
        $warga = User::factory()->create();
        $lawan = User::factory()->create();
        $orangLain = User::factory()->create();

        // Pesanan lunas milik warga (harus dihitung)
        $paidOrder = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $lawan->id,
            'total' => 50_000,
        ]);
        OrderItem::factory()->recyclable(5.00)->create([
            'order_id' => $paidOrder->id,
            'quantity' => 2,
            'unit_price' => 25_000,
            'subtotal' => 50_000,
        ]);

        // Pesanan belum lunas milik warga (wajib diabaikan)
        foreach (['factoryPending', 'paymentReview', 'cancelled', 'expired'] as $state) {
            $nonPaidOrder = $state === 'factoryPending'
                ? Order::factory()->create(['buyer_id' => $warga->id, 'seller_id' => $lawan->id, 'total' => 99_000])
                : Order::factory()->{$state}()->create(['buyer_id' => $warga->id, 'seller_id' => $lawan->id, 'total' => 99_000]);

            OrderItem::factory()->recyclable(10.00)->create([
                'order_id' => $nonPaidOrder->id,
                'quantity' => 1,
                'subtotal' => 99_000,
            ]);
        }

        // Pesanan lunas milik orang lain (wajib diabaikan)
        $otherOrder = Order::factory()->paid()->create([
            'buyer_id' => $orangLain->id,
            'seller_id' => $lawan->id,
            'total' => 120_000,
        ]);
        OrderItem::factory()->recyclable(8.00)->create([
            'order_id' => $otherOrder->id,
            'quantity' => 1,
            'subtotal' => 120_000,
        ]);

        $result = $this->service->summarize($warga);

        $this->assertSame(1, $result['summary']['total_paid_orders']);
        $this->assertSame(2, $result['summary']['total_items_quantity']);
        $this->assertSame(50_000, $result['summary']['total_amount']);
        $this->assertSame(10.0, $result['summary']['known_weight_kg']);
        $this->assertSame(1, $result['by_type'][ListingType::Recyclable->value]['orders_count']);
    }

    public function test_satu_pesanan_dengan_banyak_item_tidak_dihitung_ganda(): void
    {
        $warga = User::factory()->create();
        $pembeli = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'seller_id' => $warga->id,
            'buyer_id' => $pembeli->id,
            'total' => 95_000,
        ]);

        // Dua item berbeda dengan tipe sama (PRELOVED) di satu pesanan
        OrderItem::factory()->preloved(1.50)->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'unit_price' => 30_000,
            'subtotal' => 30_000,
        ]);
        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'unit_price' => 20_000,
            'subtotal' => 40_000,
        ]);

        // Item ketiga bertipe UPCYCLE di pesanan yang sama
        OrderItem::factory()->upcycle(0.50)->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'unit_price' => 25_000,
            'subtotal' => 25_000,
        ]);

        $result = $this->service->summarize($warga);

        $this->assertSame(1, $result['summary']['total_paid_orders']);
        $this->assertSame(4, $result['summary']['total_items_quantity']);
        $this->assertSame(95_000, $result['summary']['total_amount']);
        $this->assertSame(2.0, $result['summary']['known_weight_kg']);
        $this->assertSame(2, $result['summary']['items_with_weight_count']);
        $this->assertSame(1, $result['summary']['items_without_weight_count']);

        $preloved = $result['by_type'][ListingType::Preloved->value];
        $this->assertSame(1, $preloved['orders_count']);
        $this->assertSame(3, $preloved['quantity_total']);
        $this->assertSame(70_000, $preloved['subtotal_amount']);
        $this->assertSame(1.5, $preloved['known_weight_kg']);
        $this->assertSame(1, $preloved['items_without_weight_count']);
    }

    public function test_berat_tidak_diketahui_bernilai_null_dan_tidak_dianggap_nol(): void
    {
        $warga = User::factory()->create();
        $penjual = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $penjual->id,
            'total' => 45_000,
        ]);

        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'subtotal' => 45_000,
        ]);

        $result = $this->service->summarize($warga);

        $this->assertNull($result['summary']['known_weight_kg']);
        $this->assertSame(0, $result['summary']['items_with_weight_count']);
        $this->assertSame(1, $result['summary']['items_without_weight_count']);
        $this->assertNull($result['by_type'][ListingType::Preloved->value]['known_weight_kg']);
    }

    public function test_filter_periode_dan_peran_menyaring_data_sesuai_waktu_wib(): void
    {
        $nowWib = CarbonImmutable::create(2026, 11, 17, 10, 0, 0, CircularActivityService::TIMEZONE);
        $this->travelTo($nowWib);

        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        // Bulan ini sebagai penjual
        $thisMonthSellerOrder = Order::factory()->paid($nowWib->subDays(2)->utc())->create([
            'seller_id' => $warga->id,
            'buyer_id' => $rekan->id,
            'total' => 40_000,
        ]);
        OrderItem::factory()->recyclable(4.00)->create([
            'order_id' => $thisMonthSellerOrder->id,
            'quantity' => 1,
            'subtotal' => 40_000,
        ]);

        // 2 bulan lalu sebagai pembeli (masuk last_3_months dan this_year, tidak masuk this_month)
        $twoMonthsAgoBuyerOrder = Order::factory()->paid($nowWib->subMonths(2)->utc())->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'total' => 30_000,
        ]);
        OrderItem::factory()->upcycle(1.00)->create([
            'order_id' => $twoMonthsAgoBuyerOrder->id,
            'quantity' => 1,
            'subtotal' => 30_000,
        ]);

        // Tahun lalu (hanya masuk period 'all')
        $lastYearOrder = Order::factory()->paid($nowWib->subYear()->utc())->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'total' => 20_000,
        ]);
        OrderItem::factory()->preloved(2.00)->create([
            'order_id' => $lastYearOrder->id,
            'quantity' => 1,
            'subtotal' => 20_000,
        ]);

        $thisMonth = $this->service->summarize($warga, CircularActivityService::PERIOD_THIS_MONTH);
        $this->assertSame(1, $thisMonth['summary']['total_paid_orders']);
        $this->assertSame(40_000, $thisMonth['summary']['total_amount']);

        $last3Months = $this->service->summarize($warga, CircularActivityService::PERIOD_LAST_3_MONTHS);
        $this->assertSame(2, $last3Months['summary']['total_paid_orders']);
        $this->assertSame(70_000, $last3Months['summary']['total_amount']);

        $onlySeller = $this->service->summarize($warga, CircularActivityService::PERIOD_ALL, CircularActivityService::ROLE_SELLER);
        $this->assertSame(1, $onlySeller['summary']['total_paid_orders']);
        $this->assertSame(4.0, $onlySeller['summary']['known_weight_kg']);

        $onlyBuyer = $this->service->summarize($warga, CircularActivityService::PERIOD_ALL, CircularActivityService::ROLE_BUYER);
        $this->assertSame(2, $onlyBuyer['summary']['total_paid_orders']);
        $this->assertSame(3.0, $onlyBuyer['summary']['known_weight_kg']);
    }
}
