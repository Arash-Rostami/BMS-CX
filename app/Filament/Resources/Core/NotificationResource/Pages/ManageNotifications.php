<?php

namespace App\Filament\Resources\Core\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\User;
use App\Notifications\PersonToPersonNotification;
use App\Services\Notification\SMS\Operator;
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
                ->mutateFormDataUsing(fn($data) => static::addNotificationData($data))
                ->successNotificationTitle('Message successfully delivered.')
                ->icon('heroicon-o-sparkles')
        ];
    }


    protected static function addNotificationData(array $data): array
    {
        $data = self::mutateTableContent($data);

        if (isset($data['priority'])) {
            $data = self::processPriority($data);
        }

        return $data;
    }

    protected static function getChannelMap(): array
    {
        return [
            'highest' => ['channels' => ['SMS', 'Email', 'In-app'], 'alert' => 'CRITICAL ALERT'],
            'high' => ['channels' => ['SMS', 'In-app'], 'alert' => 'IMPORTANT ALERT'],
            'mid' => ['channels' => ['Email', 'In-app'], 'alert' => 'STANDARD ALERT'],
            'low' => ['channels' => ['In-app'], 'alert' => 'MINOR ALERT'],
        ];
    }


    /**
     * Process priority and update data accordingly.
     *
     * @param array $data
     * @return array
     */
    protected static function processPriority(array $data): array
    {
        $channelMap = self::getChannelMap();


        if (array_key_exists($data['priority'], $channelMap)) {
            $priorityDetails = $channelMap[$data['priority']];

            $data['priority_alert'] = $priorityDetails['alert'] . ' from ' . auth()->user()->fullName;

            if (in_array('SMS', $priorityDetails['channels'])) {
                self::sendSMSNotification($data);
            }

            if (in_array('Email', $priorityDetails['channels'])) {
                self::sendPriorityEmail($data);
            }
        } else {
            dd("Processing priority: {$data['priority']}");
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function mutateTableContent(array $data): array
    {
        $channelMap = self::getChannelMap();

        $priorityDetails = $channelMap[$data['priority']] ?? $channelMap['low'];

        $data['id'] = (string)Str::uuid();
        $data['type'] = 'Filament\Notifications\DatabaseNotification';
        $data['notifiable_type'] = 'App\Models\User';
        $data['priority_alert'] = $priorityDetails['alert'] . ' from ' . auth()->user()->fullName;
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
            'title' => "<span style='color:#60A5FA'>{$data['priority_alert']}</span>",
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
     * Send an SMS notification based on priority.
     *
     * @param array $data
     * @return void
     */
    protected static function sendSMSNotification(array $data): void
    {
        $recipient = User::find($data['notifiable_id']);
        $sender = auth()->user();

        if ($recipient && !empty($recipient->phone)) {
            $messageData = json_decode($data['data']);

            $message = <<<SMS
BMS Notification Service

Priority: {$data['priority']}
Message: {$messageData->body}

Sent by: {$sender->fullName}
SMS;

            $operator = new Operator();
            $operator->send($recipient->phone, $message);
        }
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
