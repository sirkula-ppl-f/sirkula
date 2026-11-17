<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function show(Request $request, int $order): View
    {
        return view('payments.show', ['order' => $this->ownOrder($request, $order)]);
    }

    public function store(Request $request, int $order): RedirectResponse
    {
        $payment = $this->payments->start($this->ownOrder($request, $order));

        return redirect()->away($payment->redirect_url);
    }

    /**
     * Halaman hasil: statusnya selalu dibaca dari database, tidak dari parameter redirect Midtrans.
     */
    public function result(Request $request, int $order): View
    {
        return view('payments.result', ['order' => $this->ownOrder($request, $order)]);
    }

    /**
     * Hanya pembeli pemilik pesanan; pesanan orang lain menghasilkan 404 (NFR-008).
     */
    private function ownOrder(Request $request, int $id): Order
    {
        return Order::query()
            ->with(['payment', 'seller'])
            ->where('buyer_id', $request->user()->getKey())
            ->findOrFail($id);
    }
}
