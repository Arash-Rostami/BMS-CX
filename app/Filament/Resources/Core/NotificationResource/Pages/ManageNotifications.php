<?php

namespace App\Filament\Resources\Core\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\User;
use App\Notifications\PersonToPersonNotification;
use App\Services\RetryableEmailService;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;


class ManageNotifications extends ManageRecords
{
    protected static string $resource = NotificationResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->createAnother(false)
                ->icon('heroicon-o-sparkles')
                ->mutateFormDataUsing(fn($data) => static::addNotificationData($data))
        ];
    }

    protected static function addNotificationData(array $data): array
    {
        $data = self::mutateTableContent($data);

        if ($data['priority'] === 'IMPORTANT ALERT') {
            self::sendPriorityEmail($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function mutateTableContent(array $data): array
    {
        $data['id'] = (string)Str::uuid();
        $data['type'] = 'Filament\Notifications\DatabaseNotification';
        $data['notifiable_type'] = 'App\Models\User';
        $data['priority'] = ($data['priority'] === 'high') ? 'IMPORTANT ALERT' : 'STANDARD ALERT';
        $data['data'] = static::createNotificationContent($data);

        return $data;
    }

    protected static function createNotificationContent(array $data): string
    {
        return json_encode([
            'body' => $data['data'],
            'color' => 'primary',
            'duration' => 'persistent',
            'icon' => 'heroicon-m-information-circle',
            'iconColor' => 'info',
            'status' => 'warning',
            'title' => "<span style='color:#60A5FA'>{$data['priority']}</span>",
            'view' => 'filament-notifications::notification',
            'viewData' => [],
            'format' => 'filament',
            'actions' => static::createNotificationActions(),
        ]);
    }

    protected static function createNotificationActions(): array
    {
        return [
            [
                'name' => 'read',
                'color' => 'secondary',
                'event' => null,
                'eventData' => [],
                'dispatchDirection' => false,
                'dispatchToComponent' => null,
                'extraAttributes' => [],
                'icon' => 'heroicon-c-bell-slash',
                'iconPosition' => 'before',
                'iconSize' => null,
                'isOutlined' => false,
                'isDisabled' => false,
                'label' => 'Read',
                'shouldClose' => false,
                'shouldMarkAsRead' => true,
                'shouldMarkAsUnread' => false,
                'shouldOpenUrlInNewTab' => false,
                'size' => 'sm',
                'tooltip' => null,
                'url' => null,
                'view' => 'filament-actions::button-action'
            ],
            [
                'name' => 'unread',
                'color' => 'danger',
                'event' => null,
                'eventData' => [],
                'dispatchDirection' => false,
                'dispatchToComponent' => null,
                'extraAttributes' => [],
                'icon' => 'heroicon-s-bell-alert',
                'iconPosition' => 'before',
                'iconSize' => null,
                'isOutlined' => false,
                'isDisabled' => false,
                'label' => 'Unread',
                'shouldClose' => false,
                'shouldMarkAsRead' => false,
                'shouldMarkAsUnread' => true,
                'shouldOpenUrlInNewTab' => false,
                'size' => 'sm',
                'tooltip' => null,
                'url' => null,
                'view' => 'filament-actions::button-action'
            ]
        ];
    }


    /**
     * @param array $data
     * @return void
     */
    public static function sendPriorityEmail(array $data): void
    {
        $arguments = [User::find($data['notifiable_id']), new PersonToPersonNotification($data)];

        RetryableEmailService::dispatchEmail('personal alert', ...$arguments);
    }

}
