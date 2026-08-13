<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Konfirmasi pesanan {$this->order->number}")
            ->greeting('Halo '.$this->order->user->name.',')
            ->line("Pesanan Anda {$this->order->number} telah kami terima dengan total {$this->order->displayCurrency()->format($this->order->totalInDisplay())}.")
            ->line('Anda dapat mengunduh produk Anda kapan saja dari pusat unduhan.')
            ->action('Buka Pusat Unduhan', route('downloads'))
            ->line('Terima kasih telah berbelanja di Diginano!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'number' => $this->order->number,
        ];
    }
}
