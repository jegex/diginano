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
                            @if ($item->plan->isPwyw())
                                <p class="mt-1 text-sm text-gray-500">Harga sesuai keinginan, minimal
                                    {{ $cart->displayCurrency()->format($cart->displayCurrency()->convertUsd($item->minAmountUsd())) }}
                                </p>
                            @elseif ($item->plan->isUsageBased())
                                <p class="mt-1 text-sm text-gray-500">Ditagih berdasarkan pemakaian saat perpanjangan.</p>
                            @endif
                            <p class="mt-1 text-sm font-medium text-gray-700">
                                {{ $cart->displayCurrency()->format($cart->displayCurrency()->convertUsd($item->lineTotalUsd())) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($item->plan->isPwyw())
                                <input
                                    type="number"
                                    step="0.01"
                                    min="{{ $item->minAmountUsd() }}"
                                    wire:model="amounts.{{ $item->id }}"
                                    wire:change="updateAmount({{ $item->id }})"
                                    class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                    aria-label="Nominal {{ $item->plan->name }}"
                                >
                                @error("amounts.{$item->id}")
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            @elseif (! $item->plan->isUsageBased())
                                <input
                                    type="number"
                                    min="1"
                                    wire:model="quantities.{{ $item->id }}"
                                    wire:change="updateQuantity({{ $item->id }})"
                                    class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                    aria-label="Jumlah {{ $item->plan->name }}"
                                >
                            @endif
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

            <div class="mt-8 flex flex-wrap items-start justify-between gap-6 rounded-xl border border-gray-200 p-5">
                <div class="space-y-4">
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
                                <option value="{{ $currency->code }}">{{ $currency->name }}</option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($coupon !== null)
                        <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                            <span class="text-sm font-medium text-green-800">Kupon {{ $coupon->code }}</span>
                            <button
                                type="button"
                                wire:click="removeCoupon"
                                class="text-sm font-medium text-red-600 transition hover:text-red-700"
                            >
                                Hapus
                            </button>
                        </div>
                    @else
                        <div>
                            <label for="coupon-code" class="block text-sm font-medium text-gray-700">
                                Kode kupon
                            </label>
                            <div class="mt-1 flex items-center gap-2">
                                <input
                                    id="coupon-code"
                                    type="text"
                                    wire:model="couponCode"
                                    wire:keydown.enter="applyCoupon"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                >
                                <button
                                    type="button"
                                    wire:click="applyCoupon"
                                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                                >
                                    Terapkan
                                </button>
                            </div>
                            @error('couponCode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    <p class="text-sm text-gray-500">Subtotal ({{ strtoupper($cart->displayCurrency()->code) }})</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ $cart->displayCurrency()->format($cart->subtotalIn($cart->displayCurrency())) }}
                    </p>
                    @if ($cart->setupFeeUsd() > 0)
                        <p class="mt-2 text-sm text-gray-500">Biaya pengaturan</p>
                        <p class="text-sm">
                            {{ $cart->displayCurrency()->format($cart->displayCurrency()->convertUsd($cart->setupFeeUsd())) }}
                        </p>
                    @endif
                    @if ($coupon !== null)
                        <p class="mt-2 text-sm text-gray-500">Diskon {{ $coupon->code }}</p>
                        <p class="text-sm text-green-600">
                            -{{ $cart->displayCurrency()->format($cart->couponDiscountIn($coupon, $cart->displayCurrency())) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500">Total</p>
                        <p class="text-lg font-semibold">
                            {{ $cart->displayCurrency()->format($cart->totalIn($cart->displayCurrency(), $coupon)) }}
                        </p>
                    @endif
                    <a
                        href="{{ route('checkout') }}"
                        class="mt-5 inline-block rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-700"
                    >
                        Checkout
                    </a>
                </div>
            </div>
        @endif
    @endguest
</div>
