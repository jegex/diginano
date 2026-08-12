<?php

namespace App\Models;

use App\DisplayCurrency;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 *
 * @method static CartFactory factory()
 */
#[Fillable(['user_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public static function for(User $user): Cart
    {
        return static::query()->firstOrCreate(['user_id' => $user->id]);
    }

    public function add(Plan $plan, int $quantity = 1): CartItem
    {
        $quantity = max(1, $quantity);

        /** @var CartItem|null $item */
        $item = $this->items()->where('plan_id', $plan->id)->first();

        if ($item !== null) {
            $item->update(['quantity' => $item->quantity + $quantity]);

            return $item;
        }

        return $this->items()->create([
            'plan_id' => $plan->id,
            'quantity' => $quantity,
        ]);
    }

    public function setQuantity(CartItem $item, int $quantity): void
    {
        $this->assertOwns($item);

        $item->update(['quantity' => max(1, $quantity)]);
    }

    public function remove(CartItem $item): void
    {
        $this->assertOwns($item);

        $item->delete();
    }

    public function subtotalUsd(): float
    {
        return (float) $this->items->sum(fn (CartItem $item): float => $item->lineTotalUsd());
    }

    public function eligibleSubtotalUsd(Coupon $coupon): float
    {
        return (float) $this->items->sum(
            fn (CartItem $item): float => $coupon->applicableTo($item->plan)
                ? $item->lineTotalUsd()
                : 0.0,
        );
    }

    public function couponDiscountUsd(Coupon $coupon): float
    {
        return $coupon->discountUsd($this->eligibleSubtotalUsd($coupon));
    }

    public function totalUsd(?Coupon $coupon = null): float
    {
        $total = $this->subtotalUsd();

        if ($coupon !== null) {
            $total -= $this->couponDiscountUsd($coupon);
        }

        return round(max(0, $total), 2);
    }

    public function couponDiscountIn(Coupon $coupon, DisplayCurrency $currency): float
    {
        return ExchangeRate::convert($this->couponDiscountUsd($coupon), $currency);
    }

    public function totalIn(DisplayCurrency $currency, ?Coupon $coupon = null): float
    {
        return ExchangeRate::convert($this->totalUsd($coupon), $currency);
    }

    public function subtotalIn(DisplayCurrency $currency): float
    {
        return ExchangeRate::convert($this->subtotalUsd(), $currency);
    }

    public function displayCurrency(): DisplayCurrency
    {
        return $this->user->display_currency;
    }

    public function totalQuantity(): int
    {
        return $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    private function assertOwns(CartItem $item): void
    {
        abort_unless($item->cart_id === $this->id, 403);
    }
}
