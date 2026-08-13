<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Services\PriceCalculator;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $plan_id
 * @property int $quantity
 * @property float|null $custom_amount
 * @property Plan $plan
 *
 * @method static CartItemFactory factory()
 */
#[Fillable(['cart_id', 'plan_id', 'quantity', 'custom_amount'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'custom_amount' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * The amount the customer chose for a pay-what-you-want plan, falling back
     * to the suggested price. Zero for every other pricing model.
     */
    public function amountUsd(): float
    {
        if (! $this->plan->isPwyw()) {
            return 0.0;
        }

        return (float) ($this->custom_amount ?? $this->plan->price?->suggested_price ?? 0);
    }

    public function minAmountUsd(): float
    {
        return (float) ($this->plan->price?->min_price ?? $this->plan->price?->suggested_price ?? 0);
    }

    public function lineTotalUsd(): float
    {
        return $this->charge()['line_total'];
    }

    /**
     * The effective per-unit price at checkout, snapshotted into the order.
     */
    public function unitPriceUsd(): float
    {
        return $this->charge()['unit_price'];
    }

    /**
     * The one-time setup fee charged for a new subscription item.
     */
    public function setupFeeUsd(): float
    {
        return $this->plan->price?->setupFeeUsd() ?? 0.0;
    }

    public function lineTotalIn(Currency $currency): float
    {
        return $currency->convertUsd($this->lineTotalUsd());
    }

    /**
     * @return array{unit_price: float, line_total: float, setup_fee: float}
     */
    private function charge(): array
    {
        $price = $this->plan->price;

        if ($price === null) {
            return ['unit_price' => 0.0, 'line_total' => 0.0, 'setup_fee' => 0.0];
        }

        return resolve(PriceCalculator::class)->calculate(
            $price,
            $this->quantity,
            customerAmount: $this->plan->isPwyw() ? $this->amountUsd() : null,
            deferUsageCharges: true,
        );
    }
}
