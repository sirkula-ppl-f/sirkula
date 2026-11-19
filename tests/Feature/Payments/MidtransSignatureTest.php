<?php

namespace Tests\Feature\Payments;

use App\Services\Payments\MidtransSignature;
use Tests\TestCase;

class MidtransSignatureTest extends TestCase
{
    private function payload(string $key): array
    {
        return [
            'order_id' => 'SRK-1',
            'status_code' => '200',
            'gross_amount' => '112500.00',
            'signature_key' => MidtransSignature::make('SRK-1', '200', '112500.00', $key),
        ];
    }

    public function test_tanda_tangan_benar_diterima(): void
    {
        config(['services.midtrans.server_key' => 'kunci']);

        $this->assertTrue(MidtransSignature::isValid($this->payload('kunci')));
    }

    public function test_tanda_tangan_dari_kunci_lain_ditolak(): void
    {
        config(['services.midtrans.server_key' => 'kunci']);

        $this->assertFalse(MidtransSignature::isValid($this->payload('kunci-lain')));
    }

    public function test_nominal_diubah_membuat_tanda_tangan_tidak_cocok(): void
    {
        config(['services.midtrans.server_key' => 'kunci']);
        $payload = $this->payload('kunci');
        $payload['gross_amount'] = '1.00';

        $this->assertFalse(MidtransSignature::isValid($payload));
    }

    public function test_server_key_kosong_selalu_ditolak(): void
    {
        config(['services.midtrans.server_key' => '']);

        $this->assertFalse(MidtransSignature::isValid($this->payload('')));
    }

    public function test_field_wajib_hilang_ditolak(): void
    {
        config(['services.midtrans.server_key' => 'kunci']);
        $payload = $this->payload('kunci');
        unset($payload['signature_key']);

        $this->assertFalse(MidtransSignature::isValid($payload));
    }
}
