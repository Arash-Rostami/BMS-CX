<?php

namespace App\Notifications;

use App\Models\Packaging;
use App\Models\Product;
use App\Services\QuoteRequestTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
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
    public function toMail(): MailMessage
    {
        $details = $this->fetchDetails($this->data);

        return (new MailMessage)
            ->subject("Quote Request - " . now()->format('d F'))
            ->from(config('app.email'), config('app.name'))
            ->greeting('Dear ' . $this->data['recipient']['name'] . ',')
            ->line(QuoteRequestTemplates::getQuestionLine())
            ->line($details['portDetails'])
            ->line($details['cargoDetails'])
            ->line($details['targetDetails'])
            ->line($details['switchBL'])
            ->line($details['extraInfo'])
            ->line(QuoteRequestTemplates::getInvitationLine() . $details['validity'])
            ->action('Give Your Quote Now', $details['quoteServiceUrl'])
            ->line(QuoteRequestTemplates::getFallBackLine() . $this->data['email'])
            ->line(QuoteRequestTemplates::getAppreciationLine())
            ->salutation(QuoteRequestTemplates::getEndingLine());
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

    private function fetchDetails(mixed $quoteRequest): array
    {
        $portDetails = $this->formatDetail("**Origin Port (POL):**", $quoteRequest['origin_port'] ?? null);
        $portDetails .= $this->formatDetail("**Destination Port (POD):**", $quoteRequest['destination_port'] ?? null);

        $cargoDetails = $this->formatDetail("**Commodity:**", optional(Product::find($quoteRequest['commodity'] ?? null))->name);
        $cargoDetails .= $this->formatDetail("**Packaging:**", optional(Packaging::find($quoteRequest['packing'] ?? null))->name);
        $cargoDetails .= $this->formatDetail("**Container Type:**", $quoteRequest['container_type'] ?? null);
        $cargoDetails .= $this->formatDetail("**Gross Weight:**", $quoteRequest['gross_weight'] ?? null);
        $cargoDetails .= $this->formatDetail("**No. of Container(Quan):**", $quoteRequest['quantity'] ?? null);

        $targetDetails = $this->formatDetail("**Target Rate:**", $quoteRequest['target_of_rate'] ?? null);
        $targetDetails .= $this->formatDetail("**Target Local Charges:**", $quoteRequest['target_local_charges'] ?? null);
        $targetDetails .= $this->formatDetail("**Target Switch BL Fees:**", $quoteRequest['target_switch_bl_fee'] ?? null);

        $validity = $this->formatDetail("(up to", $quoteRequest['validity'] ?? null, ")");
        $switchBL = $this->formatDetailIfTrue("**Switch BL Required:** Yes\n", $quoteRequest['requires_switch_bl'] ?? false);
        $extraInfo = $this->formatDetail("**Additional Details:**\n", $quoteRequest['extra']['details'] ?? null);

        return [
            'portDetails' => $portDetails,
            'cargoDetails' => $cargoDetails,
            'targetDetails' => $targetDetails,
            'validity' => $validity,
            'switchBL' => $switchBL,
            'extraInfo' => $extraInfo,
            'quoteServiceUrl' => $this->getQuoteToken()
        ];
    }

    private function formatDetail(string $label, $value, string $suffix = "\n"): string
    {
        return $value ? $label . ' ' . $value . $suffix : '';
    }

    private function formatDetailIfTrue(string $detail, bool $condition): string
    {
        return $condition ? $detail : '';
    }

    private function getQuoteToken(): ?string
    {
        $token = $this->data['token']['token'] ?? null;

        return $token ? route('quote-service', ['token' => $token]) : null;
    }
}
