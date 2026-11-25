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
            'previewHash' => $preview['preview_hash'],
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
            'preview_hash' => ['required', 'string', 'size:64', 'regex:/\A[a-fA-F0-9]{64}\z/'],
        ]);

        $order = $this->checkout->checkout(
            $request->user(),
            $seller,
            $validated['checkout_key'],
            $validated['preview_hash']
        );

        return redirect()
            ->route('payments.show', $order)
            ->with(
                'status',
                'Checkout berhasil. Nomor pesanan: '.$order->order_number
            );
    }
}
