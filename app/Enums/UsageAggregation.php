<?php

namespace App\Enums;

enum UsageAggregation: string
{
    case Sum = 'sum';
    case LastDuringPeriod = 'last_during_period';
    case LastEver = 'last_ever';
    case Max = 'max';

    public function label(): string
    {
        return match ($this) {
            self::Sum => 'Total dalam periode',
            self::LastDuringPeriod => 'Nilai terakhir dalam periode',
            self::LastEver => 'Nilai terakhir (selamanya)',
            self::Max => 'Nilai tertinggi dalam periode',
        };
    }
}
