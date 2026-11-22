<?php

namespace Tests\Unit\Models;

use App\Enums\ListingType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_dan_order_item_tersimpan_dengan_status_dan_cast_yang_benar(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 75_000,
        ]);

        $item = OrderItem::factory()->recyclable(5.00)->create([
            'order_id' => $order->id,
            'unit_price' => 25_000,
            'quantity' => 3,
            'subtotal' => 75_000,
        ]);

        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertTrue($order->isPaid());
        $this->assertTrue($order->buyer->is($buyer));
        $this->assertTrue($order->seller->is($seller));
        $this->assertSame(ListingType::Recyclable, $item->type_snapshot);
        $this->assertSame(15.0, $item->estimatedTotalWeightKg());
    }

    public function test_berat_tidak_diketahui_mengembalikan_null_bukan_nol(): void
    {
        $item = OrderItem::factory()->preloved()->withoutWeight()->create([
            'quantity' => 2,
        ]);

        $this->assertNull($item->weight_per_unit_snapshot);
        $this->assertNull($item->estimatedTotalWeightKg());
    }

    public function test_scope_paid_dan_for_participant_menyaring_pesanan_dengan_tepat(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();
        $orangLain = User::factory()->create();

        $paidAsBuyer = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
        ]);

        $paidAsSeller = Order::factory()->paid()->create([
            'buyer_id' => $rekan->id,
            'seller_id' => $warga->id,
        ]);

        // Pesanan belum lunas milik warga (tidak boleh masuk scope paid)
        Order::factory()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        // Pesanan lunas milik orang lain (tidak boleh masuk scope forParticipant warga)
        Order::factory()->paid()->create([
            'buyer_id' => $rekan->id,
            'seller_id' => $orangLain->id,
        ]);

        $hasil = Order::query()
            ->paid()
            ->forParticipant($warga)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$paidAsBuyer->id, $paidAsSeller->id], $hasil);
    }
}
