<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unknown = 'UNKNOWN';
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Failed = 'FAILED';
    case Expired = 'EXPIRED';
    case Cancelled = 'CANCELLED';

    /** Pembayaran sudah dipastikan gagal, kedaluwarsa, atau dibatalkan oleh payment gateway. */
    public function isFailure(): bool
    {
        return in_array($this, [self::Failed, self::Expired, self::Cancelled], true);
    }
}
