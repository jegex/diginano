<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::AwaitingConfirmation => 'Menunggu konfirmasi',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this === self::AwaitingConfirmation;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }
}
