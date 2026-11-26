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
        $this->assertFalse($preview['items'][0]['price_changed']);
        $this->assertSame(64, strlen($preview['preview_hash']));
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

        $service = app(CheckoutService::class);
        $preview = $service->preview($buyer, $seller->id);

        $order = $service->checkout(
            $buyer,
            $seller->id,
            'checkout-test-001',
            $preview['preview_hash']
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
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $service = app(CheckoutService::class);
        $preview = $service->preview($buyer, $seller->id);

        // Simulasi stok berubah setelah buyer membuka preview.
        $listing->update(['stock' => 1]);

        try {
            $service->checkout(
                $buyer,
                $seller->id,
                'checkout-test-002',
                $preview['preview_hash']
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
        $preview = $service->preview($buyer, $seller->id);

        $firstOrder = $service->checkout(
            $buyer,
            $seller->id,
            $checkoutKey,
            $preview['preview_hash']
        );

        $secondOrder = $service->checkout(
            $buyer,
            $seller->id,
            $checkoutKey,
            $preview['preview_hash']
        );

        $this->assertSame($firstOrder->id, $secondOrder->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 8,
        ]);
    }

    public function test_checkout_rejects_stale_preview_when_price_changes(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 90000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $service = app(CheckoutService::class);
        $preview = $service->preview($buyer, $seller->id);

        $this->assertTrue($preview['items'][0]['price_changed']);
        $this->assertSame(25000, $preview['items'][0]['price_seen']);
        $this->assertSame(90000, $preview['items'][0]['unit_price']);

        // Harga berubah lagi setelah preview dibuka.
        $listing->update(['price' => 95000]);

        try {
            $service->checkout(
                $buyer,
                $seller->id,
                'checkout-price-change-001',
                $preview['preview_hash']
            );

            $this->fail('Checkout seharusnya meminta buyer meninjau ulang harga.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout', $exception->errors());
        }

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'stock' => 10,
            'price' => 95000,
        ]);

        // Buyer membuka preview terbaru dan mengonfirmasi harga baru.
        $freshPreview = $service->preview($buyer, $seller->id);

        $order = $service->checkout(
            $buyer,
            $seller->id,
            'checkout-price-change-001',
            $freshPreview['preview_hash']
        );

        $this->assertSame(190000, $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'unit_price' => 95000,
            'quantity' => 2,
            'subtotal' => 190000,
        ]);
    }

    public function test_checkout_rejects_same_key_with_different_cart_contents(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $firstListing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $firstListing->id,
            'quantity' => 2,
            'price_seen' => 25000,
        ]);

        $service = app(CheckoutService::class);
        $checkoutKey = 'checkout-different-cart-001';
        $firstPreview = $service->preview($buyer, $seller->id);

        $service->checkout(
            $buyer,
            $seller->id,
            $checkoutKey,
            $firstPreview['preview_hash']
        );

        // Isi keranjang berubah setelah order pertama dibuat.
        $secondListing = Listing::factory()->create([
            'user_id' => $seller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 40000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $secondListing->id,
            'quantity' => 1,
            'price_seen' => 40000,
        ]);

        $secondPreview = $service->preview($buyer, $seller->id);

        try {
            $service->checkout(
                $buyer,
                $seller->id,
                $checkoutKey,
                $secondPreview['preview_hash']
            );

            $this->fail('Checkout seharusnya menolak key yang digunakan untuk isi keranjang berbeda.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout', $exception->errors());
        }

        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'listing_id' => $secondListing->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $secondListing->id,
            'stock' => 10,
        ]);
    }

    public function test_checkout_rejects_same_key_with_different_seller(): void
    {
        $buyer = User::factory()->create();
        $firstSeller = User::factory()->create();
        $secondSeller = User::factory()->create();

        $firstListing = Listing::factory()->create([
            'user_id' => $firstSeller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 25000,
            'stock' => 10,
        ]);

        $secondListing = Listing::factory()->create([
            'user_id' => $secondSeller->id,
            'listing_type' => ListingType::Preloved->value,
            'price' => 40000,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $firstListing->id,
            'quantity' => 1,
            'price_seen' => 25000,
        ]);

        CartItem::query()->create([
            'user_id' => $buyer->id,
            'listing_id' => $secondListing->id,
            'quantity' => 1,
            'price_seen' => 40000,
        ]);

        $service = app(CheckoutService::class);
        $checkoutKey = 'checkout-different-seller-001';

        $firstPreview = $service->preview($buyer, $firstSeller->id);

        $service->checkout(
            $buyer,
            $firstSeller->id,
            $checkoutKey,
            $firstPreview['preview_hash']
        );

        $secondPreview = $service->preview($buyer, $secondSeller->id);

        try {
            $service->checkout(
                $buyer,
                $secondSeller->id,
                $checkoutKey,
                $secondPreview['preview_hash']
            );

            $this->fail('Checkout seharusnya menolak key yang digunakan untuk penjual berbeda.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout', $exception->errors());
        }

        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'listing_id' => $secondListing->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $secondListing->id,
            'stock' => 10,
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
            ->assertSee('Barang Bekas')
            ->assertSee('Konfirmasi & Buat Pesanan')
            ->assertDontSee('PRELOVED');
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

        $preview = app(CheckoutService::class)->preview($buyer, $seller->id);

        $response = $this->actingAs($buyer)
            ->post(route('checkout.store', ['seller' => $seller->id]), [
                'checkout_key' => 'http-checkout-test-001',
                'preview_hash' => $preview['preview_hash'],
            ]);

        $order = Order::query()
            ->where('buyer_id', $buyer->id)
            ->where('seller_id', $seller->id)
            ->firstOrFail();

        $response
            ->assertRedirect(route('payments.show', $order))
            ->assertSessionHas('status');

        $this->assertSame(50000, $order->total);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);

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
