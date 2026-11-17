<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya mekanisme penentu status pembayaran. Dipakai pemrosesan notifikasi (PBI-13)
 * dan nanti rekonsiliasi (PBI-15), supaya aturannya tidak bercabang.
 *
 * Aturan: LUNAS bersifat final; notifikasi tidak berurutan tidak menurunkan status;
 * nominal harus cocok; pembayaran yang datang terlambat menjadi PAYMENT_REVIEW.
 *
 * Perubahan status pesanan (batal/kedaluwarsa) dan pelepasan stok untuk pembayaran gagal
 * ditangani PBI-15; di sini hanya status pembayarannya yang dicatat.
 */
class PaymentStatusResolver
{
    public function process(PaymentEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $event = PaymentEvent::query()->lockForUpdate()->find($event->getKey());

            if ($event === null || $event->process_status !== PaymentEvent::STATUS_PENDING) {
                return; // sudah diproses: aman dipanggil berulang
            }

            $payment = Payment::query()->where('provider_ref', $event->provider_ref)->lockForUpdate()->first();

            if ($payment === null) {
                $this->finish($event, PaymentEvent::STATUS_IGNORED, 'Pembayaran tidak dikenal.');

                return;
            }

            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $payload = $event->payload;

            if ($this->amount($payload) !== $payment->gross_amount) {
                $this->finish($event, PaymentEvent::STATUS_REJECTED, 'Nominal tidak cocok.', $payment);

                return;
            }

            $incoming = $this->mapStatus($payload);

            if ($incoming === PaymentStatus::Unknown) {
                $this->finish($event, PaymentEvent::STATUS_IGNORED, 'Status tidak dikenal.', $payment);

                return;
            }

            if ($payment->status === PaymentStatus::Paid) {
                $this->finish($event, PaymentEvent::STATUS_PROCESSED, 'Sudah lunas; notifikasi diabaikan.', $payment);

                return;
            }

            match (true) {
                $incoming === PaymentStatus::Paid => $this->settle($event, $payment, $order, $payload),
                $incoming === PaymentStatus::Pending => $this->markPending($event, $payment),
                default => $this->markFailure($event, $payment, $incoming),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function settle(PaymentEvent $event, Payment $payment, Order $order, array $payload): void
    {
        $canSettle = ! $payment->status->isFailure()
            && in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAYMENT_REVIEW], true);

        if ($canSettle) {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'method' => is_scalar($payload['payment_type'] ?? null) ? (string) $payload['payment_type'] : null,
            ]);
            $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);

            $this->finish($event, PaymentEvent::STATUS_PROCESSED, 'Pembayaran lunas.', $payment);

            return;
        }

        // Pembayaran datang setelah pesanan dibatalkan/kedaluwarsa atau pembayaran sudah gagal.
        $order->update(['status' => Order::STATUS_PAYMENT_REVIEW]);

        $this->finish($event, PaymentEvent::STATUS_PROCESSED, 'Pembayaran terlambat; pesanan perlu ditinjau.', $payment);
    }

    private function markPending(PaymentEvent $event, Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Unknown) {
            $payment->update(['status' => PaymentStatus::Pending]);
        }

        $this->finish($event, PaymentEvent::STATUS_PROCESSED, 'Menunggu pembayaran.', $payment);
    }

    private function markFailure(PaymentEvent $event, Payment $payment, PaymentStatus $incoming): void
    {
        $payment->update(['status' => $incoming]);

        $this->finish($event, PaymentEvent::STATUS_PROCESSED, 'Pembayaran '.strtolower($incoming->value).'.', $payment);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapStatus(array $payload): PaymentStatus
    {
        $status = (string) ($payload['transaction_status'] ?? '');
        $fraud = (string) ($payload['fraud_status'] ?? '');

        return match ($status) {
            'settlement' => PaymentStatus::Paid,
            'capture' => $fraud === 'accept' ? PaymentStatus::Paid : PaymentStatus::Pending,
            'pending' => PaymentStatus::Pending,
            'deny', 'failure' => PaymentStatus::Failed,
            'cancel' => PaymentStatus::Cancelled,
            'expire' => PaymentStatus::Expired,
            default => PaymentStatus::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function amount(array $payload): int
    {
        return isset($payload['gross_amount']) && is_numeric($payload['gross_amount'])
            ? (int) round((float) $payload['gross_amount'])
            : -1;
    }

    private function finish(PaymentEvent $event, string $status, string $note, ?Payment $payment = null): void
    {
        $event->update([
            'payment_id' => $payment?->getKey() ?? $event->payment_id,
            'process_status' => $status,
            'process_note' => $note,
            'processed_at' => now(),
        ]);
    }
}
