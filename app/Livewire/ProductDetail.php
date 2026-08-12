<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->is_published, 404);

        $this->product = $product->load('plans');
    }

    public function render(): View
    {
        abort_unless($this->product->is_published, 404);

        return view('livewire.product-detail', [
            'product' => $this->product,
        ]);
    }
}
