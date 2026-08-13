<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (CouponType $state): string => $state->label()),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(function (Coupon $record, ?string $state): string {
                        if ($record->type === CouponType::Percentage) {
                            return rtrim(rtrim(number_format((float) $state, 2), '0'), '.').'%';
                        }

                        return '$'.number_format($record->fixed_value ?? 0, 2);
                    }),
                TextColumn::make('products_count')
                    ->label('Scope')
                    ->counts('products')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Global' : "{$state} produk"),
                TextColumn::make('is_single_use')
                    ->label('Sekali pakai')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
}
