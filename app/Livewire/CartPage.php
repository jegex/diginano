<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Currency;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CartPage extends Component
{
    public string $currency = 'usd';

    public ?string $couponCode = null;

    public ?int $couponId = null;

    /** @var array<int, int> */
    public array $quantities = [];

    public function mount(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->currency = $user->display_currency;
        $this->quantities = $this->cart()->items->mapWithKeys(
            fn (CartItem $item): array => [$item->id => $item->quantity],
        )->all();

        $couponCode = session('applied_coupon_code');

        if ($couponCode !== null) {
            $coupon = Coupon::query()->where('code', $couponCode)->first();

            if ($coupon !== null) {
                $this->couponId = $coupon->id;
                $this->couponCode = $coupon->code;
            }
        }
    }

    public function changeCurrency(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate([
            'currency' => ['required', Rule::in(Currency::query()->enabled()->pluck('code')->all())],
        ]);

        auth()->user()->update(['display_currency' => $this->currency]);
    }

    public function updateQuantity(int $itemId): void
    {
        abort_unless(auth()->check(), 403);

        $quantity = max(1, (int) ($this->quantities[$itemId] ?? 1));
        $item = CartItem::findOrFail($itemId);

        $this->cart()->setQuantity($item, $quantity);
        $this->quantities[$itemId] = $quantity;
    }

    public function removeItem(int $itemId): void
    {
        abort_unless(auth()->check(), 403);

        $item = CartItem::findOrFail($itemId);

        $this->cart()->remove($item);
        unset($this->quantities[$itemId]);
    }

    public function applyCoupon(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate([
            'couponCode' => ['required', 'string', 'max:50'],
        ]);

        $coupon = Coupon::query()->where('code', strtoupper($this->couponCode))->first();

        if ($coupon === null) {
            $this->addError('couponCode', 'Kode kupon tidak ditemukan.');

            return;
        }

        $this->couponId = $coupon->id;
        session(['applied_coupon_code' => $coupon->code]);
    }

    public function removeCoupon(): void
    {
        abort_unless(auth()->check(), 403);

        $this->couponId = null;
        $this->couponCode = null;
        session()->forget('applied_coupon_code');
    }

    public function render(): View
    {
        $user = auth()->user();
        $cart = $user !== null ? Cart::for($user)->load('items.plan.product') : null;
        $coupon = $this->couponId !== null ? Coupon::find($this->couponId) : null;

        return view('livewire.cart-page', [
            'cart' => $cart,
            'coupon' => $coupon,
            'currencies' => Currency::query()->enabled()->orderByRaw('is_default DESC, code ASC')->get(),
        ]);
    }

    private function cart(): Cart
    {
        return Cart::for(auth()->user());
    }
}
