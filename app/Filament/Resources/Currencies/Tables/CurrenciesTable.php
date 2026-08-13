<?php

namespace App\Filament\Resources\Currencies\Tables;

use App\Models\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol'),
                TextColumn::make('exchange_rate')
                    ->label('Rate per 1 USD')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 6))
                    ->sortable(),
                TextColumn::make('decimal_places')
                    ->sortable(),
                ToggleColumn::make('is_enabled')
                    ->label('Enabled'),
                TextColumn::make('is_default')
                    ->label('Default')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Currency $record): bool => $record->is_default),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn (): bool => Currency::query()->where('is_default', true)->exists()),
                ]),
            ]);
    }
}
