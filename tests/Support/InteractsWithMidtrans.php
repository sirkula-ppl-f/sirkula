<?php

namespace Tests\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\MidtransSignature;
use App\Services\Payments\PaymentGateway;

trait InteractsWithMidtrans
{
    protected string $serverKey = 'SB-Mid-server-KUNCI-TES';

    protected function setUpMidtrans(): FakePaymentGateway
    {
        config(['services.midtrans.server_key' => $this->serverKey, 'services.midtrans.is_production' => false]);

        $fake = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $fake);

        return $fake;
    }

    /** Pesanan menunggu bayar yang sudah punya payment intent (status PENDING). */
    protected function orderDenganPayment(array $order = []): Order
    {
        $order = Order::factory()->create($order);

        Payment::create([
            'order_id' => $order->id,
            'provider_ref' => $order->order_number,
            'gross_amount' => $order->total,
            'status' => 'PENDING',
            'snap_token' => 'tok',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/tok',
        ]);

        return $order->fresh();
    }

    /**
     * Payload notifikasi Midtrans lengkap dengan tanda tangan yang benar.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    protected function notification(Order $order, string $status = 'settlement', array $override = []): array
    {
        $payload = array_merge([
            'order_id' => $order->order_number,
            'transaction_id' => 'trx-'.$order->id,
            'transaction_status' => $status,
            'status_code' => '200',
            'gross_amount' => number_format($order->total, 2, '.', ''),
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
            'transaction_time' => '2026-11-15 10:00:00',
        ], $override);

        $payload['signature_key'] = $override['signature_key'] ?? MidtransSignature::make(
            (string) $payload['order_id'],
            (string) $payload['status_code'],
            (string) $payload['gross_amount'],
            $this->serverKey,
        );

        return $payload;
    }
}
