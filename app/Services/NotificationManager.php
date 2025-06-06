<?php

namespace App\Services;

use App\Services\Traits\NotificationConfig;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotificationManager
{
    use NotificationConfig;


    public static function send(array $data, bool $isStatus = false)
    {
        self::processData($data);

        // separate logics of status from notification
        $titleMethod = $isStatus ? 'getTitleStatus' : 'getTitle';
        $bodyMethod = $isStatus ? 'getBodyStatus' : 'getBody';
        $iconMethod = $isStatus ? 'getIconStatus' : 'getIcon';

        $notification = Notification::make()
            ->title(self::$titleMethod($data['type']))
            ->body(sprintf(self::$bodyMethod($data['module'], $data['type']), self::getItems()))
            ->icon(self::$iconMethod($data['type']))
            ->actions([
                self::showViewAction(),
                self::showReadAction(),
                self::showUnreadAction(),
            ]);


        if ($isStatus) {
            return $notification
                ->getDatabaseMessage();
        } else {
            //adding color to title and icon
            return $notification
                ->{self::getType($data['type'])}()
                ->getDatabaseMessage();
        }
    }

    /**
     * @return Action|string
     */
    public static function showViewAction(): Action
    {
        return Action::make('view')
            ->button()
            ->icon('heroicon-s-cursor-arrow-rays')
            ->url(self::getUrl(), shouldOpenInNewTab: true);
    }

    /**
     * @return Action
     */
    public static function showReadAction(): Action
    {
        return Action::make('read')
            ->button()
            ->icon('heroicon-c-bell-slash')
            ->color('secondary')
            ->markAsRead();
    }

    /**
     * @return Action
     */
    public static function showUnreadAction(): Action
    {
        return Action::make('unread')
            ->icon('heroicon-s-bell-alert')
            ->color('danger')
            ->button()
            ->markAsUnread();
    }

    /**
     * @param array $data
     * @return void
     */
    public static function processData(array $data): void
    {
        self::setItems($data['record']);
        self::setUrl($data['url']);
    }
}
