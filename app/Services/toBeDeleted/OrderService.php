<?php

namespace App\Services\toBeDeleted;


use App\Models\User;
use App\Notifications\FilamentNotification;
use Illuminate\Support\Facades\Cache;

class OrderService
{
//    public function persistReferenceNumber($record): void
//    {
//        $yearSuffix = date('y');
//        $orderIndex = $record->id;
//        $referenceNumber = sprintf('O-%s%04d', $yearSuffix, $orderIndex);
//        $record->reference_number = $referenceNumber;
//        $record->save();
//    }
//
//    public function notifyAgents($record, $agents, $edit = null): void
//    {
//        foreach ($agents as $recipient) {
//            $recipient->notify(new FilamentNotification([
//                'record' => $record->invoice_number . ' (' . $record->reference_number . ')',
//                'type' => $edit ? 'edit' : 'new',
//                'module' => 'order',
//                'url' => route('filament.admin.resources.orders.edit', ['record' => $record->id]),
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
