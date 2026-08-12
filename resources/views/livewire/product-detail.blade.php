<div>
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
                            <p class="text-lg font-semibold">${{ number_format($plan->price, 2) }}</p>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            @if ($plan->pricing_mode === \App\PlanPricing::Subscription)
                                Per {{ $plan->periodLabel() }}
                            @else
                                Bayar sekali — berlaku selamanya
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $plan->licenses_per_unit }} lisensi per unit
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
