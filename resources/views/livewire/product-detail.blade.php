<div>
    @php($currency = \App\Models\Currency::fromCode(auth()->user()?->display_currency ?? '') ?? \App\Models\Currency::default())

    <nav class="text-sm text-gray-500">
        <a href="{{ route('catalog') }}" class="hover:text-gray-900">Katalog</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">{{ $product->name }}</span>
    </nav>

    <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
        <div>
            <x-product-image :product="$product" height="h-64" class="rounded-xl" />
        </div>

        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $product->name }}</h1>
            <p class="mt-4 leading-relaxed text-gray-600">{{ $product->description }}</p>

            <h2 class="mt-8 text-lg font-semibold">Pilih Plan</h2>
            <div class="mt-4 space-y-4">
                @foreach ($product->plans as $plan)
                    <div class="rounded-xl border border-gray-200 p-5">
                        <div class="flex items-baseline justify-between gap-4">
                            <h3 class="font-semibold">{{ $plan->name }}</h3>
                            <p class="text-lg font-semibold">
                                @if ($plan->isOnSale())
                                    <span class="mr-2 text-sm text-gray-400 line-through">
                                        {{ $currency->format($currency->convertUsd($plan->price)) }}
                                    </span>
                                @endif
                                {{ $currency->format($currency->convertUsd($plan->effectivePriceUsd())) }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            @if ($plan->pricing_mode === \App\Enums\PlanPricing::Subscription)
                                Per {{ $plan->periodLabel() }}
                            @else
                                Bayar sekali — berlaku selamanya
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $plan->licenses_per_unit }} lisensi per unit
                        </p>
                        <div class="mt-4 flex items-center gap-3">
                            <input
                                type="number"
                                min="1"
                                value="1"
                                wire:model="quantities.{{ $plan->id }}"
                                class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                aria-label="Jumlah untuk {{ $plan->name }}"
                            >
                            <button
                                type="button"
                                wire:click="addToCart({{ $plan->id }})"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                            >
                                Tambah ke keranjang
                            </button>
                            @error("quantities.{$plan->id}")
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
