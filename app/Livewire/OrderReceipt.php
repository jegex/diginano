<?php

namespace App\Livewire;

use App\Models\Order;
use App\OrderStatus;
use App\Services\CryptomusGateway;
use App\Services\MidtransGateway;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class OrderReceipt extends Component
{
    use WithFileUploads;

    public Order $order;

    #[Validate(['proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'])]
    public $proof = null;

    public function mount(Order $order): void
    {
        $this->order = $order->load('items.product', 'paymentMethod', 'proofs');
    }

    public function submitProof(): void
    {
        abort_unless($this->canUploadProof(), 403);

        $this->validate();

        $path = $this->proof->store('proofs', 'public');

        $this->order->proofs()->create([
            'file_path' => $path,
            'original_name' => $this->proof->getClientOriginalName(),
            'submitted_at' => now(),
        ]);

        $this->proof = null;
        $this->order->load('proofs');
    }

    public function canUploadProof(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $this->order->user_id === $user->id
            && $this->order->status->isAwaitingConfirmation()
            && $this->order->isManualPayment();
    }

    public function pay(): void
    {
        abort_unless($this->canPay(), 403);

        try {
            if ($this->order->isMidtransPayment()) {
                $this->payMidtrans();

                return;
            }

            $this->payCryptomus();
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Gagal membuat sesi pembayaran. Silakan coba lagi.');
        }
    }

    private function payMidtrans(): void
    {
        $snap = (new MidtransGateway($this->order->paymentMethod))->createTransaction($this->order);

        $this->order->update([
            'snap_token' => $snap['token'],
            'snap_redirect_url' => $snap['redirect_url'],
        ]);

        $this->redirect($snap['redirect_url']);
    }

    private function payCryptomus(): void
    {
        $invoice = (new CryptomusGateway($this->order->paymentMethod))->createInvoice($this->order);

        $this->order->update([
            'provider_reference' => $invoice['uuid'],
            'provider_status' => $invoice['payment_status'],
        ]);

        $this->redirect($invoice['url']);
    }

    public function canPay(): bool
    {
        $user = auth()->user();

        if ($user === null || $this->order->user_id !== $user->id || $this->order->status !== OrderStatus::Pending) {
            return false;
        }

        return $this->order->isMidtransPayment() || $this->order->isCryptomusPayment();
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user !== null && $this->order->user_id === $user->id, 403);

        return view('livewire.order-receipt', [
            'order' => $this->order,
            'bankDetails' => $this->order->paymentMethod?->bankDetails(),
        ]);
    }
}
