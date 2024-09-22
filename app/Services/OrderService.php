<?php

namespace App\Services;


use App\Models\User;
use App\Notifications\FilamentNotification;
use Illuminate\Support\Facades\Cache;

class OrderService
{
    public function persistReferenceNumber($record): void
    {
        $yearSuffix = date('y');
        $orderIndex = $record->id;
        $referenceNumber = sprintf('O-%s%04d', $yearSuffix, $orderIndex);
        $record->reference_number = $referenceNumber;
        $record->save();
    }

    public function notifyAgents($record, $agents): void
    {
        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->invoice_number . ' (' . $record->reference_number . ')',
                'type' => 'new',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.edit', ['record' => $record->id]),
            ]));
        }
    }

    public function fetchAgents(): mixed
    {
        return Cache::remember('agents', 480, function () {
            return User::getUsersByRole('agent');
        });
    }
}
