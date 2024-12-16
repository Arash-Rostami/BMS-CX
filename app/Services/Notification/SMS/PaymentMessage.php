<?php

namespace App\Services\Notification\SMS;

class PaymentMessage
{
    public string $reference_number;
    public string $type;
    public bool $status;


    public function __construct($record, $type = 'new', $status = false)
    {
        $this->reference_number = $record->reference_number;
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

        return !$this->status ? $notificationMap[$this->type] : $this->showRequesterMessage();
    }

    public function showRequesterMessage()
    {
        return $this->formatMessage("Your payment request has received payment.", "Reference: {$this->reference_number}");
    }

    public function showCreatedMessage()
    {
        return $this->formatMessage("A new payment has been created.", "Reference: {$this->reference_number}");
    }

    public function showEditedMessage()
    {
        return $this->formatMessage("The payment has been edited.", "Reference: {$this->reference_number}");
    }

    public function showDeletedMessage()
    {
        return $this->formatMessage("The payment has been deleted.", "Reference: {$this->reference_number}");
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
