<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PersonToPersonNotification extends Notification
{
    use Queueable;

    protected $data;
    protected $subjectLine;
    protected $body;

    /**
     * Create a new notification instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->subjectLine = $this->data['priority'];
        $this->body = data_get(json_decode($this->data['data']), 'body');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $email = (new MailMessage)
            ->subject("⚠️ " . $this->subjectLine)
            ->greeting('Greetings,')
            ->line($this->body)
            ->line(auth()->user()->fullName);

        return isset($this->data['link'])
            ? $email->action('View Link', $this->data['link'])
            : $email;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
