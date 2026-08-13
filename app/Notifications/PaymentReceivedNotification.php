<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
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
            ->subject("Pembayaran diterima — {$this->order->number}")
            ->greeting('Halo '.$this->order->user->name.',')
            ->line("Pembayaran untuk pesanan {$this->order->number} sebesar {$this->order->displayCurrency()->format($this->order->totalInDisplay())} telah kami terima.")
            ->line('Lisensi Anda sudah diterbitkan dan dapat digunakan sekarang.')
            ->action('Lihat Resi', route('orders.show', $this->order))
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
