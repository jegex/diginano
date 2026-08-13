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

        @if ($order->status->isAwaitingConfirmation() && $order->isManualPayment())
            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">Transfer ke rekening berikut</p>
                <dl class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Bank</dt>
                        <dd class="font-medium">{{ $bankDetails['bank_name'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Atas nama</dt>
                        <dd class="font-medium">{{ $bankDetails['account_name'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">No. Rekening</dt>
                        <dd class="font-medium">{{ $bankDetails['account_number'] }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <dt class="font-medium text-gray-900">Total yang harus dibayar</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            {{ $order->settlementCurrency()->format($order->settlementAmount()) }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($order->proofs->isNotEmpty())
                <div class="mt-4">
                    <p class="text-sm text-gray-500">Bukti pembayaran yang sudah diunggah: {{ $order->proofs->count() }}</p>
                </div>
            @endif

            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-900">Upload bukti pembayaran</p>
                <p class="mt-1 text-sm text-gray-500">Setelah transfer, unggah bukti transfer Anda untuk dikonfirmasi admin.</p>

                <form wire:submit="submitProof" class="mt-3 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="file"
                        wire:model="proof"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white"
                    >
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="shrink-0 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                    >
                        Unggah
                    </button>
                </form>

                @error('proof')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($order->status === \App\OrderStatus::Pending && ($order->isMidtransPayment() || $order->isCryptomusPayment()))
            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                <p class="text-sm text-gray-500">
                    Pesanan masih menunggu pembayaran. Klik tombol di bawah untuk melanjutkan pembayaran.
                </p>
                <button
                    type="button"
                    wire:click="pay"
                    wire:loading.attr="disabled"
                    class="mt-3 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                >
                    Bayar Sekarang
                </button>
            </div>
        @endif
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
                        {{ $order->displayCurrency()->format((float) $item->line_total_usd * (float) $order->exchange_rate) }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 space-y-1 border-t border-gray-100 pt-4 text-sm">
            <p class="flex justify-between text-gray-500">
                <span>Subtotal</span>
                <span>{{ $order->displayCurrency()->format($order->subtotalInDisplay()) }}</span>
            </p>
            @if ((float) $order->discount_usd > 0)
                <p class="flex justify-between text-green-600">
                    <span>Diskon</span>
                    <span>-{{ $order->displayCurrency()->format($order->discountInDisplay()) }}</span>
                </p>
            @endif
            <p class="flex justify-between text-base font-semibold">
                <span>Total</span>
                <span>{{ $order->displayCurrency()->format($order->totalInDisplay()) }}</span>
            </p>
        </div>
    </div>

    <p class="mt-6 text-sm {{ $order->isPaid() ? 'text-green-600' : 'text-amber-600' }}">
        {{ $order->isPaid() ? 'Pesanan ini sudah dibayar.' : 'Pesanan ini belum dibayar.' }}
    </p>
</div>
