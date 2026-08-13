<?php

namespace App\Enums;

enum LicenseLengthUnit: string
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Hari',
            self::Month => 'Bulan',
            self::Year => 'Tahun',
        };
    }
}
