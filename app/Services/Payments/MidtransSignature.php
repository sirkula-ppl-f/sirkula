<?php

namespace App\Services\Payments;

/**
 * Verifikasi tanda tangan notifikasi Midtrans:
 * SHA512(order_id + status_code + gross_amount + server_key). Selalu ditolak bila server key kosong.
 */
final class MidtransSignature
{
    public static function make(string $orderId, string $statusCode, string $grossAmount, string $serverKey): string
    {
        return hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function isValid(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field])) {
                return false;
            }
        }

        $expected = self::make(
            (string) $payload['order_id'],
            (string) $payload['status_code'],
            (string) $payload['gross_amount'],
            $serverKey,
        );

        return hash_equals($expected, strtolower((string) $payload['signature_key']));
    }
}
