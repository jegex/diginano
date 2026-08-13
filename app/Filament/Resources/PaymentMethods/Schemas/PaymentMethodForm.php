<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use App\Enums\PaymentMethodType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        PaymentMethodType::Manual->value => 'Manual (Bank Transfer)',
                        PaymentMethodType::Midtrans->value => 'Midtrans',
                        PaymentMethodType::Cryptomus->value => 'Cryptomus',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_enabled')
                    ->label('Enabled')
                    ->default(true),
                TextInput::make('config.bank_name')
                    ->label('Bank Name')
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Manual->value),
                TextInput::make('config.account_name')
                    ->label('Account Name')
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Manual->value),
                TextInput::make('config.account_number')
                    ->label('Account Number')
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Manual->value),
                TextInput::make('config.server_key')
                    ->label('Server Key')
                    ->password()
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Midtrans->value),
                TextInput::make('config.client_key')
                    ->label('Client Key')
                    ->password()
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Midtrans->value),
                Toggle::make('config.is_sandbox')
                    ->label('Sandbox mode')
                    ->default(true)
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Midtrans->value),
                TextInput::make('config.merchant_uuid')
                    ->label('Merchant ID')
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Cryptomus->value),
                TextInput::make('config.payment_api_key')
                    ->label('Payment API Key')
                    ->password()
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Cryptomus->value),
                Toggle::make('config.is_test')
                    ->label('Test mode')
                    ->default(true)
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Cryptomus->value),
            ])
            ->columns(2);
    }
}
