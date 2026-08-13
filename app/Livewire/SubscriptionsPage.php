<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesPaymentRedirect;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubscriptionsPage extends Component
{
    use HandlesPaymentRedirect;

    public ?int $renewingSubscriptionId = null;

    public ?int $paymentMethodId = null;

    public function mount(): void
    {
        $this->paymentMethodId = (int) PaymentMethod::query()->enabled()->orderBy('id')->value('id');
    }

    public function startRenew(int $subscriptionId): void
    {
        abort_unless(auth()->check(), 403);

        $this->renewingSubscriptionId = $this->subscription($subscriptionId)->id;
    }

    public function pay(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless($this->renewingSubscriptionId !== null, 422, 'Pilih langganan yang akan diperpanjang.');

        $this->validate([
            'paymentMethodId' => [
                'required',
                Rule::exists('payment_methods', 'id')->where('is_enabled', true),
            ],
        ]);

        $subscription = $this->subscription($this->renewingSubscriptionId);
        $paymentMethod = PaymentMethod::query()->enabled()->findOrFail($this->paymentMethodId);

        $order = Order::renewal($subscription, $paymentMethod);

        $this->renewingSubscriptionId = null;

        $this->redirectForPayment($order, $paymentMethod);
    }

    public function cancel(int $subscriptionId): void
    {
        abort_unless(auth()->check(), 403);

        $this->subscription($subscriptionId)->cancel();

        session()->flash('status', 'Langganan akan berhenti setelah periode berjalan berakhir.');
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user !== null, 403);

        $subscriptions = $user->subscriptions()
            ->with('plan.product')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.subscriptions-page', [
            'subscriptions' => $subscriptions,
            'paymentMethods' => PaymentMethod::query()->enabled()->get(),
        ]);
    }

    private function subscription(int $subscriptionId): Subscription
    {
        return auth()->user()->subscriptions()->findOrFail($subscriptionId);
    }
}
