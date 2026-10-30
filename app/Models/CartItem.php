<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'listing_id', 'quantity', 'price_seen'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_seen' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function subtotal(): int
    {
        return $this->quantity * (int) $this->listing->price;
    }

    public function priceChanged(): bool
    {
        return (int) $this->listing->price !== $this->price_seen;
    }

    public function stockInsufficient(): bool
    {
        return $this->quantity > $this->listing->stock;
    }

    public function unavailable(): bool
    {
        return ! $this->listing->isPurchasable();
    }
}
