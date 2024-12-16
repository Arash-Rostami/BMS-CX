<?php

namespace App\Notifications;

use App\Models\Allocation;
use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $invoice;
    public string $reference_number;
    public string $type;
    public string|bool $status;
    public string $state;

    /**
     * Create a new notification instance.
     */
    public function __construct($record, $type = 'new', $status = false)
    {
        $this->invoice = $record->order->reference_number ?? $record->proforma_invoice_number ?? Allocation::find($record->reason_for_payment)->reason;
        $this->reference_number = $record->reference_number;
        $this->state = $record->status;
        $this->type = $type;
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
        $notificationMap = [
            'new' => $this->showCreatedMessage(),
            'edit' => $this->showEditedMessage(),
            'delete' => $this->showDeletedMessage(),
        ];

        return !$this->status ? $notificationMap[$this->type] : $this->showStatusMessage();
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
            ->subject('✨ New Payment Request Made')
            ->greeting('Greetings,')
            ->line("A new payment request **{$this->reference_number}** has been created for: **{$this->invoice}**.")
            ->line('Please review the details and take the appropriate action according to the established internal procedure.')
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * @return MailMessage
     */
    public function showEditedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('💫 Payment Request Update')
            ->greeting('Greetings,')
            ->line("The Payment Request **{$this->reference_number}** ({$this->invoice}) has been edited.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }

    /**
     * @return MailMessage
     */
    public function showDeletedMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Payment Request Removal')
            ->greeting('Greetings,')
            ->line("The Payment Request **{$this->reference_number}** ({$this->invoice}) has been deleted.")
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }


    /**
     * @return MailMessage
     */
    public function showStatusMessage(): MailMessage
    {
        $message = $this->fetchStatusMessage();

        return (new MailMessage)
            ->subject('⚠️ Payment Request Status Change')
            ->greeting('Greetings,')
            ->line($message)
            ->line('Thank you for your attention.')
            ->line('(Please note, this is an informational email only. No further action is required.)');
    }


    /**
     * @return string
     */
    public function fetchStatusMessage(): string
    {
        $message = "The payment request for **{$this->invoice}** has been ";

        $message .= match ($this->state) {
            'allowed' => "initially **allowed**.",
            'approved' => "finally **approved**.",
            'rejected' => "**declined**.",
            default => "updated with status: **{$this->state}**.",
        };
        return $message;
    }
}
