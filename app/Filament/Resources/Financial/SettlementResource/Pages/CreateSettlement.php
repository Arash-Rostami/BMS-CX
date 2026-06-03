<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use App\Models\Account;
use App\Models\Settlement;
use App\Services\InterestCalculationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
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

        $createdIds = (new InterestCalculationService())->processPayment(
            $account,
            $data['settlement_amount'],
            $data['transaction_date'],
            $data['foreign_settlement_amount'] ?? null,
            $data['settlement_exchange_rate'] ?? null,
            $data['currency_type'] ?? 'USD',
            auth()->id(),
        );

        if (empty($createdIds)) {
            Notification::make()
                ->warning()
                ->title('No settlements created')
                ->body('The payment was recorded as overpayment because all loans are already settled.')
                ->send();

            $this->redirect($this->getRedirectUrl());

            throw new Halt();
        }

        return Settlement::find($createdIds[0]);
    }
}
