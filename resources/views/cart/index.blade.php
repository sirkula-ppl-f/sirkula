<x-layouts.app title="Keranjang">
    <p class="-mt-4 mb-6 text-sm text-gray-600">
        Item dikelompokkan per penjual. Checkout dilakukan satu penjual setiap kali proses.
    </p>

    @forelse ($groups as $group)
        <section class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200" aria-labelledby="penjual-{{ $group['seller']->id }}" dusk="cart-group">
            <h2 id="penjual-{{ $group['seller']->id }}" class="mb-4 text-base font-semibold">
                Penjual: {{ $group['seller']->name }}
            </h2>

            <ul class="divide-y divide-gray-100">
                @foreach ($group['items'] as $item)
                    @php
                        $listing = $item->listing;
                        $bag = 'cart-item-'.$item->id;
                        $gagal = $errors->getBag($bag)->has('quantity');
                    @endphp
                    <li class="flex flex-wrap items-start justify-between gap-4 py-4" dusk="cart-item">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $listing->title }}</p>
                            <p class="text-sm text-gray-600">
                                Rp{{ number_format($listing->price, 0, ',', '.') }} / {{ $listing->sale_unit === 'LOT' ? 'lot' : 'item' }}
                            </p>

                            @if ($item->unavailable())
                                <p class="mt-1 text-sm font-medium text-red-700" dusk="flag-unavailable">
                                    Barang ini sudah tidak tersedia. Hapus dari keranjang.
                                </p>
                            @else
                                @if ($item->priceChanged())
                                    <p class="mt-1 text-sm font-medium text-amber-700" dusk="flag-price">
                                        Harga berubah: Rp{{ number_format($item->price_seen, 0, ',', '.') }} menjadi Rp{{ number_format($listing->price, 0, ',', '.') }}
                                    </p>
                                @endif
                                @if ($item->stockInsufficient())
                                    <p class="mt-1 text-sm font-medium text-amber-700" dusk="flag-stock">
                                        Stok tersisa {{ $listing->stock }}, kurang dari jumlah di keranjang.
                                    </p>
                                @endif
                            @endif

                            @error('quantity', $bag)
                                <p class="mt-1 text-sm text-red-700" role="alert" dusk="error-{{ $item->id }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('cart.update', $item->id) }}" class="flex items-center gap-2" data-submit-once>
                                @csrf
                                @method('PATCH')
                                <label for="qty-{{ $item->id }}" class="sr-only">Jumlah {{ $listing->title }}</label>
                                <input
                                    id="qty-{{ $item->id }}"
                                    name="quantity"
                                    type="number"
                                    min="1"
                                    max="9999"
                                    value="{{ $gagal ? old('quantity') : $item->quantity }}"
                                    class="w-20 rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                                    dusk="qty-{{ $item->id }}"
                                >
                                <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-50" dusk="update-{{ $item->id }}">
                                    Ubah
                                </button>
                            </form>

                            <span class="w-28 text-right font-semibold">Rp{{ number_format($item->subtotal(), 0, ',', '.') }}</span>

                            <form method="POST" action="{{ route('cart.destroy', $item->id) }}" data-submit-once>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-700 hover:underline" dusk="remove-{{ $item->id }}">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-600">
                    Subtotal
                    <span class="ms-2 text-lg font-semibold text-brand-700" dusk="subtotal">Rp{{ number_format($group['subtotal'], 0, ',', '.') }}</span>
                </p>

                {{-- Route checkout dibuat PBI-12. Sebelum ada, tombol dinonaktifkan. --}}
                @if (Route::has('checkout.create'))
                    <a href="{{ route('checkout.create', ['seller' => $group['seller']->id]) }}" class="rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700" dusk="checkout-{{ $group['seller']->id }}">
                        Checkout penjual ini
                    </a>
                @else
                    <button type="button" disabled class="rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white opacity-60" dusk="checkout-{{ $group['seller']->id }}">
                        Checkout penjual ini
                    </button>
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-xl bg-white p-6 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200" dusk="cart-empty">
            Keranjang masih kosong.
        </div>
    @endforelse

    <p class="text-xs text-gray-500">
        Keranjang tidak mengunci stok. Harga dan ketersediaan dicek ulang saat checkout.
    </p>
</x-layouts.app>
