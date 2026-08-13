<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $exchange_rate
 * @property int $decimal_places
 * @property bool $is_enabled
 * @property bool $is_default
 *
 * @method static CurrencyFactory factory()
 */
#[Fillable(['code', 'name', 'symbol', 'exchange_rate', 'decimal_places', 'is_enabled', 'is_default'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'decimal_places' => 'integer',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Currency $currency): void {
            if ($currency->is_default) {
                $currency->exchange_rate = 1;
                $currency->is_enabled = true;
                static::query()->whereKeyNot($currency->getKey())->update(['is_default' => false]);
            }
        });

        static::deleting(function (Currency $currency): void {
            if ($currency->is_default) {
                throw new DomainException('The default currency cannot be deleted.');
            }
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * The base currency: what prices are stored in. It is the single enabled
     * currency with is_default set, so it is always the fallback for guests
     * and users without a stored preference.
     */
    public static function default(): Currency
    {
        return once(function () {
            $currency = static::query()->where('is_default', true)->first();

            if ($currency === null) {
                throw new DomainException('No default currency configured.');
            }

            return $currency;
        });
    }

    public static function fromCode(string $code): ?Currency
    {
        return static::query()->where('code', strtolower($code))->first();
    }

    public static function required(string $code): Currency
    {
        $currency = static::fromCode($code);

        if ($currency === null) {
            throw new DomainException("No currency configured for {$code}.");
        }

        return $currency;
    }

    public function rate(): float
    {
        return (float) $this->exchange_rate;
    }

    public function convertUsd(float $usdAmount): float
    {
        return $usdAmount * $this->rate();
    }

    /**
     * Format an amount in this currency's own units (not USD) for display.
     */
    public function format(float $amount): string
    {
        if ($this->decimal_places === 0) {
            return $this->symbol.' '.number_format($amount, 0, ',', '.');
        }

        return $this->symbol.number_format($amount, $this->decimal_places);
    }
}
