<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z]{3}$/'])
                    ->helperText('Kode tiga huruf (contoh: usd, idr, eur).'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->required()
                    ->maxLength(10),
                TextInput::make('exchange_rate')
                    ->label('Rate per 1 USD')
                    ->numeric()
                    ->required()
                    ->minValue(0.000001)
                    ->maxValue(999999999)
                    ->step('0.000001')
                    ->disabled(fn (Get $get): bool => (bool) $get('is_default'))
                    ->helperText('Jumlah mata uang tampilan untuk 1 USD. Wajib 1 untuk mata uang dasar.'),
                TextInput::make('decimal_places')
                    ->numeric()
                    ->default(2)
                    ->minValue(0)
                    ->maxValue(4),
                Toggle::make('is_enabled')
                    ->label('Enabled')
                    ->default(true),
                Toggle::make('is_default')
                    ->label('Default currency')
                    ->live()
                    ->helperText('Mata uang dasar tempat harga disimpan. Saat diaktifkan, rate otomatis 1 dan kurangi lain dinonaktifkan sebagai default.'),
            ])
            ->columns(2);
    }
}
