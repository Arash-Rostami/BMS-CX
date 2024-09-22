<?php

namespace App\Listeners;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\PersonToPersonNotification;
use App\Services\HTMLCrawler;
use App\Services\RetryableEmailService;
use Illuminate\Support\Str;

class SendChatNotification
{

    public function __construct()
    {
    }

    public function handle(object $event): void
    {
        $data = $event->chatMessage->toArray();
        $recipientIds = $data['mentions'];

        $users = User::findMany($recipientIds)->keyBy('id');
        $validUsers = collect();

        foreach ($recipientIds as $recipientId) {
            $user = $users->get($recipientId);

            if ($user) {
                $validUsers->push($user);
                $data = self::mutateTableContent($data, $recipientId);

                Notification::create($data);
            }
        }

        if ($validUsers->isNotEmpty()) {
            $arguments = [$data['priority'], $validUsers, new PersonToPersonNotification($data)];
            RetryableEmailService::dispatchEmail(...$arguments);
        }
    }

    public static function mutateTableContent(array $data, $recipientId): array
    {
        $data['link'] = HTMLCrawler::extractFirstLinkUsingStr($data['message']);
        $data['notifiable_id'] = $recipientId;
        $data['id'] = (string)Str::uuid();
        $data['created_at'] = now()->format('Y-m-d H:i:s');
        $data['type'] = 'Filament\Notifications\DatabaseNotification';
        $data['notifiable_type'] = 'App\Models\User';
        $data['priority'] = 'IMPORTANT ALERT ' . 'from ' . auth()->user()->first_name;
        $data['data'] = static::createNotificationContent($data);

        return $data;
    }

    protected static function createNotificationContent(array $data): string
    {
        return json_encode([
            'body' => strip_tags($data['message']),
            'color' => 'primary',
            'duration' => 'persistent',
            'icon' => 'heroicon-m-information-circle',
            'iconColor' => 'info',
            'status' => 'warning',
            'title' => "<span style='color:#60A5FA'>{$data['priority']}</span>",
            'view' => 'filament-notifications::notification',
            'viewData' => [],
            'format' => 'filament',
            'actions' => static::createNotificationActions($data),
        ]);
    }

    protected static function createNotificationActions($data): array
    {
        $actions = [
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

        if (!empty($data['link'])) {
            $viewAction = [
                'name' => 'view',
                'color' => null,
                'event' => null,
                'eventData' => [],
                'dispatchDirection' => false,
                'dispatchToComponent' => null,
                'extraAttributes' => [],
                'icon' => 'heroicon-s-cursor-arrow-rays',
                'iconPosition' => 'before',
                'iconSize' => null,
                'isOutlined' => false,
                'isDisabled' => false,
                'label' => 'Link',
                'shouldClose' => false,
                'shouldMarkAsRead' => false,
                'shouldMarkAsUnread' => false,
                'shouldOpenUrlInNewTab' => true,
                'size' => 'sm',
                'tooltip' => null,
                'url' => $data['link'],
                'view' => 'filament-actions::button-action'
            ];

            array_unshift($actions, $viewAction);
        }

        return $actions;
    }
}
