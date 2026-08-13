<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\BillingPeriod;
use App\Enums\PlanPricing;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property PlanPricing $pricing_mode
 * @property float $price
 * @property float|null $sale_price
 * @property Carbon|null $sale_starts_at
 * @property Carbon|null $sale_ends_at
 * @property BillingPeriod|null $billing_period
 * @property int $licenses_per_unit
 *
 * @method static PlanFactory factory()
 */
#[Fillable(['product_id', 'name', 'pricing_mode', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'billing_period', 'licenses_per_unit'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_mode' => PlanPricing::class,
            'billing_period' => BillingPeriod::class,
            'price' => MoneyCast::class,
            'sale_price' => MoneyCast::class,
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'licenses_per_unit' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function periodLabel(): string
    {
        return match ($this->billing_period) {
            BillingPeriod::Monthly => 'bulan',
            BillingPeriod::Quarterly => 'kuartal',
            BillingPeriod::Yearly => 'tahun',
            default => 'periode',
        };
    }

    public function isOnSale(?Carbon $now = null): bool
    {
        if ($this->sale_price === null) {
            return false;
        }

        $now ??= now();

        if ($this->sale_starts_at !== null && $this->sale_starts_at->gt($now)) {
            return false;
        }

        if ($this->sale_ends_at !== null && $this->sale_ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    public function effectivePriceUsd(): float
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    public function periodEndsAt(Carbon $from): Carbon
    {
        return match ($this->billing_period) {
            BillingPeriod::Monthly => $from->copy()->addMonth(),
            BillingPeriod::Quarterly => $from->copy()->addMonths(3),
            BillingPeriod::Yearly => $from->copy()->addYear(),
            default => $from->copy(),
        };
    }
}
