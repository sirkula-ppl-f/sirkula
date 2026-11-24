<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout
    ) {}

    /**
     * Menampilkan ringkasan checkout untuk satu penjual.
     */
    public function create(Request $request, int $seller): View
    {
        $preview = $this->checkout->preview(
            $request->user(),
            $seller
        );

        return view('checkout.create', [
            'seller' => $preview['seller'],
            'items' => $preview['items'],
            'total' => $preview['total'],
            'checkoutKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Membuat pesanan dari keranjang untuk satu penjual.
     */
    public function store(Request $request, int $seller): RedirectResponse
    {
        $validated = $request->validate([
            'checkout_key' => ['required', 'string', 'max:100'],
        ]);

        $order = $this->checkout->checkout(
            $request->user(),
            $seller,
            $validated['checkout_key']
        );

        return redirect()
            ->route('cart.index')
            ->with(
                'status',
                'Checkout berhasil. Nomor pesanan: '.$order->order_number
            );
    }
}
