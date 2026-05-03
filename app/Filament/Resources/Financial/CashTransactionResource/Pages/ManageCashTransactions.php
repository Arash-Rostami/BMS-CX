<?php

namespace App\Filament\Resources\Financial\CashTransactionResource\Pages;

use App\Filament\Resources\CashTransactionResource;
use App\Filament\Resources\Financial\CashTransactionResource\Widgets\CashBalanceHeaderWidget;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCashTransactions extends ManageRecords
{
    protected static string $resource = CashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_funds')
                ->label('Add Funds')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Admin::getAmount(),
                    Admin::getCurrency(),
                    Admin::getDescription()
                ])
                ->action(function (array $data) {
                    static::getResource()::getModel()::create([
                        'type' => 'credit',
                        'amount' => $data['amount'],
                        'currency' => $data['currency'],
                        'description' => $data['description'],
                        'user_id' => auth()->id()
                    ]);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CashBalanceHeaderWidget::class,
        ];
    }
}
