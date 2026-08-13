<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts a monetary value stored as integer cents into a float amount and
 * back. Reads divide by 100 (e.g. 1999 cents -> 19.99). Writes multiply by
 * 100, tolerate float noise around the cent grid, and reject values that are
 * meaningfully off it (e.g. 19.995).
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((int) $value / 100, 2);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cents = (float) $value * 100;
        $rounded = round($cents);

        if (abs($cents - $rounded) > 0.01) {
            throw new InvalidArgumentException("The {$key} value must be on the cent grid, got {$value}.");
        }

        return (int) $rounded;
    }
}
