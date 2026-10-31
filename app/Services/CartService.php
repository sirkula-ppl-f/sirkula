<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika keranjang (FR-014 / PBI-11). Keranjang tidak mengunci stok; stok dan harga
 * dicek ulang saat checkout (PBI-12).
 */
class CartService
{
    public function add(User $user, Listing $listing, int $quantity): CartItem
    {
        $this->pastikanBolehDibeli($user, $listing);

        return DB::transaction(function () use ($user, $listing, $quantity) {
            $item = CartItem::query()
                ->where('user_id', $user->getKey())
                ->where('listing_id', $listing->getKey())
                ->lockForUpdate()
                ->first();

            $total = $quantity + ($item?->quantity ?? 0);
            $this->pastikanCukupStok($listing, $total);

            if ($item) {
                $item->update(['quantity' => $total]);

                return $item;
            }

            return CartItem::create([
                'user_id' => $user->getKey(),
                'listing_id' => $listing->getKey(),
                'quantity' => $quantity,
                'price_seen' => $listing->price,
            ]);
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        $listing = $item->listing;

        if (! $listing->isPurchasable()) {
            throw ValidationException::withMessages([
                'quantity' => 'Barang ini sudah tidak tersedia. Hapus dari keranjang.',
            ]);
        }

        $this->pastikanCukupStok($listing, $quantity);
        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Item keranjang dikelompokkan per penjual beserta subtotal (harga terkini).
     *
     * @return Collection<int, array{seller: User, items: Collection<int, CartItem>, subtotal: int}>
     */
    public function groupedBySeller(User $user): Collection
    {
        return $user->cartItems()
            ->with('listing.seller')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (CartItem $item) => $item->listing->user_id)
            ->map(fn (Collection $items) => [
                'seller' => $items->first()->listing->seller,
                'items' => $items,
                'subtotal' => $items->sum(fn (CartItem $item) => $item->subtotal()),
            ])
            ->values();
    }

    private function pastikanBolehDibeli(User $user, Listing $listing): void
    {
        if ($listing->user_id === $user->getKey()) {
            throw ValidationException::withMessages([
                'listing_id' => 'Kamu tidak dapat membeli listing milikmu sendiri.',
            ]);
        }

        if (! $listing->isPurchasable()) {
            throw ValidationException::withMessages([
                'listing_id' => 'Barang ini tidak tersedia.',
            ]);
        }
    }

    private function pastikanCukupStok(Listing $listing, int $quantity): void
    {
        if ($quantity > $listing->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Stok tersisa {$listing->stock}. Jumlah di keranjang tidak boleh melebihinya.",
            ]);
        }
    }
}
