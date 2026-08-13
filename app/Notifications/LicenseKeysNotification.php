<?php

namespace App\Notifications;

use App\Models\License;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseKeysNotification extends Notification implements ShouldQueue
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
        $keys = $this->order->licenses
            ->groupBy(fn (License $license): string => $license->product->name)
            ->map(fn ($group, string $product): string => $product.': '.$group->implode('key', ', '))
            ->join("\n");

        return (new MailMessage)
            ->subject("Kunci lisensi Anda — {$this->order->number}")
            ->greeting('Halo '.$this->order->user->name.',')
            ->line('Kunci lisensi untuk pesanan Anda telah diterbitkan:')
            ->line($keys)
            ->line('Simpan kunci ini dengan aman. Setiap kunci dapat diaktifkan sesuai batas yang berlaku.')
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
            'keys' => $this->order->licenses->pluck('key')->all(),
        ];
    }
}
