<div>
    <h1 class="text-2xl font-semibold tracking-tight">Pesanan {{ $order->number }}</h1>

    <p class="mt-2 text-sm text-gray-600">
        Status:
        <span class="font-medium {{ $order->isPaid() ? 'text-green-600' : 'text-amber-600' }}">
            {{ $order->statusLabel() }}
        </span>
    </p>

    <div class="mt-6 rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Metode pembayaran</p>
        <p class="mt-1 font-medium">{{ $order->paymentMethod?->name ?? 'Belum ditentukan' }}</p>
    </div>

    <div class="mt-4 rounded-xl border border-gray-200 p-5">
        <div class="space-y-4">
            @foreach ($order->items as $item)
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="font-semibold">{{ $item->name }}</h2>
                        <p class="text-sm text-gray-500">Jumlah: {{ $item->quantity }}</p>
                    </div>
                    <p class="font-semibold">
                        {{ $order->currency->format((float) $item->line_total_usd * (float) $order->exchange_rate) }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 space-y-1 border-t border-gray-100 pt-4 text-sm">
            <p class="flex justify-between text-gray-500">
                <span>Subtotal</span>
                <span>{{ $order->currency->format($order->subtotalInDisplay()) }}</span>
            </p>
            @if ((float) $order->discount_usd > 0)
                <p class="flex justify-between text-green-600">
                    <span>Diskon</span>
                    <span>-{{ $order->currency->format($order->discountInDisplay()) }}</span>
                </p>
            @endif
            <p class="flex justify-between text-base font-semibold">
                <span>Total</span>
                <span>{{ $order->currency->format($order->totalInDisplay()) }}</span>
            </p>
        </div>
    </div>

    <p class="mt-6 text-sm {{ $order->isPaid() ? 'text-green-600' : 'text-amber-600' }}">
        {{ $order->isPaid() ? 'Pesanan ini sudah dibayar.' : 'Pesanan ini belum dibayar.' }}
    </p>
</div>
