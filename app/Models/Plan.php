<?php

namespace App\Models;

use App\Enums\LicenseLengthUnit;
use App\Enums\PlanStatus;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $description
 * @property bool $has_license_keys
 * @property int|null $license_activation_limit
 * @property bool $is_license_limit_unlimited
 * @property int|null $license_length_value
 * @property LicenseLengthUnit|null $license_length_unit
 * @property bool $is_license_length_unlimited
 * @property int $sort
 * @property PlanStatus $status
 * @property Price|null $price
 *
 * @method static PlanFactory factory()
 */
#[Fillable(['product_id', 'name', 'description', 'has_license_keys', 'license_activation_limit', 'is_license_limit_unlimited', 'license_length_value', 'license_length_unit', 'is_license_length_unlimited', 'sort', 'status'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Defaults for columns whose DB default may not be present in memory when
     * a model is built from a factory.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'has_license_keys' => true,
        'is_license_limit_unlimited' => false,
        'is_license_length_unlimited' => true,
        'sort' => 0,
        'status' => 'published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_license_keys' => 'boolean',
            'license_activation_limit' => 'integer',
            'is_license_limit_unlimited' => 'boolean',
            'license_length_value' => 'integer',
            'license_length_unit' => LicenseLengthUnit::class,
            'is_license_length_unlimited' => 'boolean',
            'sort' => 'integer',
            'status' => PlanStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne<Price, $this>
     */
    public function price(): HasOne
    {
        return $this->hasOne(Price::class);
    }

    public function isSubscription(): bool
    {
        return $this->price?->isSubscription() ?? false;
    }

    public function isFree(): bool
    {
        return $this->price?->isFree() ?? false;
    }

    public function isPwyw(): bool
    {
        return $this->price?->isPwyw() ?? false;
    }

    public function isUsageBased(): bool
    {
        return $this->price?->isUsageBased() ?? false;
    }

    public function hasLicenseKeys(): bool
    {
        return $this->has_license_keys;
    }

    /**
     * The per-activation limit for newly issued Licenses; null means unlimited.
     */
    public function activationLimit(): ?int
    {
        return $this->is_license_limit_unlimited ? null : $this->license_activation_limit;
    }

    public function periodLabel(): string
    {
        return $this->price?->periodLabel() ?? 'periode';
    }

    public function periodEndsAt(Carbon $from): Carbon
    {
        return $this->price?->periodEndsAt($from) ?? $from->copy();
    }

    /**
     * The moment a one-time license stops granting access, or null when it is
     * valid indefinitely.
     */
    public function licenseEndsAt(Carbon $from): ?Carbon
    {
        if ($this->is_license_length_unlimited) {
            return null;
        }

        $value = $this->license_length_value ?? 0;

        return match ($this->license_length_unit) {
            LicenseLengthUnit::Day => $from->copy()->addDays($value),
            LicenseLengthUnit::Month => $from->copy()->addMonths($value),
            LicenseLengthUnit::Year => $from->copy()->addYears($value),
            default => $from->copy(),
        };
    }

    /**
     * @param  Builder<Plan>  $query
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::Published);
    }
}
