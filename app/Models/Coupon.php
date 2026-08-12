<?php

namespace App\Models;

use App\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property string $value
 * @property bool $is_single_use
 *
 * @method static CouponFactory factory()
 */
#[Fillable(['code', 'type', 'value', 'is_single_use'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'is_single_use' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isGlobal(): bool
    {
        return $this->products()->count() === 0;
    }

    public function applicableTo(Plan $plan): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        return $this->products()->whereKey($plan->product_id)->exists();
    }

    public function discountUsd(float $eligibleSubtotalUsd): float
    {
        $discount = match ($this->type) {
            CouponType::Percentage => $eligibleSubtotalUsd * ($this->value / 100),
            CouponType::Fixed => min($this->value, $eligibleSubtotalUsd),
        };

        return round($discount, 2);
    }

    public function code(): Attribute
    {
        return Attribute::set(fn (string $value): string => strtoupper($value));
    }
}
