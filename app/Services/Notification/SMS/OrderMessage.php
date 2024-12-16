<?php

namespace App\Services\Notification\SMS;

class OrderMessage
{
    public string $reference_number;
    public string $invoice_number;
    public string $type;

    public function __construct($record, $type = 'new')
    {
        $this->reference_number = $record->reference_number;
        $this->invoice_number = $record->invoice_number ?? 'N/A';
        $this->type = $type;
    }

    public function print()
    {
        return ($this->type == 'new')
            ? $this->showCreatedMessage()
            : ($this->type == 'edit' ? $this->showEditedMessage() : $this->showDeletedMessage());
    }


    public function showCreatedMessage()
    {
        return $this->formatMessage(
            "A new order has been created.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice_number}"
        );
    }

    public function showEditedMessage()
    {
        return $this->formatMessage(
            "The order has been edited.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice_number}"
        );
    }

    public function showDeletedMessage()
    {
        return $this->formatMessage(
            "The order has been deleted.",
            "Reference: {$this->reference_number}\nInvoice: {$this->invoice_number}"
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
