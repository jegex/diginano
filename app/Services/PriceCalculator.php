<?php

namespace App\Services;

use App\Enums\PricingScheme;
use App\Enums\UsageAggregation;
use App\Models\Price;
use App\Models\Subscription;

/**
 * The single seam for all price math: scheme totals (standard/package/volume/
 * graduated), pay-what-you-want, lead-magnet free lines, setup fees, and
 * metered usage aggregation. Cart, checkout, and renewal all call this —
 * never reimplement the math elsewhere.
 */
final class PriceCalculator
{
    /**
     * @return array{unit_price: float, line_total: float, setup_fee: float}
     */
    public function calculate(
        Price $price,
        int $quantity,
        ?float $customerAmount = null,
        bool $deferUsageCharges = false,
        bool $includeSetupFee = true,
    ): array {
        if ($price->isPwyw()) {
            $line = round((float) $customerAmount, 2);

            return ['unit_price' => $line, 'line_total' => $line, 'setup_fee' => 0.0];
        }

        if ($price->isFree()) {
            return ['unit_price' => 0.0, 'line_total' => 0.0, 'setup_fee' => 0.0];
        }

        if ($price->isUsageBased() && $deferUsageCharges) {
            $unit = 0.0;
            $line = 0.0;
        } else {
            $line = $this->schemeLineTotal($price, max(1, $quantity));
            $unit = round($line / max(1, $quantity), 2);
        }

        $setup = $includeSetupFee ? $price->setupFeeUsd() : 0.0;

        return [
            'unit_price' => round($unit, 2),
            'line_total' => round($line, 2),
            'setup_fee' => round($setup, 2),
        ];
    }

    /**
     * The billable quantity for a subscription renewal: aggregated metered
     * usage for usage-based prices, otherwise the quantity snapshotted at
     * checkout.
     */
    public function renewalQuantity(Subscription $subscription): int
    {
        $price = $subscription->plan->price;

        if ($price?->isUsageBased()) {
            return $this->usageQuantity($subscription);
        }

        return max(1, $subscription->quantity);
    }

    /**
     * Aggregate the metered usage records of a subscription for its current
     * billing period, according to the price's aggregation mode.
     */
    public function usageQuantity(Subscription $subscription): int
    {
        $mode = $subscription->plan->price?->usage_aggregation;

        if ($mode === null) {
            return 0;
        }

        $records = $subscription->usageRecords()
            ->when($mode !== UsageAggregation::LastEver, fn ($query) => $query->where('recorded_at', '>=', $subscription->starts_at))
            ->orderBy('recorded_at')
            ->get();

        return match ($mode) {
            UsageAggregation::Sum => (int) $records->sum('quantity'),
            UsageAggregation::LastDuringPeriod,
            UsageAggregation::LastEver => (int) ($records->last()?->quantity ?? 0),
            UsageAggregation::Max => (int) ($records->max('quantity') ?? 0),
        };
    }

    private function schemeLineTotal(Price $price, int $quantity): float
    {
        return match ($price->scheme) {
            PricingScheme::Package => $this->packageTotal($price, $quantity),
            PricingScheme::Volume => $this->volumeTotal($price, $quantity),
            PricingScheme::Graduated => $this->graduatedTotal($price, $quantity),
            default => round((float) ($price->unit_price ?? 0) * $quantity, 2),
        };
    }

    private function packageTotal(Price $price, int $quantity): float
    {
        $packages = (int) ceil($quantity / max(1, $price->package_size));

        return round((float) ($price->unit_price ?? 0) * $packages, 2);
    }

    private function volumeTotal(Price $price, int $quantity): float
    {
        $tier = $this->tierFor($price->tiers ?? [], $quantity);

        return round(
            $quantity * ((int) $tier['unit_price'] / 100) + ((int) ($tier['fixed_fee'] ?? 0) / 100),
            2,
        );
    }

    private function graduatedTotal(Price $price, int $quantity): float
    {
        $tiers = $price->tiers ?? [];
        $total = 0.0;
        $previous = 0;
        $fixedFee = 0;

        foreach ($tiers as $index => $tier) {
            if ($index === 0) {
                $fixedFee = (int) ($tier['fixed_fee'] ?? 0);
            }

            $last = $tier['last_unit'] ?? PHP_INT_MAX;
            $units = max(0, min($quantity, $last) - $previous);
            $total += $units * ((int) $tier['unit_price'] / 100);

            if ($quantity <= $last) {
                break;
            }

            $previous = $last;
        }

        return round($total + $fixedFee / 100, 2);
    }

    /**
     * @param  array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>  $tiers
     * @return array{last_unit: int|null, unit_price: int, fixed_fee: int|null}
     */
    private function tierFor(array $tiers, int $quantity): array
    {
        foreach ($tiers as $tier) {
            if ($tier['last_unit'] === null || $quantity <= (int) $tier['last_unit']) {
                return $tier;
            }
        }

        return $tiers[array_key_last($tiers)] ?? ['last_unit' => null, 'unit_price' => 0, 'fixed_fee' => null];
    }
}
