<?php

namespace App\Models;

use App\DisplayCurrency;
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
 * @property Plan $plan
 *
 * @method static CartItemFactory factory()
 */
#[Fillable(['cart_id', 'plan_id', 'quantity'])]
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

    public function lineTotalUsd(): float
    {
        return $this->quantity * $this->plan->effectivePriceUsd();
    }

    public function lineTotalIn(DisplayCurrency $currency): float
    {
        return ExchangeRate::convert($this->lineTotalUsd(), $currency);
    }
}
