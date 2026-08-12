<?php

namespace App\Models;

use App\PaymentMethodType;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property PaymentMethodType $type
 * @property string $name
 * @property bool $is_enabled
 * @property array<string, mixed>|null $config
 *
 * @method static PaymentMethodFactory factory()
 */
#[Fillable(['type', 'name', 'is_enabled', 'config'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
