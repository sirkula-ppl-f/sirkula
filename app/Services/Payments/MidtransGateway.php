<?php

namespace App\Services\Payments;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use RuntimeException;

class MidtransGateway implements PaymentGateway
{
    public function createSnapTransaction(Order $order, int $amount): array
    {
        $this->configure();

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $amount,
            ],
            'callbacks' => [
                'finish' => route('payments.result', ['order' => $order->getKey()]),
            ],
        ];

        if ($order->expires_at !== null) {
            $params['expiry'] = [
                'unit' => 'minute',
                'duration' => max(1, (int) ceil(now()->diffInMinutes($order->expires_at, false))),
            ];
        }

        $transaction = Snap::createTransaction($params);

        return [
            'token' => (string) $transaction->token,
            'redirect_url' => (string) $transaction->redirect_url,
        ];
    }

    public function fetchStatus(string $providerRef): array
    {
        $this->configure();

        return (array) Transaction::status($providerRef);
    }

    /**
     * NFR-011: server key hanya dari environment, dan mode produksi ditolak selama masa demo.
     */
    private function configure(): void
    {
        $config = (array) config('services.midtrans');

        if (filter_var($config['is_production'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Mode produksi Midtrans tidak diizinkan selama masa demo.');
        }

        if (blank($config['server_key'] ?? null)) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur di environment.');
        }

        Config::$serverKey = (string) $config['server_key'];
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }
}
