<?php

namespace App\Notifications;

use App\Models\ProformaInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProformaInvoiceStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $product;
    public string $company;
    public string $reference_number;
    public string $type;


    /**
     * Create a new notification instance.
     */
    public function __construct($record, $type = 'new')
    {
        $this->product = $record->product->name;
        $this->company = $record->buyer->name;
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
    public function showCreatedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('✨ New Pro forma Invoice Made')
            ->greeting('Greetings,')
            ->line("A new pro forma invoice ({$this->reference_number}) has been created for **{$this->company}** for the product: **{$this->product}**.")
            ->line('Thank you for your attention to this matter.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }

    /**
     * @return MailMessage
     */
    public function showEditedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('💫 Pro forma Invoice Update')
            ->greeting('Greetings,')
            ->line("The Pro forma Invoice ({$this->reference_number}) for **{$this->product}** has been edited.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }

    /**
     * @return MailMessage
     */
    public function showDeletedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Pro forma Invoice Removal')
            ->greeting('Greetings,')
            ->line("The Pro forma Invoice ({$this->reference_number}) for **{$this->product}** has been deleted.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }
}
