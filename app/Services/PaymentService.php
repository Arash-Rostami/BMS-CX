<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\FilamentNotification;
use Illuminate\Support\Facades\Cache;

class PaymentService
{

    public function persistReferenceNumber($record): void
    {
        $yearSuffix = date('y');
        $orderIndex = $record->id;
        $referenceNumber = sprintf('P-%s%04d', $yearSuffix, $orderIndex);
        $record->reference_number = $referenceNumber;
        $record->save();
    }


    public function notifyAccountants($record, $records)
    {
        foreach ($this->fetchAccountants() as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $records,
                'type' => 'new',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.edit', ['record' => $record->id]),
            ]));
        }
    }


    public function fetchAccountants(): mixed
    {
        return Cache::remember('accountants', 480, function () {
            return User::getUsersByRole('accountant');
        });
    }
}
