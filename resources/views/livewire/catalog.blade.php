<div>
    <h1 class="text-2xl font-semibold tracking-tight">Katalog Produk</h1>

    @if ($products->isEmpty())
        <p class="mt-4 text-gray-500">Belum ada produk yang tersedia.</p>
    @else
        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <a href="{{ route('product-detail', $product) }}"
                   class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 transition hover:border-gray-300">
                    <x-product-image :product="$product" />
                    <div class="flex flex-1 flex-col gap-2 p-5">
                        <h2 class="font-semibold">{{ $product->name }}</h2>
                        <p class="line-clamp-2 text-sm text-gray-600">{{ $product->description }}</p>
                        <p class="mt-auto pt-2 text-sm font-medium text-gray-500">
                            {{ $product->plans->count() }} {{ $product->plans->count() === 1 ? 'plan' : 'plans' }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
