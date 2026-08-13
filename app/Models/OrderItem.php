<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $plan_id
 * @property int $product_id
 * @property string $name
 * @property int $quantity
 * @property float $unit_price
 * @property float $line_total
 * @property int $licenses_per_unit
 *
 * @method static OrderItemFactory factory()
 */
#[Fillable(['order_id', 'plan_id', 'product_id', 'name', 'quantity', 'unit_price', 'line_total', 'licenses_per_unit'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => MoneyCast::class,
            'line_total' => MoneyCast::class,
            'licenses_per_unit' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
