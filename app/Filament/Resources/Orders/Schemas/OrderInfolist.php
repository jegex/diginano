<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number')
                    ->label('Order'),
                TextEntry::make('user.name')
                    ->label('Customer'),
                TextEntry::make('status')
                    ->label('Status'),
                TextEntry::make('subtotal_usd')
                    ->label('Subtotal')
                    ->money('USD'),
                TextEntry::make('discount_usd')
                    ->label('Discount')
                    ->money('USD'),
                TextEntry::make('total_usd')
                    ->label('Total')
                    ->money('USD'),
                TextEntry::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->placeholder('—'),
                TextEntry::make('coupon.code')
                    ->label('Coupon')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime(),
                TextEntry::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('—'),
                RepeatableEntry::make('items')
                    ->label('Items')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Plan'),
                        TextEntry::make('quantity')
                            ->label('Qty'),
                        TextEntry::make('unit_price_usd')
                            ->label('Unit Price')
                            ->money('USD'),
                        TextEntry::make('line_total_usd')
                            ->label('Line Total')
                            ->money('USD'),
                    ]),
            ])
            ->columns(2);
    }
}
