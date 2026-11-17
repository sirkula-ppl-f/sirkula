<?php

namespace App\Enums;

/**
 * Status pesanan sesuai rancangan Class Diagram (StatusPesanan).
 * Alur sistem berakhir pada status PAID (Lunas), tanpa status COMPLETED.
 */
enum OrderStatus: string
{
    case PendingPayment = 'PENDING_PAYMENT';
    case PaymentReview = 'PAYMENT_REVIEW';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';
    case Expired = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::PaymentReview => 'Sedang Diperiksa',
            self::Paid => 'Lunas',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
