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
            $type = $this->listingTypeValue($listing->listing_type);

            return [
                'cart_item_id' => $cartItem->id,
                'listing_id' => $listing->id,
                'title' => $listing->title,
                'type' => $type,
                'type_label' => ListingType::from($type)->label(),
                'quantity' => $quantity,
                'price_seen' => (int) $cartItem->price_seen,
                'price_changed' => (int) $cartItem->price_seen !== $unitPrice,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ];
        });

        return [
            'seller' => $cartItems->first()->listing->seller,
            'items' => $items,
            'total' => $items->sum('subtotal'),
            'preview_hash' => $this->generateCheckoutHash($items),
        ];
    }

    /**
     * Membuat pesanan dan mereservasi stok.
     *
     * previewHash memastikan isi keranjang dan harga tidak berubah
     * sejak pembeli melihat halaman preview.
     * checkoutKey mencegah pesanan ganda saat permintaan diulang.
     */
    public function checkout(
        User $buyer,
        int $sellerId,
        string $checkoutKey,
        string $previewHash
    ): Order {
        $checkoutKey = trim($checkoutKey);
        $previewHash = strtolower(trim($previewHash));

        if ($checkoutKey === '' || strlen($checkoutKey) > 100) {
            throw ValidationException::withMessages([
                'checkout_key' => 'Kunci checkout tidak valid.',
            ]);
        }

        if (! preg_match('/\A[a-f0-9]{64}\z/', $previewHash)) {
            throw ValidationException::withMessages([
                'preview_hash' => 'Data preview checkout tidak valid. Silakan muat ulang halaman.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $buyer,
                $sellerId,
                $checkoutKey,
                $previewHash
            ) {
                $existingOrder = Order::query()
                    ->where('checkout_key', $checkoutKey)
                    ->first();

                if ($existingOrder) {
                    $this->ensureSameCheckoutRequest(
                        $existingOrder,
                        $buyer,
                        $sellerId,
                        $previewHash
                    );

                    return $existingOrder->load('items');
                }

                // Kunci baris keranjang terlebih dahulu agar kuantitas
                // tidak berubah selama proses checkout berlangsung.
                $cartItems = $this->getSellerCart(
                    $buyer,
                    $sellerId,
                    lockForUpdate: true
                );

                if ($cartItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Keranjang untuk penjual ini kosong.',
                    ]);
                }

                $preparedItems = collect();

                // Cart sudah diurutkan berdasarkan listing_id.
                // Listing dikunci dengan urutan yang sama untuk mengurangi deadlock.
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

                    $this->validateListing(
                        $buyer,
                        $listing,
                        $quantity
                    );

                    $unitPrice = (int) $listing->price;

                    $preparedItems->push([
                        'cart_item' => $cartItem,
                        'listing' => $listing,
                        'listing_id' => (int) $listing->id,
                        'title' => $listing->title,
                        'type' => $this->listingTypeValue($listing->listing_type),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $unitPrice * $quantity,
                    ]);
                }

                // Hitung ulang fingerprint dari data terkini yang sudah dikunci.
                $currentHash = $this->generateCheckoutHash($preparedItems);

                if (! hash_equals($previewHash, $currentHash)) {
                    throw ValidationException::withMessages([
                        'checkout' => 'Harga atau isi keranjang berubah. Silakan periksa kembali checkout sebelum melanjutkan.',
                    ]);
                }

                $total = $preparedItems->sum('subtotal');

                $order = Order::query()->create([
                    'checkout_key' => $checkoutKey,
                    'checkout_hash' => $currentHash,
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

                    /** @var CartItem $cartItem */
                    $cartItem = $item['cart_item'];

                    // Pembeli sudah mengonfirmasi harga yang ditampilkan.
                    $cartItem->update([
                        'price_seen' => $item['unit_price'],
                    ]);

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'listing_id' => $listing->id,
                        'title_snapshot' => $item['title'],
                        'type_snapshot' => $item['type'],
                        'unit_price' => $item['unit_price'],
                        // TODO(PBI-08/PBI-20): isi snapshot berat setelah atribut berat per unit tersedia.
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
            // Menangani dua request bersamaan yang memakai checkout_key sama.
            $existingOrder = Order::query()
                ->where('checkout_key', $checkoutKey)
                ->first();

            if ($existingOrder) {
                $this->ensureSameCheckoutRequest(
                    $existingOrder,
                    $buyer,
                    $sellerId,
                    $previewHash
                );

                return $existingOrder->load('items');
            }

            throw $exception;
        }
    }

    /**
     * Memastikan penggunaan ulang checkout key memiliki data yang sama.
     */
    private function ensureSameCheckoutRequest(
        Order $existingOrder,
        User $buyer,
        int $sellerId,
        string $previewHash
    ): void {
        if (
            (int) $existingOrder->buyer_id !== (int) $buyer->id
            || (int) $existingOrder->seller_id !== $sellerId
            || ! is_string($existingOrder->checkout_hash)
            || ! hash_equals($existingOrder->checkout_hash, $previewHash)
        ) {
            throw ValidationException::withMessages([
                'checkout' => 'Kunci checkout sudah digunakan.',
            ]);
        }
    }

    /**
     * Menghasilkan fingerprint berdasarkan listing, kuantitas, dan harga.
     */
    private function generateCheckoutHash(Collection $items): string
    {
        $fingerprintItems = $items
            ->map(function (array $item) {
                return [
                    'listing_id' => (int) $item['listing_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                ];
            })
            ->sortBy('listing_id')
            ->values();

        $fingerprint = $fingerprintItems
            ->map(fn (array $item) => implode(':', [
                $item['listing_id'],
                $item['quantity'],
                $item['unit_price'],
            ]))
            ->implode('|');

        return hash('sha256', $fingerprint);
    }

    /**
     * Mengambil isi keranjang untuk satu penjual.
     */
    private function getSellerCart(
        User $buyer,
        int $sellerId,
        bool $lockForUpdate = false
    ): Collection {
        $query = $buyer->cartItems()
            ->whereHas('listing', function ($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->with(['listing.seller', 'listing.hub'])
            ->orderBy('listing_id')
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
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
