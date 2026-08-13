<?php

namespace App\Enums;

enum PriceCategory: string
{
    case OneTime = 'one_time';
    case Subscription = 'subscription';
    case LeadMagnet = 'lead_magnet';
    case Pwyw = 'pwyw';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'Sekali bayar',
            self::Subscription => 'Berlangganan',
            self::LeadMagnet => 'Lead magnet (gratis)',
            self::Pwyw => 'Bayar sesuka Anda',
        };
    }

    public function isSubscription(): bool
    {
        return $this === self::Subscription;
    }

    public function isOneTime(): bool
    {
        return $this === self::OneTime;
    }

    public function isFree(): bool
    {
        return $this === self::LeadMagnet;
    }

    public function isPwyw(): bool
    {
        return $this === self::Pwyw;
    }
}
