<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use App\Models\Account;
use App\Services\InterestCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateLedgerEntry extends CreateRecord
{
    protected static string $resource = LedgerEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data = $this->sanitizePayload($data);

            if (($data['type'] ?? null) === 'disbursement') {
                $account = Account::findOrFail($data['account_id']);
                (new InterestCalculationService())->applyDisbursementCredit($account, $data);
            }

            return static::getModel()::create($data);
        });
    }

    protected function sanitizePayload($data): array
    {
        $data['rate_matrix_snapshot'] = config('financial.interest_tiers');
        $data['user_id'] = auth()->id();
        if (trim($data['description'] ?? '') === InterestCalculationService::OVERPAYMENT_DESCRIPTION) {
            $data['description'] = 'Manual credit entry';
        }

        return $data;
    }
}
