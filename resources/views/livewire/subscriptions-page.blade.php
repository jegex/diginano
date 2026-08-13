<div>
    <h1 class="text-2xl font-semibold tracking-tight">Langganan</h1>

    @guest
        <p class="mt-4 text-gray-600">
            Masuk untuk melihat langganan Anda.
            <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-gray-900 underline">Masuk</a>
        </p>
    @else
        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($subscriptions->isEmpty())
            <p class="mt-6 text-gray-500">Anda belum memiliki langganan.</p>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($subscriptions as $subscription)
                    <div wire:key="subscription-{{ $subscription->id }}"
                         class="rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <h2 class="font-semibold">{{ $subscription->plan->name }}</h2>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                        {{ $subscription->status->label() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $subscription->plan->product->name }}</p>
                                <p class="mt-2 text-sm text-gray-600">
                                    Periode berakhir: {{ $subscription->periodEndLabel() }}
                                </p>
                                @if ($subscription->isInGrace())
                                    <p class="mt-1 text-sm text-amber-600">
                                        Masa tenggang berakhir: {{ $subscription->grace_ends_at?->format('d M Y') }}
                                    </p>
                                @endif
                                @if ($subscription->isCancelled())
                                    <p class="mt-1 text-sm text-gray-500">Perpanjangan dihentikan.</p>
                                @endif
                            </div>

                            @if ($subscription->isRenewable())
                                <div class="flex shrink-0 gap-2">
                                    <button
                                        type="button"
                                        wire:click="startRenew({{ $subscription->id }})"
                                        class="rounded-lg border border-gray-900 px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-gray-50"
                                    >
                                        Perpanjang
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancel({{ $subscription->id }})"
                                        wire:confirm="Batalkan langganan ini setelah periode berjalan berakhir?"
                                        class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                    >
                                        Batalkan
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($renewingSubscriptionId === $subscription->id)
                            <div class="mt-4 rounded-lg border border-gray-200 p-4">
                                <p class="text-sm font-medium">Pilih metode pembayaran untuk perpanjangan</p>
                                <div class="mt-3 space-y-2">
                                    @forelse ($paymentMethods as $method)
                                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                                            <input
                                                type="radio"
                                                name="payment_method_{{ $subscription->id }}"
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
                                <button
                                    type="button"
                                    wire:click="pay"
                                    wire:loading.attr="disabled"
                                    class="mt-4 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                                >
                                    Bayar &amp; Perpanjang
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endguest
</div>
