<?php

namespace App;

enum DisplayCurrency: string
{
    case Usd = 'usd';
    case Idr = 'idr';
    case Eur = 'eur';

    public function label(): string
    {
        return match ($this) {
            self::Usd => 'USD (US Dollar)',
            self::Idr => 'IDR (Rupiah)',
            self::Eur => 'EUR (Euro)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Usd => '$',
            self::Idr => 'Rp',
            self::Eur => '€',
        };
    }

    public function format(float $amount): string
    {
        return match ($this) {
            self::Idr => $this->symbol().' '.number_format($amount, 0, ',', '.'),
            default => $this->symbol().number_format($amount, 2),
        };
    }
}
