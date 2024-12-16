<?php

namespace App\Filament\Resources\Operational\NotificationSubscriptionResource\Pages;

use App\Filament\Resources\NotificationSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationSubscriptions extends ManageRecords
{
    protected static string $resource = NotificationSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles')
                ->createAnother(false),
        ];
    }
}
