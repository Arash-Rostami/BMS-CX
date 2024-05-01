<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequestStatusNotification extends Notification
{
    use Queueable;

    public string $invoice;
    public string|null $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($record, $status = null)
    {
        $this->invoice = $record->order->invoice_number ?? PaymentRequest::$organizationalReasonsForPayment[$record->reason_for_payment];
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
     * @return MailMessage
     */
    public function sendToManagement(): MailMessage
    {
        return (new MailMessage)
            ->subject('✨ New Payment Request Requires Approval')
            ->greeting('Greetings,')
            ->line("A new payment request notification has been generated for: **{$this->invoice}**.")
            ->line('Please review the details and take the appropriate action according to the established internal procedure.')
            ->action('View Payment Request', url('/'))
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * @return MailMessage
     */
    public function sendToTeam(): MailMessage
    {
        $message = $this->fetchTeamMessage();

        return (new MailMessage)
            ->subject('💫 Payment Request Update')
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
        $message = "The payment request for order's invoice number **{$this->invoice}** has been ";

        $message .= match ($this->status) {
            'allowed' => "**allowed** by the finance department.",
            'approved' => "**approved** by the management.",
            'rejected' => "**declined**.",
            default => "updated with status: **{$this->status}**.",
        };
        return $message;
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
