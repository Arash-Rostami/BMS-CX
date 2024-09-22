<?php

namespace App\Services;


use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\CreatePaymentRequest;
use App\Models\User;
use App\Notifications\FilamentNotification;
use Illuminate\Support\Facades\Cache;

class PaymentRequestService
{
    public function persistReferenceNumber($record): void
    {
        $yearSuffix = date('y');
        $orderIndex = $record->id;
        $referenceNumber = sprintf('PR-%s%04d', $yearSuffix, $orderIndex);
        $record->reference_number = $referenceNumber;
        $record->save();
    }

    public function notifyAccountants($record, $accountants): void
    {
        foreach ($accountants as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($record),
                'type' => 'new',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $record->id]),
            ]));
        }

        (new CreatePaymentRequest())->notifyViaEmail($accountants, $record);
    }

    public function fetchAccountants(): mixed
    {
        return Cache::remember('accountants', 480, function () {
            return User::getUsersByRole('accountant');
        });
    }
}
