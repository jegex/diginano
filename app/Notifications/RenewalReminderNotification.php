<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription) {}

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
            ->subject("Perpanjangan langganan {$this->subscription->plan->name} diperlukan")
            ->greeting('Halo '.$this->subscription->user->name.',')
            ->line("Langganan {$this->subscription->plan->name} Anda memasuki masa tenggang.")
            ->line('Perpanjang sebelum '.$this->subscription->grace_ends_at?->format('d M Y H:i').' agar lisensi tetap aktif.')
            ->action('Perpanjang Sekarang', route('subscriptions'))
            ->line('Jika tidak diperpanjang, lisensi Anda akan dinonaktifkan setelah masa tenggang berakhir.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
        ];
    }
}
