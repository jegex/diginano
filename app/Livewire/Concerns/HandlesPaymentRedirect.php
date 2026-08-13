<?php

namespace App\Livewire\Concerns;

use App\Enums\PaymentMethodType;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\CryptomusGateway;
use App\Services\MidtransGateway;

trait HandlesPaymentRedirect
{
    /**
     * Redirect the customer to the correct payment flow for the Order.
     */
    protected function redirectForPayment(Order $order, PaymentMethod $paymentMethod): void
    {
        if ($paymentMethod->type === PaymentMethodType::Midtrans) {
            $this->redirectToSnap($order, $paymentMethod);

            return;
        }

        if ($paymentMethod->type === PaymentMethodType::Cryptomus) {
            $this->redirectToCryptomus($order, $paymentMethod);

            return;
        }

        $this->redirectRoute('orders.show', $order);
    }

    private function redirectToSnap(Order $order, PaymentMethod $paymentMethod): void
    {
        try {
            $snap = (new MidtransGateway($paymentMethod))->createTransaction($order);

            $order->update([
                'snap_token' => $snap['token'],
                'snap_redirect_url' => $snap['redirect_url'],
            ]);

            $this->redirect($snap['redirect_url']);
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Gagal membuat sesi pembayaran Midtrans. Silakan coba lagi.');

            $this->redirectRoute('orders.show', $order);
        }
    }

    private function redirectToCryptomus(Order $order, PaymentMethod $paymentMethod): void
    {
        try {
            $invoice = (new CryptomusGateway($paymentMethod))->createInvoice($order);

            $order->update([
                'provider_reference' => $invoice['uuid'],
                'provider_status' => $invoice['payment_status'],
            ]);

            $this->redirect($invoice['url']);
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Gagal membuat sesi pembayaran Cryptomus. Silakan coba lagi.');

            $this->redirectRoute('orders.show', $order);
        }
    }
}
