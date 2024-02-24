<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;


class CreateOrderRequest extends CreateRecord
{
    protected static string $resource = OrderRequestResource::class;


    protected function afterCreate(): void
    {
        $data = [
            'record' => $this->record->product->name,
            'type' => 'new',
            'module' => 'orderRequest',
            'url' => 'filament.admin.resources.order-requests.index',
            'recipients' => User::all()
        ];

        NotificationManager::send($data);
    }
}
