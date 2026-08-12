<div>
    <h1 class="text-2xl font-semibold tracking-tight">Keranjang</h1>

    @guest
        <p class="mt-4 text-gray-600">
            Masuk untuk melihat keranjang Anda.
            <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-gray-900 underline">Masuk</a>
        </p>
    @else
        @if ($cart->isEmpty())
            <p class="mt-4 text-gray-500">Keranjang kosong.</p>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($cart->items as $item)
                    <div wire:key="cart-item-{{ $item->id }}"
                         class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-5">
                        <div class="min-w-0">
                            <h2 class="font-semibold">{{ $item->plan->name }}</h2>
                            <p class="text-sm text-gray-500">{{ $item->plan->product->name }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="number"
                                min="1"
                                wire:model="quantities.{{ $item->id }}"
                                wire:change="updateQuantity({{ $item->id }})"
                                class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                aria-label="Jumlah {{ $item->plan->name }}"
                            >
                            <button
                                type="button"
                                wire:click="removeItem({{ $item->id }})"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-6 rounded-xl border border-gray-200 p-5">
                <div>
                    <label for="display-currency" class="block text-sm font-medium text-gray-700">
                        Mata uang tampilan
                    </label>
                    <select
                        id="display-currency"
                        wire:model="currency"
                        wire:change="changeCurrency"
                        class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    >
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="text-right">
                    <p class="text-sm text-gray-500">Subtotal ({{ strtoupper($cart->displayCurrency()->value) }})</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ $cart->displayCurrency()->format($cart->subtotalIn($cart->displayCurrency())) }}
                    </p>
                </div>
            </div>
        @endif
    @endguest
</div>
