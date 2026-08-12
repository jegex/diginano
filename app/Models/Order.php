<?php

namespace App\Models;

use App\DisplayCurrency;
use App\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $number
 * @property int $user_id
 * @property OrderStatus $status
 * @property string $subtotal_usd
 * @property string $discount_usd
 * @property string $total_usd
 * @property DisplayCurrency $currency
 * @property string $exchange_rate
 * @property int|null $coupon_id
 * @property int|null $payment_method_id
 * @property Carbon|null $completed_at
 *
 * @method static OrderFactory factory()
 */
#[Fillable(['number', 'user_id', 'status', 'subtotal_usd', 'discount_usd', 'total_usd', 'currency', 'exchange_rate', 'coupon_id', 'payment_method_id', 'completed_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'currency' => DisplayCurrency::class,
            'subtotal_usd' => 'decimal:2',
            'discount_usd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public static function checkout(Cart $cart, PaymentMethod $paymentMethod, ?Coupon $coupon = null): self
    {
        abort_if($cart->isEmpty(), 422, 'Keranjang kosong, tidak bisa checkout.');
        abort_if($cart->user_id !== auth()->id(), 403);

        $currency = $cart->user->display_currency;
        $rate = ExchangeRate::rateFor($currency);

        return DB::transaction(function () use ($cart, $paymentMethod, $coupon, $currency, $rate): self {
            /** @var self $order */
            $order = static::query()->create([
                'number' => static::nextNumber(),
                'user_id' => $cart->user_id,
                'status' => OrderStatus::Pending,
                'subtotal_usd' => $cart->subtotalUsd(),
                'discount_usd' => $coupon !== null ? $cart->couponDiscountUsd($coupon) : 0,
                'total_usd' => $cart->totalUsd($coupon),
                'currency' => $currency,
                'exchange_rate' => $rate,
                'coupon_id' => $coupon?->id,
                'payment_method_id' => $paymentMethod->id,
            ]);

            $items = $cart->items->map(fn (CartItem $item): array => [
                'plan_id' => $item->plan_id,
                'product_id' => $item->plan->product_id,
                'name' => $item->plan->name,
                'quantity' => $item->quantity,
                'unit_price_usd' => $item->plan->effectivePriceUsd(),
                'line_total_usd' => $item->lineTotalUsd(),
                'licenses_per_unit' => $item->plan->licenses_per_unit,
            ])->all();

            $order->items()->createMany($items);
            $cart->items()->delete();

            return $order;
        });
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function subtotalInDisplay(): float
    {
        return (float) $this->subtotal_usd * (float) $this->exchange_rate;
    }

    public function discountInDisplay(): float
    {
        return (float) $this->discount_usd * (float) $this->exchange_rate;
    }

    public function totalInDisplay(): float
    {
        return (float) $this->total_usd * (float) $this->exchange_rate;
    }

    private static function nextNumber(): string
    {
        return 'ORD-'.strtoupper(Str::random(8));
    }
}
