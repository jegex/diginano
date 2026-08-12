@props(['product', 'height' => 'h-40'])
<div {{ $attributes->merge(['class' => 'flex items-center justify-center overflow-hidden bg-gray-100 ' . $height]) }}>
    @if ($product->image)
        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
    @else
        <span class="text-4xl text-gray-300">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
    @endif
</div>
