<?php

namespace App\Livewire;

use App\DisplayCurrency;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CartPage extends Component
{
    public string $currency = DisplayCurrency::Usd->value;

    /** @var array<int, int> */
    public array $quantities = [];

    public function mount(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->currency = $user->display_currency->value;
        $this->quantities = $this->cart()->items->mapWithKeys(
            fn (CartItem $item): array => [$item->id => $item->quantity],
        )->all();
    }

    public function changeCurrency(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate([
            'currency' => ['required', Rule::enum(DisplayCurrency::class)],
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

    public function render(): View
    {
        $user = auth()->user();
        $cart = $user !== null ? Cart::for($user)->load('items.plan.product') : null;

        return view('livewire.cart-page', [
            'cart' => $cart,
            'currencies' => DisplayCurrency::cases(),
        ]);
    }

    private function cart(): Cart
    {
        return Cart::for(auth()->user());
    }
}
