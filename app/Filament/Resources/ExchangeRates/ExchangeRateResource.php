<?php

namespace App\Filament\Resources\ExchangeRates;

use App\DisplayCurrency;
use App\Filament\Resources\ExchangeRates\Pages\CreateExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\EditExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\ListExchangeRates;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Exchange Rates';

    protected static ?string $modelLabel = 'Exchange Rate';

    protected static ?string $pluralModelLabel = 'Exchange Rates';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('currency')
                    ->options([
                        'idr' => DisplayCurrency::Idr->label(),
                        'eur' => DisplayCurrency::Eur->label(),
                    ])
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('USD adalah mata uang dasar dan tidak perlu dimasukkan.'),
                TextInput::make('rate')
                    ->label('Rate per 1 USD')
                    ->numeric()
                    ->required()
                    ->minValue(0.000001)
                    ->maxValue(999999999)
                    ->step('0.000001')
                    ->helperText('Jumlah mata uang tampilan untuk 1 USD. Contoh: 16000 untuk IDR.'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency')
                    ->formatStateUsing(fn (string $state): string => DisplayCurrency::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Rate per 1 USD')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 6))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExchangeRates::route('/'),
            'create' => CreateExchangeRate::route('/create'),
            'edit' => EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
