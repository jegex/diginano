<?php

namespace App\Models;

use App\DisplayCurrency;
use App\OrderStatus;
use App\PaymentMethodType;
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
 * @property DisplayCurrency|null $settlement_currency
 * @property string|null $settlement_exchange_rate
 * @property string|null $snap_token
 * @property string|null $snap_redirect_url
 * @property string|null $provider_reference
 * @property string|null $provider_status
 * @property string|null $payment_type
 * @property int|null $coupon_id
 * @property int|null $payment_method_id
 * @property int|null $subscription_id
 * @property Carbon|null $completed_at
 *
 * @method static OrderFactory factory()
 */
#[Fillable(['number', 'user_id', 'status', 'subtotal_usd', 'discount_usd', 'total_usd', 'currency', 'exchange_rate', 'settlement_currency', 'settlement_exchange_rate', 'snap_token', 'snap_redirect_url', 'provider_reference', 'provider_status', 'payment_type', 'coupon_id', 'payment_method_id', 'subscription_id', 'completed_at'])]
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
            'settlement_currency' => DisplayCurrency::class,
            'settlement_exchange_rate' => 'decimal:6',
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

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<OrderProof, $this>
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(OrderProof::class);
    }

    public static function checkout(Cart $cart, PaymentMethod $paymentMethod, ?Coupon $coupon = null): self
    {
        abort_if($cart->isEmpty(), 422, 'Keranjang kosong, tidak bisa checkout.');
        abort_if($cart->user_id !== auth()->id(), 403);

        $currency = $cart->user->display_currency;
        $rate = ExchangeRate::rateFor($currency);

        $isManual = $paymentMethod->type === PaymentMethodType::Manual;
        $settlementCurrency = $paymentMethod->settlementCurrency();
        $settlementRate = ExchangeRate::rateFor($settlementCurrency);

        return DB::transaction(function () use ($cart, $paymentMethod, $coupon, $currency, $rate, $isManual, $settlementCurrency, $settlementRate): self {
            /** @var self $order */
            $order = static::query()->create([
                'number' => static::nextNumber(),
                'user_id' => $cart->user_id,
                'status' => $isManual ? OrderStatus::AwaitingConfirmation : OrderStatus::Pending,
                'subtotal_usd' => $cart->subtotalUsd(),
                'discount_usd' => $coupon !== null ? $cart->couponDiscountUsd($coupon) : 0,
                'total_usd' => $cart->totalUsd($coupon),
                'currency' => $currency,
                'exchange_rate' => $rate,
                'settlement_currency' => $settlementCurrency,
                'settlement_exchange_rate' => $settlementRate,
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

    /**
     * Create a single-item renewal Order for a Subscription at full price,
     * with no Coupon and no Sale applied.
     */
    public static function renewal(Subscription $subscription, PaymentMethod $paymentMethod): self
    {
        abort_if(! $subscription->isRenewable(), 422, 'Langganan ini sudah dibatalkan, tidak bisa diperpanjang.');
        abort_if($subscription->user_id !== auth()->id(), 403);

        $plan = $subscription->plan;
        $currency = $subscription->user->display_currency;
        $rate = ExchangeRate::rateFor($currency);

        $isManual = $paymentMethod->type === PaymentMethodType::Manual;
        $settlementCurrency = $paymentMethod->settlementCurrency();
        $settlementRate = ExchangeRate::rateFor($settlementCurrency);

        return DB::transaction(function () use ($subscription, $paymentMethod, $plan, $currency, $rate, $isManual, $settlementCurrency, $settlementRate): self {
            /** @var self $order */
            $order = static::query()->create([
                'number' => static::nextNumber(),
                'user_id' => $subscription->user_id,
                'status' => $isManual ? OrderStatus::AwaitingConfirmation : OrderStatus::Pending,
                'subtotal_usd' => $plan->price,
                'discount_usd' => 0,
                'total_usd' => $plan->price,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'settlement_currency' => $settlementCurrency,
                'settlement_exchange_rate' => $settlementRate,
                'payment_method_id' => $paymentMethod->id,
                'subscription_id' => $subscription->id,
            ]);

            $order->items()->create([
                'plan_id' => $plan->id,
                'product_id' => $plan->product_id,
                'name' => $plan->name,
                'quantity' => 1,
                'unit_price_usd' => $plan->price,
                'line_total_usd' => $plan->price,
                'licenses_per_unit' => $plan->licenses_per_unit,
            ]);

            return $order;
        });
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function isManualPayment(): bool
    {
        return $this->paymentMethod?->type === PaymentMethodType::Manual;
    }

    public function isMidtransPayment(): bool
    {
        return $this->paymentMethod?->type === PaymentMethodType::Midtrans;
    }

    public function isCryptomusPayment(): bool
    {
        return $this->paymentMethod?->type === PaymentMethodType::Cryptomus;
    }

    /**
     * The amount to charge in the gateway's settlement currency.
     */
    public function settlementAmount(): float
    {
        return (float) $this->total_usd * (float) $this->settlement_exchange_rate;
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
