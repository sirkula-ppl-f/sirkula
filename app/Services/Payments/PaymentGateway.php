<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Container\Attributes\Bind;

/**
 * Adapter payment gateway di sisi server (TR-005). Dipisah dari PaymentService supaya
 * tes tidak memanggil jaringan; tes memakai FakePaymentGateway.
 */
#[Bind(MidtransGateway::class)]
interface PaymentGateway
{
    /**
     * Membuat transaksi Snap. Nominal selalu dari server.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function createSnapTransaction(Order $order, int $amount): array;

    /**
     * Memeriksa status transaksi di payment gateway (dipakai rekonsiliasi PBI-15).
     *
     * @return array<string, mixed>
     */
    public function fetchStatus(string $providerRef): array;
}
