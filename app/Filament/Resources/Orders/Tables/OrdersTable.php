<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending->value => 'warning',
                        OrderStatus::AwaitingConfirmation->value => 'info',
                        OrderStatus::Completed->value => 'success',
                        OrderStatus::Expired->value => 'gray',
                        OrderStatus::Cancelled->value => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total_usd')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        OrderStatus::Pending->value => 'Pending',
                        OrderStatus::AwaitingConfirmation->value => 'Awaiting Confirmation',
                        OrderStatus::Completed->value => 'Completed',
                        OrderStatus::Expired->value => 'Expired',
                        OrderStatus::Cancelled->value => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
