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
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(99999999)
                    ->step('0.01')
                    ->label(fn (Get $get): string => $get('type') === CouponType::Fixed->value
                        ? 'Value (USD)'
                        : 'Value (%)'),
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
