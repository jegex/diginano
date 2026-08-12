<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Catalog extends Component
{
    public function render(): View
    {
        return view('livewire.catalog', [
            'products' => Product::query()
                ->published()
                ->with('plans')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
