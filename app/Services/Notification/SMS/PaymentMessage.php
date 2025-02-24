<?php

namespace App\Services\Notification\SMS;

class PaymentMessage
{
    public string $reference_number;
    public ?string $additionalDetail;
    public string $type;
    public bool $status;


    public function __construct($record, $type = 'new', $status = false)
    {
        $this->reference_number = $record->reference_number;
        $this->type = $type;
        $this->status = $status;

        $this->additionalDetail = $this->getAdditionalDetails($record);
    }

    private function getAdditionalDetails($record): ?string
    {
        $details = $record->paymentRequests->map(function ($paymentRequest) {
            if ($paymentRequest->proforma_invoice_number) {
                return $paymentRequest->proforma_invoice_number;
            }
            return data_get($paymentRequest, 'info.caseNumber');
        })->filter();

        return $details->isNotEmpty() ? $details->implode(', ') : null;
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
        return $this->formatMessage(
            "Your payment request" . ($this->additionalDetail ? " {$this->additionalDetail}" : "") . " has received payment.",
            "Reference: {$this->reference_number}"
        );
    }

    public function showCreatedMessage()
    {
        return $this->formatMessage(
            "A new payment" . ($this->additionalDetail ? " ({$this->additionalDetail})" : "") . " has been created.",
            "Reference: {$this->reference_number}"
        );
    }

    public function showEditedMessage()
    {
        return $this->formatMessage(
            "The payment" . ($this->additionalDetail ? " ({$this->additionalDetail})" : "") . " has been edited.",
            "Reference: {$this->reference_number}"
        );
    }

    public function showDeletedMessage()
    {
        return $this->formatMessage(
            "The payment" . ($this->additionalDetail ? " ({$this->additionalDetail})" : "") . " has been deleted.",
            "Reference: {$this->reference_number}"
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
