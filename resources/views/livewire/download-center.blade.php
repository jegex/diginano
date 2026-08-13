<div>
    <h1 class="text-2xl font-semibold tracking-tight">Pusat Unduhan</h1>

    @guest
        <p class="mt-4 text-gray-600">
            Masuk untuk mengakses unduhan Anda.
            <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-gray-900 underline">Masuk</a>
        </p>
    @else
        @if ($entries->isEmpty())
            <p class="mt-6 text-gray-500">Belum ada produk yang bisa Anda unduh.</p>
        @else
            <div class="mt-6 grid gap-4">
                @foreach ($entries as $entry)
                    @php($product = $entry['product'])
                    @php($release = $entry['latestRelease'])
                    <div wire:key="product-{{ $product->id }}"
                         class="rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="font-semibold">{{ $product->name }}</h2>
                                @if ($release !== null)
                                    <p class="mt-1 text-sm text-gray-500">Versi terbaru: {{ $release->version }}</p>
                                    @if ($release->changelog !== null)
                                        <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $release->changelog }}</p>
                                    @endif
                                @else
                                    <p class="mt-1 text-sm text-gray-500">Belum ada rilis untuk produk ini.</p>
                                @endif
                            </div>

                            @if ($release !== null && $release->hasFile())
                                <button
                                    type="button"
                                    wire:click="download({{ $release->id }})"
                                    wire:loading.attr="disabled"
                                    class="shrink-0 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                                >
                                    Unduh
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endguest
</div>
