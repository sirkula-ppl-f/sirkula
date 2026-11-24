<?php

namespace Tests\Feature\Checkout;

use App\Enums\ListingType;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_preview_checkout(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $preview = app(CheckoutService::class)->preview($buyer, $seller->id);

        $this->assertSame(50000, $preview['total']);
        $this->assertCount(1, $preview['items']);
        $this->assertSame(25000, $preview['items'][0]['unit_price']);
        $this->assertSame(50000, $preview['items'][0]['subtotal']);
    }

    public function test_checkout_creates_order_and_reserves_stock(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 20000,
        ]);

        $order = app(CheckoutService::class)->checkout(
            $buyer,
            $seller->id,
            'checkout-test-001'
        );

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(50000, $order->total);
        $this->assertCount(1, $order->items);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'title_snapshot' => $listing->title,
            'type_snapshot' => ListingType::Preloved->value,
            'unit_price' => 25000,
            'quantity' => 2,
            'subtotal' => 50000,
            'weight_per_unit_snapshot' => null,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 1,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        try {
            app(CheckoutService::class)->checkout(
                $buyer,
                $seller->id,
                'checkout-test-002'
            );

            $this->fail('Checkout seharusnya ditolak karena stok tidak cukup.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $this->assertDatabaseMissing('orders', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 1,
        ]);
    }

    public function test_checkout_with_same_key_does_not_create_duplicate_order(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $service = app(CheckoutService::class);
        $checkoutKey = 'checkout-idempotency-test-001';

        $firstOrder = $service->checkout($buyer, $seller->id, $checkoutKey);
        $secondOrder = $service->checkout($buyer, $seller->id, $checkoutKey);

        $this->assertSame($firstOrder->id, $secondOrder->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 8,
        ]);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_checkout(): void
    {
        $seller = User::factory()->create();

        $this->get(route('checkout.create', ['seller' => $seller->id]))
            ->assertRedirect(route('login'));
    }

    public function test_buyer_bisa_membuka_halaman_checkout(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['name' => 'Penjual Checkout']);

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'title' => 'Meja Belajar',
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['seller' => $seller->id]))
            ->assertOk()
            ->assertSee('Penjual Checkout')
            ->assertSee('Meja Belajar')
            ->assertSee('Rp50.000')
            ->assertSee('Buat Pesanan');
    }

    public function test_buyer_bisa_submit_checkout_melalui_http(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', ['seller' => $seller->id]), [
                'checkout_key' => 'http-checkout-test-001',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'total' => 50000,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_buyer_tidak_bisa_checkout_keranjang_milik_user_lain(): void
    {
        $pemilik = User::factory()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $pemilik->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $this->actingAs($buyer)
            ->from(route('cart.index'))
            ->get(route('checkout.create', ['seller' => $seller->id]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $pemilik->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
        ]);
    }
}
