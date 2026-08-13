<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\ImageEntry;
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
                TextEntry::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD'),
                TextEntry::make('discount')
                    ->label('Discount')
                    ->money('USD'),
                TextEntry::make('total')
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
                RepeatableEntry::make('proofs')
                    ->label('Bukti Pembayaran')
                    ->schema([
                        ImageEntry::make('file_path')
                            ->label('File')
                            ->disk('public')
                            ->height(80),
                        TextEntry::make('original_name')
                            ->label('Nama File'),
                        TextEntry::make('submitted_at')
                            ->label('Diunggah')
                            ->dateTime(),
                    ])
                    ->visible(fn (Order $record): bool => $record->proofs->isNotEmpty()),
                RepeatableEntry::make('items')
                    ->label('Items')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Plan'),
                        TextEntry::make('quantity')
                            ->label('Qty'),
                        TextEntry::make('unit_price')
                            ->label('Unit Price')
                            ->money('USD'),
                        TextEntry::make('line_total')
                            ->label('Line Total')
                            ->money('USD'),
                    ]),
            ])
            ->columns(2);
    }
}
