<div>
    <h1 class="text-2xl font-semibold tracking-tight">Checkout</h1>

    @guest
        <p class="mt-4 text-gray-600">
            Masuk untuk melanjutkan checkout.
            <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-gray-900 underline">Masuk</a>
        </p>
    @else
        @if ($cart->isEmpty())
            <p class="mt-4 text-gray-500">Keranjang Anda kosong.</p>
        @else
            @php($total = $cart->totalUsd($coupon))
            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
                <div class="space-y-4">
                    @foreach ($cart->items as $item)
                        <div wire:key="checkout-item-{{ $item->id }}"
                             class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-5">
                            <div class="min-w-0">
                                <h2 class="font-semibold">{{ $item->plan->name }}</h2>
                                <p class="text-sm text-gray-500">{{ $item->plan->product->name }}</p>
                                @if ($item->plan->isUsageBased())
                                    <p class="mt-1 text-sm text-gray-500">Ditagih berdasarkan pemakaian saat perpanjangan.</p>
                                @elseif ($item->plan->isPwyw())
                                    <p class="mt-1 text-sm text-gray-500">Nominal:
                                        {{ $cart->displayCurrency()->format($cart->displayCurrency()->convertUsd($item->amountUsd())) }}
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-gray-500">Jumlah: {{ $item->quantity }}</p>
                                @endif
                            </div>
                            <p class="font-semibold">
                                {{ $cart->displayCurrency()->format($item->lineTotalIn($cart->displayCurrency())) }}
                            </p>
                        </div>
                    @endforeach

                    @if ($total > 0)
                        <div class="rounded-xl border border-gray-200 p-5">
                            <h2 class="font-semibold">Metode pembayaran</h2>
                            <div class="mt-3 space-y-2">
                                @forelse ($paymentMethods as $method)
                                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $method->id }}"
                                            wire:model="paymentMethodId"
                                            class="text-gray-900"
                                        >
                                        <span class="text-sm font-medium">{{ $method->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500">Belum ada metode pembayaran yang aktif.</p>
                                @endforelse
                            </div>
                            @error('paymentMethodId')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <aside class="h-fit rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Ringkasan</p>

                    <p class="mt-3 text-sm text-gray-500">Subtotal ({{ strtoupper($cart->displayCurrency()->code) }})</p>
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
                    @endif

                    <p class="mt-2 text-sm text-gray-500">Total</p>
                    <p class="text-lg font-semibold">
                        {{ $cart->displayCurrency()->format($cart->totalIn($cart->displayCurrency(), $coupon)) }}
                    </p>

                    @if ($total > 0)
                        <button
                            type="button"
                            wire:click="checkout"
                            wire:loading.attr="disabled"
                            class="mt-5 w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                        >
                            Buat Pesanan
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="checkoutFree"
                            wire:loading.attr="disabled"
                            class="mt-5 w-full rounded-lg bg-green-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-green-500 disabled:opacity-50"
                        >
                            Dapatkan Gratis
                        </button>
                    @endif
                </aside>
            </div>
        @endif
    @endguest
</div>
