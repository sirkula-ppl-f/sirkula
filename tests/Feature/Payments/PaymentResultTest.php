<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithMidtrans;
use Tests\TestCase;

class PaymentResultTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMidtrans();
        $this->pembeli = User::factory()->create();
    }

    private function buka(Order $order, string $query = '')
    {
        return $this->actingAs($this->pembeli)->get(route('payments.result', $order->id).$query);
    }

    public function test_lunas_menampilkan_berhasil_berlabel_sandbox(): void
    {
        $order = Order::factory()->lunas()->create(['buyer_id' => $this->pembeli->id]);

        $this->buka($order)->assertOk()
            ->assertSee('Pembayaran berhasil')
            ->assertSee('SANDBOX')
            ->assertSee('Serah-terima barang dilakukan langsung');
    }

    public function test_payment_review_menampilkan_sedang_diperiksa_bukan_gagal(): void
    {
        $order = Order::factory()->create(['buyer_id' => $this->pembeli->id, 'status' => Order::STATUS_PAYMENT_REVIEW]);

        $this->buka($order)->assertOk()
            ->assertSee('Pembayaran sedang diperiksa')
            ->assertDontSee('Pembayaran gagal');
    }

    public function test_pembayaran_gagal(): void
    {
        $order = $this->orderDenganPayment(['buyer_id' => $this->pembeli->id]);
        Payment::where('order_id', $order->id)->update(['status' => 'FAILED']);

        $this->buka($order)->assertOk()->assertSee('Pembayaran gagal');
    }

    public function test_menunggu_pembayaran(): void
    {
        $order = $this->orderDenganPayment(['buyer_id' => $this->pembeli->id]);

        $this->buka($order)->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Kembali ke halaman pembayaran');
    }

    public function test_parameter_redirect_tidak_mengubah_tampilan_atau_status(): void
    {
        $order = $this->orderDenganPayment(['buyer_id' => $this->pembeli->id]);

        $this->buka($order, '?transaction_status=settlement&status_code=200&order_id='.$order->order_number)
            ->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertDontSee('Pembayaran berhasil');

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }
}
