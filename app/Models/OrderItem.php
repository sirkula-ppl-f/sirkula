<?php

namespace App\Models;

use App\Enums\ListingType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item snapshot di dalam pesanan (OrderItem).
 *
 * Menyimpan salinan judul, tipe listing, harga satuan, dan berat per unit
 * saat checkout, sehingga perubahan listing di kemudian hari tidak mengubah
 * riwayat pesanan maupun perhitungan aktivitas sirkular (FR-015, FR-022).
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'listing_id',
        'title_snapshot',
        'type_snapshot',
        'unit_price',
        'weight_per_unit_snapshot',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'type_snapshot' => ListingType::class,
            'unit_price' => 'integer',
            'weight_per_unit_snapshot' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * Pesanan induk dari item ini.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Listing asal (bisa null bila listing tidak disematkan pada fixture).
     *
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /**
     * Estimasi berat total item ini dalam kg (berat per unit × jumlah).
     * Mengembalikan null bila berat per unit tidak diketahui (FR-022: berat tidak diketahui != 0).
     */
    public function estimatedTotalWeightKg(): ?float
    {
        if ($this->weight_per_unit_snapshot === null) {
            return null;
        }

        return round((float) $this->weight_per_unit_snapshot * $this->quantity, 2);
    }
}
