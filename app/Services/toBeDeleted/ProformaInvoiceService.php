<?php

namespace App\Services\toBeDeleted;


use App\Models\User;
use App\Notifications\FilamentNotification;
use Illuminate\Support\Facades\Cache;

class ProformaInvoiceService
{
//    public function persistReferenceNumber($record): void
//    {
//        $yearSuffix = date('y');
//        $orderIndex = $record->id;
//        $referenceNumber = sprintf('PI-%s%04d', $yearSuffix, $orderIndex);
//        $record->reference_number = $referenceNumber;
//        $record->save();
//    }
//
//    public function notifyAgents($record, $agents): void
//    {
//        foreach ($agents as $recipient) {
//            $recipient->notify(new FilamentNotification([
//                'record' => $record->proforma_number . ' (' . $record->reference_number . ')',
//                'type' => 'new',
//                'module' => 'proformaInvoice',
//                'url' => route('filament.admin.resources.proforma-invoices.edit', ['record' => $record->id]),
//            ]));
//        }
//    }
//
//    public function fetchAgents($department = null, $position = null): mixed
//    {
//        return Cache::remember('agents', 480, function ($department, $position) {
//            return User::getUsersByRole('agent')
//                ->filter(function ($user) use ($department, $position) {
//                    return ($user->info['department'] ?? null) == $department && ($user->info['position'] ?? null) == $position;
//                });
//        });
//    }
}
