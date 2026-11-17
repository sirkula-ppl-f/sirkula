<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Pembayaran sandbox (FR-016 / PBI-13). Status LUNAS tidak pernah ditetapkan di sini;
 * hanya PaymentStatusResolver yang menetapkannya, dari notifikasi yang tanda tangannya terverifikasi.
 */
class PaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Memulai pembayaran: satu payment intent per pesanan. Dipanggil ulang akan memakai intent yang sama.
     */
    public function start(Order $order): Payment
    {
        $payment = DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (! $locked->isPayable()) {
                throw ValidationException::withMessages([
                    'payment' => 'Pesanan ini tidak dapat dibayar.',
                ]);
            }

            // firstOrCreate + kolom order_id unik: dua klik serentak tetap menghasilkan satu payment.
            return Payment::query()->firstOrCreate(
                ['order_id' => $locked->getKey()],
                [
                    'provider_ref' => $locked->order_number,
                    'gross_amount' => $locked->total, // nominal dari server
                    'status' => PaymentStatus::Unknown,
                ],
            );
        });

        if ($payment->redirect_url !== null) {
            return $payment;
        }

        try {
            $response = $this->gateway->createSnapTransaction($order, $payment->gross_amount);
        } catch (Throwable $e) {
            // Tidak mencatat pesan lengkap agar log tidak memuat data rahasia (NFR-014).
            Log::error('Midtrans: gagal membuat transaksi', [
                'order' => $order->order_number,
                'error' => class_basename($e),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Layanan pembayaran sedang bermasalah. Coba lagi beberapa saat.',
            ]);
        }

        $payment->update([
            'snap_token' => $response['token'],
            'redirect_url' => $response['redirect_url'],
            'status' => PaymentStatus::Pending,
        ]);

        return $payment;
    }

    /**
     * Menyimpan notifikasi yang sudah lolos verifikasi tanda tangan ke inbox, lalu memprosesnya
     * lewat antrean. Mengembalikan null bila notifikasi yang sama sudah pernah diterima.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordNotification(array $payload): ?PaymentEvent
    {
        $stored = $payload;
        unset($stored['signature_key']);

        $providerRef = (string) $payload['order_id'];

        try {
            $event = PaymentEvent::query()->create([
                'payment_id' => Payment::query()->where('provider_ref', $providerRef)->value('id'),
                'fingerprint' => $this->fingerprint($payload),
                'provider_ref' => $providerRef,
                'transaction_status' => (string) ($payload['transaction_status'] ?? ''),
                'payload' => $stored,
                'process_status' => PaymentEvent::STATUS_PENDING,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        ProcessPaymentEvent::dispatch($event->getKey())->afterCommit();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fingerprint(array $payload): string
    {
        $parts = [];

        foreach (['order_id', 'transaction_id', 'transaction_status', 'status_code', 'gross_amount', 'fraud_status', 'transaction_time'] as $field) {
            $parts[] = is_scalar($payload[$field] ?? null) ? (string) $payload[$field] : '';
        }

        return hash('sha256', implode('|', $parts));
    }
}
