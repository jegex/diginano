<?php

namespace App\Models;

use App\DisplayCurrency;
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

    /**
     * The currency the gateway settles in: the bank account's currency for
     * manual transfers, always IDR for Midtrans, USD for Cryptomus (the
     * invoice is created in USD and the customer pays in the coin of choice).
     */
    public function settlementCurrency(): DisplayCurrency
    {
        if ($this->type === PaymentMethodType::Midtrans) {
            return DisplayCurrency::Idr;
        }

        if ($this->type === PaymentMethodType::Cryptomus) {
            return DisplayCurrency::Usd;
        }

        return DisplayCurrency::from($this->config['bank_currency'] ?? DisplayCurrency::Idr->value);
    }

    public function midtransServerKey(): string
    {
        return (string) ($this->config['server_key'] ?? '');
    }

    public function midtransClientKey(): string
    {
        return (string) ($this->config['client_key'] ?? '');
    }

    public function midtransIsSandbox(): bool
    {
        return (bool) ($this->config['is_sandbox'] ?? true);
    }

    public function cryptomusMerchantUuid(): string
    {
        return (string) ($this->config['merchant_uuid'] ?? '');
    }

    public function cryptomusPaymentApiKey(): string
    {
        return (string) ($this->config['payment_api_key'] ?? '');
    }

    public function cryptomusIsTest(): bool
    {
        return (bool) ($this->config['is_test'] ?? true);
    }

    /**
     * Bank account details for manual transfers, keyed by the config field.
     *
     * @return array<string, string>
     */
    public function bankDetails(): array
    {
        return [
            'bank_name' => (string) ($this->config['bank_name'] ?? ''),
            'account_name' => (string) ($this->config['account_name'] ?? ''),
            'account_number' => (string) ($this->config['account_number'] ?? ''),
        ];
    }
}
