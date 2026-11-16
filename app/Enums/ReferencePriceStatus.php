<?php

namespace App\Enums;

/**
 * Status entri harga acuan pada suatu hari (WIB). Tidak disimpan di database, dihitung saat ditampilkan.
 */
enum ReferencePriceStatus: string
{
    /** Harga terbaru yang sedang berlaku untuk pasangan material + provinsi. */
    case Current = 'current';

    /** Tanggal berlaku setelah hari ini; belum dianggap berlaku. */
    case Scheduled = 'scheduled';

    /** Sudah pernah berlaku, tetapi digantikan entri yang lebih baru. */
    case History = 'history';

    public function label(): string
    {
        return __('reference-prices.status.'.$this->value);
    }
}
