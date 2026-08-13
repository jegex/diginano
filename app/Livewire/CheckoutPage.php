<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesPaymentRedirect;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CheckoutPage extends Component
{
    use HandlesPaymentRedirect;

    public string $currency = 'usd';

    public ?int $paymentMethodId = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->currency = $user->display_currency;

        $firstEnabled = PaymentMethod::query()->enabled()->orderBy('id')->value('id');

        if ($firstEnabled !== null) {
            $this->paymentMethodId = (int) $firstEnabled;
        }
    }

    public function checkout(): void
    {
        abort_unless(auth()->check(), 403);

        $cart = $this->cart();

        if ($cart->isEmpty()) {
            $this->redirectRoute('cart');

            return;
        }

        abort_if($cart->totalUsd($this->couponFromSession()) <= 0, 422, 'Pesanan gratis lewat tombol khusus.');

        $this->validate([
            'paymentMethodId' => [
                'required',
                Rule::exists('payment_methods', 'id')->where('is_enabled', true),
            ],
        ]);

        $paymentMethod = PaymentMethod::query()->enabled()->findOrFail($this->paymentMethodId);

        $coupon = $this->couponFromSession();

        $order = Order::checkout($cart, $paymentMethod, $coupon);

        session()->forget('applied_coupon_code');

        $this->redirectForPayment($order, $paymentMethod);
    }

    public function checkoutFree(): void
    {
        abort_unless(auth()->check(), 403);

        $cart = $this->cart();

        if ($cart->isEmpty()) {
            $this->redirectRoute('cart');

            return;
        }

        abort_if($cart->totalUsd($this->couponFromSession()) > 0, 422, 'Pesanan ini tidak gratis.');

        $order = Order::freeCheckout($cart);

        session()->forget('applied_coupon_code');

        $this->redirectRoute('orders.show', $order);
    }

    public function render(): View
    {
        $user = auth()->user();
        $cart = $user !== null ? Cart::for($user)->load('items.plan.product') : null;

        return view('livewire.checkout-page', [
            'cart' => $cart,
            'coupon' => $this->couponFromSession(),
            'paymentMethods' => PaymentMethod::query()->enabled()->get(),
            'currencies' => Currency::query()->enabled()->orderByRaw('is_default DESC, code ASC')->get(),
        ]);
    }

    private function cart(): Cart
    {
        return Cart::for(auth()->user());
    }

    private function couponFromSession(): ?Coupon
    {
        $code = session('applied_coupon_code');

        if ($code === null) {
            return null;
        }

        return Coupon::query()->where('code', $code)->first();
    }
}
