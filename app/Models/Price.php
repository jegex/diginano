<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\RenewalIntervalUnit;
use App\Enums\TrialIntervalUnit;
use App\Enums\UsageAggregation;
use Database\Factories\PriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plan_id
 * @property PriceCategory $category
 * @property PricingScheme $scheme
 * @property UsageAggregation|null $usage_aggregation
 * @property float|null $unit_price
 * @property bool $setup_fee_enabled
 * @property float|null $setup_fee
 * @property int $package_size
 * @property array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>|null $tiers
 * @property RenewalIntervalUnit|null $renewal_interval_unit
 * @property int|null $renewal_interval_quantity
 * @property TrialIntervalUnit|null $trial_interval_unit
 * @property int|null $trial_interval_quantity
 * @property float|null $min_price
 * @property float|null $suggested_price
 *
 * @method static PriceFactory factory()
 */
#[Fillable(['plan_id', 'category', 'scheme', 'usage_aggregation', 'unit_price', 'setup_fee_enabled', 'setup_fee', 'package_size', 'tiers', 'renewal_interval_unit', 'renewal_interval_quantity', 'trial_interval_unit', 'trial_interval_quantity', 'min_price', 'suggested_price'])]
class Price extends Model
{
    /** @use HasFactory<PriceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PriceCategory::class,
            'scheme' => PricingScheme::class,
            'usage_aggregation' => UsageAggregation::class,
            'unit_price' => MoneyCast::class,
            'setup_fee_enabled' => 'boolean',
            'setup_fee' => MoneyCast::class,
            'package_size' => 'integer',
            'tiers' => 'array',
            'renewal_interval_unit' => RenewalIntervalUnit::class,
            'renewal_interval_quantity' => 'integer',
            'trial_interval_unit' => TrialIntervalUnit::class,
            'trial_interval_quantity' => 'integer',
            'min_price' => MoneyCast::class,
            'suggested_price' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isSubscription(): bool
    {
        return $this->category->isSubscription();
    }

    public function isOneTime(): bool
    {
        return $this->category->isOneTime();
    }

    public function isFree(): bool
    {
        return $this->category->isFree();
    }

    public function isPwyw(): bool
    {
        return $this->category->isPwyw();
    }

    /**
     * Metered billing is enabled when an aggregation mode is set.
     */
    public function isUsageBased(): bool
    {
        return $this->usage_aggregation !== null;
    }

    /**
     * The one-time setup fee charged on the first subscription checkout.
     */
    public function setupFeeUsd(): float
    {
        if (! $this->isSubscription() || ! $this->setup_fee_enabled) {
            return 0.0;
        }

        return (float) $this->setup_fee;
    }

    public function periodLabel(): string
    {
        $quantity = $this->renewal_interval_quantity ?? 1;

        return match ($this->renewal_interval_unit) {
            RenewalIntervalUnit::Day => $quantity === 1 ? 'hari' : "{$quantity} hari",
            RenewalIntervalUnit::Week => $quantity === 1 ? 'minggu' : "{$quantity} minggu",
            RenewalIntervalUnit::Month => $quantity === 1 ? 'bulan' : "{$quantity} bulan",
            RenewalIntervalUnit::Year => $quantity === 1 ? 'tahun' : "{$quantity} tahun",
            default => 'periode',
        };
    }

    public function periodEndsAt(Carbon $from): Carbon
    {
        $quantity = $this->renewal_interval_quantity ?? 1;

        return match ($this->renewal_interval_unit) {
            RenewalIntervalUnit::Day => $from->copy()->addDays($quantity),
            RenewalIntervalUnit::Week => $from->copy()->addWeeks($quantity),
            RenewalIntervalUnit::Month => $from->copy()->addMonths($quantity),
            RenewalIntervalUnit::Year => $from->copy()->addYears($quantity),
            default => $from->copy(),
        };
    }
}
