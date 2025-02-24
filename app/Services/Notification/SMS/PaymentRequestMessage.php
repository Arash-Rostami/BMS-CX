<?php

namespace App\Services\Notification\SMS;

use App\Models\Allocation;

class PaymentRequestMessage
{
    public string $invoice;
    public string $reference_number;
    public string $type;
    public string|bool $status;
    public ?string $state;


    public function __construct($record, $type = 'new', $status = false)
    {
        $this->invoice = $record->order->reference_number ?? $record->proforma_invoice_number ?? Allocation::find($record->reason_for_payment)->reason;
        $this->reference_number = $record->reference_number;
        $this->state = $record->status ?? null;
        $this->type = $type;
        $this->status = $status;
    }


    public function print()
    {
        $notificationMap = [
            'new' => $this->showCreatedMessage(),
            'edit' => $this->showEditedMessage(),
            'delete' => $this->showDeletedMessage(),
        ];

        return !$this->status ? $notificationMap[$this->type] : $this->showStatusMessage();
    }


    public function showCreatedMessage()
    {
        return $this->formatMessage(
            "A new payment request has been created.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice}"
        );
    }

    public function showEditedMessage()
    {
        return $this->formatMessage(
            "The payment request has been edited.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice}"
        );
    }

    public function showDeletedMessage()
    {
        return $this->formatMessage(
            "The payment request has been deleted.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice}"
        );
    }

    public function showStatusMessage()
    {
        return $this->fetchStatusMessage();
    }

    public function fetchStatusMessage(): string
    {
        $statusDescription = match ($this->state) {
            'allowed' => "initially allowed",
            'approved' => "finally approved",
            'rejected' => "declined",
            default => "{$this->state}",
        };

        return $this->formatMessage(
            "The payment request status has changed.",
            "Status: {$statusDescription}\nReference: {$this->reference_number}\nInvoice: {$this->invoice}"
        );
    }

    private function formatMessage(string $title, string $body): string
    {
        return implode("\n\n", [
            "BMS Notification Service",
            $title,
            $body,
        ]);
    }
}
