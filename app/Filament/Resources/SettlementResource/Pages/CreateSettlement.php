<?php

namespace App\Filament\Resources\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use App\Models\Account;
use App\Services\InterestCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSettlement extends CreateRecord
{
    protected static string $resource = SettlementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $account = Account::findOrFail($data['account_id']);

        $calculator = new InterestCalculationService();
        $calculator->processPayment(
            $account,
            $data['settlement_amount'],
            $data['transaction_date'],
            $data['foreign_settlement_amount'] ?? null,
            $data['settlement_exchange_rate'] ?? null
        );

        // Filament expects a model to be returned.
        // We will return the latest settlement created for this account.
        $latestSettlement = \App\Models\Settlement::whereHas('ledgerEntry', function($query) use ($account) {
            $query->where('account_id', $account->id);
        })->latest('id')->first();

        // If no settlement was created (e.g. overpayment only), return an empty one or a dummy.
        // But since this is a create page, we should return something to satisfy the type hint.
        return $latestSettlement ?? new \App\Models\Settlement();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
