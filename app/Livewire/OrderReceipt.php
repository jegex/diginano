<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderReceipt extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load('items.product', 'paymentMethod');
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user !== null && $this->order->user_id === $user->id, 403);

        return view('livewire.order-receipt', [
            'order' => $this->order,
        ]);
    }
}
