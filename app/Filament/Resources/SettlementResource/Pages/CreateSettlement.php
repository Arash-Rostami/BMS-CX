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



        $latestSettlement = \App\Models\Settlement::whereHas('ledgerEntry', function($query) use ($account) {
            $query->where('account_id', $account->id);
        })->latest('id')->first();



        return $latestSettlement ?? new \App\Models\Settlement();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
