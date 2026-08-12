<?php

namespace App\Models;

use App\DisplayCurrency;
use Database\Factories\ExchangeRateFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $currency
 * @property string $rate
 *
 * @method static ExchangeRateFactory factory()
 */
#[Fillable(['currency', 'rate'])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => DisplayCurrency::class,
            'rate' => 'decimal:6',
        ];
    }

    public static function rateFor(DisplayCurrency $currency): float
    {
        if ($currency === DisplayCurrency::Usd) {
            return 1.0;
        }

        $rate = static::query()->where('currency', $currency->value)->value('rate');

        if ($rate === null) {
            throw new DomainException("No exchange rate configured for {$currency->value}.");
        }

        return (float) $rate;
    }

    public static function convert(float $usdAmount, DisplayCurrency $currency): float
    {
        return $usdAmount * static::rateFor($currency);
    }
}
