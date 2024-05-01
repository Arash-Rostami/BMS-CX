<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRequestStatusNotification extends Notification
{
    use Queueable;

    public string $product;
    public string $company;
    public string|null $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($record, $status = null)
    {
        $this->product = $record->product->name;
        $this->company = $record->buyer->name;
        $this->status = $status;
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
        return ($this->status === null) ? $this->sendToManagement() : $this->sendToTeam();
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

    /**
     * @return MailMessage
     */
    public function sendToManagement(): MailMessage
    {
        return (new MailMessage)
            ->subject('✨ New Order Request Made')
            ->greeting('Greetings,')
            ->line("A new order request has been created for **{$this->company}** for the product: **{$this->product}**.")
            ->action('Review Request', url('/'))
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * @return MailMessage
     */
    public function sendToTeam(): MailMessage
    {
        $message = $this->fetchTeamMessage();

        return (new MailMessage)
            ->subject('💫 Order Request Update')
            ->greeting('Greetings,')
            ->line($message)
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }

    /**
     * @return string
     */
    public function fetchTeamMessage(): string
    {
        $message = "The order request for **{$this->product}** has been ";

        $message .= match ($this->status) {
            'approved' => "**approved**.",
            'rejected' => "**rejected**.",
            default => "updated with status: **{$this->status}**.",
        };
        return $message;
    }
}
