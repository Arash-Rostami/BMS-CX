<?php

namespace App\Notifications;

use App\Services\NotificationManager;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Notifications\Notification as Filament;


class FilamentNotification extends Notification
{
    use Queueable;


    /**
     * Create a new notification instance.
     */
    public function __construct(public array $data, public bool $isStatus = false)
    {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return NotificationManager::send($this->data, $this->isStatus);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
