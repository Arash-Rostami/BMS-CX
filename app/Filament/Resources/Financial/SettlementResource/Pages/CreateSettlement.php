<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use App\Models\Account;
use App\Models\Settlement;
use App\Services\InterestCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSettlement extends CreateRecord
{
    protected static string $resource = SettlementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $account = Account::findOrFail($data['account_id']);

        $calculator = new InterestCalculationService();
        $calculator->processPayment(
            $account,
            $data['settlement_amount'],
            $data['transaction_date'],
            $data['foreign_settlement_amount'] ?? null,
            $data['settlement_exchange_rate'] ?? null,
            $data['currency_type'] ?? "USD",
        );


        $latestSettlement = Settlement::whereHas('ledgerEntry', function ($query) use ($account) {
            $query->where('account_id', $account->id);
        })->latest('id')->first();


        return $latestSettlement ?? new Settlement();
    }
}
