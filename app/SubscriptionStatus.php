<?php

namespace App;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::PastDue => 'Tenggang waktu',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
