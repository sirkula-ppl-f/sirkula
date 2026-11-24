<?php

namespace App\Services;

use App\Enums\ListingType;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Menampilkan preview checkout untuk satu penjual.
     */
    public function preview(User $buyer, int $sellerId): array
    {
        $cartItems = $this->getSellerCart($buyer, $sellerId);

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang untuk penjual ini kosong.',
            ]);
        }

        $items = $cartItems->map(function (CartItem $cartItem) use ($buyer) {
            $listing = $cartItem->listing;

            $this->validateListing(
                $buyer,
                $listing,
                (int) $cartItem->quantity
            );

            $unitPrice = (int) $listing->price;
            $quantity = (int) $cartItem->quantity;

            return [
                'cart_item_id' => $cartItem->id,
                'listing_id' => $listing->id,
                'title' => $listing->title,
                'type' => $this->listingTypeValue($listing->listing_type),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ];
        });

        return [
            'seller' => $cartItems->first()->listing->seller,
            'items' => $items,
            'total' => $items->sum('subtotal'),
        ];
    }

    /**
     * Membuat pesanan dan mereservasi stok.
     * checkout_key mencegah pesanan ganda saat permintaan diulang.
     */
    public function checkout(
        User $buyer,
        int $sellerId,
        string $checkoutKey
    ): Order {
        $checkoutKey = trim($checkoutKey);

        if ($checkoutKey === '' || strlen($checkoutKey) > 100) {
            throw ValidationException::withMessages([
                'checkout_key' => 'Kunci checkout tidak valid.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $buyer,
                $sellerId,
                $checkoutKey
            ) {
                $existingOrder = Order::query()
                    ->where('checkout_key', $checkoutKey)
                    ->first();

                if ($existingOrder) {
                    if (
                        (int) $existingOrder->buyer_id !== (int) $buyer->id
                        || (int) $existingOrder->seller_id !== $sellerId
                    ) {
                        throw ValidationException::withMessages([
                            'checkout' => 'Kunci checkout sudah digunakan.',
                        ]);
                    }

                    return $existingOrder->load('items');
                }

                $cartItems = $this->getSellerCart($buyer, $sellerId);

                if ($cartItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Keranjang untuk penjual ini kosong.',
                    ]);
                }

                $preparedItems = collect();

                foreach ($cartItems as $cartItem) {
                    $listing = Listing::query()
                        ->whereKey($cartItem->listing_id)
                        ->lockForUpdate()
                        ->with(['seller', 'hub'])
                        ->first();

                    if (! $listing) {
                        throw ValidationException::withMessages([
                            'cart' => 'Salah satu barang sudah tidak tersedia.',
                        ]);
                    }

                    if ((int) $listing->user_id !== $sellerId) {
                        throw ValidationException::withMessages([
                            'cart' => 'Barang tidak sesuai dengan penjual yang dipilih.',
                        ]);
                    }

                    $quantity = (int) $cartItem->quantity;

                    $this->validateListing($buyer, $listing, $quantity);

                    $unitPrice = (int) $listing->price;

                    $preparedItems->push([
                        'listing' => $listing,
                        'title' => $listing->title,
                        'type' => $this->listingTypeValue($listing->listing_type),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $unitPrice * $quantity,
                    ]);
                }

                $total = $preparedItems->sum('subtotal');

                $order = Order::query()->create([
                    'checkout_key' => $checkoutKey,
                    'order_number' => 'SRK-'.strtoupper(bin2hex(random_bytes(8))),
                    'buyer_id' => $buyer->id,
                    'seller_id' => $sellerId,
                    'total' => $total,
                    'status' => Order::STATUS_PENDING_PAYMENT,
                    'expires_at' => now()->addHours(24),
                ]);

                foreach ($preparedItems as $item) {
                    /** @var Listing $listing */
                    $listing = $item['listing'];

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'listing_id' => $listing->id,
                        'title_snapshot' => $item['title'],
                        'type_snapshot' => $item['type'],
                        'unit_price' => $item['unit_price'],
                        'weight_per_unit_snapshot' => null,
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $listing->decrement('stock', $item['quantity']);
                }

                CartItem::query()
                    ->where('user_id', $buyer->id)
                    ->whereIn('id', $cartItems->pluck('id'))
                    ->delete();

                return $order->load('items');
            });
        } catch (QueryException $exception) {
            // Jika permintaan bersamaan memakai key yang sama,
            // ambil pesanan yang berhasil dibuat oleh permintaan pertama.
            $existingOrder = Order::query()
                ->where('checkout_key', $checkoutKey)
                ->first();

            if (
                $existingOrder
                && (int) $existingOrder->buyer_id === (int) $buyer->id
                && (int) $existingOrder->seller_id === $sellerId
            ) {
                return $existingOrder->load('items');
            }

            throw $exception;
        }
    }

    /**
     * Mengambil isi keranjang untuk satu penjual.
     */
    private function getSellerCart(User $buyer, int $sellerId): Collection
    {
        return $buyer->cartItems()
            ->whereHas('listing', function ($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->with(['listing.seller', 'listing.hub'])
            ->orderBy('listing_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Memvalidasi listing sebelum checkout.
     */
    private function validateListing(
        User $buyer,
        Listing $listing,
        int $quantity
    ): void {
        if ((int) $listing->user_id === (int) $buyer->id) {
            throw ValidationException::withMessages([
                'cart' => 'Kamu tidak bisa membeli barang milik sendiri.',
            ]);
        }

        if (! $listing->isPurchasable()) {
            throw ValidationException::withMessages([
                'cart' => "Barang {$listing->title} sudah tidak tersedia.",
            ]);
        }

        if ($quantity < 1 || $quantity > (int) $listing->stock) {
            throw ValidationException::withMessages([
                'cart' => "Stok barang {$listing->title} tidak mencukupi.",
            ]);
        }
    }

    /**
     * Memastikan jenis listing sesuai enum.
     */
    private function listingTypeValue(mixed $type): string
    {
        if ($type instanceof ListingType) {
            return $type->value;
        }

        $value = (string) $type;

        if (! in_array(
            $value,
            array_column(ListingType::cases(), 'value'),
            true
        )) {
            throw ValidationException::withMessages([
                'cart' => 'Jenis barang tidak valid.',
            ]);
        }

        return $value;
    }
}
