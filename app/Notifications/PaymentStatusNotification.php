<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $reference_number;
    public string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($record, $type = 'new')
    {
        $this->reference_number = $record->reference_number;
        $this->type = $type;
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
        return ($this->type == 'new')
            ? $this->showCreatedMessage()
            : ($this->type == 'edit' ? $this->showEditedMessage() : $this->showDeletedMessage());
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }


    /**
     * @return MailMessage
     */
    public function showCreatedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('✨ New Payment Made')
            ->greeting('Greetings,')
            ->line("A new Payment has been created with reference: **{$this->reference_number}**.")
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * @return MailMessage
     */
    public function showEditedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('💫 Payment Update')
            ->greeting('Greetings,')
            ->line("The Payment **{$this->reference_number}** has been edited.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }

    /**
     * @return MailMessage
     */
    public function showDeletedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Payment Removal')
            ->greeting('Greetings,')
            ->line("The Payment **{$this->reference_number}** has been deleted.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }
}
