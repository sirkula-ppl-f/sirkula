<?php

namespace Tests\Support;

use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use RuntimeException;

/** Pengganti MidtransGateway di tes: tidak memanggil jaringan. */
class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int, array{order: string, amount: int}> */
    public array $calls = [];

    public bool $shouldFail = false;

    public function createSnapTransaction(Order $order, int $amount): array
    {
        $this->calls[] = ['order' => $order->order_number, 'amount' => $amount];

        if ($this->shouldFail) {
            throw new RuntimeException('gateway down');
        }

        return [
            'token' => 'fake-token-'.$order->order_number,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-'.$order->order_number,
        ];
    }

    public function fetchStatus(string $providerRef): array
    {
        return ['order_id' => $providerRef, 'transaction_status' => 'pending'];
    }
}
