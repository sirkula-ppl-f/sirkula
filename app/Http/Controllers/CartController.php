<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Listing;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(Request $request): View
    {
        return view('cart.index', [
            'groups' => $this->cart->groupedBySeller($request->user()),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $this->cart->add(
            $request->user(),
            Listing::with('seller')->findOrFail($request->integer('listing_id')),
            $request->integer('quantity'),
        );

        return redirect()->route('cart.index')->with('status', 'Barang ditambahkan ke keranjang.');
    }

    public function update(UpdateCartItemRequest $request, int $cartItem): RedirectResponse
    {
        // Hanya item milik sendiri; milik orang lain menghasilkan 404 (NFR-008).
        $item = $request->user()->cartItems()->with('listing.seller')->findOrFail($cartItem);

        try {
            $this->cart->updateQuantity($item, $request->integer('quantity'));
        } catch (ValidationException $e) {
            // Error ditampilkan di item yang bersangkutan saja.
            throw $e->errorBag('cart-item-'.$item->id);
        }

        return redirect()->route('cart.index')->with('status', 'Jumlah barang diperbarui.');
    }

    public function destroy(Request $request, int $cartItem): RedirectResponse
    {
        $item = $request->user()->cartItems()->findOrFail($cartItem);

        $this->cart->remove($item);

        return redirect()->route('cart.index')->with('status', 'Barang dihapus dari keranjang.');
    }
}
