<?php

namespace App\Enums;

enum PricingScheme: string
{
    case Standard = 'standard';
    case Package = 'package';
    case Volume = 'volume';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standar',
            self::Package => 'Paket',
            self::Volume => 'Volume',
            self::Graduated => 'Bertingkat',
        };
    }
}
