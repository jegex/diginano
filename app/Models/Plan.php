<?php

namespace App\Models;

use App\BillingPeriod;
use App\PlanPricing;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property PlanPricing $pricing_mode
 * @property string $price
 * @property BillingPeriod|null $billing_period
 * @property int $licenses_per_unit
 *
 * @method static PlanFactory factory()
 */
#[Fillable(['product_id', 'name', 'pricing_mode', 'price', 'billing_period', 'licenses_per_unit'])]
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
            'price' => 'decimal:2',
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
}
