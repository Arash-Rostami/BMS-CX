<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use App\Models\Account;
use App\Models\Settlement;
use App\Services\InterestCalculationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class CreateLedgerEntry extends CreateRecord
{
    protected static string $resource = LedgerEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data = $this->sanitizePayload($data);
            $account = Account::findOrFail($data['account_id']);

            $latestSettlement = Settlement::query()
                ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
                ->max('transaction_date');

            if ($latestSettlement !== null
                && Carbon::parse($data['transaction_date'])->lt(Carbon::parse($latestSettlement))) {
                Notification::make()
                    ->danger()
                    ->title('Back-dated entry not allowed')
                    ->body('This date is earlier than an existing settlement ('
                        . Carbon::parse($latestSettlement)->format('M j, Y')
                        . '). Inserting an entry into already-settled history would silently '
                        . 'invalidate every settlement after it.')
                    ->persistent()
                    ->send();

                throw new Halt();
            }

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
