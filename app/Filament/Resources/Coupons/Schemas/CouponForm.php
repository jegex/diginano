<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('Kode akan disimpan dalam huruf besar.'),
                Select::make('type')
                    ->options(CouponType::class)
                    ->required()
                    ->live(),
                TextInput::make('value')
                    ->label('Value (%)')
                    ->numeric()
                    ->required(fn (Get $get): bool => $get('type') === CouponType::Percentage)
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->step('0.01')
                    ->visible(fn (Get $get): bool => $get('type') === CouponType::Percentage),
                TextInput::make('fixed_value')
                    ->label('Value (USD)')
                    ->numeric()
                    ->required(fn (Get $get): bool => $get('type') === CouponType::Fixed)
                    ->minValue(0.01)
                    ->maxValue(99999999)
                    ->step('0.01')
                    ->prefix('$')
                    ->visible(fn (Get $get): bool => $get('type') === CouponType::Fixed),
                Toggle::make('is_single_use')
                    ->label('Sekali pakai per pelanggan'),
                CheckboxList::make('products')
                    ->relationship('products', 'name')
                    ->columns(2)
                    ->helperText('Kosongkan agar berlaku global untuk semua produk.'),
            ])
            ->columns(2);
    }
}
