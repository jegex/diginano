<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    /** @var array<int, int> */
    public array $quantities = [];

    public function mount(Product $product): void
    {
        abort_unless($product->is_published, 404);

        $this->product = $product->load('plans');
        $this->quantities = $product->plans->mapWithKeys(
            fn (Plan $plan): array => [$plan->id => 1],
        )->all();
    }

    public function addToCart(int $planId): void
    {
        $this->validate([
            "quantities.{$planId}" => ['required', 'integer', 'min:1'],
        ]);

        $plan = $this->product->plans()->findOrFail($planId);

        if (auth()->guest()) {
            $this->redirectRoute('filament.admin.auth.login');

            return;
        }

        Cart::for(auth()->user())->add($plan, (int) $this->quantities[$planId]);

        $this->redirectRoute('cart');
    }

    public function render(): View
    {
        abort_unless($this->product->is_published, 404);

        return view('livewire.product-detail', [
            'product' => $this->product,
        ]);
    }
}
